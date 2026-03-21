# Baizy WordPress Theme

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。

- カスタムブロック（Gutenberg）は React + TypeScript で実装し、Vite でビルドします
- ビルド成果物はブロックエディタ用に読み込まれます（`app/blocks/build/custom-blocks.js`）
- 共通スタイルは SCSS で管理し、Sass CLI でコンパイルします（`resources/common/scss/` → `resources/common/css/`）
- PHP は名前空間付きクラスで構成し、Composer オートロードで管理します（`Baizy\` 名前空間）

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

#### 実装済みカスタムブロック

| ブロック名 | ソース |
|-----------|--------|
| `another-block` | `app/blocks/src/blocks/another-block/` |
| `box-flex` | `app/blocks/src/blocks/box-flex/` |
| `qa-block` | `app/blocks/src/blocks/qa-block/` |
| `sample-block` | `app/blocks/src/blocks/sample-block/` |
| `ttl-block` | `app/blocks/src/blocks/ttl-block/` |

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

```
baizy/
├── app/
│   ├── admin/          管理画面用 CSS / JS / ビュー
│   ├── blocks/
│   │   ├── src/        カスタムブロックのソース（React / TypeScript）
│   │   └── build/      ビルド出力（custom-blocks.js）
│   ├── controllers/    ページ別コントローラー
│   ├── functions/      フック登録・グローバル関数（functions.php から require）
│   ├── helpers/        ユーティリティクラス（ImageHelper, TemplateHelper, EscapeHelper）
│   ├── models/         データ取得クラス（PostModel, TaxonomyModel）
│   ├── plugins/        プラグイン連携用 CSS / JS
│   ├── services/       サービスクラス（ExternalLinksManager）
│   ├── setup/          テーマ初期化クラス（ThemeSetup, Scripts, Customizer）
│   └── widgets/        カスタムウィジェット
├── data/
│   └── field-groups/   ACF / SCF フィールドグループの JSON 同期ファイル
├── include/            分割テンプレート
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
├── functions.php       エントリポイント（require のみ）
├── style.css           テーマ情報（ヘッダー必須）
└── theme.json          テーマ設定
```

## テーマ仕様

### PHP 名前空間構成

`Baizy\` 名前空間配下のクラスを Composer PSR-4 オートロードで管理します。

| 名前空間 | 役割 |
|---------|------|
| `Baizy\Setup\ThemeSetup` | テーマサポート追加・wp_head クリーンアップ・著者アーカイブ無効化 |
| `Baizy\Setup\Scripts` | CSS / JS のエンキュー管理 |
| `Baizy\Setup\Customizer` | カスタマイザーセクション（head / body タグ追加）登録 |
| `Baizy\Helpers\ImageHelper` | 画像 URL 生成・width/height 属性出力・SVG サイズ取得 |
| `Baizy\Helpers\TemplateHelper` | テンプレートパーツ読み込み |
| `Baizy\Helpers\EscapeHelper` | `esc_html` / `esc_attr` / `esc_url` / `esc_js` のラッパー |
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

// エスケープ出力
e( $str )       // esc_html して echo
e_attr( $str )  // esc_attr して echo
e_url( $str )   // esc_url して echo
e_js( $str )    // esc_js して echo
```

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

外観 > カラーパレット から、ブロックエディタで使用するカラーパレットを管理画面で追加・編集できます。

- 保存したカラーは `wp_theme_json_data_theme` フィルターで theme.json のパレットに動的にマージされます
- WordPress のデフォルトカラーパレットは非表示にします

実装: [app/functions/admin.php](app/functions/admin.php), [app/admin/views/color_palette_settings.php](app/admin/views/color_palette_settings.php)

#### タームの背景色設定

タクソノミーの編集画面にカラーピッカーを追加し、ターム単位で背景色を設定できます。

```php
// functions.php 等で対象タクソノミーを指定
add_term_background_color_field( 'sample-category' );
// 複数の場合
add_term_background_color_field( [ 'news-category', 'news-tag' ] );

// テンプレート内での利用
$color = get_term_background_color( $term_id ); // '#ffffff'
$style = get_term_background_style( $term_id ); // 'background-color: #ffffff;'
```

実装: [app/functions/term_color.php](app/functions/term_color.php)

#### 管理画面タクソノミーフィルター

カスタム投稿タイプの一覧画面にタクソノミー絞り込みセレクトボックスを追加します。`admin.php` 内の `get_post_type_taxonomies_config()` に投稿タイプ → タクソノミーの対応を追記して設定します。

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

### カスタムブロックの読み込み

ブロックエディタ（管理画面）で `app/blocks/build/custom-blocks.js` を読み込みます。

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
