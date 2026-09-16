CREATE TABLE IF NOT EXISTS interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    user_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    type VARCHAR(30) NOT NULL,
    title VARCHAR(255) NULL,
    description TEXT NOT NULL,
    outcome VARCHAR(50) NULL,
    duration_minutes INT NULL,
    scheduled_at DATETIME NULL,
    metadata TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_interactions_entity ON interactions (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_interactions_workspace ON interactions (workspace_id);
