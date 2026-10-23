<?php
/**
 * Updates & Safe Deployment Pipeline View
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$updates = WpSecureOs_Update_Provider::get_updates_with_assessment();
?>

<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Explainable Update Risk Assessment & Safe Deployment', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Every candidate update is evaluated against vulnerability feeds, PHP runtime compatibility, backup verification, and staging smoke test status.', 'wpsecureos' ); ?>
			</p>
		</div>
		<div>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpsecureos-updates&force_check=1' ), 'wpsecureos_force_updates' ) ); ?>" class="safe-btn safe-btn-outline">
				<span class="dashicons dashicons-image-rotate"></span>
				<?php esc_html_e( 'Check for New Releases', 'wpsecureos' ); ?>
			</a>
		</div>
	</div>

	<?php if ( empty( $updates ) ) : ?>
		<div style="text-align:center; padding:40px 0; color:#64748b;">
			<span class="dashicons dashicons-saved" style="font-size:48px; width:48px; height:48px; color:#10b981; margin-bottom:12px;"></span>
			<h3 style="margin:0 0 6px 0; color:#0f172a; font-size:16px;">
				<?php esc_html_e( 'All Core, Plugins, and Themes are fully up to date!', 'wpsecureos' ); ?>
			</h3>
			<p style="margin:0; font-size:13px;">
				<?php esc_html_e( 'No candidate updates are pending on this WordPress instance.', 'wpsecureos' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="wpsecureos-table-responsive">
			<table class="wpsecureos-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Component', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Installed -> Target', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Security Urgency', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Deployment Confidence', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Explainable Risk Factors', 'wpsecureos' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wpsecureos' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $updates as $u ) : ?>
						<?php
						$assessment = $u['assessment'];
						$urgency = $assessment['security_urgency'];
						$confidence = $assessment['deployment_confidence'];

						$urgency_cls = ( 'Critical' === $urgency ) ? 'badge-critical' : ( ( 'Important' === $urgency ) ? 'badge-high' : 'badge-routine' );
						$conf_cls    = ( 'High' === $confidence ) ? 'badge-clean' : ( ( 'Moderate' === $confidence ) ? 'badge-medium' : ( ( 'Low' === $confidence ) ? 'badge-critical' : 'badge-unknown' ) );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $u['name'] ); ?></strong>
								<div style="color:#64748b; font-size:11px;">
									<span class="mono-tag"><?php echo esc_html( $u['slug'] ); ?></span> &bull;
									<span style="text-transform:uppercase;"><?php echo esc_html( $u['type'] ); ?></span>
								</div>
							</td>
							<td>
								<span class="mono-tag"><?php echo esc_html( $u['installed_version'] ); ?></span> &rarr;
								<span class="mono-tag" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd; font-weight:700;">
									<?php echo esc_html( $u['new_version'] ); ?>
								</span>
								<div style="font-size:11px; color:#64748b; margin-top:3px;">
									<?php printf( /* translators: %s: bump type */ esc_html__( 'Bump: %s', 'wpsecureos' ), esc_html( strtoupper( $assessment['version_bump'] ) ) ); ?>
								</div>
							</td>
							<td>
								<span class="safe-badge <?php echo esc_attr( $urgency_cls ); ?>">
									<?php echo esc_html( $urgency ); ?>
								</span>
							</td>
							<td>
								<span class="safe-badge <?php echo esc_attr( $conf_cls ); ?>">
									<?php echo esc_html( $confidence ); ?>
								</span>
							</td>
							<td>
								<ul style="margin:0; padding-left:14px; font-size:12px; color:#334155;">
									<?php foreach ( $assessment['reasons'] as $r ) : ?>
										<li style="margin-bottom:2px;"><?php echo esc_html( $r ); ?></li>
									<?php endforeach; ?>
								</ul>
								<div style="margin-top:6px; font-size:11px; color:#64748b;">
									<strong><?php esc_html_e( 'Recommended Next Step:', 'wpsecureos' ); ?></strong>
									<span style="color:#0284c7;"><?php echo esc_html( $assessment['next_action'] ); ?></span>
								</div>
							</td>
							<td>
								<button type="button"
									class="safe-btn safe-btn-primary btn-start-pipeline"
									data-type="<?php echo esc_attr( $u['type'] ); ?>"
									data-slug="<?php echo esc_attr( $u['slug'] ); ?>"
									data-name="<?php echo esc_attr( $u['name'] ); ?>"
									data-version="<?php echo esc_attr( $u['new_version'] ); ?>">
									<span class="dashicons dashicons-shield"></span>
									<?php esc_html_e( 'Prepare Safe Update', 'wpsecureos' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
