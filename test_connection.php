<?php
// Simple test page to check if everything is working
echo "<h1>FoodShare System Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Testing Database Connection</h2>";
include 'db_connect.php';

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    echo "<p><strong>Solution:</strong> Make sure XAMPP MySQL is running and the credentials in db_connect.php are correct.</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
}

// Test 2: Check if database exists
echo "<h2>2. Testing Database Existence</h2>";
$result = $conn->query("SHOW DATABASES LIKE 'foodshare_db'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Database 'foodshare_db' exists!</p>";
    
    // Test 3: Check if users table exists
    echo "<h2>3. Testing Users Table</h2>";
    $conn->select_db("foodshare_db");
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Users table exists!</p>";
        
        // Test 4: Check table structure
        echo "<h2>4. Table Structure</h2>";
        $result = $conn->query("DESCRIBE users");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test 5: Check if there are any users
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<p>Number of users in database: <strong>" . $row['count'] . "</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist!</p>";
        echo "<p><strong>Solution:</strong> Run <a href='setup_database.php'>setup_database.php</a> to create the table.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Database 'foodshare_db' does not exist!</p>";
    echo "<p><strong>Solution:</strong> Run <a href='setup_database.php'>setup_database.php</a> to create the database and table.</p>";
}

// Test 6: File existence check
echo "<h2>5. File Existence Check</h2>";
$files = ['signup.php', 'login.php', 'login_page.php', 'dashboard.php', 'food.html'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If database/table issues: <a href='setup_database.php'>Run Database Setup</a></li>";
echo "<li>If everything is green: <a href='food.html'>Go to Home Page</a></li>";
echo "<li>Test signup: <a href='food.html'>Try signing up</a></li>";
echo "<li>Test login: <a href='login_page.php'>Try logging in</a></li>";
echo "</ol>";

$conn->close();
?>
