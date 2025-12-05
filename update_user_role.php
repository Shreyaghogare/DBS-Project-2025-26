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
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    if ($userId <= 0 || !in_array($role, ['donor', 'receiver', 'admin'], true)) {
        header("Location: admin_dashboard.php?error=Invalid input");
        exit();
    }

    // Prevent self demotion changes only if desired; we allow role change except delete; UI prevents update on self
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $userId);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?success=Role updated successfully");
        exit();
    } else {
        header("Location: admin_dashboard.php?error=Failed to update role");
        exit();
    }
}

header("Location: admin_dashboard.php");
exit();
