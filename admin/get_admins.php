<?php
include_once '../db.php';

$user = getAuthUser($conn);

if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized. Super Admin access required."], 403);
}

$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.created_at, u.commission_type, u.commission_value, u.payout_cycle,
           (SELECT COUNT(*) FROM orders o WHERE o.assigned_admin_id = u.id AND LOWER(o.status) IN ('delivered', 'completed')) as completed_orders,
           (SELECT SUM(commission_amount) FROM orders o WHERE o.assigned_admin_id = u.id AND o.commission_status = 'pending' AND LOWER(o.status) IN ('delivered', 'completed')) as outstanding_balance,
           (SELECT SUM(total_commission) FROM admin_payouts p WHERE p.admin_id = u.id) as total_paid
    FROM users u 
    WHERE u.role = 'admin'
");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    "status" => "success",
    "admins" => $admins
]);
?>
