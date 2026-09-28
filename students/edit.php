<?php
// students/edit.php
// UPDATE: edit an existing student.

require_once '../includes/auth.php';
require_admin();   // only admins can edit students
require_once '../config/connect.php';

$error = "";

// --- Check the id in the URL ---
$id = $_GET['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    $base = "../";
    $pageTitle = "Edit Student";
    require_once '../includes/header.php';
    echo "<div class='error'>Invalid student id.</div>";
    echo "<p><a class='btn' href='index.php'>Back to Students</a></p>";
    require_once '../includes/footer.php';
    exit;
}
$id = (int)$id;

// Empty starting values.
$student_no = $full_name = $grade_level = $email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_no = trim($_POST['student_no'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // --- Validation ---
    if ($student_no === '' || $full_name === '') {
        $error = "Student No and Full Name are required.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Update this student by id.
            $stmt = $pdo->prepare(
                "UPDATE students
                 SET student_no = ?, full_name = ?, grade_level = ?, email = ?
                 WHERE id = ?"
            );
            $stmt->execute([$student_no, $full_name, $grade_level, $email, $id]);

            header("Location: index.php?msg=" . urlencode("Student updated successfully."));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = "That Student No already exists. Please use a different one.";
            } else {
                $error = "Could not update the student. Please try again.";
            }
        }
    }
} else {
    // First load: fetch the student's current values.
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();

        if (!$student) {
            $base = "../";
            $pageTitle = "Edit Student";
            require_once '../includes/header.php';
            echo "<div class='error'>Student not found.</div>";
            echo "<p><a class='btn' href='index.php'>Back to Students</a></p>";
            require_once '../includes/footer.php';
            exit;
        }

        $student_no = $student['student_no'];
        $full_name = $student['full_name'];
        $grade_level = $student['grade_level'];
        $email = $student['email'];
    } catch (PDOException $e) {
        $error = "Could not load the student.";
    }
}

$base = "../";
$pageTitle = "Edit Student";
require_once '../includes/header.php';
?>

<h1>Edit Student</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="edit.php?id=<?php echo $id; ?>">
    <label>Student No *</label>
    <input type="text" name="student_no" value="<?php echo htmlspecialchars($student_no); ?>">

    <label>Full Name *</label>
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">

    <label>Grade Level</label>
    <input type="text" name="grade_level" value="<?php echo htmlspecialchars($grade_level); ?>">

    <label>Email</label>
    <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">

    <br>
    <button type="submit">Update Student</button>
    <a class="btn" href="index.php">Cancel</a>
</form>

<?php require_once '../includes/footer.php'; ?>
