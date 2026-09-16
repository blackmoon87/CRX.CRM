ALTER TABLE companies ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE people ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE opportunities ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE tasks ADD COLUMN deleted_at DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_companies_deleted ON companies (deleted_at);
CREATE INDEX IF NOT EXISTS idx_people_deleted ON people (deleted_at);
CREATE INDEX IF NOT EXISTS idx_opps_deleted ON opportunities (deleted_at);
CREATE INDEX IF NOT EXISTS idx_tasks_deleted ON tasks (deleted_at);
