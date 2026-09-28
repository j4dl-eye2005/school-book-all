<?php
// students/delete.php
// DELETE: remove a student. POST only (from the delete form).

require_once '../includes/auth.php';
require_admin();   // only admins can delete students
require_once '../config/connect.php';

// Only allow POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Read the id from the hidden field.
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: index.php?err=" . urlencode("Invalid student id."));
    exit;
}
$id = (int)$id;

try {
    // Safety check: do not delete a student who still has borrowed books OR a
    // pending borrow request.
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND status IN ('borrowed', 'pending')"
    );
    $check->execute([$id]);
    $active = (int)$check->fetchColumn();

    if ($active > 0) {
        header("Location: index.php?err=" . urlencode("Cannot delete: this student has active borrow records."));
        exit;
    }

    // Make sure the student exists.
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        header("Location: index.php?err=" . urlencode("Student not found."));
        exit;
    }

    // Delete the student.
    $del = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $del->execute([$id]);

    header("Location: index.php?msg=" . urlencode("Student deleted successfully."));
    exit;
} catch (PDOException $e) {
    header("Location: index.php?err=" . urlencode("Could not delete the student."));
    exit;
}
?>
