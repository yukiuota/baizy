# PHPCompatibility 主要ルール早見表

## PHPCompatibility のインストール確認

```bash
./vendor/bin/phpcs -i | grep -i compat
```

`PHPCompatibility` が表示されなければ未インストール：

```bash
composer require --dev phpcompatibility/php-compatibility
```

## testVersion の指定方法

| 指定値 | 意味 |
|--------|------|
| `8.2` | PHP 8.2 のみ |
| `7.4-8.2` | PHP 7.4〜8.2 の範囲 |
| `8.2-` | PHP 8.2 以降すべて |
| `-8.1` | PHP 8.1 以前すべて |

phpcs.xml で恒久設定する場合：

```xml
<ruleset name="MyProject">
    <rule ref="PHPCompatibility"/>
    <config name="testVersion" value="8.2-"/>
    <exclude-pattern>*/vendor/*</exclude-pattern>
</ruleset>
```

コマンドラインでその場指定する場合：

```bash
./vendor/bin/phpcs \
  --standard=PHPCompatibility \
  --runtime-set testVersion 8.2- \
  --extensions=php \
  --ignore=vendor/,node_modules/ \
  .
```

## よく検出されるルール

| ルール ID | 内容 | 対象バージョン |
|----------|------|--------------|
| `PHPCompatibility.FunctionUse.RemovedFunctions` | 削除された組み込み関数 | PHP 8.0+ |
| `PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue` | 引数の参照渡し変更 | PHP 5.3+ |
| `PHPCompatibility.Syntax.NewFlexibleHeredoc` | Heredoc 構文変更 | PHP 7.3+ |
| `PHPCompatibility.Classes.NewTypedProperties` | 型付きプロパティ | PHP 7.4+ |
| `PHPCompatibility.Operators.NewOperators` | null 合体代入演算子など | PHP 7.4+ |
| `PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations` | 新しい戻り値型 | PHP 8.0+ |
| `PHPCompatibility.Classes.NewReadonlyProperties` | readonly プロパティ | PHP 8.1+ |
| `PHPCompatibility.Classes.NewEnums` | enum | PHP 8.1+ |
| `PHPCompatibility.Variables.ForbiddenThisWithNonStaticMethod` | $this の不正使用 | PHP 8.0+ |

## PHP バージョン別 主な破壊的変更サマリー

### PHP 8.0
- `each()` 削除
- `create_function()` 削除
- 型強制の厳格化（`TypeError` が増える）
- `match` 式追加（新文法）
- Named Arguments 追加
- `str_contains()` / `str_starts_with()` / `str_ends_with()` 追加

### PHP 8.1
- `readonly` プロパティ
- Fibers
- Enum
- 交差型（Intersection Types）
- `never` 戻り値型
- `array_is_list()` 追加
- 浮動小数点数 → int 暗黙変換が Deprecated

### PHP 8.2
- readonly クラス
- `true` / `false` / `null` を単独型として使用可能
- 動的プロパティ Deprecated
- `utf8_encode()` / `utf8_decode()` Deprecated
- `${var}` 文字列補間 Deprecated

### PHP 8.3
- 型付き Class 定数
- `json_validate()` 追加
- `Override` アトリビュート追加
- `unserialize()` E_NOTICE → E_WARNING

### PHP 8.4
- プロパティフック（Property Hooks）
- 非対称可視性（Asymmetric Visibility）
- `#[\Deprecated]` アトリビュート
- `array_find()` / `array_find_key()` 追加
- `bcround()` / `bcceil()` / `bcfloor()` 追加
