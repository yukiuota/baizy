<?php 
if ( !defined( 'ABSPATH' ) ) exit;

// 投機的読み込み（リンク先の事前レンダリング）。WP コア側の出力は app/functions/seo.php で止めている
// 管理画面・ログイン画面・クエリ文字列付きの URL（ログアウトや nonce 付きのリンクなど）は、
// 事前に読み込むだけで処理が実行されるおそれがあるため対象外にする
?>
<?php if ( ! is_user_logged_in() ) : ?>
<script type="speculationrules">
{
  "prerender": [
    {
      "where": {
        "and": [
          { "href_matches": "/*" },
          { "not": { "href_matches": ["/wp-*.php", "/wp-admin/*", "/*\\?(.+)"] } },
          { "not": { "selector_matches": "[rel~=nofollow]" } },
          { "not": { "selector_matches": "[rel~=external]" } },
          { "not": { "selector_matches": "[data-no-prerender]" } }
        ]
      },
      "eagerness": "moderate"
    }
  ]
}
</script>
<?php endif; ?>

<?php 
// head上部に追加するタグ
// カスタマイザーで設定されたコードを出力
$head_top_code = get_theme_mod( 'baizy_head_top_code', '' );
if ( !empty( $head_top_code ) ) {
    echo $head_top_code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>