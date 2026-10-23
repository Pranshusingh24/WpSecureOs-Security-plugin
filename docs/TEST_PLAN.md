# WpSecureOs — Test Plan & Quality Assurance

## 1. Test Matrix Overview
Quality assurance covers four distinct testing layers:
1. **Unit Tests:** Deterministic algorithmic verification (risk scoring, capability checks, version parsing, checksum verification).
2. **Integration Tests:** WordPress hook lifecycles, database table creation via dbDelta, Options API persistence, and REST endpoints.
3. **Security Tests:** Nonce bypassing attempts, unauthenticated REST access, SQL injection vectors, path traversal payloads, and secret redaction.
4. **End-to-End Acceptance:** Update staging pipeline flow, visual diff thresholds, emergency override logging, and backup restore runbooks.

---

## 2. Test Execution Plan
| Test Suite | File | Scope | Pass Criteria |
|---|---|---|---|
| **Risk Scoring & Explainability** | `tests/unit/test-risk-assessor.php` | Routine, Important, Critical urgency vs Low, Moderate, High, Unknown confidence. | All edge cases correctly assign non-guarantee labels with contributing reasons. |
| **Capability Enforcement** | `tests/security/test-capabilities.php` | Verification of 7 custom capabilities across Administrator, Editor, Subscriber. | Subscribers receive 403 Forbidden; Admins are authorized. |
| **Core File Checksums** | `tests/unit/test-integrity-monitor.php` | Verification against WordPress checksums API, modified core files detection. | Modified core files flagged; missing files reported. |
| **Vulnerability Schema Validation** | `tests/unit/test-vulnerability-scanner.php` | Malformed feed handling, network outage, rate limits. | System handles feed outage gracefully; displays "Unknown" rather than "Safe". |
| **Backup Archive Integrity** | `tests/integration/test-backup-adapter.php` | Zip archive creation, SHA-256 calculation, read test. | Archive is verified only after successful test extraction. |
| **Safe Update Pipeline FSM** | `tests/integration/test-update-orchestrator.php` | State machine transitions, gate enforcement, approval block. | Pipeline halts at `APPROVAL_REQUIRED` until explicit admin action. |
| **REST Security & Sanitization** | `tests/security/test-rest-security.php` | Missing nonce/token, SQL injection payload in search/sort, XSS in log fields. | Sanitized inputs, prepared statements, unauthorized requests rejected. |

---

## 3. Automated CLI Test Harness
A standalone CLI test runner is provided (`tests/run-tests.php`) that runs without external dependencies, allowing instant verification on any PHP CLI environment.
