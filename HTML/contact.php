<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/contact.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/leaflet.css">
    <link rel="stylesheet" href="../CSS/leaflet-routing-machine.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>

<body>

 <!-- NAVIGATION CONTENTS HERE / Dont change the contents under this section -->

<?php include "navbar.php"; ?>

 <!-- CONTACT US PAGE CONTENTS START HERE -->
   <section class="contact-hero">
        <h1>CONTACT US</h1>
    </section>


    <section class="contact">
        <div class="contact-container">

            <!-- LEFT: MAP -->
            <div class="contact-map-wrapper">
                <div class="map-toolbar">
                    <button id="locateBtn" type="button">Use my location</button>
                    <div id="routeInfo" class="route-info">Allow location access to see directions and live ETA.</div>
                </div>
                <div id="map" class="contact-map"></div>
            </div>

        

            <!-- RIGHT: INFO -->
            <div class="contact-info">
                <h2>CONTACT US</h2>
                <h3>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h3>

                <p><strong>Location:</strong><br>
                Unit 203, 2ND Floor, MGC Veranda Building, 31, Gov. Pascual Avenue, Tinajeros, Malabon, Metro Manila</p>

                <p><strong>Message / Call Us</strong></p>
                <p>Facebook: Bee Home Labor Multipurpose Cooperative</p>
                <p>Gmail: info.bhscoop@gmail.com</p>
                <p>Contact No: 0917 588 1203</p>

                <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="contact-logo">
            </div>

        </div>
    </section>


    <section class="faq">
        <h2>FREQUENTLY ASKED QUESTIONS:</h2>

        <div class="faq-item">
            <button class="faq-question">
                What is Bee Home Labor Multipurpose Cooperative?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>Bee Home Labor Multipurpose Cooperative is a manpower service provider that supplies trained and reliable workers to different industries..</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                What services does Bee Home offer?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>We offer Multipurpose services, transport, bills payment, remittance, and other cooperative services.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Where is your office located?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>We are located at Unit 203, 2nd Floor, MGC Veranda Building, 31, Gov. Pascual Avenue, Tinajeros, Malabon City.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                How can I contact Bee Home?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>You may contact us through Facebook, Gmail, or call us at 0917 588 1203.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Do you offer loan services?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>Yes, we offer various loan programs exclusively for members only.</p>
            </div>
        </div>

    </section>




     <?php include "footer.php"; ?>



<script src="../JS/leaflet.js"></script>
<script src="../JS/leaflet-routing-machine.js"></script>
<script>
    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }

    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
        const button = item.querySelector(".faq-question");

        button.addEventListener("click", () => {
            item.classList.toggle("active");
        });
    });

    const officeLocation = [14.6708469, 120.9697452];
    const map = L.map('map').setView(officeLocation, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const officeMarker = L.marker(officeLocation).addTo(map)
        .bindPopup('Bee Home Labor Multipurpose Cooperative');

    let routingControl = null;
    const routeInfo = document.getElementById('routeInfo');
    const locateBtn = document.getElementById('locateBtn');

    function clearRoute() {
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
    }

    function showRoute(userLocation) {
        clearRoute();

        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(userLocation.lat, userLocation.lng),
                L.latLng(officeLocation[0], officeLocation[1])
            ],
            routeWhileDragging: false,
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            createMarker: function() {
                return null;
            },
            lineOptions: {
                styles: [{ color: '#096D2B', weight: 5, opacity: 0.8 }]
            }
        }).addTo(map);

        routingControl.on('routesfound', function(e) {
            const route = e.routes[0];
            const distanceKm = (route.summary.totalDistance / 1000).toFixed(1);
            const durationMinutes = Math.max(1, Math.round(route.summary.totalTime / 60));
            routeInfo.innerHTML = `<strong>Estimated travel:</strong> ${distanceKm} km • ${durationMinutes} min`;
        });
    }

    function locateUser() {
        if (!navigator.geolocation) {
            routeInfo.textContent = 'Geolocation is not supported by this browser.';
            return;
        }

        routeInfo.textContent = 'Finding your location...';

        navigator.geolocation.getCurrentPosition(function(position) {
            const userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            L.marker([userLocation.lat, userLocation.lng]).addTo(map)
                .bindPopup('Your location').openPopup();

            map.setView([userLocation.lat, userLocation.lng], 14);
            showRoute(userLocation);
        }, function() {
            routeInfo.textContent = 'Location access was denied. Please allow it to see live directions.';
        });
    }

    locateBtn.addEventListener('click', locateUser);
</script>

</body>
</html>
