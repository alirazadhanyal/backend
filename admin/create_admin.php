<?php
include_once '../db.php';

$user = getAuthUser($conn);

if ($user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized. Super Admin only."], 403);
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        if ($stmt->fetch()) {
            jsonResponse(["status" => "error", "message" => "Email already exists"], 400);
        }

        $id = uniqid("adm_");
        $password = password_hash($data->password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, role) VALUES (:id, :name, :email, :password, 'admin')");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':password', $password);
        
        if ($stmt->execute()) {
            jsonResponse(["status" => "success", "message" => "Admin created successfully."]);
        }
    } catch(PDOException $e) {
        jsonResponse(["status" => "error", "message" => "Failed to create admin."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
