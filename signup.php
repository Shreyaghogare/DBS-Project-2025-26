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
    $role = $_POST['role'];
    $restaurant_name = isset($_POST['restaurant_name']) ? trim($_POST['restaurant_name']) : '';
    $contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Basic validation
    if (empty($email) || empty($password) || empty($role)) {
        header("Location: food.html?error=Please fill in all fields");
        exit();
    }

    // Donor-specific validation
    if ($role === 'donor') {
        if (empty($restaurant_name) || empty($contact_no) || empty($address)) {
            header("Location: food.html?error=Please fill in all donor fields (Restaurant Name, Contact Number, Address)");
            exit();
        }
        if (!preg_match('/^[0-9]{10}$/', $contact_no)) {
            header("Location: food.html?error=Contact number must be exactly 10 digits");
            exit();
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: food.html?error=Invalid email format");
        exit();
    }

    if (strlen($password) < 6) {
        header("Location: food.html?error=Password must be at least 6 characters long");
        exit();
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if users table has donor-specific columns, if not add them
    $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'restaurant_name'");
    if ($checkColumns && $checkColumns->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN restaurant_name VARCHAR(100) NULL AFTER role");
        $conn->query("ALTER TABLE users ADD COLUMN contact_no VARCHAR(20) NULL AFTER restaurant_name");
        $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER contact_no");
    }

    // Prepare and execute the SQL statement to prevent SQL injection
    if ($role === 'donor') {
        $stmt = $conn->prepare("INSERT INTO users (email, password, role, restaurant_name, contact_no, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $email, $hashed_password, $role, $restaurant_name, $contact_no, $address);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $hashed_password, $role);
    }

    if ($stmt->execute()) {
        // Registration successful, redirect to login page
        header("Location: login_page.php?success=1");
        exit();
    } else {
        // Check for duplicate email error
        if ($stmt->errno == 1062) {
            header("Location: food.html?error=Email already exists. Please use a different email.");
        } else {
            $error_msg = "Registration failed: " . $stmt->error;
            header("Location: food.html?error=" . urlencode($error_msg));
        }
        exit();
    }

    $stmt->close();
} else {
    // If not POST request, redirect to home
    header("Location: food.html");
    exit();
}

$conn->close();
?>