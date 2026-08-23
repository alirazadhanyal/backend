<?php
include_once '../db.php';

$user = getAuthUser($conn);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->order_id) && !empty($data->rating)) {
    try {
        $stmt = $conn->prepare("UPDATE orders SET admin_rating = :rating WHERE order_id = :order_id AND user_id = :user_id");
        $stmt->bindParam(':rating', $data->rating);
        $stmt->bindParam(':order_id', $data->order_id);
        $stmt->bindParam(':user_id', $user['id']);
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(["status" => "success", "message" => "Rating submitted successfully."]);
        } else {
            jsonResponse(["status" => "error", "message" => "Order not found or not owned by you."], 404);
        }
    } catch(PDOException $e) {
        jsonResponse(["status" => "error", "message" => "Failed to submit rating."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
