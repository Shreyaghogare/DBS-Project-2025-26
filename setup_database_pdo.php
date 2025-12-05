<?php
// Database setup script using PDO (more compatible)
$servername = "localhost";
$username = "root";
$password = "";

try {
    // Create connection using PDO
    $pdo = new PDO("mysql:host=$servername", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL successfully.<br>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS foodshare_db";
    $pdo->exec($sql);
    echo "Database 'foodshare_db' created successfully or already exists.<br>";
    
    // Select the database
    $pdo->exec("USE foodshare_db");
    
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
    
    $pdo->exec($sql);
    echo "Table 'users' created successfully or already exists.<br>";
    
    // Add new columns if they don't exist (for existing databases)
    try {
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'restaurant_name'");
        if ($checkColumns->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN restaurant_name VARCHAR(100) NULL AFTER role");
            $pdo->exec("ALTER TABLE users ADD COLUMN contact_no VARCHAR(20) NULL AFTER restaurant_name");
            $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT NULL AFTER contact_no");
            echo "Added donor-specific columns to 'users' table.<br>";
        }
    } catch (PDOException $e) {
        // Columns might already exist, ignore error
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
    
    $pdo->exec($sql);
    echo "Table 'food_items' created successfully or already exists.<br>";
    
    // Check if tables have any data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Number of users in database: " . $row['count'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM food_items");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Number of food items in database: " . $row['count'] . "<br>";
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<a href='food.html'>Go to Home Page</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
