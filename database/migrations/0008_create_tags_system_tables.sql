CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#7C3AED'
);

CREATE INDEX IF NOT EXISTS idx_tags_workspace ON tags (workspace_id);

CREATE TABLE IF NOT EXISTS taggables (
    tag_id INT NOT NULL,
    taggable_type VARCHAR(50) NOT NULL,
    taggable_id INT NOT NULL,
    PRIMARY KEY (tag_id, taggable_type, taggable_id)
);

CREATE INDEX IF NOT EXISTS idx_taggables_entity ON taggables (taggable_type, taggable_id);
