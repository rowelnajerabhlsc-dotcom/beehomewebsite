<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page
$current_page = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_page = rtrim($current_page, '/') ?: '/';

// Map URLs to nav items - handle both pretty URLs and direct file paths
$nav_pages = [
    '/' => 'home',
    '/home' => 'home',
    '/home.php' => 'home',
    '/HTML/home.php' => 'home',
    '/index.php' => 'home',
    '/about' => 'about',
    '/HTML/about.php' => 'about',
    '/products' => 'products',
    '/HTML/products.php' => 'products',
    '/manpower_request' => 'manpower',
    '/manpower-request' => 'manpower',
    '/HTML/manpower_request.php' => 'manpower',
    '/HTML/manpower-request.php' => 'manpower',
    '/membership' => 'membership',
    '/HTML/membership.php' => 'membership',
    '/bee-home-cares' => 'cares',
    '/HTML/bee-home-cares.php' => 'cares',
    '/contact' => 'contact',
    '/HTML/contact.php' => 'contact',
    '/profile' => 'profile',
    '/HTML/profile.php' => 'profile',
    '/change_password' => 'profile',
    '/HTML/change_password.php' => 'profile',
    '/records' => 'profile',
    '/HTML/records.php' => 'profile',
    '/manpower-request-logs' => 'profile',
    '/HTML/manpower-request-logs.php' => 'profile',
    '/helpdesk_dashboard' => 'profile',
    '/HTML/helpdesk_dashboard.php' => 'profile',
    '/dashboard' => 'profile',
    '/HTML/dashboard.php' => 'profile',
    '/transport-dashboard' => 'profile',
    '/HTML/transport-dashboard.php' => 'profile',
];

$active_page = $nav_pages[$current_page] ?? '';
$show_dashboard = isset($_SESSION['role']) && $_SESSION['role'] >= 3;
$is_staff = (($_SESSION['role'] ?? 0) === 2);
$is_logged_in = isset($_SESSION['user_id']);
?>

<link rel="stylesheet" href="https://unpkg.com/lenis@1.3.23/dist/lenis.css">
<link rel="stylesheet" href="/CSS/lenis.css">
<script defer src="https://unpkg.com/lenis@1.3.23/dist/lenis.min.js"></script>
<script defer src="/JS/lenis.js"></script>
<script type="module" src="/JS/lenis-snap.js"></script>
<link rel="stylesheet" href="/CSS/scroll-animate.css">
<script defer src="/JS/scroll-animate.js"></script>

<!-- Ethereal Glass background overlay -->
<div class="ethereal-overlay fixed inset-0 pointer-events-none z-20 opacity-50 mix-blend-multiply bg-black/50" aria-hidden="true">
    <div class="absolute inset-0 rounded-[200%] opacity-[0.3] blur-3xl transform -rotate-6 md:translate-x-[300px] md:translate-y-[-200px] bg-gradient-to-br from-[#0b7a34] via-[#26a753] to-[#075423]"></div>
    <div class="absolute inset-0 rounded-[200%] opacity-[0.2] blur-3xl transform -rotate-6 md:translate-x[-300px] md:translate-y[200px] bg-gradient-to-br from-[#0b7a34] via-[#26a753] to-[#075423]"></div>
</div>

<nav class="relative z-50">

    <!-- LOGO -->
    <div class="logo">
        <a href="/">
            <img src="/IMAGES/logo.png" alt="Bee Home Logo">
        </a>
    </div>

    <!-- NAV LINKS -->
    <ul class="nav-links absolute top-6 right-6 flex items-center gap-8 text-white font-[PlusJakartaSans] hidden md:block">
        <li><a href="/" class="<?php echo $active_page === 'home' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">Home</a></li>

        <li class="dropdown">
            <a href="/about" class="<?php echo $active_page === 'about' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">About Us ▾</a>

            <ul class="dropdown-menu absolute right-0 mt-2 w-56 rounded-[calc(2rem-0.375rem)] bg-black/80 backdrop-blur-3xl border border-white/10 p-2 shadow-[inset_0_1px_1px_rgba(255,255,255,0.15)]">
                <li><a href="/about#history" class="block rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-[#096D2B] mb-2">History</a></li>
                <li><a href="/about#mission" class="block rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-[#096D2B] mb-2">Mission, Vision & Core Values</a></li>
                <li><a href="/about#awards" class="block rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-[#096D2B] mb-2">Awards & Recognition</a></li>
                <li><a href="/about#officers" class="block rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-[#096D2B] mb-2">Officers & Committees</a></li>
                <li><a href="/about#community" class="block rounded-full px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-[#096D2B] mb-2">Community</a></li>
            </ul>
        </li>

        <li><a href="/products" class="<?php echo $active_page === 'products' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">Products &
                Services</a></li>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="/manpower_request" class="<?php echo $active_page === 'manpower' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">Manpower
                    Request</a></li>
        <?php endif; ?>

        <!-- Membership -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/membership'
                : '/needlogin'; ?>" class="<?php echo $active_page === 'membership' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">
                Membership
            </a>
        </li>

        <!-- Bee Home Cares -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/bee-home-cares'
                : '/needlogin'; ?>" class="<?php echo $active_page === 'cares' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">
                Bee Home Cares
            </a>
        </li>
        <!-- contact -->
        <li><a href="/contact" class="<?php echo $active_page === 'contact' ? 'underline underline-offset-4 decoration-[#096D2B]' : ''; ?>">Contact Us</a></li>

    </ul>

    <!-- ACCOUNT AREA - Double Bezel Architecture -->
    <div class="account-area absolute top-6 right-0 flex items-center gap-3">

        <?php if ($is_logged_in): ?>

            <div class="account-dropdown relative">
                <a href="#" class="account-link relative flex items-center gap-2 text-white hover:text-[#096D2B] transition-colors duration-500">
                    <?php echo htmlspecialchars($_SESSION['username']); ?> ▾
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>

                <!-- Outer Shell (Double Bezel) -->
                <div class="account-menu absolute right-0 mt-2 w-64 rounded-[2rem] bg-black/80 backdrop-blur-3xl border border-white/10 p-2 shadow-[inset_0_1px_1px_rgba(255,255,255,0.15)] ring-1 ring-black/5 z-50">

                    <div class="space-y-2">

                        <a href="/profile" class="block rounded-full px-4 py-2 text-sm text-white hover:bg-[#e7f5ea] transition-colors duration-300">
                            <span class="font-medium">Profile</span>
                        </a>

                        <a href="/change_password" class="block rounded-full px-4 py-2 text-sm text-white hover:bg-[#e7f5ea] transition-colors duration-300">
                            <span class="font-medium">Change Password</span>
                        </a>

                        <?php if ($show_dashboard): ?>
                            <a href="/dashboard" class="block rounded-full px-4 py-2 text-sm text-white hover:bg-[#e7f5ea] transition-colors duration-300">
                                <span class="font-medium">Dashboard</span>
                            </a>
                        <?php endif; ?>

                        <a href="/manage_capital_share" class="block rounded-full px-4 py-2 text-sm text-[#096D2B] hover:bg-[#F5C233]/10 transition-colors duration-300 mb-1">
                            <span class="font-medium">Capital Share</span>
                        </a>

                        <?php if (($_SESSION['role'] ?? 0) === 2): ?>
                            <a href="/manage_capital_share" class="block rounded-full px-4 py-2 text-sm text-[#096D2B] hover:bg-[#F5C233]/10 transition-colors duration-300 mb-1">
                                <span class="font-medium">Capital Share (Staff)</span>
                            </a>
                        <?php endif; ?>

                        <?php if (($_SESSION['role'] ?? 0) >= 3): ?>
                            <a href="/manage_capital_share" class="block rounded-full px-4 py-2 text-sm text-[#096D2B] hover:bg-[#F5C233]/10 transition-colors duration-300 mb-1">
                                <span class="font-medium">Capital Share (Admin)</span>
                            </a>
                        <?php endif; ?>

                        <a href="/capital_share" class="block rounded-full px-4 py-2 text-sm text-white hover:bg-[#e7f5ea] transition-colors duration-300">
                            <span class="font-medium">My Capital Share</span>
                        </a>

                        <a href="/logout" class="block rounded-full px-4 py-2 text-sm text-[#5a6b5f] hover:bg-[#e7f5ea]/10 transition-colors duration-300">
                            <span class="font-medium">Logout</span>
                        </a>
                    </div>
                </div>
                <!-- /Outer Shell -->

            </div>

        <?php else: ?>

            <a href="/login" class="account-link text-white hover:text-[#096D2B] transition-colors duration-500">Login</a>

        <?php endif; ?>

    </div>

    <!-- HAMBURGER WITH MORPH ANIMATION -->
    <div class="menu-toggle absolute top-6 left-6 flex items-center gap-2 cursor-pointer md:hidden" id="mobileMenuToggle">
        <span class="hamburger-line w-6 h-0.5 bg-white transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]">
            <span></span>
        </span>
        <span class="hamburger-line w-6 h-0.5 bg-white transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]">
            <span></span>
        </span>
        <span class="hamburger-line w-6 h-0.5 bg-white transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]">
            <span></span>
        </span>
    </div>

</nav>

<script>
    // Hamburger morph animation
    const menuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    const accountMenu = document.getElementById('accountMenu');
    const accountToggle = document.getElementById('accountToggle').parentElement;

    if (menuToggle) {
        const spans = menuToggle.querySelectorAll('.hamburger-line span');

        menuToggle.addEventListener('click', function() {
            // Toggle nav links visibility
            navLinks.classList.toggle('hidden');
            navLinks.classList.toggle('block');

            // Hamburger morph to X
            spans.forEach((span, idx) => {
                if (idx === 0) {
                    span.style.transition = 'transform 0.3s ease-[cubic-bezier(0.32,0.72,0,1)]';
                    if (navLinks.classList.contains('block')) {
                        span.style.transform = 'rotate(45deg) translate(5px, 5px)';
                    } else {
                        span.style.transform = 'rotate(0deg) translate(0)';
                    }
                }
                if (idx === 1) {
                    span.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    if (navLinks.classList.contains('block')) {
                        span.style.opacity = '0';
                        span.style.transform = 'translateX(-20px)';
                    } else {
                        span.style.opacity = '1';
                        span.style.transform = 'translateX(0)';
                    }
                }
                if (idx === 2) {
                    span.style.transition = 'transform 0.3s ease-[cubic-bezier(0.32,0.72,0,1)]';
                    if (navLinks.classList.contains('block')) {
                        span.style.transform = 'rotate(-45deg) translate(5px, -5px)';
                    } else {
                        span.style.transform = 'rotate(0deg) translate(0)';
                    }
                }
            });
        });
    }

    // Account dropdown with staggered reveal
    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', function(e) {
            e.preventDefault();
            accountMenu.classList.toggle('visible');
            
            // Staggered reveal animation for menu items
            const items = accountMenu.querySelectorAll('a');
            items.forEach((item, idx) => {
                if (accountMenu.classList.contains('visible')) {
                    setTimeout(() => {
                        item.style.transition = 'all 0.5s ease-[cubic-bezier(0.32,0.72,0,1)]';
                        item.style.transform = 'translateY(0)';
                        item.style.opacity = '1';
                    }, idx * 100);
                } else {
                    item.style.transform = 'translateY(8px)';
                    item.style.opacity = '0';
                }
            });
        });

        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            if (!accountToggle.contains(e.target) && !accountMenu.contains(e.target)) {
                accountMenu.classList.remove('visible');
                const items = accountMenu.querySelectorAll('a');
                items.forEach(item => {
                    item.style.transform = 'translateY(8px)';
                    item.style.opacity = '0';
                });
            }
        });
    }

    // Navbar scroll effect - float glass pill
    let lastScroll = 0;
    const nav = document.querySelector('nav');

    if (nav) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > lastScroll && currentScroll > 100) {
                // Scroll down - hide navbar or make it more compact
                nav.style.transform = 'translateY(-100%)';
            } else {
                // Scroll up - show navbar
                nav.style.transform = 'translateY(0)';
            }
            lastScroll = currentScroll;
        }, { passive: true });
    }
</script>