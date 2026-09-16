# CRX CRM — Enterprise Multi-Tenant CRM Platform

> **CRX CRM** is a high-performance, enterprise-grade Customer Relationship Management platform built on the **Spartan Lightweight PHP 8.2+ MVC Architecture**. Designed for multi-tenant organizations requiring speed, granular RBAC security, CPQ proposal automation, and AI agent integration.

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

## 🧪 Testing & Verification

CRX includes end-to-end stress testing and route verification test suites:

```bash
# Verify all 54 system routes
php scratch/test_all_routes.php

# Run full heavy stress test (14 enterprise scenarios on Company #2)
php scratch/heavy_stress_test.php
```

---

## 📄 License

This software is released under the **MIT License**.
