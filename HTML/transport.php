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

  <!-- ABOUT US PAGE CONTENTS START HER E -->



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
      console.log("Inline script is active!");
      function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
      }

      // Mountain slide-in: progress tied to the .animation section's
      // position within the actual scrolling element (the .scroll-container,
      // not the window — the page itself doesn't scroll).
      (function () {
        const section = document.querySelector('.animation');
        const left = document.querySelector('.scroll-element.left .mountain-left');
        const right = document.querySelector('.scroll-element.right .mountain-right');
        const scroller = document.querySelector('.scroll-container');
        if (!section || !left || !right || !scroller) {
          console.warn('[mountains] missing elements', { section, left, right, scroller });
          return;
        }
        console.log('[mountains] scroller =', scroller);

        function update() {
          // sectionTop = section's offset from the top of the scroller's content
          const sectionTop = section.offsetTop;
          const scrollerRect = scroller.getBoundingClientRect();
          // scrollY = how far the scroller has been scrolled
          const scrollY = scroller.scrollTop;
          // viewport-relative top of the section inside the scroller
          const sectionViewportTop = sectionTop - scrollY + scrollerRect.top;
          const vh = scroller.clientHeight;

          // progress 0 -> 1 as section top moves from viewport bottom to middle
          const start = vh;
          const end = vh * 0.5;
          const raw = (start - sectionViewportTop) / (start - end);
          const progress = Math.max(0, Math.min(1, raw));

          const txLeft = -100 + 100 * progress;
          const txRight = 100 - 100 * progress;
          left.style.transform = `translateX(${txLeft}%)`;
          right.style.transform = `translateX(${txRight}%)`;

          console.log('[mountains] progress =', progress.toFixed(3),
            'sectionViewportTop =', sectionViewportTop.toFixed(1),
            'vh =', vh, 'scrollY =', scrollY);
        }

        scroller.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
      })();
    </script>
  </main>
</body>

</html>