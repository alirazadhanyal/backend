<?php
include_once '../db.php';

$user = getAuthUser($conn);
if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized"], 403);
}

$status = isset($_GET['status']) ? $_GET['status'] : null;
$purpose = isset($_GET['purpose']) ? $_GET['purpose'] : null;

$query = "SELECT o.*, u.cnic, u.name as customer_name, u.phone as customer_phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";

if ($status && $status !== 'All') {
    $query .= " AND o.status = :status";
}
if ($purpose && $purpose !== 'All') {
    $query .= " AND o.purpose = :purpose";
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
if ($status && $status !== 'All') {
    $stmt->bindParam(':status', $status);
}
if ($purpose && $purpose !== 'All') {
    $stmt->bindParam(':purpose', $purpose);
}
$stmt->execute();

$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Decode JSON strings back to arrays so the response is clean JSON
    $row['party1'] = json_decode($row['party1']);
    $row['party2'] = json_decode($row['party2']);
    $row['property_details'] = json_decode($row['property_details']);
    
    // Admin needs user CNIC
    if (!isset($row['user_cnic'])) {
        $row['user_cnic'] = $row['cnic'] ?? '';
    }
    
    $orders[] = $row;
}

jsonResponse(["status" => "success", "orders" => $orders]);
?>
