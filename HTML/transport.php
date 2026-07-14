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
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
            <div class="carousel-slide transport-info-text" style="margin: auto;">
              <h3>ROUTES</h3>
              <ul>
                <li>Malabon - Monumento</li>
                <li>MCU - Divisoria</li>
              </ul>
            </div>
            <div class="page-buttons carousel-slide">
              <div id="slide4">
                <h3>BEE A MODERN JEEP RENTER</h3>
                <a href="#hidden-calendar" id="rentSlideBtn" role="button" class="btn-rent"
                  style="margin-bottom: auto;">Rent</a>
              </div>
            </div>
          </div>
          <!-- button removed: progression is now scroll-driven, not click-driven -->
        </div>
        <div class="mountain-right-wrap scroll-element right">
          <div class="mountain-right"><img src="../IMAGES/building.png" alt="Building Image" /></div>
        </div>
        <div id="road"><img src="../IMAGES/road.jpg" id="srcImage" alt="Road Image" /></div>
      </div>
    </section>

    <!------------------------------RENT HERO--------------------------->
    <section class="calendar hidden" id="hidden-calendar">
      <div class="calendar-header">
        <button id="prevBtn">&lt;</button>
        <h2 id="monthYearDisplay"></h2>
        <button id="nextBtn">&gt;</button>
      </div>
      <div class="calendar-grid" id="calendarGrid">
        <!-- Weekday Headers -->
        <div class="day-name">Su</div>
        <div class="day-name">Mo</div>
        <div class="day-name">Tu</div>
        <div class="day-name">We</div>
        <div class="day-name">Th</div>
        <div class="day-name">Fr</div>
        <div class="day-name">Sa</div>
        <!-- Days will be dynamically injected here via JS -->
      </div>
    </section>

    <!-- FORM -->
    <div class="business-form-wrapper hidden" id="hidden-form">
      <h2>Rent Request Form</h2>

      <form action="/submit-rent" method="POST" id="rentRequestForm">

        <div class="form-grid">

          <!-- LEFT SIDE -->
          <div class="form-left">

            <h3>Renter Information</h3>

            <label for="business_name">Name</label>
            <input type="text" id="business_name" name="business_name" placeholder="JUAN B. BEE"
              style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" required>

            <label>Contact Number</label>
            <input type="tel" id="phone" name="phone" value="09" placeholder="0917XXXXXXX" pattern="09[0-9]{9}"
              maxlength="11" required>

            <label>Telephone Number</label>
            <input type="text" name="telephone" placeholder="123-1234">

            <label>Email Address</label>
            <input type="email" name="email" placeholder="example@email.com" required>

            <!-- PRIVACY CHECKBOX -->
            <label>
              <input type="checkbox" name="privacy_agree" required>
              I agree with our
              <span class="privacy-link" onclick="openPrivacyModal()">
                Confidentiality and Data Privacy Clause
              </span>
            </label>

          </div>

          <!-- RIGHT SIDE -->
          <div class="form-right">

            <h3>Rent Details</h3>

            <label for="passengers">Number of Passengers:</label>
            <input type="number" id="passengers" name="passengers" class="passenger-input" value="5" step="1" min="0"
              max="26" required>

            <!-- From Date Field -->
            <label for="fromDate">From:</label>
            <input type="date" id="fromDate" name="fromDate" required>

            <!-- Until Date Field -->
            <label for="untilDate">Until:</label>
            <input type="date" id="untilDate" name="untilDate" required>
          </div>

          <!-- Below Part-->
          <div class="form-below">
            <!-- ================= PICK UP SECTION ================= -->
            <div class="booking-section form-left">
              <h3>Location A: Pick Up</h3>

              <div class="form-group">
                <label>Pin Pick Up Location:</label>
                <div id="mapPickUp" class="map-box"></div>
              </div>

              <div class="form-group">
                <label for="addressPickUp">Pick Up Address:</label>
                <input type="text" id="addressPickUp" name="pickup_address" placeholder="Click or drag pin on map..."
                  readonly required>
              </div>

              <div class="form-group">
                <label for="timePickUp">Pick Up Time:</label>
                <input type="time" id="timePickUp" name="pickup_time" required>
              </div>
            </div>

            <!-- ================= DROP OFF SECTION ================= -->
            <div class="booking-section form-right">
              <h3>Location B: Drop Off</h3>

              <div class="form-group">
                <label>Pin Drop Off Location:</label>
                <div id="mapDropOff" class="map-box"></div>
              </div>

              <div class="form-group">
                <label for="addressDropOff">Drop Off Address:</label>
                <input type="text" id="addressDropOff" name="dropoff_address" placeholder="Click or drag pin on map..."
                  readonly required>
              </div>

              <div class="form-group">
                <label for="timeDropOff">Drop Off Time:</label>
                <input type="time" id="timeDropOff" name="dropoff_time" required>
              </div>
            </div>
          </div>

        </div>

        <button type="submit">Submit Request</button>

      </form>

    </div>

    <!-- ================= MODAL ================= -->
    <!-- ================= SUBMISSION RESULT MODAL ================= -->
    <div id="rentResultModal" class="modal-overlay"
      style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:9999;">
      <div class="modal-box" style="background:#fff; border-radius:8px; padding:24px; max-width:420px; width:90%;">
        <h2 id="rentResultTitle">Request Submitted</h2>
        <div class="modal-content">
          <p id="rentResultMessage"></p>
        </div>
        <button class="close-btn" onclick="closeRentResultModal()">Close</button>
      </div>
    </div>

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

    <div class="page-buttons snap-section">
      <a href="javascript:history.back()" class="btn-back">Back to Services</a>
    </div>

    <section class="contact-cta-section snap-section">
      <div class="contact-cta">
        <p>For more questions, please contact us</p>
        <a href="/contact" class="cta-btn">Click Here</a>
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

      //-----------Calendar + Availability-------------------
      (function () {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const minMonth = today.getMonth();
        const minYear = today.getFullYear();

        let currentMonth = minMonth;
        let currentYear = minYear;

        const monthNames = [
          "January", "February", "March", "April", "May", "June",
          "July", "August", "September", "October", "November", "December"
        ];

        const monthYearDisplay = document.getElementById("monthYearDisplay");
        const calendarGrid = document.getElementById("calendarGrid");
        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");

        const fromInput = document.getElementById('fromDate');
        const untilInput = document.getElementById('untilDate');

        // Map of "YYYY-MM-DD" -> number of bookings covering that date.
        let dateCounts = new Map();
        // Total vehicles available for rent (sum of all vehicle quantities).
        let totalVehicles = 0;

        function toDateKey(year, month, day) {
          const mm = String(month + 1).padStart(2, '0');
          const dd = String(day).padStart(2, '0');
          return `${year}-${mm}-${dd}`;
        }

        // Expand a from/until range (inclusive) into individual YYYY-MM-DD keys.
        function expandRange(fromStr, untilStr) {
          const keys = [];
          const cursor = new Date(fromStr + 'T00:00:00');
          const end = new Date(untilStr + 'T00:00:00');

          while (cursor <= end) {
            keys.push(toDateKey(cursor.getFullYear(), cursor.getMonth(), cursor.getDate()));
            cursor.setDate(cursor.getDate() + 1);
          }

          return keys;
        }

        // A date is fully booked when the number of overlapping bookings
        // has reached (or exceeded) the total number of vehicles available.
        function isFullyBooked(dateKey) {
          if (totalVehicles <= 0) return false; // no fleet data yet / none configured
          return (dateCounts.get(dateKey) || 0) >= totalVehicles;
        }

        function isLowAvailability(dateKey) {
          if (totalVehicles <= 0) return false;
          const remaining = totalVehicles - (dateCounts.get(dateKey) || 0);
          return remaining === 1;
        }

        // True if ANY date within [fromStr, untilStr] is fully booked.
        function isRangeFullyBooked(fromStr, untilStr) {
          return expandRange(fromStr, untilStr).some(isFullyBooked);
        }

        async function loadAvailability() {
          try {
            console.log('[availability] fetching /get-bookings ...');
            const response = await fetch('/get-bookings');
            console.log('[availability] response status:', response.status, response.statusText);
            console.log('[availability] response URL (after any redirects):', response.url);

            if (!response.ok) {
              const bodyText = await response.text();
              console.error('[availability] non-OK response body:', bodyText);
              throw new Error(`Request failed with status ${response.status}`);
            }

            const data = await response.json();
            console.log('[availability] parsed data:', data);
            const counts = new Map();

            (data.bookings || []).forEach(({ from, until }) => {
              expandRange(from, until).forEach(key => {
                counts.set(key, (counts.get(key) || 0) + 1);
              });
            });

            dateCounts = counts;
            totalVehicles = Number(data.totalVehicles) || 0;
          } catch (error) {
            console.warn('[availability] failed to load booking data:', error);
            dateCounts = new Map();
            totalVehicles = 0;
          }
        }

        function updateNavButtons() {
          // Disable "prev" only when viewing the current real-world month/year
          const atMinimum = (currentYear === minYear && currentMonth === minMonth);
          prevBtn.disabled = atMinimum;
          prevBtn.classList.toggle("disabled", atMinimum);
        }

        function renderCalendar(month, year) {
          const dayNames = document.querySelectorAll('.day-name');
          calendarGrid.innerHTML = '';
          dayNames.forEach(day => calendarGrid.appendChild(day));

          monthYearDisplay.textContent = `${monthNames[month]} ${year}`;

          const firstDayIndex = new Date(year, month, 1).getDay();
          const totalDays = new Date(year, month + 1, 0).getDate();

          for (let i = 0; i < firstDayIndex; i++) {
            const emptyDiv = document.createElement("div");
            emptyDiv.classList.add("empty-cell");
            calendarGrid.appendChild(emptyDiv);
          }

          for (let day = 1; day <= totalDays; day++) {
            const dayCell = document.createElement("div");
            dayCell.classList.add("day-cell");
            dayCell.textContent = day;

            const cellDate = new Date(year, month, day);
            const dateKey = toDateKey(year, month, day);
            const fullyBooked = isFullyBooked(dateKey);
            const lowAvailability = isLowAvailability(dateKey);

            if (fullyBooked) {
              dayCell.classList.add("booked");
              dayCell.style.backgroundColor = "#0c8a36";
              dayCell.title = "Fully booked \u2014 no vehicles available";
            } else if (lowAvailability) {
              dayCell.classList.add("low-availability");
              dayCell.style.backgroundColor = "#e6c200";
              dayCell.title = "Only 1 vehicle remaining";
            }

            if (cellDate < today) {
              dayCell.classList.add("past");
            } else {
              dayCell.classList.add("future-active");

              if (cellDate.getTime() === today.getTime()) {
                dayCell.classList.add("today");
              }
            }

            // Calendar is display-only: no click selection, no "You selected" alert.
            // Navigation is handled solely via the prev/next buttons.

            calendarGrid.appendChild(dayCell);
          }

          updateNavButtons();
        }

        prevBtn.addEventListener("click", () => {
          // Block navigating before the current real-world month
          if (currentYear === minYear && currentMonth === minMonth) return;

          currentMonth--;
          if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
          }
          renderCalendar(currentMonth, currentYear);
        });

        nextBtn.addEventListener("click", () => {
          currentMonth++;
          if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
          }
          renderCalendar(currentMonth, currentYear);
        });

        //------------from - until date picker enforcement----------
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const formattedToday = `${yyyy}-${mm}-${dd}`;

        //no past cur date
        fromInput.min = formattedToday;
        untilInput.min = formattedToday;

        fromInput.addEventListener('change', function () {
          if (!this.value) return;

          if (isFullyBooked(this.value)) {
            alert('That date is fully booked. Please choose another date.');
            this.value = '';
            return;
          }

          untilInput.min = this.value;

          // If an "until" date was already picked and the new range now
          // crosses a fully booked date, clear it so the user re-picks.
          if (untilInput.value && isRangeFullyBooked(this.value, untilInput.value)) {
            alert('That date range includes a fully booked date. Please adjust your dates.');
            untilInput.value = '';
          }
        });

        untilInput.addEventListener('change', function () {
          if (!this.value) return;

          if (isFullyBooked(this.value)) {
            alert('That date is fully booked. Please choose another date.');
            this.value = '';
            return;
          }

          fromInput.max = this.value;

          if (fromInput.value && isRangeFullyBooked(fromInput.value, this.value)) {
            alert('That date range includes a fully booked date. Please adjust your dates.');
            this.value = '';
          }
        });

        // Load availability first, then render the calendar so it's accurate from the start.
        const rentForm = document.getElementById('rentRequestForm');

        function openRentResultModal(status, message) {
          const modal = document.getElementById('rentResultModal');
          const title = document.getElementById('rentResultTitle');
          const msg = document.getElementById('rentResultMessage');
          if (!modal || !title || !msg) return;

          title.textContent = status === 'success' ? 'Request Submitted' : 'Submission Error';
          msg.textContent = message;
          msg.style.color = status === 'success' ? '#0c8a36' : '#c0392b';
          modal.style.display = 'flex';
        }

        window.closeRentResultModal = function () {
          const modal = document.getElementById('rentResultModal');
          if (modal) modal.style.display = 'none';
        };

        if (rentForm) {
          rentForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // stay on the page instead of redirecting

            // Trigger native browser validation (required fields, email format, etc.)
            if (!rentForm.checkValidity()) {
              rentForm.reportValidity();
              return;
            }

            const submitBtn = rentForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.textContent = 'Submitting...';
            }

            try {
              const response = await fetch(rentForm.action, {
                method: 'POST',
                body: new FormData(rentForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              });

              const data = await response.json();
              openRentResultModal(data.status, data.message);

              if (data.status === 'success') {
                rentForm.reset();
                // Refresh availability so the calendar/date pickers reflect the new booking right away.
                loadAvailability().then(() => {
                  renderCalendar(currentMonth, currentYear);
                });
              }
            } catch (error) {
              console.error('[rent form] submission failed:', error);
              openRentResultModal('error', 'Sorry, something went wrong submitting your request. Please try again or contact us directly.');
            } finally {
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
              }
            }
          });
        } else {
          console.warn('[rent form] could not find the rent request form on this page');
        }

        loadAvailability().then(() => {
          renderCalendar(currentMonth, currentYear);
        });
      })();

      //-----------Map-------------------
      // Fallback coordinates (Manila, Philippines)
      const defaultLat = 14.5995;
      const defaultLng = 120.9842;

      function createMapPicker(mapElementId, addressInputId, lat, lng) {
        const map = L.map(mapElementId).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        async function reverseGeocode(lat, lng) {
          const addressField = document.getElementById(addressInputId);
          addressField.value = "Fetching address details...";

          try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
            const data = await response.json();
            if (data && data.display_name) {
              addressField.value = data.display_name;
            } else {
              addressField.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }
          } catch (error) {
            addressField.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
          }
        }

        reverseGeocode(lat, lng);

        marker.on('dragend', function (e) {
          const currentPos = marker.getLatLng();
          reverseGeocode(currentPos.lat, currentPos.lng);
        });

        map.on('click', function (e) {
          marker.setLatLng(e.latlng);
          reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
      }

      // Get the user's current location, fall back to default if denied/unavailable
      function getUserLocation() {
        return new Promise((resolve) => {
          if (!navigator.geolocation) {
            resolve({ lat: defaultLat, lng: defaultLng });
            return;
          }

          navigator.geolocation.getCurrentPosition(
            (position) => {
              resolve({
                lat: position.coords.latitude,
                lng: position.coords.longitude
              });
            },
            (error) => {
              console.warn('Geolocation denied or failed, using default location:', error.message);
              resolve({ lat: defaultLat, lng: defaultLng });
            },
            { timeout: 8000 }
          );
        });
      }

      //----------------For Rent Button---------------------
      const link = document.getElementById('toggleLink');
      const section = document.getElementById('hidden-form');
      // 1. Target the new hidden calendar element
      const calendarSection = document.getElementById('hidden-calendar');
      let mapsInitialized = false;

      const rentSlideBtn = document.getElementById('rentSlideBtn');

      if (rentSlideBtn) {
        rentSlideBtn.addEventListener('click', async (e) => {
          e.preventDefault();

          // Reveal the calendar + form the same way the original toggle does,
          // but only if they're currently hidden (avoid re-hiding on repeat clicks).
          if (section.classList.contains('hidden')) {
            section.classList.remove('hidden');
            calendarSection.classList.remove('hidden');

            const isFormVisible = !section.classList.contains('hidden');
            if (!mapsInitialized && isFormVisible) {
              mapsInitialized = true;
              const { lat, lng } = await getUserLocation();
              createMapPicker('mapPickUp', 'addressPickUp', lat, lng);
              createMapPicker('mapDropOff', 'addressDropOff', lat, lng);
            }
          }

          // Now that it's visible, scroll to it.
          calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      }

      //---------max passenger (form)---------------
      const passengerInput = document.getElementById('passengers');

      passengerInput.addEventListener('input', function () {
        // 1. Convert input text to an actual number
        const value = parseInt(this.value, 10);

        // 2. If the user typed a number greater than 26, force it to 26
        if (value > 26) {
          this.value = 26;
        }

        // 3. Optional: Prevent negative numbers if typed manually
        if (value < 0) {
          this.value = 0;
        }
      });
    </script>
  </main>
</body>

</html>