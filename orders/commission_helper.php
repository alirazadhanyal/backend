<?php
// commission_helper.php
function calculateAndApplyCommission($conn, $orderId) {
    // Fetch order details
    $stmtOrder = $conn->prepare("SELECT assigned_admin_id, flow_type, stamp_value, service_fee, commission_status FROM orders WHERE order_id = :oid");
    $stmtOrder->execute([':oid' => $orderId]);
    $orderRow = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if ($orderRow && !empty($orderRow['assigned_admin_id']) && $orderRow['commission_status'] !== 'paid') {
        $adminId = $orderRow['assigned_admin_id'];
        $orderType = $orderRow['flow_type'];
        $orderAmount = (float)($orderRow['stamp_value'] + $orderRow['service_fee']);

        // Get admin rate
        $stmtRate = $conn->prepare("
            SELECT commission_type, commission_value FROM admin_commission_rates 
            WHERE admin_id = :aid AND order_type = :otype
        ");
        $stmtRate->execute([':aid' => $adminId, ':otype' => $orderType]);
        $rateRow = $stmtRate->fetch(PDO::FETCH_ASSOC);

        if (!$rateRow) {
            $stmtUser = $conn->prepare("SELECT commission_type, commission_value FROM users WHERE id = :aid");
            $stmtUser->execute([':aid' => $adminId]);
            $rateRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        }

        if ($rateRow) {
            $cType = $rateRow['commission_type'];
            $cValue = (float)$rateRow['commission_value'];
            $cAmount = 0.0;

            // Admin's Cut Logic: the value represents what the ADMIN receives
            if ($cType === 'percentage') {
                $cAmount = $orderAmount * ($cValue / 100);
            } else { // fixed
                $cAmount = $cValue;
            }
            if ($cAmount < 0) $cAmount = 0;

            // Apply commission directly to database
            $stmtUpdate = $conn->prepare("UPDATE orders SET commission_amount = :c_amount, commission_status = 'pending' WHERE order_id = :oid");
            $stmtUpdate->execute([':c_amount' => $cAmount, ':oid' => $orderId]);
        }
    }
}
?>
