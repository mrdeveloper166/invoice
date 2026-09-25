<?php

session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if(!isset($_SESSION['admin'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pin = trim($_POST['pin'] ?? '');

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT admin_pin FROM users WHERE role='admin' LIMIT 1"));
if(!$user){
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    exit;
}

$storedPin = trim((string)($user['admin_pin'] ?? ''));
if(strpos($storedPin, 'pin:') === 0){
    $storedPin = trim(substr($storedPin, 4));
}

if($pin === $storedPin){
    echo json_encode(['success' => true]);
}else{
    echo json_encode(['success' => false, 'message' => 'Invalid admin PIN']);
}

?>
