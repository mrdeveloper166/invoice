<?php

require_once __DIR__ . "/../../config/database.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";

$query = mysqli_query($conn,"
SELECT invoices.*, clients.client_name
FROM invoices
LEFT JOIN clients ON invoices.client_id = clients.id
WHERE invoices.deleted_at IS NULL
ORDER BY invoices.id DESC
");

?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-file-invoice"></i> All Invoices</h4>
</div>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header text-white d-flex justify-content-between align-items-center" style="background:#234999">

<span><i class="fa fa-file-invoice"></i> Invoice List</span>

<a href="create-invoice.php" class="btn btn-light btn-sm">
<i class="fa fa-plus"></i> Create Invoice
</a>

</div>

<div class="card-body">

<div class="table-responsive">

<table id="invoiceTable" class="table table-bordered table-striped">

<thead class="table-dark">

<tr>
<!-- <th>ID</th> -->
<th>Invoice No</th>
<th>Client</th>
<th>Date</th>
<th>Financial Year</th>
<th>Total</th>
<th>Type</th>
<th>Status</th>
<th width="180">Action</th>
</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($query)){ ?>

<tr>

<!-- <td><?php echo $row['id']; ?></td> -->

<td><?php echo $row['invoice_number']; ?></td>

<td><?php echo $row['client_name']; ?></td>

<td><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>

<td><?php echo $row['financial_year']; ?></td>

<td>
<?php 
$currency = ($row['invoice_type']=='export') ? $row['currency'] : '₹'; 
echo $currency . " " . number_format($row['grand_total'],2); 
?>
</td>

<td>

<?php if($row['invoice_type']=="gst"){ ?>
<span class="badge bg-success">GST</span>
<?php }else{ ?>
<span class="badge bg-info">Export</span>
<?php } ?>

</td>
<td>

<select class="form-control status_change
<?php echo ($row['status']=="paid") ? 'bg-success text-white' : 'bg-warning'; ?>"
data-id="<?php echo $row['id']; ?>">

<option value="unpaid"
<?php if($row['status']=="unpaid") echo "selected"; ?>>
Unpaid
</option>

<option value="paid"
<?php if($row['status']=="paid") echo "selected"; ?>>
Paid
</option>

</select>

</td>
<td>

<a href="view-invoice.php?id=<?php echo $row['id']; ?>" 
target="_blank" 
class="btn btn-info btn-sm">
<i class="fa fa-eye"></i>
</a>

<a href="edit-invoice.php?id=<?php echo $row['id']; ?>" 
class="btn btn-primary btn-sm">
<i class="fa fa-pen"></i>
</a>

<a href="send-invoice-email.php?id=<?php echo $row['id']; ?>"
class="btn btn-warning btn-sm"
title="Send invoice to client email">
<i class="fa fa-envelope"></i>
</a>

<a href="delete-invoice.php?id=<?php echo $row['id']; ?>" 
class="btn btn-danger btn-sm btn-delete-invoice">
<i class="fa fa-trash"></i>
</a>

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

<?php if(isset($_GET['deleted'])){ ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function(){
      var ok = <?php echo ($_GET['deleted'] == '1') ? 'true' : 'false'; ?>;
      if(typeof Swal === 'undefined'){ return; }
      Swal.fire({
        icon: ok ? 'success' : 'error',
        title: ok ? 'Invoice moved to Recycle Bin' : 'Unable to delete invoice',
        confirmButtonText: 'OK'
      }).then(function(){
        // clean URL
        var url = new URL(window.location.href);
        url.searchParams.delete('deleted');
        window.history.replaceState({}, document.title, url.toString());
      });
    })();
  </script>
<?php } ?>

<?php if(isset($_GET['email'])){ ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function(){
      if(typeof Swal === 'undefined'){ return; }
      var status = <?php echo json_encode($_GET['email']); ?>;
      var icon = (status === 'sent') ? 'success' : ((status === 'missing') ? 'warning' : 'error');
      var title =
        (status === 'sent') ? 'Email sent' :
        (status === 'missing') ? 'Client email missing' :
        'Email failed';
      var text =
        (status === 'sent') ? 'Invoice PDF attached and sent to client email.' :
        (status === 'missing') ? 'Client Email Not Found.' :
        'Email send fail. Please Check Server mail settings.';

      Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonText: 'OK'
      }).then(function(){
        var url = new URL(window.location.href);
        url.searchParams.delete('email');
        window.history.replaceState({}, document.title, url.toString());
      });
    })();
  </script>
<?php } ?>


<!-- DataTables v2 + Buttons v3 (same as reports) -->
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

<!-- SweetAlert2 (for per-page safety) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
if(typeof window.JSZip === 'undefined' && typeof JSZip !== 'undefined'){ window.JSZip = JSZip; }
if(typeof window.pdfMake === 'undefined' && typeof pdfMake !== 'undefined'){ window.pdfMake = pdfMake; }

$(document).ready(function(){
  new DataTable('#invoiceTable', {
    pageLength: 10,
    order: [[0,'desc']],
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
$(document).on("change",".status_change",function(){

var status = $(this).val();
var id = $(this).data("id");
var el = $(this);

$.ajax({
url:"update-status.php",
type:"POST",
data:{
id:id,
status:status
},
success:function(){

if(status=="paid"){
el.removeClass("bg-warning")
.addClass("bg-success text-white");
}
else{
el.removeClass("bg-success text-white")
.addClass("bg-warning");
}

}

});

});

// SweetAlert delete confirmation
$(document).on('click','.btn-delete-invoice',function(e){
  e.preventDefault();
  var href = $(this).attr('href');
  if(typeof Swal === 'undefined'){
    // Fallback to native confirm if SweetAlert2 is not available
    if(confirm('Are you sure to delete this invoice ?')){
      window.location = href;
    }
    return;
  }
  Swal.fire({
    icon: 'warning',
    title: 'Move this invoice to Recycle Bin?',
    text: 'You can restore it later from Recycle Bin.',
    showCancelButton: true,
    confirmButtonText: 'Yes, move to bin',
    cancelButtonText: 'Cancel'
  }).then(function(result){
    if(result.isConfirmed){
      window.location = href;
    }
  });
});
</script>
<?php include "../../includes/footer.php"; ?>