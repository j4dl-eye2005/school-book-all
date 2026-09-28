<?php
// borrowing/borrowed.php
// List borrow records.
//   - Admin: sees ALL records (oversight).
//   - User:  sees only THEIR OWN records.
// Overdue books (past due date and not returned) get a red "Overdue" label.

require_once '../includes/auth.php';
require_once '../config/connect.php';

$error = "";
$records = array();

// For a normal user, find their own student row so we can filter.
$myStudentId = 0;
if (!is_admin()) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $myStudentId = $row ? (int)$row['id'] : 0;
    } catch (PDOException $e) {
        $error = "Could not find your student profile.";
    }
}

try {
    // Join the three tables so we can show names and titles.
    $sql = "SELECT bb.id, bb.student_id, bb.borrow_date, bb.due_date, bb.return_date, bb.status,
                   s.full_name AS student_name,
                   b.title AS book_title
            FROM borrowed_books bb
            JOIN students s ON bb.student_id = s.id
            JOIN books b ON bb.book_id = b.id";

    if (is_admin()) {
        // Admin: all records.
        $sql .= " ORDER BY bb.id DESC";
        $records = $pdo->query($sql)->fetchAll();
    } else {
        // User: only their own records.
        $sql .= " WHERE bb.student_id = ? ORDER BY bb.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$myStudentId]);
        $records = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = "Could not load the borrow records.";
}

// Today's date, used to check if a book is overdue.
$today = date('Y-m-d');

$base = "../";
$pageTitle = is_admin() ? "All Borrowed Books" : "My Borrowed Books";
require_once '../includes/header.php';
?>

<h1><?php echo is_admin() ? 'All Borrowed Books' : 'My Borrowed Books'; ?></h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<p><a class="btn" href="borrow.php">+ Borrow a Book</a></p>

<?php if (count($records) === 0): ?>
    <p>No borrow records found.</p>
<?php else: ?>
    <table>
        <tr>
            <?php if (is_admin()): ?>
                <th>Student</th>
            <?php endif; ?>
            <th>Book</th>
            <th>Borrowed</th>
            <th>Due</th>
            <th>Returned</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($records as $r): ?>
            <?php
            // A record is overdue if it is still borrowed and past the due date.
            $isOverdue = ($r['status'] === 'borrowed' && $r['due_date'] < $today);
            ?>
            <tr>
                <?php if (is_admin()): ?>
                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($r['book_title']); ?></td>
                <td><?php echo htmlspecialchars($r['borrow_date']); ?></td>
                <td><?php echo htmlspecialchars($r['due_date']); ?></td>
                <td><?php echo htmlspecialchars($r['return_date'] ?? '-'); ?></td>
                <td>
                    <?php
                    // Status pill, color-coded by the CSS status classes.
                    $statusClass = 'status-' . preg_replace('/[^a-z]/', '', strtolower($r['status']));
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($r['status'])); ?>
                    </span>
                    <?php if ($isOverdue): ?>
                        <span class="overdue">Overdue</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (is_admin() && $r['status'] === 'pending'): ?>
                        <!-- Admin confirms the student actually borrowed the book. -->
                        <form method="post" action="approve.php" style="display:inline;"
                              onsubmit="return confirm('Confirm this student borrowed the book?');">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit" class="btn-small">Approve</button>
                        </form>
                    <?php elseif (is_admin() && $r['status'] === 'borrowed'): ?>
                        <!-- Admin marks a borrowed book as returned. -->
                        <form method="post" action="return.php" style="display:inline;"
                              onsubmit="return confirm('Mark this book as returned?');">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit" class="btn-small">Mark Returned</button>
                        </form>
                    <?php elseif (!is_admin() && $r['status'] === 'pending'): ?>
                        <span style="color:#999;">Awaiting approval</span>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
