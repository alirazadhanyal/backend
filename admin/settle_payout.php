<?php
include_once '../db.php';
$user = getAuthUser($conn);
if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized"], 403);
}

$data = json_decode(file_get_contents("php://input"));
if (empty($data->admin_id)) {
    jsonResponse(["status" => "error", "message" => "Admin ID is required"], 400);
}

try {
    $conn->beginTransaction();
    
    // Calculate total pending
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(commission_amount) as total FROM orders WHERE assigned_admin_id = :aid AND commission_status = 'pending' AND LOWER(status) IN ('delivered', 'completed')");
    $stmt->execute([':aid' => $data->admin_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$res || $res['count'] == 0) {
        $conn->rollBack();
        jsonResponse(["status" => "error", "message" => "No pending commission to settle."]);
        exit;
    }
    
    $total = $res['total'];
    $count = $res['count'];
    $note = isset($data->note) ? $data->note : '';
    $receipt_url = isset($data->receipt_url) ? $data->receipt_url : null;
    
    // Insert payout
    $ins = $conn->prepare("INSERT INTO admin_payouts (admin_id, order_count, total_commission, status, settled_at, note, receipt_url) VALUES (:aid, :cnt, :tot, 'settled', NOW(), :note, :receipt_url)");
    $ins->execute([':aid' => $data->admin_id, ':cnt' => $count, ':tot' => $total, ':note' => $note, ':receipt_url' => $receipt_url]);
    
    // Update orders
    $upd = $conn->prepare("UPDATE orders SET commission_status = 'paid', commission_paid_at = NOW() WHERE assigned_admin_id = :aid AND commission_status = 'pending' AND LOWER(status) IN ('delivered', 'completed')");
    $upd->execute([':aid' => $data->admin_id]);
    
    // Log action
    $logStmt = $conn->prepare("INSERT INTO admin_actions_log (admin_id, action_type, action_details) VALUES (:sid, 'settle_payout', :details)");
    $details = json_encode(['target_admin_id' => $data->admin_id, 'amount' => $total, 'count' => $count]);
    $logStmt->execute([':sid' => $user['id'], ':details' => $details]);

    $conn->commit();
    jsonResponse(["status" => "success", "message" => "Payout settled successfully."]);
} catch (Exception $e) {
    $conn->rollBack();
    jsonResponse(["status" => "error", "message" => "An error occurred while settling payout."], 500);
}
?>
