<?php
session_start();
include "config.php";

/* ACCESS CONTROL */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 3 && $_SESSION['role'] != 4)) {
    header("Location: home.php");
    exit();
}

/* CHECK ID */
if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$user_id = $_GET['id'];

/* PREVENT SELF DELETE */
if ($user_id == $_SESSION['user_id']) {
    header("Location: records.php");
    exit();
}

/* DELETE USER (CASCADE handles profile) */
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: records.php");
exit();
?>