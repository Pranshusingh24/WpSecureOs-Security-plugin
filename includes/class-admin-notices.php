<?php
/**
 * Actionable, Capability-Protected, Dismissible Admin Notices.
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpSecureOs_Admin_Notices {

	/**
	 * Hook into admin_notices.
	 */
	public static function display_notices() {
		// Only display for authorized roles who can view security or dashboard
		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_VIEW_DASHBOARD ) ) {
			return;
		}

		$dismissed = (array) get_user_meta( get_current_user_id(), 'wpsecureos_dismissed_notices', true );

		// Check for active critical vulnerabilities
		$vulns = WpSecureOs_Security_Scanner::get_known_vulnerabilities();
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
			<div class="notice notice-error is-dismissible wpsecureos-admin-notice" data-notice-id="critical_vuln_notice">
				<div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
					<div>
						<strong style="color: #ef4444; text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; margin-right: 8px;">[WpSecureOs Alert]</strong>
						<span>
							<?php
							printf(
								/* translators: %d: Number of critical vulnerabilities */
								esc_html__( '%d critical security advisory detected across active components. Back up the site and validate updates in staging prior to production deploy.', 'wpsecureos' ),
								(int) $critical_count
							);
							?>
						</span>
					</div>
					<div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-updates' ) ); ?>" class="button button-primary" style="background: #0ea5e9; border-color: #0284c7;">
							<?php esc_html_e( 'Inspect Risk & Safe Deploy', 'wpsecureos' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		// Check PHP lifecycle warning
		$php_info = WpSecureOs_Php_Advisor::analyze_current_runtime();
		if ( 'EOL' === $php_info['status'] && ! in_array( 'php_eol_notice', $dismissed, true ) ) {
			?>
			<div class="notice notice-warning is-dismissible wpsecureos-admin-notice" data-notice-id="php_eol_notice">
				<p>
					<strong style="color: #f97316;">[WpSecureOs Advisor]</strong>
					<?php
					printf(
						/* translators: 1: Current PHP version, 2: PHP lifecycle description */
						esc_html__( 'Server is running PHP %1$s (%2$s). A hosting-level runtime upgrade is recommended.', 'wpsecureos' ),
						esc_html( PHP_VERSION ),
						esc_html( $php_info['description'] )
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsecureos-security#php-lifecycle' ) ); ?>" style="margin-left: 8px; font-weight: 600;">
						<?php esc_html_e( 'View Hosting Guidance', 'wpsecureos' ); ?> &rarr;
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
		check_ajax_referer( 'wpsecureos_admin_nonce', 'nonce' );

		if ( ! current_user_can( WpSecureOs_Capabilities::CAP_VIEW_DASHBOARD ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wpsecureos' ) ), 403 );
		}

		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';
		if ( ! empty( $notice_id ) ) {
			$user_id   = get_current_user_id();
			$dismissed = (array) get_user_meta( $user_id, 'wpsecureos_dismissed_notices', true );
			if ( ! in_array( $notice_id, $dismissed, true ) ) {
				$dismissed[] = $notice_id;
				update_user_meta( $user_id, 'wpsecureos_dismissed_notices', $dismissed );
			}
		}

		wp_send_json_success();
	}
}
