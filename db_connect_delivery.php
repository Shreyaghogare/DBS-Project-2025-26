<?php
// Delivery Applications Database connection details
$servername = "localhost";
$username = "root"; // XAMPP default MySQL username
$password = ""; // XAMPP default MySQL password (empty)
$dbname = "foodshare_delivery_db";

// Create connection
$delivery_conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($delivery_conn->connect_error) {
    die("Connection failed: " . $delivery_conn->connect_error);
}
?>

