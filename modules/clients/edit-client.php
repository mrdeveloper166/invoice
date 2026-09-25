
<?php
include "../../config/database.php";
include "../../includes/header.php";
include "../../includes/sidebar.php";

$id = $_GET['id'];

$result = mysqli_query($conn,"SELECT * FROM clients WHERE id='$id'");
$data = mysqli_fetch_assoc($result);

$msg="";

if(isset($_POST['update'])){

$client_name = $_POST['client_name'];
$company_name = $_POST['company_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$state = $_POST['state'] ?? '';
$country = $_POST['country'] ?? '';

// Fallback support (when dropdown couldn't load)
if((!$country || trim($country)==='') && isset($_POST['country_fallback'])){
  $country = $_POST['country_fallback'];
}
if((!$state || trim($state)==='') && isset($_POST['state_fallback'])){
  $state = $_POST['state_fallback'];
}
$gstin = $_POST['gstin'];
$ved_no = $_POST['ved_no'];
mysqli_query($conn,"UPDATE clients SET
client_name='$client_name',
company_name='$company_name',
email='$email',
phone='$phone',
address='$address',
state='$state',
country='$country',
gstin='$gstin',
ved_no='$ved_no'
WHERE id='$id'");

$msg="Client Updated Successfully";

}
?>

<div class="content">

<div class="topbar">
<h4><i class="fa fa-edit"></i> Edit Client</h4>
</div>

<div class="container-fluid">

<?php if($msg!=""){ ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    Swal.fire({
      icon: 'success',
      title: <?php echo json_encode($msg); ?>,
      confirmButtonText: 'OK'
    }).then(function(){
      window.location = 'client-list.php';
    });
  </script>
<?php } ?>

<div class="card shadow">

<div class="card-header text-white" style="background:#234999">

<i class="fa fa-user"></i> Client Information

</div>

<div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label>Client Name</label>

<div class="input-group">

<span class="input-group-text"><i class="fa fa-user"></i></span>

<input type="text" name="client_name"
value="<?php echo $data['client_name']; ?>"
class="form-control" required>

</div>

</div>

<div class="col-md-6 mb-3">

<label>Company Name</label>

<div class="input-group">

<span class="input-group-text"><i class="fa fa-building"></i></span>

<input type="text" name="company_name"
value="<?php echo $data['company_name']; ?>"
class="form-control">

</div>

</div>

</div>


<div class="row">

<div class="col-md-6 mb-3">

<label>Email</label>

<div class="input-group">

<span class="input-group-text"><i class="fa fa-envelope"></i></span>

<input type="email" name="email"
value="<?php echo $data['email']; ?>"
class="form-control">

</div>

</div>


<div class="col-md-6 mb-3">

<label>Phone</label>

<div class="input-group">

<span class="input-group-text"><i class="fa fa-phone"></i></span>

<input type="text" name="phone"
value="<?php echo $data['phone']; ?>"
class="form-control">

</div>

</div>



<div class="col-md-6 mb-3">

<label>GSTIN</label>

<input type="text" name="gstin"
value="<?php echo $data['gstin']; ?>"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>VAT No</label>

<input type="text" name="ved_no"
value="<?php echo $data['ved_no']; ?>"
class="form-control">

</div>


</div>



<div class="row">
<div class="col-md-6 mb-3">

<label>Country</label>

<select id="countrySelect" name="country" class="form-control">
  <option value="">Select country</option>
</select>

<input type="text" id="countryFallback" name="country_fallback" class="form-control mt-2" placeholder="Type country" style="display:none;" value="<?php echo htmlspecialchars($data['country'] ?? '', ENT_QUOTES); ?>">

</div>
<div class="col-md-6 mb-3">

<label>State</label>

<select id="stateSelect" name="state" class="form-control" disabled>
  <option value="">Select state</option>
</select>

<input type="text" id="stateFallback" name="state_fallback" class="form-control mt-2" placeholder="Type state" style="display:none;" value="<?php echo htmlspecialchars($data['state'] ?? '', ENT_QUOTES); ?>">

</div>


</div>

<script>
  (function () {
    const countrySelect = document.getElementById('countrySelect');
    const stateSelect = document.getElementById('stateSelect');
    const countryHelp = document.getElementById('countryHelp');
    const stateHelp = document.getElementById('stateHelp');
    const countryFallback = document.getElementById('countryFallback');
    const stateFallback = document.getElementById('stateFallback');

    const initialCountry = <?php echo json_encode($data['country'] ?? ''); ?>;
    const initialState = <?php echo json_encode($data['state'] ?? ''); ?>;

    let countriesCache = null;

    function enableFallback() {
      countryHelp.style.display = 'block';
      stateHelp.style.display = 'block';
      countryFallback.style.display = 'block';
      stateFallback.style.display = 'block';
      countrySelect.disabled = true;
      stateSelect.disabled = true;

      const form = countrySelect.closest('form');
      if (form) {
        form.addEventListener('submit', function () {
          if (countryFallback.value) countrySelect.value = countryFallback.value;
          if (stateFallback.value) stateSelect.value = stateFallback.value;
        });
      }
    }

    function resetState() {
      stateSelect.innerHTML = '<option value="">Select state</option>';
      stateSelect.disabled = true;
    }

    function setCountriesOptions(list) {
      const frag = document.createDocumentFragment();
      frag.appendChild(new Option('Select country', ''));
      list.forEach((c) => frag.appendChild(new Option(c.name, c.name)));
      countrySelect.innerHTML = '';
      countrySelect.appendChild(frag);

      if (initialCountry) {
        countrySelect.value = initialCountry;
      }
    }

    function setStatesOptions(states) {
      const frag = document.createDocumentFragment();
      frag.appendChild(new Option('Select state', ''));
      states.forEach((s) => frag.appendChild(new Option(s.name, s.name)));
      stateSelect.innerHTML = '';
      stateSelect.appendChild(frag);
      stateSelect.disabled = false;

      if (initialState) {
        stateSelect.value = initialState;
      }
    }

    async function loadCountriesAndStates() {
      const res = await fetch('https://countriesnow.space/api/v0.1/countries/states', { cache: 'force-cache' });
      if (!res.ok) throw new Error('Failed to fetch countries');
      const json = await res.json();
      if (!json || json.error || !Array.isArray(json.data)) throw new Error('Invalid response');
      countriesCache = json.data;
      setCountriesOptions(countriesCache);

      if (initialCountry) {
        const match = countriesCache.find((c) => c.name === initialCountry);
        if (match && Array.isArray(match.states) && match.states.length > 0) {
          setStatesOptions(match.states);
        }
      }
    }

    countrySelect.addEventListener('change', function () {
      resetState();
      const selected = countrySelect.value;
      if (!selected || !countriesCache) return;
      const match = countriesCache.find((c) => c.name === selected);
      if (!match || !Array.isArray(match.states) || match.states.length === 0) return;
      setStatesOptions(match.states);
    });

    loadCountriesAndStates().catch(function () {
      enableFallback();
    });
  })();
</script>


<div class="mb-3">

<label>Address</label>

<textarea name="address"
class="form-control"><?php echo $data['address']; ?></textarea>

</div>

<button name="update"
class="btn text-white"
style="background:#234999">

<i class="fa fa-save"></i> Update Client

</button>

<a href="client-list.php"
class="btn btn-secondary">

<i class="fa fa-arrow-left"></i> Back

</a>

</form>

</div>

</div>

</div>

</div>

<?php include "../../includes/footer.php"; ?>
