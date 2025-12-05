<?php
// Database connection details
$servername = "localhost";
$username = "root"; // XAMPP default MySQL username
$password = ""; // XAMPP default MySQL password (empty)
$dbname = "foodshare_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>