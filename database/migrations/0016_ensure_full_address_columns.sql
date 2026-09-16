-- Ensure full 5-part address columns on companies and people for geographic targeting & grouping
-- Compatible with SQLite and MySQL

ALTER TABLE companies ADD COLUMN state VARCHAR(100) NULL;
ALTER TABLE companies ADD COLUMN postal_code VARCHAR(50) NULL;

ALTER TABLE people ADD COLUMN address TEXT NULL;
ALTER TABLE people ADD COLUMN city VARCHAR(100) NULL;
ALTER TABLE people ADD COLUMN state VARCHAR(100) NULL;
ALTER TABLE people ADD COLUMN postal_code VARCHAR(50) NULL;
ALTER TABLE people ADD COLUMN country VARCHAR(100) NULL;

CREATE INDEX IF NOT EXISTS idx_companies_country ON companies (country);
CREATE INDEX IF NOT EXISTS idx_companies_city ON companies (city);
CREATE INDEX IF NOT EXISTS idx_people_country ON people (country);
CREATE INDEX IF NOT EXISTS idx_people_city ON people (city);
