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

if (isset($_GET['id'])) {
    $food_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Verify that the food item belongs to the current user
    $stmt = $conn->prepare("SELECT id FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $food_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Delete the food item
        $stmt = $conn->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $food_id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: restaurant_dashboard.php?success=Food item deleted successfully!");
        } else {
            header("Location: restaurant_dashboard.php?error=Failed to delete food item.");
        }
    } else {
        header("Location: restaurant_dashboard.php?error=Food item not found or you don't have permission to delete it.");
    }
    
    $stmt->close();
} else {
    header("Location: restaurant_dashboard.php?error=Invalid request.");
}

$conn->close();
?>
