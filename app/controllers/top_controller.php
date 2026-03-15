<?php
namespace Baizy\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

class TopController {

    public function getNews( int $limit = 5 ): array {
        return \Baizy\Models\PostModel::getLatestNews( $limit );
    }
}
