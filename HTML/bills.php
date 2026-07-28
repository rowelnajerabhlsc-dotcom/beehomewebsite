<?php
include "auth_check.php";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bills & Payments - Bee Home Labor Multipurpose Cooperative</title>
        <link rel="stylesheet" href="../CSS/bills.css">
        <link rel="stylesheet" href="../CSS/navbar.css">
        <link rel="icon" type="image/png" href="../IMAGES/logo.png">
    </head>
<body>

<?php include "navbar.php"; ?>

 <!--  BILLS & PAYMENTS PAGE CONTENTS START HERE -->
   
    <section class="bills-hero">
        <h1>Bills Payment</h1>
    </section>

     <section class="soon-section">
        <div class="soon-container">
            <h2>COMING SOON</h2>
            <p>We are working hard to bring you new services and updates. Stay tuned for exciting services coming soon!</p>
        </div>
    </section>

    <section class="bills-video-section">
        <div class="video-container">
            <video controls>
                <source src="../VIDEOS/property-video.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </section>

    <div class="page-buttons">
        <a href="javascript:history.back()" class="btn-back">Back to Services</a>
    </div>



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
