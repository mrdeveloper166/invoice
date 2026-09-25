<?php

session_start();
require_once __DIR__ . "/../../config/database.php";

if(!isset($_SESSION['admin'])){
    header("Location: ../../login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$pin = trim($_GET['pin'] ?? '');

if(!$id || !$pin){
    header("Location: recycle-bin.php?restored=0");
    exit;
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT admin_pin FROM users WHERE role='admin' LIMIT 1"));
$storedPin = trim((string)($user['admin_pin'] ?? ''));
if(strpos($storedPin, 'pin:') === 0){
    $storedPin = trim(substr($storedPin, 4));
}

if($pin !== $storedPin){
    header("Location: recycle-bin.php?restored=0&error=pin");
    exit;
}

$check = mysqli_query($conn, "SELECT id FROM invoices WHERE id='$id' AND deleted_at IS NOT NULL LIMIT 1");
if(!$check || mysqli_num_rows($check) === 0){
    header("Location: recycle-bin.php?restored=0");
    exit;
}

mysqli_query($conn, "UPDATE invoices SET deleted_at=NULL WHERE id='$id'");

header("Location: recycle-bin.php?restored=1");

?>
