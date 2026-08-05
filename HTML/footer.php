<link rel="stylesheet" href="/CSS/footer.css">

<footer class="footer">
    <div class="footer-container">

        <!-- LEFT: COMPANY INFO -->
        <div class="footer-left">
            <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="footer-logo">
            <h3>BEE HOUR LABOR MULTIPURPOSE COOPERATIVE</h3>
            <p>UNIT 203, 2ND FLOOR, MGC VERANDA BUILDING, 31, GOV. PASCUAL AVENUE, MALABON, METRO MANILA</p>
        </div>

        <!-- MIDDLE: NAVIGATION -->
        <div class="footer-middle">
            <ul>
                <li><a href="../HTML/home.php">Home</a></li>
                <li><a href="../HTML/about.php">About.php">About Us</a></li>
                <li><a href="../HTML/products.php">Products & Services</a></li>
                <li><a href="../HTML/membership.php">Membership</a></li>
                <li><a href="../HTML/bee-home-cares.php">Bee Home Cares</a></li>
                <li><a href="../HTML/contact.php">Contact Us</a></li>
            </ul>
        </div>

        <!-- RIGHT: CONNECT WITH US -->
        <div class="footer-right">
            <h3>CONNECT WITH US</h3>

            <p>
                <a href="https://www.facebook.com/kabeehome/" target="_blank" rel="noopener" aria-label="Visit Bee Home Labor Multipurpose Cooperative Facebook page">
                    <img src="../IMAGES/logo-fb.png" alt="Facebook icon" class="footer-icon">
                    Bee Home Labor Multipurpose Cooperative
                </a>
            </p>

            <p><img src="../IMAGES/logo-email.png" alt="Gmail icon" class="footer-icon"> infoadmin@beehome.ph</p>
            <p><img src="../IMAGES/logo-phone.png" alt="Phone icon" class="footer-icon"> 0917 588 1203</p>
            <p><img src="../IMAGES/logo-phone.png" alt="Phone icon" class="footer-icon"> (02) 8442 7296</p>

        </div>

    </div>

    <!-- COPYRIGHT NOTICE -->
    <div class="footer-bottom">
        <p>&copy; <span id="current-year"></span> Bee Home Labor Multipurpose Cooperative. All rights reserved.</p>
    </div>
</footer>

<script>
// Update copyright year dynamically
document.getElementById('current-year').textContent = new Date().getFullYear();
</script>