<?php
// borrowing/approve.php
// Approve a PENDING borrow request: mark it 'borrowed' and commit one copy
// (quantity - 1). This is the admin's confirmation that the student actually
// took the book. POST only, admin only.

require_once '../includes/auth.php';
require_once '../config/connect.php';

// Admin only. require_admin() bounces regular users back to the dashboard.
require_admin();

// Only allow POST (from the "Approve" button on borrowed.php).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: borrowed.php");
    exit;
}

// Read and validate the borrow record id.
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: borrowed.php?err=" . urlencode("Invalid borrow record id."));
    exit;
}
$id = (int)$id;

try {
    // Load the record.
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        header("Location: borrowed.php?err=" . urlencode("Borrow record not found."));
        exit;
    }

    // Only pending requests can be approved.
    if ($record['status'] !== 'pending') {
        header("Location: borrowed.php?err=" . urlencode("Only pending requests can be approved."));
        exit;
    }

    // Do the status change and the quantity commit together, in a transaction.
    $pdo->beginTransaction();

    // Re-check availability now (a copy may have been taken since the request).
    $b = $pdo->prepare("SELECT quantity FROM books WHERE id = ? FOR UPDATE");
    $b->execute([(int)$record['book_id']]);
    $book = $b->fetch();

    if (!$book) {
        $pdo->rollBack();
        header("Location: borrowed.php?err=" . urlencode("The book for this request no longer exists."));
        exit;
    }

    if ((int)$book['quantity'] <= 0) {
        $pdo->rollBack();
        header("Location: borrowed.php?err=" . urlencode("Cannot approve: no copies of this book are available."));
        exit;
    }

    // Approve: pending -> borrowed, and commit one copy.
    $upd = $pdo->prepare("UPDATE borrowed_books SET status = 'borrowed' WHERE id = ?");
    $upd->execute([$id]);

    $dec = $pdo->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
    $dec->execute([(int)$record['book_id']]);

    $pdo->commit();

    header("Location: borrowed.php?msg=" . urlencode("Request approved. The book is now marked as borrowed."));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: borrowed.php?err=" . urlencode("Could not approve the request. Please try again."));
    exit;
}
?>
