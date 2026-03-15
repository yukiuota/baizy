<?php
namespace Baizy\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class EscapeHelper {

    /** HTML エスケープして出力 */
    public static function html( string $str ): void {
        echo esc_html( $str );
    }

    /** 属性値エスケープして出力 */
    public static function attr( string $str ): void {
        echo esc_attr( $str );
    }

    /** URL エスケープして出力 */
    public static function url( string $str ): void {
        echo esc_url( $str );
    }

    /** JavaScript エスケープして出力 */
    public static function js( string $str ): void {
        echo esc_js( $str );
    }
}
