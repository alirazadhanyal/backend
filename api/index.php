<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// TiDB Cloud Connection test
$host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_NAME') ?: 'e_stamp_db';
$user = getenv('DB_USER') ?: '31KGhSXrpU14Xtf.root';
$pass = getenv('DB_PASS') ?: 'F6AsGtn5jWpSnjT0';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo json_encode(["status" => "success", "message" => "TiDB Port 4000 Connected Successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}