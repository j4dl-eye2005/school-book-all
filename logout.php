<?php
// logout.php
// This page destroys the session and sends the user back to login.

session_start();

// Empty all the session values.
$_SESSION = array();

// Destroy the session on the server.
session_destroy();

// Go back to the login page.
header("Location: login.php");
exit;
?>
