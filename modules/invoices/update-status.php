<?php

include "../../config/database.php";

$id = $_POST['id'];
$status = $_POST['status'];

mysqli_query($conn,"UPDATE invoices 
SET status='$status'
WHERE id='$id'");

echo "success";

?>