<?php

function getCurrencySymbol($currency){
  $map = [
    'INR' => '₹',
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
  ];
  $code = strtoupper(trim((string)$currency));
  return $map[$code] ?? $code;
}

function formatMoney($amount, $currency){
  return getCurrencySymbol($currency) . ' ' . number_format((float)$amount, 2);
}

function indianGstStateCodes(){
  return [
    '01' => 'Jammu and Kashmir',
    '02' => 'Himachal Pradesh',
    '03' => 'Punjab',
    '04' => 'Chandigarh',
    '05' => 'Uttarakhand',
    '06' => 'Haryana',
    '07' => 'Delhi',
    '08' => 'Rajasthan',
    '09' => 'Uttar Pradesh',
    '10' => 'Bihar',
    '11' => 'Sikkim',
    '12' => 'Arunachal Pradesh',
    '13' => 'Nagaland',
    '14' => 'Manipur',
    '15' => 'Mizoram',
    '16' => 'Tripura',
    '17' => 'Meghalaya',
    '18' => 'Assam',
    '19' => 'West Bengal',
    '20' => 'Jharkhand',
    '21' => 'Odisha',
    '22' => 'Chhattisgarh',
    '23' => 'Madhya Pradesh',
    '24' => 'Gujarat',
    '26' => 'Dadra and Nagar Haveli and Daman and Diu',
    '27' => 'Maharashtra',
    '29' => 'Karnataka',
    '30' => 'Goa',
    '31' => 'Lakshadweep',
    '32' => 'Kerala',
    '33' => 'Tamil Nadu',
    '34' => 'Puducherry',
    '35' => 'Andaman and Nicobar Islands',
    '36' => 'Telangana',
    '37' => 'Andhra Pradesh',
    '38' => 'Ladakh',
  ];
}

function gstinStateCode($gstin){
  $g = strtoupper(preg_replace('/\s+/', '', (string)$gstin));
  if(strlen($g) >= 2 && ctype_digit(substr($g, 0, 2))){
    return substr($g, 0, 2);
  }
  return '';
}

function normalizeGstStateName($name){
  $s = strtolower(trim(preg_replace('/\s+/', ' ', (string)$name)));
  $s = str_replace(['.', ','], '', $s);
  $aliases = [
    'up' => 'uttar pradesh',
    'u p' => 'uttar pradesh',
    'uk' => 'uttarakhand',
    'ua' => 'uttarakhand',
    'tn' => 'tamil nadu',
    'ap' => 'andhra pradesh',
    'hp' => 'himachal pradesh',
    'mp' => 'madhya pradesh',
    'wb' => 'west bengal',
    'nct of delhi' => 'delhi',
    'new delhi' => 'delhi',
    'orissa' => 'odisha',
    'pondicherry' => 'puducherry',
  ];
  return $aliases[$s] ?? $s;
}

function resolvePartyGstState($state, $gstin){
  $state = trim((string)$state);
  if($state !== ''){
    return $state;
  }
  $code = gstinStateCode($gstin);
  $map = indianGstStateCodes();
  return $map[$code] ?? '';
}

function isInterStateSupply($company, $client){
  $companyGstin = $company['gstin'] ?? '';
  $clientGstin = $client['gstin'] ?? '';
  $companyCode = gstinStateCode($companyGstin);
  $clientCode = gstinStateCode($clientGstin);
  if($companyCode !== '' && $clientCode !== ''){
    return $companyCode !== $clientCode;
  }

  $companyState = resolvePartyGstState($company['state'] ?? '', $companyGstin);
  $clientState = resolvePartyGstState($client['state'] ?? '', $clientGstin);
  if($companyState === '' || $clientState === ''){
    return false;
  }
  return normalizeGstStateName($companyState) !== normalizeGstStateName($clientState);
}

function calculateGstAmounts($subtotal, $isInterState){
  $subtotal = round((float)$subtotal, 2);
  if($isInterState){
    $igst = round($subtotal * 0.18, 2);
    return [
      'cgst' => 0.00,
      'sgst' => 0.00,
      'igst' => $igst,
      'grand_total' => round($subtotal + $igst, 2),
    ];
  }
  $cgst = round($subtotal * 0.09, 2);
  $sgst = round($subtotal * 0.09, 2);
  return [
    'cgst' => $cgst,
    'sgst' => $sgst,
    'igst' => 0.00,
    'grand_total' => round($subtotal + $cgst + $sgst, 2),
  ];
}

function embedUploadImagesForPdf($html, $rootDir){
  $rootDir = rtrim(str_replace('\\', '/', (string)realpath($rootDir)), '/');
  if($rootDir === ''){
    return $html;
  }

  return preg_replace_callback(
    '/\bsrc=(["\'])(?:https?:\/\/[^"\']*\/uploads\/|(?:\.\.\/)*uploads\/)([^"\']+)\1/i',
    function($matches) use ($rootDir){
      $relPath = 'uploads/' . rawurldecode(str_replace('\\', '/', $matches[2]));
      $absPath = $rootDir . '/' . $relPath;

      if(!is_file($absPath)){
        return $matches[0];
      }

      $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
      $binary = (string)file_get_contents($absPath);
      $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
      ];
      $mime = $mimeMap[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($absPath) : 'application/octet-stream');

      if($ext === 'webp' && function_exists('imagecreatefromwebp')){
        $im = @imagecreatefromwebp($absPath);
        if($im){
          ob_start();
          imagepng($im);
          $pngData = ob_get_clean();
          imagedestroy($im);
          if($pngData){
            $binary = $pngData;
            $mime = 'image/png';
          }
        }
      }

      $data = base64_encode($binary);

      return 'src=' . $matches[1] . 'data:' . $mime . ';base64,' . $data . $matches[1];
    },
    $html
  );
}

function normalizeCurrencyForPdf($html){
  $html = str_replace('₹', 'Rs.', $html);
  $html = preg_replace('/€\s*/u', 'EUR ', $html);
  return $html;
}

function getPdfPaperSize(){
  static $cached = null;
  if($cached !== null){
    return $cached;
  }

  $pdf_paper_size = 'A4';
  $configFile = __DIR__ . '/../config/config.php';
  if(is_file($configFile)){
    include $configFile;
  }

  $paper = strtolower(trim((string)($pdf_paper_size ?? 'a4')));
  $cached = ($paper === 'letter') ? 'letter' : 'a4';
  return $cached;
}

function injectPdfInvoiceStyles($html){
  $paper = getPdfPaperSize();
  $pageSize = ($paper === 'letter') ? 'letter portrait' : 'A4 portrait';

  $pdfCss = <<<CSS
<style id="pdf-overrides">
  @page { size: {$pageSize}; margin: 10mm 10mm 16mm 10mm; }
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    font-family: DejaVu Sans, sans-serif !important;
    font-size: 11px !important;
  }
  .invoice-shell { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
  .invoice-wrap {
    display: block !important;
    position: relative !important;
    overflow: visible !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    page-break-inside: auto !important;
    font-family: DejaVu Sans, sans-serif !important;
  }
  .invoice-body {
    position: relative !important;
    z-index: 1 !important;
    display: block !important;
    min-height: 0 !important;
    padding-bottom: 12mm !important;
  }
  .invoice-watermark {
    position: fixed !important;
    top: 38% !important;
    left: 0 !important;
    width: 100% !important;
    height: auto !important;
    text-align: center !important;
    z-index: 0 !important;
  }
  .invoice-watermark img {
    display: inline-block !important;
    max-width: 220px !important;
    max-height: 220px !important;
    width: auto !important;
    height: auto !important;
    opacity: 0.1 !important;
    margin: 0 !important;
    float: none !important;
  }
  .inv-header {
    display: block !important;
    width: 100% !important;
    overflow: hidden !important;
    margin-bottom: 8px !important;
    page-break-inside: avoid !important;
  }
  .brand-left {
    display: block !important;
    float: left !important;
    width: 62% !important;
    overflow: hidden !important;
  }
  .brand-left img {
    float: left !important;
    display: block !important;
    max-height: 50px !important;
    max-width: 120px !important;
    margin-right: 10px !important;
    margin-bottom: 6px !important;
  }
  .brand-text { overflow: hidden !important; }
  .brand-text h3 { font-size: 16px !important; margin: 0 0 4px !important; }
  .brand-text p { font-size: 10px !important; line-height: 1.4 !important; margin: 0 !important; }
  .meta-right {
    float: right !important;
    width: 36% !important;
    text-align: right !important;
    margin-bottom: 0 !important;
  }
  .meta-right .doc-title { font-size: 18px !important; }
  .divider { clear: both !important; margin: 10px 0 !important; }
  .party-row {
    display: block !important;
    width: 100% !important;
    overflow: hidden !important;
    margin-bottom: 8px !important;
    page-break-inside: avoid !important;
  }
  .party-col {
    width: 48% !important;
    float: left !important;
    display: block !important;
    vertical-align: top !important;
    margin-bottom: 6px !important;
    font-size: 11px !important;
  }
  .party-col + .party-col { float: right !important; }
  table.items {
    width: 100% !important;
    clear: both !important;
    margin-top: 8px !important;
    font-size: 10px !important;
    page-break-inside: auto !important;
  }
  table.items thead { display: table-header-group !important; }
  table.items tr { page-break-inside: avoid !important; }
  table.items th, table.items td { padding: 6px 8px !important; }
  .totals-wrap {
    display: block !important;
    clear: both !important;
    text-align: right !important;
    margin-top: 8px !important;
    page-break-inside: avoid !important;
  }
  table.summary {
    margin-left: auto !important;
    width: 260px !important;
    font-size: 10px !important;
    page-break-inside: avoid !important;
  }
  .footer-grid {
    display: block !important;
    clear: both !important;
    width: 100% !important;
    overflow: hidden !important;
    margin-top: 10px !important;
    page-break-inside: avoid !important;
  }
  .footer-grid .bank {
    float: left !important;
    width: 58% !important;
    display: block !important;
    font-size: 10px !important;
  }
  .footer-grid .sign {
    float: right !important;
    width: 38% !important;
    display: block !important;
    text-align: right !important;
  }
  .footer-grid .sign img {
    display: block !important;
    margin-left: auto !important;
    max-width: 140px !important;
    height: auto !important;
  }
  .note-box {
    clear: both !important;
    margin-top: 8px !important;
    font-size: 10px !important;
    page-break-inside: avoid !important;
  }
  .status-pill { display: inline-block !important; }
  .print-footnote {
    position: fixed !important;
    left: 10mm !important;
    right: 10mm !important;
    bottom: 5mm !important;
    margin: 0 !important;
    padding: 4px 0 0 !important;
    text-align: center !important;
    font-size: 9px !important;
    color: #6b7280 !important;
    border-top: 1px solid #e5e7eb !important;
    background: #fff !important;
  }
</style>
CSS;

  if(stripos($html, '</head>') !== false){
    return str_ireplace('</head>', $pdfCss . '</head>', $html);
  }

  return $pdfCss . $html;
}

function prepareInvoiceHtmlForPdf($html, $rootDir){
  $html = embedUploadImagesForPdf($html, $rootDir);
  $html = normalizeCurrencyForPdf($html);
  return injectPdfInvoiceStyles($html);
}

function getFinancialYear(){

$year = date('Y');
$month = date('m');

if($month >= 4){

$start = $year;
$end = $year + 1;

}else{

$start = $year - 1;
$end = $year;

}

$fy = substr($start,2)."-".substr($end,2);

return $fy;

}


function generateInvoiceNumber($type,$conn){

$fy = getFinancialYear();

if($type == "gst"){
$prefix = "INV";
}else{
$prefix = "EXP";
}

$pattern_prefix = $prefix."/FY".$fy."/";

// Get current max sequence for this FY + prefix (more reliable than ORDER BY id)
$maxSeq = 0;
$safeLike = mysqli_real_escape_string($conn, $pattern_prefix) . '%';
$q = mysqli_query(
    $conn,
    "SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number,'/',-1) AS UNSIGNED)) AS max_seq
     FROM invoices
     WHERE invoice_number LIKE '$safeLike'"
);
if($q){
    $row = mysqli_fetch_assoc($q);
    if($row && $row['max_seq'] !== null){
        $maxSeq = intval($row['max_seq']);
    }
}

// Generate next number and ensure it's unique (no duplicates)
$seqNum = $maxSeq + 1;
while(true){
    $seq = str_pad($seqNum, 3, "0", STR_PAD_LEFT);
    $invoiceNo = $pattern_prefix.$seq;

    $safeInvoiceNo = mysqli_real_escape_string($conn, $invoiceNo);
    $existsQ = mysqli_query($conn, "SELECT 1 FROM invoices WHERE invoice_number='$safeInvoiceNo' LIMIT 1");
    if(!$existsQ || mysqli_num_rows($existsQ) === 0){
        return $invoiceNo;
    }
    $seqNum++;
}

}

function sendTestEmail($conn, $to){
  $autoload = __DIR__ . "/../vendor/autoload.php";
  if(!file_exists($autoload)){
    return ['status' => 'failed', 'error' => 'autoload_missing'];
  }
  require_once $autoload;

  $companyQ = mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1");
  $company = $companyQ ? mysqli_fetch_assoc($companyQ) : null;
  if(!$company){
    $company = [];
  }

  $fromName = $company['company_name'] ?? 'Invoice System';
  $fromEmail = $company['email'] ?? 'vermaabhishek79326@gmail.com';

  $logoCid = null;
  $logoPath = null;
  if(!empty($company['logo'])){
    $logoPath = __DIR__ . "/../uploads/logo/" . basename((string)$company['logo']);
    if(file_exists($logoPath)){
      $logoCid = 'companylogo';
    }else{
      $logoPath = null;
    }
  }

  $safeFromName = htmlspecialchars($fromName, ENT_QUOTES);
  $brandImgHtml = $logoCid ? '<img src="cid:' . $logoCid . '" alt="' . $safeFromName . '" style="height:42px;display:block;">' : '';

  $subject = "Test Email - Invoice System";
  $mailHtml = '
  <div style="font-family:Segoe UI,Arial,sans-serif;background:#f6f8fb;padding:24px;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,0.08);overflow:hidden;">
      <div style="padding:18px 22px;border-bottom:1px solid #e5e7eb;display:flex;gap:14px;align-items:center;">
        <div style="flex:0 0 auto;">' . $brandImgHtml . '</div>
        <div style="flex:1 1 auto;">
          <div style="font-weight:800;color:#0f172a;font-size:16px;line-height:1.2;">' . $safeFromName . '</div>
          <div style="color:#64748b;font-size:12px;margin-top:2px;">This is a short demo email.</div>
        </div>
      </div>
      <div style="padding:22px;color:#334155;font-size:13px;line-height:1.7;">
        Email sending test successful if you can read this message.
        <div style="margin-top:12px;color:#64748b;font-size:12px;">
          Sent at: ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES) . '
        </div>
      </div>
      <div style="padding:14px 22px;background:#0f172a;color:#e2e8f0;font-size:11px;">
        This is a system generated email.
      </div>
    </div>
  </div>';

  try{

    require_once __DIR__ . "/../includes/smtp-config.php";

    $mail = getMailer(); // 👈 pehle ye
    
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to, $clientName);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $mailHtml;
    $mail->AltBody = $altBody;
    if($logoCid && $logoPath){
      $mail->addEmbeddedImage($logoPath, $logoCid);
    }

    $mail->send();
    return ['status' => 'sent'];
  }catch(\Throwable $e){
    $err = method_exists($e, 'getMessage') ? $e->getMessage() : 'unknown';
    return ['status' => 'failed', 'error' => $err];
  }
}

function sendInvoiceEmailByInvoiceId($conn, $base_url, $invoice_id){
  $autoload = __DIR__ . "/../vendor/autoload.php";
  if(!file_exists($autoload)){
    return ['status' => 'failed', 'error' => 'autoload_missing'];
  }
  require_once $autoload;

  $invoice_id_safe = mysqli_real_escape_string($conn, (string)$invoice_id);

  $invQ = mysqli_query($conn, "
    SELECT invoices.*, clients.*
    FROM invoices
    LEFT JOIN clients ON invoices.client_id = clients.id
    WHERE invoices.id='$invoice_id_safe'
    LIMIT 1
  ");
  $data = $invQ ? mysqli_fetch_assoc($invQ) : null;
  if(!$data){
    return ['status' => 'failed', 'error' => 'invoice_missing'];
  }

  $to = $data['email'] ?? '';
  if(!$to){
    return ['status' => 'missing'];
  }

  $companyQ = mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1");
  $company = $companyQ ? mysqli_fetch_assoc($companyQ) : null;
  if(!$company){
    $company = [];
  }

  $itemsQ = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id='$invoice_id_safe'");
  $items_arr = [];
  if($itemsQ){
    while($item = mysqli_fetch_assoc($itemsQ)){
      $items_arr[] = $item;
    }
  }

  $invoiceType = $data['invoice_type'] ?? 'gst';

  // Build invoice HTML using existing templates
  ob_start();
  if($invoiceType === 'gst'){
    include __DIR__ . "/../templates/invoice-gst.php";
  }else{
    include __DIR__ . "/../templates/invoice-export.php";
  }
  $invoiceBodyHtml = ob_get_clean();

  $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Invoice</title></head><body style="background:#fff;">'
    . $invoiceBodyHtml
    . '</body></html>';

  $rootDir = realpath(__DIR__ . '/..');
  $html = prepareInvoiceHtmlForPdf($html, $rootDir ?: __DIR__ . '/..');

  // Generate PDF
  $pdfBytes = null;
  try{
    $options = new \Dompdf\Options();
    $options->set('chroot', $rootDir ?: __DIR__ . '/..');
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper(getPdfPaperSize(), 'portrait');
    $dompdf->render();
    $pdfBytes = $dompdf->output();
  }catch(\Throwable $e){
    return ['status' => 'failed', 'error' => 'pdf_failed'];
  }

  // Prepare email (HTML template + embedded logo if exists)
  $fromName = $company['company_name'] ?? 'Invoice System';
  $fromEmail = $company['email'] ?? 'vermaabhishek79326@gmail.com';
  $clientName = $data['client_name'] ?? 'Customer';
  $invoiceNo = $data['invoice_number'] ?? '';
  $invoiceDate = $data['invoice_date'] ?? '';
  $grandTotal = $data['grand_total'] ?? '';
  $currency = ($invoiceType === 'gst') ? 'INR' : ($data['currency'] ?? 'USD');
  $formattedTotal = formatMoney($grandTotal, $currency);

  $logoCid = null;
  $logoPath = null;
  if(!empty($company['logo'])){
    $logoPath = __DIR__ . "/../uploads/logo/" . basename((string)$company['logo']);
    if(!file_exists($logoPath)){
      $logoPath = null;
    }else{
      $logoCid = 'companylogo';
    }
  }

  $subject = "Invoice " . $invoiceNo;

  $safeFromName = htmlspecialchars($fromName, ENT_QUOTES);
  $safeInvoiceNo = htmlspecialchars($invoiceNo, ENT_QUOTES);
  $safeClient = htmlspecialchars($clientName, ENT_QUOTES);
  $safeDate = htmlspecialchars((string)$invoiceDate, ENT_QUOTES);
  $safeTotal = htmlspecialchars($formattedTotal, ENT_QUOTES);
  $companyLine = trim(($company['company_name'] ?? '') . ' ' . ($company['website'] ?? ''));
  $safeCompanyLine = htmlspecialchars($companyLine, ENT_QUOTES);

  $brandImgHtml = $logoCid ? '<img src="cid:' . $logoCid . '" alt="' . $safeFromName . '" style="height:42px;display:block;">' : '';

  $mailHtml = '
  <div style="font-family:Segoe UI,Arial,sans-serif;background:#f6f8fb;padding:24px;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,0.08);overflow:hidden;">
      <div style="padding:18px 22px;border-bottom:1px solid #e5e7eb;display:flex;gap:14px;align-items:center;">
        <div style="flex:0 0 auto;">' . $brandImgHtml . '</div>
        <div style="flex:1 1 auto;">
          <div style="font-weight:800;color:#0f172a;font-size:16px;line-height:1.2;">' . $safeFromName . '</div>
          <div style="color:#64748b;font-size:12px;margin-top:2px;">' . $safeCompanyLine . '</div>
        </div>
      </div>

      <div style="padding:22px;">
        <div style="font-size:14px;color:#0f172a;">Hello <b>' . $safeClient . '</b>,</div>
        <div style="margin-top:10px;color:#334155;font-size:13px;line-height:1.6;">
          Please find your invoice attached as PDF.
        </div>

        <div style="margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 14px;">
          <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <div style="min-width:220px;">
              <div style="color:#64748b;font-size:12px;">Invoice No</div>
              <div style="font-weight:800;color:#0f172a;font-size:14px;">' . $safeInvoiceNo . '</div>
            </div>
            <div style="min-width:160px;">
              <div style="color:#64748b;font-size:12px;">Invoice Date</div>
              <div style="font-weight:700;color:#0f172a;font-size:14px;">' . $safeDate . '</div>
            </div>
            <div style="min-width:160px;text-align:right;">
              <div style="color:#64748b;font-size:12px;">Total</div>
              <div style="font-weight:900;color:#0f172a;font-size:14px;">' . $safeTotal . '</div>
            </div>
          </div>
        </div>

        <div style="margin-top:16px;color:#475569;font-size:12px;line-height:1.6;">
          If you have any questions, just reply to this email.
        </div>

        <div style="margin-top:18px;color:#0f172a;font-size:13px;">
          Thank you,<br>
          <b>' . $safeFromName . '</b>
        </div>
      </div>

      <div style="padding:14px 22px;background:#0f172a;color:#e2e8f0;font-size:11px;">
        This is a system generated email.
      </div>
    </div>
  </div>';

  $altBody =
    "Hello " . $clientName . ",\n\n" .
    "Please find your invoice attached as PDF.\n" .
    "Invoice No: " . $invoiceNo . "\n" .
    "Invoice Date: " . $invoiceDate . "\n" .
    "Total: " . $formattedTotal . "\n\n" .
    "Thank you,\n" .
    $fromName;

  try{
    require_once __DIR__ . "/../includes/smtp-config.php";

    $mail = getMailer(); // 👈 direct SMTP
    
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);

    $companyEmail = trim((string)($company['email'] ?? ''));
    if($companyEmail !== '' && strcasecmp($companyEmail, $to) !== 0){
      $mail->addCC($companyEmail, $fromName);
    }

    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $mailHtml;
    $mail->AltBody = $altBody;

    if($logoCid && $logoPath){
      $mail->addEmbeddedImage($logoPath, $logoCid);
    }

    $fileName = 'Invoice-' . ($invoiceNo ?: $invoice_id) . '.pdf';
    $mail->addStringAttachment($pdfBytes, $fileName, 'base64', 'application/pdf');

    $mail->send();
    return ['status' => 'sent'];
  }catch(\Throwable $e){
    return ['status' => 'failed', 'error' => 'mail_failed'];
  }
}
?>

