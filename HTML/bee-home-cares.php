<?php
include "auth_check.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/bee-home-cares.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>

<body>

    <?php include "navbar.php"; ?>

    <!-- BEE HOME CARES PAGE CONTENTS START HERE -->

    <section class="hero">
        <div class="hero-text">
            <img src="../IMAGES/bhcares-logo.png" alt="Bee Home Logo" class="hero-logo">
            <h1>BEE HOME CARES</h1>
            <h2>
                A financial assistance given to qualified members in case of hospitalization due to sickness, fire,
                burial,
                death of member-worker and other emergency that the management deem necessary.
            </h2>
        </div>
    </section>


    <section class="cares-section">

        <div class="cares-box">
            <div class="cares-image">
                <img src="../IMAGES/bhcares-img1.png" alt="Hospital Assistance">
            </div>

            <div class="cares-content">
                <h3>Hospitalization Assistance (Members Only)</h3>

                <!-- Bullet Points -->
                <ul class="cares-list">
                    <li>Hospitalization Assistance due to sickness after PHILHEALTH</li>
                    <li>Hospitalization Assistance limited to emergency cases and illness subject for hospital
                        confinement.</li>
                </ul>

                <!-- Supporting Documents -->
                <p class="supporting-title">Supporting Documents:</p>

                <ol class="supporting-list">
                    <li>Approved Bee Home Cares Form</li>
                    <li>Medical Records from hospital</li>
                    <li>Official Receipts in case member with HMO statement of account</li>
                    <li>Photocopy of payroll ATM with signature and Coop ID</li>
                </ol>
            </div>
        </div>


        <div class="cares-box">
            <div class="cares-image">
                <img src="../IMAGES/bhcares-img2.png" alt="Fire Assistance">
            </div>
            <div class="cares-content">
                <h3>Fire Assistance (Members Only)</h3>

                <ul class="cares-list">
                    <li>Bee Home Cares - Fire Victim</li>
                    <li>Household affectedby th fire must be the registered address of the member in ourcooperative
                        master list.</li>
                </ul>

                <p class="supporting-title">Supporting Documents:</p>

                <ol class="supporting-list">
                    <li>Approved Bee Home Cares Form</li>
                    <li>Barangay Certificate as fire victim</li>
                    <li>Photocopy of payroll ATM with signature and Coop ID</li>
                </ol>
            </div>
        </div>

        <div class="cares-box">
            <div class="cares-image">
                <img src="../IMAGES/bhcares-img3.png" alt="Burial Assistance">
            </div>
            <div class="cares-content">
                <h3>Burial Assistance (Members Only / Immediate Family Members)</h3>

                <ul class="cares-list">
                    <li>Bee Home Cares Burial Assistance</li>
                    <li>Single - Mother and Father </li>
                    <li>Married - Spouse and Children</li>
                    <li>Single Parents - Mother, Father, and Child</li>

                </ul>

                <p class="supporting-title">Supporting Documents:</p>

                <ol class="supporting-list">
                    <li>Approved Bee Home Cares Form</li>
                    <li>If Parent - Death Certificate and members birth certificate</li>
                    <li>If Spouse - Death Certificate and Marriage Certificate</li>
                    <li>If Child - Death Certificate and Birth Certificate of the child</li>
                    <li>Photocopy of payroll ATM with signature and Coop ID</li>
                </ol>
            </div>
        </div>

        <div class="cares-box" style="flex-direction: column; justify-content: center; align-items: center; margin: 0;">
            <div class="cares-content" style="padding: 0; margin: 0;">
                <h3 style="margin-bottom:0; margin-top: 10px;">Apply For Assistance Now</h3>
            </div>
            <div class="button-group" style="margin-bottom: 10px;">
                <a href="https://forms.gle/SfAF12JSnV2wfTES8" target="_blank" class="apply-btn">Apply Now</a>
            </div>
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