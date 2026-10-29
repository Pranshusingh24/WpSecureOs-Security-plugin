<?php
/**
 * Role and capability management for MaxSafe.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Capabilities {

	/**
	 * List of custom capabilities.
	 */
	const CAP_VIEW_DASHBOARD       = 'maxsafe_view_dashboard';
	const CAP_VIEW_SECURITY        = 'maxsafe_view_security';
	const CAP_MANAGE_SETTINGS      = 'maxsafe_manage_settings';
	const CAP_RUN_SCAN             = 'maxsafe_run_scan';
	const CAP_PREPARE_UPDATE       = 'maxsafe_prepare_update';
	const CAP_APPROVE_PRODUCTION   = 'maxsafe_approve_production';
	const CAP_MANAGE_RECOVERY      = 'maxsafe_manage_recovery';

	/**
	 * Return array of all custom capabilities with human descriptions.
	 *
	 * @return array
	 */
	public static function get_all_capabilities() {
		return array(
			self::CAP_VIEW_DASHBOARD     => __( 'View MaxSafe dashboard overview', 'maxsafe' ),
			self::CAP_VIEW_SECURITY      => __( 'View security signals, CVEs, and file integrity', 'maxsafe' ),
			self::CAP_MANAGE_SETTINGS    => __( 'Configure MaxSafe settings and integrations', 'maxsafe' ),
			self::CAP_RUN_SCAN           => __( 'Trigger security and inventory scans', 'maxsafe' ),
			self::CAP_PREPARE_UPDATE     => __( 'Create backups, staging clones, and run smoke tests', 'maxsafe' ),
			self::CAP_APPROVE_PRODUCTION => __( 'Approve production deployment and emergency overrides', 'maxsafe' ),
			self::CAP_MANAGE_RECOVERY    => __( 'Execute recovery runbooks and rollback operations', 'maxsafe' ),
		);
	}

	/**
	 * Register capabilities to the administrator role upon activation.
	 */
	public static function register_capabilities() {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		foreach ( array_keys( self::get_all_capabilities() ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Remove capabilities on total cleanup if required.
	 */
	public static function remove_capabilities() {
		$roles = array( 'administrator', 'editor' );
		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( array_keys( self::get_all_capabilities() ) as $cap ) {
					$role->remove_cap( $cap );
				}
				// Also clean up any legacy wpsecureos capabilities
				$legacy_caps = array(
					'wpsecureos_view_dashboard',
					'wpsecureos_view_security',
					'wpsecureos_manage_settings',
					'wpsecureos_run_scan',
					'wpsecureos_prepare_update',
					'wpsecureos_approve_production',
					'wpsecureos_manage_recovery',
				);
				foreach ( $legacy_caps as $old_cap ) {
					$role->remove_cap( $old_cap );
				}
			}
		}
	}

	/**
	 * Helper to check current user authorization with explicit capability and nonce.
	 *
	 * @param string $capability Capability name.
	 * @param string $action     Nonce action name.
	 * @param string $query_arg  Nonce query parameter key.
	 * @return bool
	 */
	public static function check_permission( $capability, $action = '', $query_arg = 'nonce' ) {
		if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! empty( $action ) ) {
			$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				return false;
			}
		}

		return true;
	}
}
