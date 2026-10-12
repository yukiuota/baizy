---
paths:
  - "resources/common/scss/**/*.scss"
---

# SCSS ルール

## ビルド

- 修正は SCSS 側で行い、`pnpm sass:build` で `resources/common/css/` にコンパイルする（CSS を直接編集しない）
- エントリポイントは `common.scss` / `editor-style.scss` / `pages/*.scss`。ページ別ファイルを追加したら `package.json` の `sass:build` と `sass:watch` の両方に追記する
- ページ別 CSS のファイル名は body class と同じにする（例: `pages/home.scss` → `home.css`。body に `home` クラスがあると自動で読み込まれる）

## モジュール構成

- Dart Sass のモジュールシステム（`@use` / `@forward`）を使う。`@import` は使わない
- 変数・mixin・関数は `scss_var/` に置き、各ファイルで `@use "../scss_var" as var;` として `var.` 付きで参照する
- 新しいパーシャルは `_xxx.scss` で作り、該当ディレクトリの `_index.scss` に `@use` を追加する

| ディレクトリ | 内容 |
|-------------|------|
| `scss_var/` | Sass 変数（ブレイクポイント）・mixin・関数。CSS を出力しない |
| `setting/` | リセット CSS、CSS カスタムプロパティ（`:root`） |
| `components/` | ヘッダー・フッター・投稿本文などの共通パーツ |
| `pages/` | ページ別のエントリポイント |
| `admin/` | エディター（管理画面）用 |

## 書き方

- インデントはスペース2つ。ネストは3階層程度までにする
- サイズの px 指定は `var.rm(16)` で rem に変換する
- 色・フォント・幅などのデザイントークンは `setting/_var.scss` の CSS カスタムプロパティ（`--main-color` など）を使い、値を直書きしない
- CSS カスタムプロパティに Sass の値を入れるときは `#{}` で補間する（例: `--main-font-size: #{var.rm(16)};`）
- 区切りコメントは既存の形式にそろえる

```scss
/*　header
----------------------------------------------- */
```

## レスポンシブ

- ブレイクポイントは mixin を使い、`@media` を直書きしない
  - `@include var.sp {}` … 750px 以下
  - `@include var.tab {}` … 768px 以下（SP も含む）
  - `@include var.pc {}` … 750px より大きい
- PC のスタイルを先に書き、ファイル末尾の `@include var.sp {}` にまとめて SP の差分を書く
- ホバーは `@include var.hover {}` を使う（タッチ端末でホバーが残らないようにする）

## クラス名

- BEM 風（`.block__element`、状態は `.is-open` など）。`js-` で始まるクラスにはスタイルを当てない
- アニメーションはできるだけ CSS 側で実装する（`@starting-style`、`transition-behavior: allow-discrete`、`grid-template-rows` など）。JS はクラスを付け外しするだけにする
