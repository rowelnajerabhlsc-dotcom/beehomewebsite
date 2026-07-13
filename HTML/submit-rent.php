<?php
require_once __DIR__ . '/config.php'; // provides $conn (mysqli) and starts the session

header('Content-Type: application/json');

// Sends a JSON response and stops execution. Used for every outcome now
// that the form submits via fetch() instead of a full page navigation.
function rent_respond(string $status, string $message, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ---- ONLY ACCEPT POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rent_respond('error', 'Invalid request method.', 405);
}

// ---- BASIC VALIDATION ----
$required = [
    'business_name', 'phone', 'email', 'passengers',
    'fromDate', 'untilDate', 'pickup_address', 'pickup_time',
    'dropoff_address', 'dropoff_time'
];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        rent_respond('error', 'Please fill out all required fields.', 422);
    }
}

if (!isset($_POST['privacy_agree'])) {
    rent_respond('error', 'Please agree to the Confidentiality and Data Privacy Clause.', 422);
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    rent_respond('error', 'Please enter a valid email address.', 422);
}

if (!preg_match('/^09[0-9]{9}$/', $_POST['phone'])) {
    rent_respond('error', 'Please enter a valid contact number.', 422);
}

if (!filter_var($_POST['passengers'], FILTER_VALIDATE_INT) || (int) $_POST['passengers'] < 1 || (int) $_POST['passengers'] > 26) {
    rent_respond('error', 'Number of passengers must be between 1 and 26.', 422);
}

$fromDate  = $_POST['fromDate'];
$untilDate = $_POST['untilDate'];
$today     = date('Y-m-d');

$fromValid  = (bool) DateTime::createFromFormat('Y-m-d', $fromDate);
$untilValid = (bool) DateTime::createFromFormat('Y-m-d', $untilDate);

if (!$fromValid || !$untilValid || $fromDate < $today || $untilDate < $fromDate) {
    rent_respond('error', 'Please select a valid date range.', 422);
}

// ---- CHECK VEHICLE AVAILABILITY (server-side, since JS can be bypassed) ----
$vehicleResult = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM vehicles");
$totalVehicles = $vehicleResult ? (int) $vehicleResult->fetch_assoc()['total'] : 0;

if ($totalVehicles <= 0) {
    error_log('Rent form submission blocked: no vehicles configured in vehicles table.');
    rent_respond('error', 'Sorry, no vehicles are currently available for rent. Please contact us directly.', 409);
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
    rent_respond('error', 'Sorry, all vehicles are already booked for one or more of the selected dates. Please choose different dates.', 409);
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
    rent_respond('error', 'Sorry, something went wrong submitting your request. Please try again or contact us directly.', 500);
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
    $stmt->close();
    $conn->close();
    rent_respond('error', 'Sorry, something went wrong submitting your request. Please try again or contact us directly.', 500);
}

$stmt->close();
$conn->close();

// ---- SUCCESS ----
rent_respond('success', 'Thank you! Your rent request has been received. We will get back to you soon.');