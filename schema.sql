CREATE DATABASE IF NOT EXISTS `invoice_db`;
USE `invoice_db`;

-- 1. Create buyers table
CREATE TABLE IF NOT EXISTS `buyers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `buyer_name` VARCHAR(255) DEFAULT '',
  `buyer_company` VARCHAR(255) NOT NULL,
  `buyer_address` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_company` (`buyer_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create bills table
CREATE TABLE IF NOT EXISTS `bills` (
  `invoice_number` INT NOT NULL AUTO_INCREMENT,
  `buyer_id` INT NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `bag` DECIMAL(10,2) NOT NULL,
  `vehicle_number` VARCHAR(255) NOT NULL,
  `vehicle_freight` DECIMAL(10,2) DEFAULT '0.00',
  `payment_received` DECIMAL(10,2) DEFAULT '0.00',
  `created_on` DATETIME NOT NULL,
  `updated_on` DATETIME NOT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`invoice_number`),
  KEY `fk_bills_buyer_id` (`buyer_id`),
  CONSTRAINT `fk_bills_buyer_id` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create items table
CREATE TABLE IF NOT EXISTS `items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_name` (`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default items if not present
INSERT IGNORE INTO `items` (`item_name`) VALUES 
('RAGI HSN:10082031'),
('Ragi Flour HSN:10082031');
-- 4. Create bill_items table for multi-item invoices
CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` INT NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `bag` DECIMAL(10,2) DEFAULT '0.00',
  `quantity` DECIMAL(10,2) DEFAULT '0.00',
  `price` DECIMAL(10,2) DEFAULT '0.00',
  `amount` DECIMAL(10,2) DEFAULT '0.00',
  KEY `fk_bill_items_invoice` (`invoice_number`),
  CONSTRAINT `fk_bill_items_invoice` FOREIGN KEY (`invoice_number`) REFERENCES `bills` (`invoice_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
