# CRX Pro CRM: 100% Feature Parity & Engineering Tasks Specification

This document is the master engineering specification and task tracking sheet to elevate **CRX** to 100% feature completeness against top-tier industry CRMs (**Attio, HubSpot, Folk, Pipedrive, Close, and Salesforce**).

---

## Architecture Principles
1. **Zero External Runtime Dependencies**: Pure PHP 8.2+ MVC on the Spartan engine.
2. **Spartan 3D Isometric Design System** (Rules 1143–1240): High-contrast tactile blocks, 2px solid borders, directional hard shadows (`2px 2px 0 var(--border)`), bevel highlights, zero blurred drop shadows.
3. **Agent-Native First**: Every entity mutation, filter, and action must remain accessible to MCP AI tools as well as human users.
4. **Data Safety**: Never perform hard deletes from user tables; all deletions route through soft-delete recycling.
5. **High Performance**: Vanilla JS micro-controllers (< 15KB total), fast SQLite queries with composite indexes, zero front-end build steps.

---

## Complete 100% Coverage Roadmap (9 Core Pillars)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    CRX 100% PRO CRM ENGINEERING ROADMAP                     │
├───────────────────────────────┬─────────────────────────────────────────────┤
│ Pillar 1: Bulk Actions Engine │ Pillar 2: Saved Views & Filter Tabs         │
│ Pillar 3: Inline Cell Editing │ Pillar 4: Workflow Automation Engine        │
│ Pillar 5: Win/Loss & Health   │ Pillar 6: Granular RBAC Permissions         │
│ Pillar 7: Lead Scoring Matrix │ Pillar 8: Duplicate Detection & Merge       │
│ Pillar 9: Global Cmd+K Center │ Production Verification & QA Battery        │
└───────────────────────────────┴─────────────────────────────────────────────┘
```

---

## Detailed Task Breakdown

### Pillar 1: Multi-Select Bulk Actions Engine
- [x] **Task 1.1: Core Bulk Handler Service**
  - **Path**: `src/Services/BulkActionService.php`
  - **Specification**:
    - `bulkDelete(string $entity, array $ids, int $workspaceId, int $userId): int`: Soft-deletes records by updating `deleted_at = datetime('now')` and sets flash notification.
    - `bulkAssign(string $entity, array $ids, int $targetUserId, int $workspaceId): int`: Reassigns `assigned_user_id` across batch.
    - `bulkUpdateStatus(string $entity, array $ids, string $status, int $workspaceId): int`: Updates status/stage across batch.
    - `bulkExportCsv(string $entity, array $ids, int $workspaceId): string`: Streams CSV containing only the selected records with standard headers.
    - Auditing: Writes aggregate record to `activity_logs` (e.g., `"Bulk updated status for 14 contacts to Customer"`).
- [x] **Task 1.2: Controller Endpoints & Routing**
  - **Routes** (`routes/admin.php`):
    - `POST /companies/bulk` -> `CompanyController@bulk`
    - `POST /people/bulk` -> `PeopleController@bulk`
    - `POST /tasks/bulk` -> `TaskController@bulk`
    - `POST /opportunities/bulk` -> `OpportunityController@bulk`
  - **Validation**:
    - Ensures CSRF token verification.
    - Ensures `ids` is an array of positive integers strictly belonging to current `workspace_id`.
    - Whitelists permissible statuses per entity.
- [x] **Task 1.3: Spartan 3D Floating Bulk Action Toolbar**
  - **Component**: `src/Views/partials/bulk_action_bar.blade.php`
  - **Table integration**: Adds checkbox `<th><input type="checkbox" id="select-all"></th>` and `<td><input type="checkbox" class="row-select" value="{{ $item->id }}"></td>` to:
    - `src/Views/companies/index.blade.php`
    - `src/Views/people/index.blade.php`
    - `src/Views/tasks/index.blade.php`
    - `src/Views/opportunities/index.blade.php` (table mode)
  - **Client Controller**: `public/js/bulk-actions.js`
    - Shift-click range selection support.
    - Indeterminate state for master checkbox.
    - High-contrast floating Spartan bar at bottom center with:
      - Selection counter: `[ 8 Selected ]`
      - Action: Reassign Owner (dropdown of workspace users)
      - Action: Update Status (dropdown of valid statuses)
      - Action: Export Selected to CSV
      - Action: Move to Recycle Bin (with confirmation modal)
      - Action: Deselect All
- [x] **Task 1.4: Automated Test Script**
  - **Path**: `scratch/test_bulk_actions.php`
  - Tests selecting contacts, performing bulk status update, bulk delete to Recycle Bin, verifying `deleted_at` timestamp, and restoring.


---

### Pillar 2: Persistent Saved Views & Custom Filter Tabs
- [x] **Task 2.1: Database Schema & Migration**
  - **Table**: `saved_views`
  - **Columns**:
    - `id INTEGER PRIMARY KEY AUTOINCREMENT`
    - `workspace_id INTEGER NOT NULL`
    - `user_id INTEGER NOT NULL`
    - `entity_type VARCHAR(50) NOT NULL` (`companies`, `people`, `tasks`, `opportunities`)
    - `name VARCHAR(100) NOT NULL`
    - `icon VARCHAR(30) DEFAULT '📁'`
    - `query_params TEXT NOT NULL` (JSON-encoded GET parameters: sort, dir, filter_col, filter_op, filter_val, search)
    - `is_shared TINYINT DEFAULT 0`
    - `is_default TINYINT DEFAULT 0`
    - `created_at DATETIME`
    - `updated_at DATETIME`
  - **Indexes**: `(workspace_id, entity_type)`
- [x] **Task 2.2: Saved View Controller & Service**
  - **Path**: `src/Controllers/SavedViewController.php`
  - **Endpoints**:
    - `POST /saved-views` -> `store`: Serializes active query params into a new named tab.
    - `POST /saved-views/{id}/update` -> `update`: Updates name, icon, or query parameters.
    - `POST /saved-views/{id}/delete` -> `destroy`: Deletes custom view.
    - `POST /saved-views/{id}/default` -> `setDefault`: Sets view as default entry tab.
- [x] **Task 2.3: Spartan 3D Tab Navigation Component**
  - **Component**: `src/Views/partials/saved_views_tabs.blade.php`
  - **UI/UX**:
    - Renders tactile isometric tabs above the filter bar.
    - `[ 📑 All ]` tab is always present.
    - Custom tabs (e.g. `[ 🏢 Enterprise > $100k ]`, `[ ⭐ High Priority Leads ]`, `[ ⏳ Stagnant Deals ]`).
    - Active tab has high-contrast Spartan styling (`background: var(--surface)`, `border: 2px solid var(--border)`, `box-shadow: 2px 2px 0 var(--border)`).
    - `[ ➕ Save View ]` button triggers modal to name current query and toggle shared status.
    - Dropdown on tabs for `Rename`, `Set as Default`, and `Delete`.
- [x] **Task 2.4: Automated Test Script**
  - **Path**: `scratch/test_saved_views.php`
  - Simulates creating a saved filter tab, switching between tabs, verifying URL reconstruction, and deleting the view.


---

### Pillar 3: Spreadsheet-Style Inline Table Cell Editing
- [x] **Task 3.1: Generic Inline Mutation API**
  - **Route**: `POST /api/inline-update`
  - **Controller**: `src/Controllers/Api/InlineEditController.php`
  - **Payload**: `{ entity: 'people'|'companies'|'tasks'|'opportunities', id: 123, field: 'phone', value: '+123456789' }`
  - **Security Whitelist**:
    - `people`: `first_name`, `last_name`, `email`, `phone`, `job_title`, `status`, `company_id`.
    - `companies`: `name`, `domain`, `industry`, `annual_revenue`, `phone`, `email`, `city`.
    - `tasks`: `title`, `priority`, `status`, `due_date`.
    - `opportunities`: `name`, `amount`, `stage`, `probability`, `expected_close_date`.
  - **Auditing**: Automatically records field diff in `activity_logs`.
- [x] **Task 3.2: Vanilla JS Inline Cell Editor**
  - **File**: `public/js/inline-edit.js`
  - Double-click on any `.editable-cell` activates an inline input control matching the data type (text, number, date, select).
  - Status/Priority/Stage cells open a compact tactile popover with option pills.
  - `Enter` or `blur` commits change via Fetch API.
  - `Escape` aborts edit and restores original value.
- [x] **Task 3.3: Tactile Spartan Micro-Feedback**
  - Saving state: Subtle pulse outline.
  - Success: 400ms border highlight (`2px solid var(--success)`).
  - Error: 600ms shake animation + red border (`2px solid var(--danger)`) + tooltip message.
- [x] **Task 3.4: Automated Test Script**
  - **Path**: `scratch/test_inline_editing.php`
  - Verifies inline updates across all 4 entity types, checks unauthorized field rejection, and confirms activity log entry.


---

### Pillar 4: Live Event-Driven Workflow Automation Engine
- [x] **Task 4.1: Database Schema**
  - **Table**: `workflow_rules`
  - **Columns**:
    - `id INTEGER PRIMARY KEY AUTOINCREMENT`
    - `workspace_id INTEGER NOT NULL`
    - `name VARCHAR(150) NOT NULL`
    - `trigger_event VARCHAR(50) NOT NULL` (`deal.won`, `deal.lost`, `deal.created`, `contact.created`, `company.created`, `task.completed`)
    - `conditions TEXT`
    - `action_type VARCHAR(50) NOT NULL` (`create_task`, `tag_entity`, `send_webhook`)
    - `action_payload TEXT NOT NULL`
    - `is_active TINYINT DEFAULT 1`
    - `created_at DATETIME`
- [x] **Task 4.2: Workflow Dispatcher Core**
  - **Path**: `src/Services/AutomationService.php`
  - **Method**: `AutomationService::dispatch(int $workspaceId, string $event, array $payload): void`
  - Evaluates matching rules against conditions.
  - Executes actions:
    - `create_task`: Generates auto-assigned task with dynamic tokens (`[Deal: {{name}}] Onboarding`).
    - `tag_entity`: Automatically tags records.
    - `send_webhook`: Sends asynchronous HTTP POST request to external URL.
- [x] **Task 4.3: Controller Integration Hooks**
  - Hooked into `OpportunityController::updateStage()`, `OpportunityController::store()`, `PeopleController::store()`, `CompanyController::store()`, and `TaskController::toggleStatus()`.
- [x] **Task 4.4: Workflow Management UI**
  - **Path**: `src/Views/settings/workflows.blade.php` & `src/Controllers/WorkflowController.php`
  - Interface to list, toggle, create, and delete automated rules.
- [x] **Task 4.5: Automated Test Script**
  - **Path**: `scratch/test_workflow_engine.php`
  - Simulates deal moving to `deal.won`, verifies automatic task generation with dynamic tokens and priority.


---

### Pillar 5: Win/Loss Attribution Modal & Deal Health Scoring
- [ ] **Task 5.1: Win/Loss Reason Capture Modal**
  - **Component**: `src/Views/partials/deal_loss_modal.blade.php`
  - Intercepts dragging/updating deal to `closed_lost` or `closed_won`.
  - Prompts for:
    - Structured Category: `Price / Budget`, `Competitor Won`, `Feature Gap`, `Poor Timing`, `Ghosted / Unresponsive`, `Other`.
    - Competitor Name (optional text).
    - Detailed Reason / Closing Notes.
  - Persists to `opportunities.lost_reason` and logs interaction note.
- [ ] **Task 5.2: Real-Time Deal Health Scoring Algorithm (0–100)**
  - **Path**: `src/Services/DealHealthService.php`
  - Algorithmic factors:
    - Base: 100 points.
    - Activity recency: $-5$ points per day beyond stage rotting threshold (max $-40$).
    - Interaction frequency: $+15$ points if meeting or call logged within last 7 days.
    - Overdue tasks: $-10$ points per overdue task.
    - Close date hygiene: $-20$ points if `expected_close_date` is in the past.
- [ ] **Task 5.3: Visual Health Gauges & Analytics**
  - Visual badges on Kanban cards & detail pages:
    - $\ge 75$: `🟢 Strong (85)`
    - $50 - 74$: `🟡 At Risk (62)`
    - $< 50$: `🔴 Critical (35)`
  - Loss attribution analytics widget on `/reports` page showing breakdown chart.
- [ ] **Task 5.4: Automated Test Script**
  - **Path**: `scratch/test_deal_health.php`
  - Tests calculation across diverse deal ages and activity frequencies.

---

### Pillar 6: Granular Role-Based Access Control (RBAC)
- [ ] **Task 6.1: Role Definitions & Hierarchy**
  - **Path**: `src/Services/RbacService.php`
  - **Roles**:
    - `owner`: Full workspace authority, billing, team management, workspace deletion.
    - `admin`: Full record management, configuration, custom fields, workflows.
    - `manager`: Full view of team deals, reassign records, view reports; cannot export data or purge recycle bin.
    - `rep`: Can view and edit assigned records; cannot modify team settings or export records.
    - `viewer`: Read-only access across assigned views.
- [ ] **Task 6.2: Policy Gates & Middleware**
  - Add authorization checks:
    - `RbacService::can($user, 'export_csv')`
    - `RbacService::can($user, 'purge_recycle_bin')`
    - `RbacService::can($user, 'manage_workflows')`
    - `RbacService::can($user, 'manage_members')`
- [ ] **Task 6.3: Workspace Team Management UI Upgrade**
  - Update `src/Views/settings/workspace.blade.php`:
    - Role selector when inviting or editing members.
    - Distinct isometric role badges (`ADMIN`, `MANAGER`, `SALES REP`, `VIEWER`).
- [ ] **Task 6.4: Automated Test Script**
  - **Path**: `scratch/test_rbac_gates.php`
  - Verifies permission denials (403) for viewers and reps on restricted actions.

---

### Pillar 7: Smart Lead Scoring & Qualification Engine (MQL / SQL)
- [ ] **Task 7.1: Scoring Engine Core**
  - **Path**: `src/Services/LeadScoringService.php`
  - Computes numerical score (0–100) for contacts and companies based on:
    - **Firmographics**: Industry fit (+15), Company revenue (+20 for >$1M), Company size (+10).
    - **Completeness**: Verified email (+10), direct phone (+10), LinkedIn/Domain (+5).
    - **Engagement**: Notes/Interactions logged (+5 per interaction, max 25), Meetings scheduled (+15).
  - Qualification stages: `Cold` (< 40), `MQL` (40–69), `SQL` (70+).
- [ ] **Task 7.2: Database & Model Integration**
  - Add `lead_score INT DEFAULT 0` and `qualification_status VARCHAR(20) DEFAULT 'cold'` to `people` table.
  - Automatically recomputes score on contact update or new interaction.
- [ ] **Task 7.3: Visual Scoring Badges**
  - Render high-contrast tactile score badge in contacts index & detail views (`⚡ SQL 85`, `🔥 MQL 65`, `❄️ Cold 25`).
- [ ] **Task 7.4: Automated Test Script**
  - **Path**: `scratch/test_lead_scoring.php`
  - Tests score recalculation upon adding phone number and logging calls.

---

### Pillar 8: Contact Duplicate Detection & 1-Click Merge Engine
- [ ] **Task 8.1: Duplicate Detection Algorithm**
  - **Path**: `src/Services/DuplicateDetectorService.php`
  - Scans `people` and `companies` for potential duplicates based on:
    - Exact matching `email` (case-insensitive).
    - Normalized `phone` number match (strip spaces, dashes, country code).
    - Fuzzy `first_name + last_name` or `name` match with Levenshtein distance $\le 2$.
- [ ] **Task 8.2: 1-Click Merge Engine**
  - **Route**: `POST /people/merge` & `POST /companies/merge`
  - **Method**: `merge(int $primaryId, int $secondaryId, int $workspaceId): bool`
  - Re-links all secondary notes, tasks, interactions, opportunities, and custom fields to primary ID.
  - Soft-deletes secondary contact with note `"Merged into contact #{$primaryId}"`.
- [ ] **Task 8.3: Tactile Merge Resolution Modal**
  - Component: `src/Views/partials/merge_modal.blade.php`
  - Side-by-side comparison of duplicate attributes with radio buttons to choose winning values.
- [ ] **Task 8.4: Automated Test Script**
  - **Path**: `scratch/test_contact_merge.php`
  - Creates two duplicate contacts with separate notes and opportunities, executes merge, and confirms unified timeline.

---

### Pillar 9: Global Command Center & Keyboard Shortcuts (`Ctrl+K`)
- [ ] **Task 9.1: Global Omni-Search Endpoint**
  - **Route**: `GET /api/omni-search?q={query}`
  - Searches across Companies, Contacts, Deals, and Tasks in a single fast query (< 15ms), returning structured results grouped by entity.
- [ ] **Task 9.2: Spartan 3D Command Palette Modal**
  - **Component**: `src/Views/partials/command_palette.blade.php`
  - Activated anywhere via `Ctrl + K` or `Cmd + K` or header search icon.
  - Features:
    - Instant live search results with keyboard arrow navigation (`↑` / `↓` / `Enter`).
    - Quick Action Launcher:
      - `> New Contact` (`Ctrl + N`)
      - `> New Company` (`Ctrl + Shift + C`)
      - `> New Deal` (`Ctrl + Shift + D`)
      - `> Log Call` (`Ctrl + L`)
      - `> Open Recycle Bin`
- [ ] **Task 9.3: Automated Test Script**
  - **Path**: `scratch/test_omni_search.php`
  - Simulates omni-search for keywords matching across multiple entities.

---

## Execution Tracker & Sign-Off

| Pillar | Capability Domain | Status | Priority |
| :--- | :--- | :--- | :--- |
| **Pillar 1** | Multi-Select Bulk Actions Engine | ✅ Completed | Immediate |
| **Pillar 2** | Persistent Saved Views & Filter Tabs | ✅ Completed | High |
| **Pillar 3** | Spreadsheet-Style Inline Cell Editing | ✅ Completed | High |
| **Pillar 4** | Live Event-Driven Workflow Automation Engine | ✅ Completed | High |
| **Pillar 5** | Win/Loss Attribution & Deal Health Scoring | ⏳ Queued | Medium |
| **Pillar 6** | Granular Role-Based Access Control (RBAC) | ⏳ Queued | Medium |
| **Pillar 7** | Smart Lead Scoring & Qualification Engine | ⏳ Queued | Medium |
| **Pillar 8** | Contact Duplicate Detection & 1-Click Merge | ⏳ Queued | Medium |
| **Pillar 9** | Global Command Center (`Ctrl+K`) & Shortcuts | ⏳ Queued | High |
