<?php

require_once __DIR__ . "/../../config/database.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";

$query = mysqli_query($conn,"
SELECT invoices.*, clients.client_name
FROM invoices
LEFT JOIN clients ON invoices.client_id = clients.id
WHERE invoices.deleted_at IS NOT NULL
ORDER BY invoices.deleted_at DESC
");

?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-trash-restore"></i> Recycle Bin</h4>
</div>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header text-white d-flex justify-content-between align-items-center" style="background:#234999">

<span><i class="fa fa-trash-restore"></i> Deleted Invoices</span>

<a href="invoice-list.php" class="btn btn-light btn-sm">
<i class="fa fa-arrow-left"></i> Back to Invoices
</a>

</div>

<div class="card-body">

<?php if(mysqli_num_rows($query) === 0){ ?>
<div class="alert alert-info mb-0">
<i class="fa fa-info-circle"></i> Recycle bin is empty. Deleted invoices will appear here.
</div>
<?php }else{ ?>

<div class="table-responsive">

<table id="recycleBinTable" class="table table-bordered table-striped">

<thead class="table-dark">

<tr>
<th>Invoice No</th>
<th>Client</th>
<th>Date</th>
<th>Financial Year</th>
<th>Total</th>
<th>Type</th>
<th>Deleted On</th>
<th width="200">Action</th>
</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?php echo htmlspecialchars($row['invoice_number']); ?></td>

<td><?php echo htmlspecialchars($row['client_name']); ?></td>

<td><?php echo date('d-m-Y', strtotime($row['invoice_date'])); ?></td>

<td><?php echo htmlspecialchars($row['financial_year']); ?></td>

<td>
<?php
$currency = ($row['invoice_type'] == 'export') ? $row['currency'] : '₹';
echo $currency . " " . number_format($row['grand_total'], 2);
?>
</td>

<td>
<?php if($row['invoice_type'] == "gst"){ ?>
<span class="badge bg-success">GST</span>
<?php }else{ ?>
<span class="badge bg-info">Export</span>
<?php } ?>
</td>

<td><?php echo date('d-m-Y H:i', strtotime($row['deleted_at'])); ?></td>

<td>

<a href="restore-invoice.php?id=<?php echo $row['id']; ?>"
class="btn btn-success btn-sm btn-restore-invoice"
data-id="<?php echo $row['id']; ?>"
title="Restore">
<i class="fa fa-undo"></i>
</a>

<a href="view-invoice.php?id=<?php echo $row['id']; ?>"
target="_blank"
class="btn btn-info btn-sm"
title="View">
<i class="fa fa-eye"></i>
</a>

<a href="permanent-delete-invoice.php?id=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm btn-permanent-delete"
data-id="<?php echo $row['id']; ?>"
title="Permanently Delete">
<i class="fa fa-trash"></i>
</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php } ?>

</div>

</div>

</div>

</div>

<?php if(isset($_GET['restored'])){ ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  var ok = <?php echo ($_GET['restored'] == '1') ? 'true' : 'false'; ?>;
  var pinError = <?php echo (isset($_GET['error']) && $_GET['error'] == 'pin') ? 'true' : 'false'; ?>;
  if(typeof Swal === 'undefined'){ return; }
  Swal.fire({
    icon: ok ? 'success' : 'error',
    title: ok ? 'Invoice restored successfully' : (pinError ? 'Invalid admin PIN' : 'Unable to restore invoice'),
    confirmButtonText: 'OK'
  }).then(function(){
    var url = new URL(window.location.href);
    url.searchParams.delete('restored');
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url.toString());
  });
})();
</script>
<?php } ?>

<?php if(isset($_GET['deleted'])){ ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  var ok = <?php echo ($_GET['deleted'] == '1') ? 'true' : 'false'; ?>;
  var pinError = <?php echo (isset($_GET['error']) && $_GET['error'] == 'pin') ? 'true' : 'false'; ?>;
  if(typeof Swal === 'undefined'){ return; }
  Swal.fire({
    icon: ok ? 'success' : 'error',
    title: ok ? 'Invoice permanently deleted' : (pinError ? 'Invalid admin PIN' : 'Unable to delete invoice'),
    confirmButtonText: 'OK'
  }).then(function(){
    var url = new URL(window.location.href);
    url.searchParams.delete('deleted');
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url.toString());
  });
})();
</script>
<?php } ?>

<link rel="stylesheet" href="https://cdn.datatables.net/2.3.7/css/dataTables.dataTables.css">
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.3.7/js/dataTables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
  if($('#recycleBinTable').length){
    new DataTable('#recycleBinTable', {
      pageLength: 10,
      order: [[6, 'desc']]
    });
  }
});

$(document).on('click', '.btn-restore-invoice', function(e){
  e.preventDefault();
  var invoiceId = $(this).data('id');

  if(typeof Swal === 'undefined'){
    var pin = prompt('Enter admin confirmation PIN:');
    if(pin){
      window.location = 'restore-invoice.php?id=' + invoiceId + '&pin=' + encodeURIComponent(pin);
    }
    return;
  }

  Swal.fire({
    icon: 'question',
    title: 'Restore this invoice?',
    text: 'Enter admin PIN to confirm restore.',
    input: 'password',
    inputLabel: 'Admin Confirmation PIN',
    inputPlaceholder: 'Enter PIN',
    inputAttributes: {
      maxlength: 20,
      autocapitalize: 'off',
      autocorrect: 'off'
    },
    showCancelButton: true,
    confirmButtonText: 'Yes, restore it',
    confirmButtonColor: '#198754',
    cancelButtonText: 'Cancel',
    preConfirm: function(pin){
      if(!pin){
        Swal.showValidationMessage('Please enter admin PIN');
        return false;
      }
      return $.ajax({
        url: 'verify-admin-pin.php',
        type: 'POST',
        data: { pin: pin },
        dataType: 'json'
      }).then(function(response){
        if(!response.success){
          throw new Error(response.message || 'Invalid admin PIN');
        }
        return pin;
      }).catch(function(err){
        Swal.showValidationMessage(err.message || 'Invalid admin PIN');
      });
    }
  }).then(function(result){
    if(result.isConfirmed && result.value){
      window.location = 'restore-invoice.php?id=' + invoiceId + '&pin=' + encodeURIComponent(result.value);
    }
  });
});

$(document).on('click', '.btn-permanent-delete', function(e){
  e.preventDefault();
  var invoiceId = $(this).data('id');

  if(typeof Swal === 'undefined'){
    var pin = prompt('Enter admin confirmation PIN:');
    if(pin){
      window.location = 'permanent-delete-invoice.php?id=' + invoiceId + '&pin=' + encodeURIComponent(pin);
    }
    return;
  }

  Swal.fire({
    icon: 'warning',
    title: 'Permanently delete this invoice?',
    text: 'This action cannot be undone. Enter admin PIN to confirm.',
    input: 'password',
    inputLabel: 'Admin Confirmation PIN',
    inputPlaceholder: 'Enter PIN',
    inputAttributes: {
      maxlength: 20,
      autocapitalize: 'off',
      autocorrect: 'off'
    },
    showCancelButton: true,
    confirmButtonText: 'Delete permanently',
    confirmButtonColor: '#dc3545',
    cancelButtonText: 'Cancel',
    preConfirm: function(pin){
      if(!pin){
        Swal.showValidationMessage('Please enter admin PIN');
        return false;
      }
      return $.ajax({
        url: 'verify-admin-pin.php',
        type: 'POST',
        data: { pin: pin },
        dataType: 'json'
      }).then(function(response){
        if(!response.success){
          throw new Error(response.message || 'Invalid admin PIN');
        }
        return pin;
      }).catch(function(err){
        Swal.showValidationMessage(err.message || 'Invalid admin PIN');
      });
    }
  }).then(function(result){
    if(result.isConfirmed && result.value){
      window.location = 'permanent-delete-invoice.php?id=' + invoiceId + '&pin=' + encodeURIComponent(result.value);
    }
  });
});
</script>

<?php include "../../includes/footer.php"; ?>
