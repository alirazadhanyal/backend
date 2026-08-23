<?php
include_once '../db.php';

$user = getAuthUser($conn);

// Verify role
if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized access"], 403);
}

$adminId = $user['id'];

try {
    // 1. Get Outstanding Balance (pending commission from delivered/completed orders)
    $stmt1 = $conn->prepare("
        SELECT COALESCE(SUM(commission_amount), 0) as outstanding 
        FROM orders 
        WHERE assigned_admin_id = :aid 
        AND commission_status = 'pending' 
        AND LOWER(status) IN ('delivered', 'completed')
    ");
    $stmt1->execute([':aid' => $adminId]);
    $outstanding = (float)$stmt1->fetchColumn();

    // 2. Get Total Paid
    $stmt2 = $conn->prepare("
        SELECT COALESCE(SUM(total_commission), 0) as total_paid 
        FROM admin_payouts 
        WHERE admin_id = :aid
    ");
    $stmt2->execute([':aid' => $adminId]);
    $totalPaid = (float)$stmt2->fetchColumn();

    // 3. Get Payout History
    $stmt3 = $conn->prepare("
        SELECT id, order_count, total_commission, status, settled_at, note, receipt_url 
        FROM admin_payouts 
        WHERE admin_id = :aid 
        ORDER BY settled_at DESC
    ");
    $stmt3->execute([':aid' => $adminId]);
    $history = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        "status" => "success",
        "earnings" => [
            "outstanding" => $outstanding,
            "total_paid" => $totalPaid,
            "history" => $history
        ]
    ]);
} catch (Exception $e) {
    jsonResponse(["status" => "error", "message" => "An error occurred while fetching earnings."], 500);
}
?>
