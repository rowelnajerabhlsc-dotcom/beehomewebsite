<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/property.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>
<body>

<?php include "navbar.php"; ?>

<!-- HERO -->
<section class="property-hero">
    <h1>PROPERTY MANAGEMENT</h1>
</section>

<?php
$videoPath = "../VIDEO/propertyManagement.mp4";
include "video_section.php";
?>

<!-- BACK BUTTON -->
<div class="page-buttons">
    <a href="javascript:history.back()" class="btn-back">Back to Services</a>
</div>

<!-- CTA -->
<section class="contact-cta-section">
    <div class="contact-cta">
        <p>For more questions, please contact us</p>
        <a href="../HTML/contact.html" class="cta-btn">Click Here</a>
    </div>
</section>

<?php include "footer.php"; ?>

<script>
function toggleMenu() {
    document.getElementById("navLinks").classList.toggle("active");
}
</script>

</body>
</html>