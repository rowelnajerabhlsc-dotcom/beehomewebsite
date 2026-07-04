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
            <p>We are working hard to bring you new services and updates. Stay tuned for exciting services coming soon!</p>
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
            <a href="../HTML/contact.html" class="cta-btn">Click Here</a>
        </div>
    </section>

     <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">

            <!-- LEFT -->
            <div class="footer-left">
                <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="footer-logo">
                <h3>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h3>
                <p>UNIT 203, 2ND FLOOR, MGC VERANDA BUILDING, 31, GOV. PASCUAL AVENUE, MALABON, METRO MANILA</p>
                <p>(02) 8442 7296</p>
            </div>

            <!-- MIDDLE -->
            <div class="footer-middle">
                <ul>
                    <li><a href="../HTML/home.html">Home</a></li>
                    <li><a href="../HTML/about.html">About Us</a></li>
                    <li><a href="../HTML/products.html">Products & Services</a></li>
                    <li><a href="../HTML/membership.html">Membership</a></li>
                    <li><a href="../HTML/bee-home-cares.html">Bee Home Cares</a></li>
                </ul>
            </div>

            <!-- RIGHT -->
            <div class="footer-right">
                <h3>CONNECT WITH US</h3>
                <p>
                    <a href="https://www.facebook.com/kabeehome/" target="_blank">
                        <img src="../IMAGES/logo-fb.png" alt="FB" class="footer-icon"> 
                        Bee Home Labor Multipurpose Cooperative
                    </a>
                </p>
                <p><img src="../IMAGES/logo-email.png" alt="Gmail" class="footer-icon"> info.bhscoop@gmail.com</p>
                <p><img src="../IMAGES/logo-phone.png" alt="Phone" class="footer-icon"> 0917 588 1203</p>
            </div>

        </div>
    </footer>



<script>
    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }
</script>

</body>
</html>
