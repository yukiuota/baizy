---
paths:
  - "app/**/*.php"
  - "functions.php"
---

# PHP ルール（app/ ・ functions.php）

## コーディング規約

- WordPress Coding Standards（`phpcs.xml`）に従う。インデントはタブ、括弧の内側にスペース、配列は `array()` 記法
- 変更後は `composer phpcs` を実行してエラー・警告をゼロにする。自動修正は `composer phpcbf`
- `// phpcs:ignore` は理由が明確な箇所だけで使い、必ずルール名を指定する（ルール名なしの ignore は禁止）
- テキストドメインは `baizy`
- 型宣言（引数・戻り値・プロパティ）を付ける。PHP 8.0 の範囲で `?string` / `array|false` などを使う
- 比較は厳密比較（`===` / `!==`、`in_array( ..., true )`）。定数・リテラルを左に置くヨーダ記法にする

## ファイル冒頭

すべての PHP ファイルで直接アクセスを防ぐ。`app/` 以下は `return` を使う。

```php
<?php
namespace Baizy\Models;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
```

## 命名

| 対象 | 規則 | 例 |
|------|------|-----|
| ファイル名 | snake_case | `external_links_manager.php` |
| 名前空間 | `Baizy\{Setup\|Models\|Controllers\|Helpers\|Services}` | `Baizy\Models\PostModel` |
| クラス名 | PascalCase（ファイル名と対応させる） | `ExternalLinksManager` |
| メソッド・変数 | snake_case | `get_latest_news()` |
| グローバル関数 | `baizy_` プレフィックス + snake_case | `baizy_register_post_types()` |
| フック名・オプション名・theme_mod | `baizy_` プレフィックス | `baizy_head_top_code` |
| 定数 | `BAIZY_` プレフィックス + 大文字 | `BAIZY_THEME_URI` |

`resources/` 内で定義する関数（例: `baizy_custom_search_form()`）も同じくプレフィックスを付ける。ショートコードのタグ名（`external_url` / `custom_search`）は投稿本文に書かれているため変更しない。

## クラスの書き方

- **Setup クラス**: コンストラクタで `add_action` / `add_filter` を登録し、`app/functions/settings.php` で `new` する。コールバックは `array( $this, 'method' )`
- **Models / Controllers / Helpers / Services**: 状態を持たない `public static` メソッドで構成する
- クラス内でしか使わない処理は `private` にする

## MVC の役割分担

| 層 | 場所 | 役割 |
|----|------|------|
| Model | `app/models/` | `get_posts` / `get_field` などのデータ取得と整形 |
| Controller | `app/controllers/` | Model を呼び、ビューへ渡す配列を `data()` で組み立てる |
| View | `resources/` | `$args` を表示するだけ |

- ACF の値は `PageMetaModel::field()` 経由で取得する（ACF 無効時も致命的エラーにしない）
- 複数箇所で使うフィールドや整形が必要なものは Model にメソッドを作る（例: `get_hero()`）
- Repeater などの生配列はそのまま渡さず、Controller でビュー用のキーに整形してから渡す
- 見本: `app/controllers/sample_controller.php` と `resources/pages/sample_mvc.php`

## グローバル関数（app/functions/）

- フック登録はその直前か直後に関数を定義する形にそろえる（`add_action( 'init', 'baizy_xxx' );` → `function baizy_xxx() {}`）
- テンプレートから呼ぶヘルパーは `my_functions.php` に薄いラッパーとして置き、実装は `app/helpers/` のクラスへ委譲する
- 上書きされる可能性のあるラッパーは `if ( ! function_exists( '...' ) )` で囲む

## セキュリティ

- 出力はエスケープする（`esc_html` / `esc_attr` / `esc_url` / `wp_kses_post`）。エスケープ済みの値を返すメソッドは、戻り値の説明に「エスケープ済み」と明記する
- 入力はサニタイズし、`$_GET` / `$_POST` は `wp_unslash()` してから `sanitize_*()` で処理する。フォーム処理には nonce 検証を入れる
- 権限が必要な処理は `current_user_can()` で確認する
- ファイルを読むときはパスを `realpath()` で正規化し、テーマディレクトリの外を読まないようにする（例: `ExternalLinksManager`）
- セキュリティ関連のフックは `Baizy\Setup\Security` にまとめる
- デバッグ用のログは `WP_DEBUG` が有効なときだけ `error_log()` で出力する。`var_dump` / `print_r` は残さない

## アセット読み込み

- CSS / JS は `Baizy\Setup\Scripts` で `wp_enqueue_*` する。テンプレートに `<link>` / `<script>` を直書きしない
- バージョンは `filemtime()` を使ってキャッシュを無効化する
- JS は jQuery に依存させず、`'strategy' => 'defer'` と `'in_footer' => true` で読み込む
- ページ別 CSS は body class と同名の CSS を自動で読み込む仕組みを使う（例: `home` → `common/css/home.css`）
