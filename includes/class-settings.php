<?php
/**
 * Settings and options manager.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Settings {

	/**
	 * Register settings using WordPress Settings API.
	 */
	public static function register_settings() {
		register_setting(
			'wpsecureos_settings_group',
			'wpsecureos_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize and validate all incoming settings.
	 *
	 * @param array $input Raw input array.
	 * @return array Clean array.
	 */
	public static function sanitize_settings( $input ) {
		$clean = array();

		// Vulnerability Feed Provider
		$clean['vulnerability_feed_provider'] = isset( $input['vulnerability_feed_provider'] ) ? sanitize_text_field( $input['vulnerability_feed_provider'] ) : 'wpscan_compatible';

		// API Key
		$clean['vulnerability_feed_api_key'] = isset( $input['vulnerability_feed_api_key'] ) ? sanitize_text_field( $input['vulnerability_feed_api_key'] ) : '';

		// Backup Retention
		$retention = isset( $input['backup_retention_count'] ) ? (int) $input['backup_retention_count'] : 5;
		$clean['backup_retention_count'] = max( 1, min( 50, $retention ) );

		// Backup Storage Path
		$clean['backup_storage_path'] = isset( $input['backup_storage_path'] ) ? sanitize_text_field( $input['backup_storage_path'] ) : 'wp-content/uploads/wpsecureos-backups';

		// Staging Mode
		$clean['staging_mode'] = isset( $input['staging_mode'] ) ? sanitize_text_field( $input['staging_mode'] ) : 'simulated_local';

		// Visual Diff Threshold
		$threshold = isset( $input['visual_diff_threshold'] ) ? (float) $input['visual_diff_threshold'] : 0.05;
		$clean['visual_diff_threshold'] = max( 0.001, min( 0.50, $threshold ) );

		// Critical URLs for Visual Tests
		if ( ! empty( $input['critical_urls'] ) ) {
			if ( is_array( $input['critical_urls'] ) ) {
				$clean['critical_urls'] = array_filter( array_map( 'esc_url_raw', $input['critical_urls'] ) );
			} else {
				$urls = explode( "\n", str_replace( "\r", '', $input['critical_urls'] ) );
				$clean['critical_urls'] = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', $urls ) ) ) );
			}
		} else {
			$clean['critical_urls'] = array( home_url( '/' ) );
		}

		// Rate Limiting
		$clean['rate_limit_login_attempts']  = isset( $input['rate_limit_login_attempts'] ) ? max( 3, min( 50, (int) $input['rate_limit_login_attempts'] ) ) : 5;
		$clean['rate_limit_lockout_minutes'] = isset( $input['rate_limit_lockout_minutes'] ) ? max( 1, min( 1440, (int) $input['rate_limit_lockout_minutes'] ) ) : 15;

		// Alert Email
		$clean['alert_email'] = isset( $input['alert_email'] ) ? sanitize_email( $input['alert_email'] ) : get_option( 'admin_email' );

		// Checkboxes
		$clean['enable_login_audit']     = ! empty( $input['enable_login_audit'] ) ? 1 : 0;
		$clean['enable_uploads_scanner'] = ! empty( $input['enable_uploads_scanner'] ) ? 1 : 0;

		// Audit Log
		WpSecureOs_Event_Logger::log_event(
			'settings_updated',
			'low',
			'Settings',
			'Plugin settings successfully updated by administrator.',
			array( 'keys_updated' => array_keys( $clean ) )
		);

		return $clean;
	}
}
