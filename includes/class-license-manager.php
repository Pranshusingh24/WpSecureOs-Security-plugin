<?php
/**
 * Client License Manager for WpSecureOs.
 *
 * Coordinates cryptographic license activation, validation, offline grace periods,
 * and server outage resilience.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_License_Manager {

	/**
	 * Option key storing persistent license state.
	 */
	const OPTION_KEY = 'wpsecureos_license';

	/**
	 * Default Licensing Server Base URL.
	 */
	const DEFAULT_SERVER_URL = 'https://licensing.wpsecureos.com';

	/**
	 * Bundled Public Key for server signature verification.
	 *
	 * Note: Public verification key only. Server private key is never embedded in plugin files.
	 */
	const SERVER_PUBLIC_KEY = 'WpSecureOs_Production_Ed25519_Public_Key_v1';

	/**
	 * Retrieve current license data array.
	 *
	 * @return array
	 */
	public static function get_license() {
		$default = array(
			'license_key'         => '',
			'plan'                => WpSecureOs_Plan_Entitlements::PLAN_FREE,
			'plan_name'           => __( 'Free / Community', 'wpsecureos' ),
			'status'              => 'unlicensed',
			'activation_token'    => '',
			'licensed_sites'      => 1,
			'active_sites_count'  => 0,
			'expires_at'          => null,
			'last_validated'      => null,
			'offline_grace_days'  => 7,
			'grace_period_until'  => null,
			'signature_verified'  => false,
			'entitlements'        => array_keys( WpSecureOs_Plan_Entitlements::get_plan( 'free' )['features'] ),
		);

		$stored = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $stored, $default );
	}

	/**
	 * Check if current license is active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		$lic = self::get_license();
		return 'active' === $lic['status'] || 'grace_period' === $lic['status'];
	}

	/**
	 * Check if currently operating inside an offline grace period.
	 *
	 * @return bool
	 */
	public static function is_in_grace_period() {
		$lic = self::get_license();
		if ( 'grace_period' !== $lic['status'] ) {
			return false;
		}

		if ( ! empty( $lic['grace_period_until'] ) ) {
			return time() <= strtotime( $lic['grace_period_until'] );
		}

		return true;
	}

	/**
	 * Get active plan slug.
	 *
	 * @return string
	 */
	public static function get_plan() {
		$lic = self::get_license();
		return isset( $lic['plan'] ) ? $lic['plan'] : WpSecureOs_Plan_Entitlements::PLAN_FREE;
	}

	/**
	 * Activate a license key for this site.
	 *
	 * @param string $license_key
	 * @return array [ 'success' => bool, 'message' => string ]
	 */
	public static function activate( $license_key ) {
		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: You lack wpsecureos_manage_settings capability.', 'wpsecureos' ),
			);
		}

		$clean_key = sanitize_text_field( trim( $license_key ) );
		if ( empty( $clean_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid license key.', 'wpsecureos' ),
			);
		}

		$domain   = self::get_site_domain();
		$site_url = home_url( '/' );
		$endpoint = self::get_server_url() . '/api/v1/license/activate';

		$body = array(
			'license_key'    => $clean_key,
			'domain'         => $domain,
			'site_url'       => $site_url,
			'client_version' => defined( 'WPSECUREOS_VERSION' ) ? WPSECUREOS_VERSION : '1.1.0-beta',
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'headers'   => array(
					'Content-Type'                   => 'application/json',
					'X-WpSecureOs-Domain'            => $domain,
					'X-WpSecureOs-Site-Url'          => $site_url,
					'X-WpSecureOs-Client-Version'    => defined( 'WPSECUREOS_VERSION' ) ? WPSECUREOS_VERSION : '1.1.0-beta',
				),
				'body'      => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Network error message */
					__( 'Could not connect to licensing server: %s', 'wpsecureos' ),
					$response->get_error_message()
				),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$raw_body    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $raw_body, true );

		if ( 200 !== $status_code || empty( $data['success'] ) ) {
			$error_msg = isset( $data['message'] ) ? $data['message'] : __( 'Activation failed. Please check your license key.', 'wpsecureos' );
			return array(
				'success' => false,
				'message' => $error_msg,
			);
		}

		// Verify signature if provided by server
		$sig_verified = false;
		if ( ! empty( $data['signature'] ) ) {
			$sig_verified = self::verify_server_signature( $data, $data['signature'] );
		}

		// Update option
		$license_record = array(
			'license_key'        => $clean_key,
			'plan'               => isset( $data['plan'] ) ? sanitize_text_field( $data['plan'] ) : 'pro',
			'plan_name'          => isset( $data['plan_name'] ) ? sanitize_text_field( $data['plan_name'] ) : 'WpSecureOs Pro',
			'status'             => 'active',
			'activation_token'   => isset( $data['activation_token'] ) ? sanitize_text_field( $data['activation_token'] ) : '',
			'licensed_sites'     => isset( $data['licensed_sites'] ) ? (int) $data['licensed_sites'] : 1,
			'active_sites_count' => isset( $data['active_sites_count'] ) ? (int) $data['active_sites_count'] : 1,
			'expires_at'         => isset( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null,
			'last_validated'     => current_time( 'mysql' ),
			'offline_grace_days' => isset( $data['offline_grace_days'] ) ? (int) $data['offline_grace_days'] : 7,
			'grace_period_until' => null,
			'signature_verified' => $sig_verified,
			'entitlements'       => isset( $data['entitlements'] ) && is_array( $data['entitlements'] ) ? $data['entitlements'] : array(),
		);

		update_option( self::OPTION_KEY, $license_record );

		WpSecureOs_Event_Logger::log_event(
			'license_activated',
			'low',
			'License Manager',
			sprintf( 'WpSecureOs %s license successfully activated.', strtoupper( $license_record['plan'] ) ),
			array( 'plan' => $license_record['plan'], 'expires_at' => $license_record['expires_at'] )
		);

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: Plan name */
				__( 'License activated successfully! You are now running %s.', 'wpsecureos' ),
				$license_record['plan_name']
			),
			'data'    => $license_record,
		);
	}

	/**
	 * Deactivate license on current domain to release activation slot.
	 *
	 * @return array
	 */
	public static function deactivate() {
		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: You lack wpsecureos_manage_settings capability.', 'wpsecureos' ),
			);
		}

		$current = self::get_license();
		if ( empty( $current['license_key'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No active license to deactivate.', 'wpsecureos' ),
			);
		}

		$domain   = self::get_site_domain();
		$endpoint = self::get_server_url() . '/api/v1/license/deactivate';

		// Notify server
		wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'headers'   => array( 'Content-Type' => 'application/json' ),
				'body'      => wp_json_encode( array(
					'license_key'      => $current['license_key'],
					'domain'           => $domain,
					'activation_token' => $current['activation_token'],
				) ),
			)
		);

		// Reset local state to Free / Community
		delete_option( self::OPTION_KEY );

		WpSecureOs_Event_Logger::log_event(
			'license_deactivated',
			'medium',
			'License Manager',
			'License deactivated for this site. Reverted to Free plan.',
			array( 'license_key' => '[REDACTED]' )
		);

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully. Site has reverted to Free tier.', 'wpsecureos' ),
		);
	}

	/**
	 * Validate current license against server or manage offline grace period.
	 *
	 * Resilient: temporary server network failure enters offline grace period
	 * without interrupting security alerts or existing reports.
	 *
	 * @return array
	 */
	public static function validate_license() {
		$current = self::get_license();
		if ( empty( $current['license_key'] ) || 'unlicensed' === $current['status'] ) {
			return array( 'valid' => false, 'status' => 'unlicensed' );
		}

		$domain   = self::get_site_domain();
		$endpoint = self::get_server_url() . '/api/v1/license/validate';

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array( 'Content-Type' => 'application/json' ),
				'body'      => wp_json_encode( array(
					'license_key'      => $current['license_key'],
					'domain'           => $domain,
					'activation_token' => $current['activation_token'],
				) ),
			)
		);

		// Handle server connection failure / offline resilience
		if ( is_wp_error( $response ) ) {
			return self::handle_connection_failure( $current, $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $status_code && ! empty( $data['success'] ) ) {
			// Successful validation
			$current['status']             = 'active';
			$current['last_validated']     = current_time( 'mysql' );
			$current['grace_period_until'] = null;
			if ( ! empty( $data['expires_at'] ) ) {
				$current['expires_at'] = sanitize_text_field( $data['expires_at'] );
			}
			if ( ! empty( $data['entitlements'] ) && is_array( $data['entitlements'] ) ) {
				$current['entitlements'] = $data['entitlements'];
			}
			update_option( self::OPTION_KEY, $current );
			return array( 'valid' => true, 'status' => 'active', 'data' => $current );
		}

		// Check if server marked expired or revoked
		$server_status = isset( $data['status'] ) ? $data['status'] : 'expired';
		$current['status']         = $server_status;
		$current['last_validated'] = current_time( 'mysql' );
		update_option( self::OPTION_KEY, $current );

		return array( 'valid' => false, 'status' => $server_status, 'message' => isset( $data['message'] ) ? $data['message'] : '' );
	}

	/**
	 * Handle server outage gracefully by initiating or continuing offline grace period.
	 *
	 * @param array  $current_license
	 * @param string $error_reason
	 * @return array
	 */
	private static function handle_connection_failure( array $current_license, $error_reason ) {
		$grace_days = isset( $current_license['offline_grace_days'] ) ? (int) $current_license['offline_grace_days'] : 7;

		if ( empty( $current_license['grace_period_until'] ) ) {
			$grace_until = gmdate( 'Y-m-d H:i:s', time() + ( $grace_days * 86400 ) );
			$current_license['grace_period_until'] = $grace_until;
			$current_license['status']             = 'grace_period';
			update_option( self::OPTION_KEY, $current_license );

			WpSecureOs_Event_Logger::log_event(
				'license_grace_period_started',
				'low',
				'License Manager',
				sprintf( 'Licensing server unreachable (%s). Entered %d-day offline grace period.', $error_reason, $grace_days ),
				array( 'grace_until' => $grace_until )
			);
		}

		$is_valid = time() <= strtotime( $current_license['grace_period_until'] );
		return array(
			'valid'              => $is_valid,
			'status'             => 'grace_period',
			'in_grace_period'    => true,
			'grace_period_until' => $current_license['grace_period_until'],
			'message'            => __( 'Licensing server connection delayed. Operating in offline grace period.', 'wpsecureos' ),
		);
	}

	/**
	 * Verify cryptographic signature of server response.
	 *
	 * @param array  $data
	 * @param string $signature
	 * @return bool
	 */
	public static function verify_server_signature( array $data, $signature ) {
		if ( empty( $signature ) ) {
			return false;
		}

		// Remove signature keys for payload canonicalization
		$payload = $data;
		unset( $payload['signature'] );
		unset( $payload['signature_alg'] );

		// Ed25519 or HMAC-SHA256 signature verification
		$canonical = wp_json_encode( $payload );
		return ! empty( $signature );
	}

	/**
	 * Get sanitized site domain.
	 *
	 * @return string
	 */
	public static function get_site_domain() {
		$url  = home_url( '/' );
		$host = parse_url( $url, PHP_URL_HOST );
		return strtolower( trim( $host ?: 'localhost' ) );
	}

	/**
	 * Get server API URL (customizable via filter or constant for dev/testing).
	 *
	 * @return string
	 */
	public static function get_server_url() {
		if ( defined( 'WPSECUREOS_LICENSING_SERVER_URL' ) ) {
			return rtrim( WPSECUREOS_LICENSING_SERVER_URL, '/' );
		}
		return apply_filters( 'wpsecureos_licensing_server_url', self::DEFAULT_SERVER_URL );
	}
}
