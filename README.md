# Baizy WordPress Theme

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。

- カスタムブロック（Gutenberg）は **baizy-custom-blocks プラグイン**として分離しています（テーマには含まれません）
- 共通スタイルは SCSS で管理し、Sass CLI でコンパイルします（`resources/common/scss/` → `resources/common/css/`）
- PHP は名前空間付きクラスで構成し、Composer オートロードで管理します（`Baizy\` 名前空間）

## 必要環境

- WordPress 6.6 以上（`theme.json` の `version: 3` が 6.6 以上で必要）
- PHP 8.0 以上（`composer.json` に準拠）
- Node.js 22.12.0 以上（`package.json` の `engines` に準拠）
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

PHP のバージョンアップ前に Warning / Deprecation を洗い出す `/wp-warning-audit` スキルもあります（[`.claude/skills/wp-warning-audit/SKILL.md`](.claude/skills/wp-warning-audit/SKILL.md)）。

#### Claude Code スキル `/wp-editable-html`

PHP テンプレートで作ったページを、WordPress 7.1 以降の「カスタム HTML ブロックの中に編集可能なブロックを埋め込む」形式に変換します。デザインの HTML は固定したまま、見出し・段落・リスト・画像だけを管理画面で編集できるようになります。

```
/wp-editable-html resources/pages/about.php
```

- 出力は `block-markup/{slug}.html`（管理画面のコードエディターに貼り付ける）。比較用に元の HTML を `block-markup/{slug}.original.html` に保存します
- ローカルの WordPress（`http://localhost:8080`）のエディターで、ブロックの検証・保存し直したときの書き換わり・元の HTML との DOM 差分を確認します（puppeteer を使用。ログインは docker compose の wpcli で一時 Cookie を発行するか、`WP_USER` / `WP_PASS` を指定）
- 検証だけを単独で実行することもできます: `node .claude/skills/wp-editable-html/scripts/validate-blocks.cjs block-markup/about.html --compare block-markup/about.original.html`

手順と変換ルールは [`.claude/skills/wp-editable-html/SKILL.md`](.claude/skills/wp-editable-html/SKILL.md) を参照してください。

#### コーディングルール

テーマのコーディングルールは [`.claude/rules/`](.claude/rules/) にまとめています。Claude Code は、編集するファイルに応じて該当するルールを自動で読み込みます。

| ファイル | 対象 |
|---------|------|
| `general.md` | 全体（ディレクトリの役割・ファイル追加時の手順・編集しないファイル） |
| `php.md` | `app/` ・ `functions.php` |
| `templates.md` | `resources/` ・ `patterns/` などのテンプレート |
| `scss.md` | `resources/common/scss/` |
| `javascript.md` | `resources/common/js/` ・ `app/plugins/` の JS |

#### Claude Code の hooks

[`.claude/settings.json`](.claude/settings.json) に hooks を設定しており、Claude Code がファイルを編集すると次の処理が自動で実行されます（スクリプトは [`.claude/hooks/`](.claude/hooks/)）。

| タイミング | 対象 | 処理 |
|-----------|------|------|
| 編集前 | `resources/common/css/*.css` ・ `data/field-groups/*.json` ・ `vendor/` ・ `node_modules/` ・ `dist/` | 編集をブロックし、代わりに編集すべき場所を Claude に伝える |
| 編集後 | すべての PHP | `php -l` で構文チェック |
| 編集後 | `app/` ・ `functions.php` | `phpcbf` で自動修正し、残った `phpcs` の指摘を Claude に返して修正させる |
| 編集後 | `resources/common/scss/*.scss` | `pnpm sass:build` を実行し、失敗したらエラーを Claude に返す |

hooks の実行には `jq` が必要です。

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

# ローカル WordPress の主要画面を巡回して HTTP ステータスと PHP エラーを確認（URL省略時は http://localhost:8080）
pnpm smoke
pnpm smoke http://localhost:10013

# Puppeteer によるパフォーマンス計測（URL省略時は http://localhost:8080/ を計測）
pnpm perf:check http://localhost:8080/
pnpm perf:localhost
```

### スモークテスト（`pnpm smoke`）

[`scripts/smoke.sh`](scripts/smoke.sh) は、トップ・検索・404 と、REST API から拾った投稿タイプごとの詳細 / アーカイブ、タクソノミーごとのタームアーカイブを巡回し、次を確認します。投稿タイプやタクソノミーを追加してもスクリプトの修正は不要です。

- HTTP ステータス（404 ページは 404、それ以外は 200）
- HTML に PHP の Warning / Notice / Deprecated / Fatal error が出ていないか（`WP_DEBUG` 有効が前提）
- `../../docker-compose.yml` があれば、巡回中に `wordpress` コンテナのログへ出た PHP エラー（場所が違う場合は `BAIZY_COMPOSE_FILE` で指定）

テンプレートや PHP を変更したあとの動作確認に使います。NG があれば終了コード 1 で終わります。

## ディレクトリ構成（抜粋）

```
baizy/
├── .claude/
│   ├── hooks/          編集時に自動実行するチェック（Claude Code hooks）
│   ├── rules/          コーディングルール（Claude Code 用）
│   ├── skills/         Claude Code スキル（wp-phpcs, wp-warning-audit, wp-editable-html）
│   └── settings.json   hooks と許可するコマンドの設定
├── .mcp.json           Chrome DevTools MCP の設定（Claude Code が自動で読み込む）
├── app/
│   ├── controllers/    ビューへ渡すデータの組み立て（TopController, SampleController）
│   ├── functions/      フック登録・グローバル関数（Composer の files オートロードで読み込み）
│   ├── helpers/        ユーティリティクラス（ImageHelper, TemplateHelper）
│   ├── languages/      翻訳ファイル
│   ├── models/         データ取得クラス（PostModel, PageMetaModel, TaxonomyModel）
│   ├── plugins/        プラグイン連携用 CSS / JS（Contact Form 7）
│   ├── services/       サービスクラス（ExternalLinksManager）
│   └── setup/          テーマ初期化クラス（ThemeSetup, Scripts, Customizer, Security）
├── data/
│   └── field-groups/   ACF / SCF フィールドグループの JSON 同期ファイル（.htaccess で直接アクセス禁止）
├── patterns/           ブロックパターン（PHP）
├── resources/
│   ├── archives/       アーカイブテンプレート
│   ├── common/
│   │   ├── scss/       共通スタイルの SCSS ソース
│   │   └── css/        コンパイル後の CSS（Sass CLI 出力）
│   ├── img/            テーマ画像（baizy_img() の参照先。必要に応じて作成）
│   ├── include/        ヘッダー・フッター・コンポーネントなどの分割テンプレート
│   ├── layouts/        レイアウトテンプレート（index.php がページ種別ごとのルーター）
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
| `Baizy\Setup\ThemeSetup` | テーマサポート追加・wp_head クリーンアップ・wpautop 無効化・投稿スラッグの body class 追加 |
| `Baizy\Setup\Scripts` | CSS / JS のエンキュー管理（body class と同名のページ別 CSS を自動読み込み） |
| `Baizy\Setup\Customizer` | カスタマイザーセクション（head / body タグ追加）登録 |
| `Baizy\Setup\Security` | ユーザー情報の外部公開制限（REST users / users サイトマップ / 著者アーカイブ / oEmbed 著者情報の無効化）・XML-RPC の無効化 |
| `Baizy\Controllers\TopController` | トップページのビューへ渡すデータの組み立て |
| `Baizy\Controllers\SampleController` | コントローラーの書き方見本 |
| `Baizy\Helpers\ImageHelper` | 画像 URL 生成・width/height 属性出力・SVG サイズ取得 |
| `Baizy\Helpers\TemplateHelper` | テンプレートパーツ読み込み |
| `Baizy\Models\PostModel` | 投稿データ取得 |
| `Baizy\Models\PageMetaModel` | ACF フィールドの取得・整形（ACF 未有効時も安全に動作） |
| `Baizy\Models\TaxonomyModel` | 投稿に紐づくタームの取得 |
| `Baizy\Services\ExternalLinksManager` | JSON ファイルベースの外部リンク管理 |

### テーマ関数リファレンス

テーマで定義しているグローバル関数の一覧です。すべて `baizy_` プレフィックスを付けています。定義は `app/functions/` 以下（検索フォーム関連のみ `resources/include/search/search.php`）にあります。

#### テンプレートで呼び出す関数

| 関数 | 戻り値 | 内容 | 定義場所 |
|------|--------|------|---------|
| `baizy_template_part( $slug, $args = array() )` | なし（出力） | テンプレートパーツを読み込む。`$slug` はテーマルートからのパス（拡張子なし）。`$args` はテンプレート側で `$args` として受け取れる。`TemplateHelper::part()` のラッパー | `my_functions.php` |
| `baizy_img( $path )` | `string` | `resources/img/` 以下の画像の URL（エスケープ済み）を返す。ファイルがなければ空文字 | `my_functions.php` |
| `baizy_img_wh( $path, $lazy = true )` | なし（出力） | 画像の `width` / `height` 属性を出力する。`$lazy` が `true` なら `loading="lazy"` も付ける。SVG にも対応 | `my_functions.php` |
| `baizy_get_svg_dimensions( $svg_file_path )` | `array\|false` | SVG ファイル（絶対パス）の `width` / `height` を返す。属性がなければ `viewBox` から取得する | `my_functions.php` |
| `baizy_custom_pagination()` | なし（出力） | メインクエリのページネーションを `<div id="js-pagination" class="pagination">` で出力する。前後リンクの文言は `prev` / `next` | `pagination.php` |
| `baizy_display_prev_next_post_links()` | なし（出力） | 投稿詳細ページで、前後の記事へのリンク（`Prev` / `Next`）を出力する | `pagination.php` |
| `baizy_create_breadcrumb()` | なし（出力） | Schema.org（BreadcrumbList）に対応したパンくずリストを出力する | `seo.php` |
| `baizy_breadcrumb_items()` | `array` | パンくずの項目を `[ 'name' => 名前, 'url' => URL ]` の配列で返す。`url` が空の項目はリンクなしで表示する。独自のマークアップでパンくずを組みたいときに使う | `seo.php` |
| `baizy_display_terms_of_post( $taxonomy, $post_id = null )` | `array` | 投稿に紐づくタームを配列で返す。`$post_id` を省略すると現在の投稿。`TaxonomyModel::get_terms_of_post()` のラッパー | `taxonomy_display.php` |
| `baizy_custom_search_form( $args = array() )` | なし（出力） | 検索フォームを出力する。`$args` で `placeholder` / `button_text` / `form_class` を変更できる | `search/search.php` |

```php
// タームの取得（各要素のキー: name, name_escaped, slug, slug_escaped, term_id, taxonomy, link, link_escaped）
foreach ( baizy_display_terms_of_post( 'news-category' ) as $term ) {
	echo '<a href="' . $term['link_escaped'] . '">' . $term['name_escaped'] . '</a>';
}

// 検索フォーム
baizy_custom_search_form(
	array(
		'placeholder' => 'ブログ内検索',
		'button_text' => '検索する',
		'form_class'  => 'header-search-form',
	)
);
```

> `baizy_custom_search_form()` などの検索関連の関数は、テンプレート（`resources/include/search/search.php`）の中で定義しています。そのため、このファイルを `baizy_template_part()` で読み込んだ後でしか使えません。

#### ショートコード

| ショートコード | 処理する関数 | 内容 | 定義場所 |
|--------------|------------|------|---------|
| `[external_url key="instagram"]` | `baizy_external_url_shortcode()` | `resources/settings/links.json` に登録した URL を返す。キーが未指定・未登録のときは空文字（`WP_DEBUG` 有効時は HTML コメントでエラーを出す） | `global_links.php` |
| `[custom_search placeholder="" button_text="" form_class=""]` | `baizy_custom_search_shortcode()` | 検索フォームを出力する（`search.php` 読み込み後のみ有効） | `search/search.php` |

#### フックで自動実行される関数

テンプレートから呼ぶ必要はありません。挙動を変えたいときは各関数を編集してください。

| 関数 | フック | 内容 | 定義場所 |
|------|--------|------|---------|
| `baizy_register_post_types()` | `init` | カスタム投稿タイプ `news` / `sample` と、タクソノミー `news-category` / `news-tag` / `sample-category` を登録する | `post_types.php` |
| `baizy_remove_post_support()` | `init` | 投稿（`post`）の「投稿フォーマット」を無効にする | `post_types.php` |
| `baizy_custom_posts_per_page( $query )` | `pre_get_posts` | `news` アーカイブの表示件数を10件にする | `post_types.php` |
| `baizy_set_default_news_category( $post_id, $post, $_update )` | `wp_insert_post` | `news` の投稿にカテゴリーが未設定なら、スラッグ `notice` のタームを設定する | `post_types.php` |
| `baizy_register_theme_widget_areas()` | `widgets_init` | ウィジェットエリア（`header-menu` / `footer-1` / `footer-2` / `footer-3`）を登録する | `widget.php` |
| `baizy_register_theme_menus()` | `init` | メニューの位置（`header-menu` / `footer-menu`）を登録する | `widget.php` |
| `baizy_register_block_pattern_categories()` | `init` | ブロックパターンのカテゴリー（`sample` / `baizy-hero` など）を登録する | `custom_block.php` |
| `baizy_restrict_blocks_for_cases( $allowed_blocks, $block_editor_context )` | `allowed_block_types_all` | `news` の編集画面で使えるブロックを、テキスト系・メディア系の標準ブロックに制限する | `custom_block.php` |
| `baizy_enqueue_heading_script()` | `current_screen` | `news` の編集画面のときだけ、下の `baizy_modify_heading_block_attributes()` を `admin_footer` に登録する | `custom_block.php` |
| `baizy_modify_heading_block_attributes()` | `admin_footer` | エディター内の見出しブロックに `post-heading01`〜`06` のクラスを付けるスクリプトを出力する | `custom_block.php` |
| `baizy_include_body_top()` | `wp_body_open` | `<body>` 直後にカスタマイザーで設定したコード（`resources/include/tags/body_top.php`）を出力する | `seo.php` |
| `baizy_single_noindex( $robots )` | `wp_robots` | 404 / カテゴリー / タグページに `noindex, nofollow` を付ける | `seo.php` |
| `baizy_feed_request( $vars )` | `request` | 投稿タイプ指定のない RSS フィードの対象を `news` にする | `seo.php` |
| `baizy_output_custom_post_meta_description()` | `wp_head` | カスタム投稿のアーカイブページに、`register_post_type` の `description` をメタディスクリプションとして出力する | `seo.php` |
| `baizy_comment_redirect( $location )` | `comment_post_redirect` | コメント投稿後のリダイレクト先に `comment_posted=1` を付ける（完了メッセージの表示用） | `comment.php` |
| `baizy_enqueue_dashicons()` | `admin_enqueue_scripts` | 管理画面で Dashicons を読み込む | `admin.php` |
| `baizy_remove_menus()` | `admin_menu` | ダッシュボードの不要なウィジェット（クイックドラフト・WordPress ニュース・アクティビティ）と「コメント」メニューを非表示にする | `admin.php` |
| `baizy_add_custom_taxonomies_term_filter()` | `restrict_manage_posts` | 投稿一覧画面に、タクソノミーで絞り込むセレクトボックスを追加する | `admin.php` |
| `baizy_filter_posts_by_custom_taxonomy( $query )` | `pre_get_posts` | 上のセレクトボックスで選んだタームで投稿一覧を絞り込む | `admin.php` |
| `baizy_enqueue_cf7_assets()` | `wp_enqueue_scripts` | Contact Form 7 の CSS / JS を、フォームのショートコードがあるページでだけ読み込む | `plugins.php` |
| `baizy_enqueue_custom_assets_for_specific_page()` | `wp_enqueue_scripts` | フォームのあるページで `app/plugins/p_cf7/` の CSS / JS を読み込み、サンクスページの URL（`/thanks/`）を JS に渡す | `plugins.php` |
| `baizy_cf7_add_required_rules( $schema, $contact_form )` | `wpcf7_swv_create_schema` | Contact Form 7 の必須エラーを「氏名を入力してください。」のような項目ごとの文言にする。項目名は `baizy_cf7_field_labels()` に登録したものを使い、未登録の項目はフォームの「メッセージ」設定の共通文言になる | `plugins.php` |
| `baizy_wpcf7_autop_return_false()` | `wpcf7_autop_or_not` | Contact Form 7 が自動で挿入する `<p>` / `<br>` を無効にする | `plugins.php` |
| `baizy_acf_json_save_point( $_path )` | `acf/settings/save_json` | ACF の JSON 保存先を `data/field-groups/` にする | `acf_json_export.php` |
| `baizy_acf_json_load_point( $paths )` | `acf/settings/load_json` | ACF の JSON 読み込み先を `data/field-groups/` にする | `acf_json_export.php` |
| `baizy_scf_export_field_group( $post_id )` | `save_post_smart-cf` | SCF の設定を保存したときに、全設定を `data/field-groups/scf-{ID}.json` に書き出す。削除された設定の JSON は消す | `acf_json_export.php` |
| `baizy_custom_search_query( $query )` | `pre_get_posts` | 検索の対象を投稿・固定ページにし、カスタムフィールド `_custom_field1` も検索対象に含める（`search.php` 読み込み後のみ有効） | `search/search.php` |

#### 内部で使う補助関数

ほかの関数から呼ばれる関数です。テンプレートから直接使うことは想定していません。

| 関数 | 戻り値 | 内容 | 定義場所 |
|------|--------|------|---------|
| `baizy_cf7_field_labels()` | `array` | 必須エラーに表示する項目名を `フォームタグの name => 項目名` で返す。フォームの項目を変えたらここも更新する | `plugins.php` |
| `baizy_get_admin_filter_taxonomies( $post_type )` | `WP_Taxonomy[]` | 投稿一覧の絞り込み対象にするタクソノミーを返す（`category` / `post_tag` と、管理画面に表示しないものは除外） | `admin.php` |
| `baizy_scf_setting_to_array( $setting )` | `array` | SCF の設定オブジェクトを、JSON に書き出す配列に変換する | `acf_json_export.php` |
| `baizy_scf_write_file( $file_path, $contents )` | `bool` | ファイルに書き込む（`WP_Filesystem` を優先し、使えなければ `file_put_contents`） | `acf_json_export.php` |
| `baizy_scf_log( $message )` | なし | `WP_DEBUG` が有効なときだけ `error_log` にログを出す | `acf_json_export.php` |

#### テンプレートパーツのフック

`baizy_template_part()` で読み込んだパーツには、スラッグごとに次のフックが用意されています（`{slug}` は `resources/include/header/header_base` のような読み込みパス）。

| フック | 種類 | タイミング |
|--------|------|-----------|
| `baizy_part_before__{slug}` | アクション | パーツを出力する直前 |
| `baizy_part__{slug}` | フィルター | パーツの HTML を出力前に書き換える |
| `baizy_part_after__{slug}` | アクション | パーツを出力した直後 |

```php
add_filter( 'baizy_part__resources/include/header/header_base', function ( $html ) {
	return str_replace( 'header__inner', 'header__inner is-sticky', $html );
} );
```

#### クラスの主なメソッド

関数のほか、次のクラスメソッドもテンプレートや Controller から直接使えます。

| メソッド | 戻り値 | 内容 |
|---------|--------|------|
| `\Baizy\Models\PostModel::get_latest_news( $limit = 5 )` | `WP_Post[]` | 公開済みの `news` を新しい順に取得する |
| `\Baizy\Models\PageMetaModel::field( $name, $post_id, $default = null )` | `mixed` | ACF のフィールド値を取得する。ACF が無効のときや値が空のときは `$default` を返す |
| `\Baizy\Models\PageMetaModel::get_hero( $post_id )` | `array` | ヒーローセクション用の `title` / `image` を返す（`hero_title` が空なら投稿タイトル） |
| `\Baizy\Models\TaxonomyModel::get_terms_of_post( $taxonomy, $post_id = 0 )` | `array` | 投稿に紐づくタームを配列で返す（`baizy_display_terms_of_post()` の実体） |
| `\Baizy\Services\ExternalLinksManager::get_url( $key )` | `string` | `links.json` のキーに対応する URL（エスケープ済み）を返す。なければ空文字 |
| `\Baizy\Services\ExternalLinksManager::get_link( $key )` | `array\|null` | `links.json` のキーに対応する項目を配列で返す |
| `\Baizy\Services\ExternalLinksManager::get_all_links()` | `array` | `links.json` の全項目を返す |
| `\Baizy\Helpers\TemplateHelper::first_part( $candidates, $fallback, $args = array() )` | なし（出力） | 候補のテンプレートのうち、最初に存在するものを読み込む。どれもなければ `$fallback` を読み込む |
| `\Baizy\Controllers\TopController::data()` | `array` | トップページのビューに渡す `news` / `hero` を組み立てる |

### テンプレート構成（MVC）

`index.php` → `resources/layouts/index.php`（ルーター）でページ種別ごとにテンプレートを振り分けます。WordPress 標準のテンプレート階層（`single-xxx.php` など）はテーマ直下に作りません。

| ページ種別 | 読み込まれるファイル（上から優先） |
|-----------|-----------------------------------|
| 固定ページ | `resources/pages/{親スラッグ-子スラッグ}.php` → `resources/pages/{スラッグ}.php` → `resources/pages/page-base.php` |
| 投稿詳細 | `resources/single/{投稿タイプ}.php` → `resources/single/single-base.php` |
| アーカイブ | `resources/archives/{投稿タイプ}.php` → `resources/archives/archive-base.php` |
| 検索結果 | `resources/pages/search.php` → `resources/archives/archive-base.php` |
| 404 | `resources/pages/not-page.php` |

データ取得が必要なページは、役割を3つに分けて実装します。

| 層 | 場所 | 役割 |
|----|------|------|
| Model | `app/models/` | `get_posts` / `get_field` などのデータ取得と整形 |
| Controller | `app/controllers/` | Model を呼び、ビューへ渡す配列を `data()` で組み立てる |
| View | `resources/` | `$args` で受け取った値を表示するだけ（`get_field()` を直接呼ばない） |

```php
// resources/layouts/index.php（ルーター）
baizy_template_part( 'resources/pages/top', \Baizy\Controllers\TopController::data() );

// resources/pages/top.php（ビュー）
$news = $args['news'] ?? array();
```

書き方の見本は [app/controllers/sample_controller.php](app/controllers/sample_controller.php) と [resources/pages/sample_mvc.php](resources/pages/sample_mvc.php) を参照してください。

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
- `unfiltered_html` 権限を持つユーザー（通常は管理者）専用です。GTM の `<script>` をそのまま出力する必要があるため、入力値は**サニタイズせずにそのまま保存・出力**します（`wp_kses` はタグ内のコンテンツを破壊するため使用していません）
- そのため、信頼できないユーザーに `unfiltered_html` 権限を与えないでください

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

- **パンくずリスト**: `baizy_create_breadcrumb()` を呼び出すと Schema.org 対応のパンくずを出力します
- **noindex**: 404 / カテゴリー / タグページに `noindex, nofollow` を自動付与します（対象を増やす場合は `baizy_single_noindex()` に条件を追加）
- **カスタム投稿メタディスクリプション**: カスタム投稿のアーカイブページに、`register_post_type` の `description` をメタディスクリプションとして出力します
- **フィード**: RSS フィードの対象をカスタム投稿 `news` にしています（`baizy_feed_request()`）

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

`/data/field-groups/` には `.htaccess` を置き、JSON ファイルへのブラウザからの直接アクセスを禁止しています（Apache の場合。Nginx では別途設定が必要です）。

実装: [app/functions/acf_json_export.php](app/functions/acf_json_export.php)

## Chrome DevTools MCP

このリポジトリには、Chrome DevTools MCP（Model Context Protocol）を使うための設定ファイルが含まれています。

- パッケージ: `chrome-devtools-mcp`（バージョンは `package.json` を参照。`.mcp.json` は `node_modules/` 内の同じバージョンを起動します）
- 設定ファイル: [`.mcp.json`](.mcp.json)（Claude Code がプロジェクトを開くと自動で読み込みます。初回はサーバーの利用を承認してください）
- 事前に `pnpm install` が必要です

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

`node_modules/`・`.git/`・`.claude/`・`.agents/`・`.mcp.json`・`sample/`・`block-markup/`・`scripts/`・`baizy-custom-blocks/`（プラグインは別途デプロイ）・各種設定ファイル（`phpcs.xml`, `package.json`, `pnpm-lock.yaml`, `.prettierrc`, `.npmrc` など）・ドキュメント / レポート類・`.DS_Store` / `*.log` / `*.map`

> SCSS ソース（`resources/common/scss/`）は除外していないため `dist/baizy/` に含まれます。本番で不要な場合は `scripts/create_dist.sh` の rsync に `--exclude 'resources/common/scss/'` を追加してください。

除外リストの正式な定義は [`scripts/create_dist.sh`](scripts/create_dist.sh) を参照してください。除外を変更したい場合もこのスクリプトを編集します。

> **注意**: `vendor/` は開発ツールだけでなく、`app/` 以下のクラス・関数を読み込む Composer オートローダーを含むため**本番でも必須**です。`pnpm dist` は開発用パッケージ（phpcs 等）を除いた本番用 `vendor/` を自動生成するので、`dist/baizy/` に含まれる `vendor/` をそのままアップすれば問題ありません。

`dist/` は Git 管理外（`.gitignore` 済み）です。

---

## ライセンス

GPL-2.0-or-later

詳細は `LICENSE` を参照してください。
