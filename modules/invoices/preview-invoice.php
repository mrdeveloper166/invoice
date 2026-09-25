<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";

$company = mysqli_query($conn,"SELECT * FROM company_settings LIMIT 1");
$company = mysqli_fetch_assoc($company);

$invoice_type = $_POST['invoice_type'] ?? 'gst';
if($invoice_type !== 'gst' && $invoice_type !== 'export'){
  $invoice_type = 'gst';
}

$invoice_number = $_POST['invoice_number'] ?? '';
$invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
$client_id = $_POST['client_id'] ?? '';
$financial_year = $_POST['financial_year'] ?? '';

$place_of_supply = $_POST['place_of_supply'] ?? '';
$payment_terms = $_POST['payment_terms'] ?? '';
$due_date = $_POST['due_date'] ?? '';

$currency = $_POST['currency'] ?? 'INR';
if($invoice_type === 'gst'){
  $currency = 'INR';
}

$subtotal = $_POST['subtotal'] ?? 0;
$cgst = $_POST['cgst'] ?? 0;
$sgst = $_POST['sgst'] ?? 0;
$igst = $_POST['igst'] ?? 0;
$grand_total = $_POST['grand_total'] ?? 0;

$client = [
  'client_name' => '',
  'company_name' => '',
  'email' => '',
  'phone' => '',
  'address' => '',
  'state' => '',
  'country' => '',
  'gstin' => '',
  'ved_no' => ''
];

if($client_id !== ''){
  $client_id_safe = mysqli_real_escape_string($conn, $client_id);
  $cq = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id_safe' LIMIT 1");
  if($cq){
    $row = mysqli_fetch_assoc($cq);
    if($row){
      $client = array_merge($client, $row);
    }
  }
}

// Build $data in the same shape templates expect (invoice + client fields)
$data = array_merge($client, [
  'invoice_number' => $invoice_number,
  'invoice_type' => $invoice_type,
  'invoice_date' => $invoice_date,
  'financial_year' => $financial_year,
  'place_of_supply' => $place_of_supply,
  'payment_terms' => $payment_terms,
  'due_date' => $due_date,
  'currency' => $currency,
  'subtotal' => $subtotal,
  'cgst' => $cgst,
  'sgst' => $sgst,
  'igst' => $igst,
  'grand_total' => $grand_total,
  'status' => 'unpaid'
]);

// Items (same shape as invoice_items table/template expects)
$desc = $_POST['description'] ?? [];
$qty = $_POST['qty'] ?? [];
$price = $_POST['price'] ?? [];
$hsn = $_POST['hsn'] ?? [];

$items_arr = [];
$count = count($desc);
for($i=0; $i<$count; $i++){
  $d = $desc[$i] ?? '';
  $q = $qty[$i] ?? 0;
  $p = $price[$i] ?? 0;
  $h = $hsn[$i] ?? '';
  $amt = ((float)$q) * ((float)$p);

  $items_arr[] = [
    'description' => $d,
    'hsn_code' => $h,
    'qty' => $q,
    'unit_price' => $p,
    'amount' => $amt
  ];
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice Preview <?php echo htmlspecialchars($invoice_number, ENT_QUOTES); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ font-family: Arial; background:#fff; }
    .print-btn{ margin: 18px 0; }
    @media print{ .print-btn{ display:none; } }
  </style>
</head>
<body>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center print-btn">
      <button onclick="window.print()" class="btn btn-primary">
        Print / Save PDF
      </button>
      <span class="text-muted small">Preview (not saved yet)</span>
    </div>

    <?php
      if($invoice_type === 'gst'){
        include __DIR__ . "/../../templates/invoice-gst.php";
      }else{
        include __DIR__ . "/../../templates/invoice-export.php";
      }
    ?>
  </div>
</body>
</html>

