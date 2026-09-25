<?php
include "../../config/database.php";
include "../../includes/header.php";
include "../../includes/sidebar.php";

$result = mysqli_query($conn,"SELECT * FROM clients ORDER BY id DESC");
?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-users"></i> Client List</h4>
</div>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header text-white d-flex justify-content-between"
style="background:#234999">

<span><i class="fa fa-users"></i> All Clients</span>

<a href="add-client.php" class="btn btn-light btn-sm">
<i class="fa fa-plus"></i> Add Client
</a>

</div>

<div class="card-body">
<div class="table-responsive">
<table id="clientTable" class="table table-striped table-bordered">

<thead class="table-dark">

<tr>

<!-- <th>ID</th> -->
<th>Client Name</th>
<th>Company</th>
<th>Email</th>
<th>Phone</th>
<th>GSTIN</th>
<th>VAT No</th>
<th>Country</th>
<th>State</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<!-- <td><?php echo $row['id']; ?></td> -->

<td><?php echo $row['client_name']; ?></td>

<td><?php echo $row['company_name']; ?></td>

<td><?php echo $row['email']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['gstin']; ?></td>
<td><?php echo $row['ved_no']; ?></td>


<td><?php echo $row['country']; ?></td>
<td><?php echo $row['state']; ?></td>
<td width="150">

<a href="edit-client.php?id=<?php echo $row['id']; ?>" 
class="btn btn-sm btn-primary">

<i class="fa fa-edit"></i>
</a>

<a href="delete-client.php?id=<?php echo $row['id']; ?>" 
class="btn btn-sm btn-danger btn-delete-client">

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
        title: ok ? 'Client deleted successfully' : 'Unable to delete client',
        confirmButtonText: 'OK'
      }).then(function(){
        var url = new URL(window.location.href);
        url.searchParams.delete('deleted');
        window.history.replaceState({}, document.title, url.toString());
      });
    })();
  </script>
<?php } ?>

<!-- DataTables v2 + Buttons v3 (same as invoice-list) -->
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

$(document).ready(function(){

if(typeof window.JSZip === 'undefined' && typeof JSZip !== 'undefined'){ window.JSZip = JSZip; }
if(typeof window.pdfMake === 'undefined' && typeof pdfMake !== 'undefined'){ window.pdfMake = pdfMake; }

new DataTable('#clientTable', {
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
// SweetAlert delete confirmation
$(document).on('click','.btn-delete-client',function(e){
  e.preventDefault();
  var href = $(this).attr('href');
  if(typeof Swal === 'undefined'){
    if(confirm('Are you sure to deleted this client ?')){
      window.location = href;
    }
    return;
  }
  Swal.fire({
    icon: 'warning',
    title: 'Are you sure to deleted this client ?',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel'
  }).then(function(result){
    if(result.isConfirmed){
      window.location = href;
    }
  });
});
</script>

<?php include "../../includes/footer.php"; ?>
