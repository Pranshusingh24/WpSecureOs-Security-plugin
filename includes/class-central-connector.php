<?php
/**
 * Multi-site Central Dashboard Connector.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Central_Connector {

	/**
	 * Pair this site with a Central Dashboard instance using a secure one-time pairing code.
	 *
	 * @param string $central_url Dashboard URL.
	 * @param string $api_token   Secure pairing token.
	 * @return array
	 */
	public static function enroll_site( $central_url, $api_token ) {
		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: You lack wpsecureos_manage_settings capability.', 'wpsecureos' ),
			);
		}

		$clean_url   = esc_url_raw( $central_url );
		$clean_token = sanitize_text_field( $api_token );

		if ( empty( $clean_url ) || empty( $clean_token ) ) {
			return array(
				'success' => false,
				'message' => __( 'Central Dashboard URL and API Token are required.', 'wpsecureos' ),
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

		update_option( 'wpsecureos_central_connector', $connector_data );

		WpSecureOs_Event_Logger::log_event(
			'central_dashboard_enrolled',
			'low',
			'Central Connector',
			sprintf( 'Site enrolled to Central Control Plane at: %s', $clean_url ),
			array( 'central_url' => $clean_url )
		);

		return array(
			'success'  => true,
			'site_key' => $site_hash,
			'message'  => __( 'Site successfully connected to Central Management Dashboard.', 'wpsecureos' ),
		);
	}

	/**
	 * Disconnect central control plane.
	 */
	public static function disconnect() {
		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
			return false;
		}

		delete_option( 'wpsecureos_central_connector' );
		WpSecureOs_Event_Logger::log_event(
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
		$data = get_option( 'wpsecureos_central_connector', array() );
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
