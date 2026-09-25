<?php
include "../../config/database.php";

$id = intval($_GET['id'] ?? 0);
if(!$id){
    header("Location: client-list.php?deleted=0");
    exit;
}

mysqli_query($conn,"DELETE FROM clients WHERE id='$id'");

header("Location: client-list.php?deleted=1");
exit;