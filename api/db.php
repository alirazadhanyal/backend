<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_CA => true, // TiDB Cloud SSL Required
    ]);
    
    // Alias for compatibility if login.php uses $pdo
    $pdo = $conn;

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => false, 
        "message" => "Database connection error: " . $e->getMessage()
    ]);
    exit();
}
?>