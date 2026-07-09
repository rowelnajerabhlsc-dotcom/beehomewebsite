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

    <section class="animation-wrapper">
      <div class="animation">
        <div class="mountain-left-wrap scroll-element left">
          <div class="mountain-left"><img src="../IMAGES/building.png" alt="Building Image" /></div>
        </div>
        <div class="carousel">
          <div class="carousel-track">
            <div class="carousel-slide">
              <div class="transport-info-image">
                <img src="../IMAGES/transport-img1.png" alt="Transport Image" />
              </div>
            </div>
            <div class="carousel-slide">
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
            <div class="carousel-slide square-red"></div>
          </div>
          <!-- button removed: progression is now scroll-driven, not click-driven -->
        </div>
        <div class="mountain-right-wrap scroll-element right">
          <div class="mountain-right"><img src="../IMAGES/building.png" alt="Building Image" /></div>
        </div>
        <div id="road"><img src="../IMAGES/road.jpg" id="srcImage" alt="Road Image" /></div>
      </div>
    </section>

    <section class="transport-info snap-section">
      <div class="transport-info-container"></div>

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
        const wrapper = document.querySelector('.animation-wrapper');
        const pin = document.querySelector('.animation');
        const scroller = document.querySelector('.scroll-container');
        const left = document.querySelector('.mountain-left');
        const right = document.querySelector('.mountain-right');
        const carousel = document.querySelector('.carousel');
        const track = document.querySelector('.carousel-track');

        if (!wrapper || !pin || !scroller || !left || !right || !carousel || !track) {
          console.warn('[animation] missing elements');
          return;
        }

        const totalItems = track.children.length;
        const step = 100 / totalItems;
        const lastIndex = totalItems - 1;

        // Tune these to control how much of the pinned scroll each phase eats up.
        const PHASE_MOUNTAINS_END = 0.30;   // 0 -> 0.30: mountains slide in
        const PHASE_CAROUSEL_IN_END = 0.50; // 0.30 -> 0.50: whole carousel slides in from the left
        const CAROUSEL_START_OFFSET = 180;
        // 0.50 -> 1.0: carousel content scrubs left-to-right

        function update() {
          const wrapperTop = wrapper.offsetTop;
          const wrapperHeight = wrapper.offsetHeight;
          const pinHeight = pin.offsetHeight; // NEW: actual rendered height of .animation, not assumed
          const scrollY = scroller.scrollTop;

          const totalScrollable = wrapperHeight - pinHeight; // was wrapperHeight - viewportH
          const scrolledIntoWrapper = scrollY - wrapperTop;

          let progress = totalScrollable > 0 ? scrolledIntoWrapper / totalScrollable : 0;
          progress = Math.max(0, Math.min(1, progress));


          const insideSequence = scrolledIntoWrapper > 0 && scrolledIntoWrapper < totalScrollable;
          scroller.style.scrollSnapType = insideSequence ? 'none' : 'y proximity';

          // --- Phase 1: mountains slide in ---
          const mountainProgress = Math.max(0, Math.min(1, progress / PHASE_MOUNTAINS_END));
          left.style.transform = `translateX(${-100 + 100 * mountainProgress}%)`;
          right.style.transform = `translateX(${100 - 100 * mountainProgress}%)`;

          // --- Phase 2: whole carousel slides in from off-screen left ---
          const inRaw = (progress - PHASE_MOUNTAINS_END) / (PHASE_CAROUSEL_IN_END - PHASE_MOUNTAINS_END);
          const inProgress = Math.max(0, Math.min(1, inRaw));
          const startX = -CAROUSEL_START_OFFSET;
          carousel.style.transform = `translateX(${startX + (CAROUSEL_START_OFFSET) * inProgress}%)`;

          // --- Phase 3: carousel content scrubs left-to-right ---
          const contentRaw = (progress - PHASE_CAROUSEL_IN_END) / (1 - PHASE_CAROUSEL_IN_END);
          const contentProgress = Math.max(0, Math.min(1, contentRaw));
          track.style.transform = `translateX(-${(1 - contentProgress) * lastIndex * step}%)`;
        }

        scroller.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
      })();

      const road = document.getElementById('road');
      const srcImg = document.getElementById('srcImage');

      for (let i = 0; i < 20; i++) {
        const clone = srcImg.cloneNode(true);
        road.appendChild(clone);
      }


    </script>
  </main>
</body>

</html>