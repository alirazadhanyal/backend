<?php
include_once 'db.php';

try {
    // Add columns if they don't exist
    $conn->exec("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `assigned_admin_id` VARCHAR(50)");
    $conn->exec("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `admin_rating` INT");
    
    // Create super admin
    $superAdminId = 'super_admin_1';
    $password = password_hash('superadmin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES (:id, 'Super Admin', 'superadmin@estamp.com', :password, 'super_admin')");
    $stmt->bindParam(':id', $superAdminId);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    echo "Migration successful. Super admin created (email: superadmin@estamp.com, pass: superadmin123)";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
