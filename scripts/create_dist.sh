#!/usr/bin/env bash
#
# 本番アップ用の dist/baizy/ を生成するスクリプト
#
# 使い方:
#   bash scripts/create_dist.sh
#
# 事前にアセットをビルドしておくこと:
#   pnpm run sass:build  (SCSS → resources/common/css/)
#
# カスタムブロックは baizy-custom-blocks プラグインに分離済みのため、
# テーマの dist にブロックのビルドは不要。
#
# 生成後は FTP クライアントで dist/baizy/ の中身だけを
# 本番の wp-content/themes/baizy/ にアップすればOK。

set -euo pipefail

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DIST_DIR="$THEME_DIR/dist/baizy"

echo "==> dist を再生成: $DIST_DIR"
rm -rf "$THEME_DIR/dist"
mkdir -p "$DIST_DIR"

rsync -a \
	--exclude '.git/' \
	--exclude '.gitignore' \
	--exclude '.DS_Store' \
	--exclude '.agents/' \
	--exclude '.claude/' \
	--exclude '.idea/' \
	--exclude 'node_modules/' \
	--exclude 'vendor/' \
	--exclude 'dist/' \
	--exclude 'mcp/' \
	--exclude 'sample/' \
	--exclude 'scripts/' \
	--exclude 'baizy-custom-blocks/' \
	--exclude 'baizy-term-color/' \
	--exclude 'baizy-color-palette/' \
	--exclude 'resources/common/scss/' \
	--exclude '.npmrc' \
	--exclude '.prettierrc' \
	--exclude '.env' \
	--exclude '*.local' \
	--exclude '*.log' \
	--exclude '*.map' \
	--exclude 'package.json' \
	--exclude 'pnpm-lock.yaml' \
	--exclude 'pnpm-workspace.yaml' \
	--exclude 'performance-check.js' \
	--exclude 'performance-report.json' \
	--exclude 'performance-trace.json' \
	--exclude 'PERFORMANCE_REPORT.md' \
	--exclude 'README.md' \
	--exclude 'phpcs' \
	--exclude 'phpcs.xml' \
	"$THEME_DIR/" "$DIST_DIR/"

echo "==> 本番用 vendor/ を生成 (composer install --no-dev)"
composer install \
	--no-dev \
	--optimize-autoloader \
	--no-interaction \
	--quiet \
	--working-dir="$DIST_DIR"

# オートローダー生成後は composer 関連ファイルは不要
rm -f "$DIST_DIR/composer.json" "$DIST_DIR/composer.lock"

echo ""
echo "完了: dist/baizy/ の中身をそのまま本番の themes/baizy/ にアップしてください。"
du -sh "$DIST_DIR"
