<?php
include_once '../db.php';

$user = getAuthUser($conn);
unset($user['password']);

jsonResponse(["status" => "success", "data" => $user]);
?>
