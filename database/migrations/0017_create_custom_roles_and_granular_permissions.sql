-- =========================================================================
-- CRX CRM - Migration 0017: Custom Roles & Granular Permissions (RBAC)
-- Compatible with SQLite and MySQL
-- =========================================================================

-- 1. Enhance roles table
ALTER TABLE roles ADD COLUMN workspace_id INT NULL;
ALTER TABLE roles ADD COLUMN description TEXT NULL;
ALTER TABLE roles ADD COLUMN is_system TINYINT(1) DEFAULT 0;

-- 2. Enhance permissions table
ALTER TABLE permissions ADD COLUMN module VARCHAR(50) NULL;
ALTER TABLE permissions ADD COLUMN action VARCHAR(50) NULL;

-- 3. Create indices for fast role lookups
CREATE INDEX IF NOT EXISTS idx_roles_workspace ON roles (workspace_id);
CREATE INDEX IF NOT EXISTS idx_roles_slug ON roles (slug);
CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions (module);
