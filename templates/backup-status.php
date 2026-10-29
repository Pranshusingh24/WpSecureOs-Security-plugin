<?php
/**
 * Backups & Recovery Runbook View
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$maxsafe_backups       = class_exists( 'MaxSafe_Backup_Adapter' ) ? MaxSafe_Backup_Adapter::get_all_backups() : ( class_exists( 'WpSecureOs_Backup_Adapter' ) ? WpSecureOs_Backup_Adapter::get_all_backups() : array() );
$maxsafe_latest_backup = class_exists( 'MaxSafe_Backup_Adapter' ) ? MaxSafe_Backup_Adapter::get_latest_verified_backup() : ( class_exists( 'WpSecureOs_Backup_Adapter' ) ? WpSecureOs_Backup_Adapter::get_latest_verified_backup() : false );
?>

<!-- Verified Backups Overview -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-backup"></span>
				<?php esc_html_e( 'Verified Backups & Archive Integrity', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'An archive is only marked VERIFIED after passing archive readability and consistency validation. Never rely on unverified backup creation.', 'maxsafe' ); ?>
			</p>
			<p style="margin:8px 0 0 0; font-size:12px; color:#475569; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
				<span class="dashicons dashicons-category" style="font-size:16px; width:16px; height:16px; color:#0ea5e9;"></span>
				<strong><?php esc_html_e( 'Server Storage Directory:', 'maxsafe' ); ?></strong>
				<code style="font-family:var(--safe-font-mono); font-size:11px; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:4px; border:1px solid #bae6fd;">wp-content/uploads/maxsafe-backups/</code>
			</p>
		</div>
		<div>
			<button type="button" id="btn-create-backup-page" class="safe-btn safe-btn-primary" onclick="jQuery('#btn-create-backup').click();">
				<span class="dashicons dashicons-database-add"></span>
				<?php esc_html_e( 'Create Verified Backup Now', 'maxsafe' ); ?>
			</button>
		</div>
	</div>

	<?php if ( empty( $maxsafe_backups ) ) : ?>
		<div style="text-align:center; padding:30px 0; color:#64748b;">
			<span class="dashicons dashicons-warning" style="font-size:42px; width:42px; height:42px; color:#f59e0b; margin-bottom:12px;"></span>
			<h3 style="margin:0 0 6px 0; color:#0f172a; font-size:15px;">
				<?php esc_html_e( 'No backup archives on record.', 'maxsafe' ); ?>
			</h3>
			<p style="margin:0; font-size:13px;">
				<?php esc_html_e( 'Create a verified backup before applying any pending candidate updates.', 'maxsafe' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="maxsafe-table-responsive wpsecureos-table-responsive">
			<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-backups-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Archive Name', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Size', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'SHA-256 Checksum', 'maxsafe' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Integrity Test', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Created At', 'maxsafe' ); ?></th>
						<th data-priority="4"><?php esc_html_e( 'Verification Notes', 'maxsafe' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Action', 'maxsafe' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $maxsafe_backups as $maxsafe_b ) : ?>
						<tr>
							<td>
								<strong style="font-family:var(--safe-font-mono); font-size:12px; color:#0f172a; white-space:nowrap;">
									<?php echo esc_html( $maxsafe_b['backup_name'] ); ?>
								</strong>
							</td>
							<td><span class="mono-tag"><?php echo esc_html( size_format( $maxsafe_b['archive_size'] ) ); ?></span></td>
							<td style="font-family:var(--safe-font-mono); font-size:11px; color:#64748b; white-space:nowrap;">
								<?php echo esc_html( substr( $maxsafe_b['checksum_sha256'], 0, 16 ) ); ?>...
							</td>
							<td>
								<span class="safe-badge <?php echo $maxsafe_b['is_verified'] ? 'badge-clean' : 'badge-critical'; ?>" style="white-space:nowrap;">
									<?php echo $maxsafe_b['is_verified'] ? esc_html__( 'VERIFIED', 'maxsafe' ) : esc_html__( 'UNVERIFIED', 'maxsafe' ); ?>
								</span>
							</td>
							<td style="font-size:12px; color:#64748b; white-space:nowrap;">
								<?php echo esc_html( $maxsafe_b['created_at'] ); ?>
							</td>
							<td style="font-size:12px; color:#475569;">
								<?php echo esc_html( $maxsafe_b['verification_notes'] ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=maxsafe_download_backup&id=' . (int) $maxsafe_b['id'] ), 'maxsafe_download_backup_' . (int) $maxsafe_b['id'] ) ); ?>" class="safe-btn safe-btn-outline" style="padding:4px 10px; font-size:12px; display:inline-flex; align-items:center; gap:5px; text-decoration:none; white-space:nowrap;">
									<span class="dashicons dashicons-download" style="font-size:14px; width:14px; height:14px; line-height:14px;"></span>
									<?php esc_html_e( 'Download ZIP', 'maxsafe' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- Point-in-Time Recovery Runbook -->
<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-sos"></span>
				<?php esc_html_e( 'Point-in-Time Recovery Runbook & Database Safety Warning', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Follow this documented operational runbook in the event of an update failure or broken production state.', 'maxsafe' ); ?>
			</p>
		</div>
	</div>

	<div style="background:#fffbeb; border:1px solid #fef3c7; border-radius:8px; padding:16px; margin-bottom:20px;">
		<h4 style="margin:0 0 6px 0; color:#b45309; font-size:13px; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-info" style="color:#b45309;"></span>
			<?php esc_html_e( 'CRITICAL: Database Rollback Risks for Active E-Commerce & Membership Sites', 'maxsafe' ); ?>
		</h4>
		<p style="margin:0; font-size:13px; color:#92400e; line-height:1.5;">
			<?php esc_html_e( 'Restoring an old production database will overwrite orders, payments, subscriptions, and new user accounts created since the backup. ALWAYS attempt a Code Rollback (/wp-content/plugins/ or /wp-content/themes/) first to preserve your live customer transaction records.', 'maxsafe' ); ?>
		</p>
	</div>

	<div class="maxsafe-equal-cols wpsecureos-equal-cols">
		<!-- Step A: Code Rollback -->
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
			<h4 style="margin:0 0 10px 0; font-size:14px; font-weight:700; color:#0f172a;">
				<?php esc_html_e( 'Phase 1: Code-Level Rollback (Safe for Live Orders)', 'maxsafe' ); ?>
			</h4>
			<ol style="margin:0; padding-left:18px; font-size:13px; color:#334155; line-height:1.6;">
				<li><?php esc_html_e( 'Identify the broken plugin or theme version that caused regression.', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Download the previous stable release ZIP from WordPress.org or repository.', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Replace the affected component folder in /wp-content/plugins/ via SFTP or WP-CLI.', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Verify site health without touching the live database.', 'maxsafe' ); ?></li>
			</ol>
		</div>

		<!-- Step B: Database Disaster Recovery -->
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
			<h4 style="margin:0 0 10px 0; font-size:14px; font-weight:700; color:#0f172a;">
				<?php esc_html_e( 'Phase 2: Database Restoration (Disaster Recovery)', 'maxsafe' ); ?>
			</h4>
			<ol style="margin:0; padding-left:18px; font-size:13px; color:#334155; line-height:1.6;">
				<li><?php esc_html_e( 'Activate WordPress Maintenance Mode: wp maintenance-mode activate', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Export current delta records (e.g. recent WooCommerce orders table) for merging.', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Extract database_dump.sql from verified MaxSafe backup archive.', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Import schema: wp db import database_dump.sql', 'maxsafe' ); ?></li>
				<li><?php esc_html_e( 'Deactivate Maintenance Mode and run post-restore validation tests.', 'maxsafe' ); ?></li>
			</ol>
		</div>
	</div>
</div>
