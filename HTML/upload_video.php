<?php
session_start();

// ✅ ONLY ROLE 4
if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    header("Location: property.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $targetDir = "../VIDEO/";

    // sanitize filename
    $targetName = basename($_POST['target']);
    $targetFile = $targetDir . $targetName;

    if (!isset($_FILES["video"])) {
        echo "No file uploaded.";
        exit();
    }

    $file = $_FILES["video"];

    if ($file["error"] != 0) {
        echo "Upload error.";
        exit();
    }

    // check type
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($fileType !== "mp4") {
        echo "Only MP4 files allowed.";
        exit();
    }

    // size limit (50MB)
    if ($file["size"] > 50 * 1024 * 1024) {
        echo "File too large.";
        exit();
    }

    // replace video
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        echo "<script>
            alert('Video updated successfully!');
            window.history.back();
        </script>";
    } else {
        echo "Upload failed.";
    }
}