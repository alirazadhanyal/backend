<?php
include_once '../db.php';

$user = getAuthUser($conn);
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->orderId) && !empty($data->flowType)) {
    // Orders wait for manual assignment after payment verification.
    $assignedAdminId = null;

    $stmt = $conn->prepare("REPLACE INTO orders (
        order_id, user_id, flow_type, purpose, status, stamp_value, service_type, service_fee,
        party1, party2, property_details, additional_instructions, stamp_pdf_url, completed_pdf_url,
        payment_status, payment_reference, user_disclaimer_accepted, user_disclaimer_accepted_at, pay_stamp_fee_myself, assigned_admin_id
    ) VALUES (
        :order_id, :user_id, :flow_type, :purpose, :status, :stamp_value, :service_type, :service_fee,
        :party1, :party2, :property_details, :additional_instructions, :stamp_pdf_url, :completed_pdf_url,
        :payment_status, :payment_reference, :user_disclaimer_accepted, :user_disclaimer_accepted_at, :pay_stamp_fee_myself, :assigned_admin_id
    )");

    $orderId = $data->orderId;
    $status = $data->status ?? 'pending_payment';
    $stampValue = $data->stampValue ?? 0;
    $serviceFee = $data->serviceFee ?? 0;
    
    $party1 = isset($data->party1) ? (is_string($data->party1) ? $data->party1 : json_encode($data->party1)) : '{}';
    $party2 = isset($data->party2) ? (is_string($data->party2) ? $data->party2 : json_encode($data->party2)) : '{}';
    $propertyDetails = isset($data->propertyDetails) ? (is_string($data->propertyDetails) ? $data->propertyDetails : json_encode($data->propertyDetails)) : '{}';
    
    $userDisclaimerAcceptedAt = isset($data->userDisclaimerAcceptedAt) ? date('Y-m-d H:i:s', strtotime($data->userDisclaimerAcceptedAt)) : null;
    $userDisclaimerAccepted = isset($data->userDisclaimerAccepted) && $data->userDisclaimerAccepted ? 1 : 0;
    $payStampFeeMyself = isset($data->payStampFeeMyself) && $data->payStampFeeMyself ? 1 : 0;

    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':flow_type', $data->flowType);
    $stmt->bindParam(':purpose', $data->purpose);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':stamp_value', $stampValue);
    $stmt->bindParam(':service_type', $data->serviceType);
    $stmt->bindParam(':service_fee', $serviceFee);
    $stmt->bindParam(':party1', $party1);
    $stmt->bindParam(':party2', $party2);
    $stmt->bindParam(':property_details', $propertyDetails);
    $stmt->bindParam(':additional_instructions', $data->additionalInstructions);
    $stmt->bindParam(':stamp_pdf_url', $data->stampPdfUrl);
    $stmt->bindParam(':completed_pdf_url', $data->completedPdfUrl);
    $stmt->bindParam(':payment_status', $data->paymentStatus);
    $stmt->bindParam(':payment_reference', $data->paymentReference);
    $stmt->bindParam(':user_disclaimer_accepted', $userDisclaimerAccepted);
    $stmt->bindParam(':user_disclaimer_accepted_at', $userDisclaimerAcceptedAt);
    $stmt->bindParam(':pay_stamp_fee_myself', $payStampFeeMyself);
    $stmt->bindParam(':assigned_admin_id', $assignedAdminId);

    if ($stmt->execute()) {
        jsonResponse(["status" => "success", "message" => "Order created.", "orderId" => $orderId], 201);
    } else {
        jsonResponse(["status" => "error", "message" => "Unable to create order."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
