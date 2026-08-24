<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Direct include from api folder
require_once __DIR__ . '/../api/db.php';

if (!isset($conn) && isset($pdo)) {
    $conn = $pdo;
}

if (!isset($conn) || $conn === null) {
    jsonResponse(["status" => "error", "message" => "Database object \$conn is null."], 500);
}

$data = json_decode(file_get_contents("php://input"));
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

try {
    $conn->exec("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 15 MINUTE)");

    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = :ip");
    $stmt->execute([':ip' => $ip_address]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['attempts'] >= 5) {
        jsonResponse(["status" => "error", "message" => "Too many failed login attempts. Please try again in 15 minutes."], 429);
    }
} catch (PDOException $e) {}

if (!empty($data->email) && !empty($data->password)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data->password, $user['password'])) {
            unset($user['password']);
            $token = bin2hex(random_bytes(32));
            
            try {
                $sessionStmt = $conn->prepare("INSERT INTO user_sessions (token, user_id) VALUES (:token, :user_id)");
                $sessionStmt->bindParam(':token', $token);
                $sessionStmt->bindParam(':user_id', $user['id']);
                $sessionStmt->execute();
            } catch (PDOException $e) {}

            jsonResponse([
                "status" => "success",
                "message" => "Login successful.",
                "token" => $token,
                "user" => $user
            ], 200);
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
                $stmt->execute([':ip' => $ip_address]);
            } catch (PDOException $e) {}

            jsonResponse(["status" => "error", "message" => "Invalid email or password."], 401);
        }
    } catch (PDOException $e) {
        jsonResponse(["status" => "error", "message" => "Database query failed: " . $e->getMessage()], 500);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>