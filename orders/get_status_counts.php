<?php
include_once '../db.php';

$user = getAuthUser($conn);
if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized"], 403);
}

$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$stmt->execute();

$counts = [
    'pending_payment' => 0,
    'paid' => 0,
    'in_progress' => 0,
    'ready' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusKey = $row['status'];
    if (array_key_exists($statusKey, $counts)) {
        $counts[$statusKey] = (int)$row['count'];
    }
}

jsonResponse(["status" => "success", "data" => $counts]);
?>
