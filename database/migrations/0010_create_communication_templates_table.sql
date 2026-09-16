CREATE TABLE IF NOT EXISTS communication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NULL,
    body TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'sales',
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_templates_workspace ON communication_templates (workspace_id);
