<?php
session_start();
include 'db_connect.php';

// Check if database connection is successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : null; // receiver_login | donor_login | admin_login | null

    // Basic validation
    if (empty($email) || empty($password)) {
        $target = ($redirect === 'receiver_login') ? 'receiver_login.php' : (($redirect === 'donor_login') ? 'donor_login.php' : (($redirect === 'admin_login') ? 'admin_login.php' : 'login_page.php'));
        header("Location: $target?error=Please fill in all fields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $target = ($redirect === 'receiver_login') ? 'receiver_login.php' : (($redirect === 'donor_login') ? 'donor_login.php' : (($redirect === 'admin_login') ? 'admin_login.php' : 'login_page.php'));
        header("Location: $target?error=Invalid email format");
        exit();
    }

    // Prepare a statement to get the user's hashed password
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        // Enforce role based on origin page
        if ($redirect === 'donor_login' && $role !== 'donor') {
            header("Location: donor_login.php?error=Please login with a donor account");
            exit();
        }
        if ($redirect === 'receiver_login' && $role !== 'receiver') {
            header("Location: receiver_login.php?error=Please login with a receiver account");
            exit();
        }
        if ($redirect === 'admin_login' && $role !== 'admin') {
            header("Location: admin_login.php?error=Please login with an admin account");
            exit();
        }

        // Login successful, set session variables
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $target = ($redirect === 'receiver_login') ? 'receiver_login.php' : (($redirect === 'donor_login') ? 'donor_login.php' : (($redirect === 'admin_login') ? 'admin_login.php' : 'login_page.php'));
        header("Location: $target?error=Invalid email or password");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>