<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";

if(!isset($_SESSION['admin'])){
  header("Location: ../../login.php");
  exit;
}

$companyQ = mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1");
$company = $companyQ ? mysqli_fetch_assoc($companyQ) : null;

$to = $company['email'] ?? '';
if(!$to){
  $dest = "company-profile.php?test_email=missing";
  if(!headers_sent()){
    header("Location: $dest");
    exit;
  }
  echo "<p>Company email missing. <a href=\"".$dest."\">Back</a></p>";
  exit;
}

$res = sendTestEmail($conn, $to);
$status = $res['status'] ?? 'failed';
$err = $res['error'] ?? '';
if($err){
  $_SESSION['test_email_error'] = $err;
}
$dest = "company-profile.php?test_email=" . urlencode($status);
if(!headers_sent()){
  header("Location: $dest");
  exit;
}
echo "<p>Test email status: ".htmlspecialchars($status, ENT_QUOTES)."</p>";
if($err){
  echo "<pre>".htmlspecialchars($err, ENT_QUOTES)."</pre>";
}
echo "<p><a href=\"".$dest."\">Back</a></p>";
exit;

