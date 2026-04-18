<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// クラス実装は app/Setup/ 以下を参照
new Baizy\Setup\ThemeSetup();
new Baizy\Setup\Scripts();
new Baizy\Setup\Customizer();
