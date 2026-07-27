<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "permissions.php";

/**
 * UNIVERSAL IMAGE UPLOADER
 */
function handleImageUpload($inputName, $targetPath, $allowedRoles = [4]) {

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        return "unauthorized";
    }

    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== 0) {
        return "no_file";
    }

    $file = $_FILES[$inputName];
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    $allowedTypes = ["jpg", "jpeg", "png"];

    if (!in_array($fileType, $allowedTypes)) {
        return "invalid_type";
    }

    if (move_uploaded_file($file["tmp_name"], $targetPath)) {
        return "success";
    }

    return "error";
}

/**
 * UNIVERSAL FORM RENDER
 */
function renderImageUploadForm($inputName = "image_file") {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 4) {
        echo '
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <input type="file" name="'.$inputName.'" required>
            <button type="submit" class="btn-upload">Upload Image</button>
        </form>
        ';
    }
}
?>