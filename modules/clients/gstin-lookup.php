<?php

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Start Session Only If Needed
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Get API Key
|--------------------------------------------------------------------------
*/

$apiKey = 'gak_848bf272fb344d23b828d409402fd482';

if (function_exists('apache_getenv')) {
    $apiKey = apache_getenv('GSTIN_API_KEY');
}

if (!$apiKey) {
    $apiKey = getenv('GSTIN_API_KEY');
}

if (!$apiKey && isset($_SERVER['GSTIN_API_KEY'])) {
    $apiKey = $_SERVER['GSTIN_API_KEY'];
}

if (!$apiKey) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'GSTIN API is not configured on the server.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Get GSTIN
|--------------------------------------------------------------------------
*/

$gstin = strtoupper(
    trim($_POST['gstin'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Validate GSTIN
|--------------------------------------------------------------------------
*/

$gstinPattern =
    '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

if ($gstin === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a GSTIN.'
    ]);

    exit;
}

if (!preg_match($gstinPattern, $gstin)) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid GSTIN format. Please check the GSTIN.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| API URL
|--------------------------------------------------------------------------
*/

$url =
    'https://www.gstinapi.in/v1/gstin/' .
    rawurlencode($gstin);

/*
|--------------------------------------------------------------------------
| Retry Settings
|--------------------------------------------------------------------------
*/

$maxAttempts = 3;

$lastResponse = null;
$lastHttpCode = 0;
$lastCurlError = '';

/*
|--------------------------------------------------------------------------
| API Request
|--------------------------------------------------------------------------
*/

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

    $ch = curl_init($url);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPGET => true,

        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'Accept: application/json'
        ],

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_TIMEOUT => 30,

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_SSL_VERIFYHOST => 2

    ]);

    $response = curl_exec($ch);

    $curlError =
        curl_error($ch);

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    curl_close($ch);

    $lastResponse = $response;
    $lastHttpCode = $httpCode;
    $lastCurlError = $curlError;

    /*
    |--------------------------------------------------------------------------
    | Retry ONLY 429 and 502
    |--------------------------------------------------------------------------
    */

    if (
        ($httpCode === 429 || $httpCode === 502)
        &&
        $attempt < $maxAttempts
    ) {

        /*
        | Attempt 1 -> wait 1 sec
        | Attempt 2 -> wait 2 sec
        */

        sleep(
            2 ** ($attempt - 1)
        );

        continue;
    }

    break;
}

/*
|--------------------------------------------------------------------------
| Connection Error
|--------------------------------------------------------------------------
*/

if ($lastResponse === false || $lastResponse === null) {

    error_log(
        'GSTIN API cURL error: ' .
        $lastCurlError
    );

    echo json_encode([
        'success' => false,
        'message' =>
            'Unable to connect to the GST verification service.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Decode API Response
|--------------------------------------------------------------------------
*/

$data =
    json_decode(
        $lastResponse,
        true
    );

if (!is_array($data)) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid response received from GST verification service.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| HTTP 200
|--------------------------------------------------------------------------
*/

if ($lastHttpCode === 200) {

    if (
        !isset($data['success']) ||
        $data['success'] !== true ||
        !isset($data['data'])
    ) {

        echo json_encode([
            'success' => false,
            'message' =>
                $data['error'] ??
                'GSTIN verification failed.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Log Remaining Credits
    |--------------------------------------------------------------------------
    */

    if (
        isset($data['credits_remaining'])
    ) {

        error_log(
            'GSTIN API credits remaining: ' .
            $data['credits_remaining'] .
            ' | GSTIN: ' .
            $gstin
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Save Verified GSTIN in Session
    |--------------------------------------------------------------------------
    */

    $_SESSION['gstin_verified'] = [

        'gstin' => $gstin,

        'data' => $data['data'],

        'credits_remaining' =>
            $data['credits_remaining'] ?? null,

        'verified_at' => time()

    ];

    /*
    |--------------------------------------------------------------------------
    | Return Success
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'GSTIN verified successfully.',

        'credits_remaining' =>
            $data['credits_remaining'] ?? null,

        'data' =>
            $data['data']

    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Handle Errors
|--------------------------------------------------------------------------
*/

switch ($lastHttpCode) {

    case 400:

        $message =
            'Invalid GSTIN format. Please check the GSTIN.';

        break;


    case 401:

        $message =
            'GST verification service authentication failed. Please contact the administrator.';

        break;


    case 402:

        $message =
            'GST verification credits are exhausted. Please contact the administrator.';

        break;


    case 403:

        $message =
            'GST verification account is currently deactivated. Please contact the administrator.';

        break;


    case 404:

        $message =
            'This GSTIN was not found in the GST database.';

        break;


    case 429:

        $message =
            'GST verification rate limit exceeded. Please try again in a moment.';

        break;


    case 502:

        $message =
            'GST verification service is temporarily unavailable. Please try again shortly.';

        break;


    default:

        $message =
            'GST verification failed. Please try again later.';

        break;
}

/*
|--------------------------------------------------------------------------
| Return Error
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => false,

    'message' => $message,

    'api_error' =>
        $data['error'] ?? null

]);

exit;