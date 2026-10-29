<?php
/**
 * Plugin Name:       MaxSafe
 * Plugin URI:        https://maxsafe-plugin.netlify.app/
 * Description:       Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform.
 * Version:           1.2.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Pranshu Singh
 * Author URI:        https://pranshusingh19.github.io/pranshu_portfolio/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maxsafe
 * Domain Path:       /languages
 *
 * @package MaxSafe
 */

// Block direct file execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MAXSAFE_VERSION', '1.2.0' );
define( 'MAXSAFE_PLUGIN_FILE', __FILE__ );
define( 'MAXSAFE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAXSAFE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAXSAFE_MIN_PHP', '7.4' );
define( 'MAXSAFE_MIN_WP', '6.2' );

// Backwards-compatibility constants.
if ( ! defined( 'WPSECUREOS_VERSION' ) ) {
	define( 'WPSECUREOS_VERSION', MAXSAFE_VERSION );
}
if ( ! defined( 'WPSECUREOS_PLUGIN_FILE' ) ) {
	define( 'WPSECUREOS_PLUGIN_FILE', MAXSAFE_PLUGIN_FILE );
}
if ( ! defined( 'WPSECUREOS_PLUGIN_DIR' ) ) {
	define( 'WPSECUREOS_PLUGIN_DIR', MAXSAFE_PLUGIN_DIR );
}
if ( ! defined( 'WPSECUREOS_PLUGIN_URL' ) ) {
	define( 'WPSECUREOS_PLUGIN_URL', MAXSAFE_PLUGIN_URL );
}

/**
 * Autoloader for MaxSafe and backwards-compatible WpSecureOs classes.
 *
 * @param string $class_name Class name being loaded.
 */
spl_autoload_register( function ( $class_name ) {
	if ( 0 === strpos( $class_name, 'MaxSafe_' ) ) {
		$relative_class = substr( $class_name, 8 );
	} elseif ( 0 === strpos( $class_name, 'WpSecureOs_' ) ) {
		$relative_class = substr( $class_name, 11 );
	} else {
		return;
	}

	// Format class-name to filename: MaxSafe_Risk_Assessor -> class-risk-assessor.php
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
	$file_path = MAXSAFE_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;

		$maxsafe_class = 'MaxSafe_' . $relative_class;
		$legacy_class  = 'WpSecureOs_' . $relative_class;
		if ( class_exists( $maxsafe_class ) && ! class_exists( $legacy_class, false ) ) {
			class_alias( $maxsafe_class, $legacy_class );
		}
	}
} );

/**
 * Check PHP and WordPress version compatibility before activating.
 */
function maxsafe_check_requirements() {
	if ( version_compare( PHP_VERSION, MAXSAFE_MIN_PHP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'MaxSafe requires PHP %1$s or higher. Your server is running PHP %2$s.', 'maxsafe' ),
				esc_html( MAXSAFE_MIN_PHP ),
				esc_html( PHP_VERSION )
			),
			esc_html__( 'Plugin Activation Error', 'maxsafe' ),
			array( 'back_link' => true )
		);
	}

	global $wp_version;
	if ( version_compare( $wp_version, MAXSAFE_MIN_WP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: 1: Required WP version, 2: Current WP version */
				esc_html__( 'MaxSafe requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'maxsafe' ),
				esc_html( MAXSAFE_MIN_WP ),
				esc_html( $wp_version )
			),
			esc_html__( 'Plugin Activation Error', 'maxsafe' ),
			array( 'back_link' => true )
		);
	}
}

/**
 * Activation hook callback.
 */
function maxsafe_activate() {
	maxsafe_check_requirements();
	MaxSafe_Activator::activate();
}
register_activation_hook( __FILE__, 'maxsafe_activate' );

/**
 * Deactivation hook callback.
 */
function maxsafe_deactivate() {
	MaxSafe_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'maxsafe_deactivate' );

/**
 * Bootstrap the core plugin.
 */
function maxsafe_init() {
	return MaxSafe_Plugin::instance();
}

// Backwards-compatible bootstrap helper.
if ( ! function_exists( 'wpsecureos_init' ) ) {
	function wpsecureos_init() {
		return maxsafe_init();
	}
}

// Launch plugin after plugins loaded.
add_action( 'plugins_loaded', 'maxsafe_init' );

