<?php
/**
 * Main plugin orchestrator and singleton coordinator.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WpSecureOs_Plugin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return WpSecureOs_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize all WordPress action and filter hooks.
	 */
	private function init_hooks() {
		// Admin Menu & Pages
		add_action( 'admin_menu', array( 'WpSecureOs_Admin_Menu', 'register_menu' ) );

		// Settings API
		add_action( 'admin_init', array( 'WpSecureOs_Settings', 'register_settings' ) );

		// Admin Notices
		add_action( 'admin_notices', array( 'WpSecureOs_Admin_Notices', 'display_notices' ) );
		add_action( 'wp_ajax_wpsecureos_dismiss_notice', array( 'WpSecureOs_Admin_Notices', 'ajax_dismiss_notice' ) );

		// Asset Enqueuing
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// REST API Routes
		add_action( 'rest_api_init', array( 'WpSecureOs_Rest_Controller', 'register_routes' ) );

		// Security Event Hooks
		add_action( 'wp_login_failed', array( 'WpSecureOs_Event_Logger', 'handle_failed_login' ) );

		// Interactive Admin AJAX Actions
		add_action( 'wp_ajax_wpsecureos_action', array( $this, 'handle_ajax_action' ) );

		// Cron Schedule Handler
		add_action( 'wpsecureos_daily_security_scan', array( 'WpSecureOs_Security_Scanner', 'run_scan' ) );
	}

	/**
	 * Enqueue assets only on WpSecureOs admin pages to preserve performance.
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only enqueue on wpsecureos pages
		if ( false === strpos( $hook_suffix, 'wpsecureos' ) ) {
			return;
		}

		// Google Fonts: Inter and JetBrains Mono
		wp_enqueue_style(
			'wpsecureos-google-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap',
			array(),
			WPSECUREOS_VERSION
		);

		// DataTables Core & Responsive CSS
		wp_enqueue_style(
			'wpsecureos-datatables-css',
			WPSECUREOS_PLUGIN_URL . 'assets/vendor/datatables/jquery.dataTables.min.css',
			array(),
			WPSECUREOS_VERSION
		);
		wp_enqueue_style(
			'wpsecureos-datatables-responsive-css',
			WPSECUREOS_PLUGIN_URL . 'assets/vendor/datatables/responsive.dataTables.min.css',
			array( 'wpsecureos-datatables-css' ),
			WPSECUREOS_VERSION
		);

		// WpSecureOs UI Style (Loaded after DataTables to apply cyber-slate & electric-cyan custom styling)
		wp_enqueue_style(
			'wpsecureos-admin-css',
			WPSECUREOS_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wpsecureos-datatables-responsive-css' ),
			WPSECUREOS_VERSION
		);

		// DataTables Core & Responsive JS
		wp_enqueue_script(
			'wpsecureos-datatables-js',
			WPSECUREOS_PLUGIN_URL . 'assets/vendor/datatables/jquery.dataTables.min.js',
			array( 'jquery' ),
			WPSECUREOS_VERSION,
			true
		);
		wp_enqueue_script(
			'wpsecureos-datatables-responsive-js',
			WPSECUREOS_PLUGIN_URL . 'assets/vendor/datatables/dataTables.responsive.min.js',
			array( 'jquery', 'wpsecureos-datatables-js' ),
			WPSECUREOS_VERSION,
			true
		);

		// WpSecureOs UI Interactive Script
		wp_enqueue_script(
			'wpsecureos-admin-js',
			WPSECUREOS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wpsecureos-datatables-responsive-js' ),
			WPSECUREOS_VERSION,
			true
		);

		// Pass localization and nonces
		wp_localize_script(
			'wpsecureos-admin-js',
			'wpsecureos_vars',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'rest_url'   => esc_url_raw( rest_url( 'wpsecureos/v1' ) ),
				'nonce'      => wp_create_nonce( 'wpsecureos_admin_nonce' ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
				'strings'    => array(
					'scanning'            => __( 'Executing continuous offensive validation scan...', 'wpsecureos' ),
					'scan_complete'       => __( 'Scan completed successfully.', 'wpsecureos' ),
					'creating_backup'     => __( 'Creating and verifying full-site archive...', 'wpsecureos' ),
					'backup_verified'     => __( 'Backup archive verified and SHA-256 hash calculated.', 'wpsecureos' ),
					'starting_pipeline'   => __( 'Initiating Safe Update Pipeline state machine...', 'wpsecureos' ),
					'approving_deploy'    => __( 'Authorizing production deployment...', 'wpsecureos' ),
					'action_failed'       => __( 'Operation failed. Check security logs for details.', 'wpsecureos' ),
					'confirm_override'    => __( 'WARNING: Emergency override bypasses staging smoke tests! You must provide a documented operational rationale. Proceed?', 'wpsecureos' ),
				),
			)
		);
	}

	/**
	 * Central AJAX dispatcher for instant dashboard interactions.
	 */
	public function handle_ajax_action() {
		check_ajax_referer( 'wpsecureos_admin_nonce', 'nonce' );

		$sub_action = isset( $_POST['sub_action'] ) ? sanitize_text_field( wp_unslash( $_POST['sub_action'] ) ) : '';

		switch ( $sub_action ) {
			case 'run_scan':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_RUN_SCAN ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_run_scan capability.', 'wpsecureos' ) ), 403 );
				}
				$security  = WpSecureOs_Security_Scanner::run_scan();
				$integrity = WpSecureOs_Integrity_Monitor::run_integrity_check();
				wp_send_json_success(
					array(
						'security'  => $security,
						'integrity' => $integrity,
						'message'   => __( 'Continuous security and integrity validation completed.', 'wpsecureos' ),
					)
				);
				break;

			case 'create_backup':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_PREPARE_UPDATE ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_prepare_update capability.', 'wpsecureos' ) ), 403 );
				}
				$backup = WpSecureOs_Backup_Adapter::create_backup( 'full' );
				if ( $backup['success'] ) {
					wp_send_json_success( $backup );
				} else {
					wp_send_json_error( $backup );
				}
				break;

			case 'start_pipeline':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_PREPARE_UPDATE ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_prepare_update capability.', 'wpsecureos' ) ), 403 );
				}
				$type    = isset( $_POST['component_type'] ) ? sanitize_text_field( wp_unslash( $_POST['component_type'] ) ) : '';
				$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
				$version = isset( $_POST['new_version'] ) ? sanitize_text_field( wp_unslash( $_POST['new_version'] ) ) : '';

				$result = WpSecureOs_Test_Runner::start_safe_update_pipeline( $type, $slug, $version );
				wp_send_json_success( $result );
				break;

			case 'approve_deploy':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_APPROVE_PRODUCTION ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_approve_production capability.', 'wpsecureos' ) ), 403 );
				}
				$idempotency_key = isset( $_POST['idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['idempotency_key'] ) ) : '';
				$result = WpSecureOs_Test_Runner::approve_and_deploy_production( $idempotency_key );
				wp_send_json_success( $result );
				break;

			case 'emergency_override':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_APPROVE_PRODUCTION ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_approve_production capability.', 'wpsecureos' ) ), 403 );
				}
				$idempotency_key = isset( $_POST['idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['idempotency_key'] ) ) : '';
				$reason          = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
				$result          = WpSecureOs_Test_Runner::execute_emergency_override( $idempotency_key, $reason );
				wp_send_json_success( $result );
				break;

			case 'activate_license':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_manage_settings capability.', 'wpsecureos' ) ), 403 );
				}
				$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
				$result      = WpSecureOs_License_Manager::activate( $license_key );
				if ( ! empty( $result['success'] ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result );
				}
				break;

			case 'deactivate_license':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_manage_settings capability.', 'wpsecureos' ) ), 403 );
				}
				$result = WpSecureOs_License_Manager::deactivate();
				if ( ! empty( $result['success'] ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result );
				}
				break;

			case 'refresh_license':
				if ( ! current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing wpsecureos_manage_settings capability.', 'wpsecureos' ) ), 403 );
				}
				$result = WpSecureOs_License_Manager::validate_license();
				wp_send_json_success( $result );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown sub-action requested.', 'wpsecureos' ) ), 400 );
				break;
		}
	}
}
