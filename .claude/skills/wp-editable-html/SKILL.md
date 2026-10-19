---
name: wp-editable-html
description: テーマの PHP テンプレート（resources/pages/*.php など）で作ったページを、WordPress 7.1 の「カスタム HTML ブロックの中に編集可能なブロックを埋め込む」形式のブロックマークアップに変換し、管理画面のエディターに貼り付けるだけで使えるようにするスキル。デザインの HTML は固定したまま、見出し・段落・リスト・画像だけをクライアントが編集できるようにする。「ブロック化」「管理画面で編集できるようにしたい」「エディターに貼り付けたい」「カスタム HTML ブロック」「wp:html」「テンプレートの内容を固定ページの本文に移したい」などの依頼で使う。
---

# PHP テンプレート → 編集可能なカスタム HTML ブロック

テーマの PHP テンプレートで作ったページを、管理画面のエディターに貼り付けるだけで使えるブロックマークアップに変換する。
レイアウト・クラスは静的 HTML として固定し、クライアントが変える文字と画像だけを編集可能なブロックにする。

引数: 対象テンプレートのパス（例: `resources/pages/about.php`）またはスラッグ（例: `about`）

## この形式の仕組み（WordPress 7.1 以上）

```html
<!-- wp:html -->
<section class="about">
    <div class="about__inner">
        <!-- wp:heading {"className":"about__title"} -->
<h2 class="wp-block-heading about__title">会社概要</h2>
<!-- /wp:heading -->
    </div>
</section>
<!-- /wp:html -->
```

- 静的 HTML の任意の位置（div や li の中など）にブロックを置ける。保存しても静的部分は 1 バイトも変わらない
- エディターでは静的部分が編集できない状態で表示され、中のブロックだけをその場で編集できる。中のブロックは移動・削除・追加ができないため、構造を壊されない。静的部分はツールバーの「HTML を編集」から変える
- フロントでは静的 HTML とブロックの出力がそのまま連結される
- 外側の wp:html は検証されないが、**中のブロックは通常どおり厳密に検証される**。保存形式から 1 文字でもずれると「予期しないコンテンツ」エラーになり、検証を通っても保存時に別の HTML へ書き換わることがある。見た目で判断せず、必ず手順 6 のスクリプトで確かめる
- WordPress 7.1 未満のサイトでは使えない。貼り付け先のバージョンを確認する

## 手順

### 1. テンプレートと表示 URL を特定する

ルーター（`resources/layouts/index.php`）の規則から、テンプレートが表示される URL を決める。

| テンプレート | 表示される URL |
|--------------|----------------|
| `resources/pages/{slug}.php` | スラッグが `{slug}` の固定ページ |
| `resources/pages/{親}-{子}.php` | 階層ページ `/{親}/{子}/` |
| `resources/pages/top.php` | 「ホームページの表示」が最新の投稿のときのフロント。固定ページをフロントにするとルーターは page-base.php を使うので、移行方法をユーザーと相談する |

固定ページの URL は REST で調べる。

```bash
curl -s "http://localhost:8080/?rest_route=/wp/v2/pages&slug={slug}&_fields=id,link"
```

公開済みのページがなければ、ユーザーの了承を得てからローカルに作る。

```bash
docker compose -f ../../docker-compose.yml run --rm --no-deps -T wpcli wp post create --post_type=page --post_status=publish --post_name={slug} --post_title="{タイトル}"
```

### 2. テンプレートを読み、静的な部分と動的な部分を分ける

テンプレート本体に加えて、`baizy_template_part()` で読み込むパーツと、ルーターで `$args` を渡している Controller も読む。

- 静的: テンプレートに直書きの HTML とテキスト。`baizy_img()` / `home_url()` のように決まった URL を出すだけの関数も静的として扱う
- 動的: ループ（`foreach` / `have_posts()`）、`$args`・Controller・Model 由来の値、`get_field()`、ショートコード・フォーム、条件分岐で出し分ける部分、`ExternalLinksManager` の URL

動的な部分を本文に入れると、その時点の値で固まって以後は更新されない。

### 3. 描画結果を取得する

```bash
bash .claude/skills/wp-editable-html/scripts/fetch-template-html.sh <URL> block-markup/{slug}.original.html
```

header / footer を除いたテンプレートの出力が保存される。ローカルのオリジン（`http://localhost:8080`）はルート相対（`/wp-content/...`・`/contact/`）に置き換わる。
テキスト・URL・属性値はこのファイルを正とする。テンプレートのソースから手で組み立てない。

### 4. 変換方針を決める

[references/block-markup.md](references/block-markup.md) の「変換の判断」に沿って、要素ごとにブロックにするか静的 HTML のままにするかを決める。既定は次のとおり。

- 編集可能なブロックにする: 見出し、段落、リスト、内容としての画像（src は元のまま。貼り付け後に管理画面でメディアの画像に置換する前提）
- 静的 HTML のまま: ボタン・リンク（文言や URL は「HTML を編集」で変える）、レイアウト用の要素、装飾画像、svg、フォームなど
- ページ全体を包むラッパー（`<main class="p-about">` など）は本文に入れず、テンプレート側に残す前提で進める（手順 7）

動的な部分があるときは、書き始める前に AskUserQuestion で扱いを確認する（区画ごとに、まとめて聞いてよい）。

- テンプレートに残す（推奨）: 本文には入れず、テンプレートで今までどおり出力する
- 今の値で静的化する: 本文に固定値として入れ、以後は管理画面で手で更新する
- 削除する

動的な部分がなければ、確認せずに進めてよい。

### 5. ブロックマークアップを書く

`block-markup/{slug}.original.html` を `block-markup/{slug}.html` にコピーし、それを Edit で書き換えていく（区切りコメントを足し、ブロックにするタグを保存形式に合わせる）。テキストを打ち直さないことで写し間違いを防ぐ。
マークアップは Write / Edit ツールで書き、シェルの heredoc や echo は使わない（zsh が `-` などを解釈して壊す）。

- トップレベルの `<section>` などのまとまりごとに 1 つの `<!-- wp:html -->` にする。エディターのリスト表示で区画が分かれ、「HTML を編集」の影響範囲も小さくなる
- wp:html どうしは空行 1 つで区切る。wp:html の外に HTML を置かない（クラシックブロックになる）
- 各ブロックの保存形式と落とし穴は [references/block-markup.md](references/block-markup.md) を見る。特に間違えやすいのは次の 3 つ
  - BEM の `--` は区切りコメントの JSON の中だけ `--` と書く
  - 画像ブロックの img には src と alt 以外を書けない（width / height / loading / class は検証エラー）
  - ブロックの中の `&quot;` `&#039;` などの文字参照は文字に戻す

### 6. 検証する

```bash
node .claude/skills/wp-editable-html/scripts/validate-blocks.cjs block-markup/{slug}.html --compare block-markup/{slug}.original.html
```

ローカルのエディター実機で、(1) 検証エラー、(2) 保存し直したときの書き換わり、(3) ブロック外の HTML と未登録のブロック、を調べる。NG が 0 になるまで直して再実行する。NG の直し方は references/block-markup.md の末尾にある。

`--compare` で出る DOM の差分は、すべて意図した変更かを確かめる。想定どおりの差分は、外したラッパー、画像の `<figure>` 包みと img から消えた class / width / height、テキストを包んだ `<p>` だけ。テキスト行（`"…"`）の差分は写し間違いなので必ず直す。

- ログインは既定で docker compose の wpcli から一時 Cookie（15 分で失効）を発行する。docker を使わない環境では `WP_USER` / `WP_PASS` を渡す
- 対象の URL は `--url` か `BAIZY_WP_URL` で変えられる
- エディターを開くため、実行のたびに自動下書きが 1 件できる（WordPress が 7 日後に自動で削除する）

### 7. テンプレート側の対応を提案する

固定ページでは `resources/pages/{slug}.php` が page-base.php より優先されるため、テンプレートがある限り貼り付けた本文は表示されない。次のどちらにするかを AskUserQuestion で確認し、了承を得てから変更する。

- テンプレートを残し、ブロック化した範囲を本文の出力に置き換える（推奨。ラッパーや動的な部分はテンプレートに残せる）

  ```php
  <main class="p-about">
      <?php while ( have_posts() ) : the_post(); ?>
      <?php the_content(); ?>
      <?php endwhile; ?>

      <?php // 動的な部分は従来どおりテンプレートで出力する ?>
  </main>
  ```

- テンプレートを削除して page-base.php で表示する（ラッパーも動的な部分もない場合）

テンプレートを変えたら `pnpm smoke` を実行する。
ローカルの固定ページにも本文を入れて表示を確かめるときは、既存の本文が上書きされることを伝えて了承を得てから流し込む。`--user` を付けるのは、権限のないユーザーとして保存すると kses で svg などが消えるため。

```bash
docker compose -f ../../docker-compose.yml run --rm --no-deps -T wpcli wp --user={管理者のログイン名} post update {ID} /var/www/html/wp-content/themes/baizy/block-markup/{slug}.html
```

### 8. クリップボードにコピーして報告する

`pbcopy < block-markup/{slug}.html` でクリップボードに入れ、次を報告する。

- 出力ファイルと貼り付け方
  1. 固定ページの編集画面を開く
  2. 右上の「︙」→「コードエディター」（⇧⌥⌘M）に切り替え、本文を置き換える形で貼り付ける。空の段落を選んでそのまま貼り付けてもブロックとして読み込まれる
  3. 「コードエディターを終了」し、区画ごとに「カスタム HTML」ブロックになっていること、中の見出しや段落が編集できることを確かめて保存する
- 編集可能にしたもの / 静的のままにしたもの（静的部分の変更は「HTML を編集」から）
- DOM の変化と CSS の調整候補（img の class が figure に移った箇所、`.wp-block-image` に付く `margin: 0 0 1em`、テキストを包んだ `<p>` の余白など）。SCSS は変更せず提案にとどめ、頼まれたら直す
- 動的な部分の扱いと、テンプレート側の対応状況
- URL をルート相対にしたこと（本番がサブディレクトリにある場合は書き換えが必要）。画像は貼り付け後にメディアの画像へ置換すると、width / height / srcset も自動で付く

## 注意

- 静的部分は、unfiltered_html 権限のないユーザー（投稿者・寄稿者、マルチサイトでは特権管理者以外）が保存すると kses でフィルタされ、svg や一部の属性が消える
- エディターのキャンバスには editor-style.css しか読み込まれないため、ページ用 CSS のレイアウトは再現されない（フロントの表示には影響しない）。編集画面の見た目も揃えたい場合は、editor-style.scss への取り込みを提案する
- 静的部分の `<script>` と `on*` 属性はエディターでは実行されない。JS はテーマ側（`resources/common/js/`）に置く
- `block-markup/` は本番に不要なので `scripts/create_dist.sh` で除外している
