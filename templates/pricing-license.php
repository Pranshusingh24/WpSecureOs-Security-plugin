<?php
/**
 * Customer License & Subscription Management Template.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_data = WpSecureOs_License_Manager::get_license();
$all_plans    = WpSecureOs_Plan_Entitlements::get_all_plans();
$current_plan = isset( $license_data['plan'] ) ? $license_data['plan'] : 'free';
$status       = isset( $license_data['status'] ) ? $license_data['status'] : 'unlicensed';
$is_active    = WpSecureOs_License_Manager::is_active();
$in_grace     = WpSecureOs_License_Manager::is_in_grace_period();

$plan_obj     = WpSecureOs_Plan_Entitlements::get_plan( $current_plan );
?>

<div class="wpsecureos-license-container">

	<!-- License Status Hero Banner -->
	<section class="wpsecureos-panel license-hero-panel">
		<div class="license-hero-grid">
			<div class="license-hero-main">
				<div class="license-plan-header">
					<div class="license-plan-badge-lg <?php echo esc_attr( $plan_obj['badge_class'] ); ?>">
						<?php echo esc_html( strtoupper( $plan_obj['name'] ) ); ?>
					</div>
					<?php if ( 'active' === $status ) : ?>
						<span class="status-indicator status-active">
							<span class="pulse-dot-sm"></span> <?php esc_html_e( 'LICENSE ACTIVE // VALIDATED', 'wpsecureos' ); ?>
						</span>
					<?php elseif ( $in_grace ) : ?>
						<span class="status-indicator status-grace">
							<span class="pulse-dot-sm yellow"></span> <?php esc_html_e( 'OFFLINE GRACE PERIOD ACTIVE', 'wpsecureos' ); ?>
						</span>
					<?php elseif ( 'expired' === $status ) : ?>
						<span class="status-indicator status-expired">
							<?php esc_html_e( 'SUBSCRIPTION EXPIRED', 'wpsecureos' ); ?>
						</span>
					<?php elseif ( 'revoked' === $status ) : ?>
						<span class="status-indicator status-revoked">
							<?php esc_html_e( 'LICENSE REVOKED', 'wpsecureos' ); ?>
						</span>
					<?php else : ?>
						<span class="status-indicator status-free">
							<?php esc_html_e( 'COMMUNITY EDITION', 'wpsecureos' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<h2 class="license-hero-title">
					<?php echo esc_html( $plan_obj['name'] ); ?>
				</h2>
				<p class="license-hero-desc">
					<?php echo esc_html( $plan_obj['tagline'] ); ?>
				</p>

				<?php if ( $in_grace ) : ?>
					<div class="license-alert license-alert-warning">
						<span class="dashicons dashicons-warning"></span>
						<div>
							<strong><?php esc_html_e( 'Licensing Server Outage Protection Active', 'wpsecureos' ); ?></strong>
							<p>
								<?php
								printf(
									/* translators: %s: Grace period expiration date */
									esc_html__( 'The licensing server was temporarily unreachable. Your offline grace period preserves full access to all features until %s. Essential security alerts, scans, and recovery runbooks remain completely uninterrupted.', 'wpsecureos' ),
									'<code>' . esc_html( substr( $license_data['grace_period_until'], 0, 10 ) ) . '</code>'
								);
								?>
							</p>
						</div>
					</div>
				<?php elseif ( 'expired' === $status ) : ?>
					<div class="license-alert license-alert-danger">
						<span class="dashicons dashicons-clock"></span>
						<div>
							<strong><?php esc_html_e( 'Subscription Renewal Required', 'wpsecureos' ); ?></strong>
							<p>
								<?php esc_html_e( 'Your subscription has expired. Premium automated staging runs and update delivery are paused. In accordance with our security-first policy, all core security scanning, vulnerability alerts, and rollback recovery tools remain 100% accessible.', 'wpsecureos' ); ?>
							</p>
						</div>
					</div>
				<?php endif; ?>

				<!-- License Key Management Form -->
				<div class="license-key-box">
					<form id="wpsecureos-license-form" method="POST" action="">
						<?php wp_nonce_field( 'wpsecureos_admin_nonce', 'wpsecureos_license_nonce' ); ?>
						<label for="wpsecureos_license_key" class="license-input-label">
							<?php esc_html_e( 'License Key', 'wpsecureos' ); ?>
						</label>
						<div class="license-input-group">
							<input
								type="text"
								id="wpsecureos_license_key"
								name="license_key"
								class="license-key-input"
								placeholder="WPSO-PRO-XXXX-XXXX-XXXX"
								value="<?php echo esc_attr( $license_data['license_key'] ); ?>"
								spellcheck="false"
								autocomplete="off"
							/>
							<button type="button" id="btn-activate-license" class="safe-btn safe-btn-primary">
								<span class="dashicons dashicons-admin-network"></span>
								<?php echo ! empty( $license_data['license_key'] ) ? esc_html__( 'Change / Re-activate', 'wpsecureos' ) : esc_html__( 'Activate License', 'wpsecureos' ); ?>
							</button>

							<?php if ( ! empty( $license_data['license_key'] ) && $is_active ) : ?>
								<button type="button" id="btn-refresh-license" class="safe-btn safe-btn-dark" title="<?php esc_attr_e( 'Refresh entitlements and check validity', 'wpsecureos' ); ?>">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Sync Status', 'wpsecureos' ); ?>
								</button>
								<button type="button" id="btn-deactivate-license" class="safe-btn safe-btn-danger" title="<?php esc_attr_e( 'Release license seat for another domain', 'wpsecureos' ); ?>">
									<span class="dashicons dashicons-dismiss"></span>
									<?php esc_html_e( 'Deactivate', 'wpsecureos' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</form>
					<div id="license-ajax-feedback" class="license-feedback-msg" style="display:none;"></div>
				</div>
			</div>

			<!-- Meta Quotas Card -->
			<div class="license-meta-card">
				<h3 class="license-meta-title"><?php esc_html_e( 'Subscription Telemetry', 'wpsecureos' ); ?></h3>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Bound Site Domain', 'wpsecureos' ); ?></span>
					<span class="telemetry-val mono"><?php echo esc_html( WpSecureOs_License_Manager::get_site_domain() ); ?></span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Licensed Sites Quota', 'wpsecureos' ); ?></span>
					<span class="telemetry-val">
						<span class="quota-badge">
							<?php echo (int) $license_data['active_sites_count']; ?> / <?php echo (int) $license_data['licensed_sites']; ?> <?php esc_html_e( 'Sites', 'wpsecureos' ); ?>
						</span>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Current Period Expiry', 'wpsecureos' ); ?></span>
					<span class="telemetry-val mono">
						<?php echo esc_html( ! empty( $license_data['expires_at'] ) ? substr( $license_data['expires_at'], 0, 10 ) : __( 'Lifetime / Unlimited', 'wpsecureos' ) ); ?>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Last Heartbeat Check', 'wpsecureos' ); ?></span>
					<span class="telemetry-val mono">
						<?php echo esc_html( ! empty( $license_data['last_validated'] ) ? substr( $license_data['last_validated'], 0, 16 ) : __( 'Pending check', 'wpsecureos' ) ); ?>
					</span>
				</div>

				<div class="license-telemetry-row">
					<span class="telemetry-label"><?php esc_html_e( 'Cryptographic Verification', 'wpsecureos' ); ?></span>
					<span class="telemetry-val">
						<?php if ( ! empty( $license_data['signature_verified'] ) ) : ?>
							<span class="crypto-badge verified">✓ ED25519 VERIFIED</span>
						<?php else : ?>
							<span class="crypto-badge standard">STANDARD</span>
						<?php endif; ?>
					</span>
				</div>

				<div class="license-card-footer">
					<a href="https://wpsecureos.com/account/" target="_blank" class="safe-btn safe-btn-dark safe-btn-sm" style="width: 100%; justify-content: center;">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Manage Customer Billing Portal', 'wpsecureos' ); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<!-- Pricing & Plan Comparison Matrix -->
	<section class="wpsecureos-panel" style="margin-top: 24px;">
		<div class="panel-section-header">
			<div>
				<h3 class="panel-title"><?php esc_html_e( 'Available Plans & Premium Tiers', 'wpsecureos' ); ?></h3>
				<p class="panel-subtitle"><?php esc_html_e( 'Scale protection from single freelance sites to multi-site agency portfolios.', 'wpsecureos' ); ?></p>
			</div>
			<div class="billing-cycle-toggle">
				<span class="cycle-label active" id="cycle-annual"><?php esc_html_e( 'Annual Billing (Save 20%)', 'wpsecureos' ); ?></span>
			</div>
		</div>

		<div class="pricing-matrix-grid">
			<?php foreach ( $all_plans as $slug => $p ) :
				$is_current = ( $slug === $current_plan );
				$is_highlight = ! empty( $p['highlight'] );
			?>
				<div class="pricing-card <?php echo $is_current ? 'current-plan-card' : ''; ?> <?php echo $is_highlight ? 'highlight-card' : ''; ?>">
					<?php if ( $is_current ) : ?>
						<div class="current-plan-ribbon"><?php esc_html_e( 'CURRENT ACTIVE PLAN', 'wpsecureos' ); ?></div>
					<?php elseif ( $is_highlight ) : ?>
						<div class="popular-ribbon"><?php esc_html_e( 'MOST POPULAR', 'wpsecureos' ); ?></div>
					<?php endif; ?>

					<div class="pricing-card-header">
						<span class="badge-plan <?php echo esc_attr( $p['badge_class'] ); ?>"><?php echo esc_html( strtoupper( $p['slug'] ) ); ?></span>
						<h4 class="pricing-plan-name"><?php echo esc_html( $p['name'] ); ?></h4>
						<p class="pricing-plan-tagline"><?php echo esc_html( $p['tagline'] ); ?></p>
					</div>

					<div class="pricing-price-box">
						<span class="price-currency">$</span>
						<span class="price-amount"><?php echo (int) $p['price_annual']; ?></span>
						<span class="price-period">/ <?php esc_html_e( 'year', 'wpsecureos' ); ?></span>
					</div>
					<div class="pricing-site-limit">
						<span class="dashicons dashicons-admin-site-alt3"></span>
						<strong><?php echo (int) $p['site_limit'] === 0 ? esc_html__( 'Unlimited Sites', 'wpsecureos' ) : sprintf( esc_html__( '%d Site License', 'wpsecureos' ), (int) $p['site_limit'] ); ?></strong>
					</div>

					<div class="pricing-features-list">
						<?php foreach ( $p['features'] as $f_key => $f_name ) :
							$unlocked = WpSecureOs_Plan_Entitlements::can( $f_key );
						?>
							<div class="pricing-feature-item <?php echo $unlocked ? 'feature-unlocked' : 'feature-locked'; ?>">
								<span class="feature-icon"><?php echo $unlocked ? '✓' : '•'; ?></span>
								<span><?php echo esc_html( $f_name ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="pricing-card-action">
						<?php if ( $is_current ) : ?>
							<button type="button" class="safe-btn safe-btn-dark" disabled style="width: 100%; justify-content: center; opacity: 0.8;">
								<?php esc_html_e( 'Active Plan', 'wpsecureos' ); ?>
							</button>
						<?php else : ?>
							<a href="<?php echo esc_url( 'https://wpsecureos.com/checkout/?plan=' . $slug . '&domain=' . urlencode( WpSecureOs_License_Manager::get_site_domain() ) ); ?>" target="_blank" class="safe-btn safe-btn-primary" style="width: 100%; justify-content: center;">
								<?php printf( esc_html__( 'Upgrade to %s', 'wpsecureos' ), esc_html( ucfirst( $slug ) ) ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Security & Compliance Guarantees -->
	<section class="wpsecureos-panel compliance-guarantees" style="margin-top: 24px;">
		<div class="compliance-grid">
			<div class="compliance-item">
				<span class="dashicons dashicons-shield"></span>
				<div>
					<h4><?php esc_html_e( 'Uninterrupted Security Protection', 'wpsecureos' ); ?></h4>
					<p><?php esc_html_e( 'Essential core file checksums, vulnerability monitoring, audit logs, and verified database restore runbooks never lock out or disable when a license expires.', 'wpsecureos' ); ?></p>
				</div>
			</div>
			<div class="compliance-item">
				<span class="dashicons dashicons-lock"></span>
				<div>
					<h4><?php esc_html_e( 'Zero Secrets Stored in WordPress', 'wpsecureos' ); ?></h4>
					<p><?php esc_html_e( 'Payment cards, server private keys, and billing secrets are never stored in or transmitted to your WordPress database. All checkouts use secure hosted sessions.', 'wpsecureos' ); ?></p>
				</div>
			</div>
			<div class="compliance-item">
				<span class="dashicons dashicons-cloud"></span>
				<div>
					<h4><?php esc_html_e( 'Network Outage Resilience', 'wpsecureos' ); ?></h4>
					<p><?php esc_html_e( 'Temporary DNS or server connectivity drops never disable your site agent. The built-in 7-day offline grace period guarantees stability.', 'wpsecureos' ); ?></p>
				</div>
			</div>
		</div>
	</section>

</div>
