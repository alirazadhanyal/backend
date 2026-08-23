<?php
include_once '../db.php';

$user = getAuthUser($conn);
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->orderId)) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    
    $stmt->bindParam(':order_id', $data->orderId);
    $stmt->bindParam(':user_id', $user['id']);
    
    if ($stmt->execute()) {
        jsonResponse(["status" => "success", "message" => "Order deleted successfully"]);
    } else {
        jsonResponse(["status" => "error", "message" => "Failed to delete order"]);
    }
}

jsonResponse(["status" => "error", "message" => "Invalid request"], 400);
