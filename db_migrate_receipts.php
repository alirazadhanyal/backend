<?php
include_once 'db.php';

try {
    echo "Starting receipts migration...<br>";
    
    // Add column to admin_payouts
    $conn->exec("ALTER TABLE admin_payouts ADD COLUMN IF NOT EXISTS receipt_url TEXT DEFAULT NULL");
    
    echo "admin_payouts table updated with receipt_url.<br>";
    echo "Migration completed successfully!";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
