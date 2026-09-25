<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";

$type = $_GET['type'] ?? 'gst';
if($type !== 'gst' && $type !== 'export'){
    $type = 'gst';
}

$invoice_number = generateInvoiceNumber($type,$conn);

$clients = mysqli_query($conn,"SELECT * FROM clients");

$financial_year = getFinancialYear();

$default_currency = ($type === 'export') ? 'USD' : 'INR';
$default_symbol = getCurrencySymbol($default_currency);

$companyGst = mysqli_fetch_assoc(mysqli_query($conn,"SELECT gstin, state FROM company_settings LIMIT 1")) ?: ['gstin'=>'','state'=>''];

?>

<style>
.hide_hsn{
display:none;
}
</style>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-file-invoice"></i> Create Invoice</h4>
</div>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header text-white" style="background:#234999">
<i class="fa fa-file-invoice"></i> Invoice Details
</div>

<div class="card-body">

<form method="POST" action="save-invoice.php">

<!-- Invoice Info -->

<div class="row">

<div class="col-md-3 mb-3">
<label>Invoice Type</label>

<select name="invoice_type" id="invoice_type" class="form-control">
<option value="gst" <?php echo ($type === 'gst') ? 'selected' : ''; ?>>GST Invoice</option>
<option value="export" <?php echo ($type === 'export') ? 'selected' : ''; ?>>Export Invoice</option>
</select>

</div>

<div class="col-md-3 mb-3">
<label>Invoice Number</label>

<input type="text" readonly name="invoice_number"
value="<?php echo $invoice_number; ?>"
class="form-control">

</div>

<div class="col-md-3 mb-3">
<label>Invoice Date</label>

<input type="date" name="invoice_date"
value="<?php echo date('Y-m-d'); ?>"
class="form-control" required>

</div>

<div class="col-md-3 mb-3">
<label>Financial Year</label>

<input type="text"
name="financial_year"
value="<?php echo $financial_year; ?>"
class="form-control">

</div>

</div>

<!-- GST Fields -->

<div class="row gst_fields">

<div class="col-md-4 mb-3">
<label>Place of Supply</label>
<input type="text" name="place_of_supply" class="form-control">
</div>

<div class="col-md-4 mb-3">
<label>Payment Terms</label>
<input type="text" name="payment_terms" class="form-control">
</div>

<div class="col-md-4 mb-3">
<label>Due Date</label>
<input type="date" name="due_date" class="form-control">
</div>

</div>

<!-- Export Fields -->

<div class="row export_fields" style="display:none">

<div class="col-md-6 mb-3">
<label>Currency</label>

<select name="currency" id="currency" class="form-control">
<option value="USD">USD</option>
<option value="EUR">EUR</option>
<option value="GBP">GBP</option>
</select>

</div>
</div>
<div class="col-md-12 mb-12">

<label>Select Client</label>

<div class="input-group">
  <select name="client_id" id="client_id" class="form-control">

  <?php while($c=mysqli_fetch_assoc($clients)){ ?>

  <option value="<?php echo $c['id']; ?>"
    data-state="<?php echo htmlspecialchars($c['state'] ?? '', ENT_QUOTES); ?>"
    data-gstin="<?php echo htmlspecialchars($c['gstin'] ?? '', ENT_QUOTES); ?>">
  <?php echo htmlspecialchars($c['client_name']); ?>
  </option>

  <?php } ?>

  </select>

  <a href="../clients/add-client.php" class="btn btn-success btn-sm" title="Add Client">
    <i class="fa fa-plus"></i> Add Client
  </a>
</div>

</div>



<hr>

<h5><i class="fa fa-list"></i> Invoice Items</h5>

<div class="table-responsive">

<table class="table table-bordered" id="invoiceTable">

<thead class="table-dark">

<tr>

<th>Description</th>
<th width="150" class="hsn_col">HSN/SAC</th>
<th width="100">Qty</th>
<th width="150">Price </th>
<th width="150">Amount </th>
<th width="80">Action</th>

</tr>

</thead>

<tbody>

<tr>

<td>
<input type="text" name="description[]" class="form-control">
</td>

<td class="hsn_col">
<input type="text" name="hsn[]" class="form-control">
</td>

<td>
<input type="number" name="qty[]" class="form-control qty">
</td>

<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="number" name="price[]" class="form-control price">
</div>
</td>

<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text" name="amount[]" class="form-control amount" readonly>
</div>
</td>

<td>
<button type="button" class="btn btn-danger remove">
<i class="fa fa-trash"></i>
</button>
</td>

</tr>

</tbody>

</table>

</div>

<button type="button"
class="btn text-white"
style="background:#234999"
id="addRow">

<i class="fa fa-plus"></i> Add Item

</button>

<br><br>

<!-- Totals -->

<div class="row">

<div class="col-md-6"></div>

<div class="col-md-6">

<table class="table table-bordered">

<tr>
<th>Subtotal (<span class="cur_sym"><?php echo $default_symbol; ?></span>)</th>
<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text" name="subtotal"
id="subtotal"
class="form-control"
readonly>
</div>
</td>
</tr>

<tr class="gst_row">
<th>CGST (9%) (<span class="cur_sym"><?php echo $default_symbol; ?></span>)</th>
<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text"
name="cgst"
id="cgst"
class="form-control"
readonly>
</div>
</td>
</tr>

<tr class="gst_row">
<th>SGST (9%) (<span class="cur_sym"><?php echo $default_symbol; ?></span>)</th>
<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text"
name="sgst"
id="sgst"
class="form-control"
readonly>
</div>
</td>
</tr>

<tr class="gst_row">
<th>IGST (18%) (<span class="cur_sym"><?php echo $default_symbol; ?></span>)</th>
<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text"
name="igst"
id="igst"
class="form-control"
readonly>
</div>
<small class="text-muted d-block mt-1" id="gst_tax_hint"></small>
</td>
</tr>

<tr>
<th>Grand Total (<span class="cur_sym"><?php echo $default_symbol; ?></span>)</th>
<td>
<div class="input-group">
<span class="input-group-text cur_prefix"><?php echo $default_symbol; ?></span>
<input type="text"
name="grand_total"
id="grand_total"
class="form-control fw-bold"
readonly>
</div>
</td>
</tr>

</table>

</div>

</div>

<div class="export_note alert alert-info" style="display:none">

Supply meant for export under LUT without payment of IGST

</div>

<br>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <div class="form-check me-2">
    <input class="form-check-input" type="checkbox" value="1" id="send_email" name="send_email">
    <label class="form-check-label" for="send_email">
      Send email to client
    </label>
  </div>

  <button type="button" class="btn btn-primary" id="previewBtn">
    <i class="fa fa-print"></i> Print Preview
  </button>

  <button type="submit" class="btn btn-success">
    <i class="fa fa-save"></i> Save Invoice
  </button>
</div>

</form>

</div>

</div>

</div>

</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

var companyGstin = <?php echo json_encode($companyGst['gstin'] ?? ''); ?>;
var companyState = <?php echo json_encode($companyGst['state'] ?? ''); ?>;

var currencySymbols = {
  'INR': '₹',
  'USD': '$',
  'EUR': '€',
  'GBP': '£'
};

function getActiveCurrency(){
  var type = $("#invoice_type").val();
  if(type === "gst") return "INR";
  return $("#currency").val() || "USD";
}

function getCurrencySymbol(){
  var cur = getActiveCurrency();
  return currencySymbols[cur] || cur;
}

function updateCurrencySymbols(){
  var sym = getCurrencySymbol();
  $(".cur_sym").text(sym);
  $(".cur_prefix").text(sym);
}

function toggleInvoiceTypeUI(type){
  if(type == "export"){
    $(".gst_fields").hide();
    $(".gst_row").hide();

    $(".export_fields").show();
    $(".export_note").show();

    $(".hsn_col").addClass("hide_hsn");
  }else{
    $(".gst_fields").show();
    $(".gst_row").show();

    $(".export_fields").hide();
    $(".export_note").hide();

    $(".hsn_col").removeClass("hide_hsn");
  }

  updateCurrencySymbols();
  calculateTotal();
}

$("#addRow").click(function(){

var type = $("#invoice_type").val();
var sym = getCurrencySymbol();

var hsnClass = (type == "export") ? "style='display:none'" : "";

var row = `<tr>

<td><input type="text" name="description[]" class="form-control"></td>

<td class="hsn_col" ${hsnClass}>
<input type="text" name="hsn[]" class="form-control">
</td>

<td><input type="number" name="qty[]" class="form-control qty"></td>

<td><div class="input-group"><span class="input-group-text cur_prefix">${sym}</span><input type="number" name="price[]" class="form-control price"></div></td>

<td><div class="input-group"><span class="input-group-text cur_prefix">${sym}</span><input type="text" name="amount[]" class="form-control amount" readonly></div></td>

<td><button type="button" class="btn btn-danger remove"><i class="fa fa-trash"></i></button></td>

</tr>`;

$("#invoiceTable tbody").append(row);

});

$(document).on("click",".remove",function(){

$(this).closest("tr").remove();

calculateTotal();

});

$(document).on("keyup change",".qty,.price",function(){

var row = $(this).closest("tr");

var qty = row.find(".qty").val();
var price = row.find(".price").val();

var amount = qty * price;

row.find(".amount").val(amount);

calculateTotal();

});

$("#invoice_type").change(function(){

var type = $(this).val();

toggleInvoiceTypeUI(type);

// Reload page ONLY if URL type differs (so invoice number regenerates)
var params = new URLSearchParams(window.location.search);
var currentType = params.get('type') || 'gst';
if(currentType !== type){
  var baseUrl = window.location.href.split('?')[0];
  window.location.href = baseUrl + "?type=" + encodeURIComponent(type);
}

});

$("#currency").change(function(){
  updateCurrencySymbols();
});

function gstinStateCode(gstin){
  var g = String(gstin || '').replace(/\s+/g, '').toUpperCase();
  if(g.length >= 2 && /^\d{2}/.test(g)){
    return g.substring(0, 2);
  }
  return '';
}

function normalizeGstStateName(name){
  var s = String(name || '').toLowerCase().replace(/[.,]/g, '').replace(/\s+/g, ' ').trim();
  var aliases = {
    'up': 'uttar pradesh',
    'u p': 'uttar pradesh',
    'uk': 'uttarakhand',
    'tn': 'tamil nadu',
    'ap': 'andhra pradesh',
    'hp': 'himachal pradesh',
    'mp': 'madhya pradesh',
    'wb': 'west bengal',
    'nct of delhi': 'delhi',
    'new delhi': 'delhi',
    'orissa': 'odisha',
    'pondicherry': 'puducherry'
  };
  return aliases[s] || s;
}

function isInterStateClient(){
  var opt = $("#client_id option:selected");
  var clientGstin = opt.attr('data-gstin') || '';
  var clientState = opt.attr('data-state') || '';
  var companyCode = gstinStateCode(companyGstin);
  var clientCode = gstinStateCode(clientGstin);
  if(companyCode && clientCode){
    return companyCode !== clientCode;
  }
  var cState = String(companyState || '').trim();
  var clState = String(clientState || '').trim();
  if(!cState || !clState){
    return false;
  }
  return normalizeGstStateName(cState) !== normalizeGstStateName(clState);
}

function calculateTotal(){

var subtotal = 0;

$(".amount").each(function(){

subtotal += parseFloat($(this).val()) || 0;

});

$("#subtotal").val(subtotal.toFixed(2));

var type = $("#invoice_type").val();

if(type == "gst"){

var interState = isInterStateClient();
var cgst = 0;
var sgst = 0;
var igst = 0;

if(interState){
  igst = subtotal * 0.18;
  $("#gst_tax_hint").text('Different state client: IGST 18% applied. CGST and SGST are 0.');
}else{
  cgst = subtotal * 0.09;
  sgst = subtotal * 0.09;
  $("#gst_tax_hint").text('Same state client: CGST 9% + SGST 9%. IGST is 0.');
}

$("#cgst").val(cgst.toFixed(2));
$("#sgst").val(sgst.toFixed(2));
$("#igst").val(igst.toFixed(2));

var total = subtotal + cgst + sgst + igst;

$("#grand_total").val(total.toFixed(2));

}

else{

$("#cgst").val(0);
$("#sgst").val(0);
$("#igst").val(0);
$("#gst_tax_hint").text('');

$("#grand_total").val(subtotal.toFixed(2));

}

}

$(document).on("change", "#client_id", function(){
  calculateTotal();
});

$(document).ready(function(){
toggleInvoiceTypeUI($("#invoice_type").val());
});

</script>

<script>
  (function(){
    const btn = document.getElementById('previewBtn');
    if(!btn) return;

    btn.addEventListener('click', function(){
      const form = btn.closest('form');
      if(!form) return;

      const modalEl = document.getElementById('invoicePreviewModal');
      const iframe = document.getElementById('invoicePreviewFrame');
      const loading = document.getElementById('invoicePreviewLoading');
      if(!modalEl || !iframe) return;

      if(loading) loading.style.display = 'block';
      iframe.removeAttribute('src');
      iframe.removeAttribute('srcdoc');

      const fd = new FormData(form);

      fetch('preview-invoice.php', {
        method: 'POST',
        body: fd
      })
      .then((r) => r.text())
      .then((html) => {
        iframe.srcdoc = html;
        if(loading) loading.style.display = 'none';
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      })
      .catch(() => {
        if(loading) loading.style.display = 'none';
        Swal.fire({
          icon: 'error',
          title: 'Preview failed',
          text: 'Preview load nahi ho paya. Please try again.'
        });
      });
    });
  })();
</script>

<!-- Invoice Preview Modal -->
<div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-print"></i> Print Preview (Draft)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="invoicePreviewLoading" class="p-3">
          Loading preview...
        </div>
        <iframe
          id="invoicePreviewFrame"
          title="Invoice Preview"
          style="width:100%; height:80vh; border:0; display:block;"
        ></iframe>
      </div>
    </div>
  </div>
</div>

<?php include "../../includes/footer.php"; ?>