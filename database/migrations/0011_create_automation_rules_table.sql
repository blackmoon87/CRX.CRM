CREATE TABLE IF NOT EXISTS workflow_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    trigger_event VARCHAR(50) NOT NULL,
    conditions TEXT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_payload TEXT NOT NULL,
    is_active INT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_workflows_workspace ON workflow_rules (workspace_id);
