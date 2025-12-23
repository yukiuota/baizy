# Baizy WordPress Theme

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。

- カスタムブロック（Gutenberg）は React + TypeScript で実装し、Vite でビルドします
- ビルド成果物はブロックエディタ用に読み込まれます（`app/blocks/build/custom-blocks.js`）

## 必要環境

- WordPress 6.5 以上（`theme.json` のスキーマが 6.5 なので目安）
- PHP 7.4 以上（`composer.json` に準拠）
- Node.js 18 以上（`package.json` の `engines` に準拠）

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
npm install
```

2) 本番ビルド（圧縮あり）

```bash
npm run build
```

3) 監視ビルド（変更を検知して再ビルド）

```bash
npm run start
```

#### ビルド仕様

- エントリ: `app/blocks/src/index.tsx`
- 出力先: `app/blocks/build/custom-blocks.js`
- WordPress / React は外部依存として扱います（`wp.*` グローバルを参照）

※ `webpack.config.js` はリポジトリに残っていますが、現状の npm スクリプトは Vite ビルド（`vite.config.ts`）を使用します。

## 主な npm スクリプト

```bash
# ブロックの本番ビルド
npm run build

# ブロックの監視ビルド
npm run start

# Puppeteer によるパフォーマンス計測（URL省略時は localhost を計測）
npm run perf:check
npm run perf:localhost
```

## ディレクトリ構成（抜粋）

- `app/blocks/src/` ブロック関連ソース（React/TS）
- `app/blocks/build/` ブロックのビルド出力先（`custom-blocks.js`）
- `app/functions/` functions.php 相当の機能群（Composer オートロード）
- `public/` 共通 CSS/JS・ページテンプレートなど
- `include/` 分割テンプレート
- `patterns/` ブロックパターン（PHP）
- `style.css` テーマ情報（ヘッダー必須）
- `theme.json` テーマ設定

## テーマ仕様

### カスタムブロックの読み込み

ブロックエディタ（管理画面）で `app/blocks/build/custom-blocks.js` を読み込みます。

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
npm run mcp:start
npm run mcp:headless
npm run mcp:dev
```

### 注意事項

- ブラウザ内容が MCP クライアントに公開されるため、機密情報や個人情報を含むページでは使用しないでください
- サンドボックス環境等で制限が出る場合は `--isolated=true` などのオプションを使用してください

## ライセンス

GPL-2.0-or-later

詳細は `LICENSE` を参照してください。