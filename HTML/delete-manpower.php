<?php
session_start();
include "config.php";
include "permissions.php";

/* ACCESS CONTROL - Require at least Manager role */
require_role(3);

$host = "localhost";
$user = "kwchy8j4554l";
$pass = "Be3home@2026";
$db   = "beehome";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Additional permission check for managers
    $current_user_role = $_SESSION['role'];
    if ($current_user_role == 3) {
        // Managers can delete manpower requests (this is acceptable as it's not user management)
        // No additional restrictions needed for manpower request deletion
    }

    $stmt = $conn->prepare("DELETE FROM manpower_requests WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: /manpower-request-logs");
        exit();
    } else {
        $_SESSION['error'] = "Error deleting record: " . $stmt->error;
        header("Location: /manpower-request-logs");
        exit();
    }

    $stmt->close();
} else {
    header("Location: /manpower-request-logs");
    exit();
}

$conn->close();
?>