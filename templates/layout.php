<?php
/**
 * Master MaxSafe Layout Wrapper
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inventory       = MaxSafe_Inventory::get_inventory();
$vulns           = MaxSafe_Security_Scanner::get_known_vulnerabilities();
$pending_updates = MaxSafe_Update_Provider::get_updates_with_assessment();
$latest_backup   = MaxSafe_Backup_Adapter::get_latest_verified_backup();
$license_info    = MaxSafe_License_Manager::get_license();
$plan_badge      = isset( $license_info['plan'] ) ? strtoupper( $license_info['plan'] ) : 'FREE';
$plan_slug       = isset( $license_info['plan'] ) ? strtolower( $license_info['plan'] ) : 'free';

$critical_vulns_count = 0;
foreach ( $vulns as $items ) {
	foreach ( $items as $v ) {
		if ( 'critical' === strtolower( $v['severity'] ) ) {
			$critical_vulns_count++;
		}
	}
}

$updates_count = count( $pending_updates );

$logo_url = file_exists( MAXSAFE_PLUGIN_DIR . 'assets/images/maxsafe-security-logo.png' )
	? MAXSAFE_PLUGIN_URL . 'assets/images/maxsafe-security-logo.png'
	: ( file_exists( MAXSAFE_PLUGIN_DIR . 'assets/images/logo.png' )
		? MAXSAFE_PLUGIN_URL . 'assets/images/logo.png'
		: MAXSAFE_PLUGIN_URL . 'assets/images/wpsecureos-security-logo.png' );
?>
<div class="wrap maxsafe-wrap wpsecureos-wrap">
	<h1 class="wp-heading-inline screen-reader-text"><?php esc_html_e( 'MaxSafe Security Platform', 'maxsafe' ); ?></h1>
	<hr class="wp-header-end">

	<!-- MaxSafe Header -->
	<header class="maxsafe-header wpsecureos-header">
		<div class="maxsafe-brand wpsecureos-brand">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="MaxSafe Logo" />
			<div>
				<div class="maxsafe-brand-title wpsecureos-brand-title" role="heading" aria-level="1">
					MaxSafe
					<span class="badge-version">v<?php echo esc_html( MAXSAFE_VERSION ); ?></span>
					<span class="badge-plan badge-<?php echo esc_attr( $plan_slug ); ?>"><?php echo esc_html( $plan_badge ); ?></span>
				</div>
				<p class="maxsafe-brand-subtitle wpsecureos-brand-subtitle">
					<?php esc_html_e( 'Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform', 'maxsafe' ); ?>
				</p>
			</div>
		</div>

		<div class="maxsafe-header-actions wpsecureos-header-actions">
			<div class="maxsafe-live-status wpsecureos-live-status">
				<span class="pulse-dot"></span>
				<span><?php esc_html_e( 'CONTINUOUS VALIDATION: ACTIVE', 'maxsafe' ); ?></span>
			</div>

			<?php if ( current_user_can( MaxSafe_Capabilities::CAP_RUN_SCAN ) || current_user_can( 'manage_options' ) ) : ?>
				<button type="button" id="btn-run-scan" class="safe-btn safe-btn-dark">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Run Security Scan', 'maxsafe' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( current_user_can( MaxSafe_Capabilities::CAP_PREPARE_UPDATE ) || current_user_can( 'manage_options' ) ) : ?>
				<button type="button" id="btn-create-backup" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-database-add"></span>
					<?php esc_html_e( 'Take Verified Backup', 'maxsafe' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</header>

	<!-- Navigation Tabs -->
	<nav class="maxsafe-nav-tabs wpsecureos-nav-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'dashboard' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Overview', 'maxsafe' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-updates' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'updates' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Updates & Safe Deploy', 'maxsafe' ); ?>
			<?php if ( $updates_count > 0 ) : ?>
				<span class="tab-badge"><?php echo (int) $updates_count; ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-security' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'security' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-shield-alt"></span>
			<?php esc_html_e( 'Security & Integrity', 'maxsafe' ); ?>
			<?php if ( $critical_vulns_count > 0 ) : ?>
				<span class="tab-badge" style="background:#ef4444; color:#fff;"><?php echo (int) $critical_vulns_count; ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-backups' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'backup-status' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-backup"></span>
			<?php esc_html_e( 'Backups & Recovery', 'maxsafe' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-staging' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'staging-tests' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-visibility"></span>
			<?php esc_html_e( 'Staging & Visual Tests', 'maxsafe' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-logs' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'activity-log' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Audit Log', 'maxsafe' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-settings' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'settings' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'maxsafe' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-license' ) ); ?>" class="maxsafe-nav-tab wpsecureos-nav-tab <?php echo ( 'pricing-license' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-money-alt"></span>
			<?php esc_html_e( 'License & Plans', 'maxsafe' ); ?>
			<span class="tab-badge badge-<?php echo esc_attr( $plan_slug ); ?>"><?php echo esc_html( $plan_badge ); ?></span>
		</a>
	</nav>

	<!-- Main Tab View Content -->
	<main class="maxsafe-content wpsecureos-content">
		<?php
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}
		?>
	</main>

	<!-- Interactive Live Terminal Console -->
	<section class="maxsafe-panel wpsecureos-panel" style="margin-top: 30px;">
		<div class="maxsafe-terminal wpsecureos-terminal">
			<div class="maxsafe-terminal-header wpsecureos-terminal-header">
				<div class="terminal-dots">
					<span class="terminal-dot red"></span>
					<span class="terminal-dot yellow"></span>
					<span class="terminal-dot green"></span>
				</div>
				<span>MAXSAFE_CONTINUOUS_TELEMETRY_CONSOLE // SECURITY-AGENT</span>
				<span class="mono-tag" style="background: rgba(255,255,255,0.05); color: #94a3b8; border-color: rgba(255,255,255,0.1);">LIVE ENGINE</span>
			</div>
			<div id="maxsafe-live-terminal" class="maxsafe-live-terminal-box">
				<div class="terminal-line"><span class="term-time">[<?php echo esc_html( gmdate( 'H:i:s' ) ); ?>]</span> <span class="term-info">MaxSafe agent initialized. System environment indexed: PHP <?php echo esc_html( PHP_VERSION ); ?>, WP <?php echo esc_html( get_bloginfo( 'version' ) ); ?>.</span></div>
				<div class="terminal-line"><span class="term-time">[<?php echo esc_html( gmdate( 'H:i:s' ) ); ?>]</span> <span class="term-success">Continuous Offensive Validation listener armed. Ready for assessment.</span></div>
				<?php if ( $latest_backup ) : ?>
					<div class="terminal-line"><span class="term-time">[<?php echo esc_html( gmdate( 'H:i:s', strtotime( $latest_backup['created_at'] ) ) ); ?>]</span> <span class="term-success">Verified backup snapshot present: <?php echo esc_html( $latest_backup['backup_name'] ); ?> (SHA-256 confirmed).</span></div>
				<?php endif; ?>
			</div>
		</div>
	</section>

</div>

<!-- Interactive Safe Update Pipeline Modal -->
<div id="modal-safe-pipeline" class="maxsafe-modal-overlay wpsecureos-modal-overlay">
	<div class="maxsafe-modal-box wpsecureos-modal-box">
		<div class="maxsafe-modal-header wpsecureos-modal-header">
			<h3 class="maxsafe-modal-title wpsecureos-modal-title">
				<?php esc_html_e( 'Safe Update Orchestration Pipeline', 'maxsafe' ); ?>
			</h3>
			<button type="button" class="maxsafe-modal-close wpsecureos-modal-close">&times;</button>
		</div>
		<div class="maxsafe-modal-body wpsecureos-modal-body">
			<p style="margin-top:0; color:#475569; font-size:13px;">
				<?php esc_html_e( 'Component:', 'maxsafe' ); ?> <strong id="modal-pipeline-name">WooCommerce</strong> (<span id="modal-pipeline-slug" class="mono-tag"></span>) &rarr;
				<?php esc_html_e( 'Target Version:', 'maxsafe' ); ?> <span id="modal-pipeline-ver" class="mono-tag"></span>
			</p>
			<input type="hidden" id="modal-pipeline-type" value="plugin" />
			<input type="hidden" id="pipeline-idempotency-key" value="" />

			<!-- State Machine Stepper -->
			<div class="maxsafe-stepper wpsecureos-stepper">
				<div class="maxsafe-step-item wpsecureos-step-item active" id="step-1">
					<div class="maxsafe-step-circle wpsecureos-step-circle">1</div>
					<div class="maxsafe-step-label wpsecureos-step-label"><?php esc_html_e( 'Preflight', 'maxsafe' ); ?></div>
				</div>
				<div class="maxsafe-step-item wpsecureos-step-item" id="step-2">
					<div class="maxsafe-step-circle wpsecureos-step-circle">2</div>
					<div class="maxsafe-step-label wpsecureos-step-label"><?php esc_html_e( 'Verified Backup', 'maxsafe' ); ?></div>
				</div>
				<div class="maxsafe-step-item wpsecureos-step-item" id="step-3">
					<div class="maxsafe-step-circle wpsecureos-step-circle">3</div>
					<div class="maxsafe-step-label wpsecureos-step-label"><?php esc_html_e( 'Staging Clone', 'maxsafe' ); ?></div>
				</div>
				<div class="maxsafe-step-item wpsecureos-step-item" id="step-4">
					<div class="maxsafe-step-circle wpsecureos-step-circle">4</div>
					<div class="maxsafe-step-label wpsecureos-step-label"><?php esc_html_e( 'Smoke & Visual Tests', 'maxsafe' ); ?></div>
				</div>
				<div class="maxsafe-step-item wpsecureos-step-item" id="step-5">
					<div class="maxsafe-step-circle wpsecureos-step-circle">5</div>
					<div class="maxsafe-step-label wpsecureos-step-label"><?php esc_html_e( 'Approval Gate', 'maxsafe' ); ?></div>
				</div>
			</div>

			<!-- Pipeline Results Box -->
			<div id="pipeline-results-box" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-top:20px;">
				<h4 style="margin:0 0 10px 0; font-size:13px; text-transform:uppercase; letter-spacing:0.05em; color:#0f172a;">
					<?php esc_html_e( 'Staging Validation Report', 'maxsafe' ); ?>
				</h4>
				<p id="pipeline-summary-text" style="font-size:13px; color:#334155; margin-bottom:12px;"></p>
				<table class="maxsafe-table wpsecureos-table" style="background:#fff; border-radius:6px; border:1px solid #e2e8f0;">
					<tr>
						<td><strong><?php esc_html_e( 'REST API Smoke Test', 'maxsafe' ); ?></strong></td>
						<td id="pipe-smoke-rest" class="mono-tag">PASSED</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Error Log Inspection', 'maxsafe' ); ?></strong></td>
						<td id="pipe-smoke-php" class="mono-tag">CLEAN</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Sandbox Flow', 'maxsafe' ); ?></strong></td>
						<td id="pipe-smoke-wc" class="mono-tag">PASSED (Mock Gateway)</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="maxsafe-modal-footer wpsecureos-modal-footer">
			<div id="pipeline-actions-pre">
				<button type="button" class="safe-btn safe-btn-outline btn-modal-cancel"><?php esc_html_e( 'Cancel', 'maxsafe' ); ?></button>
				<button type="button" id="btn-execute-pipeline" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-controls-play"></span>
					<?php esc_html_e( 'Execute Safe Pipeline', 'maxsafe' ); ?>
				</button>
			</div>
			<div id="pipeline-actions-post" style="display:none; width:100%; display:flex; justify-content:space-between;">
				<button type="button" id="btn-emergency-override" class="safe-btn safe-btn-danger">
					<?php esc_html_e( 'Emergency Override', 'maxsafe' ); ?>
				</button>
				<div style="display:flex; gap:10px;">
					<button type="button" class="safe-btn safe-btn-outline btn-modal-cancel"><?php esc_html_e( 'Close', 'maxsafe' ); ?></button>
					<button type="button" id="btn-approve-production" class="safe-btn safe-btn-primary">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Approve & Deploy to Live', 'maxsafe' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
