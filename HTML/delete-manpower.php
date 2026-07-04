<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "beehome";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    $sql = "DELETE FROM manpower_requests WHERE id = $id";

    if ($conn->query($sql)) {
        header("Location: manpower-request-logs.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

$conn->close();
?>
