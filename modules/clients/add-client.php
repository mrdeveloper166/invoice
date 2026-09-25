<?php

include "../../config/database.php";
include "../../includes/header.php";
include "../../includes/sidebar.php";

$msg = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Save Client
|--------------------------------------------------------------------------
*/

if (isset($_POST['submit'])) {

    $client_name  = trim($_POST['client_name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');

    $state   = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Fallback support
    |--------------------------------------------------------------------------
    */

    if ($country === '' && isset($_POST['country_fallback'])) {
        $country = trim($_POST['country_fallback']);
    }

    if ($state === '' && isset($_POST['state_fallback'])) {
        $state = trim($_POST['state_fallback']);
    }

    $gstin  = strtoupper(trim($_POST['gstin'] ?? ''));
    $ved_no = trim($_POST['ved_no'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | GSTIN Validation
    |--------------------------------------------------------------------------
    */

    if ($gstin !== '') {

        $gstinPattern =
            '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

        if (!preg_match($gstinPattern, $gstin)) {

            $error = "Invalid GSTIN format.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Check whether GSTIN was already verified
            |--------------------------------------------------------------------------
            */

            $verifiedGSTIN =
                $_SESSION['gstin_verified']['gstin'] ?? '';

            if ($verifiedGSTIN !== $gstin) {

                $error =
                    "Please verify the GSTIN before saving the client.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Insert Client
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO clients
            (
                client_name,
                company_name,
                email,
                phone,
                address,
                state,
                country,
                gstin,
                ved_no
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssss",
                $client_name,
                $company_name,
                $email,
                $phone,
                $address,
                $state,
                $country,
                $gstin,
                $ved_no
            );

            if (mysqli_stmt_execute($stmt)) {

                /*
                |--------------------------------------------------------------------------
                | Clear verified GSTIN session after successful save
                |--------------------------------------------------------------------------
                */

                unset($_SESSION['gstin_verified']);

                $msg = "Client Added Successfully";

            } else {

                $error =
                    "Unable to save client: " .
                    mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);

        } else {

            $error =
                "Database error: " .
                mysqli_error($conn);
        }
    }
}

?>

<div class="content">

    <div class="topbar">
        <h4>
            <i class="fa fa-user-plus"></i>
            Add Client
        </h4>
    </div>

    <div class="container-fluid">

        <?php if ($msg !== "") { ?>

            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <script>
                Swal.fire({
                    icon: 'success',
                    title: <?= json_encode($msg); ?>,
                    confirmButtonText: 'OK'
                }).then(function () {
                    window.location = 'client-list.php';
                });
            </script>

        <?php } ?>

        <?php if ($error !== "") { ?>

            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i>
                <?= htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <div class="card shadow">

            <div class="card-header text-white" style="background:#234999">

                <i class="fa fa-user"></i>
                Client Information

            </div>


            <div class="card-body">

                <form method="POST" id="clientForm">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>Client Name</label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fa fa-user"></i>
                                </span>

                                <input
                                    type="text"
                                    name="client_name"
                                    class="form-control"
                                    required
                                >

                            </div>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label>Company Name</label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fa fa-building"></i>
                                </span>

                                <input
                                    type="text"
                                    name="company_name"
                                    id="company_name"
                                    class="form-control"
                                >

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>Email</label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fa fa-envelope"></i>
                                </span>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                >

                            </div>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label>Phone</label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fa fa-phone"></i>
                                </span>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- GSTIN -->

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>
                                GSTIN
                            </label>

                            <div class="input-group">

                                <input
                                    type="text"
                                    name="gstin"
                                    id="gstin"
                                    class="form-control"
                                    maxlength="15"
                                    placeholder="Enter GSTIN"
                                    style="text-transform:uppercase;"
                                >

                                <button
                                    type="button"
                                    id="verifyGSTIN"
                                    class="btn btn-success"
                                >
                                    <i class="fa fa-check-circle"></i>
                                    Verify
                                </button>

                            </div>

                            <small
                                id="gstinMessage"
                                class="form-text"
                            ></small>

                        </div>


                        <div class="col-md-6 mb-3">

                            <label>VAT No</label>

                            <input
                                type="text"
                                name="ved_no"
                                class="form-control"
                            >

                        </div>

                    </div>


                    <!-- GST Information -->

                    <div
                        id="gstDetails"
                        style="display:none;"
                        class="alert alert-info"
                    >

                        <div class="row">

                            <div class="col-md-6">
                                <strong>GST Status:</strong>
                                <span id="gstStatus"></span>
                            </div>

                            <div class="col-md-6">
                                <strong>Taxpayer Type:</strong>
                                <span id="gstTaxpayerType"></span>
                            </div>

                            <div class="col-md-6 mt-2">
                                <strong>Registration Date:</strong>
                                <span id="gstRegistrationDate"></span>
                            </div>

                            <div class="col-md-6 mt-2">
                                <strong>Block Status:</strong>
                                <span id="gstBlockStatus"></span>
                            </div>

                            <div class="col-md-6 mt-2">
                                <strong>State Code:</strong>
                                <span id="gstStateCode"></span>
                            </div>

                            <div class="col-md-6 mt-2">
                                <strong>Pincode:</strong>
                                <span id="gstPincode"></span>
                            </div>

                            <div class="col-md-12 mt-2">
                                <strong>City / Locality:</strong>
                                <span id="gstCity"></span>
                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label>Country</label>

                            <select
                                id="countrySelect"
                                name="country"
                                class="form-control"
                            >

                                <option value="">
                                    Select country
                                </option>

                            </select>

                            <input
                                type="text"
                                id="countryFallback"
                                name="country_fallback"
                                class="form-control mt-2"
                                placeholder="Type country"
                                style="display:none;"
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label>State</label>

                            <select
                                id="stateSelect"
                                name="state"
                                class="form-control"
                                disabled
                            >

                                <option value="">
                                    Select state
                                </option>

                            </select>

                            <input
                                type="text"
                                id="stateFallback"
                                name="state_fallback"
                                class="form-control mt-2"
                                placeholder="Type state"
                                style="display:none;"
                            >

                        </div>

                    </div>


                    <div class="mb-3">

                        <label>Address</label>

                        <textarea
                            name="address"
                            id="address"
                            class="form-control"
                            rows="3"
                        ></textarea>

                    </div>


                    <button
                        name="submit"
                        type="submit"
                        class="btn text-white"
                        style="background:#234999"
                    >

                        <i class="fa fa-save"></i>
                        Save Client

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

(function () {

    const countrySelect =
        document.getElementById('countrySelect');

    const stateSelect =
        document.getElementById('stateSelect');

    const countryFallback =
        document.getElementById('countryFallback');

    const stateFallback =
        document.getElementById('stateFallback');

    let countriesCache = null;


    /*
    |--------------------------------------------------------------------------
    | GSTIN Verification
    |--------------------------------------------------------------------------
    */

    const gstinInput =
        document.getElementById('gstin');

    const verifyButton =
        document.getElementById('verifyGSTIN');

    const gstinMessage =
        document.getElementById('gstinMessage');

    const gstDetails =
        document.getElementById('gstDetails');


    gstinInput.addEventListener('input', function () {

        this.value =
            this.value
                .toUpperCase()
                .replace(/\s/g, '');

        gstDetails.style.display = 'none';

        gstinMessage.innerHTML = '';

        verifyButton.disabled = false;

    });


    verifyButton.addEventListener('click', async function () {

        const gstin =
            gstinInput.value.trim().toUpperCase();

        const gstinPattern =
            /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;


        /*
        |--------------------------------------------------------------------------
        | Client-side validation
        |--------------------------------------------------------------------------
        */

        if (!gstinPattern.test(gstin)) {

            gstinMessage.className =
                'form-text text-danger';

            gstinMessage.innerHTML =
                '<i class="fa fa-times-circle"></i> ' +
                'Invalid GSTIN format.';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Loading
        |--------------------------------------------------------------------------
        */

        verifyButton.disabled = true;

        verifyButton.innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> Verifying...';

        gstinMessage.className =
            'form-text text-info';

        gstinMessage.innerHTML =
            'Checking GSTIN...';


        try {

            const formData = new FormData();

            formData.append('gstin', gstin);


            const response = await fetch(
                'gstin-lookup.php',
                {
                    method: 'POST',
                    body: formData
                }
            );


            const result =
                await response.json();


            /*
            |--------------------------------------------------------------------------
            | API Success
            |--------------------------------------------------------------------------
            */

            if (result.success) {

                const data =
                    result.data || {};


                gstinMessage.className =
                    'form-text text-success';

                gstinMessage.innerHTML =
                    '<i class="fa fa-check-circle"></i> ' +
                    'GSTIN verified successfully.';


                /*
                |--------------------------------------------------------------------------
                | Auto-fill company name
                |--------------------------------------------------------------------------
                */

                if (data.legal_name) {

                    document.getElementById(
                        'company_name'
                    ).value = data.legal_name;

                }


            /*
|--------------------------------------------------------------------------
| Auto-fill Address + State + Pincode
|--------------------------------------------------------------------------
*/

let fullAddress = '';

if (data.address) {
    fullAddress = data.address;
}

/*
|--------------------------------------------------------------------------
| Get State Name from GST State Code
|--------------------------------------------------------------------------
*/

const gstStateMap = {
    '01': 'Jammu and Kashmir',
    '02': 'Himachal Pradesh',
    '03': 'Punjab',
    '04': 'Chandigarh',
    '05': 'Uttarakhand',
    '06': 'Haryana',
    '07': 'Delhi',
    '08': 'Rajasthan',
    '09': 'Uttar Pradesh',
    '10': 'Bihar',
    '11': 'Sikkim',
    '12': 'Arunachal Pradesh',
    '13': 'Nagaland',
    '14': 'Manipur',
    '15': 'Mizoram',
    '16': 'Tripura',
    '17': 'Meghalaya',
    '18': 'Assam',
    '19': 'West Bengal',
    '20': 'Jharkhand',
    '21': 'Odisha',
    '22': 'Chhattisgarh',
    '23': 'Madhya Pradesh',
    '24': 'Gujarat',
    '26': 'Dadra and Nagar Haveli and Daman and Diu',
    '27': 'Maharashtra',
    '28': 'Andhra Pradesh',
    '29': 'Karnataka',
    '30': 'Goa',
    '31': 'Lakshadweep',
    '32': 'Kerala',
    '33': 'Tamil Nadu',
    '34': 'Puducherry',
    '35': 'Andaman and Nicobar Islands',
    '36': 'Telangana',
    '37': 'Andhra Pradesh',
    '38': 'Ladakh',
    '97': 'Other Territory',
    '99': 'Centre Jurisdiction'
};

const stateCode =
    String(data.state_code || '').padStart(2, '0');

const stateName =
    gstStateMap[stateCode] || '';

const pincode =
    data.pincode || '';

/*
|--------------------------------------------------------------------------
| Add State
|--------------------------------------------------------------------------
*/

if (stateName) {
    fullAddress +=
        (fullAddress ? ', ' : '') +
        stateName;
}

/*
|--------------------------------------------------------------------------
| Add Pincode
|--------------------------------------------------------------------------
*/

if (pincode) {
    fullAddress +=
        (fullAddress ? ', ' : '') +
        pincode;
}

/*
|--------------------------------------------------------------------------
| Put Complete Address in Address Field
|--------------------------------------------------------------------------
*/

document.getElementById('address').value =
    fullAddress;


                /*
                |--------------------------------------------------------------------------
                | Display GST information
                |--------------------------------------------------------------------------
                */

                document.getElementById(
                    'gstStatus'
                ).textContent =
                    data.status || '-';


                document.getElementById(
                    'gstTaxpayerType'
                ).textContent =
                    data.taxpayer_type || '-';


                document.getElementById(
                    'gstRegistrationDate'
                ).textContent =
                    data.registration_date || '-';


                document.getElementById(
                    'gstBlockStatus'
                ).textContent =
                    data.block_status || '-';


                document.getElementById(
                    'gstStateCode'
                ).textContent =
                    data.state_code || '-';


                document.getElementById(
                    'gstPincode'
                ).textContent =
                    data.pincode || '-';


                document.getElementById(
                    'gstCity'
                ).textContent =
                    data.city || '-';


                gstDetails.style.display =
                    'block';


          /*
|--------------------------------------------------------------------------
| Automatically Set Country + State From GST State Code
|--------------------------------------------------------------------------
*/

const indiaOption =
    Array.from(
        countrySelect.options
    ).find(
        option =>
            option.value.toLowerCase() === 'india'
    );

if (indiaOption) {

    countrySelect.value =
        indiaOption.value;

    /*
    | Trigger country change so India states are loaded
    */
    countrySelect.dispatchEvent(
        new Event('change')
    );


    /*
    |--------------------------------------------------------------------------
    | GST State Code
    |--------------------------------------------------------------------------
    */

    const gstStateCode =
        String(data.state_code || '').padStart(2, '0');


    /*
    |--------------------------------------------------------------------------
    | Wait for states to load
    |--------------------------------------------------------------------------
    */

    let stateAttempts = 0;

    const selectGSTState = setInterval(function () {

        stateAttempts++;

        if (
            stateSelect.options.length > 1 &&
            countriesCache
        ) {

            clearInterval(selectGSTState);


            /*
            |--------------------------------------------------------------------------
            | State code -> State name mapping
            |--------------------------------------------------------------------------
            */

            const gstStateMap = {

                '01': 'Jammu and Kashmir',
                '02': 'Himachal Pradesh',
                '03': 'Punjab',
                '04': 'Chandigarh',
                '05': 'Uttarakhand',
                '06': 'Haryana',
                '07': 'Delhi',
                '08': 'Rajasthan',
                '09': 'Uttar Pradesh',
                '10': 'Bihar',
                '11': 'Sikkim',
                '12': 'Arunachal Pradesh',
                '13': 'Nagaland',
                '14': 'Manipur',
                '15': 'Mizoram',
                '16': 'Tripura',
                '17': 'Meghalaya',
                '18': 'Assam',
                '19': 'West Bengal',
                '20': 'Jharkhand',
                '21': 'Odisha',
                '22': 'Chhattisgarh',
                '23': 'Madhya Pradesh',
                '24': 'Gujarat',
                '26': 'Dadra and Nagar Haveli and Daman and Diu',
                '27': 'Maharashtra',
                '28': 'Andhra Pradesh',
                '29': 'Karnataka',
                '30': 'Goa',
                '31': 'Lakshadweep',
                '32': 'Kerala',
                '33': 'Tamil Nadu',
                '34': 'Puducherry',
                '35': 'Andaman and Nicobar Islands',
                '36': 'Telangana',
                '37': 'Andhra Pradesh',
                '38': 'Ladakh',
                '97': 'Other Territory',
                '99': 'Centre Jurisdiction'
            };


            const stateName =
                gstStateMap[gstStateCode];


            if (!stateName) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Find matching state in dropdown
            |--------------------------------------------------------------------------
            */

            const stateOption =
                Array.from(
                    stateSelect.options
                ).find(function (option) {

                    return option.value
                        .toLowerCase()
                        .trim() ===
                        stateName.toLowerCase().trim();

                });


            if (stateOption) {

                stateSelect.value =
                    stateOption.value;

                /*
                | Trigger change event
                */
                stateSelect.dispatchEvent(
                    new Event('change')
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Stop after 5 seconds
        |--------------------------------------------------------------------------
        */

        if (stateAttempts >= 50) {

            clearInterval(selectGSTState);

        }

    }, 100);

}

                /*
                |--------------------------------------------------------------------------
                | Show remaining credits
                |--------------------------------------------------------------------------
                */

                if (
                    result.credits_remaining !== null &&
                    result.credits_remaining !== undefined
                ) {

                    gstinMessage.innerHTML +=
                        ' Credits remaining: ' +
                        result.credits_remaining;
                }

            } else {

                gstinMessage.className =
                    'form-text text-danger';

                gstinMessage.innerHTML =
                    '<i class="fa fa-times-circle"></i> ' +
                    (result.message ||
                    'GSTIN verification failed.');

                gstDetails.style.display =
                    'none';
            }

        } catch (error) {

            console.error(error);

            gstinMessage.className =
                'form-text text-danger';

            gstinMessage.innerHTML =
                '<i class="fa fa-times-circle"></i> ' +
                'Unable to verify GSTIN. Please try again.';

        }


        verifyButton.disabled = false;

        verifyButton.innerHTML =
            '<i class="fa fa-check-circle"></i> Verify';

    });


    /*
    |--------------------------------------------------------------------------
    | Country / State API
    |--------------------------------------------------------------------------
    */

    function enableFallback() {

        if (countryFallback) {
            countryFallback.style.display = 'block';
        }

        if (stateFallback) {
            stateFallback.style.display = 'block';
        }

        countrySelect.disabled = true;
        stateSelect.disabled = true;


        const form =
            countrySelect.closest('form');

        if (form) {

            form.addEventListener(
                'submit',
                function () {

                    if (countryFallback.value) {
                        countrySelect.value =
                            countryFallback.value;
                    }

                    if (stateFallback.value) {
                        stateSelect.value =
                            stateFallback.value;
                    }

                }
            );

        }

    }


    function resetState() {

        stateSelect.innerHTML =
            '<option value="">Select state</option>';

        stateSelect.disabled = true;

    }


    function setCountriesOptions(list) {

        const frag =
            document.createDocumentFragment();

        frag.appendChild(
            new Option(
                'Select country',
                ''
            )
        );


        list.forEach(function (c) {

            frag.appendChild(
                new Option(
                    c.name,
                    c.name
                )
            );

        });


        countrySelect.innerHTML = '';

        countrySelect.appendChild(frag);

    }


    function setStatesOptions(states) {

        const frag =
            document.createDocumentFragment();

        frag.appendChild(
            new Option(
                'Select state',
                ''
            )
        );


        states.forEach(function (s) {

            frag.appendChild(
                new Option(
                    s.name,
                    s.name
                )
            );

        });


        stateSelect.innerHTML = '';

        stateSelect.appendChild(frag);

        stateSelect.disabled = false;

    }


    async function loadCountriesAndStates() {

        const res = await fetch(
            'https://countriesnow.space/api/v0.1/countries/states',
            {
                cache: 'force-cache'
            }
        );


        if (!res.ok) {
            throw new Error(
                'Failed to fetch countries'
            );
        }


        const json =
            await res.json();


        if (
            !json ||
            json.error ||
            !Array.isArray(json.data)
        ) {

            throw new Error(
                'Invalid response'
            );

        }


        countriesCache =
            json.data;

        setCountriesOptions(
            countriesCache
        );

    }


    countrySelect.addEventListener(
        'change',
        function () {

            resetState();

            const selected =
                countrySelect.value;

            if (
                !selected ||
                !countriesCache
            ) {
                return;
            }


            const match =
                countriesCache.find(
                    c => c.name === selected
                );


            if (
                !match ||
                !Array.isArray(match.states) ||
                match.states.length === 0
            ) {
                return;
            }


            setStatesOptions(
                match.states
            );

        }
    );


    loadCountriesAndStates()
        .catch(function () {

            enableFallback();

        });


})();

</script>


<?php include "../../includes/footer.php"; ?>