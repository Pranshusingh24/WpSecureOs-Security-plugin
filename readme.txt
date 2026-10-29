=== MaxSafe ===
Contributors: pranshusingh
Tags: security, safe updates, staging, backup, vulnerability scanner
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade WordPress Security, Continuous Validation, Safe Updates, and Recovery Platform.

== Description ==

**MaxSafe** is an enterprise-grade security operations and safe update orchestration platform for WordPress. Built for developers, agencies, and enterprise site owners, MaxSafe assesses update risk with verifiable evidence before touching a live website.

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

1. Upload the `maxsafe` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **MaxSafe** menu in the WordPress dashboard.
4. Run your initial system inventory and integrity scan.

== Frequently Asked Questions ==

= Does MaxSafe guarantee an update is 100% safe? =
No tool can honestly guarantee an update is safe. MaxSafe provides explainable risk signals, automated staging tests, verified backups, and approval gates so you make confident, informed decisions.

= Does MaxSafe automatically upgrade PHP on my server? =
No. A WordPress plugin running inside PHP cannot safely recompile or patch the host runtime. MaxSafe detects PHP versions, tests compatibility, and provides guided runbooks for your hosting provider.

= Will suspicious files in my uploads directory be deleted automatically? =
No. MaxSafe treats unexpected PHP files in upload folders as security signals for human review, never deleting them automatically to prevent false-positive data loss.

= Where are screenshots and visual assets located? =
Plugin screenshots are stored in the WordPress.org Subversion `/assets/` directory and within the plugin's `assets/images/` folder.

== Screenshots ==

1. Overview Dashboard — Unified telemetry displaying real-time security posture, core checksum verification status, pending updates risk signals, and runtime health.
2. License & Subscription Telemetry — Cryptographic license activation, site quota management, and plan tiers.
3. Security & File Integrity — Official WordPress core checksums validation against api.wordpress.org signatures and uploads directory executable script detection.
4. Platform Settings & Integrations — Threat intelligence feed configuration, staging visual regression tolerance thresholds, and fleet central control plane pairing.
5. Staging Sandbox & Automated Smoke Tests — Isolated test environment posture (email suppression, indexing prevention, mock checkout) and automated REST API and WooCommerce checkout tests.
6. Explainable Update Risk Assessment — Multi-factor risk engine showing Security Urgency, Deployment Confidence, granular risk factors, and guided safe deployment workflows.

== Changelog ==

= 1.1.0 =
* WordPress.org Directory Compliance: Renamed plugin to MaxSafe with unique slug maxsafe.
* Production Release: Pricing, Subscription, and License Management System.
* Support for Free, Pro, Agency, and Business tier entitlements.
* Core security preservation invariant: emergency recovery runbooks and file integrity scans remain permanently active upon expiry.
* Cryptographic Ed25519 and HMAC-SHA256 license signature verification.
* Authenticated plugin update delivery engine hooked into WordPress update transients.
* Customer license management screen with interactive pricing matrix and telemetry cards.
* 7-day offline grace period for uninterrupted operation during network outages.
* Brand identity enhancements with SVG vector logos, security favicons, and updated metadata.

= 1.1.0-beta =
* Beta Release.
* Full responsive design across all viewports: mobile phones, tablets, laptops, and desktop displays.
* Isolated WordPress admin notices with standard screen-reader heading and wp-header-end anchor.
* Responsive horizontal touch-scrolling wrappers for all security and update data tables.
* Clean tab wrapping and adaptive 2-column mobile grid navigation.
* Responsive filter forms, modal viewports, and audit trails.

= 1.0.0 =
* Initial enterprise release.
* Core file integrity monitoring via WordPress checksums API.
* Explainable risk assessment engine.
* Safe update staging pipeline with approval gates.
* Visual regression and automated smoke test runner.
* Verified backups and recovery runbook.
* Modern dark telemetry console and interactive pipeline runner.

== Upgrade Notice ==

= 1.1.0 =
Production Release: Renamed to MaxSafe for WordPress.org compliance. Enhanced licensing architecture, cryptographic verification, and complete responsive telemetry console. Recommended update for all installations.
