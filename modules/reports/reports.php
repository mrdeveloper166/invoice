
<?php
require_once __DIR__ . "/../../config/database.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";

/* FILTER */

$where = "WHERE deleted_at IS NULL";

$selectedInvoiceType = $_GET['invoice_type'] ?? '';
$selectedCurrency = $_GET['currency'] ?? '';

if(!empty($_GET['fy'])){
$fy = mysqli_real_escape_string($conn, $_GET['fy']);
$where .= " AND financial_year='$fy'";
}

if(!empty($_GET['month'])){
$month = intval($_GET['month']);
$where .= " AND MONTH(invoice_date)='$month'";
}

if(!empty($_GET['from'])){
$from = mysqli_real_escape_string($conn, $_GET['from']);
$where .= " AND invoice_date >= '$from'";
}

if(!empty($_GET['to'])){
$to = mysqli_real_escape_string($conn, $_GET['to']);
$where .= " AND invoice_date <= '$to'";
}

if(!empty($_GET['status'])){
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND status='$status'";
}

if(!empty($_GET['invoice_type'])){
    $invoice_type = mysqli_real_escape_string($conn, $_GET['invoice_type']);
    $where .= " AND invoice_type='$invoice_type'";
}

// For type totals card (GST vs Export) we need a version of filters WITHOUT invoice_type restriction
$whereNoType = $where;
if(!empty($_GET['invoice_type'])){
    $invoice_type = mysqli_real_escape_string($conn, $_GET['invoice_type']);
    $whereNoType = str_replace(" AND invoice_type='$invoice_type'","",$whereNoType);
}

// Currency filter (IMPORTANT: prevents mixing INR + USD/EUR/GBP totals)
if(!empty($_GET['currency'])){
    $currency = mysqli_real_escape_string($conn, $_GET['currency']);
    $where .= " AND currency='$currency'";
}

/* QUERY */

$query = mysqli_query($conn,"
SELECT invoices.*, clients.client_name
FROM invoices
LEFT JOIN clients ON invoices.client_id = clients.id
$where
ORDER BY invoice_date DESC
");

// Summary stats for current filter (single currency if currency filter is used)
$sumRow = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT
  COUNT(*) AS total_invoices,
  SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
  SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
  SUM(grand_total) AS total_amount,
  SUM(CASE WHEN status='paid' THEN grand_total ELSE 0 END) AS paid_amount,
  SUM(CASE WHEN status='unpaid' THEN grand_total ELSE 0 END) AS unpaid_amount
FROM invoices
$where
"));

// Currency totals breakdown (for "All currencies" view)
$currencyTotals = [];
$currencyTotalsQ = mysqli_query($conn,"
  SELECT
    COALESCE(NULLIF(currency,''),'INR') AS currency,
    SUM(grand_total) AS total_amount,
    SUM(CASE WHEN status='paid' THEN grand_total ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status='unpaid' THEN grand_total ELSE 0 END) AS unpaid_amount
  FROM invoices
  $where
  GROUP BY COALESCE(NULLIF(currency,''),'INR')
  ORDER BY currency
");
while($r = mysqli_fetch_assoc($currencyTotalsQ)){
    $currencyTotals[] = $r;
}

// Invoice type counts (GST vs Export) for current filters (ignoring invoice_type filter)
$typeCountRow = mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT
    SUM(CASE WHEN invoice_type='gst' THEN 1 ELSE 0 END) AS gst_count,
    SUM(CASE WHEN invoice_type='export' THEN 1 ELSE 0 END) AS export_count
  FROM invoices
  $whereNoType
"));

// For currency dropdown options (respect invoice_type filter if chosen)
$currencyWhere = "WHERE deleted_at IS NULL";
if(!empty($_GET['invoice_type'])){
    $invT = mysqli_real_escape_string($conn, $_GET['invoice_type']);
    $currencyWhere .= " AND invoice_type='$invT'";
}
$currencyOptions = [];
$cQ = mysqli_query($conn,"
  SELECT DISTINCT COALESCE(NULLIF(currency,''),'INR') AS currency
  FROM invoices
  $currencyWhere
  ORDER BY currency
");
while($r = mysqli_fetch_assoc($cQ)){
    $currencyOptions[] = $r['currency'];
}

// Monthly totals chart (within selected FY if any, else current FY)
$chartFy = !empty($_GET['fy']) ? mysqli_real_escape_string($conn, $_GET['fy']) : null;
if(!$chartFy){
    // fallback to current FY format YY-YY
    $y = date('Y');
    $m = intval(date('m'));
    $start = ($m >= 4) ? $y : ($y - 1);
    $end = $start + 1;
    $chartFy = substr($start,2)."-".substr($end,2);
}

// Monthly chart must be single currency (otherwise chart is meaningless)
$chartCurrency = !empty($_GET['currency']) ? mysqli_real_escape_string($conn, $_GET['currency']) : null;
$monthlyWhere = "WHERE financial_year='$chartFy' AND deleted_at IS NULL";
if(!empty($_GET['invoice_type'])){
    $invT = mysqli_real_escape_string($conn, $_GET['invoice_type']);
    $monthlyWhere .= " AND invoice_type='$invT'";
}
if(!empty($_GET['status'])){
    $st = mysqli_real_escape_string($conn, $_GET['status']);
    $monthlyWhere .= " AND status='$st'";
}
if(!empty($_GET['from'])){
    $from2 = mysqli_real_escape_string($conn, $_GET['from']);
    $monthlyWhere .= " AND invoice_date >= '$from2'";
}
if(!empty($_GET['to'])){
    $to2 = mysqli_real_escape_string($conn, $_GET['to']);
    $monthlyWhere .= " AND invoice_date <= '$to2'";
}
if($chartCurrency){
    $monthlyWhere .= " AND currency='$chartCurrency'";
}

$monthlyRows = mysqli_query($conn,"
SELECT MONTH(invoice_date) AS m, SUM(grand_total) AS total
FROM invoices
$monthlyWhere
GROUP BY MONTH(invoice_date)
ORDER BY MONTH(invoice_date)
");
$monthlyTotals = array_fill(1, 12, 0.0);
while($r = mysqli_fetch_assoc($monthlyRows)){
    $mm = intval($r['m']);
    $monthlyTotals[$mm] = floatval($r['total']);
}

// Financial year totals chart (overall)
$fyChartWhere = "WHERE deleted_at IS NULL";
if(!empty($_GET['invoice_type'])){
    $invT = mysqli_real_escape_string($conn, $_GET['invoice_type']);
    $fyChartWhere .= " AND invoice_type='$invT'";
}
if(!empty($_GET['status'])){
    $st = mysqli_real_escape_string($conn, $_GET['status']);
    $fyChartWhere .= " AND status='$st'";
}
if(!empty($_GET['currency'])){
    $cur = mysqli_real_escape_string($conn, $_GET['currency']);
    $fyChartWhere .= " AND currency='$cur'";
}

$fyRows = mysqli_query($conn,"
SELECT financial_year AS fy, SUM(grand_total) AS total
FROM invoices
$fyChartWhere
GROUP BY financial_year
ORDER BY financial_year
");
$fyLabels = [];
$fyTotals = [];
while($r = mysqli_fetch_assoc($fyRows)){
    $fyLabels[] = "FY ".$r['fy'];
    $fyTotals[] = floatval($r['total']);
}

?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-chart-bar"></i> Invoice Reports</h4>
</div>

<div class="container-fluid">

<style>
  .stat-card{
    border:0;
    border-radius:14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  }
  .stat-card .label{ font-size:12px; opacity:.85; }
  .stat-card .value{ font-size:22px; font-weight:800; }
  .chart-card{
    border:0;
    border-radius:14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  }
  .chart-title{
    font-weight:800;
    font-size:14px;
    margin:0;
  }
  .dt-buttons .btn{
    margin-right:8px;
  }
  .dataTables_filter input{
    border-radius:10px;
    padding:6px 10px;
    border:1px solid #dee2e6;
  }
</style>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="label">Invoices (filtered)</div>
        <div class="value"><?php echo intval($sumRow['total_invoices'] ?? 0); ?></div>
        <div style="font-size:12px;line-height:1.6;margin-top:6px;">
          <div><b>GST:</b> <?php echo intval($typeCountRow['gst_count'] ?? 0); ?></div>
          <div><b>Export:</b> <?php echo intval($typeCountRow['export_count'] ?? 0); ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="label">
          Total Amount (filtered)
          <?php if(!empty($_GET['currency'])){ ?>
            <span class="text-muted">(<?php echo htmlspecialchars($_GET['currency']); ?>)</span>
          <?php } else { ?>
            <span class="text-muted">(All currencies)</span>
          <?php } ?>
        </div>
        <?php if(!empty($_GET['currency'])){ ?>
          <div class="value">
            <?php echo htmlspecialchars($_GET['currency']); ?> <?php echo number_format((float)($sumRow['total_amount'] ?? 0),2); ?>
          </div>
        <?php } else { ?>
          <div style="font-size:12px;line-height:1.6;">
            <?php if(empty($currencyTotals)){ ?>
              <span class="text-muted">No data</span>
            <?php } else { ?>
              <?php foreach($currencyTotals as $ct){ ?>
                <div>
                  <b><?php echo htmlspecialchars($ct['currency']); ?>:</b>
                  <?php echo number_format((float)$ct['total_amount'],2); ?>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="label">Paid / Unpaid</div>
        <div class="value"><?php echo intval($sumRow['paid_count'] ?? 0); ?> / <?php echo intval($sumRow['unpaid_count'] ?? 0); ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="label">Paid Amount</div>
        <?php if(!empty($_GET['currency'])){ ?>
          <div class="value">
            <?php echo htmlspecialchars($_GET['currency']); ?> <?php echo number_format((float)($sumRow['paid_amount'] ?? 0),2); ?>
          </div>
        <?php } else { ?>
          <div style="font-size:12px;line-height:1.6;">
            <?php if(empty($currencyTotals)){ ?>
              <span class="text-muted">No data</span>
            <?php } else { ?>
              <?php foreach($currencyTotals as $ct){ ?>
                <div>
                  <b><?php echo htmlspecialchars($ct['currency']); ?>:</b>
                  <?php echo number_format((float)$ct['paid_amount'],2); ?>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card chart-card">
      <div class="card-header bg-white" style="border:0;border-radius:14px 14px 0 0;">
        <p class="chart-title">Monthly Total (<?php echo "FY ".$chartFy; ?>)</p>
      </div>
      <div class="card-body">
        <canvas id="monthlyChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card chart-card">
      <div class="card-header bg-white" style="border:0;border-radius:14px 14px 0 0;">
        <p class="chart-title">Paid vs Unpaid (amount)</p>
      </div>
      <div class="card-body">
        <canvas id="statusChart" height="160"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card chart-card">
      <div class="card-header bg-white" style="border:0;border-radius:14px 14px 0 0;">
        <p class="chart-title">Financial Year Total</p>
      </div>
      <div class="card-body">
        <canvas id="fyChart" height="160"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="card shadow mb-4">

<div class="card-header text-white" style="background:#234999">
<i class="fa fa-filter"></i> Filters
</div>

<div class="card-body">

<form method="GET">

<div class="row">

<div class="col-md-3">

<label>Financial Year</label>

<select name="fy" class="form-control">

<option value="">All</option>

<?php
for($i=2022;$i<=2035;$i++){

$fy = substr($i,2)."-".substr($i+1,2);

$selected = (isset($_GET['fy']) && $_GET['fy']==$fy) ? "selected" : "";

echo "<option value='$fy' $selected>$i - ".($i+1)."</option>";

}
?>

</select>

</div>

<div class="col-md-2">
<label>Invoice Type</label>
<select name="invoice_type" class="form-control">
  <option value="">All</option>
  <?php
    $selType = $_GET['invoice_type'] ?? '';
  ?>
  <option value="gst" <?php echo ($selType==='gst') ? 'selected' : ''; ?>>GST</option>
  <option value="export" <?php echo ($selType==='export') ? 'selected' : ''; ?>>Export</option>
</select>
</div>

<div class="col-md-2">
<label>Currency</label>
<select name="currency" class="form-control">
  <option value="">All</option>
  <?php
    $selCur = $_GET['currency'] ?? '';
    foreach($currencyOptions as $curOpt){
      $selected = ($selCur === $curOpt) ? 'selected' : '';
      echo "<option value='".htmlspecialchars($curOpt)."' $selected>".htmlspecialchars($curOpt)."</option>";
    }
  ?>
</select>
</div>

<div class="col-md-2">
<label>Status</label>
<select name="status" class="form-control">
  <option value="">All</option>
  <?php
    $selStatus = $_GET['status'] ?? '';
  ?>
  <option value="paid" <?php echo ($selStatus==='paid') ? 'selected' : ''; ?>>Paid</option>
  <option value="unpaid" <?php echo ($selStatus==='unpaid') ? 'selected' : ''; ?>>Unpaid</option>
</select>
</div>

<div class="col-md-3">

<label>Month</label>

<select name="month" class="form-control">

<option value="">All</option>

<?php
for($m=1;$m<=12;$m++){

$monthName = date("F", mktime(0,0,0,$m,10));

$selected = (isset($_GET['month']) && $_GET['month']==$m) ? "selected" : "";

echo "<option value='$m' $selected>$monthName</option>";

}
?>

</select>

</div>


<div class="col-md-2">

<label>From Date</label>

<input type="date" name="from"
value="<?php echo $_GET['from'] ?? ''; ?>"
class="form-control">

</div>


<div class="col-md-2">

<label>To Date</label>

<input type="date" name="to"
value="<?php echo $_GET['to'] ?? ''; ?>"
class="form-control">

</div>


<div class="col-md-2 d-flex align-items-end">

<button class="btn btn-primary w-100">
<i class="fa fa-search"></i> Filter
</button>

</div>

</div>

</form>

</div>

</div>


<div class="card shadow">

<div class="card-header text-white" style="background:#234999">
<i class="fa fa-file-invoice"></i> Report Data
</div>

<div class="card-body">

<div class="table-responsive">

<table id="reportTable" class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Invoice No</th>
<th>Client</th>
<th>Date</th>
<th>Financial Year</th>
<th>Total</th>
<th>Status</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['invoice_number']; ?></td>

<td><?php echo $row['client_name']; ?></td>

<td><?php echo $row['invoice_date']; ?></td>

<td>FY <?php echo $row['financial_year']; ?></td>

<td>
<?php 
$currency = ($row['invoice_type']=='export') ? $row['currency'] : '₹'; 
echo $currency . " " . number_format($row['grand_total'],2); 
?>
</td>
<td>
<?php echo $row['status']; ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>


<link rel="stylesheet" href="https://cdn.datatables.net/2.3.7/css/dataTables.dataTables.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.6/css/buttons.dataTables.css">

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.3.7/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.dataTables.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.print.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
// Ensure libraries are available for Buttons exports
if(typeof window.JSZip === 'undefined' && typeof JSZip !== 'undefined'){ window.JSZip = JSZip; }
if(typeof window.pdfMake === 'undefined' && typeof pdfMake !== 'undefined'){ window.pdfMake = pdfMake; }

$(document).ready(function(){
  new DataTable('#reportTable', {
    pageLength: 10,
    layout: {
      topStart: {
        buttons: [
          { extend: 'copy', text: 'Copy' },
          { extend: 'csv', text: 'CSV' },
          { extend: 'excel', text: 'Excel' },
          { extend: 'pdf', text: 'PDF' },
          { extend: 'print', text: 'Print' }
        ]
      },
      topEnd: 'search',
      bottomStart: 'info',
      bottomEnd: 'paging'
    }
  });
});
</script>

<script>
  // Charts
  const monthLabels = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  const monthlyData = <?php echo json_encode(array_values($monthlyTotals)); ?>;
  const chartCurrency = <?php echo json_encode($chartCurrency ?: ''); ?>;

  new Chart(document.getElementById('monthlyChart'),{
    type:'line',
    data:{
      labels: monthLabels,
      datasets:[{
        label: chartCurrency ? ('Total ('+chartCurrency+')') : 'Total (select currency)',
        data: monthlyData,
        borderColor:'#234999',
        backgroundColor:'rgba(35,73,153,0.12)',
        fill:true,
        tension:0.35,
        pointRadius:3
      }]
    },
    options:{
      responsive:true,
      plugins:{ legend:{ display:false } },
      scales:{
        y:{ ticks:{ callback:(v)=> (chartCurrency ? (chartCurrency+' ') : '') + v } }
      }
    }
  });

  const paidAmount = <?php echo json_encode((float)($sumRow['paid_amount'] ?? 0)); ?>;
  const unpaidAmount = <?php echo json_encode((float)($sumRow['unpaid_amount'] ?? 0)); ?>;
  new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{
      labels:['Paid','Unpaid'],
      datasets:[{
        data:[paidAmount, unpaidAmount],
        backgroundColor:['#22c55e','#ef4444']
      }]
    },
    options:{
      responsive:true,
      plugins:{ legend:{ position:'bottom' } }
    }
  });

  const fyLabels = <?php echo json_encode($fyLabels); ?>;
  const fyTotals = <?php echo json_encode($fyTotals); ?>;
  new Chart(document.getElementById('fyChart'),{
    type:'bar',
    data:{
      labels: fyLabels,
      datasets:[{
        label: chartCurrency ? ('Total ('+chartCurrency+')') : 'Total (select currency)',
        data: fyTotals,
        backgroundColor:'rgba(35,73,153,0.8)'
      }]
    },
    options:{
      responsive:true,
      plugins:{ legend:{ display:false } },
      scales:{
        y:{ ticks:{ callback:(v)=> (chartCurrency ? (chartCurrency+' ') : '') + v } }
      }
    }
  });
</script>

<?php include "../../includes/footer.php"; ?>

