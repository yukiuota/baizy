# ブロックマークアップ早見表

WordPress 7.1.3 のエディターで、保存形式と往復（読み込み → 保存）で崩れないことを確認したもの。
ここにない書き方をするときは、必ず `validate-blocks.cjs` で確かめる。

## 変換の判断

ブロックにするのは、元の要素をそのまま置き換えられるときだけ。迷ったら静的 HTML のままにする（静的部分も「HTML を編集」から変更できるため、壊れるよりよい）。

| 元の要素 | 変換先 | 条件 |
|----------|--------|------|
| `h1`〜`h6` | core/heading | 属性が class / id だけで、中身がテキストと下記のインライン要素だけ |
| `p` | core/paragraph | 同上 |
| `ul` / `ol` | core/list + core/list-item | 同上（`li` も同じ条件）。`li` に div や img が入っていたら `li` は静的にして、中のテキストを段落にする |
| 内容としての `img`（alt が空でない） | core/image | 属性が src / alt / class / id だけ（width / height / loading / decoding は捨ててよい） |
| div・dd・td・li などに直書きのテキスト | その中に core/paragraph を置く | `<p>` が増えて DOM が変わるので報告する |
| 上記以外 | 静的 HTML のまま | |

ブロックの中で使えるインライン要素: `br` `strong` `b` `em` `i` `u` `s` `sup` `sub` `code` `kbd` `mark` `small` `span`（class 付きも可）`a`（href / target / rel / class 付きも可）`ruby` `rt` `time`

静的のままにするもの:

- class・id 以外の属性（`style` / `data-*` / `aria-*` / `role` / `lang` / `title` など）を持つ要素。ブロックにすると検証は通っても、保存時に `<p><p data-x="1">…</p></p>` のように壊れる
- ボタン・リンク（`<a class="c-btn">` など）。リンク付き画像は `<a>` を静的に残し、中の img だけを画像ブロックにする
- `js-` クラスや id が付いた img（画像ブロックにすると img から class が消え、JS が動かなくなる）
- svg、`picture` / `srcset` 付きの img、iframe、video、form、script、nav、装飾画像（`alt=""`）
- テーブル。会社概要のように内容の更新が見込まれる場合だけ、セルのテキストを段落ブロックにするか core/table に置き換えるかを提案する
- インライン要素の中身（span のラベル、a の中のテキストなど）。`p` や見出しの中にブロックは置けない

## 共通の書き方

- 開始コメント・タグ・終了コメントは保存形式どおりに改行で区切る。前後の静的 HTML のインデントは自由

  ```html
          <div class="note"><!-- wp:paragraph -->
  <p>本文</p>
  <!-- /wp:paragraph --></div>
  ```

- class は `className`、id は `anchor` として区切りコメントの JSON に書き、HTML 側の属性にも残す。JSON のキー順と HTML の属性順はブロックごとに決まっている（下の例のとおり）
- BEM の `--` は **JSON の中だけ** `--` と書く（HTML コメントの中に `--` を書けないため）。HTML の class 属性は `--` のまま
- ブロックの中の文字参照は `&amp;` `&lt;` `&gt;` `&nbsp;` 以外を文字に戻す（`&quot;` → `"`、`&#039;` → `'`、`&copy;` → `©`、`&#8211;` → `–`）。`esc_html()` の出力をそのまま使うと `&quot;` などが残るので注意。静的 HTML 側は変えなくてよい
- wp:html の中で開いたタグは同じ wp:html の中で閉じる。複数の wp:html にまたがるラッパーはテンプレート側に残す

## 見出し

```html
<!-- wp:heading -->
<h2 class="wp-block-heading">見出し</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3,"className":"c-title c-title--large","anchor":"sec1"} -->
<h3 id="sec1" class="wp-block-heading c-title c-title--large">見出し<span class="c-title__en">Title</span></h3>
<!-- /wp:heading -->
```

- h2 のときは `level` を書かない
- `wp-block-heading` は必須（フロントにも出力される）。id は class より前

## 段落

```html
<!-- wp:paragraph -->
<p>テキスト<br>改行 <strong>強調</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"lead","anchor":"intro"} -->
<p class="lead" id="intro">テキスト</p>
<!-- /wp:paragraph -->
```

- 見出しと違い class が id より前
- フロントでは `wp-block-paragraph` クラスが自動で付く（保存形式には書かない）

## リスト

```html
<!-- wp:list {"className":"feature"} -->
<ul class="wp-block-list feature"><!-- wp:list-item {"className":"feature__item"} -->
<li class="feature__item">項目 <a href="/a/">リンク</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>項目</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true,"className":"steps","anchor":"flow"} -->
<ol id="flow" class="wp-block-list steps"><!-- wp:list-item -->
<li>手順<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>入れ子</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></li>
<!-- /wp:list-item --></ol>
<!-- /wp:list -->
```

- `li` ごとに list-item の区切りが必要。list-item どうしは空行 1 つで区切る
- `wp-block-list` は必須。id は class より前

## 画像

```html
<!-- wp:image -->
<figure class="wp-block-image"><img src="/wp-content/themes/baizy/resources/img/a.jpg" alt="説明"/></figure>
<!-- /wp:image -->

<!-- wp:image {"className":"about__photo","anchor":"photo"} -->
<figure class="wp-block-image about__photo" id="photo"><img src="/wp-content/themes/baizy/resources/img/a.jpg" alt="説明"/></figure>
<!-- /wp:image -->
```

- img に書けるのは src と alt だけ。class / width / height / loading があると検証エラーになる。alt がないと `alt=""` が補われる
- 元の img の class は figure に移る。CSS の `.about__photo` は figure に当たるようになるため、`.about__photo img` などへの調整が要る
- `.wp-block-image` にはコアの CSS で `margin: 0 0 1em` が付く
- 管理画面でメディアライブラリの画像に「置換」すると、`{"id":123,"sizeSlug":"large"}` と `wp-image-123` クラスが付き、フロントで width / height / srcset が自動で出力される

## ショートコード（動的な部分を本文に残すとき）

```html
<!-- wp:shortcode -->
[contact-form-7 id="abc1234" title="お問い合わせ"]
<!-- /wp:shortcode -->
```

## テーブル（提案して了承を得たときだけ）

```html
<!-- wp:table {"className":"spec"} -->
<figure class="wp-block-table spec"><table class="has-fixed-layout"><thead><tr><th>項目</th><th>内容</th></tr></thead><tbody><tr><td>a</td><td>b</td></tr></tbody></table></figure>
<!-- /wp:table -->
```

- figure で包まれ、元の table の class は figure に移る
- `has-fixed-layout` を付けないときは JSON に `"hasFixedLayout":false` を書く

## 検証スクリプトの NG の直し方

| 表示 | 直し方 |
|------|--------|
| 検証エラー | 「エディターが期待する保存形式」に合わせる。余計な属性があればブロックにするのをやめる |
| 保存し直すと内容が変わります | `+` 側が実際に保存される内容。`--` や文字参照のような無害な正規化なら `+` 側に合わせる。`<p><p …>` のように構造が壊れるなら、そのブロック化をやめて静的に戻す |
| ブロックの外に HTML があります | wp:html の外にはみ出している。wp:html の中に入れる |
| 未登録のブロックです | ブロック名の綴りを直す（プラグインのブロックなら有効化されているか確認する） |
