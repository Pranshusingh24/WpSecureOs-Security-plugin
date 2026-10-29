<?php
/**
 * Security & File Integrity View
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$maxsafe_integrity       = MaxSafe_Integrity_Monitor::run_integrity_check();
$maxsafe_hardening       = MaxSafe_Security_Scanner::run_hardening_checks();
$maxsafe_vulnerabilities = MaxSafe_Security_Scanner::get_known_vulnerabilities();
$maxsafe_php_runtime     = MaxSafe_Php_Advisor::analyze_current_runtime();
?>

<!-- 1. WordPress Official Core File Checksums -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-media-code"></span>
				<?php esc_html_e( 'Official WordPress Core Checksums Integrity', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Cryptographically compares local WordPress core files against official MD5 checksums published by api.wordpress.org.', 'maxsafe' ); ?>
			</p>
		</div>
		<div>
			<span class="safe-badge <?php echo $maxsafe_integrity['is_clean'] ? 'badge-clean' : 'badge-critical'; ?>" style="font-size:12px; padding:6px 14px;">
				<?php echo $maxsafe_integrity['is_clean'] ? esc_html__( 'CORE INTEGRITY: CLEAN', 'maxsafe' ) : esc_html__( 'CORE INTEGRITY: MODIFIED FILES DETECTED', 'maxsafe' ); ?>
			</span>
		</div>
	</div>

	<?php if ( ! empty( $maxsafe_integrity['core_modified'] ) || ! empty( $maxsafe_integrity['core_missing'] ) ) : ?>
		<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:16px; margin-bottom:20px;">
			<h4 style="margin:0 0 8px 0; color:#b91c1c; font-size:14px;">
				<span class="dashicons dashicons-warning" style="color:#b91c1c;"></span>
				<?php esc_html_e( 'Core Tampering Alert: Unexpected file modifications detected in core directory', 'maxsafe' ); ?>
			</h4>
			<p style="margin:0 0 10px 0; font-size:13px; color:#7f1d1d;">
				<?php esc_html_e( 'The following files do not match the official WordPress release signatures. This may indicate unauthorized modification, corrupted installation, or web shell infection:', 'maxsafe' ); ?>
			</p>
			<ul style="margin:0; padding-left:20px; font-family:var(--safe-font-mono); font-size:12px; color:#991b1b;">
				<?php foreach ( $maxsafe_integrity['core_modified'] as $maxsafe_m ) : ?>
					<li>[MODIFIED] <?php echo esc_html( $maxsafe_m ); ?></li>
				<?php endforeach; ?>
				<?php foreach ( $maxsafe_integrity['core_missing'] as $maxsafe_mis ) : ?>
					<li>[MISSING] <?php echo esc_html( $maxsafe_mis ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php else : ?>
		<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin-bottom:14px; display:flex; align-items:center; gap:12px;">
			<span class="dashicons dashicons-yes-alt" style="color:#16a34a; font-size:24px;"></span>
			<span style="font-size:13px; color:#166534;">
				<?php printf( /* translators: %d: count */ esc_html__( 'All %d checked core WordPress files perfectly match official cryptographic release signatures.', 'maxsafe' ), (int) $maxsafe_integrity['core_total_files'] ); ?>
			</span>
		</div>
	<?php endif; ?>
</div>

<!-- 2. Suspicious Executables in Uploads Directory -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Uploads Directory Executable Signals', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Public upload folders should only contain media files. Executable scripts (.php, .phtml, .cgi) are flagged as signals for human review.', 'maxsafe' ); ?>
			</p>
		</div>
		<span class="mono-tag" style="background:#f1f5f9; color:#475569;">
			<?php esc_html_e( 'SAFE POLICY: NO AUTO-DELETION', 'maxsafe' ); ?>
		</span>
	</div>

	<?php if ( empty( $maxsafe_integrity['suspicious_uploads'] ) ) : ?>
		<p style="margin:0; font-size:13px; color:#16a34a; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'No executable PHP, CGI, or shell scripts found in the public wp-content/uploads/ hierarchy.', 'maxsafe' ); ?>
		</p>
	<?php else : ?>
		<div class="maxsafe-table-responsive wpsecureos-table-responsive">
			<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-uploads-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Relative Path', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'File Size', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Last Modified', 'maxsafe' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Recommendation', 'maxsafe' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $maxsafe_integrity['suspicious_uploads'] as $maxsafe_s_file ) : ?>
						<tr>
							<td class="maxsafe-code-path wpsecureos-code-path">
								<?php echo esc_html( $maxsafe_s_file['path'] ); ?>
							</td>
							<td><span class="mono-tag"><?php echo esc_html( size_format( $maxsafe_s_file['size'] ) ); ?></span></td>
							<td style="font-size:12px; color:#64748b; white-space:nowrap;"><?php echo esc_html( $maxsafe_s_file['modified_at'] ); ?></td>
							<td><span class="safe-badge badge-critical" style="white-space:nowrap;"><?php echo esc_html( $maxsafe_s_file['reason'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- 3. Vulnerability Intelligence Feed -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Vulnerability Intelligence Matching', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Correlates your site inventory against known CVE advisories from reputable intelligence providers.', 'maxsafe' ); ?>
			</p>
		</div>
	</div>

	<?php if ( empty( $maxsafe_vulnerabilities ) ) : ?>
		<p style="margin:0; font-size:13px; color:#16a34a; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Zero known vulnerabilities currently reported for your active WordPress core and plugin versions.', 'maxsafe' ); ?>
		</p>
	<?php else : ?>
		<div class="maxsafe-table-responsive wpsecureos-table-responsive">
			<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-vulns-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Component', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'CVE / Advisory ID', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Severity', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Affected Range', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Fixed In', 'maxsafe' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Advisory Reference', 'maxsafe' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $maxsafe_vulnerabilities as $maxsafe_slug => $maxsafe_items ) : ?>
						<?php foreach ( $maxsafe_items as $maxsafe_advisory ) : ?>
							<?php
							$maxsafe_sev_cls = 'badge-medium';
							if ( 'critical' === strtolower( $maxsafe_advisory['severity'] ) ) $maxsafe_sev_cls = 'badge-critical';
							if ( 'high' === strtolower( $maxsafe_advisory['severity'] ) )     $maxsafe_sev_cls = 'badge-high';
							if ( 'low' === strtolower( $maxsafe_advisory['severity'] ) )      $maxsafe_sev_cls = 'badge-low';
							?>
							<tr>
								<td><strong><?php echo esc_html( $maxsafe_slug ); ?></strong></td>
								<td><span class="mono-tag" style="font-weight:600; color:#0f172a;"><?php echo esc_html( $maxsafe_advisory['id'] ); ?></span></td>
								<td><span class="safe-badge <?php echo esc_attr( $maxsafe_sev_cls ); ?>"><?php echo esc_html( strtoupper( $maxsafe_advisory['severity'] ) ); ?></span></td>
								<td><span class="mono-tag"><?php echo esc_html( $maxsafe_advisory['affected_versions'] ); ?></span></td>
								<td><span class="mono-tag" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0;"><?php echo esc_html( $maxsafe_advisory['fixed_in'] ); ?></span></td>
								<td>
									<?php if ( ! empty( $maxsafe_advisory['references'][0] ) ) : ?>
										<a href="<?php echo esc_url( $maxsafe_advisory['references'][0] ); ?>" target="_blank" rel="noopener noreferrer" style="color:#0ea5e9; text-decoration:none; font-size:12px; white-space:nowrap;">
											<?php esc_html_e( 'View Advisory', 'maxsafe' ); ?> &rarr;
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- 4. Hardening Checks Checklist -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'WordPress Hardening Posture', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Evaluates critical configuration settings, admin exposure, and security header parameters.', 'maxsafe' ); ?>
			</p>
		</div>
	</div>

	<div class="maxsafe-table-responsive wpsecureos-table-responsive">
		<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-hardening-table" style="width:100%;">
			<thead>
				<tr>
					<th data-priority="1"><?php esc_html_e( 'Security Check', 'maxsafe' ); ?></th>
					<th data-priority="2"><?php esc_html_e( 'Status', 'maxsafe' ); ?></th>
					<th data-priority="3"><?php esc_html_e( 'Finding Description', 'maxsafe' ); ?></th>
					<th data-priority="4"><?php esc_html_e( 'Remediation Guide', 'maxsafe' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $maxsafe_hardening as $maxsafe_check ) : ?>
					<?php
					$maxsafe_h_badge = 'badge-clean';
					if ( 'warning' === $maxsafe_check['status'] ) $maxsafe_h_badge = 'badge-medium';
					if ( 'failed' === $maxsafe_check['status'] )  $maxsafe_h_badge = 'badge-critical';
					if ( 'info' === $maxsafe_check['status'] )    $maxsafe_h_badge = 'badge-unknown';
					?>
					<tr>
						<td><strong><?php echo esc_html( $maxsafe_check['title'] ); ?></strong></td>
						<td><span class="safe-badge <?php echo esc_attr( $maxsafe_h_badge ); ?>"><?php echo esc_html( strtoupper( $maxsafe_check['status'] ) ); ?></span></td>
						<td style="color:#334155; font-size:12px;"><?php echo esc_html( $maxsafe_check['description'] ); ?></td>
						<td style="font-size:12px; color:#64748b;"><?php echo esc_html( $maxsafe_check['fix_guide'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
