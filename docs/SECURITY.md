# WpSecureOs — Security Policy & Guidelines

## 1. Authentication & Authorization Standard
1. **Never Rely on Nonce Alone:** A WordPress nonce prevents Cross-Site Request Forgery (CSRF), but does NOT grant authorization. Every administrative request, AJAX action, and REST route must verify:
   ```php
   // Dual validation check:
   check_admin_referer('wpsecureos_action_nonce', 'nonce');
   if (!current_user_can('wpsecureos_approve_production')) {
       wp_die(__('Unauthorized action attempt.', 'wpsecureos'), 403);
   }
   ```
2. **REST Route Protection:** All REST API endpoints declare an explicit `permission_callback`. No open endpoints exist unless specifically designated for public health checks, which return zero sensitive data.

---

## 2. Input Sanitization & Output Escaping
- **Database Queries:** All database access utilizes `$wpdb->prepare()` with explicit `%d`, `%s`, and `%f` specifiers. No concatenated user input is permitted in SQL strings.
- **Output Escaping:** Context-aware escaping is strictly enforced:
  - HTML text: `esc_html()`
  - Tag attributes: `esc_attr()`
  - URLs: `esc_url()`
  - JavaScript variables: `wp_json_encode()`
  - Translatable strings: `esc_html__()`, `esc_attr_e()`

---

## 3. Secret Protection & PII Masking
- Database credentials, `NONCE_SALT`, `AUTH_KEY`, central bearer tokens, and customer personal details are scrubbed before writing to logs, reports, or REST payloads.
- Secret patterns are automatically masked:
  ```php
  preg_replace('/(password|secret|token|key|salt)["\']?\s*[:=]\s*["\']?([^"\'\s]+)/i', '$1: [REDACTED]', $content);
  ```

---

## 4. Path Traversal & Zip Slip Prevention
- When handling backup archives or file integrity scans, paths are resolved using `wp_normalize_path(realpath($path))` and compared against allowlisted root directories (`ABSPATH`, `WP_CONTENT_DIR`).
- Archive extraction explicitly inspects entry names to prevent parent directory traversal (`../`).

---

## 5. Untrusted Feed Validation
- All vulnerability and external threat intelligence feeds are treated as untrusted third-party inputs.
- Payloads are decoded with JSON schema checks. Malformed or excessively large (>2MB) feeds are safely rejected. Outages fall back to "Unknown" status rather than falsely assuming "Safe".
