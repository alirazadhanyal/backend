<?php
include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->id_token)) {
    // Verify token with Google
    $verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($data->id_token);
    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status !== 200) {
        jsonResponse(["status" => "error", "message" => "Invalid Google ID token."], 401);
    }

    $token_data = json_decode($response, true);
    if (!isset($token_data['email']) || $token_data['email'] !== $data->email || $token_data['email_verified'] !== 'true') {
        jsonResponse(["status" => "error", "message" => "Email verification failed or mismatched."], 401);
    }

    // Check if user exists by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists, just log them in
        unset($user['password']);
        jsonResponse([
            "status" => "success",
            "message" => "Login successful.",
            "token" => $user['id'],
            "user" => $user
        ]);
    } else {
        // Create new user
        $id = uniqid('usr_g_', true);
        $name = $data->name ?? 'Google User';
        // Give a random password since they use Google
        $hash = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (:id, :name, :email, :password)");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':password', $hash);

        if ($stmt->execute()) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            unset($user['password']);

            jsonResponse([
                "status" => "success",
                "message" => "Google sign in successful.",
                "token" => $id,
                "user" => $user
            ], 201);
        } else {
            jsonResponse(["status" => "error", "message" => "Unable to register via Google."], 500);
        }
    }
} else {
    jsonResponse(["status" => "error", "message" => "Incomplete data."], 400);
}
?>
