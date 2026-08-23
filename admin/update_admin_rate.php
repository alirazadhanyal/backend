<?php
include_once '../db.php';
$user = getAuthUser($conn);
if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized"], 403);
}

$data = json_decode(file_get_contents("php://input"));
if (empty($data->admin_id) || empty($data->commission_type)) {
    jsonResponse(["status" => "error", "message" => "Admin ID and Commission Type are required"], 400);
}

try {
    $val = isset($data->commission_value) ? (float)$data->commission_value : 0.00;
    
    $stmt = $conn->prepare("UPDATE users SET commission_type = :type, commission_value = :val WHERE id = :aid AND role = 'admin'");
    $stmt->execute([':type' => $data->commission_type, ':val' => $val, ':aid' => $data->admin_id]);
    
    // Log action
    $logStmt = $conn->prepare("INSERT INTO admin_actions_log (admin_id, action_type, action_details) VALUES (:sid, 'update_rate', :details)");
    $details = json_encode(['target_admin_id' => $data->admin_id, 'commission_type' => $data->commission_type, 'commission_value' => $val]);
    $logStmt->execute([':sid' => $user['id'], ':details' => $details]);

    jsonResponse(["status" => "success", "message" => "Admin rate updated successfully."]);
} catch (Exception $e) {
    jsonResponse(["status" => "error", "message" => "An error occurred while updating the rate."], 500);
}
?>
