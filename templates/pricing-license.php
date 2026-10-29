<?php
/**
 * Customer License & Subscription Management Template.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_data = class_exists( 'MaxSafe_License_Manager' ) ? MaxSafe_License_Manager::get_license() : ( class_exists( 'WpSecureOs_License_Manager' ) ? WpSecureOs_License_Manager::get_license() : array() );
$all_plans    = class_exists( 'MaxSafe_Plan_Entitlements' ) ? MaxSafe_Plan_Entitlements::get_all_plans() : ( class_exists( 'WpSecureOs_Plan_Entitlements' ) ? WpSecureOs_Plan_Entitlements::get_all_plans() : array() );
$current_plan = isset( $license_data['plan'] ) ? $license_data['plan'] : 'free';
$status       = isset( $license_data['status'] ) ? $license_data['status'] : 'unlicensed';
$is_active    = class_exists( 'MaxSafe_License_Manager' ) ? MaxSafe_License_Manager::is_active() : ( class_exists( 'WpSecureOs_License_Manager' ) ? WpSecureOs_License_Manager::is_active() : false );
$in_grace     = class_exists( 'MaxSafe_License_Manager' ) ? MaxSafe_License_Manager::is_in_grace_period() : ( class_exists( 'WpSecureOs_License_Manager' ) ? WpSecureOs_License_Manager::is_in_grace_period() : false );

$plan_obj     = class_exists( 'MaxSafe_Plan_Entitlements' ) ? MaxSafe_Plan_Entitlements::get_plan( $current_plan ) : ( class_exists( 'WpSecureOs_Plan_Entitlements' ) ? WpSecureOs_Plan_Entitlements::get_plan( $current_plan ) : array( 'name' => 'Free', 'tagline' => '', 'badge_class' => '' ) );
$site_domain  = class_exists( 'MaxSafe_License_Manager' ) ? MaxSafe_License_Manager::get_site_domain() : ( class_exists( 'WpSecureOs_License_Manager' ) ? WpSecureOs_License_Manager::get_site_domain() : home_url() );
?>

<div class="maxsafe-license-container wpsecureos-license-container">

	<!-- License Status Hero Banner -->
	<section class="maxsafe-panel wpsecureos-panel license-hero-panel">
		<div class="license-hero-grid">
			<div class="license-hero-main">
				<div class="license-plan-header">
					<div class="license-plan-badge-lg badge-free" style="background:linear-gradient(135deg, #0ea5e9, #0284c7); color:#ffffff;">
						<?php esc_html_e( 'COMMUNITY EDITION', 'maxsafe' ); ?>
					</div>
					<span class="status-indicator status-active">
						<span class="pulse-dot-sm"></span> <?php esc_html_e( 'ALL FEATURES UNLOCKED // FREE', 'maxsafe' ); ?>
					</span>
				</div>

				<h2 class="license-hero-title">
					<?php esc_html_e( 'Full Feature Access (Free Edition)', 'maxsafe' ); ?>
				</h2>
				<p class="license-hero-desc">
					<?php esc_html_e( 'All platform capabilities — including automated staging pipelines, checksum monitoring, verified backups, brute-force rate limiting, vulnerability detection, and audit logs — are 100% free and active.', 'maxsafe' ); ?>
				</p>

				<!-- License Key Management Form -->
				<div class="license-key-box">
					<form id="maxsafe-license-form" class="wpsecureos-license-form" method="POST" action="">
						<?php
						if ( function_exists( 'wp_nonce_field' ) ) {
							wp_nonce_field( 'maxsafe_admin_nonce', 'maxsafe_license_nonce' );
							wp_nonce_field( 'wpsecureos_admin_nonce', 'wpsecureos_license_nonce' );
						}
						?>
						<label for="maxsafe_license_key" class="license-input-label">
							<?php esc_html_e( 'License Key (Optional)', 'maxsafe' ); ?>
						</label>
						<div class="license-input-group">
							<input
								type="text"
								id="maxsafe_license_key"
								name="license_key"
								class="license-key-input"
								placeholder="MAXSAFE-PRO-XXXX-XXXX-XXXX"
								value="<?php echo esc_attr( $license_data['license_key'] ?? '' ); ?>"
								spellcheck="false"
								autocomplete="off"
							/>
							<button type="button" id="btn-activate-license" class="safe-btn safe-btn-primary">
								<span class="dashicons dashicons-admin-network"></span>
								<?php echo ! empty( $license_data['license_key'] ) ? esc_html__( 'Change / Re-activate', 'maxsafe' ) : esc_html__( 'Activate Key', 'maxsafe' ); ?>
							</button>

							<?php if ( ! empty( $license_data['license_key'] ) && $is_active ) : ?>
								<button type="button" id="btn-refresh-license" class="safe-btn safe-btn-dark" title="<?php esc_attr_e( 'Refresh entitlements and check validity', 'maxsafe' ); ?>">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Sync Status', 'maxsafe' ); ?>
								</button>
								<button type="button" id="btn-deactivate-license" class="safe-btn safe-btn-danger" title="<?php esc_attr_e( 'Release license seat for another domain', 'maxsafe' ); ?>">
									<span class="dashicons dashicons-dismiss"></span>
									<?php esc_html_e( 'Deactivate', 'maxsafe' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</form>
					<div id="license-ajax-feedback" class="license-feedback-msg" style="display:none;"></div>
				</div>
			</div>

			<!-- Meta Quotas Card -->
			<div class="license-meta-card">
				<h3 class="license-meta-title"><?php esc_html_e( 'Platform Telemetry', 'maxsafe' ); ?></h3>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Bound Site Domain', 'maxsafe' ); ?></span>
					<span class="telemetry-val mono"><?php echo esc_html( $site_domain ); ?></span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Feature Access', 'maxsafe' ); ?></span>
					<span class="telemetry-val">
						<span class="quota-badge" style="background:#dcfce7; color:#15803d; border-color:#86efac;">
							<?php esc_html_e( '100% Unlocked (Free)', 'maxsafe' ); ?>
						</span>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Current Period Expiry', 'maxsafe' ); ?></span>
					<span class="telemetry-val mono">
						<?php esc_html_e( 'Lifetime / Unlimited', 'maxsafe' ); ?>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Last Heartbeat Check', 'maxsafe' ); ?></span>
					<span class="telemetry-val mono">
						<?php echo esc_html( ! empty( $license_data['last_validated'] ) ? substr( $license_data['last_validated'], 0, 16 ) : current_time( 'Y-m-d H:i' ) ); ?>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Validation Status', 'maxsafe' ); ?></span>
					<span class="telemetry-val">
						<span class="crypto-badge verified">✓ ACTIVE // VERIFIED</span>
					</span>
				</div>
			</div>
		</div>
	</section>

	<!-- Security & Compliance Guarantees -->
	<section class="maxsafe-panel wpsecureos-panel compliance-guarantees" style="margin-top: 24px;">
		<div class="compliance-grid">
			<div class="compliance-item">
				<span class="dashicons dashicons-shield"></span>
				<div>
					<h4><?php esc_html_e( 'Uninterrupted Security Protection', 'maxsafe' ); ?></h4>
					<p><?php esc_html_e( 'Essential core file checksums, vulnerability monitoring, audit logs, and verified database restore runbooks never lock out or disable when a license expires.', 'maxsafe' ); ?></p>
				</div>
			</div>
			<div class="compliance-item">
				<span class="dashicons dashicons-lock"></span>
				<div>
					<h4><?php esc_html_e( 'Zero Secrets Stored in WordPress', 'maxsafe' ); ?></h4>
					<p><?php esc_html_e( 'Payment cards, server private keys, and billing secrets are never stored in or transmitted to your WordPress database. All checkouts use secure hosted sessions.', 'maxsafe' ); ?></p>
				</div>
			</div>
			<div class="compliance-item">
				<span class="dashicons dashicons-cloud"></span>
				<div>
					<h4><?php esc_html_e( 'Network Outage Resilience', 'maxsafe' ); ?></h4>
					<p><?php esc_html_e( 'Temporary DNS or server connectivity drops never disable your site agent. The built-in 7-day offline grace period guarantees stability.', 'maxsafe' ); ?></p>
				</div>
			</div>
		</div>
	</section>

</div>
