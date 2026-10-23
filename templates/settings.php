<?php
/**
 * Settings & Multi-Site Central Connector View
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings  = get_option( 'wpsecureos_settings', array() );
$connector = WpSecureOs_Central_Connector::get_status();

// Handle central pairing submission
if ( isset( $_POST['wpsecureos_enroll_central'] ) ) {
	check_admin_referer( 'wpsecureos_central_enroll_nonce' );
	$central_url = isset( $_POST['central_dashboard_url'] ) ? sanitize_text_field( wp_unslash( $_POST['central_dashboard_url'] ) ) : '';
	$token       = isset( $_POST['central_pairing_token'] ) ? sanitize_text_field( wp_unslash( $_POST['central_pairing_token'] ) ) : '';

	$enroll_res = WpSecureOs_Central_Connector::enroll_site( $central_url, $token );
	if ( $enroll_res['success'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $enroll_res['message'] ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $enroll_res['message'] ) . '</p></div>';
	}
	$connector = WpSecureOs_Central_Connector::get_status();
}

$critical_urls_text = ! empty( $settings['critical_urls'] ) ? implode( "\n", (array) $settings['critical_urls'] ) : home_url( '/' );
?>

<div class="wpsecureos-two-col">

	<!-- Left: Core Plugin Configuration -->
	<div class="wpsecureos-panel">
		<div class="wpsecureos-panel-header">
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Platform Configuration & Safety Policies', 'wpsecureos' ); ?>
			</h2>
		</div>

		<form method="POST" action="options.php">
			<?php
			settings_fields( 'wpsecureos_settings_group' );
			?>

			<!-- Section: Vulnerability Intelligence -->
			<h4 style="margin:0 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '1. Vulnerability Intelligence Feed', 'wpsecureos' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Feed Provider Adapter', 'wpsecureos' ); ?>
				</label>
				<select name="wpsecureos_settings[vulnerability_feed_provider]" style="width:100%; max-width:400px;">
					<option value="wpscan_compatible" <?php selected( $settings['vulnerability_feed_provider'] ?? '', 'wpscan_compatible' ); ?>>
						<?php esc_html_e( 'WPScan / OSV Open Vulnerability Schema (Default)', 'wpsecureos' ); ?>
					</option>
					<option value="nist_nvd" <?php selected( $settings['vulnerability_feed_provider'] ?? '', 'nist_nvd' ); ?>>
						<?php esc_html_e( 'NIST National Vulnerability Database (NVD)', 'wpsecureos' ); ?>
					</option>
				</select>
				<p style="font-size:12px; color:#64748b; margin:4px 0 0 0;">
					<?php esc_html_e( 'All external threat intelligence feeds are parsed with strict schema validation and treated as untrusted third-party data.', 'wpsecureos' ); ?>
				</p>
			</div>

			<div style="margin-bottom:20px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Provider API Key (Optional)', 'wpsecureos' ); ?>
				</label>
				<input type="password" name="wpsecureos_settings[vulnerability_feed_api_key]" value="<?php echo esc_attr( $settings['vulnerability_feed_api_key'] ?? '' ); ?>" style="width:100%; max-width:400px;" placeholder="<?php esc_attr_e( 'Leave blank to use curated local intelligence', 'wpsecureos' ); ?>" />
			</div>

			<!-- Section: Staging & Visual Regression -->
			<h4 style="margin:24px 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '2. Staging & Visual Regression Thresholds', 'wpsecureos' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Visual Difference Tolerance Threshold (Ratio: 0.01 to 0.20)', 'wpsecureos' ); ?>
				</label>
				<input type="number" step="0.01" min="0.001" max="0.50" name="wpsecureos_settings[visual_diff_threshold]" value="<?php echo esc_attr( $settings['visual_diff_threshold'] ?? 0.05 ); ?>" style="width:120px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Default 0.05 (5% change triggers human review gate)', 'wpsecureos' ); ?></span>
			</div>

			<div style="margin-bottom:20px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Critical Test URLs (One per line)', 'wpsecureos' ); ?>
				</label>
				<textarea name="wpsecureos_settings[critical_urls]" rows="4" style="width:100%; max-width:550px; font-family:var(--safe-font-mono); font-size:12px;"><?php echo esc_textarea( $critical_urls_text ); ?></textarea>
				<p style="font-size:12px; color:#64748b; margin:4px 0 0 0;">
					<?php esc_html_e( 'Important storefront URLs to benchmark for visual regressions, HTTP 200 checks, and PHP errors.', 'wpsecureos' ); ?>
				</p>
			</div>

			<!-- Section: Backups & Login Protection -->
			<h4 style="margin:24px 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '3. Backup Retention & Login Protection', 'wpsecureos' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Backup Archive Retention Count', 'wpsecureos' ); ?>
				</label>
				<input type="number" min="1" max="50" name="wpsecureos_settings[backup_retention_count]" value="<?php echo esc_attr( $settings['backup_retention_count'] ?? 5 ); ?>" style="width:100px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Retain the most recent N verified backups.', 'wpsecureos' ); ?></span>
			</div>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Failed Login Rate Limiter (Attempts before lockout)', 'wpsecureos' ); ?>
				</label>
				<input type="number" min="3" max="50" name="wpsecureos_settings[rate_limit_login_attempts]" value="<?php echo esc_attr( $settings['rate_limit_login_attempts'] ?? 5 ); ?>" style="width:100px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Consecutive failures per IP.', 'wpsecureos' ); ?></span>
			</div>

			<!-- Section: Security Posture Toggles -->
			<div style="margin-bottom:20px;">
				<label style="display:flex; align-items:center; gap:8px; font-size:13px; margin-bottom:8px;">
					<input type="checkbox" name="wpsecureos_settings[enable_login_audit]" value="1" <?php checked( $settings['enable_login_audit'] ?? 1, 1 ); ?> />
					<?php esc_html_e( 'Enable continuous login audit trail and rate-limit tracking', 'wpsecureos' ); ?>
				</label>
				<label style="display:flex; align-items:center; gap:8px; font-size:13px;">
					<input type="checkbox" name="wpsecureos_settings[enable_uploads_scanner]" value="1" <?php checked( $settings['enable_uploads_scanner'] ?? 1, 1 ); ?> />
					<?php esc_html_e( 'Scan uploads directory for unexpected executable PHP scripts', 'wpsecureos' ); ?>
				</label>
			</div>

			<div style="padding-top:14px; border-top:1px solid #f1f5f9;">
				<button type="submit" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Platform Settings', 'wpsecureos' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Right: Central Multi-Site Dashboard Connector -->
	<div class="wpsecureos-panel">
		<div class="wpsecureos-panel-header">
			<h3 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-networking"></span>
				<?php esc_html_e( 'Central Control Plane', 'wpsecureos' ); ?>
			</h3>
		</div>

		<p style="font-size:13px; color:#475569; margin:0 0 16px 0;">
			<?php esc_html_e( 'Connect this WordPress site to your central multi-site dashboard (WpSecureOs Cloud) for aggregated fleet monitoring.', 'wpsecureos' ); ?>
		</p>

		<?php if ( $connector['connected'] ) : ?>
			<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin-bottom:16px;">
				<span class="safe-badge badge-clean" style="margin-bottom:8px;">
					<span class="pulse-dot" style="background:#10b981;"></span>
					<?php esc_html_e( 'ENROLLED & SYNCHRONIZING', 'wpsecureos' ); ?>
				</span>
				<div style="font-size:12px; color:#166534; font-family:var(--safe-font-mono); margin-top:6px;">
					<?php echo esc_html( $connector['central_url'] ); ?>
				</div>
				<div style="font-size:11px; color:#64748b; margin-top:4px;">
					<?php printf( /* translators: %s: date */ esc_html__( 'Connected on %s', 'wpsecureos' ), esc_html( $connector['enrolled_at'] ) ); ?>
				</div>
			</div>
		<?php else : ?>
			<form method="POST" action="">
				<?php wp_nonce_field( 'wpsecureos_central_enroll_nonce' ); ?>
				<div style="margin-bottom:14px;">
					<label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px;">
						<?php esc_html_e( 'Central Dashboard URL', 'wpsecureos' ); ?>
					</label>
					<input type="url" name="central_dashboard_url" placeholder="https://control.wpsecureos.com" style="width:100%; font-size:12px;" required />
				</div>
				<div style="margin-bottom:16px;">
					<label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px;">
						<?php esc_html_e( 'One-Time Pairing Token', 'wpsecureos' ); ?>
					</label>
					<input type="password" name="central_pairing_token" placeholder="sec_pair_xxxxxxxx" style="width:100%; font-size:12px;" required />
				</div>
				<button type="submit" name="wpsecureos_enroll_central" class="safe-btn safe-btn-dark" style="width:100%;">
					<span class="dashicons dashicons-admin-links"></span>
					<?php esc_html_e( 'Enroll Site Agent', 'wpsecureos' ); ?>
				</button>
			</form>
		<?php endif; ?>

		<div style="margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9;">
			<h4 style="margin:0 0 6px 0; font-size:12px; text-transform:uppercase; color:#64748b;">
				<?php esc_html_e( 'Security Boundary Guarantee', 'wpsecureos' ); ?>
			</h4>
			<p style="font-size:11px; color:#64748b; margin:0; line-height:1.5;">
				<?php esc_html_e( 'In accordance with our architecture rules, the Central Dashboard connector can never execute arbitrary PHP or shell commands on your site. All updates use standard WordPress APIs.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

</div>
