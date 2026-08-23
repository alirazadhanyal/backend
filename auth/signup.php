<?php
include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        jsonResponse(["status" => "error", "message" => "Email already exists."], 400);
    }

    $id = uniqid('usr_', true);
    $hash = password_hash($data->password, PASSWORD_BCRYPT);
    $cnic = $data->cnic ?? null;
    $phone = $data->phone ?? null;

    $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, cnic, phone) VALUES (:id, :name, :email, :password, :cnic, :phone)");
    
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':password', $hash);
    $stmt->bindParam(':cnic', $cnic);
    $stmt->bindParam(':phone', $phone);

    if ($stmt->execute()) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($user['password']);

        jsonResponse([
            "status" => "success",
            "message" => "User registered.",
            "token" => $id,
            "user" => $user
        ], 201);
    } else {
        jsonResponse(["status" => "error", "message" => "Unable to register."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
