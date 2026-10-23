# WpSecureOs — Technical Architecture

## 1. High-Level Architecture Overview
WpSecureOs is built with a modular, decoupled architecture adhering to WordPress Coding Standards (WPCS) and modern object-oriented PHP patterns.

```
                           +----------------------------------------+
                           |     WpSecureOs Enterprise Admin UI     |
                           |  (Overview, Updates, Security, Tests)  |
                           +-------------------+--------------------+
                                               |
                   +---------------------------+---------------------------+
                   |                                                       |
        [ Admin Menu & Notices ]                               [ REST API Controller ]
                   |                                                       |
+------------------+-------------------------------------------------------+------------------+
|                                    WpSecureOs Plugin Core                                   |
+------------------+-------------------+-------------------+-------------------+--------------+
|                  |                   |                   |                   |              |
|  Inventory       |  Risk Assessor    |  Security Scanner |  Integrity        |  Event &     |
|  & Environment   |  & Evidence Engine|  & Vuln Feeds     |  Monitor          |  Audit Log   |
+------------------+-------------------+-------------------+-------------------+--------------+
|                  |                   |                   |                   |              |
|  Backup Adapter  |  Staging Adapter  |  Test Runner      |  Visual Test      |  PHP Runtime |
|  & Verification  |  & Isolation      |  (FSM Pipeline)   |  Worker & Diffs   |  Advisor     |
+------------------+-------------------+-------------------+-------------------+--------------+
                   |                                                       |
          [ Custom DB Tables ]                                    [ External Feeds ]
          - wpsecureos_events                                     - api.wordpress.org checksums
          - wpsecureos_scans                                      - Vulnerability feeds (WPScan)
          - wpsecureos_jobs                                       - Central Dashboard (optional)
          - wpsecureos_backups
```

---

## 2. Safe Update Finite State Machine (FSM)
The update workflow is managed as a strictly validated, stateful pipeline:

```
[ IDLE ]
   │
   ▼
[ QUEUED ] ───▶ [ PREFLIGHT ] 
                    │ (Disk space, capabilities, PHP compatibility)
                    ▼
               [ BACKUP_CREATE ]
                    │ (Database dump, file manifest, archive SHA-256 test)
                    ▼
               [ STAGING_CLONE ]
                    │ (Sandbox emails, mock payments, noindex headers)
                    ▼
               [ APPLY_STAGING_UPDATE ]
                    │ (Apply candidate update in staging only)
                    ▼
               [ RUN_AUTOMATED_TESTS ]
                    │ (HTTP 200, PHP error logs, REST health, WooCommerce checkout)
                    ▼
               [ APPROVAL_REQUIRED ] ◀── [ Gate: Admin Review & Sign-Off ]
                    │ (User inspects visual diffs & test evidence)
                    ├───▶ [ EMERGENCY_OVERRIDE ] (Audited bypass if critical)
                    ▼
               [ PRODUCTION_DEPLOY ]
                    │
                    ▼
               [ POST_VERIFY ] ───▶ [ FAILED / RECOVERY_REQUIRED ]
                    │
                    ▼
               [ COMPLETED ]
```

---

## 3. Database Schema
Created via `dbDelta()` in `WpSecureOs_Activator`:

1. **`wp_wpsecureos_events`**: High-performance audit log for login events, scan triggers, updates, configuration changes.
   - Columns: `id (BIGINT PK)`, `event_type (VARCHAR 64)`, `severity (VARCHAR 20)`, `actor_user_id (BIGINT)`, `ip_address (VARCHAR 45)`, `component_ref (VARCHAR 128)`, `message (TEXT)`, `metadata_json (LONGTEXT)`, `created_at (DATETIME)`.
2. **`wp_wpsecureos_scans`**: Historical record of security scans, core checksum integrity, and vulnerability checks.
   - Columns: `id (BIGINT PK)`, `scan_type (VARCHAR 32)`, `status (VARCHAR 20)`, `started_at (DATETIME)`, `completed_at (DATETIME)`, `summary_json (LONGTEXT)`.
3. **`wp_wpsecureos_jobs`**: State machine tracker for safe update pipeline jobs, lock timestamps, and idempotency tokens.
   - Columns: `id (BIGINT PK)`, `job_type (VARCHAR 64)`, `status (VARCHAR 32)`, `idempotency_key (VARCHAR 64)`, `current_step (VARCHAR 64)`, `payload_json (LONGTEXT)`, `result_json (LONGTEXT)`, `created_at (DATETIME)`, `updated_at (DATETIME)`.
4. **`wp_wpsecureos_backups`**: Metadata for verified backup archives and restore verification flags.
   - Columns: `id (BIGINT PK)`, `backup_name (VARCHAR 128)`, `file_path (VARCHAR 255)`, `archive_size (BIGINT)`, `checksum_sha256 (VARCHAR 64)`, `db_included (TINYINT)`, `files_included (TINYINT)`, `is_verified (TINYINT)`, `verification_notes (TEXT)`, `created_at (DATETIME)`.

---

## 4. Roles & Capabilities Matrix
| Capability | Administrator | Editor | Author | Subscriber | Description |
|---|:---:|:---:|:---:|:---:|---|
| `wpsecureos_view_dashboard` | Yes | Optional | No | No | View general status, health scores, and public notices |
| `wpsecureos_view_security` | Yes | No | No | No | View vulnerability CVEs, file integrity diffs, and audit logs |
| `wpsecureos_run_scan` | Yes | No | No | No | Trigger on-demand security and file integrity scans |
| `wpsecureos_prepare_update` | Yes | No | No | No | Trigger staging creation, backup, and automated smoke tests |
| `wpsecureos_approve_production`| Yes | No | No | No | Authorize production deployment and emergency overrides |
| `wpsecureos_manage_recovery` | Yes | No | No | No | Initiate restore runbook and rollback actions |
| `wpsecureos_manage_settings` | Yes | No | No | No | Change vulnerability feeds, API tokens, and webhook URLs |
