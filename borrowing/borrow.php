<?php
// borrowing/borrow.php
// Borrow a book.
//   - Admin: picks any student from a dropdown.
//   - User:  borrows as themselves (their own linked student row).
// Either way we reduce the book quantity by 1.

require_once '../includes/auth.php';
require_once '../config/connect.php';

$error = "";

// Default due date is 7 days from today.
$defaultDue = date('Y-m-d', strtotime('+7 days'));

// Values used to keep the form filled after an error.
// A user can arrive from the books list with ?book_id=NN to prefill the book.
$book_id = trim($_GET['book_id'] ?? '');
$student_id = "";
$due_date = $defaultDue;

// For a normal user, find their own student row up front.
// (Admins do not need this; they choose a student below.)
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

// Handle the borrow form on submit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $book_id = trim($_POST['book_id'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    // Decide WHO is borrowing.
    if (is_admin()) {
        // Admin chose a student from the dropdown.
        $student_id = trim($_POST['student_id'] ?? '');
    } else {
        // User always borrows as themselves.
        $student_id = (string)$myStudentId;
    }

    $today = date('Y-m-d');

    // --- Validation ---
    if ($book_id === '' || $student_id === '' || $student_id === '0') {
        $error = "Please choose a book" . (is_admin() ? " and a student." : ".");
    } elseif ($due_date === '') {
        $error = "Please choose a due date.";
    } elseif ($due_date < $today) {
        $error = "The due date cannot be earlier than today.";
    } else {
        try {
            // Get the chosen book to check its quantity.
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([(int)$book_id]);
            $book = $stmt->fetch();

            // Make sure the student exists.
            $sstmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
            $sstmt->execute([(int)$student_id]);
            $student = $sstmt->fetch();

            if (!$book) {
                $error = "That book does not exist.";
            } elseif (!$student) {
                $error = "That student does not exist.";
            } elseif ((int)$book['quantity'] <= 0) {
                $error = "Sorry, that book is not available right now (0 copies left).";
            } elseif (is_admin()) {
                // Admin is borrowing on behalf of a student. There is no one
                // else to confirm the handover, so we skip the pending step:
                // record it as 'borrowed' and commit one copy immediately, all
                // in a single transaction.
                $pdo->beginTransaction();

                $insert = $pdo->prepare(
                    "INSERT INTO borrowed_books (book_id, student_id, borrow_date, due_date, status)
                     VALUES (?, ?, ?, ?, 'borrowed')"
                );
                $insert->execute([(int)$book_id, (int)$student_id, $today, $due_date]);

                $dec = $pdo->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
                $dec->execute([(int)$book_id]);

                $pdo->commit();

                header("Location: borrowed.php?msg=" . urlencode("Book borrowed and marked as borrowed for the student."));
                exit;
            } else {
                // A student is requesting to borrow. We do NOT reduce the book
                // quantity yet — a copy is only committed once an admin approves
                // the request (confirming the student actually took the book).
                $insert = $pdo->prepare(
                    "INSERT INTO borrowed_books (book_id, student_id, borrow_date, due_date, status)
                     VALUES (?, ?, ?, ?, 'pending')"
                );
                $insert->execute([(int)$book_id, (int)$student_id, $today, $due_date]);

                header("Location: borrowed.php?msg=" . urlencode("Borrow request submitted. An admin will confirm it shortly."));
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Could not save the borrow record. Please try again.";
        }
    }
}

// If a user has no linked student row, they cannot borrow. Explain nicely.
if (!is_admin() && $myStudentId === 0 && $error === "") {
    $error = "Your account is not linked to a student profile, so you cannot borrow yet.";
}

// Load the dropdown lists.
$students = array();
$books = array();
try {
    // Only admins need the student list.
    if (is_admin()) {
        $students = $pdo->query("SELECT id, student_no, full_name FROM students ORDER BY full_name")->fetchAll();
    }
    $books = $pdo->query("SELECT id, title, quantity FROM books ORDER BY title")->fetchAll();
} catch (PDOException $e) {
    $error = "Could not load the students or books list.";
}

$base = "../";
$pageTitle = "Borrow a Book";
require_once '../includes/header.php';
?>

<h1>Borrow a Book</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="borrow.php">

    <?php if (is_admin()): ?>
        <!-- admin picks any student -->
        <label>Student</label>
        <select name="student_id">
            <option value="">-- choose a student --</option>
            <?php foreach ($students as $s): ?>
                <option value="<?php echo (int)$s['id']; ?>"
                    <?php echo ($student_id == $s['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['full_name'] . " (" . $s['student_no'] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <!-- user borrows as themselves; show a note instead of a picker -->
        <p>You are borrowing as <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>.</p>
    <?php endif; ?>

    <label>Book</label>
    <select name="book_id">
        <option value="">-- choose a book --</option>
        <?php foreach ($books as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>"
                <?php echo ($book_id == $b['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b['title']); ?>
                (<?php echo (int)$b['quantity']; ?> available)
            </option>
        <?php endforeach; ?>
    </select>

    <label>Due Date</label>
    <input type="date" name="due_date" value="<?php echo htmlspecialchars($due_date); ?>">

    <br>
    <button type="submit"><?php echo is_admin() ? 'Create Borrow Request' : 'Request to Borrow'; ?></button>
    <a class="btn btn-small" href="borrowed.php"><?php echo is_admin() ? 'View All Borrowed' : 'My Borrowed'; ?></a>
</form>

<?php if (is_admin()): ?>
    <p style="color:#777; font-size:13px; margin-top:16px;">
        Note: as an admin, borrowing here is confirmed immediately &mdash; a copy
        is committed and the record is marked <b>borrowed</b> right away.
    </p>
<?php else: ?>
    <p style="color:#777; font-size:13px; margin-top:16px;">
        Note: borrowing creates a <b>pending</b> request. The book is reserved for
        you only after an admin confirms the request.
    </p>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
