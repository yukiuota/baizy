# baizy

## ディレクトリ構造
baizy/
├── app/
│   ├── Controllers/       # 各ページや機能の処理をまとめる (クラス or 関数)
│   ├── Models/            # DBアクセスやビジネスロジック (WP_QueryやカスタムDB操作)
│   ├── Services/          # 外部API連携や複雑な処理
│   ├── Helpers/           # 共通関数
│   └── bootstrap.php      # 初期読み込み
│
├── resources/
│   ├── views/             # ビュー（TwigやBlade, または get_template_part()用のphp）
│   │   ├── partials/      # ヘッダー・フッター・共通UI
│   │   ├── pages/         # 固定ページ用
│   │   ├── archives/      # archiveページ用
│   │   ├── single/        # singleページ用
│   │   ├── components/    # 小さいUIパーツ
│   │   └── blocks/        # 小さいUIパーツ
│   └── blocks/            # ブロック出力先
│
├── public/                # CSS/JS/画像
│   └── common/
│   │   ├── css/
│   │   ├── scss/
│   │   ├── js/
│   └── imgs/
│
├── functions.php          # 最小限にして app/bootstrap.php を読み込む
├── style.css              # テーマ情報のみ
├── index.php              # WordPressテーマ必須
├── single.php
├── archive.php
└── ...
