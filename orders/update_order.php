<?php
include_once '../db.php';

$user = getAuthUser($conn);
$data = json_decode(file_get_contents("php://input"));

$orderId = isset($data->order_id) ? $data->order_id : (isset($data->orderId) ? $data->orderId : null);

if (!empty($orderId)) {
    $updates = [];
    $params = [':order_id' => $orderId];
    
    // Check if admin
    $isAdmin = isset($user['role']) && ($user['role'] === 'admin' || $user['role'] === 'super_admin');
    if (!$isAdmin) {
        $params[':user_id'] = $user['id'];
    }

    // Fetch current order state to prevent modification of paid orders by regular admins
    $stmtCheck = $conn->prepare("SELECT commission_status FROM orders WHERE order_id = :oid");
    $stmtCheck->execute([':oid' => $orderId]);
    $currentOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($currentOrder && $currentOrder['commission_status'] === 'paid' && $user['role'] !== 'super_admin') {
        jsonResponse(["status" => "error", "message" => "This order has already been paid out and is locked."], 403);
    }
    
    if (isset($data->paymentStatus)) {
        if (!$isAdmin) {
            jsonResponse(["status" => "error", "message" => "Only administrators can update payment status."], 403);
        }
        $updates[] = "payment_status = :payment_status";
        $params[':payment_status'] = $data->paymentStatus;
    }
    if (isset($data->paymentReference)) {
        $updates[] = "payment_reference = :payment_reference";
        $params[':payment_reference'] = $data->paymentReference;
    }
    if (isset($data->status)) {
        $updates[] = "status = :status";
        $params[':status'] = $data->status;

        // Commission Calculation Logic when marked DELIVERED
        if (strtolower($data->status) === 'delivered' && $isAdmin) {
            include_once 'commission_helper.php';
            calculateAndApplyCommission($conn, $orderId);
        }
    }
    
    // Admin-only fields
    if ($isAdmin) {
        if (isset($data->adminNotes)) {
            $updates[] = "admin_notes = :admin_notes";
            $params[':admin_notes'] = $data->adminNotes;
        }
        if (isset($data->completedPdfUrl)) {
            $updates[] = "completed_pdf_url = :completed_pdf_url";
            $params[':completed_pdf_url'] = $data->completedPdfUrl;
        }
        if (isset($data->deliveredAt)) {
            $updates[] = "delivered_at = :delivered_at";
            $params[':delivered_at'] = $data->deliveredAt;
        }
        if (isset($data->workStartTime)) {
            $updates[] = "work_start_time = :work_start_time";
            $params[':work_start_time'] = $data->workStartTime;
        }
        if (isset($data->scheduledTime)) {
            $updates[] = "scheduled_time = :scheduled_time";
            $params[':scheduled_time'] = $data->scheduledTime;
        }
    }

    if (count($updates) > 0) {
        $sql = "UPDATE orders SET " . implode(", ", $updates) . " WHERE order_id = :order_id";
        if (!$isAdmin) {
            $sql .= " AND user_id = :user_id";
        }
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            jsonResponse(["status" => "success", "message" => "Order updated."]);
        } else {
            jsonResponse(["status" => "error", "message" => "Unable to update order."], 500);
        }
    } else {
        jsonResponse(["status" => "success", "message" => "No updates provided."]);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
