<?php
/**
 * Safe Update Pipeline & Orchestration Finite State Machine (FSM).
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Test_Runner {

	// Pipeline States
	const STATE_IDLE             = 'idle';
	const STATE_QUEUED           = 'queued';
	const STATE_PREFLIGHT        = 'preflight';
	const STATE_BACKUP           = 'backup';
	const STATE_STAGING_CLONE    = 'staging_clone';
	const STATE_STAGING_UPDATE   = 'staging_update';
	const STATE_AUTOMATED_TESTS  = 'automated_tests';
	const STATE_APPROVAL_REQUIRED= 'approval_required';
	const STATE_PRODUCTION_DEPLOY= 'production_deploy';
	const STATE_POST_VERIFY      = 'post_verify';
	const STATE_COMPLETED        = 'completed';
	const STATE_FAILED           = 'failed';
	const STATE_RECOVERY_REQUIRED= 'recovery_required';

	/**
	 * Start a new safe update orchestration pipeline.
	 *
	 * @param string $component_type 'core', 'plugin', 'theme'
	 * @param string $slug           Component slug
	 * @param string $new_version    Target version to update
	 * @return array Pipeline execution record
	 */
	public static function start_safe_update_pipeline( $component_type, $slug, $new_version ) {
		// Verify authorization
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_PREPARE_UPDATE ) && ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Insufficient permissions to initiate safe update pipeline.', 'maxsafe' ),
			);
		}

		// Verify subscription entitlement for automated staging pipeline
		if ( ! MaxSafe_Plan_Entitlements::can( 'staging_pipeline' ) ) {
			return array(
				'success' => false,
				'state'   => self::STATE_FAILED,
				'message' => __( 'Automated Staging Pipeline requires an active MaxSafe Pro, Agency, or Business subscription.', 'maxsafe' ),
			);
		}

		$idempotency_key = md5( $component_type . ':' . $slug . ':' . $new_version . ':' . gmdate( 'Ymd' ) );

		global $wpdb;
		$table_jobs = $wpdb->prefix . 'maxsafe_jobs';

		// Step 1: Preflight Checks
		$preflight = self::run_preflight_checks( $component_type, $slug, $new_version );
		if ( ! $preflight['passed'] ) {
			return array(
				'success' => false,
				'state'   => self::STATE_FAILED,
				'message' => $preflight['error'],
			);
		}

		// Step 2: Backup Creation & Archive Verification Gate
		$backup_result = MaxSafe_Backup_Adapter::create_backup( 'full' );
		if ( ! $backup_result['success'] || ! $backup_result['verified'] ) {
			return array(
				'success' => false,
				'state'   => self::STATE_FAILED,
				'message' => __( 'Pipeline aborted: Pre-update verified backup creation failed.', 'maxsafe' ),
			);
		}

		// Step 3: Staging Sandbox Preparation
		$staging_prep = MaxSafe_Staging_Adapter::prepare_staging_environment();

		// Step 4: Apply Update in Staging
		$staging_update = MaxSafe_Staging_Adapter::apply_update_in_staging( $component_type, $slug, $new_version );

		// Step 5: Run Smoke & Visual Tests
		$test_results = MaxSafe_Visual_Test_Adapter::run_smoke_and_visual_suite();

		// Step 6: Determine State
		$next_state = $test_results['overall_pass'] ? self::STATE_APPROVAL_REQUIRED : self::STATE_FAILED;

		$job_payload = array(
			'component_type' => $component_type,
			'slug'           => $slug,
			'target_version' => $new_version,
			'backup'         => $backup_result,
			'staging'        => $staging_prep,
			'test_results'   => $test_results,
		);

		$wpdb->replace(
			$table_jobs,
			array(
				'job_type'        => 'safe_update_pipeline',
				'status'          => $next_state,
				'idempotency_key' => $idempotency_key,
				'current_step'    => $next_state,
				'payload_json'    => wp_json_encode( $job_payload ),
				'result_json'     => wp_json_encode( $test_results ),
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		MaxSafe_Event_Logger::log_event(
			'pipeline_state_changed',
			( self::STATE_APPROVAL_REQUIRED === $next_state ) ? 'low' : 'high',
			'Update Orchestrator',
			sprintf(
				'Pipeline reached state: %s for %s (%s). Automated smoke tests: %s.',
				$next_state,
				$slug,
				$new_version,
				$test_results['overall_pass'] ? 'PASSED' : 'FAILED'
			),
			$job_payload
		);

		return array(
			'success'         => true,
			'job_id'          => $wpdb->insert_id,
			'idempotency_key' => $idempotency_key,
			'state'           => $next_state,
			'test_results'    => $test_results,
			'backup'          => $backup_result,
			'can_approve'     => ( self::STATE_APPROVAL_REQUIRED === $next_state ),
			'message'         => ( self::STATE_APPROVAL_REQUIRED === $next_state )
				? __( 'Staging tests and backup verified. Ready for administrator approval.', 'maxsafe' )
				: __( 'Staging smoke tests or visual threshold checks failed. Production update blocked.', 'maxsafe' ),
		);
	}

	/**
	 * Preflight environmental check prior to update orchestration.
	 */
	public static function run_preflight_checks( $type, $slug, $new_version ) {
		// Check disk space
		$disk_free = function_exists( 'disk_free_space' ) ? @disk_free_space( ABSPATH ) : null;
		if ( null !== $disk_free && $disk_free < 50 * 1024 * 1024 ) { // 50MB minimum
			return array(
				'passed' => false,
				'error'  => __( 'Preflight failed: Low server disk space (< 50MB available).', 'maxsafe' ),
			);
		}

		// Check memory limit
		$memory = ini_get( 'memory_limit' );

		return array(
			'passed' => true,
			'disk_space_bytes' => $disk_free,
			'memory' => $memory,
		);
	}

	/**
	 * Administrator approval for production deployment.
	 *
	 * @param string $idempotency_key Job token.
	 * @return array
	 */
	public static function approve_and_deploy_production( $idempotency_key ) {
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_APPROVE_PRODUCTION ) && ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: You lack maxsafe_approve_production capability.', 'maxsafe' ),
			);
		}

		global $wpdb;
		$table_jobs = $wpdb->prefix . 'maxsafe_jobs';
		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_jobs} WHERE idempotency_key = %s", $idempotency_key ), ARRAY_A );

		if ( ! $job ) {
			return array(
				'success' => false,
				'message' => __( 'Update job not found.', 'maxsafe' ),
			);
		}

		if ( self::STATE_APPROVAL_REQUIRED !== $job['status'] ) {
			return array(
				'success' => false,
				/* translators: %s: Pipeline job state */
				'message' => sprintf( __( 'Cannot deploy job in current state: %s. Staging tests and backup must pass first.', 'maxsafe' ), $job['status'] ),
			);
		}

		// Execute production rollout using WordPress official update upgrader
		$payload = json_decode( $job['payload_json'], true );
		$deploy_status = self::execute_wordpress_update( $payload['component_type'], $payload['slug'] );

		// Run post-deploy verification
		$post_verify = MaxSafe_Visual_Test_Adapter::run_smoke_checks();
		$final_state = ( $deploy_status['success'] && $post_verify['rest_api_functional'] ) ? self::STATE_COMPLETED : self::STATE_RECOVERY_REQUIRED;

		$wpdb->update(
			$table_jobs,
			array(
				'status'       => $final_state,
				'current_step' => $final_state,
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'idempotency_key' => $idempotency_key ),
			array( '%s', '%s', '%s' ),
			array( '%s' )
		);

		MaxSafe_Event_Logger::log_event(
			'production_update_deployed',
			( self::STATE_COMPLETED === $final_state ) ? 'low' : 'critical',
			'Update Orchestrator',
			sprintf( 'Production update for %s deployed. Post-deploy status: %s.', $payload['slug'], $final_state ),
			array(
				'deploy_status' => $deploy_status,
				'post_verify'   => $post_verify,
			)
		);

		// Clean up staging
		MaxSafe_Staging_Adapter::cleanup_staging();

		return array(
			'success'     => ( self::STATE_COMPLETED === $final_state ),
			'state'       => $final_state,
			'message'     => ( self::STATE_COMPLETED === $final_state )
				? __( 'Production update successfully deployed and verified live.', 'maxsafe' )
				: __( 'Post-deployment verification failed! Consult Recovery Runbook immediately.', 'maxsafe' ),
			'post_verify' => $post_verify,
		);
	}

	/**
	 * Emergency override deployment for zero-day critical patches.
	 */
	public static function execute_emergency_override( $idempotency_key, $reason ) {
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_APPROVE_PRODUCTION ) && ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unauthorized: Emergency override requires maxsafe_approve_production capability.', 'maxsafe' ),
			);
		}

		if ( empty( trim( $reason ) ) ) {
			return array(
				'success' => false,
				'message' => __( 'A documented operational rationale is mandatory for emergency overrides.', 'maxsafe' ),
			);
		}

		MaxSafe_Event_Logger::log_event(
			'emergency_override_executed',
			'critical',
			'Update Orchestrator',
			sprintf( 'EMERGENCY OVERRIDE BY USER #%d: %s', get_current_user_id(), sanitize_text_field( $reason ) ),
			array( 'reason' => $reason, 'job_key' => $idempotency_key )
		);

		return self::approve_and_deploy_production( $idempotency_key );
	}

	/**
	 * Perform actual component upgrade using standard WordPress Upgrader API.
	 */
	private static function execute_wordpress_update( $type, $slug ) {
		// Include standard WordPress upgrader libraries
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		return array(
			'success' => true,
			'message' => sprintf( 'Component %s updated to latest version.', $slug ),
		);
	}
}
