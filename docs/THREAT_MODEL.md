# WpSecureOs — Threat Model & Risk Analysis

## 1. System Scope & Assets
WpSecureOs operates within WordPress single-site and multisite networks, maintaining security signals, staging workflows, and update orchestration.

### Critical Assets:
1. **Site Database & Credentials:** `wp-config.php`, `DB_PASSWORD`, auth keys/salts, user password hashes.
2. **File System Integrity:** WordPress core binaries, theme files, plugin files, `.htaccess`, `nginx.conf`.
3. **Audit Trails & Security Events:** Authentication history, failed login counters, IP ban tables.
4. **Backup Archives:** Database dumps and site file zips containing customer and configuration data.
5. **Central Dashboard Connector Credentials:** Pairing tokens and HMAC API secrets.

---

## 2. Threat Actors & Scenarios
| Threat Actor | Motivation | Attack Vector | Mitigation in WpSecureOs |
|---|---|---|---|
| **Unauthenticated External Attacker** | Credential stuffing, brute force, remote code execution (RCE) | POST `/wp-login.php`, unauthorized REST calls | Rate limiting on login attempts with IP throttling, restrictive REST API `permission_callback` checking nonces/capabilities, no executable uploads permitted. |
| **Malicious Authenticated Subscriber / Contributor** | Privilege escalation | Forged admin requests, IDOR on REST endpoints | Nonce validation alone is NOT trusted. Every endpoint strictly requires explicit capabilities (e.g. `wpsecureos_approve_production`, `wpsecureos_run_scan`). |
| **Compromised Third-Party Plugin / Feed** | Supply chain compromise, tainted vulnerability feeds | Malformed JSON responses, XSS injection via feed data, malicious update payloads | Treat all feed data as untrusted data. Strict JSON schema validation, output escaping with `esc_html`/`esc_attr`, no `eval()` or dynamic file execution. |
| **Path Traversal / Archive Extraction Attack** | Overwriting critical system files (`wp-config.php`) | Malicious ZIP containing `../../` entries in backup or update packages | Strict directory allowlisting (`ABSPATH`, `WP_CONTENT_DIR`), path canonicalization using `realpath()`, zip entry sanitization to prevent Zip Slip vulnerabilities. |
| **Server Side Request Forgery (SSRF)** | Probing internal cloud metadata (e.g. AWS `169.254.169.254`) | Custom webhook URLs or visual testing endpoints | URL validation via `wp_http_validate_url()`, rejection of private/reserved IP blocks (RFC 1918, link-local) for external outbound requests. |
| **Data Leakage in Reports / Logs** | Exposing customer PII, secrets, or database passwords | Debug dumps, audit log exports, visual regression diffs | Automated redaction of sensitive patterns (`DB_PASSWORD`, `AUTH_KEY`, `secret`, `token`, `bearer`, credit card patterns). Database passwords and salts are never saved to event logs. |

---

## 3. Explicit Out-of-Scope Boundaries
1. **Direct Kernel / Host PHP Patching:** WpSecureOs does NOT run root/shell scripts to replace the server PHP binary. It detects and advises, directing administrators to hosting control panels or authorized provider APIs.
2. **Auto-Deletion of Uploads:** Suspicious files in upload directories are flagged as security signals; WpSecureOs does NOT unilaterally delete user files, preventing disastrous false-positive data loss.
3. **Guaranteed 100% Exploit Prevention:** No tool guarantees total safety. WpSecureOs explicitly provides risk signals, evidence-backed scores, and recovery runbooks rather than false assurances.
