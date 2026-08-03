<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);   // keep off in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

/* ============================================================
   Pull in shared credentials/config.
   config.php already creates $conn (mysqli) and $mail_config.
   Adjust the path below if config.php lives somewhere else
   relative to this file.
   ============================================================ */
require __DIR__ . '/config.php';

/* GET FORM DATA */
$business_name     = $_POST['business_name']    ?? '';
$contact_person    = $_POST['contact_person']   ?? '';
$position          = $_POST['position']         ?? '';
$email             = $_POST['email']            ?? '';
$telephone         = $_POST['telephone']        ?? '';
$fax               = $_POST['fax']              ?? '';
$website           = $_POST['website']          ?? '';

$req_position      = $_POST['req_position']     ?? '';
$number_required   = $_POST['number_required']  ?? 0;
$job_description   = $_POST['job_description']  ?? '';
$assignment_place  = $_POST['assignment_place'] ?? '';

/* INSERT QUERY */
$sql = "INSERT INTO manpower_requests (
    business_name,
    contact_person,
    position,
    email,
    telephone,
    fax,
    website,
    req_position,
    number_required,
    job_description,
    assignment_place
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssssssssiss",
    $business_name,
    $contact_person,
    $position,
    $email,
    $telephone,
    $fax,
    $website,
    $req_position,
    $number_required,
    $job_description,
    $assignment_place
);

/* EXECUTE */
if ($stmt->execute()) {

    $mail = new PHPMailer(true);

    try {

        if ($mail_config['driver'] === 'sendmail') {
            $mail->isSendmail();
        } else {
            $mail->isSMTP();
            $mail->Host       = $mail_config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mail_config['username'];
            $mail->Password   = $mail_config['password'];
            $mail->SMTPSecure = $mail_config['secure'] === 'tls'
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $mail_config['port'];
        }

        $mail->setFrom(
            $mail_config['from_email'],
            $mail_config['from_name']
        );

        $mail->addAddress($email, $contact_person);

        $mail->isHTML(true);
        $mail->Subject = 'Thank You for Your Manpower Request';

        $mail->Body = "
            Dear <b>{$contact_person}</b>,<br><br>

            Thank you for submitting your manpower request to
            <b>Bee Home Labor Multipurpose Cooperative</b>.<br><br>

            We have successfully received your request and our team will review it shortly.<br><br>

            You may view our company profile here:<br><br>

            <a href='https://heyzine.com/flip-book/4ff3b6a16d.html'>
                View Company Profile
            </a><br><br>

            Thank you for choosing BHLMPC.<br><br>

            Best Regards,<br>
            Bee Home Labor Multipurpose Cooperative
        ";

        $mail->send();

    } catch (Exception $e) {
        // Email failed but manpower request was saved
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }

    echo "<script>
        alert('Manpower request submitted successfully!');
        window.location.href = '/manpower_request.php';
    </script>";

} else {
    error_log("DB Insert Error: " . $stmt->error);
    echo "Something went wrong while submitting your request. Please try again later.";
}

$stmt->close();
$conn->close();
