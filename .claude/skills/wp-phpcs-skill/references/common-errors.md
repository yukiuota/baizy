# よくある WordPress PHPCS エラーと修正パターン

## コメント・ドキュメント関連

### ファイルヘッダーコメント不足
**エラー**: `Missing file doc comment`

```php
<?php
/**
 * テンプレートの説明
 *
 * @package YourThemeName
 */
```

### 関数 docblock 不足
**エラー**: `Missing doc comment for function`

```php
/**
 * 関数の説明。
 *
 * @param string $param パラメータの説明。
 * @return string 戻り値の説明。
 */
function my_function( $param ) {
```

---

## セキュリティ関連

### nonce 検証なし
**エラー**: `Processing form data without nonce verification`

```php
// フォーム側
wp_nonce_field( 'my_action', 'my_nonce' );

// 処理側
if ( ! isset( $_POST['my_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['my_nonce'] ), 'my_action' ) ) {
    wp_die( esc_html__( '不正なリクエストです', 'your-textdomain' ) );
}
```

### sanitize 漏れ
**エラー**: `Detected usage of a non-sanitized input variable`

```php
// NG
$value = $_POST['my_field'];

// OK
$value = sanitize_text_field( wp_unslash( $_POST['my_field'] ) );
// テキストエリアの場合
$value = sanitize_textarea_field( wp_unslash( $_POST['my_field'] ) );
// 整数の場合
$value = absint( $_POST['my_field'] );
// メールの場合
$value = sanitize_email( wp_unslash( $_POST['my_field'] ) );
```

### escape 漏れ
**エラー**: `All output should be run through an escaping function`

```php
// NG
echo $variable;
echo get_option( 'my_option' );

// OK
echo esc_html( $variable );           // 一般テキスト
echo esc_attr( $variable );           // HTML属性値
echo esc_url( $variable );            // URL
echo wp_kses_post( $variable );       // 限定的なHTML許可
echo esc_html( get_option( 'my_option' ) );
```

### 直接 DB アクセス
**エラー**: `Detected usage of a direct database query`

```php
global $wpdb;

// NG
$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID = " . $id );

// OK
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}posts WHERE ID = %d",
        $id
    )
);
```

---

## WordPress API 関連

### プレフィックス不足
**エラー**: `Function/variable name is not prefixed`

```php
// NG
function setup() { ... }
$options = [];

// OK（テーマ名やプラグイン名をプレフィックスに）
function mytheme_setup() { ... }
$mytheme_options = [];
```

### テキストドメイン
**エラー**: `A textdomain must be provided for string`

```php
// NG
__( 'テキスト' );
_e( 'テキスト' );

// OK
__( 'テキスト', 'your-textdomain' );
_e( 'テキスト', 'your-textdomain' );
esc_html__( 'テキスト', 'your-textdomain' );
esc_html_e( 'テキスト', 'your-textdomain' );
```

---

## フォーマット関連

### インデント（タブ使用）
**エラー**: `Spaces found; expected tabs`

phpcsでは WordPress 標準のタブインデントが必要。phpcbf で自動修正可能。

### 演算子前後のスペース
**エラー**: `Expected 1 space before/after operator`

```php
// NG
$a=$b+$c;
if($a==$b){

// OK
$a = $b + $c;
if ( $a === $b ) {
```

### 配列構文
**エラー**: `Short array syntax must be used to define arrays`

```php
// NG（古い書き方）
$arr = array( 'a', 'b' );

// OK
$arr = [ 'a', 'b' ];
```

### 行末スペース
**エラー**: `Whitespace found at end of line`

phpcbf で自動修正可能。エディタの設定で「保存時に行末スペースを削除」を有効にしておくと良い。

---

## ファイル終端

### ファイル末尾の改行
**エラー**: `File must end with a newline character`

ファイルの最終行の後に改行が必要。phpcbf で自動修正可能。

### PHP 閉じタグ
**警告**: `PHP closing tag should be omitted`

PHPのみのファイルでは `?>` を省略する。

```php
// NG（ファイル末尾）
?>

// OK（省略）
// ファイル終端（改行のみ）
```
