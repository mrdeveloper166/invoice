<?php
include "../../config/database.php";
include "../../includes/functions.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$id = intval($_POST['id'] ?? 0);
if(!$id){
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <title>Invalid Invoice</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
  <body>
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Invalid invoice',
      confirmButtonText: 'OK'
    }).then(function(){
      window.location = 'invoice-list.php';
    });
  </script>
  </body>
  </html>
  <?php
  exit;
}

// Get Data
$invoice_type = $_POST['invoice_type'] ?? 'gst';
$invoice_number = $_POST['invoice_number'] ?? '';
$invoice_date = $_POST['invoice_date'] ?? '';
$client_id = intval($_POST['client_id'] ?? 0);
$financial_year = $_POST['financial_year'] ?? '';

$place_of_supply = $_POST['place_of_supply'] ?? '';
$payment_terms = $_POST['payment_terms'] ?? '';
$due_date = $_POST['due_date'] ?? '';

$currency = $_POST['currency'] ?? 'INR';
$exchange_rate = $_POST['exchange_rate'] ?? 1;

// GST fix
if($invoice_type === 'gst'){
  $currency = 'INR';
  $exchange_rate = 1;
}

// Escape
$invoice_type = mysqli_real_escape_string($conn, $invoice_type);
$invoice_number = mysqli_real_escape_string($conn, $invoice_number);
$invoice_date = mysqli_real_escape_string($conn, $invoice_date);
$financial_year = mysqli_real_escape_string($conn, $financial_year);
$place_of_supply = mysqli_real_escape_string($conn, $place_of_supply);
$payment_terms = mysqli_real_escape_string($conn, $payment_terms);
$currency = mysqli_real_escape_string($conn, $currency);

// 🔥 FIX: due_date NULL handling
if(empty($due_date)){
  $due_date_sql = "NULL";
}else{
  $due_date_sql = "'" . mysqli_real_escape_string($conn, $due_date) . "'";
}

// Numbers
$subtotal = floatval($_POST['subtotal'] ?? 0);
$cgst = 0;
$sgst = 0;
$igst = 0;
$grand_total = $subtotal;
$exchange_rate = floatval($exchange_rate);

if($invoice_type === 'gst' && $client_id){
  $companyRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gstin, state FROM company_settings LIMIT 1")) ?: [];
  $clientRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gstin, state FROM clients WHERE id='$client_id' LIMIT 1")) ?: [];
  $gst = calculateGstAmounts($subtotal, isInterStateSupply($companyRow, $clientRow));
  $cgst = $gst['cgst'];
  $sgst = $gst['sgst'];
  $igst = $gst['igst'];
  $grand_total = $gst['grand_total'];
}else{
  $grand_total = floatval($_POST['grand_total'] ?? $subtotal);
}

// ✅ UPDATE QUERY FIXED
mysqli_query($conn,"
  UPDATE invoices SET
    client_id='$client_id',
    invoice_type='$invoice_type',
    invoice_number='$invoice_number',
    invoice_date='$invoice_date',
    financial_year='$financial_year',
    place_of_supply='$place_of_supply',
    payment_terms='$payment_terms',
    due_date=$due_date_sql,
    currency='$currency',
    subtotal='$subtotal',
    cgst='$cgst',
    sgst='$sgst',
    igst='$igst',
    grand_total='$grand_total'
  WHERE id='$id'
") or die(mysqli_error($conn));

// Delete old items
mysqli_query($conn,"DELETE FROM invoice_items WHERE invoice_id='$id'");

// Insert new items
$desc = $_POST['description'] ?? [];
$qty = $_POST['qty'] ?? [];
$price = $_POST['price'] ?? [];
$hsn = $_POST['hsn'] ?? [];

for($i=0;$i<count($desc);$i++){
  $description = mysqli_real_escape_string($conn, $desc[$i] ?? '');
  $quantity = floatval($qty[$i] ?? 0);
  $unit_price = floatval($price[$i] ?? 0);
  $hsn_code = mysqli_real_escape_string($conn, $hsn[$i] ?? '');
  $amount = $quantity * $unit_price;

  if($description === '' && $quantity == 0 && $unit_price == 0){
    continue;
  }

  mysqli_query($conn,"
    INSERT INTO invoice_items
    (invoice_id, description, hsn_code, qty, unit_price, amount)
    VALUES
    ('$id','$description','$hsn_code','$quantity','$unit_price','$amount')
  ");
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice Updated</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Invoice Updated Successfully',
    confirmButtonText: 'OK'
  }).then(function(){
    window.location = 'invoice-list.php';
  });
</script>
</body>
</html>