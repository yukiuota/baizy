---
paths:
  - "resources/**/*.php"
  - "patterns/**/*.php"
  - "sample/**/*.php"
  - "index.php"
  - "header.php"
  - "footer.php"
  - "comments.php"
---

# テンプレート（ビュー）ルール

## ファイル冒頭

テンプレートの1行目で直接アクセスを防ぐ（`app/` と違い `exit` を使う）。

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
```

## テンプレートの振り分け

`index.php` → `resources/layouts/index.php`（ルーター）で振り分ける。WP 標準のテンプレート階層（`single-xxx.php` など）はテーマ直下に作らない。

| 種別 | 読み込まれるファイル（上から優先） |
|------|-----------------------------------|
| 固定ページ | `resources/pages/{親-子}.php` → `resources/pages/{slug}.php` → `page-base.php` |
| 投稿詳細 | `resources/single/{post_type}.php` → `single-base.php` |
| アーカイブ | `resources/archives/{post_type}.php` → `archive-base.php` |
| 検索 | `resources/pages/search.php` → `archives/archive-base.php` |
| 404 | `resources/pages/not-page.php` |

- 新しいページや投稿タイプのテンプレートは、上の命名に合わせてファイルを置けば読み込まれる。ルーターの変更が必要なのは Controller からデータを渡すときだけ
- 分割パーツは `resources/include/` 以下に置く（再利用部品は `resources/include/components/{名前}/`）

## 読み込み

- パーツは `get_template_part()` ではなく `baizy_template_part( 'resources/...', $args )` で読み込む（パスは拡張子なし）
- データは第2引数の `$args` で渡し、受け取る側では `$args['key'] ?? 既定値` でフォールバックを用意する

## ビューに書いてよいこと・いけないこと

- ビューは表示に専念する。`get_field()` / `WP_Query` / `get_posts()` を直接書かない。データ取得は Model、組み立ては Controller で行い `$args` で受け取る
- メインループ（`have_posts()` / `the_post()`）と、`the_title()` などの WP テンプレートタグは使ってよい
- サブループで `setup_postdata()` を使ったら、必ず `wp_reset_postdata()` で戻す

## エスケープ

- 動的な値は必ずエスケープして出力する

| 出力先 | 関数 |
|--------|------|
| テキスト | `esc_html()` |
| 属性値 | `esc_attr()` |
| URL（`href` / `src`） | `esc_url()` |
| 投稿本文相当の HTML | `wp_kses_post()` |

- `the_title()` / `the_permalink()` などの WP テンプレートタグは内部でエスケープ済みなので、そのまま使ってよい
- カスタマイザーのタグ追加欄（`baizy_head_top_code` / `baizy_body_top_code`）だけは意図的に生出力している。ほかの値で真似しない

## 画像

- テーマ内の画像は `resources/img/` に置き、`baizy_img()` で URL を、`baizy_img_wh()` で width / height / loading 属性を出力する
- ファーストビューの画像は `baizy_img_wh( 'xxx.jpg', false )` として `loading="lazy"` を付けない
- `alt` 属性は必ず付ける（装飾画像は `alt=""`）

## HTML / クラス名

- クラス名は BEM 風の命名にする（`block__element`、状態は `is-open` / `is-opened`）。例: `.news-list__item`, `.hero__title`
- JS から触る要素は `js-` プレフィックスの id / class を付け、スタイル用クラスと分ける（例: `#js-menu-trigger`）
- 見出しレベルや `<main>` / `<header>` / `<nav>` などのランドマーク要素を正しく使う。ボタンには `aria-label` などで目的を示す
- 外部リンクの URL は `resources/settings/links.json` に登録し、`ExternalLinksManager::get_url()` か `[external_url key="..."]` で呼び出す。テンプレートに直書きしない

## インデント

- 既存ファイルのインデント（スペース4つ or タブ）に合わせる。phpcs の対象外だが、エスケープ・命名のルールは `app/` と同じにする

## ブロックパターン（patterns/）

- ファイル先頭のヘッダーコメントに `Title` / `Slug`（`baizy/` プレフィックス）/ `Categories` を書く
- 中身はブロックエディターのマークアップ（`<!-- wp:xxx -->`）で書く
