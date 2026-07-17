<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="CSS/home.css?v=scroll-snap-mandatory-2">
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="icon" href="IMAGES/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body class="loading">

    <div id="page-loader" class="page-loader" aria-hidden="true">
        <div class="loader-stage">
            <div class="loader-wordmark">
                <span class="loader-piece loader-c" aria-hidden="true">C</span>
                <div class="loader-piece loader-emblem">
                    <img src="IMAGES/emblem.png" alt="Bee Home Cooperative emblem">
                </div>
                <span class="loader-piece loader-o" aria-hidden="true">O</span>
                <span class="loader-piece loader-p" aria-hidden="true">P</span>
            </div>
        </div>
    </div>

    <?php include "HTML/navbar.php"; ?>


    <!-- HOME PAGE CONTENTS START HERE -->
    <!-- CONTENT 1 - WELCOME -->
    <div class="scroll-container">
        <div class="lenis-content">
            <section class="hero">
                <div class="hero-text">

                    <h1>WELCOME TO</h1>

                    <h2>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h2>
                </div>
            </section>

            <!-- CONTENT 2 - SERVICES -->
            <section class="services-intro">
                <div id="service-intro-left" class="scroll-element left dial">
                    <h2>EXPLORE</h2>
                    <h2>OUR PRODUCTS</h2>
                    <div class="dial__wrapper">
                        <div class="dial__track">
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-bowl-rice"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i class="fa-solid fa-jar"></i></span>
                            </div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-bowl-food"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-cart-shopping"></i></span></div>
                        </div>
                        <div class="dial__knob"></div>
                    </div>
                </div>

                <div id="service-intro-right" class="scroll-element right dial">
                    <h2>AND</h2>
                    <h2>OUR SERVICES</h2>
                    <div class="dial__wrapper">
                        <div class="dial__track">
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-hand-holding"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i class="fa-solid fa-bus"></i></span>
                            </div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-house-user"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-map-location-dot"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-credit-card"></i></span></div>
                            <div class="dial__segment"><span class="dial__inner"><i
                                        class="fa-solid fa-money-bill-1-wave"></i></span></div>
                        </div>
                        <div class="dial__knob"></div>
                    </div>

                </div>
            </section>

            <section class="services">
                <div class="services-container">
                    <p class="services-desc">
                        Bee Home Labor Multipurpose Cooperative is dedicated to assisting its members in increasing
                        their
                        financial prosperity
                        by supplying worthwhile goods and services.
                    </p>

                    <div class="services-grid">

                        <a href="HTML/labor.php" class="service-box link-box">
                            <img src="../IMAGES/home-logo1.png" alt="Service 1">
                            <h3>Labor Operation</h3>
                            <p>Providing skilled, well-trained, and disciplined manpower solutions across diverse
                                industries.</p>
                        </a>

                        <a href="HTML/credit.php" class="service-box link-box">
                            <img src="../IMAGES/home-logo2.png" alt="Service 2">
                            <h3>Credit Operation</h3>
                            <p>Delivering accessible loan programs and financial support to help members achieve their
                                goals. (MEMBERS ONLY)</p>
                        </a>

                        <a href="HTML/transport.php" class="service-box link-box">
                            <img src="../IMAGES/home-logo3.png" alt="Service 3">
                            <h3>Transport Operation</h3>
                            <p>Providing safe, reliable, and efficient transportation services for passengers.</p>
                        </a>

                        <a href="HTML/products.php" class="service-box link-box">
                            <img src="../IMAGES/home-logo4.png" alt="Service 4">
                            <h3>More Services</h3>
                            <p>Discover more services offered by Bee Home.</p>
                        </a>

                    </div>

                </div>
            </section>

            <!-- CONTENT 3 - BEE A MEMBER -->
            <section class="community">

                <!-- LEFT SIDE -->
                <div class="community-left" data-animate="slide-left">
                    <h2 style="font-size: 30px; text-align: center;">
                        ARE YOU LOOKING FOR LABOR EMPLOYMENT?
                    </h2>
                    <p>
                        Join a trusted cooperative that connects skilled workers with
                        meaningful opportunities across different industries.
                    </p>
                    <a href="labor" class="community-btn-left">APPLY FOR WORK</a>
                </div>
                <!-- RIGHT SIDE -->
                <div class="community-content" data-animate="slide-right">
                    <h2 style="font-size: 30px; text-align: center;">
                        BEE ONE OF US!</h2>
                    <p>
                        Secure opportunities, grow with a trusted cooperative,
                        and build a better future together, we thrive.
                    </p>
                    <a href="membership" class="community-btn">BEE A MEMBER</a>
                </div>

            </section>
            <!-- MANPOWER REQUEST -->
            <section class="manpower-section">

                <div class="manpower-container" data-animate="fade-in">
                    <h2>Manpower Request</h2>

                    <p>
                        We are happy to assist you with the manpower you need.
                        Click the manpower request button below and we will be proud
                        to provide the best and qualified Member workers for your company.
                    </p>

                    <a href="HTML/manpower_request.php" class="manpower-btn">
                        MANPOWER REQUEST
                    </a>
                </div>

            </section>

            <!-- CONTENT 4 - ABOUT US -->
            <section class="about">
                <div class="about-container">
                    <h2 data-animate="fade-in">ABOUT US</h2>

                    <p style="font-size: 22px; text-align: justify;" data-animate="slide-left">
                        <strong>Bee Home Labor Multipurpose Cooperative (BHLMPC)</strong> is a registered, versatile
                        cooperative
                        dedicated to driving community growth and uplifting our members' socio-economic well-being. As a
                        dynamic, <strong>multipurpose organization</strong>, we operate a diverse ecosystem of services
                        engineered to
                        meet a wide range of business and community needs. Our comprehensive portfolio spans
                        <strong>modern</strong> essential public utility <strong>transports</strong>,
                        <strong>property</strong>
                        management, diverse <strong>financial</strong> and <strong>credit</strong> solutions,
                        <strong>retail</strong> distribution, and professional <strong>labor</strong> services.
                    </p>

                    <p style="font-size: 22px; text-align: justify;" data-animate="slide-right">
                        Looking forward, we <strong>continue to expand</strong> our reach with upcoming services in
                        <strong>travel and tours</strong>,
                        alongside convenient <strong>bills and payment</strong> solutions. Every member-worker is a
                        co-owner of this
                        growing enterprise, united under the shared values of professionalism, integrity, and mutual
                        progress. By continuously bridging new service gaps, BHLMPC remains a proud partner in
                        productivity and sustainable community development.
                    </p>

                    <div class="about-slider" data-animate="fade-in">

                        <div class="slides fade">
                            <img src="../IMAGES/home-img2.png" alt="Slide 1">
                        </div>

                        <div class="slides fade">
                            <img src="../IMAGES/aboutus-img1.png" alt="Slide 2">
                        </div>

                        <div class="slides fade">
                            <img src="../IMAGES/aboutus-img3.png" alt="Slide 3">
                        </div>

                        <div class="slides fade">
                            <img src="../IMAGES/aboutus-img4.png" alt="Slide 4">
                        </div>

                        <!-- Dots -->
                        <div class="slider-dots">
                            <span class="dot" onclick="currentSlide(1)"></span>
                            <span class="dot" onclick="currentSlide(2)"></span>
                            <span class="dot" onclick="currentSlide(3)"></span>
                            <span class="dot" onclick="currentSlide(4)"></span>
                        </div>

                    </div>
                </div>
            </section>

            <!-- CONTENT 5 - GROWING COMMUNITY -->
            <section class="hero-two" data-no-snap>
                <div class="hero-two-content" data-animate="fade-in">
                    <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="hero-two-logo">

                    <h2>THE GROWING COMMUNITY OF</h2>
                    <h1>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h1>

                    <p>
                        A growing cooperative committed to empowering members through collaboration,
                        sustainable livelihoods, and community development.
                    </p>

                    <a href="../HTML/about.php" class="hero-btn">See More</a>
                </div>
            </section>


            <!-- CONTENT 6 - AFFILIATIORS -->
            <section class="affiliation" data-no-snap>
                <div class="affiliation-container">

                    <div class="affiliation-wrapper">

                        <!-- LEFT SIDE -->
                        <div class="affiliation-left" data-animate="slide-left">
                            <h2>Proud Member of</h2>
                            <div class="affiliation-logos">
                                <img src="../IMAGES/oc-img.png" alt="Org 1">
                                <img src="../IMAGES/ocb-img.png" alt="Org 2">
                                <img src="../IMAGES/logo-cmcdc.png" alt="CMCDC">
                            </div>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="affiliation-right" data-animate="slide-right">
                            <h2>Affiliation</h2>
                            <div class="proud-logos">
                                <img src="../IMAGES/logo-cda.png" alt="CDA">
                                <img src="../IMAGES/logo-dole.png" alt="DOLE">
                                <img src="../IMAGES/logo-bir.png" alt="BIR">
                                <img src="../IMAGES/logo-dot.png" alt="DOT">
                                <img src="../IMAGES/logo-otc.png" alt="OTC">
                                <img src="../IMAGES/logo-ltfrb.png" alt="LTFRB">
                                <img src="../IMAGES/LOGO-PESO.png" alt="PESO">
                            </div>
                        </div>

                    </div>

                </div>
            </section>
            <!-- FOOTER -->
            <?php include "HTML/footer.php"; ?>
        </div>
    </div>



    <script>
        // Lenis is now initialized sitewide via HTML/navbar.php -> JS/lenis.js
        // (which auto-detects this page's .scroll-container / .lenis-content
        // and exposes window.lenis). No per-page init needed here anymore.

        function toggleMenu() {
            document.getElementById("navLinks").classList.toggle("active");
        }

        let slideIndex = 0;
        showSlides();

        function showSlides() {
            let slides = document.getElementsByClassName("slides");
            let dots = document.getElementsByClassName("dot");

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }

            slideIndex++;
            if (slideIndex > slides.length) { slideIndex = 1 }

            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active");
            }

            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].classList.add("active");

            setTimeout(showSlides, 4000);
        }

        function currentSlide(n) {
            slideIndex = n - 1;
            showSlides();
        }

        //fade out transition of section when scrolling down
        const scrollContainer = document.querySelector('.scroll-container');
        const hero = document.querySelector('.hero');
        const servIntro = document.querySelector('.services-intro');
        const fadeDistance = window.innerHeight;
        const nextSection = document.querySelector('.services'); // the section right after services-intro

        // Cache the offset once (and on resize) instead of calling
        // getBoundingClientRect() on every scroll frame — Lenis fires scroll
        // events every animation frame, so repeated layout reads here were
        // the main cause of the sluggish feel.
        function measureNextSectionOffset() {
            const containerRect = scrollContainer.getBoundingClientRect();
            const nextRect = nextSection.getBoundingClientRect();
            // distance from the top of the scrollable content to nextSection,
            // independent of current scroll position
            return (nextRect.top - containerRect.top) + scrollContainer.scrollTop;
        }
        let nextSectionOffsetTop = measureNextSectionOffset();
        window.addEventListener('resize', () => {
            nextSectionOffsetTop = measureNextSectionOffset();
        });

        scrollContainer.addEventListener('scroll', () => {
            const fadeFactor = 0.6;
            const scrollTop = scrollContainer.scrollTop;

            // Hero fade
            const heroOpacity = 1 - Math.min(scrollTop / (fadeDistance * fadeFactor), 1);
            hero.style.opacity = heroOpacity;
            hero.style.pointerEvents = heroOpacity <= 0.01 ? 'none' : 'auto';

            // Services-intro fades out as .services approaches/covers it
            const nextTopRelativeToContainer = nextSectionOffsetTop - scrollTop;

            const introOpacity = Math.max(0, Math.min(1, nextTopRelativeToContainer / (fadeDistance * fadeFactor)));
            servIntro.style.opacity = introOpacity;
            servIntro.style.pointerEvents = introOpacity <= 0.01 ? 'none' : 'auto';

        }, { passive: true });

        //Services Dial Menu 
        function initDial(container) {
            const track = container.querySelector('.dial__track');
            const knob = container.querySelector('.dial__knob');
            const segments = container.querySelectorAll('.dial__segment');
            const icons = container.querySelectorAll('.dial__segment i');

            let dragging = false;
            let startAngle = 0;
            let currentRot = 0;
            const STEP_DEG = 360 / segments.length;
            const AUTO_SPEED = 0.15;
            const RADIUS = 120;

            segments.forEach((seg, i) => {
                const angle = i * STEP_DEG;
                seg.style.transform = `rotate(${angle}deg) translate(0, -${RADIUS}px) rotate(${-angle}deg)`;
            });

            const baseAngles = Array.from(segments).map((_, i) => i * STEP_DEG);

            function getAngle(e) {
                const rect = track.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                const x = e.clientX ?? e.touches[0].clientX;
                const y = e.clientY ?? e.touches[0].clientY;
                return Math.atan2(y - cy, x - cx) * (180 / Math.PI);
            }

            function normalize(angle) {
                return ((angle % 360) + 360) % 360;
            }

            function angleDiff(a, b) {
                const diff = Math.abs(normalize(a) - normalize(b));
                return Math.min(diff, 360 - diff);
            }

            function applyRotation() {
                track.style.transform = `rotate(${currentRot}deg)`;

                let closestIndex = -1;
                let closestDist = Infinity;

                baseAngles.forEach((base, i) => {
                    const absoluteAngle = normalize(base + currentRot);
                    const dist = angleDiff(absoluteAngle, 0); // top highlight
                    if (dist < closestDist) {
                        closestDist = dist;
                        closestIndex = i;
                    }
                });

                segments.forEach((seg, i) => {
                    icons[i].style.transform = `rotate(${-currentRot}deg)`;
                    seg.classList.toggle('is-active', i === closestIndex);
                });
            }

            function autoRotate() {
                if (!dragging) {
                    currentRot += AUTO_SPEED;
                    applyRotation();
                }
                requestAnimationFrame(autoRotate);
            }
            requestAnimationFrame(autoRotate);

            knob.addEventListener('mousedown', e => {
                e.preventDefault();
                dragging = true;
                startAngle = getAngle(e);
                knob.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', e => {
                if (!dragging) return;
                const angleDelta = getAngle(e) - startAngle;
                currentRot += angleDelta;
                startAngle = getAngle(e);
                applyRotation();
            });

            document.addEventListener('mouseup', () => {
                dragging = false;
                knob.style.cursor = 'grab';
            });

            knob.addEventListener('touchstart', e => {
                e.preventDefault();
                dragging = true;
                startAngle = getAngle(e.touches[0]);
            });

            document.addEventListener('touchmove', e => {
                if (!dragging) return;
                const angleDelta = getAngle(e.touches[0]) - startAngle;
                currentRot += angleDelta;
                startAngle = getAngle(e.touches[0]);
                applyRotation();
            });

            document.addEventListener('touchend', () => {
                dragging = false;
            });
        }

        // Initialize every dial on the page
        document.querySelectorAll('.dial').forEach(initDial);

        //scroll animation for elements
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Adds class when scrolling DOWN into view
                    entry.target.classList.add('visible');
                } else {
                    // Removes class when scrolling UP out of view
                    entry.target.classList.remove('visible');
                }
            });
        }, {
            threshold: 0.1 // Triggers when 10% of the element is visible
        });

        // Target all elements you want to animate
        document.querySelectorAll('.scroll-element').forEach(el => observer.observe(el));

    </script>

    <!-- Section snapping is now handled sitewide via HTML/navbar.php ->
         JS/lenis-snap.js, which auto-detects this page's .lenis-content
         sections and offsets for the navbar height. No per-page snap init
         needed here anymore. -->

    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.2/lib/anime.min.js"></script>
    <script src="JS/home-loader.js?v=loader-fragments-2"></script>

</body>

</html>