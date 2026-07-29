<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

/* DATABASE CONNECTION */
$host = "localhost:3306";
$user = "kwchy8j4554l";
$password = "Be3home@2026";
$database = "beehome";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* FETCH REQUESTS */
$stmt = $conn->prepare("SELECT * FROM manpower_requests ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manpower Request Logs</title>

<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="stylesheet" href="../CSS/home.css">
<link rel="stylesheet" href="../CSS/manpower-request-logs.css">
<link rel="icon" href="IMAGES/logo.png">

</head>
<body>

<div id="page-content">

    <?php include "navbar.php"; ?>

    <div class="logs-container">

        <h2>Manpower Request Logs</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Position</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Duration</th>
                    <th>Workers Requested</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['company']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['phone']); ?></td>
                    <td><?= htmlspecialchars($row['position']); ?></td>
                    <td><?= htmlspecialchars($row['start_date']); ?></td>
                    <td><?= htmlspecialchars($row['end_date']); ?></td>
                    <td><?= htmlspecialchars($row['duration']); ?></td>
                    <td><?= htmlspecialchars($row['workers_requested']); ?></td>
                    <td><?= htmlspecialchars($row['rate']); ?></td>
                    <td><?= htmlspecialchars($row['total']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td>
                        <?php if (is_manager_or_admin()): ?>
                        <a href="delete-manpower.php?id=<?= (int)$row['id']; ?>"
                           onclick="return confirm('Delete this manpower request?')">
                            <button class="action-btn delete">Delete</button>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>

</div>

<script>
// JavaScript for modals and interactivity would go here
</script>

</body>
</html>

<?php
$conn->close();
?>