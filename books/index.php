<?php
// books/index.php
// READ: list all books with a search box and pagination (10 per page).

require_once '../includes/auth.php';   // must be logged in
require_once '../config/connect.php';  // database connection

$error = "";
$books = array();
$totalRows = 0;

// --- Search ---
// Read the search text from the URL (?search=...). Default is empty.
$search = trim($_GET['search'] ?? '');

// --- Pagination settings ---
$perPage = 10;                          // 10 books per page
$page = (int)($_GET['page'] ?? 1);      // current page number
if ($page < 1) {
    $page = 1;                          // never go below page 1
}
$offset = ($page - 1) * $perPage;       // how many rows to skip

try {
    // Build the WHERE part only if there is a search term.
    // We search in title OR author using LIKE.
    $where = "";
    $params = array();
    if ($search !== '') {
        $where = "WHERE title LIKE ? OR author LIKE ?";
        $like = "%" . $search . "%";
        $params[] = $like;
        $params[] = $like;
    }

    // First: count the total matching rows (for the Next button logic).
    $countSql = "SELECT COUNT(*) FROM books $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Then: get just this page of books.
    // LIMIT and OFFSET control which 10 rows we show.
    $sql = "SELECT * FROM books $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Could not load the books list.";
}

// Work out if there is a next page.
$totalPages = (int)ceil($totalRows / $perPage);

$base = "../";
$pageTitle = "Books";
require_once '../includes/header.php';
?>

<h1>Books</h1>

<?php if (isset($_GET['msg'])): ?>
    <!-- friendly message after add/edit/delete -->
    <div class="success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (is_admin()): ?>
    <p><a class="btn" href="create.php">+ Add New Book</a></p>
<?php endif; ?>

<!-- search form (uses GET so the term stays in the URL) -->
<form method="get" action="index.php">
    <input type="text" name="search" placeholder="Search title or author"
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?>
        <a class="btn btn-small" href="index.php">Clear</a>
    <?php endif; ?>
</form>

<?php if (count($books) === 0): ?>
    <!-- empty results message -->
    <p>No books found.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Category</th>
            <th>Grade</th>
            <th>Qty</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($books as $book): ?>
            <tr>
                <td><?php echo htmlspecialchars($book['title']); ?></td>
                <td><?php echo htmlspecialchars($book['author']); ?></td>
                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                <td><?php echo htmlspecialchars($book['category']); ?></td>
                <td><?php echo htmlspecialchars($book['grade_level']); ?></td>
                <td><?php echo (int)$book['quantity']; ?></td>
                <td>
                    <a class="btn btn-small" href="view.php?id=<?php echo (int)$book['id']; ?>">View</a>

                    <?php if (is_admin()): ?>
                        <!-- admin actions: edit and delete -->
                        <a class="btn btn-small" href="edit.php?id=<?php echo (int)$book['id']; ?>">Edit</a>

                        <!-- delete uses a POST form + confirm() -->
                        <form method="post" action="delete.php" style="display:inline;"
                              onsubmit="return confirm('Are you sure you want to delete this?');">
                            <input type="hidden" name="id" value="<?php echo (int)$book['id']; ?>">
                            <button type="submit" class="btn-small">Delete</button>
                        </form>
                    <?php else: ?>
                        <!-- users can borrow this book (only if copies are left) -->
                        <?php if ((int)$book['quantity'] > 0): ?>
                            <a class="btn btn-small"
                               href="../borrowing/borrow.php?book_id=<?php echo (int)$book['id']; ?>">Borrow</a>
                        <?php else: ?>
                            <span style="color:#999999;">Not available</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Previous / Next links, keeping the search term -->
    <p>
        <?php
        // Build the query string suffix so search is kept in the links.
        $searchQuery = ($search !== '') ? "&search=" . urlencode($search) : "";
        ?>

        <?php if ($page > 1): ?>
            <a class="btn btn-small" href="index.php?page=<?php echo $page - 1; ?><?php echo $searchQuery; ?>">Previous</a>
        <?php endif; ?>

        Page <?php echo $page; ?> of <?php echo ($totalPages > 0 ? $totalPages : 1); ?>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-small" href="index.php?page=<?php echo $page + 1; ?><?php echo $searchQuery; ?>">Next</a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
