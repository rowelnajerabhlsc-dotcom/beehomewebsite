<?php
if (isset($_SESSION['user_id'])) {
    $user_id   = $_SESSION['user_id'];
    $user_role = isset($_SESSION['role']) ? (int) $_SESSION['role'] : 1;

    $username  = null;
    $has_photo = false;
    $initials  = null;

    // Wrapped in try/catch: on PHP 8+, calling a method on a closed mysqli
    // connection (e.g. dashboard.php closes $conn before including navbar)
    // throws mysqli_sql_exception rather than returning false. Catch it and
    // degrade to initials-only instead of a fatal error / blank page.
    try {
        if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) {
            $stmt = $conn->prepare("
                SELECT u.username, p.fname, p.lname, p.profile_photo_public_id
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_username, $db_fname, $db_lname, $db_public_id);
            if ($stmt->fetch()) {
                $username  = $db_username;
                $has_photo = !empty($db_public_id);
                $initials  = strtoupper(substr((string) $db_fname, 0, 1) . substr((string) $db_lname, 0, 1));
            }
            $stmt->close();
        }
    } catch (\Throwable $e) {
        // Connection closed or query failed — fall through to defaults below.
    }

    $username = $username ?? ($_SESSION['username'] ?? 'User');
    $initials = $initials ?? strtoupper(substr($username, 0, 1));
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
        <li><a href="/" class="<?php echo $active_page === 'home' ? 'active' : ''; ?>">Home</a></li>

        <li class="dropdown">
            <a href="/about" class="<?php echo $active_page === 'about' ? 'active' : ''; ?>">About Us ▾</a>
            <ul class="dropdown-menu">
                <li><a href="/about#history">History</a></li>
                <li><a href="/about#mission">Mission, Vision & Core Values</a></li>
                <li><a href="/about#awards">Awards & Recognition</a></li>
                <li><a href="/about#community">Community</a></li>
            </ul>
        </li>

        <li><a href="/products" class="<?php echo $active_page === 'products' ? 'active' : ''; ?>">Products & Services</a></li>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="/manpower_request" class="<?php echo $active_page === 'manpower' ? 'active' : ''; ?>">Manpower Request</a></li>
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

            <!-- Account toggle with profile picture/initials -->
            <a href="#" class="account-link" id="accountToggle">
                <span class="account-avatar">
                    <?php if ($has_photo): ?>
                        <img src="/serve_profile_photo?user_id=<?= $user_id ?>" alt="Profile photo"
                             onerror="this.style.display='none';"
                             class="avatar-photo">
                    <?php else: ?>
                        <span class="avatar-initials"><?= $initials; ?></span>
                    <?php endif; ?>
                </span>
                <span class="account-name"><?= htmlspecialchars($username); ?></span>
                <span class="account-arrow">▼</span>
            </a>

            <!-- Dropdown menu -->
            <div class="account-menu" id="accountMenu">

                <a href="/profile">Profile</a>

                <a href="/change_password">Change Password</a>

                <?php if ($user_role >= 3): ?>
                    <a href="/dashboard">Dashboard</a>
                <?php endif; ?>

                <?php if ($user_role >= 2): ?>
                    <a href="/manage_capital_share" class="nav-link">Share Capital</a>
                <?php endif; ?>

                <!-- All logged-in members (any role) can see their own capital share. -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/capital_share" class="nav-link">My Share Capital</a>
                <?php endif; ?>

                <a href="/logout" class="logout-btn">Logout</a>

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

            document.addEventListener("click", function (e) {
                if (!accountToggle.contains(e.target) && !accountMenu.contains(e.target)) {
                    accountMenu.classList.remove("show");
                }
            });
        });
    }
</script>