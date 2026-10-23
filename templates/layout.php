<?php
/**
 * Master WpSecureOs Layout Wrapper
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inventory     = WpSecureOs_Inventory::get_inventory();
$vulns         = WpSecureOs_Security_Scanner::get_known_vulnerabilities();
$pending_updates = WpSecureOs_Update_Provider::get_updates_with_assessment();
$latest_backup = WpSecureOs_Backup_Adapter::get_latest_verified_backup();

$critical_vulns_count = 0;
foreach ( $vulns as $items ) {
	foreach ( $items as $v ) {
		if ( 'critical' === strtolower( $v['severity'] ) ) {
			$critical_vulns_count++;
		}
	}
}

$updates_count = count( $pending_updates );
?>
<div class="wrap wpsecureos-wrap">

	<!-- WpSecureOs Header -->
	<header class="wpsecureos-header">
		<div class="wpsecureos-brand">
			<img src="<?php echo esc_url( WPSECUREOS_PLUGIN_URL . 'assets/images/logo.svg' ); ?>" alt="WpSecureOs Logo" />
			<div>
				<h1 class="wpsecureos-brand-title">
					WpSecureOs
					<span class="badge-version">v<?php echo esc_html( WPSECUREOS_VERSION ); ?></span>
				</h1>
				<p class="wpsecureos-brand-subtitle">
					<?php esc_html_e( 'Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform', 'wpsecureos' ); ?>
				</p>
			</div>
		</div>

		<div class="wpsecureos-header-actions">
			<div class="wpsecureos-live-status">
				<span class="pulse-dot"></span>
				<span><?php esc_html_e( 'CONTINUOUS VALIDATION: ACTIVE', 'wpsecureos' ); ?></span>
			</div>

			<?php if ( current_user_can( WpSecureOs_Capabilities::CAP_RUN_SCAN ) ) : ?>
				<button type="button" id="btn-run-scan" class="safe-btn safe-btn-dark">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Run Security Scan', 'wpsecureos' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( current_user_can( WpSecureOs_Capabilities::CAP_PREPARE_UPDATE ) ) : ?>
				<button type="button" id="btn-create-backup" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-database-add"></span>
					<?php esc_html_e( 'Take Verified Backup', 'wpsecureos' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</header>

	<!-- Navigation Tabs -->
	<nav class="wpsecureos-nav-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'dashboard' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Overview', 'wpsecureos' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-updates' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'updates' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-update"></span>
			<?php esc_html_e( 'Updates & Safe Deploy', 'wpsecureos' ); ?>
			<?php if ( $updates_count > 0 ) : ?>
				<span class="tab-badge"><?php echo (int) $updates_count; ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-security' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'security' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-shield-alt"></span>
			<?php esc_html_e( 'Security & Integrity', 'wpsecureos' ); ?>
			<?php if ( $critical_vulns_count > 0 ) : ?>
				<span class="tab-badge" style="background:#ef4444; color:#fff;"><?php echo (int) $critical_vulns_count; ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-backups' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'backup-status' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-backup"></span>
			<?php esc_html_e( 'Backups & Recovery', 'wpsecureos' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-staging' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'staging-tests' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-visibility"></span>
			<?php esc_html_e( 'Staging & Visual Tests', 'wpsecureos' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-logs' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'activity-log' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Audit Log', 'wpsecureos' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-settings' ) ); ?>" class="wpsecureos-nav-tab <?php echo ( 'settings' === $current_tab ) ? 'active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'wpsecureos' ); ?>
		</a>
	</nav>

	<!-- Main Tab View Content -->
	<main class="wpsecureos-content">
		<?php
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}
		?>
	</main>

	<!-- Interactive Live Terminal Console -->
	<section class="wpsecureos-panel" style="margin-top: 30px;">
		<div class="wpsecureos-terminal">
			<div class="wpsecureos-terminal-header">
				<div class="terminal-dots">
					<span class="terminal-dot red"></span>
					<span class="terminal-dot yellow"></span>
					<span class="terminal-dot green"></span>
				</div>
				<span>WPSECUREOS_CONTINUOUS_TELEMETRY_CONSOLE // WP-AGENT</span>
				<span class="mono-tag" style="background: rgba(255,255,255,0.05); color: #94a3b8; border-color: rgba(255,255,255,0.1);">LIVE ENGINE</span>
			</div>
			<div id="wpsecureos-live-terminal">
				<div class="terminal-line"><span class="term-time">[<?php echo esc_html( date( 'H:i:s' ) ); ?>]</span> <span class="term-info">WpSecureOs agent initialized. System environment indexed: PHP <?php echo esc_html( PHP_VERSION ); ?>, WP <?php echo esc_html( get_bloginfo( 'version' ) ); ?>.</span></div>
				<div class="terminal-line"><span class="term-time">[<?php echo esc_html( date( 'H:i:s' ) ); ?>]</span> <span class="term-success">Continuous Offensive Validation listener armed. Ready for assessment.</span></div>
				<?php if ( $latest_backup ) : ?>
					<div class="terminal-line"><span class="term-time">[<?php echo esc_html( date( 'H:i:s', strtotime( $latest_backup['created_at'] ) ) ); ?>]</span> <span class="term-success">Verified backup snapshot present: <?php echo esc_html( $latest_backup['backup_name'] ); ?> (SHA-256 confirmed).</span></div>
				<?php endif; ?>
			</div>
		</div>
	</section>

</div>

<!-- Interactive Safe Update Pipeline Modal -->
<div id="modal-safe-pipeline" class="wpsecureos-modal-overlay">
	<div class="wpsecureos-modal-box">
		<div class="wpsecureos-modal-header">
			<h3 class="wpsecureos-modal-title">
				<?php esc_html_e( 'Safe Update Orchestration Pipeline', 'wpsecureos' ); ?>
			</h3>
			<button type="button" class="wpsecureos-modal-close">&times;</button>
		</div>
		<div class="wpsecureos-modal-body">
			<p style="margin-top:0; color:#475569; font-size:13px;">
				<?php esc_html_e( 'Component:', 'wpsecureos' ); ?> <strong id="modal-pipeline-name">WooCommerce</strong> (<span id="modal-pipeline-slug" class="mono-tag"></span>) &rarr;
				<?php esc_html_e( 'Target Version:', 'wpsecureos' ); ?> <span id="modal-pipeline-ver" class="mono-tag"></span>
			</p>
			<input type="hidden" id="modal-pipeline-type" value="plugin" />
			<input type="hidden" id="pipeline-idempotency-key" value="" />

			<!-- State Machine Stepper -->
			<div class="wpsecureos-stepper">
				<div class="wpsecureos-step-item active" id="step-1">
					<div class="wpsecureos-step-circle">1</div>
					<div class="wpsecureos-step-label"><?php esc_html_e( 'Preflight', 'wpsecureos' ); ?></div>
				</div>
				<div class="wpsecureos-step-item" id="step-2">
					<div class="wpsecureos-step-circle">2</div>
					<div class="wpsecureos-step-label"><?php esc_html_e( 'Verified Backup', 'wpsecureos' ); ?></div>
				</div>
				<div class="wpsecureos-step-item" id="step-3">
					<div class="wpsecureos-step-circle">3</div>
					<div class="wpsecureos-step-label"><?php esc_html_e( 'Staging Clone', 'wpsecureos' ); ?></div>
				</div>
				<div class="wpsecureos-step-item" id="step-4">
					<div class="wpsecureos-step-circle">4</div>
					<div class="wpsecureos-step-label"><?php esc_html_e( 'Smoke & Visual Tests', 'wpsecureos' ); ?></div>
				</div>
				<div class="wpsecureos-step-item" id="step-5">
					<div class="wpsecureos-step-circle">5</div>
					<div class="wpsecureos-step-label"><?php esc_html_e( 'Approval Gate', 'wpsecureos' ); ?></div>
				</div>
			</div>

			<!-- Pipeline Results Box -->
			<div id="pipeline-results-box" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-top:20px;">
				<h4 style="margin:0 0 10px 0; font-size:13px; text-transform:uppercase; letter-spacing:0.05em; color:#0f172a;">
					<?php esc_html_e( 'Staging Validation Report', 'wpsecureos' ); ?>
				</h4>
				<p id="pipeline-summary-text" style="font-size:13px; color:#334155; margin-bottom:12px;"></p>
				<table class="wpsecureos-table" style="background:#fff; border-radius:6px; border:1px solid #e2e8f0;">
					<tr>
						<td><strong><?php esc_html_e( 'REST API Smoke Test', 'wpsecureos' ); ?></strong></td>
						<td id="pipe-smoke-rest" class="mono-tag">PASSED</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Error Log Inspection', 'wpsecureos' ); ?></strong></td>
						<td id="pipe-smoke-php" class="mono-tag">CLEAN</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Sandbox Flow', 'wpsecureos' ); ?></strong></td>
						<td id="pipe-smoke-wc" class="mono-tag">PASSED (Mock Gateway)</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="wpsecureos-modal-footer">
			<div id="pipeline-actions-pre">
				<button type="button" class="safe-btn safe-btn-outline btn-modal-cancel"><?php esc_html_e( 'Cancel', 'wpsecureos' ); ?></button>
				<button type="button" id="btn-execute-pipeline" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-controls-play"></span>
					<?php esc_html_e( 'Execute Safe Pipeline', 'wpsecureos' ); ?>
				</button>
			</div>
			<div id="pipeline-actions-post" style="display:none; width:100%; display:flex; justify-content:space-between;">
				<button type="button" id="btn-emergency-override" class="safe-btn safe-btn-danger">
					<?php esc_html_e( 'Emergency Override', 'wpsecureos' ); ?>
				</button>
				<div style="display:flex; gap:10px;">
					<button type="button" class="safe-btn safe-btn-outline btn-modal-cancel"><?php esc_html_e( 'Close', 'wpsecureos' ); ?></button>
					<button type="button" id="btn-approve-production" class="safe-btn safe-btn-primary">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Approve & Deploy to Live', 'wpsecureos' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
