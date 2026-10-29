<?php
/**
 * PHP Runtime Lifecycle and Hosting Provider Guidance.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Php_Advisor {

	/**
	 * Get PHP lifecycle data based on official php.net releases.
	 *
	 * @return array
	 */
	public static function get_php_lifecycle_matrix() {
		return array(
			'7.4' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life (Critical Risk)', 'maxsafe' ),
				'severity'    => 'critical',
				'description' => __( 'PHP 7.4 reached End of Life in November 2022. It receives no security patches and has known exploitable vulnerabilities.', 'maxsafe' ),
			),
			'8.0' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life', 'maxsafe' ),
				'severity'    => 'high',
				'description' => __( 'PHP 8.0 reached End of Life in November 2023. Upgrade to PHP 8.2 or 8.3 is strongly advised.', 'maxsafe' ),
			),
			'8.1' => array(
				'status'      => 'EOL',
				'label'       => __( 'End of Life / Security EOL', 'maxsafe' ),
				'severity'    => 'medium',
				'description' => __( 'PHP 8.1 security support ended in late 2024. Upgrade recommended.', 'maxsafe' ),
			),
			'8.2' => array(
				'status'      => 'security_only',
				'label'       => __( 'Security Fixes Only', 'maxsafe' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.2 receives active security patches. Safe for production.', 'maxsafe' ),
			),
			'8.3' => array(
				'status'      => 'active',
				'label'       => __( 'Active Support (Recommended)', 'maxsafe' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.3 is currently actively maintained with optimal performance and security.', 'maxsafe' ),
			),
			'8.4' => array(
				'status'      => 'active',
				'label'       => __( 'Active Support (Latest)', 'maxsafe' ),
				'severity'    => 'low',
				'description' => __( 'PHP 8.4 is the latest stable release.', 'maxsafe' ),
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
			'label'       => __( 'Unknown Lifecycle Status', 'maxsafe' ),
			'severity'    => 'medium',
			/* translators: %s: PHP version */
			'description' => sprintf( __( 'PHP version %s is not recognized in official lifecycle table.', 'maxsafe' ), PHP_VERSION ),
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
					__( 'Log in to your hosting cPanel account.', 'maxsafe' ),
					__( 'Navigate to Software > MultiPHP Manager.', 'maxsafe' ),
					__( 'Select your domain checkbox, choose PHP 8.2 or 8.3, and click Apply.', 'maxsafe' ),
					__( 'Test staging clone first before switching the live production domain.', 'maxsafe' ),
				),
			),
			'cloudways' => array(
				'name'  => 'Cloudways',
				'steps' => array(
					__( 'Log into Cloudways Console > Servers > Server Management.', 'maxsafe' ),
					__( 'Click Settings & Packages > Packages.', 'maxsafe' ),
					__( 'Under PHP, select PHP 8.2 or 8.3 from the dropdown and click Save.', 'maxsafe' ),
				),
			),
			'kinsta' => array(
				'name'  => 'Kinsta',
				'steps' => array(
					__( 'Log into MyKinsta > WordPress Sites > Select your site.', 'maxsafe' ),
					__( 'Go to Tools > PHP Engine and click Modify.', 'maxsafe' ),
					__( 'Select PHP 8.2 or 8.3 and confirm change.', 'maxsafe' ),
				),
			),
			'wpengine' => array(
				'name'  => 'WP Engine',
				'steps' => array(
					__( 'Log into WP Engine User Portal > Sites > Environment.', 'maxsafe' ),
					__( 'Under PHP Version, click Upgrade to PHP 8.2/8.3.', 'maxsafe' ),
					__( 'Run pre-upgrade staging compatibility check provided in WP Engine dashboard.', 'maxsafe' ),
				),
			),
		);
	}
}
