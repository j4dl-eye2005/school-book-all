<?php
// books/create.php
// CREATE: add a new book to the library.

require_once '../includes/auth.php';
require_admin();   // only admins can add books
require_once '../config/connect.php';

$error = "";

// Start with empty values so the form can be reused if there is an error.
$title = "";
$author = "";
$isbn = "";
$category = "";
$grade_level = "";
$quantity = "1";

// Handle the form on submit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and trim the typed values.
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
            // Insert with a prepared statement (safe from SQL injection).
            $stmt = $pdo->prepare(
                "INSERT INTO books (title, author, isbn, category, grade_level, quantity)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $title, $author, $isbn, $category, $grade_level, (int)$quantity
            ]);

            // Success: go back to the list with a message.
            header("Location: index.php?msg=" . urlencode("Book added successfully."));
            exit;
        } catch (PDOException $e) {
            // Error code 23000 means a UNIQUE rule was broken (duplicate ISBN).
            if ($e->getCode() === '23000') {
                $error = "That ISBN already exists. Please use a different ISBN.";
            } else {
                $error = "Could not save the book. Please try again.";
            }
        }
    }
}

$base = "../";
$pageTitle = "Add Book";
require_once '../includes/header.php';
?>

<h1>Add New Book</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="create.php">
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
    <button type="submit">Save Book</button>
    <a class="btn" href="index.php">Cancel</a>
</form>

<?php require_once '../includes/footer.php'; ?>
