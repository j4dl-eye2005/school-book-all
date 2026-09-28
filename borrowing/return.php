<?php
// borrowing/return.php
// Return a book: mark the record returned, set return_date, add 1 to quantity.
// POST only (from the "Return" button on borrowed.php).

require_once '../includes/auth.php';
require_once '../config/connect.php';

// Admin only: only an admin may mark a borrowed book as returned.
require_admin();

// Only allow POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: borrowed.php");
    exit;
}

// Read the borrow record id from the hidden field.
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: borrowed.php?err=" . urlencode("Invalid borrow record id."));
    exit;
}
$id = (int)$id;

try {
    // Get the borrow record.
    $stmt = $pdo->prepare("SELECT * FROM borrowed_books WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        header("Location: borrowed.php?err=" . urlencode("Borrow record not found."));
        exit;
    }

    // Only a book that is currently 'borrowed' can be returned.
    // A 'pending' request has no committed copy yet, and 'returned' is done.
    if ($record['status'] === 'pending') {
        header("Location: borrowed.php?err=" . urlencode("This is a pending request. Approve it before it can be returned."));
        exit;
    }
    if ($record['status'] === 'returned') {
        header("Location: borrowed.php?err=" . urlencode("This book was already returned."));
        exit;
    }
    if ($record['status'] !== 'borrowed') {
        header("Location: borrowed.php?err=" . urlencode("This record cannot be returned."));
        exit;
    }

    // Update the record and add the copy back, together in a transaction.
    $today = date('Y-m-d');
    $pdo->beginTransaction();

    $update = $pdo->prepare(
        "UPDATE borrowed_books SET status = 'returned', return_date = ? WHERE id = ?"
    );
    $update->execute([$today, $id]);

    // Add 1 back to the book's quantity.
    $addBack = $pdo->prepare("UPDATE books SET quantity = quantity + 1 WHERE id = ?");
    $addBack->execute([(int)$record['book_id']]);

    $pdo->commit();

    header("Location: borrowed.php?msg=" . urlencode("Book returned successfully."));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: borrowed.php?err=" . urlencode("Could not return the book."));
    exit;
}
?>
