<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page
$current_page = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove trailing slash for comparison
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
?>

<link rel="stylesheet" href="https://unpkg.com/lenis@1.3.23/dist/lenis.css">
<link rel="stylesheet" href="/CSS/lenis.css">
<script defer src="https://unpkg.com/lenis@1.3.23/dist/lenis.min.js"></script>
<script defer src="/JS/lenis.js"></script>
<script type="module" src="/JS/lenis-snap.js"></script>
<link rel="stylesheet" href="/CSS/scroll-animate.css">
<script defer src="/JS/scroll-animate.js"></script>

<nav>

    <!-- LOGO -->
    <div class="logo">
        <a href="/">
            <img src="/IMAGES/logo.png" alt="Bee Home Logo">
        </a>
    </div>

    <!-- NAV LINKS -->
    <ul class="nav-links" id="navLinks">

        <li><a href="/" class="<?php echo $active_page === 'home' ? 'active' : ''; ?>">Home</a></li>

        <li class="dropdown">
            <a href="/about" class="<?php echo $active_page === 'about' ? 'active' : ''; ?>">About Us ▾</a>

            <ul class="dropdown-menu">
                <li><a href="/about#history">History</a></li>
                <li><a href="/about#mission">Mission, Vision & Core Values</a></li>
                <li><a href="/about#awards">Awards & Recognition</a></li>
                <li><a href="/about#officers">Officers & Committees</a></li>
                <li><a href="/about#community">Community</a></li>
            </ul>
        </li>

        <li><a href="/products" class="<?php echo $active_page === 'products' ? 'active' : ''; ?>">Products &
                Services</a></li>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="/manpower_request" class="<?php echo $active_page === 'manpower' ? 'active' : ''; ?>">Manpower
                    Request</a></li>
        <?php endif; ?>

        <!-- Membership -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/membership'
                : '/needlogin'; ?>" class="<?php echo $active_page === 'membership' ? 'active' : ''; ?>">
                Membership
            </a>
        </li>

        <!-- Bee Home Cares -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/bee-home-cares'
                : '/needlogin'; ?>" class="<?php echo $active_page === 'cares' ? 'active' : ''; ?>">
                Bee Home Cares
            </a>
        </li>
        <!-- contact -->
        <li><a href="/contact" class="<?php echo $active_page === 'contact' ? 'active' : ''; ?>">Contact Us</a></li>

    </ul>

    <!-- ACCOUNT AREA -->
    <div class="account-area">

        <?php if (isset($_SESSION['user_id'])): ?>

            <div class="account-dropdown">

                <a href="#" class="account-link" id="accountToggle">
                    <?php echo htmlspecialchars($_SESSION['username']); ?> ▾
                </a>

                <div class="account-menu" id="accountMenu">

                    <a href="/profile">Profile</a>

                    <a href="/change_password">Change Password</a>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] >= 3): ?>

                        <a href="/dashboard">Dashboard</a>

                    <?php endif; ?>

                    <a href="/logout">Logout</a>

                </div>

            </div>

        <?php else: ?>

            <a href="/login" class="account-link">Login</a>

        <?php endif; ?>

    </div>

    <!-- HAMBURGER -->
    <div class="menu-toggle" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>

</nav>

<script>
    const accountToggle = document.getElementById("accountToggle");
    const accountMenu = document.getElementById("accountMenu");

    if (accountToggle) {
        accountToggle.addEventListener("click", function (e) {
            e.preventDefault();
            accountMenu.classList.toggle("show");
        });

        document.addEventListener("click", function (e) {
            if (!accountToggle.contains(e.target) && !accountMenu.contains(e.target)) {
                accountMenu.classList.remove("show");
            }
        });
    }

    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }
</script>