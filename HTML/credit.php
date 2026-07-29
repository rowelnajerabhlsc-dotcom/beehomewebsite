<?php
session_start();
include "auth_check.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit - Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/credit.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>
<body>

<?php include "navbar.php"; ?>

<section class="credit-hero">
    <h1>CREDIT OPERATION</h1>
</section>

<section class="credit-operations">
    <div class="credit-header">
        <h2>Credit Operations</h2>
        <p>Our cooperative offers various credit forms to assist members with their financial needs. Download the forms below to apply for loans and other services.</p>
    </div>

    <div class="credit-forms">

    <?php
    $stmt = $conn->prepare("SELECT * FROM credit_forms");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
    ?>

        <div class="form-card">
            <div class="form-image">
                <img src="../IMAGES/credit-img1.png" alt="Form Image">
            </div>

            <div class="form-content">
                <h3><?php echo $row['title']; ?></h3>
                <p><?php echo $row['description']; ?></p>

                <!-- DYNAMIC LINK -->
                <a href="<?php echo $row['link']; ?>" target="_blank" class="download-btn">
                    Click here to apply &#8594;
                </a>

                <!-- ✅ EDIT BUTTON (ONLY ACCOUNTING + MANAGER) -->
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 1 || $_SESSION['role'] == 4)) { ?>
                    <br><br>
                    <a href="edit_form?id=<?php echo $row['id']; ?>" class="edit-btn">
                        Edit Link
                    </a>
                <?php } ?>

            </div>
        </div>

    <?php } ?>

    <?php
    $stmt->close();
    ?>

</div>
</section>

<?php include "consumer_assistance_section.php"; ?>

<?php include "footer.php"; ?>

<script>
function toggleMenu() {
    document.getElementById("navLinks").classList.toggle("active");
}
</script>

</body>
</html>