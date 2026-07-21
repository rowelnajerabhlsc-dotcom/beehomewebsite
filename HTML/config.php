<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "kwchy8j4554l";
$password = "Be3home@2026";
$database = "beehome";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ============================================================
   SMTP / mail settings
   ------------------------------------------------------------
   Values are read from environment variables (set in the host
   control panel or a non-committed .env). Falling back to the
   historical values keeps local dev working but the hardcoded
   production App Password should be rotated and replaced.
   ============================================================ */
$mail_config = [
    'host'         => getenv('SMTP_HOST')     ?: 'smtp.gmail.com',
    'port'         => (int)(getenv('SMTP_PORT') ?: 465),
    'username'     => getenv('SMTP_USER')     ?: 'adminstmp@beehome.ph',
    'password'     => getenv('SMTP_PASS')     ?: 'qipizhdiflzyczly',
    'secure'       => getenv('SMTP_SECURE')   ?: 'ssl', // 'tls' or 'ssl'
    'from_email'   => getenv('MAIL_FROM')     ?: 'infoadmin@beehome.ph',
    'from_name'    => getenv('MAIL_FROM_NAME')?: 'Bee Home Labor Multipurpose Cooperative',
];

/* Shared token required to run test_email.php. The .htaccess
   rule below also blocks direct access without it. */
$test_email_token = getenv('TEST_EMAIL_TOKEN') ?: 'dev-only-change-me';
?>