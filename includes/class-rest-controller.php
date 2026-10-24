<?php
/**
 * REST API Controller for WpSecureOs.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Rest_Controller {

	const NAMESPACE = 'wpsecureos/v1';

	/**
	 * Register all REST API routes.
	 */
	public static function register_routes() {
		// 1. GET /status
		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_status' ),
				'permission_callback' => array( __CLASS__, 'check_view_permission' ),
			)
		);

		// 2. POST /scan
		register_rest_route(
			self::NAMESPACE,
			'/scan',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'run_scan' ),
				'permission_callback' => array( __CLASS__, 'check_scan_permission' ),
			)
		);

		// 3. GET /updates
		register_rest_route(
			self::NAMESPACE,
			'/updates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_updates' ),
				'permission_callback' => array( __CLASS__, 'check_view_permission' ),
			)
		);

		// 4. POST /update/orchestrate
		register_rest_route(
			self::NAMESPACE,
			'/update/orchestrate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'orchestrate_update' ),
				'permission_callback' => array( __CLASS__, 'check_prepare_permission' ),
			)
		);

		// 5. POST /update/approve
		register_rest_route(
			self::NAMESPACE,
			'/update/approve',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'approve_update' ),
				'permission_callback' => array( __CLASS__, 'check_approve_permission' ),
			)
		);

		// 6. POST /update/emergency-override
		register_rest_route(
			self::NAMESPACE,
			'/update/emergency-override',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'emergency_override' ),
				'permission_callback' => array( __CLASS__, 'check_approve_permission' ),
			)
		);

		// 7. POST /backup/create
		register_rest_route(
			self::NAMESPACE,
			'/backup/create',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_backup' ),
				'permission_callback' => array( __CLASS__, 'check_prepare_permission' ),
			)
		);

		// 8. GET /logs
		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_logs' ),
				'permission_callback' => array( __CLASS__, 'check_view_security_permission' ),
			)
		);

		// 9. GET /license
		register_rest_route(
			self::NAMESPACE,
			'/license',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_license' ),
				'permission_callback' => array( __CLASS__, 'check_view_permission' ),
			)
		);

		// 10. POST /license/activate
		register_rest_route(
			self::NAMESPACE,
			'/license/activate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'activate_license' ),
				'permission_callback' => array( __CLASS__, 'check_manage_settings_permission' ),
			)
		);

		// 11. POST /license/deactivate
		register_rest_route(
			self::NAMESPACE,
			'/license/deactivate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'deactivate_license' ),
				'permission_callback' => array( __CLASS__, 'check_manage_settings_permission' ),
			)
		);
	}

	// Permission Callbacks
	public static function check_manage_settings_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_MANAGE_SETTINGS );
	}

	public static function check_view_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_VIEW_DASHBOARD );
	}

	public static function check_view_security_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_VIEW_SECURITY );
	}

	public static function check_scan_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_RUN_SCAN );
	}

	public static function check_prepare_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_PREPARE_UPDATE );
	}

	public static function check_approve_permission( $request ) {
		return current_user_can( WpSecureOs_Capabilities::CAP_APPROVE_PRODUCTION );
	}

	// Route Callbacks
	public static function get_status( $request ) {
		$inventory = WpSecureOs_Inventory::get_inventory();
		$latest_backup = WpSecureOs_Backup_Adapter::get_latest_verified_backup();
		$php_info = WpSecureOs_Php_Advisor::analyze_current_runtime();

		return rest_ensure_response(
			array(
				'success'       => true,
				'inventory'     => $inventory,
				'latest_backup' => $latest_backup,
				'php_runtime'   => $php_info,
			)
		);
	}

	public static function run_scan( $request ) {
		$scan_results = WpSecureOs_Security_Scanner::run_scan();
		$integrity    = WpSecureOs_Integrity_Monitor::run_integrity_check();

		return rest_ensure_response(
			array(
				'success'   => true,
				'security'  => $scan_results,
				'integrity' => $integrity,
			)
		);
	}

	public static function get_updates( $request ) {
		$force = (bool) $request->get_param( 'force' );
		$updates = WpSecureOs_Update_Provider::get_updates_with_assessment( $force );

		return rest_ensure_response(
			array(
				'success' => true,
				'updates' => $updates,
			)
		);
	}

	public static function orchestrate_update( $request ) {
		$type    = sanitize_text_field( $request->get_param( 'component_type' ) );
		$slug    = sanitize_text_field( $request->get_param( 'slug' ) );
		$version = sanitize_text_field( $request->get_param( 'new_version' ) );

		if ( empty( $type ) || empty( $slug ) || empty( $version ) ) {
			return new WP_Error( 'missing_params', __( 'component_type, slug, and new_version are required.', 'wpsecureos' ), array( 'status' => 400 ) );
		}

		$result = WpSecureOs_Test_Runner::start_safe_update_pipeline( $type, $slug, $version );
		return rest_ensure_response( $result );
	}

	public static function approve_update( $request ) {
		$key = sanitize_text_field( $request->get_param( 'idempotency_key' ) );
		if ( empty( $key ) ) {
			return new WP_Error( 'missing_key', __( 'idempotency_key is required.', 'wpsecureos' ), array( 'status' => 400 ) );
		}

		$result = WpSecureOs_Test_Runner::approve_and_deploy_production( $key );
		return rest_ensure_response( $result );
	}

	public static function emergency_override( $request ) {
		$key    = sanitize_text_field( $request->get_param( 'idempotency_key' ) );
		$reason = sanitize_textarea_field( $request->get_param( 'reason' ) );

		if ( empty( $key ) || empty( $reason ) ) {
			return new WP_Error( 'missing_fields', __( 'idempotency_key and documented reason are required.', 'wpsecureos' ), array( 'status' => 400 ) );
		}

		$result = WpSecureOs_Test_Runner::execute_emergency_override( $key, $reason );
		return rest_ensure_response( $result );
	}

	public static function create_backup( $request ) {
		$type = sanitize_text_field( $request->get_param( 'backup_type' ) );
		if ( empty( $type ) ) {
			$type = 'full';
		}

		$result = WpSecureOs_Backup_Adapter::create_backup( $type );
		return rest_ensure_response( $result );
	}

	public static function get_logs( $request ) {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$severity = sanitize_text_field( $request->get_param( 'severity' ) );
		$search   = sanitize_text_field( $request->get_param( 'search' ) );

		if ( $page < 1 ) $page = 1;
		if ( $per_page < 1 || $per_page > 100 ) $per_page = 20;

		$logs = WpSecureOs_Event_Logger::get_events( $page, $per_page, $severity, $search );
		return rest_ensure_response( $logs );
	}

	public static function get_license( $request ) {
		$license = WpSecureOs_License_Manager::get_license();
		return rest_ensure_response( array(
			'success' => true,
			'license' => $license,
		) );
	}

	public static function activate_license( $request ) {
		$key    = sanitize_text_field( $request->get_param( 'license_key' ) );
		$result = WpSecureOs_License_Manager::activate( $key );
		$status = ! empty( $result['success'] ) ? 200 : 400;
		return new WP_REST_Response( $result, $status );
	}

	public static function deactivate_license( $request ) {
		$result = WpSecureOs_License_Manager::deactivate();
		$status = ! empty( $result['success'] ) ? 200 : 400;
		return new WP_REST_Response( $result, $status );
	}
}
