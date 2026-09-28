<?php
// books/view.php
// READ: show the details of one single book.

require_once '../includes/auth.php';
require_once '../config/connect.php';

$error = "";
$book = null;

// --- Check the id in the URL ---
$id = $_GET['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    $error = "Invalid book id.";
} else {
    $id = (int)$id;
    try {
        // Get the book by id.
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();

        if (!$book) {
            $error = "Book not found.";
        }
    } catch (PDOException $e) {
        $error = "Could not load the book.";
    }
}

$base = "../";
$pageTitle = "Book Details";
require_once '../includes/header.php';
?>

<h1>Book Details</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <p><a class="btn" href="index.php">Back to Books</a></p>
<?php else: ?>
    <table>
        <tr><th>Title</th><td><?php echo htmlspecialchars($book['title']); ?></td></tr>
        <tr><th>Author</th><td><?php echo htmlspecialchars($book['author']); ?></td></tr>
        <tr><th>ISBN</th><td><?php echo htmlspecialchars($book['isbn']); ?></td></tr>
        <tr><th>Category</th><td><?php echo htmlspecialchars($book['category']); ?></td></tr>
        <tr><th>Grade Level</th><td><?php echo htmlspecialchars($book['grade_level']); ?></td></tr>
        <tr><th>Quantity</th><td><?php echo (int)$book['quantity']; ?></td></tr>
        <tr><th>Added On</th><td><?php echo htmlspecialchars($book['created_at']); ?></td></tr>
    </table>

    <p>
        <?php if (is_admin()): ?>
            <a class="btn" href="edit.php?id=<?php echo (int)$book['id']; ?>">Edit</a>
        <?php elseif ((int)$book['quantity'] > 0): ?>
            <a class="btn" href="../borrowing/borrow.php?book_id=<?php echo (int)$book['id']; ?>">Borrow</a>
        <?php endif; ?>
        <a class="btn" href="index.php">Back to Books</a>
    </p>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
