<?php
/**
 * Backups & Recovery Runbook View
 *
 * @package WpSecureOs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$backups       = WpSecureOs_Backup_Adapter::get_all_backups();
$latest_backup = WpSecureOs_Backup_Adapter::get_latest_verified_backup();
?>

<!-- Verified Backups Overview -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-backup"></span>
				<?php esc_html_e( 'Verified Backups & Archive Integrity', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'An archive is only marked VERIFIED after passing archive readability and consistency validation. Never rely on unverified backup creation.', 'wpsecureos' ); ?>
			</p>
		</div>
		<div>
			<button type="button" id="btn-create-backup-page" class="safe-btn safe-btn-primary" onclick="jQuery('#btn-create-backup').click();">
				<span class="dashicons dashicons-database-add"></span>
				<?php esc_html_e( 'Create Verified Backup Now', 'wpsecureos' ); ?>
			</button>
		</div>
	</div>

	<?php if ( empty( $backups ) ) : ?>
		<div style="text-align:center; padding:30px 0; color:#64748b;">
			<span class="dashicons dashicons-warning" style="font-size:42px; width:42px; height:42px; color:#f59e0b; margin-bottom:12px;"></span>
			<h3 style="margin:0 0 6px 0; color:#0f172a; font-size:15px;">
				<?php esc_html_e( 'No backup archives on record.', 'wpsecureos' ); ?>
			</h3>
			<p style="margin:0; font-size:13px;">
				<?php esc_html_e( 'Create a verified backup before applying any pending candidate updates.', 'wpsecureos' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="wpsecureos-table-responsive">
			<table class="wpsecureos-table wpsecureos-datatable display responsive" id="wpsecureos-backups-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Archive Name', 'wpsecureos' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Size', 'wpsecureos' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'SHA-256 Checksum', 'wpsecureos' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Integrity Test', 'wpsecureos' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Created At', 'wpsecureos' ); ?></th>
						<th data-priority="4"><?php esc_html_e( 'Verification Notes', 'wpsecureos' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $backups as $b ) : ?>
						<tr>
							<td>
								<strong style="font-family:var(--safe-font-mono); font-size:12px; color:#0f172a; white-space:nowrap;">
									<?php echo esc_html( $b['backup_name'] ); ?>
								</strong>
							</td>
							<td><span class="mono-tag"><?php echo esc_html( size_format( $b['archive_size'] ) ); ?></span></td>
							<td style="font-family:var(--safe-font-mono); font-size:11px; color:#64748b; white-space:nowrap;">
								<?php echo esc_html( substr( $b['checksum_sha256'], 0, 16 ) ); ?>...
							</td>
							<td>
								<span class="safe-badge <?php echo $b['is_verified'] ? 'badge-clean' : 'badge-critical'; ?>" style="white-space:nowrap;">
									<?php echo $b['is_verified'] ? esc_html__( 'VERIFIED', 'wpsecureos' ) : esc_html__( 'UNVERIFIED', 'wpsecureos' ); ?>
								</span>
							</td>
							<td style="font-size:12px; color:#64748b; white-space:nowrap;">
								<?php echo esc_html( $b['created_at'] ); ?>
							</td>
							<td style="font-size:12px; color:#475569;">
								<?php echo esc_html( $b['verification_notes'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- Point-in-Time Recovery Runbook -->
<div class="wpsecureos-panel">
	<div class="wpsecureos-panel-header">
		<div>
			<h2 class="wpsecureos-panel-title">
				<span class="dashicons dashicons-sos"></span>
				<?php esc_html_e( 'Point-in-Time Recovery Runbook & Database Safety Warning', 'wpsecureos' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Follow this documented operational runbook in the event of an update failure or broken production state.', 'wpsecureos' ); ?>
			</p>
		</div>
	</div>

	<div style="background:#fffbeb; border:1px solid #fef3c7; border-radius:8px; padding:16px; margin-bottom:20px;">
		<h4 style="margin:0 0 6px 0; color:#b45309; font-size:13px; display:flex; align-items:center; gap:6px;">
			<span class="dashicons dashicons-info" style="color:#b45309;"></span>
			<?php esc_html_e( 'CRITICAL: Database Rollback Risks for Active E-Commerce & Membership Sites', 'wpsecureos' ); ?>
		</h4>
		<p style="margin:0; font-size:13px; color:#92400e; line-height:1.5;">
			<?php esc_html_e( 'Restoring an old production database will overwrite orders, payments, subscriptions, and new user accounts created since the backup. ALWAYS attempt a Code Rollback (/wp-content/plugins/ or /wp-content/themes/) first to preserve your live customer transaction records.', 'wpsecureos' ); ?>
		</p>
	</div>

	<div class="wpsecureos-equal-cols">
		<!-- Step A: Code Rollback -->
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
			<h4 style="margin:0 0 10px 0; font-size:14px; font-weight:700; color:#0f172a;">
				<?php esc_html_e( 'Phase 1: Code-Level Rollback (Safe for Live Orders)', 'wpsecureos' ); ?>
			</h4>
			<ol style="margin:0; padding-left:18px; font-size:13px; color:#334155; line-height:1.6;">
				<li><?php esc_html_e( 'Identify the broken plugin or theme version that caused regression.', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Download the previous stable release ZIP from WordPress.org or repository.', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Replace the affected component folder in /wp-content/plugins/ via SFTP or WP-CLI.', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Verify site health without touching the live database.', 'wpsecureos' ); ?></li>
			</ol>
		</div>

		<!-- Step B: Database Disaster Recovery -->
		<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
			<h4 style="margin:0 0 10px 0; font-size:14px; font-weight:700; color:#0f172a;">
				<?php esc_html_e( 'Phase 2: Database Restoration (Disaster Recovery)', 'wpsecureos' ); ?>
			</h4>
			<ol style="margin:0; padding-left:18px; font-size:13px; color:#334155; line-height:1.6;">
				<li><?php esc_html_e( 'Activate WordPress Maintenance Mode: wp maintenance-mode activate', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Export current delta records (e.g. recent WooCommerce orders table) for merging.', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Extract database_dump.sql from verified WpSecureOs backup archive.', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Import schema: wp db import database_dump.sql', 'wpsecureos' ); ?></li>
				<li><?php esc_html_e( 'Deactivate Maintenance Mode and run post-restore validation tests.', 'wpsecureos' ); ?></li>
			</ol>
		</div>
	</div>
</div>
