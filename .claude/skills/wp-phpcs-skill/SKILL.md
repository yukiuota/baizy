---
name: wp-phpcs
description: WordPress テーマ・プラグイン開発時に PHP コーディング規約（WordPress Coding Standards）を自動でチェック・修正するスキル。「phpcs」「phpcbf」「コーディング規約」「WPCSチェック」「WordPress標準」「コードチェック」などのキーワードが出たとき、またはコードレビューや品質チェックの文脈で必ず使用すること。composer経由のphpcs/phpcbfを使い、自動修正 → 手動修正 → 最終確認の順で完全に問題をゼロにすることを目的とする。
---

# WordPress PHPCS 自動チェック・修正スキル

## 概要

WordPress テーマ/プラグインのコードが WordPress Coding Standards (WPCS) に準拠しているかを確認し、問題をすべて解消するスキル。

**実行フロー：**
1. `composer phpcs` でチェック
2. エラーがあれば `composer phpcbf` で自動修正
3. 残ったエラーを Claude Code で手動修正
4. WordPress の観点での最終レビュー
5. 再チェックして問題ゼロを確認

---

## Step 1: 初回チェック

```bash
composer phpcs 2>&1 | tee /tmp/phpcs_result.txt
echo "Exit code: $?"
```

出力を解析して以下を把握する：
- **エラー数 (ERROR)** : 必ず修正が必要
- **警告数 (WARNING)** : 可能な限り修正
- **対象ファイル** : 問題のあるファイルの一覧

エラーがゼロなら「✅ コーディング規約に問題はありません」と報告して終了。

---

## Step 2: 自動修正（phpcbf）

エラー/警告がある場合：

```bash
composer phpcbf 2>&1 | tee /tmp/phpcbf_result.txt
echo "Exit code: $?"
```

phpcbf 後に再チェック：

```bash
composer phpcs 2>&1 | tee /tmp/phpcs_after_fix.txt
```

自動修正で解決したエラー数と、残ったエラー数をユーザーに報告する。

---

## Step 3: 手動修正（残ったエラー対応）

残ったエラーを `/tmp/phpcs_after_fix.txt` から解析し、ファイルと行番号を特定して修正する。

### よくある手動修正パターン

詳細は `references/common-errors.md` を参照。主なカテゴリ：

- **コメント・ドキュメント**: ファイルヘッダーコメント、関数 docblock 不足
- **セキュリティ**: nonce 検証、sanitize/escape 漏れ
- **WordPress API**: プレフィックス不足、direct DB クエリ
- **フォーマット**: インデント、空白、行末スペース

修正後、必ず再チェックを実行：

```bash
composer phpcs 2>&1
```

---

## Step 4: WordPress 観点でのレビュー

phpcs が通ったあと、WordPress 固有の品質を確認する。
詳細は `references/wp-review-checklist.md` を参照。

主なチェックポイント：
- [ ] すべての関数・クラスにテーマ/プラグインのプレフィックスがある
- [ ] `$_GET`/`$_POST` を使うところで nonce 検証している
- [ ] ユーザー入力を DB に保存する前に sanitize している
- [ ] HTML 出力前に escape している（`esc_html()`, `esc_attr()` 等）
- [ ] 直接 SQL を書いている場合は `$wpdb->prepare()` を使っている
- [ ] `wp_enqueue_scripts` フックで正しく assets を読み込んでいる

---

## Step 5: 最終確認と報告

```bash
composer phpcs 2>&1
```

結果をもとに以下の形式で報告：

```
## WordPress PHPCS チェック結果

### 最終ステータス
✅ エラー: 0件 / ⚠️ 警告: N件

### 実施した修正
- phpcbf による自動修正: X件
- 手動修正: Y件（ファイル名と内容を列挙）

### WordPress レビュー
- セキュリティ: ✅/⚠️
- コーディング規約: ✅/⚠️
- 特記事項: ...
```

---

## トラブルシューティング

### composer コマンドが見つからない場合
```bash
# composer.json に phpcs スクリプトがあるか確認
cat composer.json | grep -A 10 '"scripts"'

# なければ直接実行
./vendor/bin/phpcs --standard=WordPress .
./vendor/bin/phpcbf --standard=WordPress .
```

### phpcs がインストールされていない場合
```bash
composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs
./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs
```

### 設定ファイルの確認
```bash
# phpcs.xml または .phpcs.xml を確認
cat phpcs.xml 2>/dev/null || cat .phpcs.xml 2>/dev/null
```
