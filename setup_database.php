<?php
// Database setup script for FoodShare application
$servername = "localhost";
$username = "root";
$password = "";

try {
    // Create connection without specifying database
    $conn = new mysqli($servername, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL successfully.<br>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS foodshare_db";
    if ($conn->query($sql) === TRUE) {
        echo "Database 'foodshare_db' created successfully or already exists.<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    // Select the database
    $conn->select_db("foodshare_db");
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('donor', 'receiver', 'admin') NOT NULL,
        restaurant_name VARCHAR(100) NULL,
        contact_no VARCHAR(20) NULL,
        address TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'users' created successfully or already exists.<br>";
        
        // Add new columns if they don't exist (for existing databases)
        $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'restaurant_name'");
        if ($checkColumns->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN restaurant_name VARCHAR(100) NULL AFTER role");
            $conn->query("ALTER TABLE users ADD COLUMN contact_no VARCHAR(20) NULL AFTER restaurant_name");
            $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER contact_no");
            echo "Added donor-specific columns to 'users' table.<br>";
        }
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
    
    // Create food_items table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS food_items (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        restaurant_name VARCHAR(100) NOT NULL,
        food_name VARCHAR(100) NOT NULL,
        description TEXT,
        quantity INT NOT NULL,
        expiry_date DATE,
        category VARCHAR(50),
        image_url VARCHAR(255),
        status ENUM('available', 'reserved', 'donated') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT(6) UNSIGNED,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'food_items' created successfully or already exists.<br>";
    } else {
        echo "Error creating food_items table: " . $conn->error . "<br>";
    }
    
    // Check if tables have any data
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "Number of users in database: " . $row['count'] . "<br>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM food_items");
    $row = $result->fetch_assoc();
    echo "Number of food items in database: " . $row['count'] . "<br>";
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<a href='food.html'>Go to Home Page</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
