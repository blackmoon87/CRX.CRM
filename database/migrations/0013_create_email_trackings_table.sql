CREATE TABLE IF NOT EXISTS email_trackings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    tracking_token VARCHAR(64) NOT NULL,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    open_count INT NOT NULL DEFAULT 0,
    first_opened_at DATETIME NULL,
    last_opened_at DATETIME NULL,
    click_count INT NOT NULL DEFAULT 0,
    last_clicked_at DATETIME NULL,
    last_clicked_url TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_tracking_token ON email_trackings (tracking_token);
CREATE INDEX IF NOT EXISTS idx_tracking_entity ON email_trackings (entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_tracking_workspace ON email_trackings (workspace_id);
