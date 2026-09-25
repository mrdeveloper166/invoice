
<?php
require_once __DIR__ . "/../../config/database.php";
include "../../includes/header.php";
include "../../includes/sidebar.php";

$query = mysqli_query($conn,"SELECT * FROM company_settings LIMIT 1");
$data = mysqli_fetch_assoc($query);

$msg="";

if(isset($_POST['save'])){

$company_name = $_POST['company_name'];
$address = $_POST['address'];
$state = $_POST['state'] ?? '';
$gstin = $_POST['gstin'];
$pan = $_POST['pan'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$website = $_POST['website'];

$bank_name = $_POST['bank_name'];
$account_number = $_POST['account_number'];
$ifsc = $_POST['ifsc'];
$swift = $_POST['swift'];

$logo_name = $data['logo'] ?? "";
$signature_name = $data['signature'] ?? "";


/* LOGO UPLOAD */

if(!empty($_FILES['logo']['name'])){

$logo_name = time()."_".$_FILES['logo']['name'];

move_uploaded_file(
$_FILES['logo']['tmp_name'],
"../../uploads/logo/".$logo_name
);

}


/* SIGNATURE UPLOAD */

if(!empty($_FILES['signature']['name'])){

$signature_name = time()."_".$_FILES['signature']['name'];

move_uploaded_file(
$_FILES['signature']['tmp_name'],
"../../uploads/signature/".$signature_name
);

}


mysqli_query($conn,"UPDATE company_settings SET

company_name='$company_name',
address='$address',
state='$state',
gstin='$gstin',
pan='$pan',
phone='$phone',
email='$email',
website='$website',

bank_name='$bank_name',
account_number='$account_number',
ifsc='$ifsc',
swift_code='$swift',

logo='$logo_name',
signature='$signature_name'

WHERE id=1

");

$msg="Company Profile Updated Successfully";

$query = mysqli_query($conn,"SELECT * FROM company_settings LIMIT 1");
$data = mysqli_fetch_assoc($query);

}
?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-building"></i> Company Profile</h4>
</div>

<div class="container-fluid">

<?php if($msg!=""){ ?>

<div class="alert alert-success">
<i class="fa fa-check-circle"></i> <?php echo $msg; ?>
</div>

<?php } ?>

<?php if(isset($_GET['test_email'])){ ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function(){
      if(typeof Swal === 'undefined'){ return; }
      var status = <?php echo json_encode($_GET['test_email']); ?>;
      var icon = (status === 'sent') ? 'success' : ((status === 'missing') ? 'warning' : 'error');
      var title =
        (status === 'sent') ? 'Test email sent' :
        (status === 'missing') ? 'Company email missing' :
        'Test email failed';
      var text =
        (status === 'sent') ? 'Company email par demo mail bhej di gayi.' :
        (status === 'missing') ? 'Company Profile me email fill karein.' :
        'Server mail settings check karein.';

      var err = <?php echo json_encode($_SESSION['test_email_error'] ?? ''); ?>;
      <?php unset($_SESSION['test_email_error']); ?>
      if(status !== 'sent' && err){
        text = text + "\n\nError: " + err;
      }

      Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonText: 'OK'
      }).then(function(){
        var url = new URL(window.location.href);
        url.searchParams.delete('test_email');
        window.history.replaceState({}, document.title, url.toString());
      });
    })();
  </script>
<?php } ?>

<div class="card shadow">

<div class="card-header text-white" style="background:#234999">

<i class="fa fa-building"></i> Company Information

</div>

<div class="card-body">

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-6 mb-3">

<label>Company Name</label>

<input type="text" name="company_name"
value="<?php echo $data['company_name'] ?? ''; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>GSTIN</label>

<input type="text" name="gstin"
value="<?php echo $data['gstin'] ?? ''; ?>"
class="form-control">

</div>

</div>


<div class="mb-3">

<label>Address</label>

<textarea name="address"
class="form-control"><?php echo $data['address'] ?? ''; ?></textarea>

</div>

<div class="mb-3">
<label>State</label>
<input type="text" name="state"
value="<?php echo htmlspecialchars($data['state'] ?? '', ENT_QUOTES); ?>"
class="form-control"
placeholder="e.g. Uttar Pradesh">
<small class="text-muted">Used to apply IGST when client is in a different state.</small>
</div>


<div class="row">

<div class="col-md-6 mb-3">

<label>PAN</label>

<input type="text" name="pan"
value="<?php echo $data['pan'] ?? ''; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Phone</label>

<input type="text" name="phone"
value="<?php echo $data['phone'] ?? ''; ?>"
class="form-control">

</div>



<div class="col-md-6 mb-3">

<label>Email</label>

<input type="email" name="email"
value="<?php echo $data['email'] ?? ''; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Website</label>

<input type="text" name="website"
value="<?php echo $data['website'] ?? ''; ?>"
class="form-control">

</div>
</div>
<hr>

<h5><i class="fa fa-bank"></i> Bank Details</h5>

<div class="row">

<div class="col-md-6 mb-3">

<label>Bank Name</label>

<input type="text" name="bank_name"
value="<?php echo $data['bank_name'] ?? ''; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>Account Number</label>

<input type="text" name="account_number"
value="<?php echo $data['account_number'] ?? ''; ?>"
class="form-control">

</div>

</div>


<div class="row">

<div class="col-md-6 mb-3">

<label>IFSC</label>

<input type="text" name="ifsc"
value="<?php echo $data['ifsc'] ?? ''; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>SWIFT Code</label>

<input type="text" name="swift"
value="<?php echo $data['swift_code'] ?? ''; ?>"
class="form-control">

</div>

</div>


<hr>

<h5><i class="fa fa-image"></i> Company Logo</h5>

<input type="file" name="logo" class="form-control">

<?php if(!empty($data['logo'])){ ?>

<br>

<p>Current Logo:</p>

<img src="../../uploads/logo/<?php echo $data['logo']; ?>" width="150">

<?php } ?>

<!-- <div class="d-flex align-items-center gap-2 mt-3">
  <a href="send-test-email.php" class="btn btn-outline-primary btn-sm">
    <i class="fa fa-paper-plane"></i> Send Test Email
  </a>
  <span class="text-muted small">Demo ke liye company email par short test mail bheje.</span>
</div> -->

<hr>

<h5><i class="fa fa-pen"></i> Authorized Signature</h5>

<input type="file" name="signature" class="form-control">

<?php if(!empty($data['signature'])){ ?>

<br>

<p>Current Signature:</p>

<img src="../../uploads/signature/<?php echo $data['signature']; ?>" width="200">

<?php } ?>


<br>

<button class="btn text-white" style="background:#234999" name="save">

<i class="fa fa-save"></i> Save Profile

</button>

</form>

</div>

</div>

</div>

</div>

<?php include "../../includes/footer.php"; ?>

