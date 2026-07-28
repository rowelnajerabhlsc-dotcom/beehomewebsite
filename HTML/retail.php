<?php include "auth_check.php"; ?>  

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Retail Operation - Bee Home Labor Multipurpose Cooperative</title>
        <link rel="stylesheet" href="../CSS/retail.css">
        <link rel="stylesheet" href="../CSS/navbar.css">
        <link rel="icon" type="image/png" href="../IMAGES/logo.png">
    </head>
<body>

<?php include "navbar.php"; ?>

 <!--  TRAVEL & TOURS PAGE CONTENTS START HERE -->
   
    <section class="retail-hero">
        <h1>RETAIL OPERATION</h1>
    </section>
   
    <section class="retail-section">
        <div class="retail-container">
            
            <div class="retail-image">
            <img src="../IMAGES/BEE-GASAN.png" alt="Retail Operation">
            </div>

            <div class="retail-content">
            <h3>BEE-GASAN NI BEE HOME</h3>
            <p>
                BEE-Gasan ni BEE HOME is a community service of BEE HOME Labor Multi-Purpose Cooperative 
                that provides affordable rice and basic goods to members, helping support 
                daily needs and promote food security.
            </p>
            </div>

             <div class="page-buttons">
                <a href="https://docs.google.com/forms/d/e/1FAIpQLSey9ZITz1_UrhtN7L9ypH_CSWOMuhxN0H81Xk_j6BWLe7WokA/viewform?usp=publish-editor" target="_blank" class="btn-apply">Order Now!</a>
                <a href="javascript:history.back()" class="btn-back">Back to Services</a>
            </div>

        </div>
    </section>


    <section class="contact-cta-section">
        <div class="contact-cta">
            <p>For more questions, please contact us</p>
            <?php include "footer.php"; ?>
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
