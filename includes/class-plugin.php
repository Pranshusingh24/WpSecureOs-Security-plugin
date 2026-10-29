<?php
/**
 * Main plugin orchestrator and singleton coordinator.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var MaxSafe_Plugin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return MaxSafe_Plugin
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
		add_action( 'admin_menu', array( 'MaxSafe_Admin_Menu', 'register_menu' ) );

		// Capability fallback filter so any administrator always has access
		add_filter( 'user_has_cap', array( 'MaxSafe_Capabilities', 'filter_user_has_cap' ), 10, 4 );

		// Auto-migration & database table verification check
		add_action( 'admin_init', array( $this, 'maybe_migrate_or_activate' ) );

		// Settings API
		add_action( 'admin_init', array( 'MaxSafe_Settings', 'register_settings' ) );

		// Admin Notices
		add_action( 'admin_notices', array( 'MaxSafe_Admin_Notices', 'display_notices' ) );
		add_action( 'wp_ajax_maxsafe_dismiss_notice', array( 'MaxSafe_Admin_Notices', 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_wpsecureos_dismiss_notice', array( 'MaxSafe_Admin_Notices', 'ajax_dismiss_notice' ) );

		// Asset Enqueuing
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// REST API Routes
		add_action( 'rest_api_init', array( 'MaxSafe_Rest_Controller', 'register_routes' ) );

		// Security Event Hooks
		add_action( 'wp_login_failed', array( 'MaxSafe_Event_Logger', 'handle_failed_login' ) );

		// Interactive Admin AJAX Actions
		add_action( 'wp_ajax_maxsafe_action', array( $this, 'handle_ajax_action' ) );
		add_action( 'wp_ajax_wpsecureos_action', array( $this, 'handle_ajax_action' ) );

		// Cron Schedule Handler
		add_action( 'maxsafe_daily_security_scan', array( 'MaxSafe_Security_Scanner', 'run_scan' ) );
		add_action( 'wpsecureos_daily_security_scan', array( 'MaxSafe_Security_Scanner', 'run_scan' ) );
	}

	/**
	 * Automatically ensure database tables exist, capabilities are registered,
	 * and legacy options are migrated even if the plugin was upgraded/renamed in-place.
	 */
	public function maybe_migrate_or_activate() {
		$current_version = get_option( 'maxsafe_db_version' );
		if ( $current_version !== MAXSAFE_VERSION ) {
			MaxSafe_Activator::activate();
			update_option( 'maxsafe_db_version', MAXSAFE_VERSION );
		}

		if ( current_user_can( 'manage_options' ) ) {
			MaxSafe_Capabilities::register_capabilities();
		}
	}

	/**
	 * Enqueue assets only on MaxSafe admin pages to preserve performance.
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only enqueue on maxsafe pages (support legacy wpsecureos prefix too)
		if ( false === strpos( $hook_suffix, 'maxsafe' ) && false === strpos( $hook_suffix, 'wpsecureos' ) ) {
			return;
		}

		$css_file = MAXSAFE_PLUGIN_DIR . 'assets/css/admin.css';
		$js_file  = MAXSAFE_PLUGIN_DIR . 'assets/js/admin.js';
		$css_ver  = MAXSAFE_VERSION . '.' . ( file_exists( $css_file ) ? filemtime( $css_file ) : '1' );
		$js_ver   = MAXSAFE_VERSION . '.' . ( file_exists( $js_file ) ? filemtime( $js_file ) : '1' );

		// Google Fonts: Inter and JetBrains Mono
		wp_enqueue_style(
			'maxsafe-google-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap',
			array(),
			MAXSAFE_VERSION
		);

		// DataTables Core & Responsive CSS
		wp_enqueue_style(
			'maxsafe-datatables-css',
			MAXSAFE_PLUGIN_URL . 'assets/vendor/datatables/jquery.dataTables.min.css',
			array(),
			MAXSAFE_VERSION
		);
		wp_enqueue_style(
			'maxsafe-datatables-responsive-css',
			MAXSAFE_PLUGIN_URL . 'assets/vendor/datatables/responsive.dataTables.min.css',
			array( 'maxsafe-datatables-css' ),
			MAXSAFE_VERSION
		);

		// MaxSafe UI Style
		wp_enqueue_style(
			'maxsafe-admin-css',
			MAXSAFE_PLUGIN_URL . 'assets/css/admin.css',
			array( 'maxsafe-datatables-responsive-css' ),
			$css_ver
		);

		// DataTables Core & Responsive JS
		wp_enqueue_script(
			'maxsafe-datatables-js',
			MAXSAFE_PLUGIN_URL . 'assets/vendor/datatables/jquery.dataTables.min.js',
			array( 'jquery' ),
			MAXSAFE_VERSION,
			true
		);
		wp_enqueue_script(
			'maxsafe-datatables-responsive-js',
			MAXSAFE_PLUGIN_URL . 'assets/vendor/datatables/dataTables.responsive.min.js',
			array( 'jquery', 'maxsafe-datatables-js' ),
			MAXSAFE_VERSION,
			true
		);

		// MaxSafe UI Interactive Script
		wp_enqueue_script(
			'maxsafe-admin-js',
			MAXSAFE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'maxsafe-datatables-responsive-js' ),
			$js_ver,
			true
		);

		// Pass localization and nonces
		$vars = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'rest_url'   => esc_url_raw( rest_url( 'maxsafe/v1' ) ),
			'nonce'      => wp_create_nonce( 'maxsafe_admin_nonce' ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'strings'    => array(
				'scanning'            => __( 'Executing continuous offensive validation scan...', 'maxsafe' ),
				'scan_complete'       => __( 'Scan completed successfully.', 'maxsafe' ),
				'creating_backup'     => __( 'Creating and verifying full-site archive...', 'maxsafe' ),
				'backup_verified'     => __( 'Backup archive verified and SHA-256 hash calculated.', 'maxsafe' ),
				'starting_pipeline'   => __( 'Initiating Safe Update Pipeline state machine...', 'maxsafe' ),
				'approving_deploy'    => __( 'Authorizing production deployment...', 'maxsafe' ),
				'action_failed'       => __( 'Operation failed. Check security logs for details.', 'maxsafe' ),
				'confirm_override'    => __( 'WARNING: Emergency override bypasses staging smoke tests! You must provide a documented operational rationale. Proceed?', 'maxsafe' ),
			),
		);

		wp_localize_script( 'maxsafe-admin-js', 'maxsafe_vars', $vars );
		// Legacy fallback variable
		wp_localize_script( 'maxsafe-admin-js', 'wpsecureos_vars', $vars );
	}

	/**
	 * Central AJAX dispatcher for instant dashboard interactions.
	 */
	public function handle_ajax_action() {
		// Verify either maxsafe or legacy wpsecureos nonce
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'maxsafe_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'wpsecureos_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page.', 'maxsafe' ) ), 403 );
		}

		$sub_action = isset( $_POST['sub_action'] ) ? sanitize_text_field( wp_unslash( $_POST['sub_action'] ) ) : '';

		switch ( $sub_action ) {
			case 'run_scan':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_RUN_SCAN ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_run_scan capability.', 'maxsafe' ) ), 403 );
				}
				$security  = MaxSafe_Security_Scanner::run_scan();
				$integrity = MaxSafe_Integrity_Monitor::run_integrity_check();
				wp_send_json_success(
					array(
						'security'  => $security,
						'integrity' => $integrity,
						'message'   => __( 'Continuous security and integrity validation completed.', 'maxsafe' ),
					)
				);
				break;

			case 'create_backup':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_PREPARE_UPDATE ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_prepare_update capability.', 'maxsafe' ) ), 403 );
				}
				$backup = MaxSafe_Backup_Adapter::create_backup( 'full' );
				if ( $backup['success'] ) {
					wp_send_json_success( $backup );
				} else {
					wp_send_json_error( $backup );
				}
				break;

			case 'start_pipeline':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_PREPARE_UPDATE ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_prepare_update capability.', 'maxsafe' ) ), 403 );
				}
				$type    = isset( $_POST['component_type'] ) ? sanitize_text_field( wp_unslash( $_POST['component_type'] ) ) : '';
				$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
				$version = isset( $_POST['new_version'] ) ? sanitize_text_field( wp_unslash( $_POST['new_version'] ) ) : '';

				$result = MaxSafe_Test_Runner::start_safe_update_pipeline( $type, $slug, $version );
				wp_send_json_success( $result );
				break;

			case 'approve_deploy':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_APPROVE_PRODUCTION ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_approve_production capability.', 'maxsafe' ) ), 403 );
				}
				$idempotency_key = isset( $_POST['idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['idempotency_key'] ) ) : '';
				$result = MaxSafe_Test_Runner::approve_and_deploy_production( $idempotency_key );
				wp_send_json_success( $result );
				break;

			case 'emergency_override':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_APPROVE_PRODUCTION ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_approve_production capability.', 'maxsafe' ) ), 403 );
				}
				$idempotency_key = isset( $_POST['idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['idempotency_key'] ) ) : '';
				$reason          = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
				$result          = MaxSafe_Test_Runner::execute_emergency_override( $idempotency_key, $reason );
				wp_send_json_success( $result );
				break;

			case 'activate_license':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_MANAGE_SETTINGS ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_manage_settings capability.', 'maxsafe' ) ), 403 );
				}
				$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
				$result      = MaxSafe_License_Manager::activate( $license_key );
				if ( ! empty( $result['success'] ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result );
				}
				break;

			case 'deactivate_license':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_MANAGE_SETTINGS ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_manage_settings capability.', 'maxsafe' ) ), 403 );
				}
				$result = MaxSafe_License_Manager::deactivate();
				if ( ! empty( $result['success'] ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result );
				}
				break;

			case 'refresh_license':
				if ( ! current_user_can( MaxSafe_Capabilities::CAP_MANAGE_SETTINGS ) && ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'Unauthorized: Missing maxsafe_manage_settings capability.', 'maxsafe' ) ), 403 );
				}
				$result = MaxSafe_License_Manager::validate_license();
				wp_send_json_success( $result );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown sub-action requested.', 'maxsafe' ) ), 400 );
				break;
		}
	}
}
