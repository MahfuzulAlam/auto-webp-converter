<?php
/**
 * Plugin Name: Auto WebP Converter
 * Plugin URI: https://wpxplore.org/plugins/auto-webp-converter/
 * Description: Automatically compresses images and converts them to WebP format during upload. Uses Imagick with GD fallback.
 * Version: 1.0.0
 * Author: wpXplore
 * Author URI: https://wpxplore.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpxplore-webp-converter
 * Requires at least: 5.0
 * Requires PHP: 7.0
 *
 * @package Auto_WebP_Converter
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
if ( ! defined( 'AWC_VERSION' ) ) {
	define( 'AWC_VERSION', '1.0.0' );
}

if ( ! defined( 'AWC_PLUGIN_DIR' ) ) {
	define( 'AWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'AWC_PLUGIN_URL' ) ) {
	define( 'AWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'AWC_PLUGIN_BASENAME' ) ) {
	define( 'AWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Load plugin files
 */
require_once AWC_PLUGIN_DIR . 'includes/class-auto-webp-converter.php';
require_once AWC_PLUGIN_DIR . 'includes/functions.php';

/**
 * Initialize plugin
 *
 * @return void
 */
function wpxplore_awc_init() {
	$plugin = WpXplore_Auto_WebP_Converter::get_instance();
}
add_action( 'plugins_loaded', 'wpxplore_awc_init' );