<?php
/**
 * Fired during plugin deactivation.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Deactivator {

	/**
	 * Run deactivation routines.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'maxsafe_daily_security_scan' );
		wp_clear_scheduled_hook( 'wpsecureos_daily_security_scan' );
		delete_transient( 'maxsafe_system_health' );
		delete_transient( 'maxsafe_inventory_cache' );
		delete_transient( 'wpsecureos_system_health' );
		delete_transient( 'wpsecureos_inventory_cache' );
	}
}
