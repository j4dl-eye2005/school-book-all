<?php
// includes/auth.php
// This file protects pages. It checks if someone is logged in.
// It also has small helpers to check the user's ROLE (admin or user).

// Start the session so we can read $_SESSION values.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Work out the correct path back to the root pages (login.php etc).
// Pages inside folders (books/, students/, borrowing/) need "../".
function auth_prefix() {
    $self = $_SERVER['PHP_SELF'];
    if (strpos($self, '/books/') !== false
        || strpos($self, '/students/') !== false
        || strpos($self, '/borrowing/') !== false) {
        return "../";
    }
    return "";
}

// --- Guard 1: must be logged in ---
// Require this at the top of every protected page.
if (!isset($_SESSION['user_id'])) {
    header("Location: " . auth_prefix() . "login.php");
    exit;
}

// Small helper: is the logged-in person an admin?
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// --- Guard 2: must be an ADMIN ---
// Call require_admin() at the top of admin-only pages
// (after this file is required). Regular users get bounced to the dashboard.
function require_admin() {
    if (!is_admin()) {
        header("Location: " . auth_prefix() . "index.php?err="
            . urlencode("You do not have permission to open that page."));
        exit;
    }
}
?>
