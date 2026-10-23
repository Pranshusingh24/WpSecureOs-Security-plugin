<?php
/**
 * WpSecureOs Automated Test Harness & Test Runner
 *
 * Runs deterministic unit, integration, and security verification tests directly via PHP CLI.
 * Usage: php tests/run-tests.php
 *
 * @package WpSecureOs
 */

define( 'WPSECUREOS_TESTING', true );
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPSECUREOS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WPSECUREOS_VERSION', '1.0.0' );

if ( ! defined( 'OBJECT' ) ) define( 'OBJECT', 'OBJECT' );
if ( ! defined( 'ARRAY_A' ) ) define( 'ARRAY_A', 'ARRAY_A' );
if ( ! defined( 'ARRAY_N' ) ) define( 'ARRAY_N', 'ARRAY_N' );

// Minimal WordPress Environment Stubs for standalone CLI verification
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0 ) { return json_encode( $data, $options ); }
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) { return 'https://example.com' . $path; }
}
if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '' ) { return 'https://example.com' . $path; }
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) { return date( 'Y-m-d H:i:s' ); }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show ) { return '6.4.2'; }
}
if ( ! function_exists( 'get_stylesheet' ) ) {
	function get_stylesheet() { return 'twentytwentyfour'; }
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $opt, $default = false ) {
		global $mock_options;
		return isset( $mock_options[ $opt ] ) ? $mock_options[ $opt ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $opt, $val ) {
		global $mock_options;
		$mock_options[ $opt ] = $val;
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $t ) {
		global $mock_transients;
		return isset( $mock_transients[ $t ] ) ? $mock_transients[ $t ] : false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $t, $val, $exp = 0 ) {
		global $mock_transients;
		$mock_transients[ $t ] = $val;
		return true;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		global $mock_current_user_caps;
		return ! empty( $mock_current_user_caps[ $cap ] );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return 1; }
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		$tmp = sys_get_temp_dir() . '/wpsecureos_test_uploads';
		if ( ! file_exists( $tmp ) ) @mkdir( $tmp, 0777, true );
		return array( 'basedir' => $tmp, 'baseurl' => 'https://example.com/wp-content/uploads' );
	}
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $name ) { return preg_replace( '/[^a-zA-Z0-9_\.-]/', '', $name ); }
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) { return @mkdir( $target, 0777, true ); }
}
if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) { return 'https://example.com/wp-json/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		return array( 'response' => array( 'code' => 200 ), 'body' => '{"status":"ok"}' );
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return false; }
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) { return 200; }
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) { return isset( $response['body'] ) ? $response['body'] : ''; }
}
if ( ! function_exists( 'size_format' ) ) {
	function size_format( $bytes ) { return round( $bytes / 1024, 2 ) . ' KB'; }
}

// Mock WordPress Database
class MockWPDB {
	public $prefix = 'wp_';
	public $inserted = array();
	public $insert_id = 1;
	public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4'; }
	public function insert( $table, $data, $format ) {
		$this->inserted[] = array( 'table' => $table, 'data' => $data );
		return 1;
	}
	public function replace( $table, $data, $format ) { return 1; }
	public function update( $table, $data, $where, $format, $where_format ) { return 1; }
	public function get_row( $sql, $output = OBJECT ) { return null; }
	public function get_results( $sql, $output = OBJECT ) { return array(); }
	public function get_col( $query = '', $x = 0 ) { return array( 'wp_options', 'wp_posts' ); }
	public function get_var( $sql ) { return 0; }
	public function db_version() { return '8.0.35'; }
	public function esc_like( $text ) { return addcslashes( $text, '_%\\' ); }
	public function prepare( $query, ...$args ) { return vsprintf( str_replace( array( '%s', '%d', '%f' ), array( "'%s'", '%d', '%f' ), $query ), is_array( $args[0] ) ? $args[0] : $args ); }
}
global $wpdb;
$wpdb = new MockWPDB();

// Load plugin classes
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-capabilities.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-risk-assessor.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-inventory.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-security-scanner.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-integrity-monitor.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-event-logger.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-backup-adapter.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-staging-adapter.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-visual-test-adapter.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-test-runner.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-php-advisor.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-central-connector.php';
require_once WPSECUREOS_PLUGIN_DIR . 'includes/class-rest-controller.php';

// Test Runner Class
class WpSecureOs_Test_Harness {
	private $passed = 0;
	private $failed = 0;

	public function run() {
		echo "\n=======================================================\n";
		echo "  WPSECUREOS AUTOMATED TEST SUITE & QA VERIFICATION\n";
		echo "=======================================================\n\n";

		$this->test_capabilities();
		$this->test_risk_assessor();
		$this->test_vulnerability_version_matching();
		$this->test_php_lifecycle_advisor();
		$this->test_secret_redaction();
		$this->test_backup_archive_integrity();
		$this->test_staging_state_machine_approval_gate();
		$this->test_rest_security_permissions();

		echo "\n-------------------------------------------------------\n";
		echo "TEST RESULTS SUMMARY:\n";
		echo "Passed: " . $this->passed . "\n";
		echo "Failed: " . $this->failed . "\n";
		echo "-------------------------------------------------------\n";

		if ( $this->failed === 0 ) {
			echo "ALL TESTS PASSED SUCCESSFULLY! (100% Green)\n\n";
			exit( 0 );
		} else {
			echo "CRITICAL: Some tests failed. Review issues above.\n\n";
			exit( 1 );
		}
	}

	private function assert( $condition, $test_name, $details = '' ) {
		if ( $condition ) {
			echo "[PASS] " . $test_name . "\n";
			$this->passed++;
		} else {
			echo "[FAIL] " . $test_name . ( $details ? " - " . $details : "" ) . "\n";
			$this->failed++;
		}
	}

	// 1. Capabilities Tests
	private function test_capabilities() {
		echo "\n--- Suite 1: Capabilities & Roles Enforcement ---\n";
		$caps = WpSecureOs_Capabilities::get_all_capabilities();
		$this->assert( count( $caps ) === 7, "Registered exactly 7 granular capabilities" );
		$this->assert( isset( $caps['wpsecureos_approve_production'] ), "wpsecureos_approve_production capability exists" );
		$this->assert( isset( $caps['wpsecureos_manage_recovery'] ), "wpsecureos_manage_recovery capability exists" );
	}

	// 2. Risk Assessor Tests
	private function test_risk_assessor() {
		echo "\n--- Suite 2: Explainable Risk Assessor & Evidence Engine ---\n";

		// Test A: Critical CVE Urgency
		$comp_cve = array(
			'slug'              => 'contact-form-7',
			'installed_version' => '5.3.1',
			'new_version'       => '5.3.2',
			'is_active'         => true,
		);
		$context_cve = array(
			'vulnerabilities' => array(
				'contact-form-7' => array(
					array( 'id' => 'CVE-2020-35489', 'severity' => 'Critical' )
				)
			),
			'recent_verified_backup_hours' => 2,
			'staging_tests_passed'         => true,
		);
		$res_cve = WpSecureOs_Risk_Assessor::assess_component( $comp_cve, $context_cve );
		$this->assert( $res_cve['security_urgency'] === 'Critical', "Urgency is marked Critical when high/critical CVE is reported" );
		$this->assert( $res_cve['deployment_confidence'] === 'High', "Confidence is High after staging tests and recent verified backup" );
		$this->assert( ! empty( $res_cve['reasons'] ), "Contributing reasons are explicitly detailed" );

		// Test B: Missing Backup Gate
		$context_no_backup = array(
			'recent_verified_backup_hours' => null,
			'staging_tests_passed'         => true,
		);
		$res_no_backup = WpSecureOs_Risk_Assessor::assess_component( $comp_cve, $context_no_backup );
		$this->assert( false !== strpos( $res_no_backup['next_action'], 'backup' ), "Recommends verified backup creation when no recent backup exists" );

		// Test C: Staging Failure Blocks High Confidence
		$context_failed = array(
			'recent_verified_backup_hours' => 1,
			'staging_tests_failed'         => true,
		);
		$res_failed = WpSecureOs_Risk_Assessor::assess_component( $comp_cve, $context_failed );
		$this->assert( $res_failed['deployment_confidence'] === 'Low', "Confidence is Low when staging regression is detected" );
		$this->assert( $res_failed['overall_risk'] === 'High Risk', "Overall Risk is High Risk when staging tests fail" );
	}

	// 3. Vulnerability Version Matching
	private function test_vulnerability_version_matching() {
		echo "\n--- Suite 3: Vulnerability Version Constraint Matching ---\n";
		$this->assert( WpSecureOs_Security_Scanner::version_is_affected( '6.2.1', '<= 6.2.2' ) === true, "Version 6.2.1 matches <= 6.2.2" );
		$this->assert( WpSecureOs_Security_Scanner::version_is_affected( '6.2.3', '<= 6.2.2' ) === false, "Version 6.2.3 does not match <= 6.2.2" );
		$this->assert( WpSecureOs_Security_Scanner::version_is_affected( '3.17.0', '< 3.18.1' ) === true, "Version 3.17.0 matches < 3.18.1" );
		$this->assert( WpSecureOs_Security_Scanner::version_is_affected( '3.18.1', '< 3.18.1' ) === false, "Version 3.18.1 does not match < 3.18.1" );
	}

	// 4. PHP Advisor Tests
	private function test_php_lifecycle_advisor() {
		echo "\n--- Suite 4: PHP Lifecycle & Hosting Advisor ---\n";
		$matrix = WpSecureOs_Php_Advisor::get_php_lifecycle_matrix();
		$this->assert( isset( $matrix['7.4'] ) && $matrix['7.4']['status'] === 'EOL', "PHP 7.4 marked EOL" );
		$this->assert( isset( $matrix['8.3'] ) && $matrix['8.3']['status'] === 'active', "PHP 8.3 marked Active Support" );

		$runbooks = WpSecureOs_Php_Advisor::get_hosting_runbooks();
		$this->assert( isset( $runbooks['cpanel'] ) && isset( $runbooks['cloudways'] ), "Hosting runbooks include cPanel and Cloudways" );
	}

	// 5. Secret Redaction Tests
	private function test_secret_redaction() {
		echo "\n--- Suite 5: Audit Log Secret & Credential Redaction ---\n";
		$sample_data = array(
			'user'        => 'admin',
			'password'    => 'SuperSecret123!',
			'auth_key'    => 'hash_string_abc',
			'api_token'   => 'token_live_xyz',
			'server_name' => 'prod-cluster-1',
		);
		$redacted = WpSecureOs_Event_Logger::redact_secrets( $sample_data );
		$this->assert( $redacted['password'] === '[REDACTED]', "Password field was redacted" );
		$this->assert( $redacted['auth_key'] === '[REDACTED]', "Auth key field was redacted" );
		$this->assert( $redacted['api_token'] === '[REDACTED]', "API token field was redacted" );
		$this->assert( $redacted['server_name'] === 'prod-cluster-1', "Non-sensitive data preserved" );
	}

	// 6. Backup Archive Integrity Tests
	private function test_backup_archive_integrity() {
		echo "\n--- Suite 6: Backup Archive Validation & Consistency Checks ---\n";
		$tmp_zip = sys_get_temp_dir() . '/test_backup_' . time() . '.zip';

		// Create test valid zip
		$zip = new ZipArchive();
		$zip->open( $tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		$zip->addFromString( 'wpsecureos_manifest.json', json_encode( array( 'site_url' => 'https://example.com' ) ) );
		$zip->addFromString( 'database_dump.sql', '-- SQL schema' );
		$zip->close();

		$valid_result = WpSecureOs_Backup_Adapter::verify_archive_integrity( $tmp_zip );
		$this->assert( $valid_result['is_valid'] === true, "Valid backup zip with manifest and SQL passes integrity check" );

		// Create corrupt zip without manifest
		$tmp_bad_zip = sys_get_temp_dir() . '/test_bad_backup_' . time() . '.zip';
		$zip2 = new ZipArchive();
		$zip2->open( $tmp_bad_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		$zip2->addFromString( 'random_file.txt', 'test' );
		$zip2->close();

		$bad_result = WpSecureOs_Backup_Adapter::verify_archive_integrity( $tmp_bad_zip );
		$this->assert( $bad_result['is_valid'] === false, "Incomplete archive without SQL or manifest is flagged invalid" );

		@unlink( $tmp_zip );
		@unlink( $tmp_bad_zip );
	}

	// 7. Staging State Machine & Approval Gate
	private function test_staging_state_machine_approval_gate() {
		echo "\n--- Suite 7: Staging Pipeline State Machine & Approval Gates ---\n";
		global $mock_current_user_caps;

		// Without capability: must reject
		$mock_current_user_caps = array();
		$res_reject = WpSecureOs_Test_Runner::start_safe_update_pipeline( 'plugin', 'woocommerce', '8.5.0' );
		$this->assert( $res_reject['success'] === false, "Unprivileged user cannot start safe update pipeline" );

		// With capability: must succeed through gates
		$mock_current_user_caps = array(
			WpSecureOs_Capabilities::CAP_PREPARE_UPDATE     => true,
			WpSecureOs_Capabilities::CAP_APPROVE_PRODUCTION => true,
		);
		$res_ok = WpSecureOs_Test_Runner::start_safe_update_pipeline( 'plugin', 'woocommerce', '8.5.0' );
		$this->assert( $res_ok['success'] === true, "Authorized user initiates pipeline" );
		$this->assert( $res_ok['state'] === WpSecureOs_Test_Runner::STATE_APPROVAL_REQUIRED, "Pipeline halts at APPROVAL_REQUIRED gate" );
	}

	// 8. REST Security Permissions
	private function test_rest_security_permissions() {
		echo "\n--- Suite 8: REST API Permission Callbacks ---\n";
		global $mock_current_user_caps;

		$req = new stdClass();

		// Subscriber with no caps
		$mock_current_user_caps = array();
		$this->assert( WpSecureOs_Rest_Controller::check_view_permission( $req ) === false, "Subscriber denied GET /status" );
		$this->assert( WpSecureOs_Rest_Controller::check_scan_permission( $req ) === false, "Subscriber denied POST /scan" );
		$this->assert( WpSecureOs_Rest_Controller::check_approve_permission( $req ) === false, "Subscriber denied POST /update/approve" );

		// Admin with all caps
		$mock_current_user_caps = array(
			WpSecureOs_Capabilities::CAP_VIEW_DASHBOARD     => true,
			WpSecureOs_Capabilities::CAP_RUN_SCAN           => true,
			WpSecureOs_Capabilities::CAP_APPROVE_PRODUCTION => true,
		);
		$this->assert( WpSecureOs_Rest_Controller::check_view_permission( $req ) === true, "Authorized admin allowed GET /status" );
		$this->assert( WpSecureOs_Rest_Controller::check_scan_permission( $req ) === true, "Authorized admin allowed POST /scan" );
		$this->assert( WpSecureOs_Rest_Controller::check_approve_permission( $req ) === true, "Authorized admin allowed POST /update/approve" );
	}
}

// Execute tests
$harness = new WpSecureOs_Test_Harness();
$harness->run();
