<?php

include "../../config/database.php";
include "../../includes/functions.php";
include "../../config/config.php";

/* ================= SAFE INPUT ================= */

$invoice_type = $_POST['invoice_type'] ?? 'gst';

$allowed_types = ['gst','export'];
if(!in_array($invoice_type, $allowed_types)){
    die("Invalid invoice type");
}

$invoice_number = $_POST['invoice_number'] ?? '';
$invoice_date   = $_POST['invoice_date'] ?? '';
$client_id      = $_POST['client_id'] ?? null;

$financial_year = $_POST['financial_year'] ?? '';

$place_of_supply = $_POST['place_of_supply'] ?? '';
$payment_terms   = $_POST['payment_terms'] ?? '';
$due_date        = $_POST['due_date'] ?? null;

$currency = $_POST['currency'] ?? 'INR';
$exchange_rate = $_POST['exchange_rate'] ?? 1;

// GST fix
if($invoice_type === 'gst'){
    $currency = 'INR';
    $exchange_rate = 1;
}

$subtotal    = floatval($_POST['subtotal'] ?? 0);
$cgst        = 0;
$sgst        = 0;
$igst        = 0;
$grand_total = $subtotal;

if($invoice_type === 'gst' && !empty($client_id)){
    $companyRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gstin, state FROM company_settings LIMIT 1")) ?: [];
    $clientRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gstin, state FROM clients WHERE id='".intval($client_id)."' LIMIT 1")) ?: [];
    $gst = calculateGstAmounts($subtotal, isInterStateSupply($companyRow, $clientRow));
    $cgst = $gst['cgst'];
    $sgst = $gst['sgst'];
    $igst = $gst['igst'];
    $grand_total = $gst['grand_total'];
}else{
    $grand_total = floatval($_POST['grand_total'] ?? $subtotal);
}

/* ================= FIX VALUES ================= */

// client_id fix
$client_id_sql = !empty($client_id) ? (int)$client_id : "NULL";

// due_date fix
$due_date_sql = !empty($due_date) ? "'$due_date'" : "NULL";

/* ================= INSERT QUERY ================= */

$query = "INSERT INTO invoices
(
invoice_number,
invoice_type,
client_id,
invoice_date,
financial_year,
place_of_supply,
payment_terms,
due_date,
currency,
subtotal,
cgst,
sgst,
igst,
grand_total,
status
)

VALUES
(
'$invoice_number',
'$invoice_type',
$client_id_sql,
'$invoice_date',
'$financial_year',
'$place_of_supply',
'$payment_terms',
$due_date_sql,
'$currency',
'$subtotal',
'$cgst',
'$sgst',
'$igst',
'$grand_total',
'unpaid'
)";

if(!mysqli_query($conn, $query)){
    die("SQL Error: " . mysqli_error($conn));
}

$invoice_id = mysqli_insert_id($conn);

/* ================= SAVE ITEMS ================= */

$desc  = $_POST['description'] ?? [];
$qty   = $_POST['qty'] ?? [];
$price = $_POST['price'] ?? [];
$hsn   = $_POST['hsn'] ?? [];

for($i=0; $i<count($desc); $i++){

    if(empty($desc[$i])) continue;

    $description = $desc[$i];
    $quantity    = $qty[$i] ?? 0;
    $unit_price  = $price[$i] ?? 0;
    $hsn_code    = $hsn[$i] ?? '';

    $amount = $quantity * $unit_price;

    mysqli_query($conn,"INSERT INTO invoice_items
    (
    invoice_id,
    description,
    hsn_code,
    qty,
    unit_price,
    amount
    )
    VALUES
    (
    '$invoice_id',
    '$description',
    '$hsn_code',
    '$quantity',
    '$unit_price',
    '$amount'
    )");
}

/* ================= EMAIL ================= */

$send_email = isset($_POST['send_email']) && $_POST['send_email'] == '1';
$emailStatus = null;

if($send_email){
    $res = sendInvoiceEmailByInvoiceId($conn, $base_url, $invoice_id);

    if(($res['status'] ?? '') === 'missing'){
        $emailStatus = 'skipped';
    }else{
        $emailStatus = $res['status'] ?? 'failed';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice Created</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
Swal.fire({
    icon: 'success',
    title: 'Invoice Created Successfully',
    <?php if($send_email){ ?>
    text: <?php
        if($emailStatus === 'sent'){
            echo json_encode("Email sent successfully ✅");
        }elseif($emailStatus === 'skipped'){
            echo json_encode("Client email missing ⚠️");
        }else{
            echo json_encode("Email failed ❌ (Check SMTP)");
        }
    ?>,
    <?php } ?>
    confirmButtonText: 'OK'
}).then(function(){
    window.location = 'invoice-list.php';
});
</script>

</body>
</html>