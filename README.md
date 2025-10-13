# WordPress オリジナルテーマ制作テンプレート

クラシックテーマをベースにしたオリジナルテーマ開発用テンプレートです。ブロック（Gutenberg）関連の JS を Webpack でビルドします。

## 必要環境

- WordPress 6.6 以上
- PHP 8.0 以上
- Node.js 18 以上

## セットアップ（カスタムブロック開発）

1) 依存関係をインストール

```
npm install
```

2) 本番ビルド

```
npm run build
```

出力ファイル: `app/blocks/build/custom-blocks.js`

## ディレクトリ構成（抜粋）

- `app/blocks/src/` ブロック関連のソース
- `app/blocks/build/` Webpack 出力先
- `public/` 共通 CSS/JS・ページテンプレート等
- `include/` 分割テンプレート
- `style.css` テーマ情報（ヘッダー必須）
- `theme.json` テーマ設定

## 運用メモ

- `.gitignore` で `node_modules` やビルド成果物、エディタ設定等を除外しています。
- NPM パッケージとしての公開を避けるため `package.json` の `private: true` を設定しています。
- ライセンスは WordPress に合わせて GPL-2.0-or-later としています（`style.css` と `LICENSE` を参照）。


# テーマ制作メモ
## 画像表示
```
<picture>
<source srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" media="(max-width: 750px)">
<img src="<?php echo baizy_img('xx/xx.png'); ?>" srcset="<?php echo baizy_img('xx/xx.png'); ?> 1x, <?php echo baizy_img('xx/xx@2x.png'); ?> 2x" <?php baizy_img_wh('xx/xx.png'); ?> alt="">
</picture>


例:
<!-- loading="lazy"あり(デフォルト) -->
<img src="<?php echo baizy_img('sample.jpg'); ?>" <?php baizy_img_wh('sample.jpg'); ?> alt="">

<!-- loading="lazy"なし -->
<img src="<?php echo baizy_img('hero.jpg'); ?>" <?php baizy_img_wh('hero.jpg', false); ?> alt="">
```


## functions.phpの読み込み（オートロード）
composer dump-autoload

## ライセンス

GPL-2.0-or-later

詳細は `LICENSE` を参照してください。
