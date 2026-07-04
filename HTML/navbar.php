<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav>

    <!-- LOGO -->
    <div class="logo">
        <a href="/home.php">
            <img src="/IMAGES/logo.png" alt="Bee Home Logo">
        </a>
    </div>

    <!-- NAV LINKS -->
    <ul class="nav-links" id="navLinks">

        <li><a href="/home.php">Home</a></li>

        <li class="dropdown">
            <a href="/HTML/about.php">About Us ▾</a>

            <ul class="dropdown-menu">
                <li><a href="/HTML/about.php#history">History</a></li>
                <li><a href="/HTML/about.php#mission">Mission, Vision & Core Values</a></li>
                <li><a href="/HTML/about.php#awards">Awards & Recognition</a></li>
                <li><a href="/HTML/about.php#officers">Officers & Committees</a></li>
                <li><a href="/HTML/about.php#community">Community</a></li>
            </ul>
        </li>

        <li><a href="/HTML/products.php">Products & Services</a></li>

        <li><a href="/HTML/manpower_request.php">Manpower Request</a></li>

        <!-- Membership -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/HTML/membership.php'
                : '/HTML/needlogin.php'; ?>">
                Membership
            </a>
        </li>

        <!-- Bee Home Cares -->
        <li>
            <a href="<?php echo isset($_SESSION['user_id'])
                ? '/HTML/bee-home-cares.php'
                : '/HTML/needlogin.php'; ?>">
                Bee Home Cares
            </a>
        </li>

        <li><a href="/HTML/contact.php">Contact Us</a></li>

    </ul>

    <!-- ACCOUNT AREA -->
    <div class="account-area">

        <?php if (isset($_SESSION['user_id'])): ?>

            <div class="account-dropdown">

                <a href="#" class="account-link" id="accountToggle">
                    <?php echo htmlspecialchars($_SESSION['username']); ?> ▾
                </a>

                <div class="account-menu" id="accountMenu">

                    <a href="/HTML/profile.php">Profile</a>

                    <a href="/HTML/change_password.php">Change Password</a>

                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 3 || $_SESSION['role'] == 4)): ?>

                        <a href="/HTML/records.php">Records</a>

                        <a href="/HTML/manpower-request-logs.php">Manpower Request Logs</a>

                    <?php endif; ?>

                    <a href="/HTML/logout.php">Logout</a>

                </div>

            </div>

        <?php else: ?>

            <a href="/HTML/login.php" class="account-link">Login</a>

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