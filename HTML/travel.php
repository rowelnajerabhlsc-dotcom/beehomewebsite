<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel & Tours - Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/travel.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>

<body>

    <!-- NAVIGATION CONTENTS HERE / Dont change the contents under this section -->

    <?php include "navbar.php"; ?>

    <!--  TRAVEL & TOURS PAGE CONTENTS START HERE -->

    <section class="travel-hero">
        <h1>TRAVEL & TOURS</h1>
    </section>

    <section class="soon-section">
        <div class="soon-container">
            <h2>COMING SOON</h2>
            <p>We are working hard to bring you new services and updates. Stay tuned for exciting services coming soon!
            </p>
        </div>
    </section>


    <section class="travel-video-section">
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

    <!-- FOOTER -->
    <?php include "footer.php"; ?>



    <script>
        function toggleMenu() {
            document.getElementById("navLinks").classList.toggle("active");
        }
    </script>

</body>

</html>