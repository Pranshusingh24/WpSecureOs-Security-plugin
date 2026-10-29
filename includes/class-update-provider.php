<?php
/**
 * Core, plugin, and theme update provider.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Update_Provider {

	/**
	 * Get all pending updates across Core, Plugins, Themes along with explainable risk assessment.
	 *
	 * @param bool $force_check Whether to trigger WordPress core update check.
	 * @return array
	 */
	public static function get_updates_with_assessment( $force_check = false ) {
		if ( $force_check ) {
			wp_version_check();
			wp_update_plugins();
			wp_update_themes();
			delete_transient( 'maxsafe_inventory_cache' );
			delete_transient( 'wpsecureos_inventory_cache' );
		}

		$inventory = MaxSafe_Inventory::get_inventory( $force_check );

		// Retrieve backup health context
		$last_backup = MaxSafe_Backup_Adapter::get_latest_verified_backup();
		$backup_hours = null;
		if ( $last_backup ) {
			$backup_time = strtotime( $last_backup['created_at'] );
			$backup_hours = round( ( time() - $backup_time ) / 3600 );
		}

		// Retrieve vulnerability intelligence context
		$vulns = MaxSafe_Security_Scanner::get_known_vulnerabilities();

		// Check if WooCommerce is installed and active
		$has_woocommerce = class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', array() ), true );

		$context = array(
			'recent_verified_backup_hours' => $backup_hours,
			'vulnerabilities'              => $vulns,
			'has_woocommerce'              => $has_woocommerce,
			'staging_tests_passed'         => false,
			'staging_tests_failed'         => false,
		);

		$assessed_updates = array();

		// 1. Core Update
		if ( ! empty( $inventory['wordpress']['has_update'] ) ) {
			$core_item = array(
				'type'              => 'core',
				'slug'              => 'wordpress-core',
				'name'              => 'WordPress Core',
				'installed_version' => $inventory['wordpress']['version'],
				'new_version'       => $inventory['wordpress']['new_version'],
				'is_active'         => true,
			);
			$assessment = MaxSafe_Risk_Assessor::assess_component( $core_item, $context );
			$assessed_updates[] = array_merge( $core_item, array( 'assessment' => $assessment ) );
		}

		// 2. Plugins Updates
		if ( ! empty( $inventory['plugins'] ) ) {
			foreach ( $inventory['plugins'] as $plugin_file => $plugin_data ) {
				if ( ! empty( $plugin_data['has_update'] ) ) {
					$assessment = MaxSafe_Risk_Assessor::assess_component( $plugin_data, $context );
					$assessed_updates[] = array_merge(
						$plugin_data,
						array(
							'type'       => 'plugin',
							'assessment' => $assessment,
						)
					);
				}
			}
		}

		// 3. Themes Updates
		if ( ! empty( $inventory['themes'] ) ) {
			foreach ( $inventory['themes'] as $theme_slug => $theme_data ) {
				if ( ! empty( $theme_data['has_update'] ) ) {
					$assessment = MaxSafe_Risk_Assessor::assess_component( $theme_data, $context );
					$assessed_updates[] = array_merge(
						$theme_data,
						array(
							'type'       => 'theme',
							'assessment' => $assessment,
						)
					);
				}
			}
		}

		return $assessed_updates;
	}
}
