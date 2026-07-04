<?php
session_start();
include("config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 1. Check if empty
    if (empty($new_password) || empty($confirm_password)) {
        header("Location: /change_password?error=Please fill in all fields");
        exit();
    }

    // 2. Check if passwords match
    if ($new_password !== $confirm_password) {
        header("Location: /change_password?error=Passwords do not match");
        exit();
    }

    // 3. Validate password rules
    if (
        strlen($new_password) < 8 ||
        !preg_match("/[A-Za-z]/", $new_password) ||
        !preg_match("/[0-9]/", $new_password)
    ) {
        header("Location: /change_password?error=Password must be at least 8 characters and contain both letters and numbers");
        exit();
    }

    // 4. Hash password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 5. Update password in database
    $stmt = $conn->prepare("UPDATE users SET password = ?, temp_password = NULL WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Optional: remove forced password change flag
            if (isset($_SESSION['force_password_change'])) {
                unset($_SESSION['force_password_change']);
            }

            // Redirect to home/dashboard
            header("Location: /?success=Password%20updated%20successfully");
exit();
        } else {
            header("Location: /change_password?error=Failed to update password");
            exit();
        }

        $stmt->close();
    } else {
        header("Location: /change_password?error=Database error");
        exit();
    }

} else {
    // If accessed directly
    header("Location: /change_password");
    exit();
}
?>
