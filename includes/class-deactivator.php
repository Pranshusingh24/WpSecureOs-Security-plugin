<?php
/**
 * Fired during plugin deactivation.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Deactivator {

	/**
	 * Run deactivation routines.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wpsecureos_daily_security_scan' );
		delete_transient( 'wpsecureos_system_health' );
		delete_transient( 'wpsecureos_inventory_cache' );
	}
}
