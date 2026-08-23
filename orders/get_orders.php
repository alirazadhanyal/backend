<?php
include_once '../db.php';

$user = getAuthUser($conn);

$stmt = $conn->prepare("SELECT o.*, u.cnic FROM orders o JOIN users u ON o.user_id = u.id WHERE o.user_id = :user_id AND o.status != 'deleted' ORDER BY o.created_at DESC");
$stmt->bindParam(':user_id', $user['id']);
$stmt->execute();

$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Decode JSON strings back to arrays so the response is clean JSON
    $row['party1'] = json_decode($row['party1']);
    $row['party2'] = json_decode($row['party2']);
    $row['property_details'] = json_decode($row['property_details']);
    // Format camelCase for Flutter app
    $camelRow = [
        'orderId' => $row['order_id'],
        'userId' => $row['user_id'],
        'flowType' => $row['flow_type'],
        'purpose' => $row['purpose'],
        'status' => $row['status'],
        'stampValue' => (int)$row['stamp_value'],
        'serviceType' => $row['service_type'],
        'serviceFee' => (int)$row['service_fee'],
        'party1' => $row['party1'] ?? new stdClass(),
        'party2' => $row['party2'] ?? new stdClass(),
        'propertyDetails' => $row['property_details'] ?? new stdClass(),
        'additionalInstructions' => $row['additional_instructions'],
        'stampPdfUrl' => $row['stamp_pdf_url'],
        'completedPdfUrl' => $row['completed_pdf_url'],
        'paymentStatus' => $row['payment_status'],
        'paymentReference' => $row['payment_reference'],
        'userDisclaimerAccepted' => (bool)$row['user_disclaimer_accepted'],
        'userDisclaimerAcceptedAt' => $row['user_disclaimer_accepted_at'],
        'payStampFeeMyself' => (bool)$row['pay_stamp_fee_myself'],
        'adminNotes' => $row['admin_notes'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
        'deliveredAt' => $row['delivered_at'],
        'workStartTime' => isset($row['work_start_time']) ? $row['work_start_time'] : null,
        'scheduledTime' => isset($row['scheduled_time']) ? $row['scheduled_time'] : null,
        'userCnic' => $row['cnic'] ?? ''
    ];
    $orders[] = $camelRow;
}

jsonResponse(["status" => "success", "data" => $orders]);
?>
