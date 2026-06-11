USE invoice_db;

-- Create buyers table
CREATE TABLE IF NOT EXISTS buyers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_name VARCHAR(255) DEFAULT '',
    buyer_company VARCHAR(255) NOT NULL UNIQUE,
    buyer_address TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safe migration steps to normalize existing database
-- 1. Insert unique buyers from current bills into buyers table
INSERT IGNORE INTO buyers (buyer_name, buyer_company, buyer_address)
SELECT DISTINCT buyer_name, buyer_company, buyer_address
FROM bills;

-- 2. Add buyer_id foreign key column to bills table
ALTER TABLE bills ADD COLUMN buyer_id INT DEFAULT NULL;

-- 3. Map bills to their respective buyer_id
UPDATE bills b
JOIN buyers buy ON b.buyer_company = buy.buyer_company
SET b.buyer_id = buy.id;

-- 4. Set buyer_id as NOT NULL after mapping
ALTER TABLE bills MODIFY COLUMN buyer_id INT NOT NULL;

-- 5. Add Foreign Key Constraint
ALTER TABLE bills ADD CONSTRAINT fk_bills_buyer_id FOREIGN KEY (buyer_id) REFERENCES buyers(id);

-- 6. Drop redundant columns from bills table
ALTER TABLE bills DROP COLUMN buyer_name;
ALTER TABLE bills DROP COLUMN buyer_company;
ALTER TABLE bills DROP COLUMN buyer_address;
