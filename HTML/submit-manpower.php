```php
<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

/* DATABASE CONNECTION */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "beehome";

$conn = new mysqli($host, $user, $pass, $db);

/* CHECK CONNECTION */
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* GET FORM DATA */
$business_name     = $_POST['business_name'];
$contact_person    = $_POST['contact_person'];
$position          = $_POST['position'];
$email             = $_POST['email'];
$telephone         = $_POST['telephone'];
$fax               = $_POST['fax'];
$website           = $_POST['website'];

$req_position      = $_POST['req_position'];
$number_required   = $_POST['number_required'];
$job_description   = $_POST['job_description'];
$assignment_place  = $_POST['assignment_place'];

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

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bhojtdrive@gmail.com';
        $mail->Password = 'nfttjhybjbehhozy'; // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(
            'bhojtdrive@gmail.com',
            'Bee Home Labor Multipurpose Cooperative'
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
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
```
