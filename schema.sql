-- MySQL Schema for E-Stamp Assistance

CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100),
  `email` VARCHAR(100) UNIQUE,
  `password` VARCHAR(255),
  `cnic` VARCHAR(20),
  `phone` VARCHAR(20),
  `address` TEXT,
  `role` VARCHAR(20) DEFAULT 'user',
  `commission_type` VARCHAR(20) DEFAULT 'fixed',
  `commission_value` DECIMAL(10,2) DEFAULT 0.00,
  `payout_cycle` VARCHAR(20) DEFAULT 'monthly',
  `fcm_token` VARCHAR(255),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `token` VARCHAR(128) PRIMARY KEY,
  `user_id` VARCHAR(50),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` VARCHAR(50) PRIMARY KEY,
  `user_id` VARCHAR(50),
  `flow_type` VARCHAR(50),
  `purpose` VARCHAR(50),
  `status` VARCHAR(50),
  `stamp_value` INT,
  `service_type` VARCHAR(50),
  `service_fee` INT,
  `party1` TEXT,
  `party2` TEXT,
  `property_details` TEXT,
  `additional_instructions` TEXT,
  `stamp_pdf_url` TEXT,
  `completed_pdf_url` TEXT,
  `payment_status` VARCHAR(50),
  `payment_reference` VARCHAR(100),
  `user_disclaimer_accepted` TINYINT(1),
  `user_disclaimer_accepted_at` DATETIME,
  `pay_stamp_fee_myself` TINYINT(1) DEFAULT 0,
  `admin_notes` TEXT,
  `assigned_admin_id` VARCHAR(50),
  `admin_rating` INT,
  `scheduled_time` DATETIME,
  `work_start_time` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  `delivered_at` DATETIME,
  `commission_amount` DECIMAL(10,2) DEFAULT NULL,
  `commission_status` VARCHAR(20) DEFAULT 'pending',
  `commission_paid_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`assigned_admin_id`) REFERENCES `users`(`id`)
);

CREATE TABLE IF NOT EXISTS `admin_commission_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` VARCHAR(50),
  `order_type` VARCHAR(100),
  `commission_type` VARCHAR(20) DEFAULT 'fixed',
  `commission_value` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `admin_order_type` (`admin_id`, `order_type`),
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `admin_payouts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` VARCHAR(50),
  `period_start` DATETIME,
  `period_end` DATETIME,
  `order_count` INT,
  `total_commission` DECIMAL(10,2),
  `status` VARCHAR(20) DEFAULT 'pending',
  `settled_at` DATETIME DEFAULT NULL,
  `note` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `app_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(50) UNIQUE,
  `config_value` JSON
);

-- Default Config Data
INSERT IGNORE INTO `app_config` (`config_key`, `config_value`) VALUES 
('ticker', '{"text": "Welcome to E-Stamp Assistance! Contact us on WhatsApp for fast processing.", "isActive": true}'),
('fees', '{"assisted_standard": 400, "assisted_urgent": 600, "self_standard": 200, "self_urgent": 350}');
