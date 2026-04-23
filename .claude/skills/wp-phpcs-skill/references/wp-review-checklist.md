# WordPress コードレビュー チェックリスト

phpcs が通った後に確認する WordPress 固有の品質チェック。

---

## セキュリティ

### 入力値の検証・サニタイズ
- [ ] `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE` を使う箇所すべてで sanitize している
- [ ] `wp_unslash()` してから sanitize している
- [ ] ユーザー権限チェックが必要な箇所で `current_user_can()` を使っている

### Nonce
- [ ] フォーム送信に `wp_nonce_field()` を使っている
- [ ] AJAX リクエストに nonce を含めている（`wp_create_nonce()`）
- [ ] 受信側で `wp_verify_nonce()` または `check_ajax_referer()` を使っている

### 出力のエスケープ
- [ ] テキスト出力: `esc_html()` / `esc_html_e()`
- [ ] 属性値: `esc_attr()`
- [ ] URL: `esc_url()`
- [ ] JavaScript 内: `esc_js()`
- [ ] HTML 許可が必要な場合: `wp_kses()` / `wp_kses_post()`

### データベース
- [ ] 直接 SQL を書く場合は `$wpdb->prepare()` を使っている
- [ ] 可能な限り WP_Query, WP_User_Query 等の WordPress API を使っている

---

## WordPress API の正しい使い方

### フック
- [ ] `add_action()` / `add_filter()` のコールバックにプレフィックスがついている
- [ ] 適切なフックタイミングを使っている（`init`, `wp_enqueue_scripts` 等）
- [ ] `remove_action()` を使う場合は優先度・引数数が一致している

### Assets の読み込み
- [ ] CSS/JS を `wp_enqueue_style()` / `wp_enqueue_scripts()` で登録している
- [ ] 直接 `<link>` / `<script>` タグを header.php などに書いていない
- [ ] 依存関係（`$deps`）が正しく設定されている
- [ ] バージョン番号（`$ver`）が設定されている（キャッシュバスティング）

### オプション・データ保存
- [ ] `get_option()` の結果にデフォルト値を設定している
- [ ] `update_option()` に `$autoload` 引数を設定している（必要に応じて `false`）
- [ ] カスタムテーブルが必要な場合は `dbDelta()` を使っている

### 翻訳
- [ ] すべての表示文字列を翻訳関数でラップしている
- [ ] `load_theme_textdomain()` / `load_plugin_textdomain()` を呼び出している
- [ ] テキストドメインが一貫している（composer.json/style.css の定義と合致）

---

## テーマ固有

### テンプレート階層
- [ ] WordPress のテンプレート階層に沿ったファイル名になっている
- [ ] `get_template_part()` を適切に使っている
- [ ] `get_header()`, `get_footer()`, `get_sidebar()` を使っている

### style.css ヘッダー
```css
/*
Theme Name: テーマ名
Theme URI: https://example.com
Author: 作者名
Author URI: https://example.com
Description: テーマの説明
Version: 1.0.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: your-textdomain
*/
```

### `functions.php`
- [ ] `after_setup_theme` フックで `add_theme_support()` を呼んでいる
- [ ] ショートコード・ウィジェット等の登録が適切なフックで行われている

---

## パフォーマンス

- [ ] ループ内で `get_option()` や重いクエリを繰り返していない
- [ ] 必要に応じてトランジェント（`set_transient()`）でキャッシュしている
- [ ] 画像は `wp_get_attachment_image()` 等を使っている（srcset 対応）

---

## プラグイン固有（該当する場合）

- [ ] プラグインヘッダーが正しく記述されている
- [ ] 有効化/無効化フックが設定されている（`register_activation_hook()`）
- [ ] アンインストール時のクリーンアップが `register_uninstall_hook()` または `uninstall.php` で行われている
- [ ] グローバル変数を最小限にしている（クラスや名前空間を使う）
