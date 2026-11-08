-- Trigger function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- 1. Create buyers table
CREATE TABLE IF NOT EXISTS buyers (
  id SERIAL PRIMARY KEY,
  buyer_name VARCHAR(255) DEFAULT '',
  buyer_company VARCHAR(255) NOT NULL UNIQUE,
  buyer_address TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_buyers_updated_at
BEFORE UPDATE ON buyers
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- 2. Create bills table
CREATE TABLE IF NOT EXISTS bills (
  invoice_number SERIAL PRIMARY KEY,
  buyer_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  bag DECIMAL(10,2) NOT NULL,
  vehicle_number VARCHAR(255) NOT NULL,
  vehicle_freight DECIMAL(10,2) DEFAULT '0.00',
  payment_received DECIMAL(10,2) DEFAULT '0.00',
  created_on TIMESTAMP NOT NULL,
  updated_on TIMESTAMP NOT NULL,
  url VARCHAR(500) DEFAULT NULL,
  CONSTRAINT fk_bills_buyer_id FOREIGN KEY (buyer_id) REFERENCES buyers (id) ON DELETE CASCADE
);

CREATE INDEX fk_bills_buyer_id_idx ON bills(buyer_id);

-- 3. Create items table
CREATE TABLE IF NOT EXISTS items (
  id SERIAL PRIMARY KEY,
  item_name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_items_updated_at
BEFORE UPDATE ON items
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- Insert default items if not present
INSERT INTO items (item_name) VALUES
('RAGI HSN:10082031'),
('Ragi Flour HSN:10082031')
ON CONFLICT DO NOTHING;

-- 4. Create bill_items table for multi-item invoices
CREATE TABLE IF NOT EXISTS bill_items (
  id SERIAL PRIMARY KEY,
  invoice_number INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  bag DECIMAL(10,2) DEFAULT '0.00',
  quantity DECIMAL(10,2) DEFAULT '0.00',
  price DECIMAL(10,2) DEFAULT '0.00',
  amount DECIMAL(10,2) DEFAULT '0.00',
  CONSTRAINT fk_bill_items_invoice FOREIGN KEY (invoice_number) REFERENCES bills (invoice_number) ON DELETE CASCADE
);

CREATE INDEX fk_bill_items_invoice_idx ON bill_items(invoice_number);
