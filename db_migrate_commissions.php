<?php
include_once 'db.php';

try {
    echo "Starting migration...<br>";
    
    // Add columns to users
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS commission_type VARCHAR(20) DEFAULT 'fixed'");
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS commission_value DECIMAL(10,2) DEFAULT 0.00");
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_cycle VARCHAR(20) DEFAULT 'monthly'");
    echo "users table updated.<br>";

    // Add columns to orders
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10,2) DEFAULT NULL");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_status VARCHAR(20) DEFAULT 'pending'");
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_paid_at DATETIME DEFAULT NULL");
    echo "orders table updated.<br>";

    // admin_commission_rates table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `admin_commission_rates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `admin_id` VARCHAR(50),
        `order_type` VARCHAR(100),
        `commission_type` VARCHAR(20) DEFAULT 'fixed',
        `commission_value` DECIMAL(10,2) DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `admin_order_type` (`admin_id`, `order_type`),
        FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )
    ");
    echo "admin_commission_rates table checked/created.<br>";

    // admin_payouts table
    $conn->exec("
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
        )
    ");
    echo "admin_payouts table checked/created.<br>";

    echo "Migration completed successfully!";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
