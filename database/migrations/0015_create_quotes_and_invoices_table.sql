CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    opportunity_id INT NULL,
    person_id INT NULL,
    company_id INT NULL,
    quote_number VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    public_token VARCHAR(64) NOT NULL,
    valid_until DATE NULL,
    notes TEXT NULL,
    accepted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_quotes_workspace ON quotes (workspace_id);
CREATE INDEX IF NOT EXISTS idx_quotes_token ON quotes (public_token);
CREATE INDEX IF NOT EXISTS idx_quotes_opp ON quotes (opportunity_id);

CREATE TABLE IF NOT EXISTS quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_quote_items_quote ON quote_items (quote_id);
