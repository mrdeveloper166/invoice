<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";

$company = mysqli_query($conn,"SELECT * FROM company_settings LIMIT 1");
$company = mysqli_fetch_assoc($company);

$id = $_GET['id'];

$invoice = mysqli_query($conn,"
SELECT invoices.*, clients.*
FROM invoices
LEFT JOIN clients ON invoices.client_id = clients.id
WHERE invoices.id='$id'
");

$data = mysqli_fetch_assoc($invoice);

$items = mysqli_query($conn,"
SELECT * FROM invoice_items
WHERE invoice_id='$id'
");

$type = $data['invoice_type'];

?>

<!DOCTYPE html>
<html>
<head>

<title>Invoice <?php echo $data['invoice_number']; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
font-family: Arial;
background:#fff;
}

.print-btn{
margin-bottom:20px;
}

@media print{

.print-btn{
display:none;
}

  .container{
    margin:0 !important;
    padding:0 !important;
    max-width:100% !important;
  }

}

</style>

</head>

<body>

<div class="container mt-4">

<button onclick="window.print()" class="btn btn-primary print-btn">
Print / Save PDF
</button>

<?php
// Load items into an array so templates can iterate cleanly
$items_arr = [];
while($item = mysqli_fetch_assoc($items)){
    $items_arr[] = $item;
}

if($type === 'gst'){
    include __DIR__ . "/../../templates/invoice-gst.php";
}else{
    include __DIR__ . "/../../templates/invoice-export.php";
}
?>

</div>

</body>
</html>