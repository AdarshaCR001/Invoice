-- 1. Create bill_items table
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

-- 2. Migrate existing single-item bills into bill_items if not already migrated
INSERT INTO bill_items (invoice_number, item_name, bag, quantity, price, amount)
SELECT invoice_number, item_name, bag, quantity, price, (quantity * price)
FROM bills
WHERE invoice_number NOT IN (SELECT DISTINCT invoice_number FROM bill_items);
