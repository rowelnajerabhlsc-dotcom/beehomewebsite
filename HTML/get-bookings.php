<?php
require_once __DIR__ . '/config.php'; // provides $conn (mysqli), starts session

header('Content-Type: application/json');

// ---- Approved bookings (these count against vehicle capacity) ----
$stmt = $conn->prepare("SELECT from_date, until_date FROM rent_requests WHERE status = 'approved' AND until_date >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'from'  => $row['from_date'],
            'until' => $row['until_date'],
        ];
    }
    $result->free();
} else {
    error_log('get-bookings.php bookings query failed: ' . $conn->error);
}
$stmt->close();

// ---- Pending bookings (awaiting manager review — shown for info only, don't block capacity) ----
$stmt = $conn->prepare("SELECT from_date, until_date FROM rent_requests WHERE status = 'new' AND until_date >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();

$pendingBookings = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pendingBookings[] = [
            'from'  => $row['from_date'],
            'until' => $row['until_date'],
        ];
    }
    $result->free();
} else {
    error_log('get-bookings.php pending bookings query failed: ' . $conn->error);
}
$stmt->close();

// ---- Total vehicle capacity (sum of all vehicle quantities) ----
$totalVehicles = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM vehicles");
$stmt->execute();
$vehicleResult = $stmt->get_result();

if ($vehicleResult) {
    $row = $vehicleResult->fetch_assoc();
    $totalVehicles = (int) $row['total'];
    $vehicleResult->free();
} else {
    error_log('get-bookings.php vehicles query failed: ' . $conn->error);
}
$stmt->close();

$conn->close();

echo json_encode([
    'bookings'        => $bookings,
    'pendingBookings' => $pendingBookings,
    'totalVehicles'   => $totalVehicles,
]);
exit;