# CRX CRM — Enterprise Multi-Tenant CRM Platform

[![Live Demo & Landing Page](https://img.shields.io/badge/Live_Landing_Page-GitHub_Pages-6366f1?style=for-the-badge&logo=github)](https://blackmoon87.github.io/CRX.CRM/)
[![License: MIT](https://img.shields.io/badge/License-MIT-06b6d4?style=for-the-badge)](https://opensource.org/licenses/MIT)
[![Memory Footprint](https://img.shields.io/badge/RAM_Footprint-2.0_MB-10b981?style=for-the-badge)](https://blackmoon87.github.io/CRX.CRM/#benchmarks)

> **CRX CRM** is a high-performance, enterprise-grade Customer Relationship Management platform built on the **Spartan Lightweight PHP 8.2+ MVC Architecture**. Designed for multi-tenant organizations requiring speed, granular RBAC security, CPQ proposal automation, and AI agent integration.
>
> 🌐 **Official Live Landing Page**: [https://blackmoon87.github.io/CRX.CRM/](https://blackmoon87.github.io/CRX.CRM/)

---

## 🚀 Key Features

### 🏢 Multi-Tenant Workspace Architecture
- **Complete Tenant Isolation**: Every record (companies, contacts, deals, tasks, custom fields) is strictly scoped to `workspace_id`.
- **Seamless Workspace Switching**: Instant workspace switching from top navigation with persistent session state.

### 👥 Core CRM Entities
- **Companies & Accounts**: Comprehensive profile management with 5-part international postal addresses, annual revenue tracking, industry segmentation, and domain discovery.
- **People (Contacts)**: Lead lifecycle tracking, direct company linking, and phone/email communication logs.
- **Opportunities & Sales Pipelines**: Interactive Kanban drag-and-drop boards, deal rotting indicators (colored visual warnings for idle deals), win/loss reason tracking, and weighted pipeline forecasting.
- **Tasks & Omnichannel Timeline**: High-priority task assignment, interactive status toggling, chronological interaction feeds (calls, meetings, emails), and RFC 5545 `.ics` calendar appointment exports.
- **Upcoming Reminder Alarms**: Live background polling endpoint (`/api/upcoming-alarms`) for meeting notifications.

### 💼 CPQ & Digital Proposal Acceptance
- **Configure, Price, Quote (CPQ)**: Multi-item quote generation with dynamic subtotal, custom percentage taxes, discounts, and currency management.
- **Public Client Proposals**: Secure, tokenized public proposal URLs (`/quote/{public_token}`) requiring no login.
- **Digital Signature Acceptance**: Clients can review proposals and digitally sign with audit timestamps (`/quote/{token}/accept`).

### 🧩 Dynamic Custom Fields Engine
- Support for **12 flexible field types**:
  - `text`, `textarea`, `number`, `currency`, `date`, `boolean`
  - `select`, `multi_select`, `email`, `phone`, `url`, `rating` (1-5 stars)
- Available across all primary entities (Companies, Contacts, Opportunities).
- Integrated into table views, detail pages, and CSV export streams.

### 🛡️ Enterprise RBAC & Granular Permissions
- **Predefined Roles**: Owner, Admin, Member, Restricted Viewer.
- **Custom Role Engine**: Workspace owners can create custom roles with granular checkboxes for entity-level permissions (`companies.create`, `contacts.delete`, `deals.export`, etc.).
- Secure authorization middleware enforced across all HTTP routes and API endpoints.

### 🔀 Smart Duplicate Detection & Merge Engine
- Automated duplicate detection by domain or email address.
- 1-Click consolidation modal re-parenting all linked deals, contacts, tasks, and notes to the primary record before soft-deleting the duplicate.

### ♻️ Recycle Bin & Soft Deletes
- Non-destructive deletion across all core entities with `deleted_at` audit timestamps.
- Dedicated Recycle Bin UI (`/settings/recycle-bin`) allowing immediate restoration or permanent purge.

### 📊 Reports & Sales Velocity Analytics
- Sales velocity metrics, conversion funnels, win/loss breakdowns, and date-range filtered reporting with CSV export capabilities.

### 🌍 Internationalization (i18n) & RTL Ready
- Native support for **English (`en`)**, **Arabic (`ar` with full RTL layout)**, and **French (`fr`)**.
- Instant locale switching via `/locale/{lang}`.

### 🤖 AI Agent Integration (MCP) & REST API v1
- **Model Context Protocol (MCP)**: Embedded JSON-RPC agent server at `/api/mcp` allowing AI agents to query records, trigger automations, and manage CRM entities.
- **REST API v1**: Token-authenticated RESTful API (`/api/v1/...`) supporting Bearer authentication via Personal Access Tokens.

---

## 🛠️ System Requirements

- **PHP**: 8.2 or higher (PHP 8.3 recommended)
- **PHP Extensions**: `pdo`, `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `json`, `curl`
- **Composer** (Dependency Manager)
- **Web Server**: Built-in PHP server, Apache, Nginx, or Laragon

---

## ⚡ Quick Start & Installation

### 1. Clone the Repository
```bash
git clone https://github.com/blackmoon87/CRX.CRM.git
cd CRX.CRM
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
```bash
cp .env.example .env
```
*(By default, SQLite is configured at `storage/database.sqlite`)*

### 4. Run Migrations & Seed Database
```bash
php spartan migrate
php spartan db:seed
```

### 5. Start the Development Server
```bash
php -S 127.0.0.1:8000 -t public public/index.php
```
Open your browser and navigate to **`http://127.0.0.1:8000`**.

---

## 🔑 Default Seed Accounts

| Role | Email | Password |
|---|---|---|
| **System Admin / Owner** | `admin@crx.local` | `password123` |
| **Workspace Member** | `user@crx.local` | `password123` |

---

## ⚔️ Architectural Comparison Matrix

| Capability / Metric | **CRX CRM** (Spartan MVC) | **Twenty CRM** (NestJS / React) | **Laravel / Filament CRM** | **HubSpot / Salesforce** |
|---|---|---|---|---|
| **Base Memory (RAM)** | **`~2.0 MB`** ⚡ | `~220 - 350 MB` | `~35 - 55 MB` | Cloud-only ($$$) |
| **Framework Boot Time** | **`< 1.5 ms`** | `~1,100 ms` | `~55 - 80 ms` | Closed SaaS |
| **API Response Latency** | **`~18 - 25 ms`** | `~85 - 180 ms` | `~60 - 120 ms` | `~150 - 450 ms` |
| **Database Operations** | **`64,000+ ops/sec`** (WAL) | `~4,500 ops/sec` | `~8,000 ops/sec` | Rate-limited API |
| **Native AI Protocol** | **Native MCP Server (`/api/mcp`)** | Webhooks / REST only | Manual implementation | Proprietary Einstein ($$$) |
| **CPQ Proposals & E-Sign**| **Built-in Native & Public Link** | Third-party integrations | Custom package needed | Expensive Tier ($150+/mo) |
| **Multi-Tenant Workspaces**| **Native `workspace_id` Isolation** | Complex PostgreSQL schemas | Requires tenancy packages | Multi-org ($$$$) |
| **Deployment Complexity** | **Zero-config (PHP + SQLite/MySQL)** | 6+ Docker containers mandatory | PHP + Redis + Queue worker | Closed Vendor Lock-in |
| **License & Sovereignty** | **100% MIT Open Source** | AGPL-3.0 / Commercial | MIT / Paid Plugins | Proprietary ($25-$300/user/mo) |

---

## 📊 Performance Benchmarks & Latency Audit

> Audited directly against live development server running on PHP 8.3 + SQLite in **WAL mode (`PRAGMA journal_mode = WAL`)**.

```
=========================================================================
CRX CRM - PERFORMANCE BENCHMARK & LATENCY AUDIT
=========================================================================

--- 1. Database Operations (SQLite WAL Engine) ---
  • Read Query Latency:    0.016 ms  (64,131 queries/sec)
  • Write Batch Latency:   0.013 ms  (78,456 writes/sec)

--- 2. End-to-End HTTP Request Latencies (Live Server) ---
  • Public Login Page        Avg:  21.47 ms | Min:  15.25 ms | Max:  37.47 ms
  • REST API Summary (JSON)  Avg:  22.75 ms | Min:  18.47 ms | Max:  27.47 ms
  • Dashboard Overview       Avg:  26.10 ms | Min:  22.64 ms | Max:  33.22 ms
  • Deals Pipeline (HTML)    Avg:  32.92 ms | Min:  26.41 ms | Max:  41.57 ms
  • Companies Grid (HTML)    Avg:  33.53 ms | Min:  25.42 ms | Max:  58.13 ms

--- 3. Memory Consumption ---
  • Base Process Memory:   2.00 MB
  • Peak Execution Memory: 2.00 MB
=========================================================================
```

---

## 🧪 Testing & Verification

CRX includes end-to-end stress testing, route verification, and performance benchmark suites:

```bash
# Verify all 54 system routes
php scratch/test_all_routes.php

# Run full heavy stress test (14 enterprise scenarios on Company #2)
php scratch/heavy_stress_test.php

# Run performance & latency benchmark audit
php scratch/benchmark.php
```

---

## 📄 License

This software is released under the **MIT License**.
