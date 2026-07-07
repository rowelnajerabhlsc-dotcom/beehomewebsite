<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Labor - Bee Home Labor Multipurpose Cooperative</title>
  <link rel="stylesheet" href="../CSS/transport.css" />
  <link rel="stylesheet" href="../CSS/navbar.css">
  <link rel="icon" type="image/png" href="../IMAGES/logo.png" />
</head>

<body>
  <!-- NAVIGATION CONTENTS HERE / Dont change the contents under this section -->

  <?php include "navbar.php"; ?>

  <!-- ABOUT US PAGE CONTENTS START HERE -->



  <main class="transport-section scroll-container">

    <section class="contact-hero">
      <h1>TRANSPORT OPERATION</h1>
    </section>
    <?php
    $videoPath = "../VIDEO/transportManagement.mp4";
    include "video_section.php";
    ?>

    <section class="animation">
      <div class="mountain-left-wrap scroll-element left">
        <div class="mountain-left"></div>
      </div>
      <div class="mountain-right-wrap scroll-element right">
        <div class="mountain-right"></div>
      </div>
    </section>
    
    <section class="transport-info snap-section">
      <div class="transport-info-container">

        <!-- IMAGE -->
        <div class="transport-info-image">
          <img src="../IMAGES/transport-img1.png" alt="Transport Image" />
        </div>

        <!-- TEXT -->
        <div class="transport-info-text">
          <h3>BEE HOME MODERN JEEPNEY</h3>
          <p>
            The modern jeepney of Bee Home Labor Multi-Purpose Cooperative
            offers safe, comfortable, and efficient transportation. It features
            air-conditioning, modern safety systems, and eco-friendly technology
            to provide a better commuting experience for passengers.
          </p>
        </div>

      </div>

      <!-- ROUTES SECTION -->
      <div class="transport-routes">
        <h3>ROUTES</h3>
        <ul>
          <li>Malabon - Monumento</li>
          <li>MCU - Divisoria</li>
        </ul>
      </div>

    </section>

    <div class="page-buttons snap-section">
      <a href="javascript:history.back()" class="btn-back">Back to Services</a>
      <a href="for_rent.php" class="btn-rent">For Rent</a>
    </div>




    <section class="contact-cta-section snap-section">
      <div class="contact-cta">
        <p>For more questions, please contact us</p>
        <a href="../HTML/contact.html" class="cta-btn">Click Here</a>
      </div>
    </section>



    <?php include "footer.php"; ?>

    <script>
      function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
      }
      //scroll animaion
      document.addEventListener('DOMContentLoaded', () => {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            console.log(entry.target.className, entry.isIntersecting); // keep temporarily to confirm it fires
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            } else {
              entry.target.classList.remove('visible');
            }
          });
        }, {
          threshold: 0.1
        });

        document.querySelectorAll('.scroll-element').forEach(el => observer.observe(el));
      });
    </script>
  </main>
</body>

</html>