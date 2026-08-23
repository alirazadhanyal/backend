<?php
include_once '../db.php';

$user = getAuthUser($conn);

if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized. Super Admin only."], 403);
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->order_id) && isset($data->admin_id)) {
    try {
        // admin_id can be null to unassign
        $adminId = empty($data->admin_id) ? null : $data->admin_id;

        $stmt = $conn->prepare("UPDATE orders SET assigned_admin_id = :admin_id WHERE order_id = :order_id");
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->bindParam(':order_id', $data->order_id);
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $logStmt = $conn->prepare("INSERT INTO admin_actions_log (admin_id, action_type, action_details) VALUES (:aid, 'assign_order', :details)");
            $details = json_encode(['order_id' => $data->order_id, 'assigned_admin_id' => $data->admin_id]);
            $logStmt->execute([':aid' => $user['id'], ':details' => $details]);

            jsonResponse(["status" => "success", "message" => "Order assignment updated successfully."]);
        }
    } catch(PDOException $e) {
        jsonResponse(["status" => "error", "message" => "Failed to update assignment."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
