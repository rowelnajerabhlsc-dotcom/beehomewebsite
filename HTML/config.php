<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================================================
   Load private env-style secrets from outside the web root.
   File Manager -> Home (/home/kwchy8j4554l/secrets.php) — one
   level above public_html, so it's never reachable by URL.
   Safe to skip silently if it doesn't exist yet (e.g. local dev).
   ============================================================ */
$secretsPath = '/home/kwchy8j4554l/secrets.php';
if (file_exists($secretsPath)) {
    require $secretsPath;
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
    // 'smtp' (default) talks to Gmail over the network.
    // 'sendmail' hands the message to the host's local MTA — use this when
    // outbound SMTP (465/587) is blocked by the hosting firewall.
    'driver'       => getenv('MAIL_DRIVER')    ?: 'sendmail',

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

/* ============================================================
   Registration-link mailer
   ------------------------------------------------------------
   Deliberately separate from $mail_config above. That block sends
   as infoadmin@beehome.ph relayed through a Gmail account
   (adminstmp@beehome.ph) and has an unresolved delivery problem.
   This one sends locally, from a real cPanel mailbox on the same
   server, authenticated with that mailbox's own password — not a
   Gmail App Password. Set these via env vars in cPanel; there is
   no working hardcoded fallback on purpose, so a missing env var
   fails loudly instead of silently trying bad credentials.
   ============================================================ */
$reg_mail_config = [
    'host'      => getenv('REG_MAIL_HOST')      ?: 'mail.beehome.ph',
    'port'      => (int)(getenv('REG_MAIL_PORT') ?: 465),
    'username'  => getenv('REG_MAIL_USER')      ?: '',
    'password'  => getenv('REG_MAIL_PASS')      ?: '',
    'secure'    => getenv('REG_MAIL_SECURE')    ?: 'ssl', // 'tls' (587) or 'ssl' (465)
    'from_email'=> getenv('REG_MAIL_FROM')      ?: 'registration@beehome.ph',
    'from_name' => getenv('REG_MAIL_FROM_NAME') ?: 'Bee Home Labor Multipurpose Cooperative',
];

/* ============================================================
   Google Sheets — Help Desk import
   ============================================================ */
$google_service_account_path = getenv('GOOGLE_SERVICE_ACCOUNT_PATH')
    ?: '/home/kwchy8j4554l/google-service-account.json';

$google_sheet_id = getenv('GOOGLE_SHEET_ID')
    ?: '1xgb7YmI2KPWMHQ7Tf-14U1O5U5FsKE4-_yXBGBDn6Y4'; 

$google_sheet_range = getenv('GOOGLE_SHEET_RANGE')
    ?: 'Form Responses 1!A2:N';
?>