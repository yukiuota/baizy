# PHP Warning 修正パターン集

## PHP 8.0 以降の主な破壊的変更

### `each()` の削除（PHP 8.0）

```php
// Before
while (list($key, $val) = each($array)) { ... }

// After
foreach ($array as $key => $val) { ... }
```

### `create_function()` の削除（PHP 8.0）

```php
// Before
$fn = create_function('$x', 'return $x * 2;');

// After
$fn = fn($x) => $x * 2;
// または
$fn = function($x) { return $x * 2; };
```

### `strpos()` / `stripos()` の戻り値変更（PHP 8.0）

```php
// Before (PHP 7 では false を返す場合があった)
if (strpos($str, 'foo') !== false) { ... }

// After (PHP 8 でも同じ書き方で OK だが、型チェックが厳格化)
if (str_contains($str, 'foo')) { ... }  // PHP 8.0+ 推奨
```

### `str_starts_with()` / `str_ends_with()` — PHP 8.0 で追加

```php
// Before
if (strpos($str, 'prefix_') === 0) { ... }

// After (PHP 8.0+)
if (str_starts_with($str, 'prefix_')) { ... }
```

---

## PHP 8.1 以降の主な変更

### `mb_convert_encoding()` の `$encodings` 引数変更（PHP 8.1）

```php
// Before
mb_convert_encoding($str, 'UTF-8', 'auto');

// After
mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
```

### 浮動小数点数から int への暗黙的変換が Deprecated（PHP 8.1）

```php
// Before
function foo(int $x) {}
foo(1.5);  // PHP 8.1: Deprecated

// After
foo((int) 1.5);  // 明示的にキャスト
```

### readonly プロパティ（PHP 8.1 新機能）

```php
class Post {
    public readonly int $id;  // 一度セットしたら変更不可
}
```

### `never` 戻り値型（PHP 8.1 新機能）

```php
function throwError(): never {
    throw new RuntimeException('error');
}
```

---

## PHP 8.2 以降の主な変更

### 動的プロパティの Deprecated（PHP 8.2）

```php
// Before (PHP 8.2 で Deprecated、8.3 以降は Warning/Error の可能性)
class Foo {}
$foo = new Foo();
$foo->bar = 'baz';  // 未宣言プロパティへの代入

// After: プロパティを宣言する
class Foo {
    public string $bar = '';
}
// または #[AllowDynamicProperties] アトリビュートを使う
#[AllowDynamicProperties]
class Foo {}
```

### `utf8_encode()` / `utf8_decode()` の Deprecated（PHP 8.2）

```php
// Before
$encoded = utf8_encode($str);

// After
$encoded = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
```

### `${var}` 文字列補間が Deprecated（PHP 8.2）

```php
// Before
echo "Hello ${name}";

// After
echo "Hello {$name}";
// または
echo "Hello $name";
```

---

## PHP 8.3 以降の主な変更

### 型宣言なしの定数が Deprecated（PHP 8.3）

```php
// Before
class Foo {
    const VERSION = '1.0';  // 型なし
}

// After
class Foo {
    const string VERSION = '1.0';
}
```

### `unserialize()` の Deprecated オプション（PHP 8.3）

```php
// After (信頼できないデータには allowed_classes を指定)
$obj = unserialize($data, ['allowed_classes' => ['MyClass']]);
```

---

## WordPress Deprecated API

### `get_page_by_title()` — WP 6.2 で Deprecated

```php
// Before
$page = get_page_by_title('My Page');

// After
$pages = get_posts([
    'post_type'   => 'page',
    'title'       => 'My Page',
    'numberposts' => 1,
]);
$page = $pages[0] ?? null;
```

### `wp_get_loading_attr_default()` — WP 6.3 で Deprecated

```php
// Before
$loading = wp_get_loading_attr_default('the_content');

// After
$loading = wp_lazy_loading_enabled('img', 'the_content') ? 'lazy' : false;
```

### `the_widget()` — WP 6.4 で Deprecated

古い widget API の代わりに Block Widgets または直接クラスをインスタンス化する。

---

## 型宣言・null 安全演算子

### null 安全演算子（PHP 8.0）

```php
// Before
$city = $user ? ($user->getAddress() ? $user->getAddress()->getCity() : null) : null;

// After
$city = $user?->getAddress()?->getCity();
```

### Union 型（PHP 8.0）

```php
// Before
/**
 * @param int|string $id
 */
function find($id) {}

// After
function find(int|string $id) {}
```

### match 式（PHP 8.0）

```php
// Before
switch ($status) {
    case 1: $label = 'Active'; break;
    case 2: $label = 'Inactive'; break;
    default: $label = 'Unknown';
}

// After
$label = match($status) {
    1 => 'Active',
    2 => 'Inactive',
    default => 'Unknown',
};
```
