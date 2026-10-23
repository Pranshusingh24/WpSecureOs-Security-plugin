/**
 * WpSecureOs Interactive Admin Console
 */

(function($) {
	'use strict';

	var WpSecureOs = {
		init: function() {
			this.bindEvents();
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
			$(document).on('click', '.wpsecureos-modal-close, .btn-modal-cancel', function(e) {
				e.preventDefault();
				$('.wpsecureos-modal-overlay').fadeOut(150);
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
			$(document).on('click', '.wpsecureos-admin-notice .notice-dismiss', function() {
				var noticeId = $(this).closest('.wpsecureos-admin-notice').data('notice-id');
				if (noticeId) {
					$.post(wpsecureos_vars.ajax_url, {
						action: 'wpsecureos_dismiss_notice',
						nonce: wpsecureos_vars.nonce,
						notice_id: noticeId
					});
				}
			});
		},

		showToast: function(msg, isError) {
			$('.wpsecureos-toast').remove();
			var border = isError ? '#ef4444' : '#0ea5e9';
			var $toast = $('<div class="wpsecureos-toast" style="border-left-color:' + border + '"><span>' + msg + '</span></div>');
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

			$.post(wpsecureos_vars.ajax_url, {
				action: 'wpsecureos_action',
				sub_action: 'run_scan',
				nonce: wpsecureos_vars.nonce
			}, function(res) {
				$btn.prop('disabled', false).html(originalText);
				if (res.success) {
					self.appendTerminalLog('Security scan completed. Vulnerabilities: ' + res.data.security.total_findings + ' findings.', 'success');
					self.appendTerminalLog('Core file integrity verification: ' + res.data.integrity.core_status.toUpperCase(), res.data.integrity.is_clean ? 'success' : 'warn');
					self.showToast(wpsecureos_vars.strings.scan_complete);
					setTimeout(function() { location.reload(); }, 1200);
				} else {
					self.appendTerminalLog('Scan error: ' + (res.data.message || 'Server error'), 'error');
					self.showToast(wpsecureos_vars.strings.action_failed, true);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html(originalText);
				self.showToast(wpsecureos_vars.strings.action_failed, true);
			});
		},

		triggerBackup: function($btn) {
			var self = this;
			var orig = $btn.html();
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Backing up...');

			self.appendTerminalLog('Exporting database & building verified ZIP archive manifest...', 'info');

			$.post(wpsecureos_vars.ajax_url, {
				action: 'wpsecureos_action',
				sub_action: 'create_backup',
				nonce: wpsecureos_vars.nonce
			}, function(res) {
				$btn.prop('disabled', false).html(orig);
				if (res.success) {
					self.appendTerminalLog('Archive generated: ' + res.data.backup_name + ' (' + res.data.size + ')', 'success');
					self.appendTerminalLog('SHA-256 Checksum: ' + res.data.sha256, 'info');
					self.appendTerminalLog('Archive Integrity Status: ' + (res.data.verified ? 'PASSED & READABLE' : 'FAILED'), res.data.verified ? 'success' : 'error');
					self.showToast(wpsecureos_vars.strings.backup_verified);
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					self.appendTerminalLog('Backup creation failed: ' + res.data.message, 'error');
					self.showToast(res.data.message, true);
				}
			}).fail(function() {
				$btn.prop('disabled', false).html(orig);
				self.showToast(wpsecureos_vars.strings.action_failed, true);
			});
		},

		openPipelineModal: function(type, slug, ver, name) {
			$('#modal-pipeline-name').text(name);
			$('#modal-pipeline-slug').text(slug);
			$('#modal-pipeline-ver').text(ver);
			$('#modal-pipeline-type').val(type);

			// Reset stepper
			$('.wpsecureos-step-item').removeClass('active completed');
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

			$.post(wpsecureos_vars.ajax_url, {
				action: 'wpsecureos_action',
				sub_action: 'start_pipeline',
				component_type: type,
				slug: slug,
				new_version: ver,
				nonce: wpsecureos_vars.nonce
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

			$.post(wpsecureos_vars.ajax_url, {
				action: 'wpsecureos_action',
				sub_action: 'approve_deploy',
				idempotency_key: key,
				nonce: wpsecureos_vars.nonce
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
			var proceed = confirm(wpsecureos_vars.strings.confirm_override);
			if (!proceed) return;

			var reason = prompt('Document mandatory operational rationale for bypassing staging gates:');
			if (reason && reason.trim().length > 0) {
				var key = $('#pipeline-idempotency-key').val();
				$.post(wpsecureos_vars.ajax_url, {
					action: 'wpsecureos_action',
					sub_action: 'emergency_override',
					idempotency_key: key,
					reason: reason,
					nonce: wpsecureos_vars.nonce
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
			var $term = $('#wpsecureos-live-terminal');
			if (!$term.length) return;

			var time = new Date().toLocaleTimeString();
			var cls = 'term-' + (level || 'info');
			var line = '<div class="terminal-line"><span class="term-time">[' + time + ']</span> <span class="' + cls + '">' + msg + '</span></div>';
			$term.append(line);
			$term.scrollTop($term[0].scrollHeight);
		}
	};

	$(document).ready(function() {
		WpSecureOs.init();
	});

})(jQuery);
