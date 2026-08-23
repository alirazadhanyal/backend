<?php
include_once '../db.php';

$user = getAuthUser($conn);
$data = json_decode(file_get_contents("php://input"));

if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized. Super Admin only."], 403);
}

if (!empty($data->admin_id)) {
    // Cannot delete yourself
    if ($data->admin_id === $user['id']) {
        jsonResponse(["status" => "error", "message" => "Cannot delete your own account."], 400);
    }
    
    // Optional: Could reassign orders from this admin to empty/another admin here if needed.
    $stmt = $conn->prepare("UPDATE orders SET assigned_admin_id = '' WHERE assigned_admin_id = :admin_id");
    $stmt->bindParam(':admin_id', $data->admin_id);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'admin'");
    $stmt->bindParam(':id', $data->admin_id);
    
    if ($stmt->execute()) {
        jsonResponse(["status" => "success", "message" => "Admin deleted successfully."]);
    } else {
        jsonResponse(["status" => "error", "message" => "Failed to delete admin."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Missing admin_id."], 400);
}
?>
