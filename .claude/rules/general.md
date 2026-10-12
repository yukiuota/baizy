# 全体ルール（Baizy テーマ）

クラシックテーマベースのオリジナルテーマ開発用テンプレート。全体像は [README.md](../../README.md) を参照。

## 動作要件

- PHP 8.0 以上 / WordPress 6.6 以上を前提にする（PHP 8.0 で動かない構文・関数は使わない）
- Node.js 22.12.0 以上、パッケージマネージャーは **pnpm**（npm / yarn は使わない）

## ディレクトリの役割

| パス | 役割 |
|------|------|
| `functions.php` | 定数定義と `vendor/autoload.php` の読み込みのみ。処理を書き足さない |
| `index.php` / `header.php` / `footer.php` | WP 標準テンプレート。中身は `resources/layouts/index.php` へ委譲する |
| `app/setup/` | テーマ初期化クラス（`Baizy\Setup\*`） |
| `app/models/` | データ取得・整形（`Baizy\Models\*`） |
| `app/controllers/` | ビューへ渡す配列の組み立て（`Baizy\Controllers\*`） |
| `app/helpers/` | ユーティリティ（`Baizy\Helpers\*`） |
| `app/services/` | 独立した機能のサービスクラス（`Baizy\Services\*`） |
| `app/functions/` | フック登録・グローバル関数（Composer の `files` で読み込み） |
| `resources/` | ビュー（テンプレート）・SCSS・JS・設定 JSON |
| `patterns/` | ブロックパターン |
| `data/field-groups/` | ACF / SCF の JSON 同期ファイル（手で編集しない） |
| `sample/` | 参照用サンプル。本番には含まれない |

## ファイル追加時の手順

- `app/setup|models|controllers|helpers|services/` にクラスを追加したら `composer dump-autoload` を実行する（classmap オートロード）
- `app/functions/` にファイルを追加したら `composer.json` の `autoload.files` に追記し、`composer dump-autoload` を実行する
- ページ別 CSS を追加したら `package.json` の `sass:build` / `sass:watch` の両方に入出力を追記する
- 本番に不要なファイル・ディレクトリを追加したら `scripts/create_dist.sh` の除外リストに追加する

## 動作確認

- PHP の構文・phpcs・SCSS のビルドは hooks（`.claude/hooks/`）が編集のたびに自動で実行する。hooks からエラーが返ったらその場で直す
- テンプレート・PHP の変更を終えたら `pnpm smoke` を実行し、NG がゼロであることを確認する（ローカル WordPress が `http://localhost:8080` で起動している前提）

## プラグインとの境界

以下はテーマから分離済み。テーマ側に実装を戻さない。

- カスタムブロック → `baizy-custom-blocks` プラグイン
- カラーパレット管理 → `baizy-color-palette` プラグイン
- タームの背景色 → `baizy-term-color` プラグイン（テーマから呼ぶときは `function_exists()` でガード）

ACF / SCF などプラグインの関数も同様に、未有効時に致命的エラーにならないようガードする（例: `PageMetaModel::field()`）。

## コメント・ドキュメント

- コメントは日本語で書く。「何をするか」より「なぜそうするか」を優先して書く
- 機能・構成・コマンドを変更したら README.md の該当箇所も更新する

## 編集しないもの

- `vendor/` / `node_modules/` / `dist/`
- `resources/common/css/*.css`（SCSS のコンパイル結果。修正は SCSS 側で行い `pnpm sass:build` する）
- `data/field-groups/*.json`（管理画面からの同期で生成される）
