# WpSecureOs — Architectural Decision Records (ADRs)

## ADR-001: Separation of Security Urgency and Deployment Confidence
- **Context:** Typical WordPress update tools give a single percentage score (e.g. "92% safe"), which gives site owners a false sense of security and leads to site breakage or neglected critical patches.
- **Decision:** Split update evaluation into two distinct dimensions:
  1. **Security Urgency:** `Routine`, `Important`, `Critical/Urgent`.
  2. **Deployment Confidence:** `Low`, `Moderate`, `High`, `Unknown`.
- **Consequences:** Makes risk explainable; administrators can see that an update is urgently needed for security, but confidence is "Unknown" until a staging test and backup are completed.

---

## ADR-002: Dual Capability + Nonce Authorization
- **Context:** Many WordPress plugins rely solely on nonces or generic `manage_options`. Nonces only protect against CSRF, not privilege escalation, and `manage_options` does not allow granular delegation.
- **Decision:** Implement 7 granular capabilities (`wpsecureos_view_dashboard`, `wpsecureos_view_security`, `wpsecureos_manage_settings`, `wpsecureos_run_scan`, `wpsecureos_prepare_update`, `wpsecureos_approve_production`, `wpsecureos_manage_recovery`). Require both capability check and nonce verification on every state-altering action.
- **Consequences:** Prevents lower-level roles from triggering sensitive actions or exposing security details.

---

## ADR-003: No Destructive Auto-Deletion of Suspicious Files
- **Context:** Security scanners that automatically delete unrecognized `.php` files in `wp-content/uploads/` frequently break legitimate plugins, PDF generators, cache engines, or user assets.
- **Decision:** WpSecureOs flags suspicious executable scripts as security signals requiring manual review. No automatic file deletion is performed in the default configuration.
- **Consequences:** Eliminates accidental site destruction from false positives while still providing clear threat visibility.

---

## ADR-004: Enterprise Cybersecurity Color & Design System Integration
- **Context:** The standard WordPress admin interface is cluttered and dated. Modern security operations require high visual clarity, immediate severity recognition, and smooth telemetry.
- **Decision:** Adopt high-contrast cybersecurity aesthetics, featuring Inter and JetBrains Mono typography, slate light and dark cards, Electric Cyan (`#0ea5e9`) accent glow, continuous validation status indicators, and clear severity tokens.
- **Consequences:** Delivers an enterprise-grade, stunning UX that feels like a modern SaaS security console directly inside WordPress.

---

## ADR-005: Hosting-Honest PHP Runtime Policy
- **Context:** A WordPress plugin running in PHP cannot safely upgrade the PHP interpreter on a remote server.
- **Decision:** Detect PHP lifecycle status (PHP 8.0, 8.1, 8.2, 8.3, 8.4), compute plugin/theme compatibility, and provide hosting-specific runbooks (cPanel, Plesk, Cloudways, Kinsta, WP Engine) rather than attempting unsafe shell scripts.
- **Consequences:** Protects server stability and sets honest expectations for administrators.
