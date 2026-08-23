<?php
include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

$ip_address = $_SERVER['REMOTE_ADDR'];

// Cleanup old attempts (> 15 mins)
$conn->exec("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 15 MINUTE)");

// Check rate limit (max 5 attempts per 15 mins)
$stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = :ip");
$stmt->execute([':ip' => $ip_address]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['attempts'] >= 5) {
    jsonResponse(["status" => "error", "message" => "Too many failed login attempts. Please try again in 15 minutes."], 429);
}

if (!empty($data->email) && !empty($data->password)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password'])) {
        // Remove password hash from response
        unset($user['password']);
        
        // Generate secure session token
        $token = bin2hex(random_bytes(32));
        
        try {
            // Store in user_sessions table
            $sessionStmt = $conn->prepare("INSERT INTO user_sessions (token, user_id) VALUES (:token, :user_id)");
            $sessionStmt->bindParam(':token', $token);
            $sessionStmt->bindParam(':user_id', $user['id']);
            $sessionStmt->execute();
        } catch (PDOException $e) {
            jsonResponse(["status" => "error", "message" => "Database error."], 500);
        }

        jsonResponse([
            "status" => "success",
            "message" => "Login successful.",
            "token" => $token,
            "user" => $user
        ]);
    } else {
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
        $stmt->execute([':ip' => $ip_address]);
        jsonResponse(["status" => "error", "message" => "Invalid email or password."], 401);
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
