<?php
include "auth_check.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/membership.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
    <link rel="stylesheet" href="../CSS/skeleton.css">
</head>
<body>

<?php include "navbar.php"; ?>

 <!-- MEMBERSHIP PAGE CONTENTS START HERE -->

    <section class="membership-hero">
        <h1>MEMBERSHIP</h1>
    </section>


    <section class="membership-cards">

        <!-- CARD 1 -->
        <div class="member-card">
            <div class="card-bg" style="background-image: url('../IMAGES/home-img2.png');" data-bg="../IMAGES/home-img2.png" class="lazy-bg"></div>

            <div class="card-overlay"></div>

            <div class="card-content">
                <h3>Regular Membership</h3>
                <p>A Regular member is one who has compiled with all the membership requirements and entitled to all rights and privileges of membership.</p>
                <button class="learn-btn">Learn More</button>
            </div>

            <div class="card-expanded">
                <h3>Regular Membership</h3>
                <ul>
                    <li>Must be residing and/or working within the area of operation for at least two (2) years.</li>
                    <li>At least college graduate or with work experience in the field of specialization for at least two years.</li>
                    <li>With good moral character and no derogatory records.</li>
                </ul>
                <button class="close-btn">Close</button>
            </div>
        </div>

        <!-- CARD 2 -->
        <div class="member-card">
            <div class="card-bg" style="background-image: url('../IMAGES/membership-img3.png');" data-bg="../IMAGES/membership-img3.png" class="lazy-bg"></div>

            <div class="card-overlay"></div>

            <div class="card-content">
                <h3>Associate Membership</h3>
                <p>An Associate member is the one who has no voting rights and entitled only to rights and privileges specified in the by-laws.</p>
                <button class="learn-btn">Learn More</button>
            </div>

            <div class="card-expanded">
                <h3>Associate Membership</h3>
                <ul>
                    <li>Must be residing and/or working within the area of operation for at least Six (6) months.</li>
                    <li>At least High school graduate.</li>
                    <li>With good moral character and no derogatory records.</li>
                </ul>
                <button class="close-btn">Close</button>
            </div>
        </div>

    </section>



    <?php include "footer.php"; ?>


<script>
    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }

    const cards = document.querySelectorAll('.member-card');

        cards.forEach(card => {
            const learnBtn = card.querySelector('.learn-btn');
            const closeBtn = card.querySelector('.close-btn');

            learnBtn.addEventListener('click', () => {
                card.classList.add('active');
            });

            closeBtn.addEventListener('click', () => {
                card.classList.remove('active');
            });
        });
</script>

<script src="../JS/lazy-load.js"></script>

</body>
</html>