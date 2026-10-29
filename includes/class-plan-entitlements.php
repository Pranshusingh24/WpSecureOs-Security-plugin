<?php
/**
 * Plan Definitions & Entitlements Gatekeeper.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Plan_Entitlements {

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
				'name'          => __( 'Free / Community', 'maxsafe' ),
				'tagline'       => __( 'Essential security posture monitoring & manual safety baseline', 'maxsafe' ),
				'price_monthly' => 0.00,
				'price_annual'  => 0.00,
				'site_limit'    => 1,
				'badge_class'   => 'badge-free',
				'features'      => array(
					'core_integrity'              => __( 'Official Core Checksum Integrity Monitoring', 'maxsafe' ),
					'vulnerability_scan_basic'    => __( 'Public Vulnerability Database Alerts', 'maxsafe' ),
					'local_backup_manual'         => __( 'Manual Local Database + File Backup Creation', 'maxsafe' ),
					'risk_urgency_scoring'        => __( 'Explainable Urgency & Confidence Badges', 'maxsafe' ),
					'basic_hardening'             => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'maxsafe' ),
					'event_logging'               => __( 'Authentication & Privilege Security Audit Log', 'maxsafe' ),
					'staging_pipeline'            => __( 'Automated Staging Update & Sandbox Gate Machine', 'maxsafe' ),
					'rate_limiting_login'         => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'maxsafe' ),
					'scheduled_daily_scans'       => __( 'Automated Scheduled Daily Background Scans', 'maxsafe' ),
					'visual_regression_single'    => __( 'Visual Page Regression Testing', 'maxsafe' ),
					'cloud_backups'               => __( 'Encrypted Cloud Storage Backup Adapter', 'maxsafe' ),
					'verified_rollback'           => __( 'One-Click Restorable Code & Database Rollback', 'maxsafe' ),
					'auto_update_delivery'        => __( 'Authenticated Premium Update Delivery', 'maxsafe' ),
					'visual_regression_unlimited' => __( 'Unlimited Visual Diff Testing on Multiple Viewports', 'maxsafe' ),
					'deep_malware_heuristics'     => __( 'Deep Uploads Heuristic Malware Analysis', 'maxsafe' ),
					'priority_vuln_feeds_hourly'  => __( 'Hourly Priority Threat Intelligence Feeds', 'maxsafe' ),
					'emergency_override_audit'    => __( 'Cryptographically Audited Emergency Override', 'maxsafe' ),
					'central_connector_multi_site'=> __( 'Central Multi-Site Control Plane Enrollment', 'maxsafe' ),
					'custom_webhook_dispatch'     => __( 'Custom SIEM Webhook Event Dispatcher', 'maxsafe' ),
					'enterprise_sla_patches'      => __( 'Priority SLA & Zero-Day Patch Delivery', 'maxsafe' ),
					'hosting_api_runtime_control' => __( 'Hosting Control Panel PHP Runtime Integration', 'maxsafe' ),
					'team_rbac_delegation'        => __( 'Granular Role-Based Team Permissions', 'maxsafe' ),
					'white_label_reporting'       => __( 'Custom White-Label Maintenance Client Reports', 'maxsafe' ),
				),
			),
			self::PLAN_PRO      => array(
				'id'            => self::PLAN_PRO,
				'slug'          => 'pro',
				'name'          => __( 'MaxSafe Pro', 'maxsafe' ),
				'tagline'       => __( 'Automated safe update pipeline & visual smoke diffs', 'maxsafe' ),
				'price_monthly' => 9.00,
				'price_annual'  => 89.00,
				'site_limit'    => 1,
				'badge_class'   => 'badge-pro',
				'highlight'     => true,
				'features'      => array(
					'core_integrity'           => __( 'Official Core Checksum Integrity Monitoring', 'maxsafe' ),
					'vulnerability_scan_basic' => __( 'Public Vulnerability Database Alerts', 'maxsafe' ),
					'local_backup_manual'      => __( 'Manual Local Database + File Backup Creation', 'maxsafe' ),
					'risk_urgency_scoring'     => __( 'Explainable Urgency & Confidence Badges', 'maxsafe' ),
					'basic_hardening'          => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'maxsafe' ),
					'event_logging'            => __( 'Authentication & Privilege Security Audit Log', 'maxsafe' ),
					'staging_pipeline'         => __( 'Automated Staging Update & Sandbox Gate Machine', 'maxsafe' ),
					'rate_limiting_login'      => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'maxsafe' ),
					'scheduled_daily_scans'    => __( 'Automated Scheduled Daily Background Scans', 'maxsafe' ),
					'visual_regression_single' => __( 'Visual Page Regression Testing (1 Critical URL)', 'maxsafe' ),
					'cloud_backups'            => __( 'Encrypted Cloud Storage Backup Adapter', 'maxsafe' ),
					'verified_rollback'        => __( 'One-Click Restorable Code & Database Rollback', 'maxsafe' ),
					'auto_update_delivery'     => __( 'Authenticated Premium Update Delivery', 'maxsafe' ),
				),
			),
			self::PLAN_AGENCY   => array(
				'id'            => self::PLAN_AGENCY,
				'slug'          => 'agency',
				'name'          => __( 'MaxSafe Agency', 'maxsafe' ),
				'tagline'       => __( 'Multi-site fleet defense & deep heuristic malware traps', 'maxsafe' ),
				'price_monthly' => 29.00,
				'price_annual'  => 279.00,
				'site_limit'    => 5,
				'badge_class'   => 'badge-agency',
				'features'      => array(
					'core_integrity'              => __( 'Official Core Checksum Integrity Monitoring', 'maxsafe' ),
					'vulnerability_scan_basic'    => __( 'Public Vulnerability Database Alerts', 'maxsafe' ),
					'local_backup_manual'         => __( 'Manual Local Database + File Backup Creation', 'maxsafe' ),
					'risk_urgency_scoring'        => __( 'Explainable Urgency & Confidence Badges', 'maxsafe' ),
					'basic_hardening'             => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'maxsafe' ),
					'event_logging'               => __( 'Authentication & Privilege Security Audit Log', 'maxsafe' ),
					'staging_pipeline'            => __( 'Automated Staging Update & Sandbox Gate Machine', 'maxsafe' ),
					'rate_limiting_login'         => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'maxsafe' ),
					'scheduled_daily_scans'       => __( 'Automated Scheduled Daily Background Scans', 'maxsafe' ),
					'visual_regression_single'    => __( 'Visual Page Regression Testing', 'maxsafe' ),
					'cloud_backups'               => __( 'Encrypted Cloud Storage Backup Adapter', 'maxsafe' ),
					'verified_rollback'           => __( 'One-Click Restorable Code & Database Rollback', 'maxsafe' ),
					'auto_update_delivery'        => __( 'Authenticated Premium Update Delivery', 'maxsafe' ),
					'visual_regression_unlimited' => __( 'Unlimited Visual Diff Testing on Multiple Viewports', 'maxsafe' ),
					'deep_malware_heuristics'     => __( 'Deep Uploads Heuristic Malware Analysis', 'maxsafe' ),
					'priority_vuln_feeds_hourly'  => __( 'Hourly Priority Threat Intelligence Feeds', 'maxsafe' ),
					'emergency_override_audit'    => __( 'Cryptographically Audited Emergency Override', 'maxsafe' ),
					'central_connector_multi_site'=> __( 'Central Multi-Site Control Plane Enrollment', 'maxsafe' ),
				),
			),
			self::PLAN_BUSINESS => array(
				'id'            => self::PLAN_BUSINESS,
				'slug'          => 'business',
				'name'          => __( 'MaxSafe Enterprise Business', 'maxsafe' ),
				'tagline'       => __( 'Hosting control API, SIEM alerts, white-label, priority SLA', 'maxsafe' ),
				'price_monthly' => 79.00,
				'price_annual'  => 749.00,
				'site_limit'    => 25,
				'badge_class'   => 'badge-business',
				'features'      => array(
					'core_integrity'              => __( 'Official Core Checksum Integrity Monitoring', 'maxsafe' ),
					'vulnerability_scan_basic'    => __( 'Public Vulnerability Database Alerts', 'maxsafe' ),
					'local_backup_manual'         => __( 'Manual Local Database + File Backup Creation', 'maxsafe' ),
					'risk_urgency_scoring'        => __( 'Explainable Urgency & Confidence Badges', 'maxsafe' ),
					'basic_hardening'             => __( 'HTTPS, Sensitive Directory & Hardening Checks', 'maxsafe' ),
					'event_logging'               => __( 'Authentication & Privilege Security Audit Log', 'maxsafe' ),
					'staging_pipeline'            => __( 'Automated Staging Update & Sandbox Gate Machine', 'maxsafe' ),
					'rate_limiting_login'         => __( 'Brute-force Login Rate Limiting & Auto-Lockout', 'maxsafe' ),
					'scheduled_daily_scans'       => __( 'Automated Scheduled Daily Background Scans', 'maxsafe' ),
					'visual_regression_single'    => __( 'Visual Page Regression Testing', 'maxsafe' ),
					'cloud_backups'               => __( 'Encrypted Cloud Storage Backup Adapter', 'maxsafe' ),
					'verified_rollback'           => __( 'One-Click Restorable Code & Database Rollback', 'maxsafe' ),
					'auto_update_delivery'        => __( 'Authenticated Premium Update Delivery', 'maxsafe' ),
					'visual_regression_unlimited' => __( 'Unlimited Visual Diff Testing on Multiple Viewports', 'maxsafe' ),
					'deep_malware_heuristics'     => __( 'Deep Uploads Heuristic Malware Analysis', 'maxsafe' ),
					'priority_vuln_feeds_hourly'  => __( 'Hourly Priority Threat Intelligence Feeds', 'maxsafe' ),
					'emergency_override_audit'    => __( 'Cryptographically Audited Emergency Override', 'maxsafe' ),
					'central_connector_multi_site'=> __( 'Central Multi-Site Control Plane Enrollment', 'maxsafe' ),
					'custom_webhook_dispatch'     => __( 'Custom SIEM Webhook Event Dispatcher', 'maxsafe' ),
					'enterprise_sla_patches'      => __( 'Priority SLA & Zero-Day Patch Delivery', 'maxsafe' ),
					'hosting_api_runtime_control' => __( 'Hosting Control Panel PHP Runtime Integration', 'maxsafe' ),
					'team_rbac_delegation'        => __( 'Granular Role-Based Team Permissions', 'maxsafe' ),
					'white_label_reporting'       => __( 'Custom White-Label Maintenance Client Reports', 'maxsafe' ),
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
	 * Determine if current installed site license allows a specific feature.
	 *
	 * All features are 100% unlocked and free for all users.
	 *
	 * @param string $feature_slug Feature key to check.
	 * @return bool Always true.
	 */
	public static function can( $feature_slug ) {
		// All plugin features are 100% unlocked and free for all users.
		return true;
	}
}
