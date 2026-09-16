CREATE TABLE IF NOT EXISTS custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    options TEXT NULL,
    is_required INT NOT NULL DEFAULT 0,
    order_column INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_cf_workspace_entity ON custom_fields (workspace_id, entity_type);
CREATE INDEX IF NOT EXISTS idx_cf_code ON custom_fields (workspace_id, code);

CREATE TABLE IF NOT EXISTS custom_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    custom_field_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    text_value TEXT NULL,
    number_value DECIMAL(15, 4) NULL,
    date_value DATE NULL,
    json_value TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_cfv_entity ON custom_field_values (workspace_id, entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_cfv_field ON custom_field_values (custom_field_id);
