# Baizy WordPress Theme

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。

- カスタムブロック（Gutenberg）は React + TypeScript で実装し、Vite でビルドします
- ビルド成果物はブロックエディタ用に読み込まれます（`app/blocks/build/custom-blocks.js`）
- 共通スタイルは SCSS で管理し、Sass CLI でコンパイルします（`resources/common/scss/` → `resources/common/css/`）

## 必要環境

- WordPress 6.5 以上（`theme.json` のスキーマが 6.5 なので目安）
- PHP 7.4 以上（`composer.json` に準拠）
- Node.js 18 以上（`package.json` の `engines` に準拠）
- pnpm 10.0 以上（パッケージマネージャー）

## セットアップ

### PHP（オートロード）

Composer のオートロードを利用しています。

```bash
composer install
composer dump-autoload
```

### カスタムブロック（Gutenberg）

1) 依存関係をインストール

```bash
pnpm install
```

2) 本番ビルド（圧縮あり）

```bash
pnpm build
```

3) 監視ビルド（変更を検知して再ビルド）

```bash
pnpm start
```

#### ビルド仕様

- エントリ: `app/blocks/src/index.tsx`
- 出力先: `app/blocks/build/custom-blocks.js`
- WordPress / React は外部依存として扱います（`wp.*` グローバルを参照）

### 共通 SCSS

`resources/common/scss/` 以下の SCSS を `resources/common/css/` へコンパイルします。

1) 一回だけコンパイル

```bash
pnpm sass:build
```

2) 監視（変更を検知して自動コンパイル）

```bash
pnpm sass:watch
```

#### コンパイル対象

| 入力 | 出力 |
|------|------|
| `resources/common/scss/common.scss` | `resources/common/css/common.css` |
| `resources/common/scss/editor-style.scss` | `resources/common/css/editor-style.css` |
| `resources/common/scss/pages/home.scss` | `resources/common/css/home.css` |

ブロックビルドと SCSS 監視を同時に動かす場合は、ターミナルを2つ開いて `pnpm start` と `pnpm sass:watch` を並行実行してください。

## 主な pnpm スクリプト

```bash
# ブロックの本番ビルド
pnpm build

# ブロックの監視ビルド
pnpm start

# SCSS の一回コンパイル
pnpm sass:build

# SCSS の監視（変更を検知して自動コンパイル）
pnpm sass:watch

# Puppeteer によるパフォーマンス計測（URL省略時は localhost を計測）
pnpm perf:check
pnpm perf:localhost
```

## ディレクトリ構成（抜粋）

- `app/blocks/src/` ブロック関連ソース（React/TS）
- `app/blocks/build/` ブロックのビルド出力先（`custom-blocks.js`）
- `app/functions/` functions.php 相当の機能群（Composer オートロード）
- `resources/common/scss/` 共通スタイルの SCSS ソース
- `resources/common/css/` コンパイル後の CSS（Sass CLI 出力）
- `resources/` 共通 CSS/JS・ページテンプレートなど
- `include/` 分割テンプレート
- `patterns/` ブロックパターン（PHP）
- `style.css` テーマ情報（ヘッダー必須）
- `theme.json` テーマ設定

## テーマ仕様

### カスタムブロックの読み込み

ブロックエディタ（管理画面）で `app/blocks/build/custom-blocks.js` を読み込みます。

### カスタムフィールドのJSON同期

ACF（Advanced Custom Fields）とSCF（Smart Custom Fields）のフィールドグループ設定を `/data/field-groups/` ディレクトリでJSON形式で管理します。

#### ACF JSON同期

- ACFの標準JSON同期機能を使用
- フィールドグループの保存・読み込み先を `/data/field-groups/` に設定
- 管理画面での設定変更が自動的にJSONファイルとして保存されます

#### SCF JSONエクスポート

- SCFのカスタムフィールド設定を `scf-{設定ID}.json` 形式でエクスポート
- `smart-cf` 投稿タイプの保存時に自動的にエクスポートされます

実装: [app/functions/acf_json_export.php](app/functions/acf_json_export.php)

### 画像表示ヘルパー

```html
<picture>
  <source srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" media="(max-width: 750px)">
  <img src="<?php echo baizy_img('xx/xx.png'); ?>" srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" <?php baizy_img_wh('xx/xx.png'); ?> alt="">
</picture>

例:
<!-- loading="lazy"あり(デフォルト) -->
<img src="<?php echo baizy_img('sample.jpg'); ?>" <?php baizy_img_wh('sample.jpg'); ?> alt="">

<!-- loading="lazy"なし -->
<img src="<?php echo baizy_img('hero.jpg'); ?>" <?php baizy_img_wh('hero.jpg', false); ?> alt="">
```

## Chrome DevTools MCP

このリポジトリには、Chrome DevTools MCP（Model Context Protocol）を使うための設定ファイルが含まれています。

- パッケージ: `chrome-devtools-mcp`（バージョンは `package.json` を参照）
- 設定ファイル:
  - `mcp-servers.json`
  - `mcp-config.json`

### 利用可能なスクリプト

```bash
pnpm mcp:start
pnpm mcp:headless
pnpm mcp:dev
```

### 注意事項

- ブラウザ内容が MCP クライアントに公開されるため、機密情報や個人情報を含むページでは使用しないでください
- サンドボックス環境等で制限が出る場合は `--isolated=true` などのオプションを使用してください

## ライセンス

GPL-2.0-or-later

詳細は `LICENSE` を参照してください。