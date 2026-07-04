<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav>

    <!-- LOGO -->
    <div class="logo">
        <a href="/">
            <img src="/IMAGES/logo.png" alt="Bee Home Logo">
        </a>
    </div>

    <!-- NAV LINKS -->
    <ul class="nav-links" id="navLinks">

        <li><a href="/">Home</a></li>

        <li class="dropdown">
            <a href="/about">About Us ▾</a>

            <ul class="dropdown-menu">
                <li><a href="/about#history">History</a></li>
                <li><a href="/about#mission">Mission, Vision & Core Values</a></li>
                <li><a href="/about#awards">Awards & Recognition</a></li>
                <li><a href="/about#officers">Officers & Committees</a></li>
                <li><a href="/about#community">Community</a></li>
            </ul>
        </li>

        <li><a href="/products">Products & Services</a></li>

        <li><a href="/manpower_request">Manpower Request</a></li>

        <!-- Membership -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/membership'
                : '/needlogin'; ?>">
                Membership
            </a>
        </li>

        <!-- Bee Home Cares -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/bee-home-cares'
                : '/needlogin'; ?>">
                Bee Home Cares
            </a>
        </li>

        <li><a href="/contact">Contact Us</a></li>

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

                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 3 || $_SESSION['role'] == 4)): ?>

                        <a href="/records">Records</a>

                        <a href="/manpower-request-logs">Manpower Request Logs</a>

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
    accountToggle.addEventListener("click", function(e) {
        e.preventDefault();
        accountMenu.classList.toggle("show");
    });

    document.addEventListener("click", function(e) {
        if (!accountToggle.contains(e.target) && !accountMenu.contains(e.target)) {
            accountMenu.classList.remove("show");
        }
    });
}

function toggleMenu() {
    document.getElementById("navLinks").classList.toggle("active");
}
</script>
