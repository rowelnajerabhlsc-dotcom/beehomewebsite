
<?php
session_start();
include "config.php";


/*  GET CURRENT LABOR FORM LINK */
$stmt = $conn->prepare("SELECT * FROM labor_form LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();
$stmt->close();
?>  


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labor Operation - Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/labor.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>
<body>

<?php include "navbar.php"; ?>

<section class="contact-hero">
    <h1>LABOR OPERATION</h1>
</section>

<section class="labor-content">
    <div class="labor-wrapper">

        <!-- LEFT SIDE -->
        <div class="labor-text">
            <h2>Our Labor Services</h2>
            <p>MANUFACTURING / INDUSTRIAL / ENGINEERS</p>
            <ul>
                <li>Factory Worker</li>
                <li>Machinist</li>
                <li>Production/Machine Operator</li>
                <li>Production Helper</li>
                <li>Line Technician</li>
                <li>QA Tester</li>
                <li>Lab Aide</li>
                <li>Welder, Electrician</li>
                <li>Aircon Technician</li>
                <li>Mechanical Engineer</li>
                <li>Chemical Engineer</li>
                <li>Production Engineer</li>
                <li>Structural Engineer</li>
                <li>Utility Maintenance</li>
            </ul>

            <br><br>

            <p>WAREHOUSE / LOGISTICS</p>
            <ul>
                <li>Warehouse Man</li>
                <li>Warehouse Checker</li>
                <li>Associate Picker</li>
                <li>Loader</li>
                <li>Storage helper</li>
                <li>Spareparts Custodian</li>
                <li>Logistic Crew</li>
                <li>Forklift Operator</li>
                <li>L300 Driver</li>
                <li>10-Wheeler Driver</li>
                <li>Truck Driver</li>
                <li>Deliver Helper</li>
            </ul>   
        </div>

        <!-- RIGHT SIDE -->
        <div class="labor-images">

            <div class="img-grid">
                <img src="../IMAGES/labor-img1.png">
                <img src="../IMAGES/labor-img2.png">
            </div>

            <div class="flavor-text">
                Our cooperative delivers dependable and skilled manpower to support
                industries and businesses, ensuring efficiency, professionalism,
                and consistent service quality.
            </div>

            <div class="img-grid">
                <img src="../IMAGES/labor-img3.png">
                <img src="../IMAGES/labor-img4.png">
            </div>

            <div class="flavor-text">
                Through training and collaboration, Bee Home empowers its members
                with opportunities while helping partner organizations achieve
                productivity and growth.
            </div>

            <div class="extra-services">
                <div>
                    <p>HEALTH AND SANITATION</p>
                    <ul>
                        <li>Hygiene Officer</li>
                        <li>Laundry Man</li>
                        <li>House Keeping</li>
                        <li>Utility</li>
                    </ul>

                    <br>

                    <p>FOOD PROCESSING</p>
                    <ul>
                        <li>Butcher</li>
                    </ul>
                </div>

                <div>
                    <p>AGRICULTURE</p>
                    <ul>
                        <li>Farm Aide</li>
                        <li>Egg Man</li>
                    </ul>

                    <br><br><br>

                    <p>OFFICE</p>
                    <ul>
                        <li>Data Entry</li>
                        <li>Office Staff</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<!--  BUTTONS -->
<div class="page-buttons">

    <!-- DYNAMIC APPLY LINK -->
    <a href="<?php echo $form['link']; ?>" target="_blank" class="btn-apply">
        Apply Now!
    </a>

    <!--  EDIT BUTTON (RECRUITMENT ONLY) -->
    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 2 || $_SESSION['role'] == 4)) { ?>
        <a href="edit_labor_form" class="edit-btn">Edit Link</a>
    <?php } ?>

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