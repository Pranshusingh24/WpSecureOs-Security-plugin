<?php
/**
 * Plan Definitions & Entitlements Gatekeeper.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Plan_Entitlements {

	const PLAN_FREE     = 'free';
	const PLAN_PRO      = 'pro';
	const PLAN_AGENCY   = 'agency';
	const PLAN_BUSINESS = 'business';

	/**
	 * Get full plan comparison matrix.
	 *
	 * @return array
	 */
	public static function get_all_plans() {
		return array(
			self::PLAN_FREE     => array(
				'id'            => self::PLAN_FREE,
				'slug'          => 'free',
				'name'          => __( 'Free / Community', 'wpsecureos' ),
				'tagline'       => __( 'Essential security posture monitoring & manual safety baseline', 'wpsecureos' ),
				'price_monthly' => 0.00,
				'price_annual'  => 0.00,
				'site_limit'    => 1,
				'badge_class'   => 'badge-free',
				'features'      => array(
					'core_integrity'           => __( 'Official Core Checksum Integrity Monitoring', 'wpsecureos' ),
					'vulnerability_scan_basic' => __( 'Public Vulnerability Database Alerts', 'wpsecureos' ),
					'local_backup_manual'      => __( 'Manual Local Database + File Backup Creation', 'wpsecureos' ),
					'risk_urgency_scoring'     => __( 'Explainable Urgency & Confidence Badges', 'wpsecureos' ),
					'basic_hardening'          => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'wpsecureos' ),
					'event_logging'            => __( 'Authentication & Privilege Security Audit Log', 'wpsecureos' ),
				),
			),
			self::PLAN_PRO      => array(
				'id'            => self::PLAN_PRO,
				'slug'          => 'pro',
				'name'          => __( 'WpSecureOs Pro', 'wpsecureos' ),
				'tagline'       => __( 'Automated safe update pipeline & visual smoke diffs', 'wpsecureos' ),
				'price_monthly' => 9.00,
				'price_annual'  => 89.00,
				'site_limit'    => 1,
				'badge_class'   => 'badge-pro',
				'highlight'     => true,
				'features'      => array(
					'core_integrity'           => __( 'Official Core Checksum Integrity Monitoring', 'wpsecureos' ),
					'vulnerability_scan_basic' => __( 'Public Vulnerability Database Alerts', 'wpsecureos' ),
					'local_backup_manual'      => __( 'Manual Local Database + File Backup Creation', 'wpsecureos' ),
					'risk_urgency_scoring'     => __( 'Explainable Urgency & Confidence Badges', 'wpsecureos' ),
					'basic_hardening'          => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'wpsecureos' ),
					'event_logging'            => __( 'Authentication & Privilege Security Audit Log', 'wpsecureos' ),
					'staging_pipeline'         => __( 'Automated Staging Update & Sandbox Gate Machine', 'wpsecureos' ),
					'rate_limiting_login'      => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'wpsecureos' ),
					'scheduled_daily_scans'    => __( 'Automated Scheduled Daily Background Scans', 'wpsecureos' ),
					'visual_regression_single' => __( 'Visual Page Regression Testing (1 Critical URL)', 'wpsecureos' ),
					'cloud_backups'            => __( 'Encrypted Cloud Storage Backup Adapter', 'wpsecureos' ),
					'verified_rollback'        => __( 'One-Click Restorable Code & Database Rollback', 'wpsecureos' ),
					'auto_update_delivery'     => __( 'Authenticated Premium Update Delivery', 'wpsecureos' ),
				),
			),
			self::PLAN_AGENCY   => array(
				'id'            => self::PLAN_AGENCY,
				'slug'          => 'agency',
				'name'          => __( 'WpSecureOs Agency', 'wpsecureos' ),
				'tagline'       => __( 'Multi-site fleet defense & deep heuristic malware traps', 'wpsecureos' ),
				'price_monthly' => 29.00,
				'price_annual'  => 279.00,
				'site_limit'    => 5,
				'badge_class'   => 'badge-agency',
				'features'      => array(
					'core_integrity'              => __( 'Official Core Checksum Integrity Monitoring', 'wpsecureos' ),
					'vulnerability_scan_basic'    => __( 'Public Vulnerability Database Alerts', 'wpsecureos' ),
					'local_backup_manual'         => __( 'Manual Local Database + File Backup Creation', 'wpsecureos' ),
					'risk_urgency_scoring'        => __( 'Explainable Urgency & Confidence Badges', 'wpsecureos' ),
					'basic_hardening'             => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'wpsecureos' ),
					'event_logging'               => __( 'Authentication & Privilege Security Audit Log', 'wpsecureos' ),
					'staging_pipeline'            => __( 'Automated Staging Update & Sandbox Gate Machine', 'wpsecureos' ),
					'rate_limiting_login'         => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'wpsecureos' ),
					'scheduled_daily_scans'       => __( 'Automated Scheduled Daily Background Scans', 'wpsecureos' ),
					'visual_regression_single'    => __( 'Visual Page Regression Testing', 'wpsecureos' ),
					'cloud_backups'               => __( 'Encrypted Cloud Storage Backup Adapter', 'wpsecureos' ),
					'verified_rollback'           => __( 'One-Click Restorable Code & Database Rollback', 'wpsecureos' ),
					'auto_update_delivery'        => __( 'Authenticated Premium Update Delivery', 'wpsecureos' ),
					'visual_regression_unlimited' => __( 'Unlimited Visual Diff Testing on Multiple Viewports', 'wpsecureos' ),
					'deep_malware_heuristics'     => __( 'Deep Uploads Heuristic Malware Analysis', 'wpsecureos' ),
					'priority_vuln_feeds_hourly'  => __( 'Hourly Priority Threat Intelligence Feeds', 'wpsecureos' ),
					'emergency_override_audit'    => __( 'Cryptographically Audited Emergency Override', 'wpsecureos' ),
					'central_connector_multi_site'=> __( 'Central Multi-Site Control Plane Enrollment', 'wpsecureos' ),
				),
			),
			self::PLAN_BUSINESS => array(
				'id'            => self::PLAN_BUSINESS,
				'slug'          => 'business',
				'name'          => __( 'Enterprise Business', 'wpsecureos' ),
				'tagline'       => __( 'Hosting control API, SIEM alerts, white-label, priority SLA', 'wpsecureos' ),
				'price_monthly' => 79.00,
				'price_annual'  => 749.00,
				'site_limit'    => 25,
				'badge_class'   => 'badge-business',
				'features'      => array(
					'core_integrity'              => __( 'Official Core Checksum Integrity Monitoring', 'wpsecureos' ),
					'vulnerability_scan_basic'    => __( 'Public Vulnerability Database Alerts', 'wpsecureos' ),
					'local_backup_manual'         => __( 'Manual Local Database + File Backup Creation', 'wpsecureos' ),
					'risk_urgency_scoring'        => __( 'Explainable Urgency & Confidence Badges', 'wpsecureos' ),
					'basic_hardening'             => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'wpsecureos' ),
					'event_logging'               => __( 'Authentication & Privilege Security Audit Log', 'wpsecureos' ),
					'staging_pipeline'            => __( 'Automated Staging Update & Sandbox Gate Machine', 'wpsecureos' ),
					'rate_limiting_login'         => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'wpsecureos' ),
					'scheduled_daily_scans'       => __( 'Automated Scheduled Daily Background Scans', 'wpsecureos' ),
					'visual_regression_single'    => __( 'Visual Page Regression Testing', 'wpsecureos' ),
					'cloud_backups'               => __( 'Encrypted Cloud Storage Backup Adapter', 'wpsecureos' ),
					'verified_rollback'           => __( 'One-Click Restorable Code & Database Rollback', 'wpsecureos' ),
					'auto_update_delivery'        => __( 'Authenticated Premium Update Delivery', 'wpsecureos' ),
					'visual_regression_unlimited' => __( 'Unlimited Visual Diff Testing on Multiple Viewports', 'wpsecureos' ),
					'deep_malware_heuristics'     => __( 'Deep Uploads Heuristic Malware Analysis', 'wpsecureos' ),
					'priority_vuln_feeds_hourly'  => __( 'Hourly Priority Threat Intelligence Feeds', 'wpsecureos' ),
					'emergency_override_audit'    => __( 'Cryptographically Audited Emergency Override', 'wpsecureos' ),
					'central_connector_multi_site'=> __( 'Central Multi-Site Control Plane Enrollment', 'wpsecureos' ),
					'custom_webhook_dispatch'     => __( 'Custom SIEM Webhook Event Dispatcher', 'wpsecureos' ),
					'enterprise_sla_patches'      => __( 'Priority SLA & Zero-Day Patch Delivery', 'wpsecureos' ),
					'hosting_api_runtime_control' => __( 'Hosting Control Panel PHP Runtime Integration', 'wpsecureos' ),
					'team_rbac_delegation'        => __( 'Granular Role-Based Team Permissions', 'wpsecureos' ),
					'white_label_reporting'       => __( 'Custom White-Label Maintenance Client Reports', 'wpsecureos' ),
				),
			),
		);
	}

	/**
	 * Get single plan configuration.
	 *
	 * @param string $plan_slug
	 * @return array
	 */
	public static function get_plan( $plan_slug ) {
		$plans = self::get_all_plans();
		$slug  = strtolower( trim( $plan_slug ) );
		return isset( $plans[ $slug ] ) ? $plans[ $slug ] : $plans[ self::PLAN_FREE ];
	}

	/**
	 * Determine if current installed site license allows a specific premium feature.
	 *
	 * Core security invariant: essential security alerts, access to existing reports,
	 * and recovery guidance are NEVER disabled, even if license is expired.
	 *
	 * @param string $feature_slug Feature key to check.
	 * @return bool
	 */
	public static function can( $feature_slug ) {
		// Non-negotiable base capabilities: always allowed for all users
		$essential_features = array(
			'core_integrity',
			'vulnerability_scan_basic',
			'local_backup_manual',
			'risk_urgency_scoring',
			'basic_hardening',
			'event_logging',
			'access_existing_reports',
			'recovery_guidance',
		);

		if ( in_array( $feature_slug, $essential_features, true ) ) {
			return true;
		}

		// Check active license entitlements
		$license_data = WpSecureOs_License_Manager::get_license();
		$status       = isset( $license_data['status'] ) ? $license_data['status'] : 'unlicensed';

		// Active or inside offline grace period allows feature execution
		if ( 'active' !== $status && 'grace_period' !== $status ) {
			return false;
		}

		$entitlements = isset( $license_data['entitlements'] ) && is_array( $license_data['entitlements'] )
			? $license_data['entitlements']
			: array();

		return in_array( $feature_slug, $entitlements, true );
	}
}
