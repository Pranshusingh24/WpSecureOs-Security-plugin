<?php
/**
 * MaxSafe Dashboard Overview Template
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$maxsafe_inventory     = MaxSafe_Inventory::get_inventory();
$maxsafe_vulns         = MaxSafe_Security_Scanner::get_known_vulnerabilities();
$maxsafe_updates       = MaxSafe_Update_Provider::get_updates_with_assessment();
$maxsafe_latest_backup = MaxSafe_Backup_Adapter::get_latest_verified_backup();
$maxsafe_php_runtime   = MaxSafe_Php_Advisor::analyze_current_runtime();

$maxsafe_total_vulns = 0;
$maxsafe_critical_vulns = 0;
foreach ( $maxsafe_vulns as $maxsafe_items ) {
	foreach ( $maxsafe_items as $maxsafe_v ) {
		$maxsafe_total_vulns++;
		if ( 'critical' === strtolower( $maxsafe_v['severity'] ) ) {
			$maxsafe_critical_vulns++;
		}
	}
}

$maxsafe_integrity = MaxSafe_Integrity_Monitor::run_integrity_check();
?>

<!-- KPI Stat Cards -->
<div class="maxsafe-grid wpsecureos-grid">

	<!-- Card 1: Vulnerabilities -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $maxsafe_critical_vulns > 0 ) ? 'critical' : ( ( $maxsafe_total_vulns > 0 ) ? 'warning' : 'success' ); ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Offensive Validation Signals', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value"><?php echo (int) $maxsafe_total_vulns; ?></div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			if ( $maxsafe_critical_vulns > 0 ) {
				printf( '<strong style="color:#ef4444;">%d Critical CVEs Detected</strong>', (int) $maxsafe_critical_vulns );
			} elseif ( $maxsafe_total_vulns > 0 ) {
				esc_html_e( 'Known advisories matching inventory', 'maxsafe' );
			} else {
				esc_html_e( 'Zero known CVE exposures in active feed', 'maxsafe' );
			}
			?>
		</div>
	</div>

	<!-- Card 2: Core Integrity -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $maxsafe_integrity['is_clean'] ) ? 'success' : 'warning'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Core Checksums Status', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value" style="font-size:22px; text-transform:uppercase;">
			<?php echo esc_html( $maxsafe_integrity['core_status'] ); ?>
		</div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			printf(
				/* translators: 1: Checked files, 2: Modified files */
				esc_html__( '%1$d official files verified. %2$d modified.', 'maxsafe' ),
				(int) $maxsafe_integrity['core_total_files'],
				count( $maxsafe_integrity['core_modified'] )
			);
			?>
		</div>
	</div>

	<!-- Card 3: Pending Updates -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( count( $maxsafe_updates ) > 0 ) ? 'warning' : 'success'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Pending Updates Assessed', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value"><?php echo count( $maxsafe_updates ); ?></div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php esc_html_e( 'Explainable risk evaluated with backup gates', 'maxsafe' ); ?>
		</div>
	</div>

	<!-- Card 4: Backup Health -->
	<div class="maxsafe-kpi-card wpsecureos-kpi-card <?php echo ( $maxsafe_latest_backup && $maxsafe_latest_backup['is_verified'] ) ? 'success' : 'critical'; ?>">
		<div class="maxsafe-kpi-title wpsecureos-kpi-title"><?php esc_html_e( 'Restorable Backup Health', 'maxsafe' ); ?></div>
		<div class="maxsafe-kpi-value wpsecureos-kpi-value" style="font-size:20px;">
			<?php echo ( $maxsafe_latest_backup && $maxsafe_latest_backup['is_verified'] ) ? esc_html__( 'VERIFIED', 'maxsafe' ) : esc_html__( 'UNVERIFIED', 'maxsafe' ); ?>
		</div>
		<div class="maxsafe-kpi-desc wpsecureos-kpi-desc">
			<?php
			if ( $maxsafe_latest_backup ) {
				printf(
					/* translators: %s: Human time diff */
					esc_html__( 'Created %s ago (SHA-256 Validated)', 'maxsafe' ),
					esc_html( human_time_diff( strtotime( $maxsafe_latest_backup['created_at'] ), time() ) )
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

		<?php if ( empty( $maxsafe_updates ) ) : ?>
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
						<?php foreach ( array_slice( $maxsafe_updates, 0, 5 ) as $maxsafe_u ) : ?>
							<?php
							$maxsafe_risk = $maxsafe_u['assessment']['overall_risk'];
							$maxsafe_badge_cls = 'badge-medium';
							if ( false !== stripos( $maxsafe_risk, 'high' ) ) {
								$maxsafe_badge_cls = 'badge-critical';
							} elseif ( false !== stripos( $maxsafe_risk, 'low' ) ) {
								$maxsafe_badge_cls = 'badge-clean';
							}
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $maxsafe_u['name'] ); ?></strong>
									<div style="color:#64748b; font-size:11px;"><?php echo esc_html( $maxsafe_u['slug'] ); ?></div>
								</td>
								<td>
									<span class="mono-tag"><?php echo esc_html( strtoupper( $maxsafe_u['type'] ) ); ?></span>
								</td>
								<td>
									<span class="mono-tag"><?php echo esc_html( $maxsafe_u['installed_version'] ); ?></span> &rarr;
									<span class="mono-tag" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;"><?php echo esc_html( $maxsafe_u['new_version'] ); ?></span>
								</td>
								<td>
									<span class="safe-badge <?php echo esc_attr( $maxsafe_badge_cls ); ?>">
										<?php echo esc_html( $maxsafe_risk ); ?>
									</span>
								</td>
								<td>
									<button type="button"
										class="safe-btn safe-btn-primary btn-start-pipeline"
										style="padding:5px 12px; font-size:12px;"
										data-type="<?php echo esc_attr( $maxsafe_u['type'] ); ?>"
										data-slug="<?php echo esc_attr( $maxsafe_u['slug'] ); ?>"
										data-name="<?php echo esc_attr( $maxsafe_u['name'] ); ?>"
										data-version="<?php echo esc_attr( $maxsafe_u['new_version'] ); ?>">
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
				<span class="safe-badge <?php echo ( 'EOL' === $maxsafe_php_runtime['status'] ) ? 'badge-critical' : 'badge-clean'; ?>">
					PHP <?php echo esc_html( PHP_VERSION ); ?> (<?php echo esc_html( $maxsafe_php_runtime['status'] ); ?>)
				</span>
			</div>
			<p style="font-size:12px; color:#475569; margin:0 0 14px 0;">
				<?php echo esc_html( $maxsafe_php_runtime['description'] ); ?>
			</p>
		</div>

		<div style="border-top:1px solid #f1f5f9; padding-top:14px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'HTTPS Enforcement', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo $maxsafe_inventory['security_posture']['https'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo $maxsafe_inventory['security_posture']['https'] ? 'ENABLED' : 'DISABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Theme/Plugin File Editor', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo $maxsafe_inventory['security_posture']['disallow_file_edit'] ? 'badge-clean' : 'badge-medium'; ?>">
					<?php echo $maxsafe_inventory['security_posture']['disallow_file_edit'] ? 'DISALLOWED' : 'ENABLED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Debug Display Exposure', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo ! $maxsafe_inventory['security_posture']['debug_display'] ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo ! $maxsafe_inventory['security_posture']['debug_display'] ? 'PROTECTED' : 'EXPOSED'; ?>
				</span>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center;">
				<span style="font-size:13px; font-weight:500;"><?php esc_html_e( 'Uploads Directory Execution', 'maxsafe' ); ?></span>
				<span class="safe-badge <?php echo empty( $maxsafe_integrity['suspicious_uploads'] ) ? 'badge-clean' : 'badge-critical'; ?>">
					<?php echo empty( $maxsafe_integrity['suspicious_uploads'] ) ? 'CLEAN (0 Scripts)' : count( $maxsafe_integrity['suspicious_uploads'] ) . ' SIGNALS'; ?>
				</span>
			</div>
		</div>
	</div>

</div>
