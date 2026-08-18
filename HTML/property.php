<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/property.css?v=2">
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
$videoOverlayTitle = "Watch Our Canteen Services";
$videoOverlaySubtitle = "Service Overview Video";
$videoTags = ["🐝 Bee Home Cooperative", "Divine Mercy College"];
?>

<!-- ABOUT THIS SERVICE -->
<section class="about-service-section">
    <div class="about-service-wrapper">

        <!-- LEFT: TEXT CONTENT -->
        <div class="about-service-text">
            <span class="about-badge">About This Service</span>

            <h2 class="about-title">
                Canteen Management,<br>
                <span class="highlight">Serving Every</span><br>
                Student's Needs
            </h2>

            <p>
                Bee Home Cooperative manages the canteen operations of Divine Mercy
                College Foundation Inc., providing students with affordable,
                quality meals and everyday school supplies right on campus.
                Our cooperative members handle daily food preparation, stall
                operations, and inventory to keep the canteen running smoothly.
            </p>

            <p>
                From freshly cooked meals to notebooks, pens, and other essentials,
                we make sure students have quick and convenient access to what
                they need throughout the school day — all while upholding food
                safety, cleanliness, and fair pricing.
            </p>

            <ul class="about-checklist">
                <li><span class="check-icon">&#10003;</span> Freshly prepared, budget-friendly student meals</li>
                <li><span class="check-icon">&#10003;</span> Ready stock of school and writing supplies</li>
                <li><span class="check-icon">&#10003;</span> Trained cooperative staff maintaining hygiene standards</li>
                <li><span class="check-icon">&#10003;</span> Reliable daily service throughout the school year</li>
            </ul>
        </div>

        <!-- RIGHT: VIDEO CARD (shared partial) -->
        <div class="about-service-video">
            <?php include "video_section.php"; ?>
        </div>

    </div>
</section>

<!-- BACK BUTTON -->
<div class="page-buttons">
    <a href="javascript:history.back()" class="btn-back">Back to Services</a>
</div>

<!-- CTA -->
<section class="contact-cta-section">
    <div class="contact-cta">
        <p>For more questions, please contact us</p>
        <a href="/contact" class="cta-btn">Click Here</a>
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