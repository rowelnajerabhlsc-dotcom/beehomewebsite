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
                <div class="orbit-showcase-header">
                    <span class="orbit-badge">Our Offerings</span>
                    <h2>Explore Our Products &amp; Services</h2>
                    <p class="orbit-showcase-desc">
                        Empowering our cooperative community with nourishing farm essentials and reliable household welfare programs.
                    </p>
                </div>

                <div class="orbit-showcase-grid">
                <div id="service-intro-left" class="scroll-element left">
                    <div class="orbit-col">
                        <div class="orbit-header">
                            <h3>Products</h3>
                            <p id="product-desc">Fresh groceries, essentials &amp; more</p>
                        </div>

                        <div class="orbit-circle">
                            <div class="orbit-glow"></div>
                            <div class="orbit-pulse-ring"></div>
                            <div class="orbit-dashed-ring"></div>

                            <div id="product-orbit-ring" class="orbit-ring">

                                <div class="orbit-icon-wrap" style="top:15%; left:50%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('product', 'Fresh Groceries', 'Farm-to-table organic produce & essentials')" onmouseout="resetCenterLabel('product', 'Products', 'Fresh groceries, essentials & more')">
                                        <span class="orbit-tooltip">Fresh Groceries</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:39.5%; left:88.5%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('product', 'Warm Meals', 'Nutritious hot meals cooked daily')" onmouseout="resetCenterLabel('product', 'Products', 'Fresh groceries, essentials & more')">
                                        <span class="orbit-tooltip">Warm Meals</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707m11.314 11.314l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:79.5%; left:74%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('product', 'Preserves', 'Artisanal jams, honey & home preserves')" onmouseout="resetCenterLabel('product', 'Products', 'Fresh groceries, essentials & more')">
                                        <span class="orbit-tooltip">Local Preserves</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:79.5%; left:26%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('product', 'Organic Grains', 'Premium grains, rice, & baking staples')" onmouseout="resetCenterLabel('product', 'Products', 'Fresh groceries, essentials & more')">
                                        <span class="orbit-tooltip">Organic Grains</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:39.5%; left:11.5%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('product', 'Home Supplies', 'Daily necessities & household care')" onmouseout="resetCenterLabel('product', 'Products', 'Fresh groceries, essentials & more')">
                                        <span class="orbit-tooltip">Home Essentials</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="orbit-center">
                                <span id="product-center-title" class="orbit-center-title">Products</span>
                                <span class="orbit-center-divider"></span>
                                <span class="orbit-center-sub">Cooperative</span>
                            </div>
                        </div>

                        <p class="orbit-footer">Hover over icons to inspect details</p>
                    </div>
                </div>

                <div id="service-intro-right" class="scroll-element right">
                    <div class="orbit-col">
                        <div class="orbit-header">
                            <h3>Services</h3>
                            <p id="service-desc">Cooperative assistance, transit &amp; care</p>
                        </div>

                        <div class="orbit-circle">
                            <div class="orbit-glow"></div>
                            <div class="orbit-pulse-ring"></div>
                            <div class="orbit-dashed-ring" style="animation-duration: 50s;"></div>

                            <div id="service-orbit-ring" class="orbit-ring">

                                <div class="orbit-icon-wrap" style="top:15%; left:50%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Savings & Credit', 'Cooperative savings accounts & micro-financing')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Savings &amp; Loans</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:32.5%; left:80.3%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Member Help', 'Welfare benefits, medical aid & guidance')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Member Support</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7h7m-7 0a5 5 0 11-10 0 5 5 0 0110 0z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:67.5%; left:80.3%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Transit Shuttle', 'Safe, accessible community shuttle services')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Transit Shuttle</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:85%; left:50%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Home Care', 'Professional patient & elderly welfare assistance')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Bee Home Cares</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:67.5%; left:19.7%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Branch Offices', 'Locate regional branch & support centers')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Branch Offices</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="orbit-icon-wrap" style="top:32.5%; left:19.7%;">
                                    <div class="orbit-icon" onmouseover="updateCenterLabel('service', 'Savings Card', 'Direct debit, deposits & card payments')" onmouseout="resetCenterLabel('service', 'Services', 'Cooperative assistance, transit & care')">
                                        <span class="orbit-tooltip">Coop Cash Card</span>
                                        <div class="orbit-icon-btn">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="orbit-center">
                                <span id="service-center-title" class="orbit-center-title">Services</span>
                                <span class="orbit-center-divider"></span>
                                <span class="orbit-center-sub">Cooperative</span>
                            </div>
                        </div>

                        <p class="orbit-footer">Hover over icons to inspect details</p>
                    </div>
                </div>
                </div>

                <div class="orbit-showcase-footer">
                    <span>Sustainable</span>
                    <span class="orbit-footer-dot"></span>
                    <span>Organic</span>
                    <span class="orbit-footer-dot"></span>
                    <span>Reliable</span>
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

        //Products & Services orbit widgets
        const productOrbit = document.getElementById('product-orbit-ring');
        const serviceOrbit = document.getElementById('service-orbit-ring');

        function updateCenterLabel(type, title, description) {
            document.getElementById(type + '-center-title').innerText = title;
            const desc = document.getElementById(type + '-desc');
            desc.innerText = description;
            desc.classList.add('orbit-active-desc');
        }

        function resetCenterLabel(type, originalTitle, originalDescription) {
            document.getElementById(type + '-center-title').innerText = originalTitle;
            const desc = document.getElementById(type + '-desc');
            desc.innerText = originalDescription;
            desc.classList.remove('orbit-active-desc');
        }

        // Single source of truth for rotation — the ring's angle and each
        // icon's counter-rotation are computed from the same value every
        // frame, so they can never drift out of sync (which is what was
        // causing icons to end up slanted/upside-down with dual CSS
        // animations).
        function initOrbit(ring) {
            if (!ring) return null;

            const icons = ring.querySelectorAll('.orbit-icon');
            let angle = 0;
            let paused = false;
            let resumeTimer = null;

            // Slower auto-rotate speed on smaller/mobile viewports (deg/sec)
            const speed = () => (window.innerWidth < 1024 ? 360 / 36 : 360 / 24);

            let lastTime = performance.now();
            function tick(now) {
                const dt = (now - lastTime) / 1000;
                lastTime = now;
                if (!paused) {
                    angle = (angle + speed() * dt) % 360;
                    ring.style.transform = `rotate(${angle}deg)`;
                    icons.forEach(icon => {
                        icon.style.transform = `rotate(${-angle}deg)`;
                    });
                }
                requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);

            const circle = ring.closest('.orbit-circle');
            if (circle) {
                circle.addEventListener('mouseenter', () => { paused = true; });
                circle.addEventListener('mouseleave', () => { paused = false; });
            }

            return {
                pauseFor(ms) {
                    paused = true;
                    clearTimeout(resumeTimer);
                    resumeTimer = setTimeout(() => { paused = false; }, ms);
                }
            };
        }

        const productOrbitCtrl = initOrbit(productOrbit);
        const serviceOrbitCtrl = initOrbit(serviceOrbit);

        //scroll animation for elements
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Adds class when scrolling DOWN into view
                    entry.target.classList.add('visible');

                    // Briefly pause the orbit on entry so the icons settle
                    // instead of appearing already mid-spin
                    const ring = entry.target.querySelector('.orbit-ring');
                    if (ring === productOrbit && productOrbitCtrl) productOrbitCtrl.pauseFor(2800);
                    if (ring === serviceOrbit && serviceOrbitCtrl) serviceOrbitCtrl.pauseFor(2800);
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