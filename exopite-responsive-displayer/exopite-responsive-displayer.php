<?php
/**
 * Exopite Responsive Displayer
 *
 * @link              http://joe.szalai.org
 * @since             20171221
 * @package           Exopite_Responsive_Displayer
 *
 * @wordpress-plugin
 * Plugin Name:       Exopite Responsive Displayer
 * Plugin URI:        https://joe.szalai.org/exopite/exopite-responsive-displayer/
 * Description:       Conditional display for different devices to control which content is being displayed via shortcodes, class names or hooks, depending on the visitor's device.
 * Version:           20200608
 * Author:            Joe Szalai
 * Author URI:        https://joe.szalai.org
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       exopite-responsive-displayer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

define( 'EXOPITE_RESPONSIVE_DISPLAYER_PLUGIN_NAME', 'exopite-responsive-displayer' );
define( 'EXOPITE_RESPONSIVE_DISPLAYER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * PHP Simple HTML DOM Parser
 * @link https://sourceforge.net/projects/simplehtmldom/files/
 *
 * Docs:
 * - https://stackoverflow.com/questions/14264525/php-simple-html-dom-parser-select-only-divs-with-multiple-classes
 * - https://code.tutsplus.com/tutorials/html-parsing-and-screen-scraping-with-the-simple-html-dom-library--net-11856
 * - https://stackoverflow.com/questions/4812691/preserve-line-breaks-simple-html-dom-parser
 */
if( ! class_exists( 'simple_html_dom' ) ) {
    require_once( plugin_dir_path( __FILE__ ) . '/simple_html_dom.1.9.1.php' );
}

require_once( plugin_dir_path( __FILE__ ) . '/class-exopite-mobile-detect.php' );

class Exopite_Responsive_Displayer
{

    private static $debug = false;

    private static $add_body_classes = false;

    private static $add_shortcodes = true;

    private static $remove_classes = true;

    private static $devices = array(
        'tablet',
        'mobile',
        'android',
        'android-mobile',
        'android-tablet',
        'ios',
        'iphone',
        'ipad',
        'linux-desktop',
        'mac',
        'desktop',
        'windows-desktop',
        'windows-mobile',
        'windows-tablet',
        'blackberry',
        'bot'
    );

    /**
     * Checks if the current request is a WP REST API request.
     *
     * Case #1: After WP_REST_Request initialisation
     * Case #2: Support "plain" permalink settings
     * Case #3: URL Path begins with wp-json/ (your REST prefix)
     *          Also supports WP installations in subfolders
     *
     * @returns boolean
     * @author matzeeable
     * @link https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist/317041#317041
     * @link https://gist.github.com/matzeeable/dfd82239f48c2fedef25141e48c8dc30
     */
    public static function is_rest_request() {
        $prefix = rest_get_url_prefix( );
        if (defined('REST_REQUEST') && REST_REQUEST // (#1)
            || isset($_GET['rest_route']) // (#2)
                && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix , 0 ) === 0)
            return true;

        // (#3)
        $rest_url = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    }

    /**
     * @link https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist/279422#279422
     * @link https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist/339174#339174
     */
    public static function is_rest_url() {
        return ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-json/' ) !== false);
    }

    public static function is_rest() {
        return ( self::is_rest_request() || self::is_rest_url() );
    }

    public static function is_api_request() {

        if (
                ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) ||
                self::is_rest() ||
                ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ||
                ( defined('DOING_AJAX') && DOING_AJAX ) ||
                wp_doing_ajax()

            ) {
            return true;
        }

        return false;

    }

    public static function init() {

        if ( is_admin() || self::is_api_request() ) {
            return;
        }

        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ||empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return;
        }

        self::$debug = apply_filters( 'exopite-responsive-displayer-debug' , self::$debug );
        self::$add_body_classes = apply_filters( 'exopite-responsive-displayer-add-body-classes' , self::$add_body_classes );
        self::$devices = apply_filters( 'exopite-responsive-displayer-devices' , self::$devices );
        self::$add_shortcodes = apply_filters( 'exopite-responsive-displayer-add-shortcodes' , self::$add_shortcodes );
        self::$remove_classes = apply_filters( 'exopite-responsive-displayer-remove-classes' , self::$remove_classes );

        if ( self::$remove_classes ) {

            /**
             * Start buffering when wp_loaded hook called
             * end buffering when showdown hook called
             *
             * @link https://codex.wordpress.org/Plugin_API/Action_Reference
             * @link https://wordpress.stackexchange.com/questions/214498/when-is-wp-loaded-initiated-only-with-admin-or-only-when-user-enters-the-site-or
             */
            add_action('wp_loaded', array( 'Exopite_Responsive_Displayer', 'buffer_start' ) );
            add_action('shutdown', array( 'Exopite_Responsive_Displayer', 'buffer_end' ) );
        }

        if ( self::$add_shortcodes ) {

            // Generates [android][/android], ... shortcodes
            foreach ( self::$devices as $device ) {
                add_shortcode( $device, array( 'Exopite_Responsive_Displayer', 'shortcode_callback' ) );
            }
        }

        if ( self::$add_body_classes ) {
            add_filter( 'body_class', array( 'Exopite_Responsive_Displayer', 'add_body_classes' ) );
        }

    }

    // This will run on any not existing function call
    public static function __callStatic( $name, $args ) {

        // Check if called function without the 'is_' prefix, exist in devices list
        if ( in_array( str_replace( 'is_', '', $name ), self::$devices ) ) {

            // Check if called function name is a valid Exopite_Device_Detector methode
            if ( method_exists( 'Exopite_Device_Detector', $name ) ) {

                // Check if called function name is mathc with the current device
                if ( Exopite_Device_Detector::$name() ) {

                    // Do action with the exopite-responsive-displayer-{called function name} but replace '_' to '-'
                    do_action( 'exopite-responsive-displayer-' . str_replace( '_', '-', $name ) );
                }
            }

        }

    }

    public static function add_body_classes( $classes ) {

        // Loop trough devices list
        foreach ( self::$devices as $device ) {

            // Add 'is_' prefix to shortcode name and replace '-' to '_'
            $name = 'is_' . str_replace( '-', '_', $device );

            // Check if shortcode name is a valid methode in Exopite_Device_Detector
            if ( method_exists( 'Exopite_Device_Detector', $name ) ) {

                // Check if shortcode name is the current device
                if ( Exopite_Device_Detector::$name() ) {

                    // If current device is not in body classes, then add it.
                    if ( ! in_array( $device, $classes ) ) {
                        $classes[] = $device;
                    }
                }
            }
        }

        return $classes;

    }

    public static function buffer_start() {

        // Start output buffering with a callback function
        ob_start( array( 'Exopite_Responsive_Displayer', 'remover_callback' ) );

    }

    public static function buffer_end() {

        // Display buffer
        if ( ob_get_length() ) ob_end_flush();

    }

    public static function shortcode_callback( $atts, $content = null, $tag ) {

        // Add 'is_' prefix to shortcode name and replace '-' to '_'
        $name = 'is_' . str_replace( '-', '_', $tag );

        // Check if shortcode name is a valid methode in Exopite_Device_Detector
        if ( method_exists( 'Exopite_Device_Detector', $name ) ) {

            // Check if shortcode name is the current device
            if ( Exopite_Device_Detector::$name() ) {

                // Show content, can be override via filters
                return do_shortcode( apply_filters( 'exopite-responsive-displayer-' . $tag . '-shortcode-content', $content ) );
            }
        }

    }


    public static function remover_callback( $buffer ) {

        if ( ! is_admin() && ! self::is_api_request() ) {

            if ( self::$debug ) $before = microtime(true);

            $class_to_remove = array();

            // Loop trough devices list
            foreach ( self::$devices as $device ) {

                // Add 'is_' prefix to shortcode name and replace '-' to '_'
                $name = 'is_' . str_replace( '-', '_', $device );

                // Check if shortcode name is a valid methode in Exopite_Device_Detector
                if ( method_exists( 'Exopite_Device_Detector', $name ) ) {

                    // Check if it is the current device, if yes, add classes to remove list
                    if ( Exopite_Device_Detector::$name() ) {
                        $class_to_remove[] = '.remove-' . $device;
                    }
                }
            }

            if ( self::$debug ) $class_to_remove[] = '.remove-test';

            // Classes can be override via filters
            $class_to_remove = apply_filters( 'exopite-responsive-displayer-remove-classes', $class_to_remove );

            // Create a Simple DOM object
            $html = new simple_html_dom();

            // Load HTML from a string/variable
            $html->load( $buffer, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT );

            // Get all items with class and loop trough
            foreach( $html->find( implode( ',', $class_to_remove ) ) as $element ){

                if ( self::$debug ) {

                    $element->innertext = '<p><span style="color:red;"><b>DEBUG ON</b></span><br><b>Class(es) to remove:</b> <i>' . implode( ',', $class_to_remove ) . '</i></p>';
                    $element->innertext .= '<p><b>User Agent:</b> <i>' . $_SERVER['HTTP_USER_AGENT'] . '</i></p>';

                } else {

                    // Remove element
                    $element->outertext = apply_filters( 'exopite-responsive-displayer-machted-classes', '' );

                }


            }

            $buffer = $html->save();

            $html->clear();
            unset($html);

            $log = '';

            if ( self::$debug ) $log .= '<!-- Exopite Responsive Remover plugin HTML parsing last for ' . number_format( ( microtime(true) - $before ), 4 ) . " s. -->\n";
        }

        if ( self::$debug ) {
            if ( ! ( ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) ) {
                $buffer = $buffer . $log;
            }
        }

        // file_put_contents( EXOPITE_RESPONSIVE_DISPLAYER_PATH . 'buffer.log', var_export( $buffer, true ) . "\n\n" );

        return $buffer;
    }

}

/**
 * This plugin based on HTTP_USER_AGENT, so if it not set, we have nothing to do.
 */
if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
    Exopite_Responsive_Displayer::init();
}

/**
 * Updater
 */
if ( is_admin() ) {

    /**
     * A custom update checker for WordPress plugins.
     *
     * Useful if you don't want to host your project
     * in the official WP repository, but would still like it to support automatic updates.
     * Despite the name, it also works with themes.
     *
     * @link http://w-shadow.com/blog/2011/06/02/automatic-updates-for-commercial-themes/
     * @link https://github.com/YahnisElsts/plugin-update-checker
     * @link https://github.com/YahnisElsts/wp-update-server
     */
    if( ! class_exists( 'Puc_v4_Factory' ) ) {

        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_RESPONSIVE_DISPLAYER_PATH, 'vendor', 'plugin-update-checker', 'plugin-update-checker.php' ) );

    }

    $MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://update.joeszalai.org/?action=get_metadata&slug=' . EXOPITE_RESPONSIVE_DISPLAYER_PLUGIN_NAME, //Metadata URL.
        __FILE__, //Full path to the main plugin file.
        EXOPITE_RESPONSIVE_DISPLAYER_PLUGIN_NAME //Plugin slug. Usually it's the same as the name of the directory.
    );

}
// End Update

/*
 * How to use
 */

// Hooks
// -----

// function test_m() {
//     echo "Mobile hook<br>";
// }

// function test_d() {
//     echo "Dekstop hook<br>";
// }

// function test_t() {
//     echo "Tablet hook<br>";
// }

// add_action( 'exopite-responsive-displayer-is-desktop', 'test_d' );
// add_action( 'exopite-responsive-displayer-is-mobile', 'test_m' );
// add_action( 'exopite-responsive-displayer-is-tablet', 'test_t' );

// Exopite_Responsive_Displayer::is_mobile();
// Exopite_Responsive_Displayer::is_tablet();
// Exopite_Responsive_Displayer::is_desktop();

// Shortcodes
// ----------

// [tablet]Only on tablet[/tablet]
// [mobile]Only on mobile[/mobile]
// [android]Only on android[/android]
// [android-mobile]Only on android-mobile[/android-mobile]
// [android-tablet]Only on android-tablet[/android-tablet]
// [ios]Only on ios[/ios]
// [iphone]Only on iphone[/iphone]
// [ipad]Only on ipad[/ipad]
// [linux-desktop]Only on linux-desktop[/linux-desktop]
// [mac]Only on mac[/mac]
// [desktop]Only on desktop[/desktop]
// [windows-desktop]Only on windows-desktop[/windows-desktop]
// [windows-mobile]Only on windows-mobile[/windows-mobile]
// [windows-tablet]Only on windows-tablet[/windows-tablet]
// [blackberry]Only on blackberry[/blackberry]
// [bot]Only on bot[/bot]

// Classes to remove
// -----------------

// <div class='tablet'>Remove this on tablet</div>
// <div class='mobile'>Remove this on mobile</div>
// <div class='android'>Remove this on android</div>
// <div class='android-mobile'>Remove this on android-mobile</div>
// <div class='android-tablet'>Remove this on android-tablet</div>
// <div class='ios'>Remove this on ios</div>
// <div class='iphone'>Remove this on iphone</div>
// <div class='ipad'>Remove this on ipad</div>
// <div class='linux-desktop'>Remove this on linux-desktop</div>
// <div class='mac'>Remove this on mac</div>
// <div class='desktop'>Remove this on desktop</div>
// <div class='windows-desktop'>Remove this on windows-deskto</div>
// <div class='windows-mobile'>Remove this on windows-mobile</div>
// <div class='windows-tablet'>Remove this on windows-tablet</div>
// <div class='blackberry'>Remove this on blackberry</div>
// <div class='bot'>Remove this on bot</div>
