<?php
// students/index.php
// READ: list all students.

require_once '../includes/auth.php';
require_admin();   // only admins can manage students
require_once '../config/connect.php';

$error = "";
$students = array();

try {
    // Get all students, newest first.
    $students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $error = "Could not load the students list.";
}

$base = "../";
$pageTitle = "Students";
require_once '../includes/header.php';
?>

<h1>Students</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<p><a class="btn" href="create.php">+ Add New Student</a></p>

<?php if (count($students) === 0): ?>
    <p>No students found.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Student No</th>
            <th>Full Name</th>
            <th>Grade</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['student_no']); ?></td>
                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td>
                    <a class="btn btn-small" href="edit.php?id=<?php echo (int)$student['id']; ?>">Edit</a>

                    <!-- delete uses a POST form + confirm() -->
                    <form method="post" action="delete.php" style="display:inline;"
                          onsubmit="return confirm('Are you sure you want to delete this?');">
                        <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">
                        <button type="submit" class="btn-small">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
