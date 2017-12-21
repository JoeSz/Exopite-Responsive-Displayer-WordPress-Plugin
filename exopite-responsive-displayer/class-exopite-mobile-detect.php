<?php
if ( ! defined( 'WPINC' ) ) die;

class Exopite_Device_Detector
{

    public static function is_bot() {
        $bots = array( "google", "duckduckbot", "msnbot", "bingbot", "ask", "facebook", "yahoo", "addthis" );
        $patterns = implode( '|', $bots );
        return preg_match( '/' . $patterns . '/i', $_SERVER['HTTP_USER_AGENT'] );
    }

    public static function is_windows() {
        return (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Windows' );
    }

    public static function is_mac() {
        return ( ! self::is_ipad() && ! self::is_iphone() && (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Mac' ) );
    }

    public static function is_ipad() {
        // Mozilla/5.0 (iPad; CPU OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B137 Safari/601.1
        $is_ipad = (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'iPad' );
        if ( $is_ipad )
            return true;
        else return false;
    }

    public static function is_iphone() {
        // Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B137 Safari/601.1
        return ( ! self::is_ipad() && ( (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) ) );
    }

    public static function is_ios() { // if the user is on any iOS Device
        return ( self::is_iphone() || self::is_ipad() );
    }

    public static function is_blackberry() {
        return (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' );

    }

    private static function is_mobile_or_phone() {
        return (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) || (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Phone' );
    }

    public static function is_windows_mobile() {
        return ( self::is_windows() && self::is_mobile_or_phone() );
    }

    public static function is_windows_desktop() {
        return ( self::is_windows() && ! self::is_mobile_or_phone() );
    }

    public static function is_windows_tablet() {
        $is_tablet = (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Tablet' );
        return ( self::is_windows() && $is_tablet );
    }

    public static function is_android() {
        return (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' );

    }

    public static function is_linux_desktop() { // if the user is on any iOS Device
        return ( (bool) strpos( $_SERVER['HTTP_USER_AGENT'], 'Linux' ) && ! self::is_android() );
    }

    public static function is_android_mobile() {
        return ( self::is_android() && self::is_mobile_or_phone()  );
    }

    public static function is_android_tablet() { // detect only Android tablets
        global $is_iphone;
        return ( self::is_android() && ! self::is_android_mobile() );
    }

    public static function is_mobile() {
        global $is_iphone;
        return ( ! self::is_ipad() && ( self::is_android_mobile() || $is_iphone || self::is_iphone() || self::is_blackberry() || self::is_windows_mobile() ) );
    }

    public static function is_tablet() {
        return ( self::is_android_tablet() || self::is_ipad() );
    }

    public static function is_desktop() {
        return ( ! self::is_tablet() && ! self::is_mobile() );
    }

}
