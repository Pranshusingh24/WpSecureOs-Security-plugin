/**
 * MaxSafe Interactive Admin Console
 */

(function($) {
	'use strict';

	var cfg = window.maxsafe_vars || window.wpsecureos_vars || {
		ajax_url: '',
		nonce: '',
		strings: {
			scan_complete: 'Security and checksum scan completed.',
			backup_verified: 'Verified backup generated.',
			action_failed: 'Action failed. Please try again.',
			confirm_override: 'WARNING: Bypassing automated safety gates may deploy breaking changes to production. Are you sure?'
		}
	};

	var MaxSafe = {
		init: function() {
			this.bindEvents();
			this.initDataTables();
		},

		initDataTables: function() {
			if (!$.fn.DataTable) {
				return;
			}

			$('.maxsafe-datatable, .wpsecureos-datatable').each(function() {
				var $table = $(this);
				if ($.fn.DataTable.isDataTable(this)) {
					return;
				}

				// Determine if last column is Action button to prevent sorting on it
				var lastHeader = $table.find('thead th:last-child').text().trim().toLowerCase();
				var columnDefs = [];
				if (lastHeader === 'action') {
					columnDefs.push({ targets: -1, orderable: false, searchable: false });
				}

				$table.DataTable({
					dom: '<"maxsafe-datatable-top"lf><"maxsafe-table-scroll"rt><"maxsafe-datatable-bottom"ip>',
					responsive: true,
					autoWidth: false,
					pageLength: 10,
					lengthMenu: [5, 10, 25, 50],
					columnDefs: columnDefs,
					order: [], // preserve server rendering order
					language: {
						search: '',
						searchPlaceholder: 'Filter records...',
						lengthMenu: 'Show _MENU_ entries',
						info: 'Showing _START_ to _END_ of _TOTAL_ items',
						infoEmpty: 'Showing 0 items',
						infoFiltered: '(filtered from _MAX_ total records)',
						emptyTable: 'No matching records in active view',
						zeroRecords: 'No matching records found',
						paginate: {
							first: '«',
							previous: '‹',
							next: '›',
							last: '»'
						}
					}
				});
			});
		},

		bindEvents: function() {
			var self = this;

			// 1. Run On-demand Scan
			$(document).on('click', '#btn-run-scan', function(e) {
				e.preventDefault();
				self.triggerScan($(this));
			});

			// 2. Trigger Full Verified Backup
			$(document).on('click', '#btn-create-backup', function(e) {
				e.preventDefault();
				self.triggerBackup($(this));
			});

			// 3. Open Safe Update Pipeline Modal
			$(document).on('click', '.btn-start-pipeline', function(e) {
				e.preventDefault();
				var type = $(this).data('type');
				var slug = $(this).data('slug');
				var ver  = $(this).data('version');
				var name = $(this).data('name') || slug;
				self.openPipelineModal(type, slug, ver, name);
			});

			// 4. Modal Close
			$(document).on('click', '.maxsafe-modal-close, .wpsecureos-modal-close, .btn-modal-cancel', function(e) {
				e.preventDefault();
				$('.maxsafe-modal-overlay, .wpsecureos-modal-overlay').fadeOut(150);
			});

			// 5. Execute Pipeline Run
			$(document).on('click', '#btn-execute-pipeline', function(e) {
				e.preventDefault();
				self.executePipeline($(this));
			});

			// 6. Approve Production Deployment
			$(document).on('click', '#btn-approve-production', function(e) {
				e.preventDefault();
				self.approveDeployment($(this));
			});

			// 7. Emergency Override Button
			$(document).on('click', '#btn-emergency-override', function(e) {
				e.preventDefault();
				self.openOverrideModal();
			});

			// 8. Confirm Emergency Override
			$(document).on('click', '#btn-confirm-override', function(e) {
				e.preventDefault();
				self.executeOverride($(this));
			});

			// 9. Dismiss Admin Notice
			$(document).on('click', '.maxsafe-admin-notice .notice-dismiss, .wpsecureos-admin-notice .notice-dismiss', function() {
				var $notice = $(this).closest('.maxsafe-admin-notice, .wpsecureos-admin-notice');
				var noticeId = $notice.data('notice-id');
				if (noticeId) {
					$.post(cfg.ajax_url, {
						action: 'maxsafe_dismiss_notice',
						nonce: cfg.nonce,
						notice_id: noticeId
					});
				}
			});

			// 10. Activate License
			$(document).on('click', '#btn-activate-license', function(e) {
				e.preventDefault();
				self.activateLicense($(this));
			});

			// 11. Deactivate License
			$(document).on('click', '#btn-deactivate-license', function(e) {
				e.preventDefault();
				self.deactivateLicense($(this));
			});

			// 12. Refresh / Sync License Status
			$(document).on('click', '#btn-refresh-license', function(e) {
				e.preventDefault();
				self.refreshLicense($(this));
			});
		},

		showToast: function(msg, isError) {
			$('.maxsafe-toast, .wpsecureos-toast').remove();
			var border = isError ? '#ef4444' : '#0ea5e9';
			var $toast = $('<div class="maxsafe-toast wpsecureos-toast" style="border-left-color:' + border + '"><span>' + msg + '</span></div>');
			$('body').append($toast);
			setTimeout(function() {
				$toast.fadeOut(300, function() { $(this).remove(); });
			}, 4000);
		},

		triggerScan: function($btn) {
			var self = this;
			var originalText = $btn.html();
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Scanning...');

			self.appendTerminalLog('Initiating continuous security posture & core checksums scan...', 'info');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'run_scan',
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).html(originalText);
				if (res.success) {
					self.appendTerminalLog('Security scan completed. Vulnerabilities: ' + res.data.security.total_findings + ' findings.', 'success');
					self.appendTerminalLog('Core file integrity verification: ' + res.data.integrity.core_status.toUpperCase(), res.data.integrity.is_clean ? 'success' : 'warn');
					self.showToast(cfg.strings.scan_complete || 'Scan complete');
					setTimeout(function() { location.reload(); }, 1200);
				} else {
					self.appendTerminalLog('Scan error: ' + (res.data.message || 'Server error'), 'error');
					self.showToast(cfg.strings.action_failed || 'Failed', true);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html(originalText);
				self.showToast(cfg.strings.action_failed || 'Failed', true);
			});
		},

		triggerBackup: function($btn) {
			var self = this;
			var orig = $btn.html();
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Backing up...');

			self.appendTerminalLog('Exporting database & building verified ZIP archive manifest...', 'info');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'create_backup',
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).html(orig);
				if (res.success) {
					self.appendTerminalLog('Archive generated: ' + res.data.backup_name + ' (' + res.data.size + ')', 'success');
					self.appendTerminalLog('SHA-256 Checksum: ' + res.data.sha256, 'info');
					self.appendTerminalLog('Archive Integrity Status: ' + (res.data.verified ? 'PASSED & READABLE' : 'FAILED'), res.data.verified ? 'success' : 'error');
					self.showToast(cfg.strings.backup_verified || 'Backup verified');
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					self.appendTerminalLog('Backup creation failed: ' + res.data.message, 'error');
					self.showToast(res.data.message, true);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html(orig);
				self.showToast(cfg.strings.action_failed || 'Failed', true);
			});
		},

		openPipelineModal: function(type, slug, ver, name) {
			$('#modal-pipeline-name').text(name);
			$('#modal-pipeline-slug').text(slug);
			$('#modal-pipeline-ver').text(ver);
			$('#modal-pipeline-type').val(type);

			// Reset stepper
			$('.maxsafe-step-item, .wpsecureos-step-item').removeClass('active completed');
			$('#step-1').addClass('active');

			$('#pipeline-results-box').hide();
			$('#pipeline-actions-pre').show();
			$('#pipeline-actions-post').hide();
			$('#modal-safe-pipeline').fadeIn(150);
		},

		executePipeline: function($btn) {
			var self = this;
			var type = $('#modal-pipeline-type').val();
			var slug = $('#modal-pipeline-slug').text();
			var ver  = $('#modal-pipeline-ver').text();

			$btn.prop('disabled', true).text('Running Pipeline Steps...');

			// Animate through state machine
			$('#step-1').removeClass('active').addClass('completed');
			$('#step-2').addClass('active');

			setTimeout(function() {
				$('#step-2').removeClass('active').addClass('completed');
				$('#step-3').addClass('active');
			}, 600);

			setTimeout(function() {
				$('#step-3').removeClass('active').addClass('completed');
				$('#step-4').addClass('active');
			}, 1200);

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'start_pipeline',
				component_type: type,
				slug: slug,
				new_version: ver,
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).text('Run Pipeline');
				if (res.success && res.data.success) {
					$('#step-4').removeClass('active').addClass('completed');
					$('#step-5').addClass('active');

					$('#pipeline-results-box').show();
					$('#pipeline-actions-pre').hide();
					$('#pipeline-actions-post').show();

					$('#pipeline-idempotency-key').val(res.data.idempotency_key);
					$('#pipeline-summary-text').text(res.data.message);

					var smoke = res.data.test_results.smoke_tests;
					var wc    = res.data.test_results.woocommerce_tests;

					$('#pipe-smoke-rest').text(smoke.rest_api_functional ? 'PASSED (200 OK)' : 'FAILED');
					$('#pipe-smoke-php').text(smoke.no_php_fatals ? 'CLEAN (0 Errors)' : 'FATAL DETECTED');
					$('#pipe-smoke-wc').text(wc.is_applicable ? (wc.mock_checkout_status.toUpperCase() + ' (Sandbox)') : 'N/A');

					self.showToast('Staging tests complete. Ready for approval.');
				} else {
					$('#step-4').removeClass('active');
					self.showToast(res.data ? res.data.message : 'Pipeline aborted.', true);
				}
			});
		},

		approveDeployment: function($btn) {
			var self = this;
			var key = $('#pipeline-idempotency-key').val();
			$btn.prop('disabled', true).text('Deploying to Production...');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'approve_deploy',
				idempotency_key: key,
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).text('Approve & Deploy to Live');
				if (res.success && res.data.success) {
					$('#step-5').removeClass('active').addClass('completed');
					self.showToast('Production update deployed and verified!');
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					self.showToast(res.data.message || 'Production deployment failed.', true);
				}
			});
		},

		openOverrideModal: function() {
			var proceed = confirm(cfg.strings.confirm_override || 'Bypass safety gates?');
			if (!proceed) return;

			var reason = prompt('Document mandatory operational rationale for bypassing staging gates:');
			if (reason && reason.trim().length > 0) {
				var key = $('#pipeline-idempotency-key').val();
				$.post(cfg.ajax_url, {
					action: 'maxsafe_action',
					sub_action: 'emergency_override',
					idempotency_key: key,
					reason: reason,
					nonce: cfg.nonce
				}, function(res) {
					if (res.success && res.data.success) {
						alert('Emergency override deployment completed. Logged to audit trail.');
						location.reload();
					} else {
						alert('Override failed: ' + (res.data.message || 'Unknown error'));
					}
				});
			}
		},

		appendTerminalLog: function(msg, level) {
			var $term = $('#maxsafe-live-terminal, #wpsecureos-live-terminal');
			if (!$term.length) return;

			var time = new Date().toLocaleTimeString();
			var cls = 'term-' + (level || 'info');
			var line = '<div class="terminal-line"><span class="term-time">[' + time + ']</span> <span class="' + cls + '">' + msg + '</span></div>';
			$term.append(line);
			$term.scrollTop($term[0].scrollHeight);
		},

		activateLicense: function($btn) {
			var self = this;
			var $keyInput = $('#maxsafe_license_key');
			if (!$keyInput.length) {
				$keyInput = $('#wpsecureos_license_key');
			}
			var key = $keyInput.val().trim();
			var $feedback = $('#license-ajax-feedback');

			if (!key) {
				$feedback.removeClass('success').addClass('error').text('Please enter a valid license key.').fadeIn();
				return;
			}

			$btn.prop('disabled', true).addClass('loading');
			$feedback.hide();
			self.appendTerminalLog('Connecting to licensing server for cryptographic validation...', 'info');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'activate_license',
				license_key: key,
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).removeClass('loading');
				if (res.success && res.data.success) {
					$feedback.removeClass('error').addClass('success').text(res.data.message).fadeIn();
					self.appendTerminalLog('License validated successfully: ' + (res.data.data ? res.data.data.plan_name : 'Active'), 'success');
					setTimeout(function() {
						location.reload();
					}, 1200);
				} else {
					var msg = (res.data && res.data.message) ? res.data.message : 'Activation failed.';
					$feedback.removeClass('success').addClass('error').text(msg).fadeIn();
					self.appendTerminalLog('License activation error: ' + msg, 'error');
				}
			}).fail(function() {
				$btn.prop('disabled', false).removeClass('loading');
				$feedback.removeClass('success').addClass('error').text('Network communication error with server.').fadeIn();
			});
		},

		deactivateLicense: function($btn) {
			var self = this;
			if (!confirm('Are you sure you want to deactivate this license? This will release the site seat and revert to the Community Free plan.')) {
				return;
			}

			$btn.prop('disabled', true).addClass('loading');
			self.appendTerminalLog('Deactivating license and releasing domain seat...', 'warn');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'deactivate_license',
				nonce: cfg.nonce
			}, function(res) {
				if (res.success) {
					self.appendTerminalLog('Seat released. Site returned to Free edition.', 'info');
					location.reload();
				} else {
					$btn.prop('disabled', false).removeClass('loading');
					alert('Deactivation error: ' + (res.data ? res.data.message : 'Unknown error'));
				}
			});
		},

		refreshLicense: function($btn) {
			var self = this;
			$btn.prop('disabled', true).addClass('loading');
			self.appendTerminalLog('Syncing entitlements with central server...', 'info');

			$.post(cfg.ajax_url, {
				action: 'maxsafe_action',
				sub_action: 'refresh_license',
				nonce: cfg.nonce
			}, function(res) {
				$btn.prop('disabled', false).removeClass('loading');
				if (res.success && res.data.valid) {
					self.appendTerminalLog('License status refreshed: ACTIVE', 'success');
					location.reload();
				} else {
					self.appendTerminalLog('License status: ' + (res.data ? res.data.status : 'checked'), 'warn');
					location.reload();
				}
			});
		}
	};

	window.MaxSafe = MaxSafe;
	window.WpSecureOs = MaxSafe;

	$(document).ready(function() {
		MaxSafe.init();
	});

})(jQuery);
