<?php 
if ( !defined( 'ABSPATH' ) ) exit;
?>
<?php if ( ! is_user_logged_in() ) : ?>
<script type="speculationrules">
{
  "prerender": [
    {
      "where": {
        "and": [
          { "href_matches": "/*" },
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
    echo wp_kses_post( $head_top_code ) . "\n";
}
?>