<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";
include "../../includes/header.php";
include "../../includes/sidebar.php";

$id = intval($_GET['id'] ?? 0);
if(!$id){
  echo "<div class='content'><div class='alert alert-danger'>Invalid invoice.</div></div>";
  include "../../includes/footer.php";
  exit;
}

$invQ = mysqli_query($conn,"
  SELECT invoices.*, clients.client_name
  FROM invoices
  LEFT JOIN clients ON invoices.client_id = clients.id
  WHERE invoices.id='$id'
  LIMIT 1
");
$data = mysqli_fetch_assoc($invQ);
if(!$data){
  echo "<div class='content'><div class='alert alert-danger'>Invoice not found.</div></div>";
  include "../../includes/footer.php";
  exit;
}

if(!empty($data['deleted_at'])){
  echo "<div class='content'><div class='alert alert-warning'>This invoice is in Recycle Bin. Restore it before editing. <a href='recycle-bin.php'>Go to Recycle Bin</a></div></div>";
  include "../../includes/footer.php";
  exit;
}

$itemsQ = mysqli_query($conn,"SELECT * FROM invoice_items WHERE invoice_id='$id'");
$items = [];
while($r = mysqli_fetch_assoc($itemsQ)){ $items[] = $r; }
if(empty($items)){
  $items = [[ 'description'=>'', 'hsn_code'=>'', 'qty'=>'', 'unit_price'=>'', 'amount'=>'' ]];
}

$clients = mysqli_query($conn,"SELECT * FROM clients");
$type = $data['invoice_type'];
$active_currency = ($type === 'gst') ? 'INR' : ($data['currency'] ?: 'USD');
$cur_symbol = getCurrencySymbol($active_currency);
$companyGst = mysqli_fetch_assoc(mysqli_query($conn,"SELECT gstin, state FROM company_settings LIMIT 1")) ?: ['gstin'=>'','state'=>''];
?>

<style>
  .hide_hsn{ display:none; }
</style>

<div class="content">
  <div class="topbar">
    <h4><i class="fa fa-file-invoice"></i> Edit Invoice</h4>
  </div>

  <div class="container-fluid">
    <div class="card shadow">
      <div class="card-header text-white" style="background:#234999">
        <i class="fa fa-file-invoice"></i> Invoice Details
      </div>

      <div class="card-body">
        <form method="POST" action="update-invoice.php">
          <input type="hidden" name="id" value="<?php echo $id; ?>">
          <input type="hidden" name="invoice_type" value="<?php echo htmlspecialchars($type); ?>">

          <div class="row">
            <div class="col-md-3 mb-3">
              <label>Invoice Type</label>
              <select class="form-control" disabled>
                <option value="gst" <?php echo ($type==='gst')?'selected':''; ?>>GST Invoice</option>
                <option value="export" <?php echo ($type==='export')?'selected':''; ?>>Export Invoice</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label>Invoice Number</label>
              <input type="text" name="invoice_number"
                value="<?php echo htmlspecialchars($data['invoice_number']); ?>"
                class="form-control" readonly>
            </div>

            <div class="col-md-3 mb-3">
              <label>Invoice Date</label>
              <input type="date" name="invoice_date"
                value="<?php echo htmlspecialchars($data['invoice_date']); ?>"
                class="form-control" required>
            </div>

            <div class="col-md-3 mb-3">
              <label>Financial Year</label>
              <input type="text" name="financial_year"
                value="<?php echo htmlspecialchars($data['financial_year']); ?>"
                class="form-control" readonly>
            </div>
          </div>

          <div class="row gst_fields" <?php echo ($type==='export')?'style="display:none"':''; ?>>
            <div class="col-md-4 mb-3">
              <label>Place of Supply</label>
              <input type="text" name="place_of_supply" class="form-control"
                value="<?php echo htmlspecialchars($data['place_of_supply']); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label>Payment Terms</label>
              <input type="text" name="payment_terms" class="form-control"
                value="<?php echo htmlspecialchars($data['payment_terms']); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label>Due Date</label>
              <input type="date" name="due_date" class="form-control"
                value="<?php echo htmlspecialchars($data['due_date']); ?>">
            </div>
          </div>

          <div class="row export_fields" <?php echo ($type==='gst')?'style="display:none"':''; ?>>
            <div class="col-md-6 mb-3">
              <label>Currency</label>
              <select name="currency" id="currency" class="form-control">
                <?php
                  $cur = $data['currency'] ?: 'USD';
                  $opts = ['USD','EUR','GBP'];
                  foreach($opts as $o){
                    $sel = ($cur===$o) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($o)."' $sel>".htmlspecialchars($o)."</option>";
                  }
                ?>
              </select>
            </div>
          </div>

          <div class="col-md-12 mb-12">
            <label>Select Client</label>
            <select name="client_id" id="client_id" class="form-control">
              <?php while($c=mysqli_fetch_assoc($clients)){ ?>
                <option value="<?php echo $c['id']; ?>"
                  data-state="<?php echo htmlspecialchars($c['state'] ?? '', ENT_QUOTES); ?>"
                  data-gstin="<?php echo htmlspecialchars($c['gstin'] ?? '', ENT_QUOTES); ?>"
                  <?php echo ($c['id']==$data['client_id'])?'selected':''; ?>>
                  <?php echo htmlspecialchars($c['client_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <hr>
          <h5><i class="fa fa-list"></i> Invoice Items</h5>

          <div class="table-responsive">
            <table class="table table-bordered" id="invoiceTable">
              <thead class="table-dark">
                <tr>
                  <th>Description</th>
                  <?php if($type === 'gst'){ ?>
                    <th width="150" class="hsn_col">HSN/SAC</th>
                  <?php } ?>
                  <th width="100">Qty</th>
                  <th width="150">Price (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <th width="150">Amount (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <th width="80">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($items as $it){ ?>
                  <tr>
                    <td><input type="text" name="description[]" class="form-control" value="<?php echo htmlspecialchars($it['description']); ?>"></td>
                    <?php if($type === 'gst'){ ?>
                      <td class="hsn_col">
                        <input type="text" name="hsn[]" class="form-control" value="<?php echo htmlspecialchars($it['hsn_code']); ?>">
                      </td>
                    <?php } ?>
                    <td><input type="number" name="qty[]" class="form-control qty" value="<?php echo htmlspecialchars($it['qty']); ?>"></td>
                    <td>
                      <div class="input-group">
                        <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                        <input type="number" name="price[]" class="form-control price" value="<?php echo htmlspecialchars($it['unit_price']); ?>">
                      </div>
                    </td>
                    <td>
                      <div class="input-group">
                        <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                        <input type="text" name="amount[]" class="form-control amount" readonly value="<?php echo htmlspecialchars($it['amount']); ?>">
                      </div>
                    </td>
                    <td>
                      <button type="button" class="btn btn-danger remove">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <button type="button" class="btn text-white" style="background:#234999" id="addRow">
            <i class="fa fa-plus"></i> Add Item
          </button>

          <br><br>

          <div class="row">
            <div class="col-md-6"></div>
            <div class="col-md-6">
              <table class="table table-bordered">
                <tr>
                  <th>Subtotal (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <td>
                    <div class="input-group">
                      <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                      <input type="text" name="subtotal" id="subtotal" class="form-control" readonly>
                    </div>
                  </td>
                </tr>
                <tr class="gst_row" <?php echo ($type==='export')?'style="display:none"':''; ?>>
                  <th>CGST (9%) (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <td>
                    <div class="input-group">
                      <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                      <input type="text" name="cgst" id="cgst" class="form-control" readonly>
                    </div>
                  </td>
                </tr>
                <tr class="gst_row" <?php echo ($type==='export')?'style="display:none"':''; ?>>
                  <th>SGST (9%) (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <td>
                    <div class="input-group">
                      <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                      <input type="text" name="sgst" id="sgst" class="form-control" readonly>
                    </div>
                  </td>
                </tr>
                <tr class="gst_row" <?php echo ($type==='export')?'style="display:none"':''; ?>>
                  <th>IGST (18%) (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <td>
                    <div class="input-group">
                      <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                      <input type="text" name="igst" id="igst" class="form-control" readonly>
                    </div>
                    <small class="text-muted d-block mt-1" id="gst_tax_hint"></small>
                  </td>
                </tr>
                <tr>
                  <th>Grand Total (<span class="cur_sym"><?php echo $cur_symbol; ?></span>)</th>
                  <td>
                    <div class="input-group">
                      <span class="input-group-text cur_prefix"><?php echo $cur_symbol; ?></span>
                      <input type="text" name="grand_total" id="grand_total" class="form-control fw-bold" readonly>
                    </div>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> Update Invoice
          </button>
          <a href="invoice-list.php" class="btn btn-secondary">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
var invoiceType = <?php echo json_encode($type); ?>;
var companyGstin = <?php echo json_encode($companyGst['gstin'] ?? ''); ?>;
var companyState = <?php echo json_encode($companyGst['state'] ?? ''); ?>;
var currencySymbols = {
  'INR': '₹',
  'USD': '$',
  'EUR': '€',
  'GBP': '£'
};

function getActiveCurrency(){
  if(invoiceType === "gst") return "INR";
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

  if(invoiceType === "gst"){
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
    $("#grand_total").val((subtotal + cgst + sgst + igst).toFixed(2));
  }else{
    $("#cgst").val(0);
    $("#sgst").val(0);
    $("#igst").val(0);
    $("#gst_tax_hint").text('');
    $("#grand_total").val(subtotal.toFixed(2));
  }
}

$("#addRow").click(function(){
  var type = invoiceType;
  var sym = getCurrencySymbol();
  var row = `<tr>
    <td><input type="text" name="description[]" class="form-control"></td>
    ${type === "gst" ? `<td class="hsn_col"><input type="text" name="hsn[]" class="form-control"></td>` : ``}
    <td><input type="number" name="qty[]" class="form-control qty"></td>
    <td><div class="input-group"><span class="input-group-text cur_prefix">${sym}</span><input type="number" name="price[]" class="form-control price"></div></td>
    <td><div class="input-group"><span class="input-group-text cur_prefix">${sym}</span><input type="text" name="amount[]" class="form-control amount" readonly></div></td>
    <td><button type="button" class="btn btn-danger remove"><i class="fa fa-trash"></i></button></td>
  </tr>`;
  $("#invoiceTable tbody").append(row);
});

$("#currency").change(function(){
  updateCurrencySymbols();
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

$(document).on("change", "#client_id", function(){
  calculateTotal();
});

$(document).ready(function(){
  updateCurrencySymbols();
  // Fill initial amounts if missing
  $("#invoiceTable tbody tr").each(function(){
    var row = $(this);
    var qty = parseFloat(row.find(".qty").val()) || 0;
    var price = parseFloat(row.find(".price").val()) || 0;
    row.find(".amount").val((qty * price) ? (qty*price) : (parseFloat(row.find(".amount").val())||0));
  });
  calculateTotal();
});
</script>

<?php include "../../includes/footer.php"; ?>

