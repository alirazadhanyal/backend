<?php
include_once 'db.php';

$user = getAuthUser($conn);

if (isset($_FILES['file'])) {
    $target_dir = "uploads/";
    // Ensure directory exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($file_extension, $allowed_extensions)) {
        jsonResponse(["status" => "error", "message" => "Invalid file extension. Only JPG, PNG, and PDF are allowed."], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($mime, $allowed_mimes)) {
        jsonResponse(["status" => "error", "message" => "Invalid file format."], 400);
    }

    $new_filename = md5(uniqid('doc_', true)) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Return the full URL if possible, or just relative path.
        // E.g. "https://your-domain.com/php_backend/uploads/doc_123.pdf"
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['REQUEST_URI']);
        
        $file_url = $protocol . "://" . $host . $path . "/" . $target_file;
        
        jsonResponse([
            "status" => "success", 
            "message" => "File uploaded successfully",
            "fileUrl" => $file_url
        ]);
    } else {
        jsonResponse(["status" => "error", "message" => "Sorry, there was an error uploading your file."], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "No file sent."], 400);
}
?>
