#!/usr/bin/env bash
#
# PostToolUse: PHP ファイルの編集直後に構文チェックとコーディング規約チェックを行う
# 「変更後は composer phpcs」の実行漏れを防ぎ、指摘をその場で Claude に修正させるため
#
# - すべての PHP: php -l（構文エラーは exit 2 で Claude に返す）
# - phpcs.xml の対象（app/ ・ functions.php）: phpcbf で自動修正 → 残った指摘を exit 2 で返す

set -euo pipefail

root="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
file="$(jq -r '.tool_input.file_path // empty')"

case "$file" in
	*.php) ;;
	*) exit 0 ;;
esac

rel="${file#"$root"/}"
case "$rel" in
	/* | vendor/* | node_modules/* | dist/*) exit 0 ;;
esac
[ -f "$file" ] || exit 0

cd "$root"

if ! out="$(php -l "$rel" 2>&1)"; then
	printf 'PHP 構文エラー:\n%s\n' "$out" >&2
	exit 2
fi

# phpcs.xml の <file> と同じ範囲だけ規約チェックする（テンプレートは対象外）
case "$rel" in
	app/* | functions.php) ;;
	*) exit 0 ;;
esac
[ -x vendor/bin/phpcs ] || exit 0

# phpcbf の終了コード 1 = 自動修正あり。Claude が古い内容のまま次の Edit をしないよう知らせる
fixed=0
vendor/bin/phpcbf -q --no-colors "$rel" >/dev/null 2>&1 || [ $? -ne 1 ] || fixed=1

if ! out="$(vendor/bin/phpcs -q --no-colors --report=full "$rel" 2>&1)"; then
	{
		[ "$fixed" -eq 1 ] && echo "phpcbf で自動修正できる分は修正済みです（ファイルを読み直してから編集してください）。"
		echo "phpcs の指摘が残っています。修正してください:"
		echo "$out"
	} >&2
	exit 2
fi

if [ "$fixed" -eq 1 ]; then
	jq -n --arg f "$rel" '{hookSpecificOutput: {hookEventName: "PostToolUse", additionalContext: ("phpcbf が " + $f + " を自動整形しました。続けて編集する場合は読み直してください。")}}'
fi
exit 0
