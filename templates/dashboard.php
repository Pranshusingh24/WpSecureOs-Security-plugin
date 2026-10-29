<?php
/**
 * MaxSafe Dashboard Overview Template
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inventory     = MaxSafe_Inventory::get_inventory();
$vulns         = MaxSafe_Security_Scanner::get_known_vulnerabilities();
$updates       = MaxSafe_Update_Provider::get_updates_with_assessment();
$latest_backup = MaxSafe_Backup_Adapter::get_latest_verified_backup();
$php_runtime   = MaxSafe_Php_Advisor::analyze_current_runtime();

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

$integrity = MaxSafe_Integrity_Monitor::run_integrity_check();
?>

<!-- KPI Stat Cards -->
<div class="maxsafe-grid wpsecureos-grid">

	<!-- Card 1: Vulnerabilities -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $critical_vulns > 0 ) ? 'critical' : ( ( $total_vulns > 0 ) ? 'warning' : 'success' ); ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Offensive Validation Signals', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value"><?php echo (int) $total_vulns; ?></div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			if ( $critical_vulns > 0 ) {
				printf( '<strong style="color:#ef4444;">%d Critical CVEs Detected</strong>', (int) $critical_vulns );
			} elseif ( $total_vulns > 0 ) {
				esc_html_e( 'Known advisories matching inventory', 'maxsafe' );
			} else {
				esc_html_e( 'Zero known CVE exposures in active feed', 'maxsafe' );
			}
			?>
		</div>
	</div>

	<!-- Card 2: Core Integrity -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $integrity['is_clean'] ) ? 'success' : 'warning'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Core Checksums Status', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value" style="font-size:22px; text-transform:uppercase;">
			<?php echo esc_html( $integrity['core_status'] ); ?>
		</div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			printf(
				/* translators: 1: Checked files, 2: Modified files */
				esc_html__( '%1$d official files verified. %2$d modified.', 'maxsafe' ),
				(int) $integrity['core_total_files'],
				count( $integrity['core_modified'] )
			);
			?>
		</div>
	</div>

	<!-- Card 3: Pending Updates -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( count( $updates ) > 0 ) ? 'warning' : 'success'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Pending Updates Assessed', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value"><?php echo count( $updates ); ?></div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php esc_html_e( 'Explainable risk evaluated with backup gates', 'maxsafe' ); ?>
		</div>
	</div>

	<!-- Card 4: Backup Health -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $latest_backup && $latest_backup['is_verified'] ) ? 'success' : 'critical'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Restorable Backup Health', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value" style="font-size:20px;">
			<?php echo ( $latest_backup && $latest_backup['is_verified'] ) ? esc_html__( 'VERIFIED', 'maxsafe' ) : esc_html__( 'UNVERIFIED', 'maxsafe' ); ?>
		</div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			if ( $latest_backup ) {
				printf(
					/* translators: %s: Human time diff */
					esc_html__( 'Created %s ago (SHA-256 Validated)', 'maxsafe' ),
					esc_html( human_time_diff( strtotime( $latest_backup['created_at'] ), time() ) )
				);
			} else {
				esc_html_e( 'No verified backup archive on record', 'maxsafe' );
			}
			?>
		</div>
	</div>

</div>

<!-- System Posture & Pending Updates Grid -->
<div class="maxsafe-two-col wpsecureos-two-col">

	<!-- Left: Pending Candidate Updates & Safe Deployment -->
	<div class="maxsafe-panel wpsecureos-panel">
		<div class="maxsafe-panel-header wpsecureos-panel-header">
			<h3 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Update Risk & Controlled Deployment', 'maxsafe' ); ?>
			</h3>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-updates' ) ); ?>" class="safe-btn safe-btn-outline" style="padding:4px 10px; font-size:12px;">
				<?php esc_html_e( 'View All Updates', 'maxsafe' ); ?> &rarr;
			</a>
		</div>

		<?php if ( empty( $updates ) ) : ?>
			<div style="text-align:center; padding:30px 0; color:#64748b;">
				<span class="dashicons dashicons-yes-alt" style="font-size:42px; width:42px; height:42px; color:#10b981; margin-bottom:12px;"></span>
				<p style="margin:0; font-size:14px; font-weight:600; color:#0f172a;">
					<?php esc_html_e( 'All components up to date!', 'maxsafe' ); ?>
				</p>
				<p style="margin:4px 0 0 0; font-size:12px;">
					<?php esc_html_e( 'Continuous validation reports zero pending releases.', 'maxsafe' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="maxsafe-table-responsive wpsecureos-table-responsive">
				<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-dashboard-updates-table" style="width:100%;">
					<thead>
						<tr>
							<th data-priority="1"><?php esc_html_e( 'Component', 'maxsafe' ); ?></th>
							<th data-priority="3"><?php esc_html_e( 'Type', 'maxsafe' ); ?></th>
							<th data-priority="2"><?php esc_html_e( 'Version Jump', 'maxsafe' ); ?></th>
							<th data-priority="2"><?php esc_html_e( 'Risk Level', 'maxsafe' ); ?></th>
							<th data-priority="1"><?php esc_html_e( 'Action', 'maxsafe' ); ?></th>
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
										<?php esc_html_e( 'Safe Deploy', 'maxsafe' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<!-- Right: Environment Posture & PHP Lifecycle -->
	<div class="maxsafe-panel wpsecureos-panel">
		<div class="maxsafe-panel-header wpsecureos-panel-header">
			<h3 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Runtime Posture', 'maxsafe' ); ?>
			</h3>
		</div>

		<div style="margin-bottom:16px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
				<span style="font-size:12px; color:#64748b; font-weight:600;"><?php esc_html_e( 'PHP RUNTIME', 'maxsafe' ); ?></span>
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
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'HTTPS Enforcement', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo $inventory['security_posture']['https'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo $inventory['security_posture']['https'] ? 'ENABLED' : 'DISABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Theme/Plugin File Editor', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo $inventory['security_posture']['disallow_file_edit'] ? 'badge-clean' : 'badge-medium'; ?>">
					<?php echo $inventory['security_posture']['disallow_file_edit'] ? 'DISALLOWED' : 'ENABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Debug Display Exposure', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo ! $inventory['security_posture']['debug_display'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo ! $inventory['security_posture']['debug_display'] ? 'PROTECTED' : 'EXPOSED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Uploads Directory Execution', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo empty( $integrity['suspicious_uploads'] ) ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo empty( $integrity['suspicious_uploads'] ) ? 'CLEAN (0 Scripts)' : count( $integrity['suspicious_uploads'] ) . ' SIGNALS'; ?>
				</span>
			</div>
		</div>
	</div>

</div>
