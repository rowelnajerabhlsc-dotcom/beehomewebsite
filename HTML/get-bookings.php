<?php
require_once __DIR__ . '/config.php'; // provides $conn (mysqli), starts session

header('Content-Type: application/json');

// ---- Bookings (not cancelled, still relevant from today onward) ----
$sql = "SELECT from_date, until_date
        FROM rent_requests
        WHERE status != 'cancelled'
          AND until_date >= CURDATE()";

$result = $conn->query($sql);

$bookings = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'from'  => $row['from_date'],
            'until' => $row['until_date'],
        ];
    }
} else {
    error_log('get-bookings.php bookings query failed: ' . $conn->error);
}

// ---- Total vehicle capacity (sum of all vehicle quantities) ----
$totalVehicles = 0;
$vehicleResult = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM vehicles");

if ($vehicleResult) {
    $row = $vehicleResult->fetch_assoc();
    $totalVehicles = (int) $row['total'];
} else {
    error_log('get-bookings.php vehicles query failed: ' . $conn->error);
}

$conn->close();

echo json_encode([
    'bookings'      => $bookings,
    'totalVehicles' => $totalVehicles,
]);
exit;