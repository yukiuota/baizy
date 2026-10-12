#!/usr/bin/env bash
#
# PreToolUse: 生成物・外部管理ファイルへの直接編集を止める
# .claude/rules/general.md「編集しないもの」をルールの記述だけに頼らず仕組みで守るため
#
# exit 2 で編集をブロックし、stderr の理由を Claude に返す

set -euo pipefail

root="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
file="$(jq -r '.tool_input.file_path // .tool_input.notebook_path // empty')"
[ -z "$file" ] && exit 0

rel="${file#"$root"/}"

case "$rel" in
	vendor/* | node_modules/* | dist/*)
		reason="Composer / pnpm / dist 生成で上書きされるディレクトリです。元のソースか設定を修正してください"
		;;
	resources/common/css/*.css)
		reason="SCSS のコンパイル結果です。resources/common/scss/ 側を編集してください（保存時に pnpm sass:build が自動実行されます）"
		;;
	data/field-groups/*.json)
		reason="ACF / SCF の管理画面からの同期で生成されるファイルです。フィールドは管理画面で編集してください"
		;;
	*)
		exit 0
		;;
esac

echo "編集をブロックしました: ${rel} — ${reason}" >&2
exit 2
