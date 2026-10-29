<?php
/**
 * Admin Menu registration and page rendering router.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Admin_Menu {

	/**
	 * Register MaxSafe top-level and submenu items.
	 */
	public static function register_menu() {
		// Ensure administrator role has registered capabilities
		if ( current_user_can( 'manage_options' ) && ! current_user_can( MaxSafe_Capabilities::CAP_VIEW_DASHBOARD ) ) {
			MaxSafe_Capabilities::register_capabilities();
		}

		// Seamlessly redirect legacy bookmarked URLs (?page=wpsecureos* -> ?page=maxsafe*)
		if ( is_admin() && isset( $_GET['page'] ) && is_string( $_GET['page'] ) && 0 === strpos( $_GET['page'], 'wpsecureos' ) ) {
			$new_page = str_replace( 'wpsecureos', 'maxsafe', sanitize_text_field( wp_unslash( $_GET['page'] ) ) );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $new_page ) );
			exit;
		}

		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0ea5e9"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 2.18l7 3.12v4.7c0 4.54-3.13 8.78-7 9.9-3.87-1.12-7-5.36-7-9.9V6.3l7-3.12zm-1 5.82v6h2v-6h-2zm0 8v2h2v-2h-2z"/></svg>'
		);

		// 1. Top Level Menu: Overview
		add_menu_page(
			__( 'MaxSafe — Security & Safe Updates', 'maxsafe' ),
			'MaxSafe',
			MaxSafe_Capabilities::CAP_VIEW_DASHBOARD,
			'maxsafe',
			array( __CLASS__, 'render_overview_page' ),
			$icon_svg,
			59
		);

		// 2. Submenu: Overview
		add_submenu_page(
			'maxsafe',
			__( 'Overview — MaxSafe', 'maxsafe' ),
			__( 'Overview', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_VIEW_DASHBOARD,
			'maxsafe',
			array( __CLASS__, 'render_overview_page' )
		);

		// 3. Submenu: Updates & Safe Deploy
		add_submenu_page(
			'maxsafe',
			__( 'Updates & Safe Deploy — MaxSafe', 'maxsafe' ),
			__( 'Updates', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_PREPARE_UPDATE,
			'maxsafe-updates',
			array( __CLASS__, 'render_updates_page' )
		);

		// 4. Submenu: Security & File Integrity
		add_submenu_page(
			'maxsafe',
			__( 'Security & File Integrity — MaxSafe', 'maxsafe' ),
			__( 'Security', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_VIEW_SECURITY,
			'maxsafe-security',
			array( __CLASS__, 'render_security_page' )
		);

		// 5. Submenu: Backups & Recovery
		add_submenu_page(
			'maxsafe',
			__( 'Backups & Recovery — MaxSafe', 'maxsafe' ),
			__( 'Backups', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_MANAGE_RECOVERY,
			'maxsafe-backups',
			array( __CLASS__, 'render_backups_page' )
		);

		// 6. Submenu: Staging & Visual Tests
		add_submenu_page(
			'maxsafe',
			__( 'Staging & Visual Tests — MaxSafe', 'maxsafe' ),
			__( 'Staging & Tests', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_PREPARE_UPDATE,
			'maxsafe-staging',
			array( __CLASS__, 'render_staging_page' )
		);

		// 7. Submenu: Security Activity Log
		add_submenu_page(
			'maxsafe',
			__( 'Security & Audit Log — MaxSafe', 'maxsafe' ),
			__( 'Activity Log', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_VIEW_SECURITY,
			'maxsafe-logs',
			array( __CLASS__, 'render_logs_page' )
		);

		// 8. Submenu: Settings
		add_submenu_page(
			'maxsafe',
			__( 'Settings — MaxSafe', 'maxsafe' ),
			__( 'Settings', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_MANAGE_SETTINGS,
			'maxsafe-settings',
			array( __CLASS__, 'render_settings_page' )
		);

		// 9. Submenu: License
		add_submenu_page(
			'maxsafe',
			__( 'License — MaxSafe', 'maxsafe' ),
			__( 'License', 'maxsafe' ),
			MaxSafe_Capabilities::CAP_MANAGE_SETTINGS,
			'maxsafe-license',
			array( __CLASS__, 'render_license_page' )
		);
	}

	/**
	 * Page Render Callbacks
	 */
	public static function render_overview_page() {
		self::render_template( 'dashboard' );
	}

	public static function render_updates_page() {
		self::render_template( 'updates' );
	}

	public static function render_security_page() {
		self::render_template( 'security' );
	}

	public static function render_backups_page() {
		self::render_template( 'backup-status' );
	}

	public static function render_staging_page() {
		self::render_template( 'staging-tests' );
	}

	public static function render_logs_page() {
		self::render_template( 'activity-log' );
	}

	public static function render_settings_page() {
		self::render_template( 'settings' );
	}

	public static function render_license_page() {
		self::render_template( 'pricing-license' );
	}

	/**
	 * Render template with master MaxSafe layout wrapper.
	 *
	 * @param string $template_name
	 */
	private static function render_template( $template_name ) {
		$template_file = MAXSAFE_PLUGIN_DIR . 'templates/' . $template_name . '.php';
		if ( file_exists( $template_file ) ) {
			// Include master layout which includes the specific template
			$current_tab = $template_name;
			include MAXSAFE_PLUGIN_DIR . 'templates/layout.php';
		} else {
			/* translators: %s: Template file name */
			$error_message = sprintf( __( 'Template %s not found.', 'maxsafe' ), $template_name );
			echo '<div class="notice notice-error"><p>' . esc_html( $error_message ) . '</p></div>';
		}
	}
}
