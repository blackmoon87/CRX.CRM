CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NULL,
    industry VARCHAR(100) NULL,
    size VARCHAR(50) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    annual_revenue DECIMAL(15, 2) NULL,
    description TEXT NULL,
    assigned_user_id INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_companies_workspace ON companies (workspace_id);
CREATE INDEX IF NOT EXISTS idx_companies_name ON companies (name);

CREATE TABLE IF NOT EXISTS people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    company_id INT NULL,
    first_name VARCHAR(150) NOT NULL,
    last_name VARCHAR(150) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    job_title VARCHAR(150) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'lead',
    assigned_user_id INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_people_workspace ON people (workspace_id);
CREATE INDEX IF NOT EXISTS idx_people_company ON people (company_id);
CREATE INDEX IF NOT EXISTS idx_people_email ON people (email);

CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    company_id INT NULL,
    person_id INT NULL,
    name VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    stage VARCHAR(50) NOT NULL DEFAULT 'lead',
    probability INT NOT NULL DEFAULT 20,
    expected_close_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'open',
    assigned_user_id INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_opportunities_workspace ON opportunities (workspace_id);
CREATE INDEX IF NOT EXISTS idx_opportunities_stage ON opportunities (stage);
CREATE INDEX IF NOT EXISTS idx_opportunities_company ON opportunities (company_id);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    due_date DATE NULL,
    priority VARCHAR(50) NOT NULL DEFAULT 'medium',
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    assigned_user_id INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_tasks_workspace ON tasks (workspace_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks (status);
CREATE INDEX IF NOT EXISTS idx_tasks_entity ON tasks (entity_type, entity_id);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NULL,
    body TEXT NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_notes_workspace ON notes (workspace_id);
CREATE INDEX IF NOT EXISTS idx_notes_entity ON notes (entity_type, entity_id);
