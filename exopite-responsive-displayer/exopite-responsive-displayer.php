<?php
/**
 * Exopite Responsive Displayer
 *
 * @link              http://joe.szalai.org
 * @since             20170723
 * @package           Exopite_Responsive_Displayer
 *
 * @wordpress-plugin
 * Plugin Name:       Exopite Responsive Displayer
 * Plugin URI:        https://joe.szalai.org/
 * Description:       Remove HTML elements with class name "remove-destop", "remove-mobile", "remove-tablet"
 * Version:           20170725
 * Author:            Joe Szalai
 * Author URI:        http://joe.szalai.org
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       exopite-responsive-displayer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

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
    require_once( plugin_dir_path( __FILE__ ) . '/simple_html_dom.php' );
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

    public static function init() {

        self::$debug = apply_filters( 'exopite-responsive-displayer-debug' , self::$debug );
        self::$add_body_classes = apply_filters( 'exopite-responsive-displayer-add-body-classes' , self::$add_body_classes );
        self::$devices = apply_filters( 'exopite-responsive-displayer-devices' , self::$devices );
        self::$add_shortcodes = apply_filters( 'exopite-responsive-displayer-add-shortcodes' , self::$add_shortcodes );
        self::$remove_classes = apply_filters( 'exopite-responsive-displayer-remove-classes' , self::$remove_classes );

        if ( self::$remove_classes ) {

            /*
             * Start buffering when wp_loaded hook called
             * end buffering when showdown hook called
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

        if ( ! is_admin() ) {

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

            // Dumps the internal DOM tree back into string
            $buffer = $html->save();
            $html->clear();
            unset($html);

            if ( self::$debug ) $after = microtime(true);
            if ( self::$debug ) $buffer .= '<!-- Exopite Responsive Remover plugin HTML parsing last for ' . ( $after - $before ) . " s. -->\n";

        }

        return $buffer . $log;
    }

}

Exopite_Responsive_Displayer::init();

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
