<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manpower Request</title>

    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/home.css">
    <link rel="stylesheet" href="../CSS/manpower_request.css">

    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>

<body>

<?php include "navbar.php"; ?>

<!-- MANPOWER HERO -->
<section class="manpower-section">

    <div class="manpower-container">

        <h2>Manpower Request</h2>

        <p>
            We are happy to assist you with the manpower you need.
            Fill out the form below and we will provide qualified Filipino workers for your company.
        </p>

    </div>

</section>

<!-- FORM -->
<div class="business-form-wrapper">

    <h2>Manpower Request Form</h2>

    <form action="submit-manpower.php" method="POST">

        <div class="form-grid">

            <!-- LEFT SIDE -->
            <div class="form-left">

                <h3>Business Information</h3>

                <label>Business Name</label>
                <input type="text" name="business_name" required>

                <label>Contact Person</label>
                <input type="text" name="contact_person" required>

                <label>Position</label>
                <input type="text" name="position" required>

                <label>Email Address</label>
                <input type="email" name="email" required>

                <label>Telephone Number</label>
                <input type="text" name="telephone">

                <label>Fax Number</label>
                <input type="text" name="fax">

                <label>Website</label>
                <input type="text" name="website">

                <!-- PRIVACY CHECKBOX -->
                <label>
                    <input type="checkbox" name="privacy_agree" required>
agree with our
                    <span class="privacy-link" onclick="openPrivacyModal()">
                    Confidentiality and Data Privacy Clause
                    </span>
                </label>

            </div>

            <!-- RIGHT SIDE -->
            <div class="form-right">

                <h3>Manpower Requirements</h3>

                <label>Position</label>
                <input type="text" name="req_position" required>

                <label>Number Required</label>
                <input type="number" name="number_required" required>

                <label>Job Description</label>
                <textarea name="job_description"></textarea>

                <label>Place of Assignment</label>
                <textarea name="assignment_place"></textarea>

            </div>

        </div>

        <button type="submit">Submit Request</button>

    </form>

</div>

<!-- ================= MODAL ================= -->
<div id="privacyModal" class="modal-overlay">

    <div class="modal-box">

        <h2>Confidentiality and Data Privacy Clause for Website Information Collection</h2>

        <div class="modal-content">

            <p><strong>Confidentiality and Data Privacy</strong></p>

            <p>
                We are committed to protecting the confidentiality, integrity, and security of the information collected through this website. Any Company information provided by users shall be treated as confidential and shall be collected, processed, stored, and used only for legitimate business purposes and in accordance with applicable data privacy and data protection laws.
            </p>

            <p>
                By submitting information through this website, users consent to the collection and processing of their personal data for purposes including, but not limited to, responding to inquiries, providing requested services, improving website functionality, communicating relevant updates, and complying with legal and regulatory requirements.
            </p>

            <p>
                We implement reasonable safeguards to protect personal data against unauthorized access, alteration, or misuse.
            </p>

            <p>
                Personal information will not be sold or shared except when required by law or with user consent.
            </p>

            <p>
                Data is retained only as long as necessary and securely deleted afterward.
            </p>

            <p>
                Users may request access, correction, or deletion of their data subject to applicable laws.
            </p>

            <p>
                By using this website, users agree to this policy.
            </p>

        </div>

        <button class="close-btn" onclick="closePrivacyModal()">Close</button>

    </div>

</div>

         <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">

            <!-- LEFT -->
            <div class="footer-left">
                <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="footer-logo">
                <h3>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h3>
                <p>UNIT 203, 2ND FLOOR, MGC VERANDA BUILDING, 31, GOV. PASCUAL AVENUE, MALABON, METRO MANILA</p>
                
            </div>

            <!-- MIDDLE -->
            <div class="footer-middle">
                <ul>
                    <li><a href="../HTML/home.php">Home</a></li>
                    <li><a href="../HTML/about.php">About Us</a></li>
                    <li><a href="../HTML/products.php">Products & Services</a></li>
                    <li><a href="../HTML/membership.php">Membership</a></li>
                    <li><a href="../HTML/bee-home-cares.php">Bee Home Cares</a></li>
                    <li><a href="../HTML/contact.php">Contact Us</a></li>
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
                <p><img src="../IMAGES/logo-email.png" alt="Gmail" class="footer-icon"> infoadmin@beehome.ph</p>
                <p><img src="../IMAGES/logo-phone.png" alt="Phone" class="footer-icon"> 0917 588 1203</p>
                <p><img src="../IMAGES/logo-phone.png" alt="Phone" class="footer-icon"> (02) 8442 7296</p>
            </div>

        </div>
    </footer>


    
<!-- ================= SCRIPT ================= -->
<script>
function openPrivacyModal() {
    document.getElementById("privacyModal").style.display = "flex";
}

function closePrivacyModal() {
    document.getElementById("privacyModal").style.display = "none";
}
</script>

</body>
</html>