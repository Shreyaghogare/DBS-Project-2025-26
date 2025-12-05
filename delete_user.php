<?php
session_start();
include 'db_connect.php';

// Require admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_page.php?error=Admin access required");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($userId <= 0) {
        header("Location: admin_dashboard.php?error=Invalid user");
        exit();
    }

    // Prevent deleting yourself
    if ((int)$_SESSION['user_id'] === $userId) {
        header("Location: admin_dashboard.php?error=You cannot delete your own account");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?success=User deleted successfully");
        exit();
    } else {
        header("Location: admin_dashboard.php?error=Failed to delete user");
        exit();
    }
}

header("Location: admin_dashboard.php");
exit();
