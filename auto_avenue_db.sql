CREATE DATABASE IF NOT EXISTS `auto_avenue_db`;
USE `auto_avenue_db`;

-- Users table with enhanced security
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff', 'manager') NOT NULL DEFAULT 'staff',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,
  INDEX `idx_active` (`is_active`)
);

-- Products table with full audit tracking
CREATE TABLE `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `quantity` INT NOT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL,
  `cost_price` DECIMAL(10,2),
  `category` VARCHAR(50),
  `supplier` VARCHAR(100),
  `supplier_contact` VARCHAR(100),
  `min_stock_level` INT DEFAULT 10,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_active` (`is_active`),
  INDEX `idx_category` (`category`)
);

-- Sales table with customer information
CREATE TABLE `sales` (
  `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT,
  `quantity_sold` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `customer_name` VARCHAR(100),
  `customer_contact` VARCHAR(50),
  `sold_by` INT,
  `sale_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  `is_refunded` BOOLEAN DEFAULT FALSE,
  `refund_reason` TEXT,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL,
  FOREIGN KEY (`sold_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_sale_date` (`sale_date`)
);


-- Alerts system with priority levels
CREATE TABLE `alerts` (
  `alert_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT,
  `user_id` INT,
  `message` VARCHAR(200) NOT NULL,
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `alert_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL,
  `resolved_by` INT,
  `resolution_notes` TEXT,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`resolved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_unresolved` (`resolved_at`)
);

-- Inventory adjustments log
CREATE TABLE `inventory_log` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT,
  `adjustment_type` ENUM('sale', 'purchase', 'return', 'damage', 'adjustment') NOT NULL,
  `quantity_change` INT NOT NULL,
  `old_quantity` INT NOT NULL,
  `new_quantity` INT NOT NULL,
  `notes` TEXT,
  `log_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
);

-- Sample data with realistic values
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

INSERT INTO `products` (`name`, `description`, `quantity`, `price`, `cost_price`, `category`, `supplier`, `supplier_contact`, `min_stock_level`, `created_by`) VALUES
('Engine Oil 5W-30', 'Fully synthetic engine oil for all weather conditions', 15, 450.00, 320.00, 'Fluids', 'Shell Philippines', 'supply@shell.ph', 5, 1),
('Brake Pads', 'Premium ceramic brake pads for smooth braking', 8, 1200.00, 850.00, 'Brakes', 'Brembo', 'sales@brembo.com', 4, 1),
('Air Filter', 'High-performance air filter for better engine breathing', 20, 350.00, 220.00, 'Filters', 'K&N', 'orders@knfilters.com', 10, 1);