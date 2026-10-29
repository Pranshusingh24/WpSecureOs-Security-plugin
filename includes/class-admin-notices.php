<?php
/**
 * Actionable, Capability-Protected, Dismissible Admin Notices.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Admin_Notices {

	/**
	 * Hook into admin_notices.
	 */
	public static function display_notices() {
		// Only display for authorized roles who can view security or dashboard
		if ( ! current_user_can( MaxSafe_Capabilities::CAP_VIEW_DASHBOARD ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dismissed = (array) get_user_meta( get_current_user_id(), 'maxsafe_dismissed_notices', true );
		$legacy_dismissed = (array) get_user_meta( get_current_user_id(), 'wpsecureos_dismissed_notices', true );
		$dismissed = array_unique( array_merge( $dismissed, $legacy_dismissed ) );

		// Check for active critical vulnerabilities
		$vulns = MaxSafe_Security_Scanner::get_known_vulnerabilities();
		$critical_count = 0;
		if ( ! empty( $vulns ) ) {
			foreach ( $vulns as $items ) {
				foreach ( $items as $v ) {
					if ( 'critical' === strtolower( $v['severity'] ) ) {
						$critical_count++;
					}
				}
			}
		}

		if ( $critical_count > 0 && ! in_array( 'critical_vuln_notice', $dismissed, true ) ) {
			?>
			<div class="notice notice-error is-dismissible maxsafe-admin-notice wpsecureos-admin-notice" data-notice-id="critical_vuln_notice">
				<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 6px 0;">
					<div>
						<strong style="color: #ef4444; text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; margin-right: 8px;">[MaxSafe Alert]</strong>
						<span>
							<?php
							printf(
								/* translators: %d: Number of critical vulnerabilities */
								esc_html__( '%d critical security advisory detected across active components. Back up the site and validate updates in staging prior to production deploy.', 'maxsafe' ),
								(int) $critical_count
							);
							?>
						</span>
					</div>
					<div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-updates' ) ); ?>" class="button button-primary" style="background: #0ea5e9; border-color: #0284c7;">
							<?php esc_html_e( 'Inspect Risk & Safe Deploy', 'maxsafe' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		// Check PHP lifecycle warning
		$php_info = MaxSafe_Php_Advisor::analyze_current_runtime();
		if ( 'EOL' === $php_info['status'] && ! in_array( 'php_eol_notice', $dismissed, true ) ) {
			?>
			<div class="notice notice-warning is-dismissible maxsafe-admin-notice wpsecureos-admin-notice" data-notice-id="php_eol_notice">
				<p>
					<strong style="color: #f97316;">[MaxSafe Advisor]</strong>
					<?php
					printf(
						/* translators: 1: Current PHP version, 2: PHP lifecycle description */
						esc_html__( 'Server is running PHP %1$s (%2$s). A hosting-level runtime upgrade is recommended.', 'maxsafe' ),
						esc_html( PHP_VERSION ),
						esc_html( $php_info['description'] )
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-security#php-lifecycle' ) ); ?>" style="margin-left: 8px; font-weight: 600;">
						<?php esc_html_e( 'View Hosting Guidance', 'maxsafe' ); ?> &rarr;
					</a>
				</p>
			</div>
			<?php
		}

		// Check License Grace Period Notice
		if ( MaxSafe_License_Manager::is_in_grace_period() && ! in_array( 'license_grace_notice', $dismissed, true ) ) {
			$lic = MaxSafe_License_Manager::get_license();
			?>
			<div class="notice notice-info is-dismissible maxsafe-admin-notice wpsecureos-admin-notice" data-notice-id="license_grace_notice">
				<p>
					<strong style="color: #0ea5e9;">[MaxSafe License]</strong>
					<?php
					printf(
						/* translators: %s: Expiration date of grace period */
						esc_html__( 'Licensing server check is temporarily delayed. Offline grace period active until %s. All security monitoring, backups, and reports remain 100%% functional.', 'maxsafe' ),
						'<strong>' . esc_html( substr( $lic['grace_period_until'], 0, 10 ) ) . '</strong>'
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-license' ) ); ?>" style="margin-left: 8px; font-weight: 600;">
						<?php esc_html_e( 'Check Status', 'maxsafe' ); ?> &rarr;
					</a>
				</p>
			</div>
			<?php
		}

		// Check License Expired Notice
		$lic_status = MaxSafe_License_Manager::get_license()['status'];
		if ( 'expired' === $lic_status && ! in_array( 'license_expired_notice', $dismissed, true ) ) {
			?>
			<div class="notice notice-warning is-dismissible maxsafe-admin-notice wpsecureos-admin-notice" data-notice-id="license_expired_notice">
				<p>
					<strong style="color: #f59e0b;">[MaxSafe Subscription]</strong>
					<?php esc_html_e( 'Your MaxSafe Pro/Agency subscription has expired. Automated staging runs are paused. All core security scans, checksum verification, and recovery rollback tools remain fully active.', 'maxsafe' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=maxsafe-license' ) ); ?>" style="margin-left: 8px; font-weight: 600;">
						<?php esc_html_e( 'Renew Subscription', 'maxsafe' ); ?> &rarr;
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX handler to dismiss a notice per user.
	 */
	public static function ajax_dismiss_notice() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'maxsafe_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'wpsecureos_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'maxsafe' ) ), 403 );
		}

		if ( ! current_user_can( MaxSafe_Capabilities::CAP_VIEW_DASHBOARD ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'maxsafe' ) ), 403 );
		}

		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';
		if ( ! empty( $notice_id ) ) {
			$user_id   = get_current_user_id();
			$dismissed = (array) get_user_meta( $user_id, 'maxsafe_dismissed_notices', true );
			if ( ! in_array( $notice_id, $dismissed, true ) ) {
				$dismissed[] = $notice_id;
				update_user_meta( $user_id, 'maxsafe_dismissed_notices', $dismissed );
			}
		}

		wp_send_json_success();
	}
}
