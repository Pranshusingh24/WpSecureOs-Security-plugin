<?php
/**
 * Security & File Integrity View
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrity       = WpSecureOs_Integrity_Monitor::run_integrity_check();
$hardening       = WpSecureOs_Security_Scanner::run_hardening_checks();
$vulnerabilities = WpSecureOs_Security_Scanner::get_known_vulnerabilities();
$php_runtime     = WpSecureOs_Php_Advisor::analyze_current_runtime();
?>

<!-- 1. WordPress Official Core File Checksums -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-media-code"></span>
				<?php esc_html_e( 'Official WordPress Core Checksums Integrity', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Cryptographically compares local WordPress core files against official MD5 checksums published by api.wordpress.org.', 'wpsecureos' ); ?>
			</p>
		</div>
		<div>
			<span class="safe-badge <?php echo $integrity['is_clean'] ? 'badge-clean' : 'badge-critical'; ?>" style="font-size:12px; padding:6px 14px;">
				<?php echo $integrity['is_clean'] ? esc_html__( 'CORE INTEGRITY: CLEAN', 'wpsecureos' ) : esc_html__( 'CORE INTEGRITY: MODIFIED FILES DETECTED', 'wpsecureos' ); ?>
			</span>
		</div>
	</div>

	<?php if ( ! empty( $integrity['core_modified'] ) || ! empty( $integrity['core_missing'] ) ) : ?>
		<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:16px; margin-bottom:20px;">
			<h4 style="margin:0 0 8px 0; color:#b91c1c; font-size:14px;">
				<span class="dashicons dashicons-warning" style="color:#b91c1c;"></span>
				<?php esc_html_e( 'Core Tampering Alert: Unexpected file modifications detected in core directory', 'wpsecureos' ); ?>
			</h4>
			<p style="margin:0 0 10px 0; font-size:13px; color:#7f1d1d;">
				<?php esc_html_e( 'The following files do not match the official WordPress release signatures. This may indicate unauthorized modification, corrupted installation, or web shell infection:', 'wpsecureos' ); ?>
			</p>
			<ul style="margin:0; padding-left:20px; font-family:var(--safe-font-mono); font-size:12px; color:#991b1b;">
				<?php foreach ( $integrity['core_modified'] as $m ) : ?>
					<li>[MODIFIED] <?php echo esc_html( $m ); ?></li>
				<?php endforeach; ?>
				<?php foreach ( $integrity['core_missing'] as $mis ) : ?>
					<li>[MISSING] <?php echo esc_html( $mis ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php else : ?>
		<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin-bottom:14px; display:flex; align-items:center; gap:12px;">
			<span class="dashicons dashicons-yes-alt" style="color:#16a34a; font-size:24px;"></span>
			<span style="font-size:13px; color:#166534;">
				<?php printf( /* translators: %d: count */ esc_html__( 'All %d checked core WordPress files perfectly match official cryptographic release signatures.', 'wpsecureos' ), (int) $integrity['core_total_files'] ); ?>
			</span>
		</div>
	<?php endif; ?>
</div>

<!-- 2. Suspicious Executables in Uploads Directory -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Uploads Directory Executable Signals', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Public upload folders should only contain media files. Executable scripts (.php, .phtml, .cgi) are flagged as signals for human review.', 'wpsecureos' ); ?>
			</p>
		</div>
		<span class="mono-tag" style="background:#f1f5f9; color:#475569;">
			<?php esc_html_e( 'SAFE POLICY: NO AUTO-DELETION', 'wpsecureos' ); ?>
		</span>
	</div>

	<?php if ( empty( $integrity['suspicious_uploads'] ) ) : ?>
		<p style="margin:0; font-size:13px; color:#16a34a; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'No executable PHP, CGI, or shell scripts found in the public wp-content/uploads/ hierarchy.', 'wpsecureos' ); ?>
		</p>
	<?php else : ?>
		<div class="wpsecureos-table-responsive">
			<table class="wpsecureos-table wpsecureos-datatable display responsive" id="wpsecureos-uploads-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Relative Path', 'wpsecureos' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'File Size', 'wpsecureos' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Last Modified', 'wpsecureos' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Recommendation', 'wpsecureos' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $integrity['suspicious_uploads'] as $s_file ) : ?>
						<tr>
							<td class="wpsecureos-code-path">
								<?php echo esc_html( $s_file['path'] ); ?>
							</td>
							<td><span class="mono-tag"><?php echo esc_html( size_format( $s_file['size'] ) ); ?></span></td>
							<td style="font-size:12px; color:#64748b; white-space:nowrap;"><?php echo esc_html( $s_file['modified_at'] ); ?></td>
							<td><span class="safe-badge badge-critical" style="white-space:nowrap;"><?php echo esc_html( $s_file['reason'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- 3. Vulnerability Intelligence Feed -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Vulnerability Intelligence Matching', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Correlates your site inventory against known CVE advisories from reputable intelligence providers.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

	<?php if ( empty( $vulnerabilities ) ) : ?>
		<p style="margin:0; font-size:13px; color:#16a34a; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Zero known vulnerabilities currently reported for your active WordPress core and plugin versions.', 'wpsecureos' ); ?>
		</p>
	<?php else : ?>
		<div class="wpsecureos-table-responsive">
			<table class="wpsecureos-table wpsecureos-datatable display responsive" id="wpsecureos-vulns-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Component', 'wpsecureos' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'CVE / Advisory ID', 'wpsecureos' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Severity', 'wpsecureos' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Affected Range', 'wpsecureos' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Fixed In', 'wpsecureos' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Advisory Reference', 'wpsecureos' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $vulnerabilities as $slug => $items ) : ?>
						<?php foreach ( $items as $advisory ) : ?>
							<?php
							$sev_cls = 'badge-medium';
							if ( 'critical' === strtolower( $advisory['severity'] ) ) $sev_cls = 'badge-critical';
							if ( 'high' === strtolower( $advisory['severity'] ) ) $sev_cls = 'badge-high';
							if ( 'low' === strtolower( $advisory['severity'] ) ) $sev_cls = 'badge-low';
							?>
							<tr>
								<td><strong><?php echo esc_html( $slug ); ?></strong></td>
								<td><span class="mono-tag" style="font-weight:600; color:#0f172a;"><?php echo esc_html( $advisory['id'] ); ?></span></td>
								<td><span class="safe-badge <?php echo esc_attr( $sev_cls ); ?>"><?php echo esc_html( strtoupper( $advisory['severity'] ) ); ?></span></td>
								<td><span class="mono-tag"><?php echo esc_html( $advisory['affected_versions'] ); ?></span></td>
								<td><span class="mono-tag" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0;"><?php echo esc_html( $advisory['fixed_in'] ); ?></span></td>
								<td>
									<?php if ( ! empty( $advisory['references'][0] ) ) : ?>
										<a href="<?php echo esc_url( $advisory['references'][0] ); ?>" target="_blank" rel="noopener noreferrer" style="color:#0ea5e9; text-decoration:none; font-size:12px; white-space:nowrap;">
											<?php esc_html_e( 'View Advisory', 'wpsecureos' ); ?> &rarr;
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
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'WordPress Hardening Posture', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Evaluates critical configuration settings, admin exposure, and security header parameters.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

	<div class="wpsecureos-table-responsive">
		<table class="wpsecureos-table wpsecureos-datatable display responsive" id="wpsecureos-hardening-table" style="width:100%;">
			<thead>
				<tr>
					<th data-priority="1"><?php esc_html_e( 'Security Check', 'wpsecureos' ); ?></th>
					<th data-priority="2"><?php esc_html_e( 'Status', 'wpsecureos' ); ?></th>
					<th data-priority="3"><?php esc_html_e( 'Finding Description', 'wpsecureos' ); ?></th>
					<th data-priority="4"><?php esc_html_e( 'Remediation Guide', 'wpsecureos' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $hardening as $check ) : ?>
					<?php
					$h_badge = 'badge-clean';
					if ( 'warning' === $check['status'] ) $h_badge = 'badge-medium';
					if ( 'failed' === $check['status'] )  $h_badge = 'badge-critical';
					if ( 'info' === $check['status'] )    $h_badge = 'badge-unknown';
					?>
					<tr>
						<td><strong><?php echo esc_html( $check['title'] ); ?></strong></td>
						<td><span class="safe-badge <?php echo esc_attr( $h_badge ); ?>"><?php echo esc_html( strtoupper( $check['status'] ) ); ?></span></td>
						<td style="color:#334155; font-size:12px;"><?php echo esc_html( $check['description'] ); ?></td>
						<td style="font-size:12px; color:#64748b;"><?php echo esc_html( $check['fix_guide'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
