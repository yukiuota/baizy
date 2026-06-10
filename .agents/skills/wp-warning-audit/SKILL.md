---
name: wp-warning-audit
description: WordPressテーマ・プラグインのPHP Warning / Deprecation / Notice を、指定PHPバージョン向けに静的解析で検出→一覧化→管理者確認→修正するスキル。「Warning」「Deprecation」「PHP移行」「サーバ移行」「PHPバージョンアップ」「php warning 確認」などのキーワードで必ず使用すること。
---

# WordPress PHP Warning 監査スキル

## 概要

PHP バージョンアップやサーバ移行時に発生しうる Warning / Deprecation / Notice / Fatal を
静的解析で洗い出し、管理者確認のうえ修正する。

**引数（オプション）**：
- `php_version` — 対象の PHP バージョン番号（例: `8.2`、`8.3`、`8.4`）
  省略時は現在の PHP バージョンを自動検出して使用する。

---

## Step 0: PHP バージョンの確定

### バージョン引数を確認する

ユーザーが `/wp-warning-audit 8.2` のように呼び出した場合、引数（`8.2`）を `TARGET_PHP` として使用する。

引数がなければ以下で現在バージョンを取得し、`TARGET_PHP` とする：

```bash
php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;"
```

### 利用可能な PHP バイナリを探す

```bash
# Homebrew でインストールされたバージョン一覧
ls /opt/homebrew/opt/ | grep -E '^php(@[0-9.]+)?$' | sort

# 各バイナリのパス候補
# php@8.1 → /opt/homebrew/opt/php@8.1/bin/php
# php@8.2 → /opt/homebrew/opt/php@8.2/bin/php
# php@8.3 → /opt/homebrew/opt/php@8.3/bin/php
# php (latest) → /opt/homebrew/bin/php
```

`TARGET_PHP` に対応するバイナリを `PHP_BIN` として設定する。

| 条件 | PHP_BIN |
|------|---------|
| `TARGET_PHP` に対応する `php@X.Y` バイナリが存在 | `/opt/homebrew/opt/php@X.Y/bin/php` |
| 現在のデフォルト PHP が `TARGET_PHP` と一致 | `/opt/homebrew/bin/php` or `php` |
| 対応バイナリが見つからない | ユーザーに通知し、インストール手順を案内してスキップ可能か確認 |

対応バイナリが存在しない場合のインストール案内：
```
brew install php@X.Y
```

### バージョンを確認して報告する

```bash
$PHP_BIN --version
```

「PHP X.Y を対象に Warning 監査を開始します」と報告してから次のステップへ。

---

## Step 1: 使用ツールの確認

以下を順に確認し、利用できるツールを把握する。

```bash
# 1. phpcs (PHPCompatibility 含むか確認)
./vendor/bin/phpcs -i 2>/dev/null | grep -i compat

# 2. PHPStan
./vendor/bin/phpstan --version 2>/dev/null || echo "phpstan: not found"

# 3. PHP 構文チェック用バイナリ
$PHP_BIN -l --version 2>/dev/null | head -1
```

**結果に応じて以下を実施する：**

### PHPCompatibility が未インストールの場合

```bash
composer require --dev phpcompatibility/php-compatibility
```

インストール後、`phpcs.xml` に以下を追加（またはコマンドライン引数で指定）：

```xml
<rule ref="PHPCompatibility"/>
<config name="testVersion" value="TARGET_PHP-"/>
```

---

## Step 2: 構文チェック（php -l）

**全 PHP ファイルに対して構文エラーをチェックする：**

```bash
find . -name "*.php" \
  -not -path "*/vendor/*" \
  -not -path "*/node_modules/*" \
  -not -path "*/.git/*" \
  | sort \
  | xargs -I{} $PHP_BIN -l {} 2>&1 \
  | grep -v "^No syntax errors" \
  | tee /tmp/wp_audit_syntax.txt

echo "--- syntax check done ---"
cat /tmp/wp_audit_syntax.txt | grep -c "Parse error\|Fatal error" || echo "syntax errors: 0"
```

---

## Step 3: PHPCompatibility チェック（phpcs）

指定 PHP バージョンに対する互換性問題を検出する。

```bash
./vendor/bin/phpcs \
  --standard=PHPCompatibility \
  --runtime-set testVersion TARGET_PHP- \
  --extensions=php \
  --ignore=vendor/,node_modules/ \
  --report=full \
  -p . 2>&1 | tee /tmp/wp_audit_compat.txt

echo "Exit: $?"
```

> `testVersion TARGET_PHP-` は「TARGET_PHP 以降のすべてのバージョン」を意味する。
> 特定バージョン範囲にする場合は `7.4-8.2` のように指定する。

---

## Step 4: PHPStan 静的解析（インストール済みの場合のみ）

PHPStan が使用可能な場合、PHP バージョンを指定して実行：

```bash
# phpstan.neon が存在する場合はそのまま実行
./vendor/bin/phpstan analyse \
  --level=5 \
  --php-version=TARGET_PHP_INT \
  --memory-limit=512M \
  2>&1 | tee /tmp/wp_audit_phpstan.txt
```

> `TARGET_PHP_INT` は `8.2` → `80200`、`8.3` → `80300` のように変換する。

---

## Step 5: WordPress 固有の Deprecated API チェック（phpcs WPCS）

```bash
./vendor/bin/phpcs \
  --standard=WordPress \
  --extensions=php \
  --ignore=vendor/,node_modules/ \
  --report=full \
  -p . 2>&1 | tee /tmp/wp_audit_wpcs.txt
```

deprecated な WordPress 関数・フックの使用を検出する。

---

## Step 6: Warning 一覧レポートの生成

Step 2〜5 の結果を集約し、以下の形式で **Markdown テーブル**としてユーザーに提示する：

```markdown
## PHP Warning 監査レポート — PHP {TARGET_PHP} 対象

### Summary

| カテゴリ | 件数 |
|---------|------|
| 構文エラー (Fatal/Parse) | N |
| PHP 互換性問題 (PHPCompatibility) | N |
| 静的解析 (PHPStan) | N |
| WP Deprecated API (WPCS) | N |
| **合計** | **N** |

---

### 詳細一覧

| # | ファイル | 行 | 重要度 | カテゴリ | 内容 |
|---|---------|---|--------|---------|------|
| 1 | app/setup/theme.php | 42 | ERROR | PHPCompat | `each()` は PHP 8.0 で削除 |
| 2 | app/functions/settings.php | 15 | WARNING | PHPCompat | `mb_convert_encoding()` 引数変更 |
| 3 | app/models/Post.php | 88 | WARNING | PHPStan | Property typed as string but null returned |
...

---

### 重要度の凡例
- **ERROR** : 実行時に Fatal / Exception が発生する可能性が高い（優先対応）
- **WARNING** : 動作に影響する可能性がある
- **NOTICE** : ベストプラクティス違反・軽微な問題
```

---

## Step 7: 管理者確認

レポートを提示したあと、以下を確認する：

**まず全体方針を確認：**
```
上記 N 件の問題が検出されました。
どのように対応しますか？

A) すべて修正する
B) カテゴリを選んで修正する（例: 「ERROR のみ」「PHPCompatibility のみ」）
C) 個別に確認しながら修正する
D) 今回は修正せず、レポートのみ保存する
```

選択肢 B の場合は、対象カテゴリをユーザーに確認する。
選択肢 C の場合は、1件ずつ「修正する / スキップ / 後回し」を確認しながら進む。

---

## Step 8: 修正の実施

承認された項目を以下の優先順で修正する：

### 8-1. 自動修正（phpcbf）

```bash
./vendor/bin/phpcbf \
  --standard=PHPCompatibility \
  --runtime-set testVersion TARGET_PHP- \
  --extensions=php \
  --ignore=vendor/,node_modules/ \
  . 2>&1
```

### 8-2. 手動修正（Codex によるコード編集）

自動修正できなかった項目を、`references/fix-patterns.md` のパターンを参考に手動で修正する。

修正前後の変更をユーザーに提示してから適用すること。
「個別確認モード（選択肢 C）」の場合は、1件ずつ確認を取る。

### 修正優先順位

1. **構文エラー（Fatal/Parse）** — 即時修正必須
2. **PHPCompatibility ERROR** — 対象バージョンで動作しない
3. **PHPCompatibility WARNING** — 対象バージョンで動作が変わる可能性
4. **PHPStan ERROR/WARNING** — 実行時エラーの原因になりうる
5. **WPCS WARNING（Deprecated API）** — 将来の WP バージョンで削除される
6. **NOTICE 類** — 余裕があれば対応

---

## Step 9: 修正後の確認

修正が完了したら、Step 2〜5 を再実行して残件数を確認する。

```bash
# 再チェック（構文）
find . -name "*.php" -not -path "*/vendor/*" -not -path "*/node_modules/*" \
  | xargs -I{} $PHP_BIN -l {} 2>&1 | grep -v "No syntax errors"

# 再チェック（PHPCompatibility）
./vendor/bin/phpcs \
  --standard=PHPCompatibility \
  --runtime-set testVersion TARGET_PHP- \
  --extensions=php \
  --ignore=vendor/,node_modules/ \
  -p . 2>&1 | tail -20
```

最終結果を以下の形式で報告する：

```
## 監査完了レポート

- 対象 PHP バージョン : X.Y
- 検出件数 : N 件
- 修正済み : N 件
- スキップ / 保留 : N 件
- 残存エラー : N 件

[残存エラーがある場合は内容を列挙]
```

---

## 参考

- [修正パターン集](references/fix-patterns.md)
- [PHPCompatibility ルール一覧](references/phpcompat-rules.md)
