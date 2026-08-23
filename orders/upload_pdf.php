<?php
include_once '../db.php';

$user = getAuthUser($conn);

// Only admins can upload completed PDFs
if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
    jsonResponse(["status" => "error", "message" => "Unauthorized."], 403);
}

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    jsonResponse(["status" => "error", "message" => "Missing order_id."], 400);
}
$order_id = $_POST['order_id'];

if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(["status" => "error", "message" => "Missing or invalid file upload."], 400);
}

$fileTmpPath = $_FILES['pdf_file']['tmp_name'];
$fileName = $_FILES['pdf_file']['name'];
$fileSize = $_FILES['pdf_file']['size'];
$fileType = $_FILES['pdf_file']['type'];

$fileNameCmps = explode(".", $fileName);
$fileExtension = strtolower(end($fileNameCmps));

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fileTmpPath);
finfo_close($finfo);

if ($fileExtension !== 'pdf' || $mime !== 'application/pdf') {
    jsonResponse(["status" => "error", "message" => "Only PDF files are allowed."], 400);
}

// Ensure uploads directory exists
$uploadFileDir = '../uploads/completed_pdfs/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0755, true);
}

// Generate unique file name
$newFileName = md5(time() . $fileName) . '.pdf';
$dest_path = $uploadFileDir . $newFileName;

if (move_uploaded_file($fileTmpPath, $dest_path)) {
    // Generate public URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host . "/php_backend";
    $publicUrl = $baseUrl . '/uploads/completed_pdfs/' . $newFileName;
    
    // Update the database order
    $stmt = $conn->prepare("UPDATE orders SET completed_pdf_url = :pdf_url, status = 'completed' WHERE order_id = :order_id");
    $stmt->bindParam(':pdf_url', $publicUrl);
    $stmt->bindParam(':order_id', $order_id);
    
    if ($stmt->execute()) {
        include_once 'commission_helper.php';
        calculateAndApplyCommission($conn, $order_id);
        
        jsonResponse(["status" => "success", "message" => "File uploaded successfully.", "url" => $publicUrl]);
    } else {
        jsonResponse(["status" => "error", "message" => "File saved but database update failed."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "There was an error moving the uploaded file."], 500);
}
?>
