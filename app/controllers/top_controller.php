<?php
namespace Baizy\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class TopController {

	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	public function getNews( int $limit = 5 ): array {
		return \Baizy\Models\PostModel::getLatestNews( $limit );
	}
}
