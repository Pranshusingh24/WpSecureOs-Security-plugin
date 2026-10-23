<?php
/**
 * Plugin Name:       WpSecureOs
 * Plugin URI:        https://wpsecureos.com/
 * Description:       Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Pranshu Singh
 * Author URI:        https://wpsecureos.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpsecureos
 * Domain Path:       /languages
 *
 * @package WpSecureOs
 */

// Block direct file execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPSECUREOS_VERSION', '1.0.0' );
define( 'WPSECUREOS_PLUGIN_FILE', __FILE__ );
define( 'WPSECUREOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSECUREOS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSECUREOS_MIN_PHP', '7.4' );
define( 'WPSECUREOS_MIN_WP', '6.0' );

/**
 * Autoloader for WpSecureOs classes.
 *
 * @param string $class_name Class name being loaded.
 */
spl_autoload_register( function ( $class_name ) {
	if ( 0 !== strpos( $class_name, 'WpSecureOs_' ) ) {
		return;
	}

	// Format class-name to filename: WpSecureOs_Risk_Assessor -> class-risk-assessor.php
	$relative_class = substr( $class_name, 11 );
	$file_name      = 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
	$file_path      = WPSECUREOS_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * Check PHP and WordPress version compatibility before activating.
 */
function wpsecureos_check_requirements() {
	if ( version_compare( PHP_VERSION, WPSECUREOS_MIN_PHP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'WpSecureOs requires PHP %1$s or higher. Your server is running PHP %2$s.', 'wpsecureos' ),
				esc_html( WPSECUREOS_MIN_PHP ),
				esc_html( PHP_VERSION )
			),
			esc_html__( 'Plugin Activation Error', 'wpsecureos' ),
			array( 'back_link' => true )
		);
	}

	global $wp_version;
	if ( version_compare( $wp_version, WPSECUREOS_MIN_WP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: 1: Required WP version, 2: Current WP version */
				esc_html__( 'WpSecureOs requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'wpsecureos' ),
				esc_html( WPSECUREOS_MIN_WP ),
				esc_html( $wp_version )
			),
			esc_html__( 'Plugin Activation Error', 'wpsecureos' ),
			array( 'back_link' => true )
		);
	}
}

/**
 * Activation hook callback.
 */
function wpsecureos_activate() {
	wpsecureos_check_requirements();
	WpSecureOs_Activator::activate();
}
register_activation_hook( __FILE__, 'wpsecureos_activate' );

/**
 * Deactivation hook callback.
 */
function wpsecureos_deactivate() {
	WpSecureOs_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'wpsecureos_deactivate' );

/**
 * Bootstrap the core plugin.
 */
function wpsecureos_init() {
	return WpSecureOs_Plugin::instance();
}

// Launch plugin after plugins loaded.
add_action( 'plugins_loaded', 'wpsecureos_init' );
