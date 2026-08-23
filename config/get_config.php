<?php
include_once '../db.php';

$stmt = $conn->prepare("SELECT * FROM app_config");
$stmt->execute();

$config = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $config[$row['config_key']] = json_decode($row['config_value']);
}

jsonResponse(["status" => "success", "data" => $config]);
?>
