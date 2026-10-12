#!/usr/bin/env bash
#
# PostToolUse: SCSS の編集直後に pnpm sass:build を実行する
# コンパイル済み CSS（resources/common/css/）の更新漏れと、SCSS の構文エラーの見逃しを防ぐため

set -euo pipefail

root="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
file="$(jq -r '.tool_input.file_path // empty')"

case "${file#"$root"/}" in
	resources/common/scss/*.scss) ;;
	*) exit 0 ;;
esac

cd "$root"
if ! out="$(pnpm -s sass:build 2>&1)"; then
	printf 'pnpm sass:build が失敗しました:\n%s\n' "$out" >&2
	exit 2
fi
exit 0
