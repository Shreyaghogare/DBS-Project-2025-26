<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: food.html");
    exit();
}

// Redirect based on user role
$role = $_SESSION['role'];

switch ($role) {
    case 'donor':
        header("Location: restaurant_dashboard.php");
        break;
    case 'receiver':
        header("Location: receiver_dashboard.php");
        break;
    case 'admin':
        header("Location: admin_dashboard.php");
        break;
    default:
        header("Location: restaurant_dashboard.php");
        break;
}
exit();
?>