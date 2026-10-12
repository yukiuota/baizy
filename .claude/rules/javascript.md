---
paths:
  - "resources/common/js/**/*.js"
  - "app/plugins/**/*.js"
---

# JavaScript ルール

## 基本

- jQuery を使わず、素の JavaScript（ES2020 以降）で書く
- `const` / `let` を使う（`var` は使わない）。関数はアロー関数を基本にする
- フォーマットは Prettier（`.prettierrc`: printWidth 120）に合わせる。文字列はダブルクォート、文末にセミコロンを付ける
- 変更後は `node --check <ファイル>` で構文エラーがないことを確認する

## 構成（resources/common/js/script.js）

- 機能ごとに既存の見出しコメントで区切る

```js
/************************************
機能名
*************************************/
```

- 機能は関数にまとめ、ファイル末尾の「実行」セクションの `DOMContentLoaded` から呼ぶ
- 対象の要素が存在しない場合は早期 return する（どのページでもエラーにしない）

```js
function headerMenu() {
  const menuTrigger = document.getElementById("js-menu-trigger");
  if (!menuTrigger) return;
  // ...
}
```

## DOM 操作

- JS から取得する要素は `js-` プレフィックスの id / class で指定する（スタイル用のクラスに依存しない）
- 状態はクラスの付け外し（`is-open` / `is-opened` / `active` など）で表し、見た目の変化やアニメーションは CSS 側で行う
- 高さを auto にするアニメーションなど、CSS だけで実現できない場合に限り Web Animations API（`element.animate()`）を使う
- スクロール連動の表示は `IntersectionObserver` を使う。`scroll` イベントで位置を監視しない
- ユーザーの入力や取得したデータを `innerHTML` で挿入しない（`textContent` を使う）

## 読み込み

- スクリプトは `Baizy\Setup\Scripts` で `defer` 付きで読み込んでいる。新しいファイルを追加するときも同じ方法で enqueue する
- 特定のページでしか使わないプラグイン連携用の JS は `app/plugins/{プラグイン名}/` に置き、必要なページでだけ読み込む
