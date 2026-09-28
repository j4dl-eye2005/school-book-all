<?php
// config/connect.php
// This file creates the database connection using PDO.
// Every page loads this file so it can talk to MySQL.

$host = "localhost";          // where MySQL runs (XAMPP uses localhost)
$dbname = "school_books_db";  // the name of our database
$username = "root";           // default XAMPP MySQL user
$password = "";               // default XAMPP password is empty

// We use try/catch so a connection problem shows a friendly message
// instead of a scary PHP error page.
try {
    // Build the connection string (DSN). utf8mb4 supports all characters.
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    // Create the PDO object. This is our $pdo we use everywhere.
    $pdo = new PDO($dsn, $username, $password);

    // Make PDO throw exceptions when something goes wrong.
    // This lets our try/catch blocks work on every query.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return rows as simple associative arrays (column name => value).
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If we cannot connect, stop the page and show a simple message.
    die("Database connection failed. Please check your settings and try again.");
}
?>
