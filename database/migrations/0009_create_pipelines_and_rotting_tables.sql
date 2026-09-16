CREATE TABLE IF NOT EXISTS pipelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    is_default INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_pipelines_workspace ON pipelines (workspace_id);

CREATE TABLE IF NOT EXISTS pipeline_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pipeline_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL,
    probability INT NOT NULL DEFAULT 20,
    rotting_days INT NOT NULL DEFAULT 14,
    order_column INT NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL DEFAULT '#0284C7'
);

CREATE INDEX IF NOT EXISTS idx_stages_pipeline ON pipeline_stages (pipeline_id);

ALTER TABLE opportunities ADD COLUMN pipeline_id INT NULL;
ALTER TABLE opportunities ADD COLUMN lost_reason VARCHAR(255) NULL;
