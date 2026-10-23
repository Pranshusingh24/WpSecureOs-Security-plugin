<?php
/**
 * WpSecureOs Dashboard Overview Template
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inventory     = WpSecureOs_Inventory::get_inventory();
$vulns         = WpSecureOs_Security_Scanner::get_known_vulnerabilities();
$updates       = WpSecureOs_Update_Provider::get_updates_with_assessment();
$latest_backup = WpSecureOs_Backup_Adapter::get_latest_verified_backup();
$php_runtime   = WpSecureOs_Php_Advisor::analyze_current_runtime();

$total_vulns = 0;
$critical_vulns = 0;
foreach ( $vulns as $items ) {
	foreach ( $items as $v ) {
		$total_vulns++;
		if ( 'critical' === strtolower( $v['severity'] ) ) {
			$critical_vulns++;
		}
	}
}

$integrity = WpSecureOs_Integrity_Monitor::run_integrity_check();
?>

<!-- KPI Stat Cards -->
<div class="wpsecureos-grid">

	<!-- Card 1: Vulnerabilities -->
	<div class="wpsecureos-kpi-card <?php echo ( $critical_vulns > 0 ) ? 'critical' : ( ( $total_vulns > 0 ) ? 'warning' : 'success' ); ?>">
		<div class="wpsecureos-kpi-title"><?php esc_html_e( 'Offensive Validation Signals', 'wpsecureos' ); ?></div>
		<div class="wpsecureos-kpi-value"><?php echo (int) $total_vulns; ?></div>
		<div class="wpsecureos-kpi-desc">
			<?php
			if ( $critical_vulns > 0 ) {
				printf( '<strong style="color:#ef4444;">%d Critical CVEs Detected</strong>', (int) $critical_vulns );
			} elseif ( $total_vulns > 0 ) {
				esc_html_e( 'Known advisories matching inventory', 'wpsecureos' );
			} else {
				esc_html_e( 'Zero known CVE exposures in active feed', 'wpsecureos' );
			}
			?>
		</div>
	</div>

	<!-- Card 2: Core Integrity -->
	<div class="wpsecureos-kpi-card <?php echo ( $integrity['is_clean'] ) ? 'success' : 'warning'; ?>">
		<div class="wpsecureos-kpi-title"><?php esc_html_e( 'Core Checksums Status', 'wpsecureos' ); ?></div>
		<div class="wpsecureos-kpi-value" style="font-size:22px; text-transform:uppercase;">
			<?php echo esc_html( $integrity['core_status'] ); ?>
		</div>
		<div class="wpsecureos-kpi-desc">
			<?php
			printf(
				/* translators: 1: Checked files, 2: Modified files */
				esc_html__( '%1$d official files verified. %2$d modified.', 'wpsecureos' ),
				(int) $integrity['core_total_files'],
				count( $integrity['core_modified'] )
			);
			?>
		</div>
	</div>

	<!-- Card 3: Pending Updates -->
	<div class="wpsecureos-kpi-card <?php echo ( count( $updates ) > 0 ) ? 'warning' : 'success'; ?>">
		<div class="wpsecureos-kpi-title"><?php esc_html_e( 'Pending Updates Assessed', 'wpsecureos' ); ?></div>
		<div class="wpsecureos-kpi-value"><?php echo count( $updates ); ?></div>
		<div class="wpsecureos-kpi-desc">
			<?php esc_html_e( 'Explainable risk evaluated with backup gates', 'wpsecureos' ); ?>
		</div>
	</div>

	<!-- Card 4: Backup Health -->
	<div class="wpsecureos-kpi-card <?php echo ( $latest_backup && $latest_backup['is_verified'] ) ? 'success' : 'critical'; ?>">
		<div class="wpsecureos-kpi-title"><?php esc_html_e( 'Restorable Backup Health', 'wpsecureos' ); ?></div>
		<div class="wpsecureos-kpi-value" style="font-size:20px;">
			<?php echo ( $latest_backup && $latest_backup['is_verified'] ) ? esc_html__( 'VERIFIED', 'wpsecureos' ) : esc_html__( 'UNVERIFIED', 'wpsecureos' ); ?>
		</div>
		<div class="wpsecureos-kpi-desc">
			<?php
			if ( $latest_backup ) {
				printf(
					/* translators: %s: Human time diff */
					esc_html__( 'Created %s ago (SHA-256 Validated)', 'wpsecureos' ),
					esc_html( human_time_diff( strtotime( $latest_backup['created_at'] ), time() ) )
				);
			} else {
				esc_html_e( 'No verified backup archive on record', 'wpsecureos' );
			}
			?>
		</div>
	</div>

</div>

<!-- System Posture & Pending Updates Grid -->
<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px;">

	<!-- Left: Pending Candidate Updates & Safe Deployment -->
	<div class="wpsecureos-panel">
		<div class="wpsecureos-panel-header">
			<h3 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Update Risk & Controlled Deployment', 'wpsecureos' ); ?>
			</h3>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-updates' ) ); ?>" class="safe-btn safe-btn-outline" style="padding:4px 10px; font-size:12px;">
				<?php esc_html_e( 'View All Updates', 'wpsecureos' ); ?> &rarr;
			</a>
		</div>

		<?php if ( empty( $updates ) ) : ?>
			<div style="text-align:center; padding:30px 0; color:#64748b;">
				<span class="dashicons dashicons-yes-alt" style="font-size:42px; width:42px; height:42px; color:#10b981; margin-bottom:12px;"></span>
				<p style="margin:0; font-size:14px; font-weight:600; color:#0f172a;">
					<?php esc_html_e( 'All components up to date!', 'wpsecureos' ); ?>
				</p>
				<p style="margin:4px 0 0 0; font-size:12px;">
					<?php esc_html_e( 'Continuous validation reports zero pending releases.', 'wpsecureos' ); ?>
				</p>
			</div>
		<?php else : ?>
			<table class="wpsecureos-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Component', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Type', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Version Jump', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Risk Level', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wpsecureos' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $updates, 0, 5 ) as $u ) : ?>
						<?php
						$risk = $u['assessment']['overall_risk'];
						$badge_cls = 'badge-medium';
						if ( false !== stripos( $risk, 'high' ) ) {
							$badge_cls = 'badge-critical';
						} elseif ( false !== stripos( $risk, 'low' ) ) {
							$badge_cls = 'badge-clean';
						}
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $u['name'] ); ?></strong>
								<div style="color:#64748b; font-size:11px;"><?php echo esc_html( $u['slug'] ); ?></div>
							</td>
							<td>
								<span class="mono-tag"><?php echo esc_html( strtoupper( $u['type'] ) ); ?></span>
							</td>
							<td>
								<span class="mono-tag"><?php echo esc_html( $u['installed_version'] ); ?></span> &rarr;
								<span class="mono-tag" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;"><?php echo esc_html( $u['new_version'] ); ?></span>
							</td>
							<td>
								<span class="safe-badge <?php echo esc_attr( $badge_cls ); ?>">
									<?php echo esc_html( $risk ); ?>
								</span>
							</td>
							<td>
								<button type="button"
									class="safe-btn safe-btn-primary btn-start-pipeline"
									style="padding:5px 12px; font-size:12px;"
									data-type="<?php echo esc_attr( $u['type'] ); ?>"
									data-slug="<?php echo esc_attr( $u['slug'] ); ?>"
									data-name="<?php echo esc_attr( $u['name'] ); ?>"
									data-version="<?php echo esc_attr( $u['new_version'] ); ?>">
									<?php esc_html_e( 'Safe Deploy', 'wpsecureos' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Right: Environment Posture & PHP Lifecycle -->
	<div class="wpsecureos-panel">
		<div class="wpsecureos-panel-header">
			<h3 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Runtime Posture', 'wpsecureos' ); ?>
			</h3>
		</div>

		<div style="margin-bottom:16px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
				<span style="font-size:12px; color:#64748b; font-weight:600;"><?php esc_html_e( 'PHP RUNTIME', 'wpsecureos' ); ?></span>
				<span class="safe-badge <?php echo ( 'EOL' === $php_runtime['status'] ) ? 'badge-critical' : 'badge-clean'; ?>">
					PHP <?php echo esc_html( PHP_VERSION ); ?> (<?php echo esc_html( $php_runtime['status'] ); ?>)
				</span>
			</div>
			<p style="font-size:12px; color:#475569; margin:0 0 14px 0;">
				<?php echo esc_html( $php_runtime['description'] ); ?>
			</p>
		</div>

		<div style="border-top:1px solid #f1f5f9; padding-top:14px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'HTTPS Enforcement', 'wpsecureos' ); ?></span>
				<span class="safe-badge <?php echo $inventory['security_posture']['https'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo $inventory['security_posture']['https'] ? 'ENABLED' : 'DISABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Theme/Plugin File Editor', 'wpsecureos' ); ?></span>
				<span class="safe-badge <?php echo $inventory['security_posture']['disallow_file_edit'] ? 'badge-clean' : 'badge-medium'; ?>">
					<?php echo $inventory['security_posture']['disallow_file_edit'] ? 'DISALLOWED' : 'ENABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Debug Display Exposure', 'wpsecureos' ); ?></span>
				<span class="safe-badge <?php echo ! $inventory['security_posture']['debug_display'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo ! $inventory['security_posture']['debug_display'] ? 'PROTECTED' : 'EXPOSED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Uploads Directory Execution', 'wpsecureos' ); ?></span>
				<span class="safe-badge <?php echo empty( $integrity['suspicious_uploads'] ) ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo empty( $integrity['suspicious_uploads'] ) ? 'CLEAN (0 Scripts)' : count( $integrity['suspicious_uploads'] ) . ' SIGNALS'; ?>
				</span>
			</div>
		</div>
	</div>

</div>
