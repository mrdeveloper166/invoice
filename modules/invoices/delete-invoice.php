<?php

session_start();
require_once __DIR__ . "/../../config/database.php";

if(!isset($_SESSION['admin'])){
    header("Location: ../../login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if(!$id){
    header("Location: invoice-list.php?deleted=0");
    exit;
}

$now = date('Y-m-d H:i:s');
mysqli_query($conn, "UPDATE invoices SET deleted_at='$now' WHERE id='$id' AND deleted_at IS NULL");

header("Location: invoice-list.php?deleted=1");

?>
