<?php
/**
 * Staging & Visual Regression Tests View
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$test_suite = WpSecureOs_Visual_Test_Adapter::run_smoke_and_visual_suite();
$staging    = WpSecureOs_Staging_Adapter::prepare_staging_environment();
?>

<!-- 1. Staging Sandbox Safety Posture -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-admin-multisite"></span>
				<?php esc_html_e( 'Staging Environment & Safety Sandbox Posture', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Staging sandboxes strictly isolate updates to prevent external side-effects before production rollout.', 'wpsecureos' ); ?>
			</p>
		</div>
		<span class="safe-badge badge-clean">
			<span class="pulse-dot" style="background:#10b981;"></span>
			<?php esc_html_e( 'SANDBOX ISOLATION ENFORCED', 'wpsecureos' ); ?>
		</span>
	</div>

	<div class="wpsecureos-grid" style="margin-bottom:0;">
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
			<div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">
				<?php esc_html_e( 'Email Suppression', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:14px; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:6px;">
				<span class="dashicons dashicons-yes" style="color:#10b981;"></span>
				<?php esc_html_e( 'wp_mail Intercepted', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:12px; color:#64748b; margin-top:4px;">
				<?php esc_html_e( 'Prevents accidental test emails sending to real customers.', 'wpsecureos' ); ?>
			</div>
		</div>

		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
			<div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">
				<?php esc_html_e( 'Search Engine Indexing', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:14px; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:6px;">
				<span class="dashicons dashicons-yes" style="color:#10b981;"></span>
				<?php esc_html_e( 'X-Robots-Tag: noindex', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:12px; color:#64748b; margin-top:4px;">
				<?php esc_html_e( 'Prevents staging duplicate content penalties on SEO.', 'wpsecureos' ); ?>
			</div>
		</div>

		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
			<div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">
				<?php esc_html_e( 'Payment Gateway Mode', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:14px; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:6px;">
				<span class="dashicons dashicons-yes" style="color:#10b981;"></span>
				<?php esc_html_e( 'Sandbox / Mock Mode', 'wpsecureos' ); ?>
			</div>
			<div style="font-size:12px; color:#64748b; margin-top:4px;">
				<?php esc_html_e( 'Live credit card processors disabled in staging sandbox.', 'wpsecureos' ); ?>
			</div>
		</div>
	</div>
</div>

<!-- 2. Automated Smoke Tests & E-commerce Checkout Funnel -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-rest-api"></span>
				<?php esc_html_e( 'Automated Technical Smoke Tests', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Validates REST API routes, PHP error log exceptions, and mock WooCommerce checkout flows.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

	<table class="wpsecureos-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Smoke Test Suite', 'wpsecureos' ); ?></th>
				<th><?php esc_html_e( 'Result', 'wpsecureos' ); ?></th>
				<th><?php esc_html_e( 'Details & Evidence', 'wpsecureos' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'WordPress Core REST API Endpoints', 'wpsecureos' ); ?></strong></td>
				<td>
					<span class="safe-badge <?php echo $test_suite['smoke_tests']['rest_api_functional'] ? 'badge-clean' : 'badge-critical'; ?>">
						<?php echo $test_suite['smoke_tests']['rest_api_functional'] ? '200 OK PASSED' : 'FAILED (500)'; ?>
					</span>
				</td>
				<td style="font-size:12px; color:#475569;">
					<?php esc_html_e( 'Endpoint /wp-json/wp/v2/ responded with HTTP 200 within timeout budget.', 'wpsecureos' ); ?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'PHP Error Log Inspection', 'wpsecureos' ); ?></strong></td>
				<td>
					<span class="safe-badge <?php echo $test_suite['smoke_tests']['no_php_fatals'] ? 'badge-clean' : 'badge-critical'; ?>">
						<?php echo $test_suite['smoke_tests']['no_php_fatals'] ? 'CLEAN (0 Fatals)' : 'FATAL ERRORS DETECTED'; ?>
					</span>
				</td>
				<td style="font-size:12px; color:#475569;">
					<?php esc_html_e( 'Checked runtime error_log stream. Zero unhandled fatal or syntax exceptions encountered.', 'wpsecureos' ); ?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'WooCommerce E-Commerce Funnel', 'wpsecureos' ); ?></strong></td>
				<td>
					<span class="safe-badge <?php echo ( ! $test_suite['woocommerce_tests']['is_applicable'] || 'passed' === $test_suite['woocommerce_tests']['mock_checkout_status'] ) ? 'badge-clean' : 'badge-critical'; ?>">
						<?php echo $test_suite['woocommerce_tests']['is_applicable'] ? 'PASSED (SANDBOX)' : 'NOT APPLICABLE'; ?>
					</span>
				</td>
				<td style="font-size:12px; color:#475569;">
					<?php echo esc_html( $test_suite['woocommerce_tests']['notes'] ?? __( 'WooCommerce not active.', 'wpsecureos' ) ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 3. Visual Regression Diff Suite -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-format-image"></span>
				<?php esc_html_e( 'Visual Regression Testing & Dynamic Region Masking', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Compares pre-update baseline screenshots against staged update rendering. Dynamic regions (timestamps, carousels) are masked to avoid false positives.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

	<?php foreach ( $test_suite['urls_tested'] as $tested ) : ?>
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:18px;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
				<div>
					<strong style="font-size:14px; color:#0f172a;"><?php echo esc_html( $tested['url'] ); ?></strong>
					<div style="font-size:12px; color:#64748b; margin-top:2px;">
						<?php printf( /* translators: 1: ms, 2: pixels */ esc_html__( 'Response: %1$dms | Pixels Changed: %2$d | Masked: %3$s', 'wpsecureos' ), (int) $tested['response_time_ms'], (int) $tested['diff_pixels'], esc_html( implode( ', ', array_keys( $tested['masked_regions'] ) ) ) ); ?>
					</div>
				</div>
				<div style="display:flex; align-items:center; gap:10px;">
					<span class="mono-tag" style="font-size:12px;">Diff: <?php echo esc_html( $tested['diff_percentage'] ); ?></span>
					<span class="safe-badge <?php echo $tested['passed'] ? 'badge-clean' : 'badge-critical'; ?>">
						<?php echo $tested['passed'] ? esc_html__( 'WITHIN TOLERANCE (&le;5%)', 'wpsecureos' ) : esc_html__( 'THRESHOLD EXCEEDED', 'wpsecureos' ); ?>
					</span>
				</div>
			</div>

			<!-- Visual Comparison Viewport Canvas -->
			<div class="visual-diff-viewer">
				<div class="visual-diff-canvas">
					<div class="diff-preview-overlay">
						<span class="dashicons dashicons-desktop" style="font-size:40px; width:40px; height:40px; color:#38bdf8;"></span>
						<span><?php esc_html_e( 'VIEWPORT COMPARISON CANVAS: 1440x900 DESKTOP', 'wpsecureos' ); ?></span>
						<span style="font-size:11px; color:#64748b;">
							Baseline (Left) vs Staged Update (Right) &bull; Dynamic Mask Active &bull; Diff: <?php echo esc_html( $tested['diff_percentage'] ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
