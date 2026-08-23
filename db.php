<?php
// db.php
include_once __DIR__ . '/config/env.php';

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $env['ALLOWED_ORIGINS'])) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Credentials from env.php
$host = $env['DB_HOST'];
$db_name = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASS'];

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");
    
    // Auto-create user_sessions table if it doesn't exist to prevent login crashes
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `user_sessions` (
          `token` VARCHAR(128) PRIMARY KEY,
          `user_id` VARCHAR(50),
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )
    ");

    // Auto-create login_attempts table for rate limiting
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
          `ip_address` VARCHAR(45) NOT NULL,
          `attempt_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX (`ip_address`)
        )
    ");

    // Auto-create admin_actions_log table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `admin_actions_log` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `admin_id` VARCHAR(50),
          `action_type` VARCHAR(50),
          `action_details` TEXT,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");
} catch(PDOException $exception) {
    echo json_encode(["status" => "error", "message" => "Database connection error."]);
    exit;
}

// Helper to return json response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Simple authentication token verification for endpoints
// For a production app, use JWT. Here we pass userId directly for simplicity.
function getAuthUser($conn) {
    $token = null;
    
    // Check custom X-Auth-Token fallback (avoids Apache stripping Authorization header)
    if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $token = $_SERVER['HTTP_X_AUTH_TOKEN'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } else {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, 'Authorization') === 0) {
                $token = str_replace('Bearer ', '', $value);
                break;
            } elseif (strcasecmp($key, 'X-Auth-Token') === 0) {
                $token = $value;
                break;
            }
        }
    }

    if ($token) {
        $stmt = $conn->prepare("
            SELECT u.* 
            FROM users u
            JOIN user_sessions s ON u.id = s.user_id
            WHERE s.token = :token
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return $user;
        }
    }
    jsonResponse(["status" => "error", "message" => "Unauthorized"], 401);
}
?>
