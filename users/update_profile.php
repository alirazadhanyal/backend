<?php
include_once '../db.php';

$user = getAuthUser($conn);
$data = json_decode(file_get_contents("php://input"));

$updates = [];
$params = [':id' => $user['id']];

if (isset($data->name)) {
    $updates[] = "name = :name";
    $params[':name'] = $data->name;
}
if (isset($data->cnic)) {
    $updates[] = "cnic = :cnic";
    $params[':cnic'] = $data->cnic;
}
if (isset($data->phone)) {
    $updates[] = "phone = :phone";
    $params[':phone'] = $data->phone;
}
if (isset($data->address)) {
    $updates[] = "address = :address";
    $params[':address'] = $data->address;
}

if (count($updates) > 0) {
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute($params)) {
        jsonResponse(["status" => "success", "message" => "Profile updated."]);
    } else {
        jsonResponse(["status" => "error", "message" => "Unable to update profile."], 500);
    }
} else {
    jsonResponse(["status" => "success", "message" => "No updates provided."]);
}
?>
