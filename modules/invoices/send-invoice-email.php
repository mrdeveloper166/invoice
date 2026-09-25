<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../includes/functions.php";

$id = $_GET['id'] ?? '';
if($id === ''){
  header("Location: invoice-list.php?email=failed");
  exit;
}

$id_safe = mysqli_real_escape_string($conn, $id);

$q = mysqli_query($conn, "
  SELECT invoices.id, invoices.invoice_number, invoices.invoice_type, invoices.deleted_at, clients.client_name, clients.email
  FROM invoices
  LEFT JOIN clients ON invoices.client_id = clients.id
  WHERE invoices.id='$id_safe'
  LIMIT 1
");

$row = $q ? mysqli_fetch_assoc($q) : null;
if(!$row || !empty($row['deleted_at'])){
  header("Location: invoice-list.php?email=failed");
  exit;
}

$res = sendInvoiceEmailByInvoiceId($conn, $base_url, $row['id']);
$status = $res['status'] ?? 'failed';
header("Location: invoice-list.php?email=" . urlencode($status));
exit;

