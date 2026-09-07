<?php
// ============================================================
// Add this into navbar.php, inside whichever conditional block
// currently checks $_SESSION['role'] to build the nav links.
// ============================================================

// Staff (role 2) don't have dashboard access, so give them a direct
// link to the capital share encoding page instead of burying it
// behind a dashboard they can't reach.
if (($_SESSION['role'] ?? 0) === 2) {
    echo '<a href="/manage_capital_share" class="nav-link">Capital Share</a>';
}

// Managers/Admins (role 3-4) already have dashboard access — if you'd
// rather they reach this page through the dashboard's tab navigation
// instead of a standalone navbar link, skip this and just add a tab
// there pointing at manage_capital_share.php.
if (($_SESSION['role'] ?? 0) >= 3) {
    echo '<a href="/manage_capital_share" class="nav-link">Capital Share</a>';
}

// All logged-in members (any role) can see their own capital share.
if (isset($_SESSION['user_id'])) {
    echo '<a href="/capital_share" class="nav-link">My Capital Share</a>';
}
