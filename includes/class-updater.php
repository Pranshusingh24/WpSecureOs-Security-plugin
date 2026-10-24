<?php
/**
 * Authenticated Update Delivery Client for WpSecureOs.
 *
 * Integrates with standard WordPress core update transients (update_plugins)
 * and plugin_information modal while enforcing subscription entitlements.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Updater {

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private static $plugin_basename = 'wpsecureos/wpsecureos.php';

	/**
	 * Initialize update hooks.
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 20, 3 );
	}

	/**
	 * Check licensing server for new releases during WordPress update check.
	 *
	 * @param object $transient
	 * @return object
	 */
	public static function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$license = WpSecureOs_License_Manager::get_license();
		$installed_ver = defined( 'WPSECUREOS_VERSION' ) ? WPSECUREOS_VERSION : '1.1.0-beta';
		$domain        = WpSecureOs_License_Manager::get_site_domain();
		$server_url    = WpSecureOs_License_Manager::get_server_url();

		// Make request to server
		$response = wp_remote_post(
			$server_url . '/api/v1/updates/check',
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array( 'Content-Type' => 'application/json' ),
				'body'      => wp_json_encode( array(
					'license_key'       => $license['license_key'],
					'domain'            => $domain,
					'installed_version' => $installed_ver,
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $transient;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['update_available'] ) || empty( $data['version'] ) ) {
			return $transient;
		}

		// Prepare standard WordPress plugin update object
		$obj              = new stdClass();
		$obj->slug        = 'wpsecureos';
		$obj->plugin      = self::$plugin_basename;
		$obj->new_version = $data['version'];
		$obj->url         = 'https://wpsecureos-plugin.netlify.app/';
		$obj->package     = ! empty( $data['package'] ) ? $data['package'] : '';
		$obj->requires    = ! empty( $data['requires_wp'] ) ? $data['requires_wp'] : '6.2';
		$obj->requires_php= ! empty( $data['requires_php'] ) ? $data['requires_php'] : '7.4';
		$obj->tested      = ! empty( $data['tested_wp'] ) ? $data['tested_wp'] : '6.5';

		if ( ! empty( $data['checksum_sha256'] ) ) {
			$obj->icons = array(
				'default' => WPSECUREOS_PLUGIN_URL . 'assets/images/logo.svg',
			);
		}

		$transient->response[ self::$plugin_basename ] = $obj;
		return $transient;
	}

	/**
	 * Provide modal details when administrator clicks "View version details".
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public static function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'wpsecureos' !== $args->slug ) {
			return $result;
		}

		$license       = WpSecureOs_License_Manager::get_license();
		$installed_ver = defined( 'WPSECUREOS_VERSION' ) ? WPSECUREOS_VERSION : '1.1.0-beta';
		$server_url    = WpSecureOs_License_Manager::get_server_url();

		$response = wp_remote_post(
			$server_url . '/api/v1/updates/check',
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array( 'Content-Type' => 'application/json' ),
				'body'      => wp_json_encode( array(
					'license_key'       => $license['license_key'],
					'domain'            => WpSecureOs_License_Manager::get_site_domain(),
					'installed_version' => $installed_ver,
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $result;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data ) ) {
			return $result;
		}

		$res               = new stdClass();
		$res->name         = 'WpSecureOs Enterprise';
		$res->slug         = 'wpsecureos';
		$res->version      = isset( $data['version'] ) ? $data['version'] : $installed_ver;
		$res->author       = '<a href="https://wpsecureos-plugin.netlify.app/">Pranshu Singh</a>';
		$res->homepage     = 'https://wpsecureos-plugin.netlify.app/';
		$res->requires     = isset( $data['requires_wp'] ) ? $data['requires_wp'] : '6.2';
		$res->requires_php = isset( $data['requires_php'] ) ? $data['requires_php'] : '7.4';
		$res->download_link= isset( $data['package'] ) ? $data['package'] : '';
		$res->sections     = array(
			'description' => 'Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform.',
			'changelog'   => isset( $data['changelog'] ) ? nl2br( esc_html( is_string( $data['changelog'] ) ? $data['changelog'] : wp_json_encode( $data['changelog'] ) ) ) : 'Maintenance updates.',
		);

		return $res;
	}
}
