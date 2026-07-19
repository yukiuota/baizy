# Baizy WordPress Theme

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。

- カスタムブロック（Gutenberg）は **baizy-custom-blocks プラグイン**として分離しています（テーマには含まれません）
- 共通スタイルは SCSS で管理し、Sass CLI でコンパイルします（`resources/common/scss/` → `resources/common/css/`）
- PHP は名前空間付きクラスで構成し、Composer オートロードで管理します（`Baizy\` 名前空間）

## 必要環境

- WordPress 6.5 以上（`theme.json` のスキーマが 6.5 なので目安）
- PHP 8.0 以上（`composer.json` に準拠）
- Node.js 18 以上（`package.json` の `engines` に準拠）
- pnpm 10.0 以上（パッケージマネージャー）

## セットアップ

### PHP（オートロード）

Composer のオートロードを利用しています。

```bash
composer install
composer dump-autoload
```

### コード品質チェック（phpcs）

WordPress コーディング規約に沿ったスタティック解析を実行できます。

```bash
# チェック実行
composer phpcs

# 自動修正
composer phpcbf
```

ルールセットは [`phpcs.xml`](phpcs.xml) で管理しています。

#### Claude Code スキル `/wp-phpcs`

Claude Code を使っている場合、`/wp-phpcs` スキルで phpcs チェック・自動修正・手動修正・WordPress 観点でのレビューまでを一括で実行できます。

```
/wp-phpcs
```

スキルの定義は [`.claude/skills/wp-phpcs-skill/SKILL.md`](.claude/skills/wp-phpcs-skill/SKILL.md) を参照してください。

### カスタムブロック（Gutenberg）

カスタムブロックは **baizy-custom-blocks プラグイン**として分離しました。
`wp-content/plugins/baizy-custom-blocks/` に配置して有効化してください。ブロックの追加・ビルド方法はプラグイン側の README を参照してください。

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

## 主な pnpm スクリプト

```bash
# SCSS の一回コンパイル
pnpm sass:build

# SCSS の監視（変更を検知して自動コンパイル）
pnpm sass:watch

# 本番アップ用の dist/baizy/ を生成（SCSS → dist 作成を一括実行）
pnpm dist

# Puppeteer によるパフォーマンス計測（URL省略時は localhost を計測）
pnpm perf:check
pnpm perf:localhost
```

## ディレクトリ構成（抜粋）

```
baizy/
├── app/
│   ├── functions/      フック登録・グローバル関数（functions.php から require）
│   ├── helpers/        ユーティリティクラス（ImageHelper, TemplateHelper）
│   ├── models/         データ取得クラス（PostModel, TaxonomyModel）
│   ├── plugins/        プラグイン連携用 CSS / JS
│   ├── services/       サービスクラス（ExternalLinksManager）
│   └── setup/          テーマ初期化クラス（ThemeSetup, Scripts, Customizer）
├── data/
│   └── field-groups/   ACF / SCF フィールドグループの JSON 同期ファイル
├── patterns/           ブロックパターン（PHP）
├── resources/
│   ├── archives/       アーカイブテンプレート
│   ├── common/
│   │   ├── scss/       共通スタイルの SCSS ソース
│   │   └── css/        コンパイル後の CSS（Sass CLI 出力）
│   ├── include/        ヘッダー・フッター・コンポーネントなどの分割テンプレート
│   ├── layouts/        レイアウトテンプレート
│   ├── pages/          固定ページテンプレート
│   ├── settings/       links.json（外部リンク管理）
│   └── single/         投稿詳細テンプレート
├── sample/             サンプルテンプレート（参照用）
├── vendor/             Composer オートロード
├── phpcs.xml           PHP CodeSniffer 設定（WordPress 規約）
├── functions.php       エントリポイント（require のみ）
├── style.css           テーマ情報（ヘッダー必須）
└── theme.json          テーマ設定
```

## テーマ仕様

### PHP 名前空間構成

`Baizy\` 名前空間配下のクラスを Composer の classmap オートロードで管理します（ファイル名は WordPress 規約の snake_case のため PSR-4 は使用しません）。クラスを追加したら `composer dump-autoload` を実行してください。

| 名前空間 | 役割 |
|---------|------|
| `Baizy\Setup\ThemeSetup` | テーマサポート追加・wp_head クリーンアップ・著者アーカイブ無効化 |
| `Baizy\Setup\Scripts` | CSS / JS のエンキュー管理 |
| `Baizy\Setup\Customizer` | カスタマイザーセクション（head / body タグ追加）登録 |
| `Baizy\Helpers\ImageHelper` | 画像 URL 生成・width/height 属性出力・SVG サイズ取得 |
| `Baizy\Helpers\TemplateHelper` | テンプレートパーツ読み込み |
| `Baizy\Models\PostModel` | 投稿データ取得 |
| `Baizy\Models\TaxonomyModel` | タクソノミー・ターム取得（背景色メタ含む） |
| `Baizy\Services\ExternalLinksManager` | JSON ファイルベースの外部リンク管理 |

### グローバル関数ラッパー

`app/functions/my_functions.php` にヘルパークラスへの薄いラッパーを定義しています。

```php
// 画像
baizy_img( 'path/to/image.png' )        // URL を返す
baizy_img_wh( 'path/to/image.png' )     // width / height 属性を出力
baizy_get_svg_dimensions( $svg_path )   // SVG の幅・高さを配列で返す

// テンプレートパーツ
baizy_template_part( 'slug' )
```

エスケープ出力は WordPress 標準の `esc_html()` / `esc_attr()` / `esc_url()` / `esc_js()` をそのまま使用します。

### 画像表示ヘルパー

```html
<picture>
  <source srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" media="(max-width: 750px)">
  <img src="<?php echo baizy_img('xx/xx.png'); ?>" srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" <?php baizy_img_wh('xx/xx.png'); ?> alt="">
</picture>

<!-- loading="lazy" あり（デフォルト） -->
<img src="<?php echo baizy_img('sample.jpg'); ?>" <?php baizy_img_wh('sample.jpg'); ?> alt="">

<!-- loading="lazy" なし -->
<img src="<?php echo baizy_img('hero.jpg'); ?>" <?php baizy_img_wh('hero.jpg', false); ?> alt="">
```

### カスタマイザー（head / body タグ追加）

外観 > カスタマイズ > タグ追加 にて、`<head>` 直後・`<body>` 直後に任意のコードを追加できます。

- Google Analytics / Google Tag Manager などのトラッキングコードの挿入を想定
- 管理者専用。入力値は `wp_kses` で `<script>` / `<meta>` / `<link>` などの許可タグのみ保持

実装: [app/setup/customizer.php](app/setup/customizer.php)

### 管理画面カスタマイズ

#### カラーパレット管理

**baizy-color-palette プラグイン**として分離しました。
`wp-content/plugins/baizy-color-palette/` に配置して有効化すると、外観 > カラーパレット からブロックエディタで使用するカラーパレットを追加・編集できます。詳細はプラグイン側の README を参照してください。

#### タームの背景色設定

**baizy-term-color プラグイン**として分離しました。
`wp-content/plugins/baizy-term-color/` に配置して有効化してください。対象タクソノミーの設定・テンプレートでの利用方法はプラグイン側の README を参照してください。

```php
// テンプレート内での利用（プラグイン未有効時も動くよう function_exists でガード）
$color = function_exists( 'get_term_background_color' ) ? get_term_background_color( $term_id ) : '';
```

#### 管理画面タクソノミーフィルター

カスタム投稿タイプの一覧画面にタクソノミー絞り込みセレクトボックスを追加します。`register_taxonomy` 済みの情報から自動で導出されるため、投稿タイプを追加しても設定は不要です（標準の category / post_tag は WP 標準 UI があるため除外）。

実装: [app/functions/admin.php](app/functions/admin.php)

### 外部リンク管理

`resources/settings/links.json` に URL を一元管理し、PHP またはショートコードから呼び出します。

```json
{
  "instagram": { "url": "https://www.instagram.com/example/" },
  "twitter":   { "url": "https://twitter.com/example" }
}
```

```php
// PHP から取得
$url = \Baizy\Services\ExternalLinksManager::get_url( 'instagram' );
```

```
<!-- ショートコードで取得 -->
[external_url key="instagram"]
```

実装: [app/services/external_links_manager.php](app/services/external_links_manager.php), [app/functions/global_links.php](app/functions/global_links.php)

### SEO 機能

実装: [app/functions/seo.php](app/functions/seo.php)

- **パンくずリスト**: `create_breadcrumb()` を呼び出すと Schema.org 対応のパンくずを出力します
- **noindex**: 404 / 指定カスタム投稿 / カテゴリー / タグページに `noindex, nofollow` を自動付与します
- **カスタム投稿メタディスクリプション**: アーカイブページにメタディスクリプションを出力します
- **HTML ミニファイ**: `start_html_minify()` を `get_header` アクションに追加することで有効化できます（デフォルト無効）

### ブロックエディター関連（テーマ側）

ブロックパターンカテゴリーの登録、投稿タイプごとの使用可能ブロック制限などのテーマ固有のブロックエディター調整を行います。
TypeScript 製カスタムブロック本体は baizy-custom-blocks プラグインへ移行済みです。

実装: [app/functions/custom_block.php](app/functions/custom_block.php)

### カスタムフィールドの JSON 同期

ACF（Advanced Custom Fields）と SCF（Smart Custom Fields）のフィールドグループ設定を `/data/field-groups/` ディレクトリで JSON 形式で管理します。

#### ACF JSON 同期

- ACF の標準 JSON 同期機能を使用
- フィールドグループの保存・読み込み先を `/data/field-groups/` に設定
- 管理画面での設定変更が自動的に JSON ファイルとして保存されます

#### SCF JSON エクスポート

- SCF のカスタムフィールド設定を `scf-{設定ID}.json` 形式でエクスポート
- `smart-cf` 投稿タイプの保存時に自動的にエクスポートされます

実装: [app/functions/acf_json_export.php](app/functions/acf_json_export.php)

## Chrome DevTools MCP

このリポジトリには、Chrome DevTools MCP（Model Context Protocol）を使うための設定ファイルが含まれています。

- パッケージ: `chrome-devtools-mcp`（バージョンは `package.json` を参照）
- 設定ファイル:
  - `mcp/servers.json`
  - `mcp/config.json`

### 利用可能なスクリプト

```bash
pnpm mcp:start
pnpm mcp:headless
pnpm mcp:dev
```

### 注意事項

- ブラウザ内容が MCP クライアントに公開されるため、機密情報や個人情報を含むページでは使用しないでください
- サンドボックス環境等で制限が出る場合は `--isolated=true` などのオプションを使用してください

## 本番デプロイ（FTP アップ用の dist 生成）

アップするファイルを目視で選別する必要はありません。以下のコマンドで、本番に必要なファイルだけを含む `dist/baizy/` が生成されます。

```bash
pnpm dist
```

FTP では **`dist/baizy/` の中身をそのまま本番の `wp-content/themes/baizy/` にアップ**してください。

### `pnpm dist` がやること

1. `pnpm sass:build` — SCSS のコンパイル（`resources/common/css/`）
2. [`scripts/create_dist.sh`](scripts/create_dist.sh) — 開発用ファイルを除外して `dist/baizy/` へコピーし、`composer install --no-dev --optimize-autoloader` で本番用の最小 `vendor/`（オートローダーのみ）を生成

### 除外されるもの（抜粋）

`node_modules/`・`.git/`・`.claude/`・`mcp/`・`sample/`・`scripts/`・`baizy-custom-blocks/`（プラグインは別途デプロイ）・SCSS ソース（`resources/common/scss/`）・各種設定ファイル（`phpcs.xml`, `package.json`, `pnpm-lock.yaml` など）・ドキュメント / レポート類・`.DS_Store` / `*.log` / `*.map`

除外リストの正式な定義は [`scripts/create_dist.sh`](scripts/create_dist.sh) を参照してください。除外を変更したい場合もこのスクリプトを編集します。

> **注意**: `vendor/` は開発ツールだけでなく、`app/` 以下のクラス・関数を読み込む Composer オートローダーを含むため**本番でも必須**です。`pnpm dist` は開発用パッケージ（phpcs 等）を除いた本番用 `vendor/` を自動生成するので、`dist/baizy/` に含まれる `vendor/` をそのままアップすれば問題ありません。

`dist/` は Git 管理外（`.gitignore` 済み）です。

---

## ライセンス

GPL-2.0-or-later

詳細は `LICENSE` を参照してください。
