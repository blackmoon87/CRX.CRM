-- Down migration 0017
DROP INDEX IF EXISTS idx_roles_workspace;
DROP INDEX IF EXISTS idx_roles_slug;
DROP INDEX IF EXISTS idx_permissions_module;
