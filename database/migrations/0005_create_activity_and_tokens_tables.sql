CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    description TEXT NOT NULL,
    metadata TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_activity_workspace ON activity_log (workspace_id);
CREATE INDEX IF NOT EXISTS idx_activity_entity ON activity_log (entity_type, entity_id);

CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_tokens_token ON personal_access_tokens (token);
CREATE INDEX IF NOT EXISTS idx_tokens_workspace ON personal_access_tokens (workspace_id);
