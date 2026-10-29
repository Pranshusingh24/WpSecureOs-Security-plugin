<?php
/**
 * Settings & Multi-Site Central Connector View
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$maxsafe_settings  = get_option( 'maxsafe_settings', get_option( 'wpsecureos_settings', array() ) );
$maxsafe_connector = class_exists( 'MaxSafe_Central_Connector' ) ? MaxSafe_Central_Connector::get_status() : ( class_exists( 'WpSecureOs_Central_Connector' ) ? WpSecureOs_Central_Connector::get_status() : array( 'connected' => false ) );

// Handle central pairing submission
if ( isset( $_POST['maxsafe_enroll_central'] ) || isset( $_POST['wpsecureos_enroll_central'] ) ) {
	if ( function_exists( 'check_admin_referer' ) ) {
		if ( isset( $_POST['maxsafe_enroll_central'] ) ) {
			check_admin_referer( 'maxsafe_central_enroll_nonce' );
		} else {
			check_admin_referer( 'wpsecureos_central_enroll_nonce' );
		}
	}
	$maxsafe_central_url = isset( $_POST['central_dashboard_url'] ) ? sanitize_text_field( wp_unslash( $_POST['central_dashboard_url'] ) ) : '';
	$maxsafe_token       = isset( $_POST['central_pairing_token'] ) ? sanitize_text_field( wp_unslash( $_POST['central_pairing_token'] ) ) : '';

	if ( class_exists( 'MaxSafe_Central_Connector' ) ) {
		$maxsafe_enroll_res = MaxSafe_Central_Connector::enroll_site( $maxsafe_central_url, $maxsafe_token );
	} elseif ( class_exists( 'WpSecureOs_Central_Connector' ) ) {
		$maxsafe_enroll_res = WpSecureOs_Central_Connector::enroll_site( $maxsafe_central_url, $maxsafe_token );
	} else {
		$maxsafe_enroll_res = array( 'success' => false, 'message' => __( 'Connector class not found.', 'maxsafe' ) );
	}

	if ( $maxsafe_enroll_res['success'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $maxsafe_enroll_res['message'] ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $maxsafe_enroll_res['message'] ) . '</p></div>';
	}
	if ( class_exists( 'MaxSafe_Central_Connector' ) ) {
		$maxsafe_connector = MaxSafe_Central_Connector::get_status();
	}
}

$maxsafe_critical_urls_text = ! empty( $maxsafe_settings['critical_urls'] ) ? implode( "\n", (array) $maxsafe_settings['critical_urls'] ) : home_url( '/' );
?>

<div class="maxsafe-two-col wpsecureos-two-col">

	<!-- Left: Core Plugin Configuration -->
	<div class="maxsafe-panel wpsecureos-panel">
		<div class="maxsafe-panel-header wpsecureos-panel-header">
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Platform Configuration & Safety Policies', 'maxsafe' ); ?>
			</h2>
		</div>

		<form method="POST" action="options.php">
			<?php
			settings_fields( 'maxsafe_settings_group' );
			?>

			<!-- Section: Vulnerability Intelligence -->
			<h4 style="margin:0 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '1. Vulnerability Intelligence Feed', 'maxsafe' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Feed Provider Adapter', 'maxsafe' ); ?>
				</label>
				<select name="maxsafe_settings[vulnerability_feed_provider]" style="width:100%; max-width:400px;">
					<option value="wpscan_compatible" <?php selected( $maxsafe_settings['vulnerability_feed_provider'] ?? '', 'wpscan_compatible' ); ?>>
						<?php esc_html_e( 'WPScan / OSV Open Vulnerability Schema (Default)', 'maxsafe' ); ?>
					</option>
					<option value="nist_nvd" <?php selected( $maxsafe_settings['vulnerability_feed_provider'] ?? '', 'nist_nvd' ); ?>>
						<?php esc_html_e( 'NIST National Vulnerability Database (NVD)', 'maxsafe' ); ?>
					</option>
				</select>
				<p style="font-size:12px; color:#64748b; margin:4px 0 0 0;">
					<?php esc_html_e( 'All external threat intelligence feeds are parsed with strict schema validation and treated as untrusted third-party data.', 'maxsafe' ); ?>
				</p>
			</div>

			<div style="margin-bottom:20px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Provider API Key (Optional)', 'maxsafe' ); ?>
				</label>
				<input type="password" name="maxsafe_settings[vulnerability_feed_api_key]" value="<?php echo esc_attr( $maxsafe_settings['vulnerability_feed_api_key'] ?? '' ); ?>" style="width:100%; max-width:400px;" placeholder="<?php esc_attr_e( 'Leave blank to use curated local intelligence', 'maxsafe' ); ?>" />
			</div>

			<!-- Section: Staging & Visual Regression -->
			<h4 style="margin:24px 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '2. Staging & Visual Regression Thresholds', 'maxsafe' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Visual Difference Tolerance Threshold (Ratio: 0.01 to 0.20)', 'maxsafe' ); ?>
				</label>
				<input type="number" step="0.01" min="0.001" max="0.50" name="maxsafe_settings[visual_diff_threshold]" value="<?php echo esc_attr( $maxsafe_settings['visual_diff_threshold'] ?? 0.05 ); ?>" style="width:120px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Default 0.05 (5% change triggers human review gate)', 'maxsafe' ); ?></span>
			</div>

			<div style="margin-bottom:20px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Critical Test URLs (One per line)', 'maxsafe' ); ?>
				</label>
				<textarea name="maxsafe_settings[critical_urls]" rows="4" style="width:100%; max-width:550px; font-family:var(--safe-font-mono); font-size:12px;"><?php echo esc_textarea( $maxsafe_critical_urls_text ); ?></textarea>
				<p style="font-size:12px; color:#64748b; margin:4px 0 0 0;">
					<?php esc_html_e( 'Important storefront URLs to benchmark for visual regressions, HTTP 200 checks, and PHP errors.', 'maxsafe' ); ?>
				</p>
			</div>

			<!-- Section: Backups & Login Protection -->
			<h4 style="margin:24px 0 12px 0; font-size:14px; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
				<?php esc_html_e( '3. Backup Retention & Login Protection', 'maxsafe' ); ?>
			</h4>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Backup Archive Retention Count', 'maxsafe' ); ?>
				</label>
				<input type="number" min="1" max="50" name="maxsafe_settings[backup_retention_count]" value="<?php echo esc_attr( $maxsafe_settings['backup_retention_count'] ?? 5 ); ?>" style="width:100px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Retain the most recent N verified backups.', 'maxsafe' ); ?></span>
			</div>

			<div style="margin-bottom:16px;">
				<label style="display:block; font-weight:600; font-size:13px; margin-bottom:4px;">
					<?php esc_html_e( 'Failed Login Rate Limiter (Attempts before lockout)', 'maxsafe' ); ?>
				</label>
				<input type="number" min="3" max="50" name="maxsafe_settings[rate_limit_login_attempts]" value="<?php echo esc_attr( $maxsafe_settings['rate_limit_login_attempts'] ?? 5 ); ?>" style="width:100px;" />
				<span style="font-size:12px; color:#64748b; margin-left:8px;"><?php esc_html_e( 'Consecutive failures per IP.', 'maxsafe' ); ?></span>
			</div>

			<!-- Section: Security Posture Toggles -->
			<div style="margin-bottom:20px;">
				<label style="display:flex; align-items:center; gap:8px; font-size:13px; margin-bottom:8px;">
					<input type="checkbox" name="maxsafe_settings[enable_login_audit]" value="1" <?php checked( $maxsafe_settings['enable_login_audit'] ?? 1, 1 ); ?> />
					<?php esc_html_e( 'Enable continuous login audit trail and rate-limit tracking', 'maxsafe' ); ?>
				</label>
				<label style="display:flex; align-items:center; gap:8px; font-size:13px;">
					<input type="checkbox" name="maxsafe_settings[enable_uploads_scanner]" value="1" <?php checked( $maxsafe_settings['enable_uploads_scanner'] ?? 1, 1 ); ?> />
					<?php esc_html_e( 'Scan uploads directory for unexpected executable PHP scripts', 'maxsafe' ); ?>
				</label>
			</div>

			<div style="padding-top:14px; border-top:1px solid #f1f5f9;">
				<button type="submit" class="safe-btn safe-btn-primary">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Platform Settings', 'maxsafe' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Right: Central Multi-Site Dashboard Connector -->
	<div class="maxsafe-panel wpsecureos-panel">
		<div class="maxsafe-panel-header wpsecureos-panel-header">
			<h3 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-networking"></span>
				<?php esc_html_e( 'Central Control Plane', 'maxsafe' ); ?>
			</h3>
		</div>

		<p style="font-size:13px; color:#475569; margin:0 0 16px 0;">
			<?php esc_html_e( 'Connect this WordPress site to your central multi-site dashboard (MaxSafe Cloud) for aggregated fleet monitoring.', 'maxsafe' ); ?>
		</p>

		<?php if ( $maxsafe_connector['connected'] ) : ?>
			<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin-bottom:16px;">
				<span class="safe-badge badge-clean" style="margin-bottom:8px;">
					<span class="pulse-dot" style="background:#10b981;"></span>
					<?php esc_html_e( 'ENROLLED & SYNCHRONIZING', 'maxsafe' ); ?>
				</span>
				<div style="font-size:12px; color:#166534; font-family:var(--safe-font-mono); margin-top:6px;">
					<?php echo esc_html( $maxsafe_connector['central_url'] ); ?>
				</div>
				<div style="font-size:11px; color:#64748b; margin-top:4px;">
					<?php printf( /* translators: %s: date */ esc_html__( 'Connected on %s', 'maxsafe' ), esc_html( $maxsafe_connector['enrolled_at'] ) ); ?>
				</div>
			</div>
		<?php else : ?>
			<form method="POST" action="">
				<?php wp_nonce_field( 'maxsafe_central_enroll_nonce' ); ?>
				<div style="margin-bottom:14px;">
					<label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px;">
						<?php esc_html_e( 'Central Dashboard URL', 'maxsafe' ); ?>
					</label>
					<input type="url" name="central_dashboard_url" placeholder="https://control.maxsafe.io" style="width:100%; font-size:12px;" required />
				</div>
				<div style="margin-bottom:16px;">
					<label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px;">
						<?php esc_html_e( 'One-Time Pairing Token', 'maxsafe' ); ?>
					</label>
					<input type="password" name="central_pairing_token" placeholder="sec_pair_xxxxxxxx" style="width:100%; font-size:12px;" required />
				</div>
				<button type="submit" name="maxsafe_enroll_central" class="safe-btn safe-btn-dark" style="width:100%;">
					<span class="dashicons dashicons-admin-links"></span>
					<?php esc_html_e( 'Enroll Site Agent', 'maxsafe' ); ?>
				</button>
			</form>
		<?php endif; ?>

		<div style="margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9;">
			<h4 style="margin:0 0 6px 0; font-size:12px; text-transform:uppercase; color:#64748b;">
				<?php esc_html_e( 'Security Boundary Guarantee', 'maxsafe' ); ?>
			</h4>
			<p style="font-size:11px; color:#64748b; margin:0; line-height:1.5;">
				<?php esc_html_e( 'In accordance with our architecture rules, the Central Dashboard connector can never execute arbitrary PHP or shell commands on your site. All updates use standard WordPress APIs.', 'maxsafe' ); ?>
			</p>
		</div>
	</div>

</div>
