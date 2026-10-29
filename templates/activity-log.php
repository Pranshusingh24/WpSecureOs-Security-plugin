<?php
/**
 * Security Activity & Audit Log View
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page     = isset( $_GET['log_page'] ) ? max( 1, (int) $_GET['log_page'] ) : 1;
$severity = isset( $_GET['severity'] ) ? sanitize_text_field( wp_unslash( $_GET['severity'] ) ) : '';
$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

$logs_data   = class_exists( 'MaxSafe_Event_Logger' ) ? MaxSafe_Event_Logger::get_events( $page, 20, $severity, $search ) : ( class_exists( 'WpSecureOs_Event_Logger' ) ? WpSecureOs_Event_Logger::get_events( $page, 20, $severity, $search ) : array( 'items' => array(), 'total_pages' => 1 ) );
$events      = $logs_data['items'];
$total_pages = $logs_data['total_pages'];
?>

<div class="maxsafe-panel wpsecureos-panel">
	<div class="maxsafe-panel-header wpsecureos-panel-header">
		<div>
			<h2 class="maxsafe-panel-title wpsecureos-panel-title">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'Security Events & Administrative Audit Trail', 'maxsafe' ); ?>
			</h2>
			<p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
				<?php esc_html_e( 'Records authentication attempts, rate limiting events, scans, and privileged update operations. Credentials and secrets are automatically redacted.', 'maxsafe' ); ?>
			</p>
		</div>

		<!-- Filter Bar -->
		<form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="maxsafe-filter-form wpsecureos-filter-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'maxsafe-logs' ); ?>" />

			<select name="severity" style="font-size:12px; height:34px;">
				<option value=""><?php esc_html_e( 'All Severities', 'maxsafe' ); ?></option>
				<option value="critical" <?php selected( $severity, 'critical' ); ?>><?php esc_html_e( 'Critical', 'maxsafe' ); ?></option>
				<option value="high" <?php selected( $severity, 'high' ); ?>><?php esc_html_e( 'High', 'maxsafe' ); ?></option>
				<option value="medium" <?php selected( $severity, 'medium' ); ?>><?php esc_html_e( 'Medium', 'maxsafe' ); ?></option>
				<option value="low" <?php selected( $severity, 'low' ); ?>><?php esc_html_e( 'Low / Routine', 'maxsafe' ); ?></option>
			</select>

			<input type="text" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search message, IP, component...', 'maxsafe' ); ?>" style="font-size:12px; height:34px;" />

			<button type="submit" class="safe-btn safe-btn-outline" style="padding:6px 14px; font-size:12px;">
				<?php esc_html_e( 'Filter', 'maxsafe' ); ?>
			</button>
		</form>
	</div>

	<?php if ( empty( $events ) ) : ?>
		<div style="text-align:center; padding:40px 0; color:#64748b;">
			<span class="dashicons dashicons-search" style="font-size:40px; width:40px; height:40px; color:#cbd5e1; margin-bottom:12px;"></span>
			<p style="margin:0; font-size:14px; font-weight:600; color:#0f172a;">
				<?php esc_html_e( 'No security events matching filter.', 'maxsafe' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="maxsafe-table-responsive wpsecureos-table-responsive">
			<table class="maxsafe-table wpsecureos-table maxsafe-datatable wpsecureos-datatable display responsive" id="maxsafe-logs-table" style="width:100%;">
				<thead>
					<tr>
						<th data-priority="1"><?php esc_html_e( 'Timestamp', 'maxsafe' ); ?></th>
						<th data-priority="1"><?php esc_html_e( 'Severity', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Component', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Event Type', 'maxsafe' ); ?></th>
						<th data-priority="2"><?php esc_html_e( 'Message & Redacted Metadata', 'maxsafe' ); ?></th>
						<th data-priority="3"><?php esc_html_e( 'Actor IP', 'maxsafe' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $events as $ev ) : ?>
						<?php
						$sev_cls = 'badge-low';
						if ( 'critical' === $ev['severity'] ) $sev_cls = 'badge-critical';
						if ( 'high' === $ev['severity'] )     $sev_cls = 'badge-high';
						if ( 'medium' === $ev['severity'] )   $sev_cls = 'badge-medium';
						?>
						<tr>
							<td style="font-size:12px; color:#64748b; white-space:nowrap; font-family:var(--safe-font-mono);">
								<?php echo esc_html( $ev['created_at'] ); ?>
							</td>
							<td>
								<span class="safe-badge <?php echo esc_attr( $sev_cls ); ?>" style="white-space:nowrap;">
									<?php echo esc_html( strtoupper( $ev['severity'] ) ); ?>
								</span>
							</td>
							<td><strong><?php echo esc_html( $ev['component_ref'] ); ?></strong></td>
							<td><span class="mono-tag" style="white-space:nowrap;"><?php echo esc_html( $ev['event_type'] ); ?></span></td>
							<td style="font-size:13px; color:#334155; min-width:200px;">
								<?php echo esc_html( $ev['message'] ); ?>
							</td>
							<td><span class="mono-tag" style="white-space:nowrap;"><?php echo esc_html( $ev['ip_address'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:20px;">
				<span style="font-size:12px; color:#64748b;">
					<?php printf( /* translators: 1: Current page, 2: Total pages */ esc_html__( 'Page %1$d of %2$d', 'maxsafe' ), (int) $page, (int) $total_pages ); ?>
				</span>
				<div style="display:flex; gap:6px;">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'log_page', $page - 1 ) ); ?>" class="safe-btn safe-btn-outline" style="padding:4px 10px; font-size:12px;">
							&laquo; <?php esc_html_e( 'Previous', 'maxsafe' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'log_page', $page + 1 ) ); ?>" class="safe-btn safe-btn-outline" style="padding:4px 10px; font-size:12px;">
							<?php esc_html_e( 'Next', 'maxsafe' ); ?> &raquo;
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
