<?php
/**
 * PHP Runtime Lifecycle and Hosting Provider Guidance.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Php_Advisor {

	/**
	 * Get PHP lifecycle data based on official php.net releases.
	 *
	 * @return array
	 */
	public static function get_php_lifecycle_matrix() {
		return array(
			'7.4' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life (Critical Risk)', 'wpsecureos' ),
				'severity'    => 'critical',
				'description' => __( 'PHP 7.4 reached End of Life in November 2022. It receives no security patches and has known exploitable vulnerabilities.', 'wpsecureos' ),
			),
			'8.0' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life', 'wpsecureos' ),
				'severity'    => 'high',
				'description' => __( 'PHP 8.0 reached End of Life in November 2023. Upgrade to PHP 8.2 or 8.3 is strongly advised.', 'wpsecureos' ),
			),
			'8.1' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life / Security EOL', 'wpsecureos' ),
				'severity'    => 'medium',
				'description' => __( 'PHP 8.1 security support ended in late 2024. Upgrade recommended.', 'wpsecureos' ),
			),
			'8.2' => array(
				'status'      => 'security_only',
				'label'       => __( 'Security Fixes Only', 'wpsecureos' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.2 receives active security patches. Safe for production.', 'wpsecureos' ),
			),
			'8.3' => array(
				'status'      => 'active',
				'label'       => __( 'Active Support (Recommended)', 'wpsecureos' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.3 is currently actively maintained with optimal performance and security.', 'wpsecureos' ),
			),
			'8.4' => array(
				'status'      => 'active',
				'label'       => __( 'Active Support (Latest)', 'wpsecureos' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.4 is the latest stable release.', 'wpsecureos' ),
			),
		);
	}

	/**
	 * Analyze current server PHP runtime version.
	 *
	 * @return array
	 */
	public static function analyze_current_runtime() {
		$version_major_minor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$lifecycle = self::get_php_lifecycle_matrix();

		$current_status = isset( $lifecycle[ $version_major_minor ] ) ? $lifecycle[ $version_major_minor ] : array(
			'status'      => 'unknown',
			'label'       => __( 'Unknown Lifecycle Status', 'wpsecureos' ),
			'severity'    => 'medium',
			/* translators: %s: PHP version */
			'description' => sprintf( __( 'PHP version %s is not recognized in official lifecycle table.', 'wpsecureos' ), PHP_VERSION ),
		);

		return array(
			'current_version' => PHP_VERSION,
			'branch'          => $version_major_minor,
			'status'          => $current_status['status'],
			'label'           => $current_status['label'],
			'severity'        => $current_status['severity'],
			'description'     => $current_status['description'],
			'hosting_guides'  => self::get_hosting_runbooks(),
		);
	}

	/**
	 * Hosting provider specific runbooks for safe PHP runtime upgrades.
	 */
	public static function get_hosting_runbooks() {
		return array(
			'cpanel' => array(
				'name'  => 'cPanel / WHM',
				'steps' => array(
					__( 'Log in to your hosting cPanel account.', 'wpsecureos' ),
					__( 'Navigate to Software > MultiPHP Manager.', 'wpsecureos' ),
					__( 'Select your domain checkbox, choose PHP 8.2 or 8.3, and click Apply.', 'wpsecureos' ),
					__( 'Test staging clone first before switching the live production domain.', 'wpsecureos' ),
				),
			),
			'cloudways' => array(
				'name'  => 'Cloudways',
				'steps' => array(
					__( 'Log into Cloudways Console > Servers > Server Management.', 'wpsecureos' ),
					__( 'Click Settings & Packages > Packages.', 'wpsecureos' ),
					__( 'Under PHP, select PHP 8.2 or 8.3 from the dropdown and click Save.', 'wpsecureos' ),
				),
			),
			'kinsta' => array(
				'name'  => 'Kinsta',
				'steps' => array(
					__( 'Log into MyKinsta > WordPress Sites > Select your site.', 'wpsecureos' ),
					__( 'Go to Tools > PHP Engine and click Modify.', 'wpsecureos' ),
					__( 'Select PHP 8.2 or 8.3 and confirm change.', 'wpsecureos' ),
				),
			),
			'wpengine' => array(
				'name'  => 'WP Engine',
				'steps' => array(
					__( 'Log into WP Engine User Portal > Sites > Environment.', 'wpsecureos' ),
					__( 'Under PHP Version, click Upgrade to PHP 8.2/8.3.', 'wpsecureos' ),
					__( 'Run pre-upgrade staging compatibility check provided in WP Engine dashboard.', 'wpsecureos' ),
				),
			),
		);
	}
}
