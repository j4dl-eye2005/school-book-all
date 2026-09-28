<?php
// books/delete.php
// DELETE: remove a book. This page only accepts POST (from the delete form).

require_once '../includes/auth.php';
require_admin();   // only admins can delete books
require_once '../config/connect.php';

// Only allow POST. If someone visits by URL, send them back.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Read the id from the hidden form field.
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: index.php?err=" . urlencode("Invalid book id."));
    exit;
}
$id = (int)$id;

try {
    // Safety check: do not delete a book that is still borrowed OR has a
    // pending request waiting on it.
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM borrowed_books WHERE book_id = ? AND status IN ('borrowed', 'pending')"
    );
    $check->execute([$id]);
    $active = (int)$check->fetchColumn();

    if ($active > 0) {
        header("Location: index.php?err=" . urlencode("Cannot delete: this book has active borrow records."));
        exit;
    }

    // Make sure the book exists before deleting.
    $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        header("Location: index.php?err=" . urlencode("Book not found."));
        exit;
    }

    // Delete the book.
    $del = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $del->execute([$id]);

    header("Location: index.php?msg=" . urlencode("Book deleted successfully."));
    exit;
} catch (PDOException $e) {
    header("Location: index.php?err=" . urlencode("Could not delete the book."));
    exit;
}
?>
