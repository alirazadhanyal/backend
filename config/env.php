<?php
// env.php
// Store this file outside your public HTML directory in a real production environment.
$env = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'estamp',
    'DB_USER' => 'estamp_user', // Changed from root for production
    'DB_PASS' => 'your_secure_password_here',
    'ALLOWED_ORIGINS' => [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8080',
        'https://estamp.yourdomain.com'
    ]
];
?>
