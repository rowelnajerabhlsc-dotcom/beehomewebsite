<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Labor - Bee Home Labor Multipurpose Cooperative</title>
  <link rel="stylesheet" href="../CSS/transport.css" />
  <link rel="stylesheet" href="../CSS/navbar.css">
  <link rel="icon" type="image/png" href="../IMAGES/logo.png" />
  <link rel="stylesheet" href="../CSS/rent_request.css">
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
      <a href="#" id="toggleLink" role="button" class="btn-rent">For Rent</a>
    </div>

    <!-- RENT HERO -->
    <section class="rent-section">

      <div class="rent-container">

        <h2>Rent Request</h2>

        <p>
          We are happy to assist you with your rent request.
          Fill out the form below and we will get back to you as soon as possible.
        </p>

      </div>

    </section>

    <!-- FORM -->
    <div class="business-form-wrapper hidden" id="hidden-form">

      <h2>Rent Request Form</h2>

      <?php if (isset($_SESSION['rent_status'])): ?>
        <div class="form-alert form-alert-<?php echo htmlspecialchars($_SESSION['rent_status']); ?>">
          <?php echo htmlspecialchars($_SESSION['rent_message']); ?>
        </div>
        <?php
        unset($_SESSION['rent_status']);
        unset($_SESSION['rent_message']);
        ?>
      <?php endif; ?>

      <form action="submit-rent.php" method="POST">

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

            <h3>Rent Requirements</h3>

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
            We are committed to protecting the confidentiality, integrity, and security of the information collected
            through this website. Any Company information provided by users shall be treated as confidential and shall
            be collected, processed, stored, and used only for legitimate business purposes and in accordance with
            applicable data privacy and data protection laws.
          </p>

          <p>
            By submitting information through this website, users consent to the collection and processing of their
            personal data for purposes including, but not limited to, responding to inquiries, providing requested
            services, improving website functionality, communicating relevant updates, and complying with legal and
            regulatory requirements.
          </p>

          <p>
            We implement reasonable safeguards to protect personal data against unauthorized access, alteration, or
            misuse.
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




    <section class="contact-cta-section snap-section">
      <div class="contact-cta">
        <p>For more questions, please contact us</p>
        <a href="../HTML/contact.html" class="cta-btn">Click Here</a>
      </div>
    </section>



    <?php include "footer.php"; ?>

    <script defer>
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

      //For Rent Button
      const link = document.getElementById('toggleLink');
      const section = document.getElementById('hidden-form');

      link.addEventListener('click', (e) => {
        e.preventDefault(); // Stops the "#" from changing the URL or jumping the page
        section.classList.toggle('hidden');
      });
    </script>
  </main>
</body>

</html>