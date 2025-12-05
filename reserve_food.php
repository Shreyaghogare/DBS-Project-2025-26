<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a receiver
if (!isset($_SESSION['user_id'])) {
    header("Location: receiver_login.php?error=Please login to reserve food");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') {
    header("Location: receiver_login.php?error=Only receivers can reserve food");
    exit();
}

$receiver_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['food_id'])) {
    $food_id = intval($_POST['food_id']);
    
    // Check if food item exists and is available
    $stmt = $conn->prepare("SELECT id, status, food_name FROM food_items WHERE id = ?");
    $stmt->bind_param("i", $food_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        header("Location: receiver_dashboard.php?error=Food item not found");
        exit();
    }
    
    $food_item = $result->fetch_assoc();
    
    // Check if already reserved or donated
    if ($food_item['status'] !== 'available') {
        $status_msg = ucfirst($food_item['status']);
        header("Location: receiver_dashboard.php?error=This food item is already {$status_msg}");
        exit();
    }
    
    // Check if reservations table exists, if not create it
    $checkTable = $conn->query("SHOW TABLES LIKE 'reservations'");
    if ($checkTable->num_rows == 0) {
        $createTable = "CREATE TABLE reservations (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            food_id INT(6) UNSIGNED NOT NULL,
            receiver_id INT(6) UNSIGNED NOT NULL,
            reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('reserved', 'picked_up', 'cancelled') DEFAULT 'reserved',
            FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_food_id (food_id),
            INDEX idx_receiver_id (receiver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($createTable);
    }
    
    // Check if this receiver already reserved this item
    $checkReservation = $conn->prepare("SELECT id FROM reservations WHERE food_id = ? AND receiver_id = ? AND status = 'reserved'");
    $checkReservation->bind_param("ii", $food_id, $receiver_id);
    $checkReservation->execute();
    $existingReservation = $checkReservation->get_result();
    
    if ($existingReservation->num_rows > 0) {
        header("Location: receiver_dashboard.php?error=You have already reserved this food item");
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update food item status to reserved
        $updateStmt = $conn->prepare("UPDATE food_items SET status = 'reserved' WHERE id = ? AND status = 'available'");
        $updateStmt->bind_param("i", $food_id);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows == 0) {
            throw new Exception("Failed to update food item status. It may have been reserved by someone else.");
        }
        
        // Create reservation record
        $reserveStmt = $conn->prepare("INSERT INTO reservations (food_id, receiver_id) VALUES (?, ?)");
        $reserveStmt->bind_param("ii", $food_id, $receiver_id);
        $reserveStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $food_name = htmlspecialchars($food_item['food_name']);
        header("Location: receiver_dashboard.php?success=Successfully reserved: {$food_name}");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: receiver_dashboard.php?error=" . urlencode($e->getMessage()));
        exit();
    }
    
    $updateStmt->close();
    $reserveStmt->close();
} else {
    header("Location: receiver_dashboard.php?error=Invalid request");
    exit();
}

$conn->close();
?>

