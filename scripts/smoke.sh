#!/usr/bin/env bash
#
# ローカル WordPress のフロント主要画面を巡回し、HTTP ステータスと PHP エラーの出力を確認するスモークテスト
#
# 使い方:
#   pnpm smoke                          # http://localhost:8080 を確認
#   pnpm smoke http://localhost:10013   # URL を指定
#   BAIZY_SMOKE_URL=http://... pnpm smoke
#
# 前提: WP_DEBUG が有効でエラーが画面に出る環境（docker-compose.yml の環境はこれを満たす）。
#
# 確認する URL は REST API から投稿タイプ・タクソノミーごとに1件ずつ自動で拾う。
# 投稿タイプやタクソノミーを追加してもこのスクリプトを直す必要はない。
# パーマリンク設定に依存しないよう、REST は /wp-json/ ではなく ?rest_route= で呼ぶ。
#
# docker-compose.yml が見つかれば、巡回中に wordpress コンテナのログに出た PHP エラーも拾う
# （HTML 属性の中など、画面に出ても目視しづらいエラーを見逃さないため）。
# 場所が違う場合は BAIZY_COMPOSE_FILE で指定する。

set -uo pipefail

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
THEME_SLUG="$(basename "$THEME_DIR")"
BASE_URL="${1:-${BAIZY_SMOKE_URL:-http://localhost:8080}}"
BASE_URL="${BASE_URL%/}"
COMPOSE_FILE="${BAIZY_COMPOSE_FILE:-$THEME_DIR/../../docker-compose.yml}"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

# html_errors が有効なら <b>Warning</b>:、無効なら行頭の Warning: で出力される
ERROR_PATTERN='<b>(Fatal error|Parse error|Warning|Notice|Deprecated)</b>:|^(PHP )?(Fatal error|Parse error|Warning|Notice|Deprecated): |critical error on this website'

checked=0
failed=0

for cmd in curl jq; do
	if ! command -v "$cmd" >/dev/null 2>&1; then
		echo "$cmd が必要です" >&2
		exit 1
	fi
done

# 1 URL を取得し、ステータスと PHP エラーを確認する
# $1: ラベル / $2: URL / $3: 期待するステータス
check() {
	local label="$1" url="$2" expected="$3" code errors
	checked=$((checked + 1))

	code="$(curl -s -L -o "$TMP_DIR/body" -w '%{http_code}' "$url")"
	errors="$(grep -E "$ERROR_PATTERN" "$TMP_DIR/body" | sed -e 's/<[^>]*>//g' | head -n 3)"

	if [ "$code" != "$expected" ] || [ -n "$errors" ]; then
		failed=$((failed + 1))
		printf 'NG  %s  %s  (%s, 期待値 %s)\n' "$label" "$url" "$code" "$expected"
		[ -n "$errors" ] && printf '%s\n' "$errors" | sed -e 's/^/      /'
	else
		printf 'OK  %s  %s\n' "$label" "$url"
	fi
}

rest() {
	curl -s "$BASE_URL/?rest_route=$1"
}

# 一覧 API の先頭1件のリンクを返す（0 件・エラー時は空）
first_link() {
	rest "$1&per_page=1&_fields=link" | jq -r 'if type == "array" then (.[0].link // empty) else empty end' 2>/dev/null
}

if ! curl -s -o "$TMP_DIR/home" "$BASE_URL/"; then
	echo "$BASE_URL に接続できません。WordPress が起動しているか確認してください（docker compose up -d）" >&2
	exit 1
fi

# 別テーマが有効だとテストの意味がないため、最初に確認して止める
if ! grep -q "themes/$THEME_SLUG/" "$TMP_DIR/home"; then
	echo "$BASE_URL では $THEME_SLUG テーマが有効になっていないようです" >&2
	exit 1
fi

since="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "==> $BASE_URL を確認します"

check "トップ" "$BASE_URL/" 200
check "検索" "$BASE_URL/?s=a" 200
check "404" "$BASE_URL/?p=999999999" 404

# 投稿タイプ: 詳細ページと（post / page 以外は）アーカイブ
# wp_* はブロックテーマ用・内部用の投稿タイプなので除外する
while read -r type ns base; do
	[ -z "$type" ] && continue
	link="$(first_link "/$ns/$base")"
	if [ -n "$link" ]; then
		check "詳細: $type" "$link" 200
	else
		echo "--  詳細: $type  公開済みの記事がないためスキップ"
	fi
	if [ "$type" != "post" ] && [ "$type" != "page" ]; then
		check "アーカイブ: $type" "$BASE_URL/?post_type=$type" 200
	fi
done < <(rest /wp/v2/types | jq -r '
	to_entries[]
	| select(.key | test("^(attachment|nav_menu_item|wp_.*)$") | not)
	| select(.value.rest_base != null)
	| "\(.key) \(.value.rest_namespace // "wp/v2") \(.value.rest_base)"' 2>/dev/null)

# タクソノミー: 記事が紐づいたタームのアーカイブ
while read -r tax ns base; do
	[ -z "$tax" ] && continue
	link="$(first_link "/$ns/$base&hide_empty=true")"
	if [ -n "$link" ]; then
		check "ターム: $tax" "$link" 200
	else
		echo "--  ターム: $tax  記事のあるタームがないためスキップ"
	fi
done < <(rest /wp/v2/taxonomies | jq -r '
	to_entries[]
	| select(.key | test("^(nav_menu|wp_.*)$") | not)
	| select(.value.rest_base != null)
	| "\(.key) \(.value.rest_namespace // "wp/v2") \(.value.rest_base)"' 2>/dev/null)

# コンテナログの PHP エラー（docker-compose 環境のときだけ）
if [ -f "$COMPOSE_FILE" ] && command -v docker >/dev/null 2>&1; then
	log_errors="$(docker compose -f "$COMPOSE_FILE" logs --no-log-prefix --since "$since" wordpress 2>/dev/null |
		grep -E 'PHP (Fatal error|Parse error|Warning|Notice|Deprecated)' | sort -u | head -n 10)"
	checked=$((checked + 1))
	if [ -n "$log_errors" ]; then
		failed=$((failed + 1))
		echo "NG  コンテナログに PHP エラー"
		printf '%s\n' "$log_errors" | sed -e 's/^/      /'
	else
		echo "OK  コンテナログに PHP エラーなし"
	fi
fi

echo ""
echo "==> ${checked} 件中 ${failed} 件 NG"
[ "$failed" -eq 0 ]
