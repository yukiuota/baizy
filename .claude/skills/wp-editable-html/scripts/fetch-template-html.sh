#!/usr/bin/env bash
#
# ページの描画結果から header / footer を除いた部分（= テンプレート本体の出力）を取り出す
#
# 使い方:
#   bash .claude/skills/wp-editable-html/scripts/fetch-template-html.sh <URL> <出力ファイル>
#
# header_base.php 末尾の <!-- /header --> と footer_base.php 先頭の <!-- footer --> の間を切り出す。
# 本番に localhost の URL を持ち込まないよう、取得元のオリジン（例: http://localhost:8080）は
# ルート相対（/wp-content/... や /about/）に置き換える。

set -euo pipefail

url="${1:?URL を指定してください}"
out="${2:?出力ファイルを指定してください}"
origin="$(printf '%s' "$url" | sed -E 's#^(https?://[^/]+).*#\1#')"

tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

code="$(curl -s -L -o "$tmp" -w '%{http_code}' "$url")"
if [ "$code" != "200" ]; then
	echo "${url} の取得に失敗しました（HTTP ${code}）。ページが公開されているか確認してください" >&2
	exit 1
fi

# エラー文言が混ざったまま変換すると、そのまま本文に貼られてしまうため止める
if grep -qE '<b>(Fatal error|Parse error|Warning|Notice|Deprecated)</b>:' "$tmp"; then
	echo "PHP のエラーが出力されています。先にテンプレートを修正してください:" >&2
	grep -E '<b>(Fatal error|Parse error|Warning|Notice|Deprecated)</b>:' "$tmp" | sed -e 's/<[^>]*>//g' | head -n 5 >&2
	exit 1
fi

if ! grep -q '<!-- /header -->' "$tmp" || ! grep -q '<!-- footer -->' "$tmp"; then
	echo "目印のコメント（<!-- /header --> / <!-- footer -->）が見つかりません。header / footer パーツを確認してください" >&2
	exit 1
fi

mkdir -p "$(dirname "$out")"
ORIGIN="$origin" perl -0777 -ne '
	my $o = quotemeta $ENV{ORIGIN};
	if ( m{<!-- /header -->\n?(.*?)[ \t]*<!-- footer -->}s ) {
		my $c = $1;
		$c =~ s{$o/}{/}g;               # http://localhost:8080/about/ → /about/
		$c =~ s{$o(?=["\x27])}{/}g;     # home_url() のように末尾スラッシュがない場合
		print $c;
	}
' "$tmp" >"$out"

echo "保存しました: ${out}（$(wc -l <"$out" | tr -d ' ') 行）"
