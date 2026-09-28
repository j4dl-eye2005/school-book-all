<?php
// books/edit.php
// UPDATE: edit an existing book.

require_once '../includes/auth.php';
require_admin();   // only admins can edit books
require_once '../config/connect.php';

$error = "";

// --- Check the id in the URL ---
// It must be there and it must be a number.
$id = $_GET['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    // Bad or missing id: show a friendly message and stop.
    $base = "../";
    $pageTitle = "Edit Book";
    require_once '../includes/header.php';
    echo "<div class='error'>Invalid book id.</div>";
    echo "<p><a class='btn' href='index.php'>Back to Books</a></p>";
    require_once '../includes/footer.php';
    exit;
}
$id = (int)$id;

// Start with empty values.
$title = $author = $isbn = $category = $grade_level = "";
$quantity = "1";

// Handle the form on submit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');

    // --- Validation ---
    if ($title === '' || $author === '') {
        $error = "Title and Author are required.";
    } elseif ($quantity === '' || !is_numeric($quantity) || (int)$quantity < 0) {
        $error = "Quantity must be a number that is 0 or more.";
    } else {
        try {
            // Update this book by id using a prepared statement.
            $stmt = $pdo->prepare(
                "UPDATE books
                 SET title = ?, author = ?, isbn = ?, category = ?, grade_level = ?, quantity = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $title, $author, $isbn, $category, $grade_level, (int)$quantity, $id
            ]);

            header("Location: index.php?msg=" . urlencode("Book updated successfully."));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = "That ISBN already exists. Please use a different ISBN.";
            } else {
                $error = "Could not update the book. Please try again.";
            }
        }
    }
} else {
    // First load (GET): fetch the current book values into the form.
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();

        if (!$book) {
            // No book with this id.
            $base = "../";
            $pageTitle = "Edit Book";
            require_once '../includes/header.php';
            echo "<div class='error'>Book not found.</div>";
            echo "<p><a class='btn' href='index.php'>Back to Books</a></p>";
            require_once '../includes/footer.php';
            exit;
        }

        // Fill the form values from the database.
        $title = $book['title'];
        $author = $book['author'];
        $isbn = $book['isbn'];
        $category = $book['category'];
        $grade_level = $book['grade_level'];
        $quantity = $book['quantity'];
    } catch (PDOException $e) {
        $error = "Could not load the book.";
    }
}

$base = "../";
$pageTitle = "Edit Book";
require_once '../includes/header.php';
?>

<h1>Edit Book</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="edit.php?id=<?php echo $id; ?>">
    <label>Title *</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">

    <label>Author *</label>
    <input type="text" name="author" value="<?php echo htmlspecialchars($author); ?>">

    <label>ISBN</label>
    <input type="text" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">

    <label>Category</label>
    <input type="text" name="category" value="<?php echo htmlspecialchars($category); ?>">

    <label>Grade Level</label>
    <input type="text" name="grade_level" value="<?php echo htmlspecialchars($grade_level); ?>">

    <label>Quantity</label>
    <input type="text" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">

    <br>
    <button type="submit">Update Book</button>
    <a class="btn" href="index.php">Cancel</a>
</form>

<?php require_once '../includes/footer.php'; ?>
