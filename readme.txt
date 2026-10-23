=== WpSecureOs — WordPress Security, Safe Updates & Recovery Platform ===
Contributors: pranshusingh, wpsecureos
Tags: security, updates, staging, backup, vulnerability scanner, integrity, safe updates
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform.

== Description ==

**WpSecureOs** is an enterprise-grade security operations and safe update orchestration platform for WordPress. Built for developers, agencies, and enterprise site owners, WpSecureOs assesses update risk with verifiable evidence before touching a live website.

### Core Workflow:
**Assess → Back up → Stage → Test → Approve → Deploy → Verify → Recover**

### Key Features:
* **Explainable Update Risk Assessment:** Replaces black-box percentages with distinct Security Urgency (Routine, Important, Critical) and Deployment Confidence (Low, Moderate, High, Unknown) based on verifiable evidence.
* **Continuous Security Validation:** Official WordPress core file checksum verification via api.wordpress.org, unauthorized executable script detection in uploads, and hardening configuration review.
* **Vulnerability Intelligence Feed:** Built-in provider-agnostic vulnerability scanning for WordPress core, plugins, and themes.
* **Verified Backups & Rollback Runbook:** Tests archive readability and SHA-256 integrity before declaring a backup verified. Documents exact point-in-time recovery steps.
* **Staging & Safe Update Pipeline:** Automated Finite State Machine: preflight check → backup → staging clone → update in staging → automated smoke tests → admin approval gate → production deploy → post-deploy verification.
* **Visual Regression & Smoke Tests:** Monitors critical pages, captures visual diffs with dynamic content masking, checks HTTP 200 statuses, parses PHP error logs, and simulates WooCommerce checkout sandbox flows.
* **Hosting-Honest PHP Runtime Advisor:** Identifies PHP lifecycle status (PHP 8.0, 8.1, 8.2, 8.3, 8.4) and provides hosting-specific execution runbooks for cPanel, Plesk, Cloudways, Kinsta, and WP Engine.
* **Dual Authorization Architecture:** Every privileged action requires both explicit WordPress capabilities (7 custom roles) and CSRF nonce protection.
* **Modern High-Contrast UI:** Sleek, high-contrast dark and light theme, JetBrains Mono telemetry, pulsing validation badges, and interactive pipeline runners.

== Installation ==

1. Upload the `wpsecureos` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **WpSecureOs** menu in the WordPress dashboard.
4. Run your initial system inventory and integrity scan.

== Frequently Asked Questions ==

= Does WpSecureOs guarantee an update is 100% safe? =
No tool can honestly guarantee an update is safe. WpSecureOs provides explainable risk signals, automated staging tests, verified backups, and approval gates so you make confident, informed decisions.

= Does WpSecureOs automatically upgrade PHP on my server? =
No. A WordPress plugin running inside PHP cannot safely recompile or patch the host runtime. WpSecureOs detects PHP versions, tests compatibility, and provides guided runbooks for your hosting provider.

= Will suspicious files in my uploads directory be deleted automatically? =
No. WpSecureOs treats unexpected PHP files in upload folders as security signals for human review, never deleting them automatically to prevent false-positive data loss.

== Changelog ==

= 1.0.0 =
* Initial enterprise release.
* Core file integrity monitoring via WordPress checksums API.
* Explainable risk assessment engine.
* Safe update staging pipeline with approval gates.
* Visual regression and automated smoke test runner.
* Verified backups and recovery runbook.
* Modern dark telemetry console and interactive pipeline runner.
