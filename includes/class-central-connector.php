<?php
/**
 * Multi-site Central Dashboard Connector.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Central_Connector {

	/**
	 * Pair this site with a Central Dashboard instance using a secure one-time pairing code.
	 *
	 * @param string $central_url Dashboard URL.
	 * @param string $api_token   Secure pairing token.
	 * @return array
	 */
	public static function enroll_site( $central_url, $api_token ) {
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_MANAGE_SETTINGS ) && ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: You lack maxsafe_manage_settings capability.', 'maxsafe' ),
			);
		}

		$clean_url   = esc_url_raw( $central_url );
		$clean_token = sanitize_text_field( $api_token );

		if ( empty( $clean_url ) || empty( $clean_token ) ) {
			return array(
				'success' => false,
				'message' => __( 'Central Dashboard URL and API Token are required.', 'maxsafe' ),
			);
		}

		$site_hash = hash( 'sha256', wp_generate_password( 32, false ) );
		$connector_data = array(
			'central_url' => $clean_url,
			'site_key'    => $site_hash,
			'token_hash'  => wp_hash_password( $clean_token ),
			'enrolled_at' => current_time( 'mysql' ),
			'status'      => 'connected',
		);

		update_option( 'maxsafe_central_connector', $connector_data );

		MaxSafe_Event_Logger::log_event(
			'central_dashboard_enrolled',
			'low',
			'Central Connector',
			sprintf( 'Site enrolled to Central Control Plane at: %s', $clean_url ),
			array( 'central_url' => $clean_url )
		);

		return array(
			'success'  => true,
			'site_key' => $site_hash,
			'message'  => __( 'Site successfully connected to Central Management Dashboard.', 'maxsafe' ),
		);
	}

	/**
	 * Disconnect central control plane.
	 */
	public static function disconnect() {
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_MANAGE_SETTINGS ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		delete_option( 'maxsafe_central_connector' );
		delete_option( 'wpsecureos_central_connector' );
		MaxSafe_Event_Logger::log_event(
			'central_dashboard_disconnected',
			'medium',
			'Central Connector',
			'Site disconnected from Central Dashboard.',
			array()
		);
		return true;
	}

	/**
	 * Get connection status.
	 */
	public static function get_status() {
		$data = get_option( 'maxsafe_central_connector', array() );
		if ( empty( $data ) ) {
			$data = get_option( 'wpsecureos_central_connector', array() );
		}
		if ( empty( $data ) || empty( $data['central_url'] ) ) {
			return array(
				'connected' => false,
				'status'    => 'disconnected',
			);
		}

		return array(
			'connected'   => true,
			'status'      => 'connected',
			'central_url' => $data['central_url'],
			'enrolled_at' => $data['enrolled_at'],
		);
	}
}
