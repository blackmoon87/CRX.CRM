CREATE TABLE IF NOT EXISTS lead_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    uuid VARCHAR(64) NOT NULL,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    fields_schema TEXT NOT NULL,
    submit_button_text VARCHAR(80) NOT NULL DEFAULT 'Submit Inquiry',
    success_message TEXT NULL,
    redirect_url VARCHAR(255) NULL,
    assigned_user_id INT NULL,
    submissions_count INT NOT NULL DEFAULT 0,
    is_active INT NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_forms_uuid ON lead_forms (uuid);
CREATE INDEX IF NOT EXISTS idx_forms_workspace ON lead_forms (workspace_id);
