# WpSecureOs — Product Requirements Document (PRD)

## 1. Document Overview
- **Product Name:** WpSecureOs (WordPress Security, Safe Updates & Recovery Platform)
- **Plugin URI:** https://wpsecureos.com/
- **Author:** Pranshu Singh
- **Version:** 1.0.0
- **Target Audience:** Freelance developers, WordPress agencies, IT operations, and site owners managing single or multi-site WordPress environments.

---

## 2. Problem Statement
WordPress powers over 40% of the web, but updating core, themes, and plugins remains one of the primary vectors for site breakage, downtime, and security vulnerabilities. Typical pain points include:
1. **Fear of Updating:** Site administrators delay security updates due to fear of breaking customer checkouts, theme styling, or PHP fatal errors.
2. **Black-box Risk Scores:** Existing tools display arbitrary percentage scores without actionable, explainable evidence.
3. **Unverified Backups:** Backup plugins declare a backup successful simply because an archive was written, without testing archive readability or database restorability.
4. **Lack of Staging & Visual Validation:** Updates are pushed directly to production without testing checkout funnels or comparing critical URLs visually.
5. **Hosting & PHP Runtime Disconnect:** A plugin cannot magically patch server PHP runtime versions; pretending it can leads to misconfiguration and broken sites.

---

## 3. Product Vision & Principles
**Core Workflow:**  
$$\text{Assess} \longrightarrow \text{Back up} \longrightarrow \text{Stage} \longrightarrow \text{Test} \longrightarrow \text{Approve} \longrightarrow \text{Deploy} \longrightarrow \text{Verify} \longrightarrow \text{Recover}$$

### Guiding Principles:
1. **Explainable Risk:** Never provide a black-box guarantee. Provide distinct **Security Urgency** (Routine, Important, Critical) and **Deployment Confidence** (Low, Moderate, High, Unknown) backed by verifiable evidence.
2. **Defense in Depth:** Verify official WordPress core checksums, scan for suspicious PHP uploads, monitor file baselines, audit authentication events, and enforce security headers.
3. **Safety First:** Normal production updates require both backup verification and staging test validation. An emergency override requires dedicated capabilities and an audited rationale.
4. **No Destructive Automation:** Suspicious files in user uploads are flagged for human review, never silently or automatically deleted.
5. **Hosting Honesty:** Clearly inform administrators when PHP changes require hosting control panel intervention.

---

## 4. Key Functional Modules
| Module | Component | Description |
|---|---|---|
| **Inventory & Health** | `WpSecureOs_Inventory` | Gathers WP core, PHP runtime, DB version, installed plugins/themes, active theme, HTTPS status, debug constants, and server environment without leaking secrets. |
| **Risk Assessor** | `WpSecureOs_Risk_Assessor` | Evaluates update risk using changelogs, CVE advisories, PHP compatibility, backup recency, and staging test results. |
| **Vulnerability Intelligence** | `WpSecureOs_Security_Scanner` | Connects to trusted vulnerability feeds (WPScan / OSV / NIST schemas) with local caching, rate limiting, and untrusted data validation. |
| **Integrity Monitor** | `WpSecureOs_Integrity_Monitor` | Compares core files against official WordPress checksums API (`api.wordpress.org`), flags modified/missing files, and inspects uploads directory for unauthorized executable scripts. |
| **Audit & Event Logger** | `WpSecureOs_Event_Logger` | Records authentication events, failed logins with rate limiting, administrative changes, and update jobs in custom high-performance tables with secret redaction. |
| **Backup Adapter** | `WpSecureOs_Backup_Adapter` | Generates full database + files manifests, tests archive readability, computes SHA-256 hashes, and documents point-in-time recovery runbooks. |
| **Staging & Safe Update Orchestrator** | `WpSecureOs_Staging_Adapter` & `WpSecureOs_Test_Runner` | Finite State Machine managing preflight checks, staging environment creation, staging update execution, automated smoke tests, approval gates, and production rollout. |
| **Visual Regression & Smoke Tests** | `WpSecureOs_Visual_Test_Adapter` | Automated smoke tests for critical URLs, HTTP 200 status checks, PHP error log parsing, WooCommerce checkout sandbox simulation, and visual regression diff comparison with configurable dynamic masking. |
| **PHP Runtime Advisor** | `WpSecureOs_Php_Advisor` | Official PHP lifecycle tracking (Active, Security Only, End of Life), plugin/theme minimum PHP requirements parser, and hosting provider execution runbooks (cPanel, Plesk, Cloudways, Kinsta, WP Engine). |
| **Central Control Plane Connector** | `WpSecureOs_Central_Connector` | REST API routes under `/wp-json/wpsecureos/v1/` with token authentication, HMAC signing, and tenant isolation for optional multi-site central management. |

---

## 5. User Interface Experience (Enterprise Cybersecurity UI)
- **Typography:** Inter for clean user interface text, JetBrains Mono for telemetry, hashes, version numbers, and log output.
- **Palette:** Crisp modern slate/white surfaces, high-contrast dark telemetry cards, Electric Cyan (`#0ea5e9`) accent glow, and standard severity tokens (Critical `#ef4444`, High `#f97316`, Medium `#eab308`, Low `#0ea5e9`, Unknown `#64748b`).
- **Interactive Shell:** Tabbed navigation (Overview, Updates, Security, Backups, Staging & Tests, Logs, Settings), interactive update pipeline modal with real-time step execution, visual diff comparison viewer, and instant scan actions via WordPress AJAX / REST.
