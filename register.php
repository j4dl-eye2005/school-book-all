<?php
// register.php
// Lets a new USER create their own account.
// When they register we make TWO things:
//   1) a row in "users" (role = 'user') so they can log in
//   2) a row in "students" linked to that user, so they can borrow books
// Both are done together in a transaction (all or nothing).

session_start();
require_once 'config/connect.php';

// If already logged in, no need to register.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

// Keep typed values so the form stays filled after an error.
$username = "";
$full_name = "";
$grade_level = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // --- Validation ---
    if ($username === '' || $password === '' || $full_name === '') {
        $error = "Username, password, and full name are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "The two passwords do not match.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Make sure the username is not already taken.
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = "That username is already taken. Please pick another.";
            } else {
                // Hash the password before saving it.
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Do both inserts together so they cannot get out of sync.
                $pdo->beginTransaction();

                // 1) Create the login account (role = 'user').
                $insUser = $pdo->prepare(
                    "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')"
                );
                $insUser->execute([$username, $hash]);
                $newUserId = (int)$pdo->lastInsertId();

                // 2) Build a simple unique student number, e.g. "U-7".
                $student_no = "U-" . $newUserId;

                // 3) Create the linked student row.
                $insStudent = $pdo->prepare(
                    "INSERT INTO students (user_id, student_no, full_name, grade_level, email)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $insStudent->execute([$newUserId, $student_no, $full_name, $grade_level, $email]);

                $pdo->commit();

                // Send them to login with a success message.
                header("Location: login.php?msg=" . urlencode("Account created. You can log in now."));
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000 could be a duplicate username or student_no.
            if ($e->getCode() === '23000') {
                $error = "That account could not be created (duplicate). Please try again.";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - School Book All</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-shell">

    <!-- Left: marketing / "what this is" hero panel -->
    <aside class="auth-hero">
        <div class="auth-brand"><span class="auth-diamond"></span> School Book All</div>

        <div class="auth-eyebrows">
            <span class="badge">✦ School library</span>
            <span class="badge">Free student account</span>
        </div>

        <h2 class="auth-headline">Join &amp; start<br>borrowing <span class="hl">today.</span></h2>

        <p class="auth-lead">
            Create your reader account to browse the catalog, borrow books as
            yourself, and keep track of what you have out and when it is due.
        </p>

        <ul class="auth-points">
            <li><span class="dot dot-coral"></span> One account for browsing and borrowing</li>
            <li><span class="dot dot-yellow"></span> Automatic 7-day due dates</li>
            <li><span class="dot dot-green"></span> Your borrowing history in one view</li>
        </ul>
    </aside>

    <!-- Right: the register card -->
    <div class="content">
        <h2>Create an Account</h2>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
        <label>Username *</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">

        <label>Password * (at least 6 characters)</label>
        <input type="password" name="password">

        <label>Confirm Password *</label>
        <input type="password" name="confirm">

        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">

        <label>Grade Level</label>
        <input type="text" name="grade_level" value="<?php echo htmlspecialchars($grade_level); ?>">

        <label>Email</label>
        <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <br>
        <button type="submit">Register</button>
    </form>

        <p>Already have an account? <a href="login.php">Log in here</a>.</p>
    </div>

</div>

</body>
</html>
