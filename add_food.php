<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: food.html");
    exit();
}

// Check if database connection is successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $restaurant_name = "Restaurant " . explode('@', $_SESSION['email'])[0];
    $food_name = trim($_POST['food_name']);
    $description = trim($_POST['description']);
    $quantity = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'];
    $category = $_POST['category'];

    // Basic validation
    if (empty($food_name) || empty($description) || empty($quantity) || empty($expiry_date) || empty($category)) {
        header("Location: restaurant_dashboard.php?error=Please fill in all fields");
        exit();
    }

    if ($quantity <= 0) {
        header("Location: restaurant_dashboard.php?error=Quantity must be greater than 0");
        exit();
    }

    // Check if expiry date is not in the past
    if (strtotime($expiry_date) < strtotime('today')) {
        header("Location: restaurant_dashboard.php?error=Expiry date cannot be in the past");
        exit();
    }

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO food_items (restaurant_name, food_name, description, quantity, expiry_date, category, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $restaurant_name, $food_name, $description, $quantity, $expiry_date, $category, $user_id);

    if ($stmt->execute()) {
        header("Location: restaurant_dashboard.php?success=Food item added successfully!");
        exit();
    } else {
        header("Location: restaurant_dashboard.php?error=Failed to add food item. Please try again.");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
