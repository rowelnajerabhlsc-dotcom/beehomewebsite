<?php
require_once __DIR__ . '/config.php'; // provides $conn (mysqli) and starts the session

// Build an absolute URL for redirects so there's no ambiguity from relative
// path resolution (which is what caused the earlier 404 after submission).
function rent_redirect(): void
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'beehome.ph';
    header('Location: ' . $scheme . '://' . $host . '/transport');
    exit;
}

// ---- ONLY ACCEPT POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rent_redirect();
}

// ---- BASIC VALIDATION ----
$required = [
    'business_name', 'phone', 'email', 'passengers',
    'fromDate', 'untilDate', 'pickup_address', 'pickup_time',
    'dropoff_address', 'dropoff_time'
];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['rent_status'] = 'error';
        $_SESSION['rent_message'] = 'Please fill out all required fields.';
        rent_redirect();
    }
}

if (!isset($_POST['privacy_agree'])) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Please agree to the Confidentiality and Data Privacy Clause.';
    rent_redirect();
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Please enter a valid email address.';
    rent_redirect();
}

if (!preg_match('/^09[0-9]{9}$/', $_POST['phone'])) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Please enter a valid contact number.';
    rent_redirect();
}

if (!filter_var($_POST['passengers'], FILTER_VALIDATE_INT) || (int) $_POST['passengers'] < 1 || (int) $_POST['passengers'] > 26) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Number of passengers must be between 1 and 26.';
    rent_redirect();
}

$fromDate  = $_POST['fromDate'];
$untilDate = $_POST['untilDate'];
$today     = date('Y-m-d');

$fromValid  = (bool) DateTime::createFromFormat('Y-m-d', $fromDate);
$untilValid = (bool) DateTime::createFromFormat('Y-m-d', $untilDate);

if (!$fromValid || !$untilValid || $fromDate < $today || $untilDate < $fromDate) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Please select a valid date range.';
    rent_redirect();
}

// ---- CHECK VEHICLE AVAILABILITY (server-side, since JS can be bypassed) ----
$vehicleResult = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM vehicles");
$totalVehicles = $vehicleResult ? (int) $vehicleResult->fetch_assoc()['total'] : 0;

if ($totalVehicles <= 0) {
    error_log('Rent form submission blocked: no vehicles configured in vehicles table.');
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, no vehicles are currently available for rent. Please contact us directly.';
    rent_redirect();
}

$checkStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM rent_requests
     WHERE status != 'cancelled' AND from_date <= ? AND until_date >= ?"
);
$checkStmt->bind_param('ss', $untilDate, $fromDate);
$checkStmt->execute();
$overlapCount = (int) $checkStmt->get_result()->fetch_assoc()['cnt'];
$checkStmt->close();

if ($overlapCount >= $totalVehicles) {
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, all vehicles are already booked for one or more of the selected dates. Please choose different dates.';
    rent_redirect();
}

// ---- BUILD DATA (sanitized/trimmed) ----
$business_name    = trim($_POST['business_name']);
$phone             = trim($_POST['phone']);
$telephone         = trim($_POST['telephone'] ?? '');
$email             = trim($_POST['email']);
$privacy_agree     = 1;
$passengers        = (int) $_POST['passengers'];
$pickup_address    = trim($_POST['pickup_address']);
$pickup_time       = trim($_POST['pickup_time']);
$dropoff_address   = trim($_POST['dropoff_address']);
$dropoff_time      = trim($_POST['dropoff_time']);
$ip_address        = $_SERVER['REMOTE_ADDR'] ?? null;

// ---- SAVE TO DATABASE ----
$sql = "INSERT INTO rent_requests
            (business_name, phone, telephone, email, privacy_agree, passengers,
             from_date, until_date, pickup_address, pickup_time, dropoff_address, dropoff_time, ip_address)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Rent form submission - prepare failed: ' . $conn->error);
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, something went wrong submitting your request. Please try again or contact us directly.';
    rent_redirect();
}

// types: s = string, i = integer
$stmt->bind_param(
    'ssssiisssssss',
    $business_name,
    $phone,
    $telephone,
    $email,
    $privacy_agree,
    $passengers,
    $fromDate,
    $untilDate,
    $pickup_address,
    $pickup_time,
    $dropoff_address,
    $dropoff_time,
    $ip_address
);

if (!$stmt->execute()) {
    error_log('Rent form submission - execute failed: ' . $stmt->error);
    $_SESSION['rent_status'] = 'error';
    $_SESSION['rent_message'] = 'Sorry, something went wrong submitting your request. Please try again or contact us directly.';
    $stmt->close();
    $conn->close();
    rent_redirect();
}

$stmt->close();
$conn->close();

// ---- SUCCESS ----
$_SESSION['rent_status'] = 'success';
$_SESSION['rent_message'] = 'Thank you! Your rent request has been received. We will get back to you soon.';
rent_redirect();