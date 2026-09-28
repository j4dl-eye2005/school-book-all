<?php
// includes/header.php
// This is the top part of every page: the HTML head and the navbar.
// We use a $base variable so links work from the root and from subfolders.

// If a page did not set $base, default to "" (root folder).
if (!isset($base)) {
    $base = "";
}

// If a page did not set a title, use a default one.
if (!isset($pageTitle)) {
    $pageTitle = "School Book All";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- our one plain CSS file -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body>

<!-- navbar -->
<div class="navbar">
    <a href="<?php echo $base; ?>index.php">Dashboard</a>

    <!-- everyone can browse and borrow books -->
    <a href="<?php echo $base; ?>books/index.php">Books</a>
    <a href="<?php echo $base; ?>borrowing/borrow.php">Borrow a Book</a>

    <?php if (is_admin()): ?>
        <!-- admin-only links -->
        <a href="<?php echo $base; ?>students/index.php">Students</a>
        <a href="<?php echo $base; ?>borrowing/borrowed.php">All Borrowed</a>
    <?php else: ?>
        <!-- users see only their own borrowed books -->
        <a href="<?php echo $base; ?>borrowing/borrowed.php">My Borrowed</a>
    <?php endif; ?>

    <!-- show who is logged in (with role) and a logout link -->
    <span class="nav-user">
        Logged in as:
        <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'guest'); ?>
        (<?php echo htmlspecialchars(isset($_SESSION['role']) ? $_SESSION['role'] : 'user'); ?>)
        | <a href="<?php echo $base; ?>logout.php">Logout</a>
    </span>
</div>

<!-- page content starts here -->
<div class="content">
