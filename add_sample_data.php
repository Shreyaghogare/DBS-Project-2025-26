<?php
// Script to add sample food data for testing
include 'db_connect.php';

// Check if database connection is successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Sample food items data
$sample_foods = [
    [
        'restaurant_name' => 'Restaurant Demo',
        'food_name' => 'Chicken Biryani',
        'description' => 'Aromatic basmati rice with tender chicken pieces, cooked with traditional spices and herbs.',
        'quantity' => 15,
        'expiry_date' => date('Y-m-d', strtotime('+2 days')),
        'category' => 'Main Course',
        'user_id' => 1
    ],
    [
        'restaurant_name' => 'Restaurant Demo',
        'food_name' => 'Vegetable Curry',
        'description' => 'Fresh mixed vegetables cooked in a rich tomato and coconut curry sauce.',
        'quantity' => 12,
        'expiry_date' => date('Y-m-d', strtotime('+1 day')),
        'category' => 'Main Course',
        'user_id' => 1
    ],
    [
        'restaurant_name' => 'Restaurant Demo',
        'food_name' => 'Chocolate Cake',
        'description' => 'Rich and moist chocolate cake with chocolate ganache frosting.',
        'quantity' => 8,
        'expiry_date' => date('Y-m-d', strtotime('+3 days')),
        'category' => 'Dessert',
        'user_id' => 1
    ],
    [
        'restaurant_name' => 'Restaurant Demo',
        'food_name' => 'Caesar Salad',
        'description' => 'Fresh romaine lettuce with parmesan cheese, croutons, and caesar dressing.',
        'quantity' => 10,
        'expiry_date' => date('Y-m-d', strtotime('+1 day')),
        'category' => 'Salad',
        'user_id' => 1
    ],
    [
        'restaurant_name' => 'Restaurant Demo',
        'food_name' => 'Tomato Soup',
        'description' => 'Creamy tomato soup made with fresh tomatoes and herbs.',
        'quantity' => 6,
        'expiry_date' => date('Y-m-d', strtotime('+2 days')),
        'category' => 'Soup',
        'user_id' => 1
    ]
];

echo "<h1>Adding Sample Food Data</h1>";

// Check if user with ID 1 exists
$result = $conn->query("SELECT id FROM users WHERE id = 1");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>Error: No user with ID 1 found. Please create a user account first.</p>";
    echo "<p><a href='food.html'>Go to Home Page to Sign Up</a></p>";
    exit();
}

// Insert sample food items
$stmt = $conn->prepare("INSERT INTO food_items (restaurant_name, food_name, description, quantity, expiry_date, category, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

$added_count = 0;
foreach ($sample_foods as $food) {
    $stmt->bind_param("sssisss", $food['restaurant_name'], $food['food_name'], $food['description'], $food['quantity'], $food['expiry_date'], $food['category'], $food['user_id']);
    
    if ($stmt->execute()) {
        $added_count++;
        echo "<p style='color: green;'>✅ Added: " . $food['food_name'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to add: " . $food['food_name'] . " - " . $stmt->error . "</p>";
    }
}

$stmt->close();

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Successfully added <strong>$added_count</strong> food items to the database.</p>";
echo "<p><a href='restaurant_dashboard.php'>View Restaurant Dashboard</a></p>";
echo "<p><a href='food.html'>Go to Home Page</a></p>";

$conn->close();
?>
