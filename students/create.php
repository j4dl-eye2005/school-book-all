<?php
// students/create.php
// CREATE: add a new student.

require_once '../includes/auth.php';
require_admin();   // only admins can add students
require_once '../config/connect.php';

$error = "";

// Empty starting values.
$student_no = "";
$full_name = "";
$grade_level = "";
$email = "";

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
            // Insert the new student with a prepared statement.
            $stmt = $pdo->prepare(
                "INSERT INTO students (student_no, full_name, grade_level, email)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$student_no, $full_name, $grade_level, $email]);

            header("Location: index.php?msg=" . urlencode("Student added successfully."));
            exit;
        } catch (PDOException $e) {
            // 23000 = duplicate student_no (it is UNIQUE).
            if ($e->getCode() === '23000') {
                $error = "That Student No already exists. Please use a different one.";
            } else {
                $error = "Could not save the student. Please try again.";
            }
        }
    }
}

$base = "../";
$pageTitle = "Add Student";
require_once '../includes/header.php';
?>

<h1>Add New Student</h1>

<?php if ($error !== ''): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="create.php">
    <label>Student No *</label>
    <input type="text" name="student_no" value="<?php echo htmlspecialchars($student_no); ?>">

    <label>Full Name *</label>
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">

    <label>Grade Level</label>
    <input type="text" name="grade_level" value="<?php echo htmlspecialchars($grade_level); ?>">

    <label>Email</label>
    <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">

    <br>
    <button type="submit">Save Student</button>
    <a class="btn" href="index.php">Cancel</a>
</form>

<?php require_once '../includes/footer.php'; ?>
