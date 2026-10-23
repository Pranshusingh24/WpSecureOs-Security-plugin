<?php
/**
 * Site inventory and environment analyzer.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Inventory {

	/**
	 * Collect full site inventory safely without exposing credentials.
	 *
	 * @param bool $force_refresh Whether to bypass transient cache.
	 * @return array
	 */
	public static function get_inventory( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( 'wpsecureos_inventory_cache' );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		global $wp_version, $wpdb;

		// 1. Core Environment
		$is_ssl       = is_ssl();
		$is_multisite = is_multisite();
		$debug_mode   = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_log    = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$file_edit_disabled = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
		$db_version   = $wpdb->db_version();

		// 2. Plugins Inventory
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins    = get_plugins();
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( $is_multisite ) {
			$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins = array_unique( array_merge( $active_plugins, array_keys( $network_active ) ) );
		}

		// Check available plugin updates from WordPress core transient.
		$update_plugins_transient = get_site_transient( 'update_plugins' );
		$plugin_update_candidates = ! empty( $update_plugins_transient->response ) ? $update_plugins_transient->response : array();

		$plugins_inventory = array();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$is_active    = in_array( $plugin_file, $active_plugins, true );
			$slug         = dirname( $plugin_file );
			if ( '.' === $slug ) {
				$slug = sanitize_title( $plugin_data['Name'] );
			}

			$has_update    = isset( $plugin_update_candidates[ $plugin_file ] );
			$new_version   = $has_update ? $plugin_update_candidates[ $plugin_file ]->new_version : $plugin_data['Version'];
			$package_url   = $has_update && ! empty( $plugin_update_candidates[ $plugin_file ]->package ) ? 'wp_official' : 'custom_or_none';
			$requires_php  = isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '';
			$requires_wp   = isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '';

			$plugins_inventory[ $plugin_file ] = array(
				'file'              => $plugin_file,
				'slug'              => $slug,
				'name'              => $plugin_data['Name'],
				'installed_version' => $plugin_data['Version'],
				'new_version'       => $new_version,
				'has_update'        => $has_update,
				'is_active'         => $is_active,
				'author'            => wp_strip_all_tags( $plugin_data['Author'] ),
				'requires_php'      => $requires_php,
				'requires_wp'       => $requires_wp,
				'update_source'     => $package_url,
			);
		}

		// 3. Themes Inventory
		$active_theme     = wp_get_theme();
		$all_themes       = wp_get_themes();
		$update_themes    = get_site_transient( 'update_themes' );
		$theme_candidates = ! empty( $update_themes->response ) ? $update_themes->response : array();

		$themes_inventory = array();
		foreach ( $all_themes as $stylesheet => $theme_obj ) {
			$has_update  = isset( $theme_candidates[ $stylesheet ] );
			$new_version = $has_update && ! empty( $theme_candidates[ $stylesheet ]['new_version'] ) ? $theme_candidates[ $stylesheet ]['new_version'] : $theme_obj->get( 'Version' );

			$themes_inventory[ $stylesheet ] = array(
				'slug'              => $stylesheet,
				'name'              => $theme_obj->get( 'Name' ),
				'installed_version' => $theme_obj->get( 'Version' ),
				'new_version'       => $new_version,
				'has_update'        => $has_update,
				'is_active'         => ( $stylesheet === $active_theme->get_stylesheet() ),
				'is_parent'         => ( $stylesheet === $active_theme->get_template() ),
			);
		}

		// 4. Core Update Status
		$update_core_transient = get_site_transient( 'update_core' );
		$core_update_available = false;
		$core_new_version      = $wp_version;
		if ( ! empty( $update_core_transient->updates ) ) {
			foreach ( $update_core_transient->updates as $core_candidate ) {
				if ( 'upgrade' === $core_candidate->response && version_compare( $core_candidate->current, $wp_version, '>' ) ) {
					$core_update_available = true;
					$core_new_version      = $core_candidate->current;
					break;
				}
			}
		}

		// 5. Server Capabilities & File Permissions
		$upload_dir       = wp_upload_dir();
		$uploads_writable = wp_is_writable( $upload_dir['basedir'] );
		$memory_limit     = ini_get( 'memory_limit' );
		$max_execution    = ini_get( 'max_execution_time' );

		$inventory = array(
			'timestamp'         => current_time( 'mysql' ),
			'wordpress'         => array(
				'version'          => $wp_version,
				'has_update'       => $core_update_available,
				'new_version'      => $core_new_version,
				'is_multisite'     => $is_multisite,
				'site_url'         => site_url(),
				'home_url'         => home_url(),
			),
			'runtime'           => array(
				'php_version'      => PHP_VERSION,
				'php_sapi'         => PHP_SAPI,
				'db_engine'        => 'MySQL / MariaDB',
				'db_version'       => $db_version,
				'memory_limit'     => $memory_limit ? $memory_limit : 'N/A',
				'max_execution_sec'=> $max_execution ? $max_execution : 'N/A',
				'server_software'  => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Web Server',
			),
			'security_posture'  => array(
				'https'            => $is_ssl,
				'debug_mode'       => $debug_mode,
				'debug_log'        => $debug_log,
				'debug_display'    => $debug_display,
				'disallow_file_edit' => $file_edit_disabled,
				'uploads_writable' => $uploads_writable,
			),
			'active_theme'      => array(
				'name'             => $active_theme->get( 'Name' ),
				'slug'             => $active_theme->get_stylesheet(),
				'version'          => $active_theme->get( 'Version' ),
			),
			'plugins'           => $plugins_inventory,
			'themes'            => $themes_inventory,
		);

		// Cache for 10 minutes to minimize database queries.
		set_transient( 'wpsecureos_inventory_cache', $inventory, 10 * MINUTE_IN_SECONDS );

		return $inventory;
	}
}
