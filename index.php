<?php include "includes/header.php"; ?>
<?php
// Dashboard stats
$total_clients = 0;
$total_invoices = 0;
$paid_count = 0;
$unpaid_count = 0;
$paid_amount = 0.0;
$unpaid_amount = 0.0;
$total_amount = 0.0;

$cRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM clients"));
if($cRow){ $total_clients = intval($cRow['c']); }

$iRow = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT
    COUNT(*) AS total_invoices,
    SUM(grand_total) AS total_amount,
    SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
    SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
    SUM(CASE WHEN status='paid' THEN grand_total ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status='unpaid' THEN grand_total ELSE 0 END) AS unpaid_amount
  FROM invoices
  WHERE deleted_at IS NULL
"));
if($iRow){
  $total_invoices = intval($iRow['total_invoices'] ?? 0);
  $total_amount = floatval($iRow['total_amount'] ?? 0);
  $paid_count = intval($iRow['paid_count'] ?? 0);
  $unpaid_count = intval($iRow['unpaid_count'] ?? 0);
  $paid_amount = floatval($iRow['paid_amount'] ?? 0);
  $unpaid_amount = floatval($iRow['unpaid_amount'] ?? 0);
}

// Dashboard currency-wise totals (so INR/USD/EUR/GBP don't mix)
$dashCurrencyTotals = [];
$dashCurQ = mysqli_query($conn,"
  SELECT
    COALESCE(NULLIF(currency,''),'INR') AS currency,
    SUM(CASE WHEN status='paid' THEN grand_total ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status='unpaid' THEN grand_total ELSE 0 END) AS unpaid_amount,
    SUM(grand_total) AS total_amount
  FROM invoices
  WHERE deleted_at IS NULL
  GROUP BY COALESCE(NULLIF(currency,''),'INR')
  ORDER BY currency
");
while($r = mysqli_fetch_assoc($dashCurQ)){
  $dashCurrencyTotals[] = $r;
}

// GST vs Export counts (overall)
$typeCounts = mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT
    SUM(CASE WHEN invoice_type='gst' THEN 1 ELSE 0 END) AS gst_count,
    SUM(CASE WHEN invoice_type='export' THEN 1 ELSE 0 END) AS export_count
  FROM invoices
  WHERE deleted_at IS NULL
"));

// Current financial year totals for monthly chart (Apr-Mar based on stored financial_year)
$y = date('Y');
$m = intval(date('m'));
$start = ($m >= 4) ? $y : ($y - 1);
$end = $start + 1;
$current_fy = substr($start,2)."-".substr($end,2);

$monthlyRows = mysqli_query($conn,"
  SELECT MONTH(invoice_date) AS m, SUM(grand_total) AS total
  FROM invoices
  WHERE financial_year='$current_fy' AND deleted_at IS NULL
  GROUP BY MONTH(invoice_date)
  ORDER BY MONTH(invoice_date)
");
$monthlyTotals = array_fill(1, 12, 0.0);
while($r = mysqli_fetch_assoc($monthlyRows)){
  $mm = intval($r['m']);
  $monthlyTotals[$mm] = floatval($r['total']);
}

$fyRows = mysqli_query($conn,"
  SELECT financial_year AS fy, SUM(grand_total) AS total
  FROM invoices
  WHERE deleted_at IS NULL
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

<div class="sidebar">
<?php include "includes/sidebar.php"; ?>
</div>

<div class="content">

<div class="topbar d-flex justify-content-between">

<div class="logo">
Invoice Dashboard
</div>

<div>
<i class="fa fa-user"></i> <?php echo $users['name']; ?>
</div>

</div>

<style>
  .dash-card{
    border:0;
    border-radius:14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  }
  .dash-card .small{
    font-size:12px;
    opacity:.85;
  }
  .dash-card .count{
    font-size:22px;
    font-weight:800;
    margin:6px 0 0 0;
  }
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
  .status-layout{
    display:flex;
    gap:14px;
    align-items:flex-start;
  }
  .status-left{
    width:48%;
    min-width:180px;
  }
  .status-right{
    flex:1;
    text-align:left;
    font-size:12px;
    line-height:1.4;
    border-left:1px solid #eef0f3;
    padding-left:14px;
  }
  .cur-block{
    padding:10px 0;
    border-bottom:1px dashed #e7e9ee;
  }
  .cur-block:last-child{ border-bottom:0; }
  .cur-title{
    font-weight:900;
    font-size:13px;
    margin-bottom:6px;
    color:#111827;
  }
  .cur-inline{
    color:#374151;
    white-space:nowrap;
  }
  .cur-inline b{ color:#111827; }

  @media (max-width: 1200px){
    .status-layout{ flex-direction:column; }
    .status-left{ width:100%; }
    .status-right{ border-left:0; padding-left:0; border-top:1px solid #eef0f3; padding-top:12px; }
  }
</style>

<div class="row">

<div class="col-md-3">

<div class="card dash-card text-center p-3">

<i class="fa fa-users fa-2x text-primary"></i>

<h5 class="mt-2">Clients</h5>
<div class="small">Total Clients</div>
<div class="count"><?php echo $total_clients; ?></div>

<a href="<?php echo $base_url; ?>modules/clients/client-list.php" class="btn btn-primary btn-sm mt-2">View</a>

</div>

</div>


<div class="col-md-3">

<div class="card dash-card text-center p-3">

<i class="fa fa-file-invoice fa-2x text-success"></i>

<h5 class="mt-2">Invoices</h5>
<div class="small">Total Invoices</div>
<div class="count"><?php echo $total_invoices; ?></div>
<div class="small mt-1">
  GST: <?php echo intval($typeCounts['gst_count'] ?? 0); ?> |
  Export: <?php echo intval($typeCounts['export_count'] ?? 0); ?>
</div>

<a href="<?php echo $base_url; ?>modules/invoices/invoice-list.php" class="btn btn-success btn-sm mt-2">View</a>

</div>

</div>


<div class="col-md-3">

<div class="card dash-card text-center p-3">

<i class="fa fa-user-plus fa-2x text-warning"></i>

<h5 class="mt-2">Add Client</h5>
<div class="small">Quick action</div>
<div class="count">+</div>

<a href="<?php echo $base_url; ?>modules/clients/add-client.php" class="btn btn-warning btn-sm mt-2">Add</a>

</div>

</div>


<div class="col-md-3">

<div class="card dash-card text-center p-3">

<i class="fa fa-chart-line fa-2x text-danger"></i>

<h5 class="mt-2">Reports</h5>
<div class="small">Paid / Unpaid</div>
<div class="count"><?php echo $paid_count; ?> / <?php echo $unpaid_count; ?></div>

<a href="<?php echo $base_url; ?>modules/reports/reports.php" class="btn btn-danger btn-sm mt-2">View</a>

</div>

</div>

</div>
<div class="row g-3 mt-2">
  <div class="col-lg-6">
    <div class="card chart-card">
      <div class="card-header bg-white d-flex align-items-center justify-content-between" style="border:0;border-radius:14px 14px 0 0;">
        <p class="chart-title mb-0">
          <span id="salesChartTitle">Monthly Total</span> (<?php echo "FY ".$current_fy; ?>)
        </p>
        <select id="salesChartMode" class="form-select form-select-sm" style="width:auto;">
          <option value="monthly" selected>Monthly</option>
          <option value="yearly">Yearly</option>
        </select>
      </div>
      <div class="card-body">
        <canvas id="monthlyChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card chart-card">
      <div class="card-header bg-white" style="border:0;border-radius:14px 14px 0 0;">
        <p class="chart-title">Paid vs Unpaid (amount)</p>
      </div>
      <div class="card-body">
        <div class="status-layout">
          <div class="status-left">
            <canvas id="statusChart" height="200"></canvas>
          </div>
          <div class="status-right">
            <?php foreach($dashCurrencyTotals as $ct){ ?>
              <div class="cur-block">
                <div class="cur-title"><?php echo htmlspecialchars($ct['currency']); ?></div>
                <div class="cur-inline">
                  <b>Paid</b> <?php echo number_format((float)$ct['paid_amount'],2); ?> |
                  <b>Unpaid</b> <?php echo number_format((float)$ct['unpaid_amount'],2); ?> |
                  <b>Total</b> <?php echo number_format((float)$ct['total_amount'],2); ?>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const monthLabels = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  const monthlyData = <?php echo json_encode(array_values($monthlyTotals)); ?>;
  const yearlyLabels = <?php echo json_encode($fyLabels); ?>;
  const yearlyData = <?php echo json_encode($fyTotals); ?>;

  let salesChart = null;
  function renderSalesChart(mode){
    const canvas = document.getElementById('monthlyChart');
    if(!canvas) return;
    if(salesChart){ salesChart.destroy(); }

    const isMonthly = (mode === 'monthly');
    const labels = isMonthly ? monthLabels : yearlyLabels;
    const data = isMonthly ? monthlyData : yearlyData;
    const type = isMonthly ? 'line' : 'bar';

    const titleEl = document.getElementById('salesChartTitle');
    if(titleEl){ titleEl.textContent = isMonthly ? 'Monthly Total' : 'Yearly Total'; }

    salesChart = new Chart(canvas,{
      type,
      data:{
        labels,
        datasets:[{
          label:'Total',
          data,
          borderColor:'#234999',
          backgroundColor: isMonthly ? 'rgba(35,73,153,0.12)' : 'rgba(35,73,153,0.8)',
          fill: isMonthly,
          tension: isMonthly ? 0.35 : 0,
          pointRadius: isMonthly ? 3 : 0
        }]
      },
      options:{
        responsive:true,
        plugins:{ legend:{ display:false } },
        scales:{ y:{ ticks:{ callback:(v)=>'₹'+v } } }
      }
    });
  }

  renderSalesChart('monthly');

  const modeSelect = document.getElementById('salesChartMode');
  if(modeSelect){
    modeSelect.addEventListener('change', function(){
      renderSalesChart(this.value);
    });
  }

  new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{
      labels:['Paid','Unpaid'],
      datasets:[{
        data:[<?php echo json_encode($paid_amount); ?>, <?php echo json_encode($unpaid_amount); ?>],
        backgroundColor:['#22c55e','#ef4444']
      }]
    },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
  });
</script>

<?php include "includes/footer.php"; ?>