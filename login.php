<?php
// login.php
// The login form. Works for BOTH admins and users.
// This page and register.php are the only pages that do not require auth.php.

// Start the session so we can save the logged-in user.
session_start();

// Load the database connection.
require_once 'config/connect.php';

// If already logged in, go to the dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";      // error message shown above the form
$username = "";   // keep the typed username after an error

// Handle the form when it is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Both fields are required.
    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Look up the user by username using a prepared statement.
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Check the user exists AND the password matches the hash.
            if ($user && password_verify($password, $user['password'])) {
                // Success: save info (including the ROLE) in the session.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - School Book All</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-shell">

    <!-- Left: marketing / "what this is" hero panel -->
    <aside class="auth-hero">
        <div class="auth-brand"><span class="auth-diamond"></span> School Book All</div>

        <div class="auth-eyebrows">
            <span class="badge">✦ School library</span>
            <span class="badge">Books &amp; borrowing</span>
        </div>

        <h2 class="auth-headline">Your library,<br>run <span class="hl">simply.</span></h2>

        <p class="auth-lead">
            Track every title, manage students, and handle borrow-and-return in one
            tidy place. Fewer lost books, clearer due dates, less paperwork.
        </p>

        <ul class="auth-points">
            <li><span class="dot dot-coral"></span> Browse and search the whole catalog</li>
            <li><span class="dot dot-yellow"></span> Borrow and return with automatic due dates</li>
            <li><span class="dot dot-green"></span> See overdue books at a glance</li>
        </ul>
    </aside>

    <!-- Right: the login card -->
    <div class="content">
        <h2>Login</h2>

        <?php if (isset($_GET['msg'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">

            <label>Password</label>
            <input type="password" name="password">

            <br>
            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Create one here</a>.</p>
    </div>

</div>

</body>
</html>
