<?php
include_once '../db.php';

$user = getAuthUser($conn);

if ($user['role'] !== 'admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized. Admin only."], 403);
}

$stmt = $conn->prepare("
    SELECT o.*, u.cnic, u.name as customer_name, u.phone as customer_phone 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.assigned_admin_id = :admin_id 
    ORDER BY o.created_at DESC
");
$stmt->bindParam(':admin_id', $user['id']);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    "status" => "success",
    "orders" => $orders
]);
?>
