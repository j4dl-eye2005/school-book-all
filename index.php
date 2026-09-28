<?php
// index.php  (root)
// The dashboard. Admins see library-wide counts.
// Users see their own borrowing summary.

require_once 'includes/auth.php';    // must be logged in
require_once 'config/connect.php';   // database connection

$error = "";

// Numbers we might show.
$totalBooks = 0;
$totalStudents = 0;
$currentlyBorrowed = 0;
$overdueCount = 0;

// For a normal user: their own counts.
$myBorrowed = 0;
$myOverdue = 0;
$myStudentId = 0;

try {
    if (is_admin()) {
        // Admin sees the whole library.
        $totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
        $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $currentlyBorrowed = $pdo->query(
            "SELECT COUNT(*) FROM borrowed_books WHERE status = 'borrowed'"
        )->fetchColumn();
        $overdueCount = $pdo->query(
            "SELECT COUNT(*) FROM borrowed_books
             WHERE status = 'borrowed' AND due_date < CURDATE()"
        )->fetchColumn();
    } else {
        // Find the student row linked to this user account.
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $myStudentId = $row ? (int)$row['id'] : 0;

        // Total books they can browse.
        $totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();

        if ($myStudentId > 0) {
            // How many books this user currently has out.
            $b = $pdo->prepare(
                "SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND status = 'borrowed'"
            );
            $b->execute([$myStudentId]);
            $myBorrowed = (int)$b->fetchColumn();

            // How many of those are overdue.
            $o = $pdo->prepare(
                "SELECT COUNT(*) FROM borrowed_books
                 WHERE student_id = ? AND status = 'borrowed' AND due_date < CURDATE()"
            );
            $o->execute([$myStudentId]);
            $myOverdue = (int)$o->fetchColumn();
        }
    }
} catch (PDOException $e) {
    $error = "Could not load the dashboard numbers.";
}

$base = "";
$pageTitle = "Dashboard";
require_once 'includes/header.php';
?>

<?php
    // Personalized greeting: admins are greeted as "Admin", everyone else by
    // their own username. (Falls back gracefully if the session lacks a name.)
    $displayName = is_admin()
        ? 'Admin'
        : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Reader');
?>
<h1>Welcome, <?php echo htmlspecialchars($displayName); ?></h1>

<!-- Pop Polygon eyebrow badges: quick at-a-glance context pills -->
<div class="eyebrows">
    <?php if (is_admin()): ?>
        <span class="badge">✦ Admin console</span>
        <span class="badge">Library overview</span>
        <span class="badge"><?php echo date('l, M j'); ?></span>
    <?php else: ?>
        <span class="badge">✦ My library</span>
        <span class="badge">Reader account</span>
        <span class="badge"><?php echo date('l, M j'); ?></span>
    <?php endif; ?>
</div>

<?php if (isset($_GET['err'])): ?>
    <div class="error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (is_admin()): ?>
    <!-- ADMIN dashboard -->
    <div class="box">
        <h2><?php echo (int)$totalBooks; ?></h2>
        Total Books
    </div>
    <div class="box">
        <h2><?php echo (int)$totalStudents; ?></h2>
        Total Students
    </div>
    <div class="box">
        <h2><?php echo (int)$currentlyBorrowed; ?></h2>
        Currently Borrowed
    </div>
    <div class="box">
        <h2><?php echo (int)$overdueCount; ?></h2>
        Overdue Books
    </div>

    <p>
        <a class="btn" href="books/index.php">Manage Books</a>
        <a class="btn" href="students/index.php">Manage Students</a>
        <a class="btn" href="borrowing/borrowed.php">All Borrow Records</a>
    </p>
<?php else: ?>
    <!-- USER dashboard -->
    <div class="box">
        <h2><?php echo (int)$totalBooks; ?></h2>
        Books in Library
    </div>
    <div class="box">
        <h2><?php echo (int)$myBorrowed; ?></h2>
        Books You Have Out
    </div>
    <div class="box">
        <h2><?php echo (int)$myOverdue; ?></h2>
        Your Overdue Books
    </div>

    <p>
        <a class="btn" href="books/index.php">Browse Books</a>
        <a class="btn" href="borrowing/borrow.php">Borrow a Book</a>
        <a class="btn" href="borrowing/borrowed.php">My Borrowed Books</a>
    </p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
