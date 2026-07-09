<?php
session_start();

// ---- CONFIG ----
// Paste the Web app URL you got from Apps Script (ends in /exec)
define('SCRIPT_URL', 'PASTE_YOUR_APPS_SCRIPT_WEB_APP_URL_HERE');

// ---- ONLY ACCEPT POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rent_request.php');
    exit;
}

// ---- BASIC VALIDATION ----
$required = ['business_name', 'contact_person', 'position', 'email', 'req_position', 'number_required'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['rent_status'] = 'error';
        $_SESSION['rent_message'] = 'Please fill out all required fields.';
        header('Location: rent_request.php');
        exit;
    }
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Please enter a valid email address.';
    header('Location: rent_request.php');
    exit;
}

// ---- BUILD PAYLOAD ----
$payload = [
    'business_name' => $_POST['business_name'] ?? '',
    'contact_person' => $_POST['contact_person'] ?? '',
    'position' => $_POST['position'] ?? '',
    'email' => $_POST['email'] ?? '',
    'telephone' => $_POST['telephone'] ?? '',
    'fax' => $_POST['fax'] ?? '',
    'website' => $_POST['website'] ?? '',
    'privacy_agree' => isset($_POST['privacy_agree']) ? '1' : '',
    'req_position' => $_POST['req_position'] ?? '',
    'number_required' => $_POST['number_required'] ?? '',
    'job_description' => $_POST['job_description'] ?? '',
    'assignment_place' => $_POST['assignment_place'] ?? '',
];

// ---- SEND TO GOOGLE SHEETS VIA APPS SCRIPT ----
$ch = curl_init(SCRIPT_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Apps Script responses involve a redirect

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ---- HANDLE RESULT ----
if ($curlError || $httpCode !== 200) {
    error_log('Rent form submission failed: ' . $curlError . ' | HTTP ' . $httpCode . ' | ' . $response);
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, something went wrong submitting your request. Please try again or contact us directly.';
    header('Location: rent_request.php');
    exit;
}

$result = json_decode($response, true);

if (!$result || $result['status'] !== 'success') {
    error_log('Rent form submission - unexpected response: ' . $response);
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, something went wrong submitting your request. Please try again or contact us directly.';
    header('Location: rent_request.php');
    exit;
}

$_SESSION['rent_status'] = 'success';
$_SESSION['rent_message'] = 'Thank you! Your rent request has been received. We will get back to you soon.';
header('Location: rent_request.php');
exit;
