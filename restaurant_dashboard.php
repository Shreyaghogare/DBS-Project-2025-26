<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id'])) {
    header("Location: donor_login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: donor_login.php?error=Please login with a donor account");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_email = $_SESSION['email'];

// Get restaurant information (assuming user is a restaurant owner)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get food items for this restaurant with reservation info
$stmt = $conn->prepare("
    SELECT 
        fi.*,
        r.id as reservation_id,
        r.receiver_id,
        r.reserved_at,
        u.email as receiver_email
    FROM food_items fi
    LEFT JOIN reservations r ON fi.id = r.food_id AND r.status = 'reserved'
    LEFT JOIN users u ON r.receiver_id = u.id
    WHERE fi.user_id = ? 
    ORDER BY fi.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$food_items = $stmt->get_result();

// Get restaurant name and address from database
$restaurant_name = !empty($user['restaurant_name']) ? $user['restaurant_name'] : "Restaurant " . explode('@', $user_email)[0];
$restaurant_address = !empty($user['address']) ? $user['address'] : '';
$contact_no = !empty($user['contact_no']) ? $user['contact_no'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - FoodShare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .logo h1 {
            color: #667eea;
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info span {
            color: #666;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .restaurant-info {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .restaurant-info h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .restaurant-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            opacity: 0.9;
        }

        .add-food-btn {
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            margin: 1rem 0;
            transition: background 0.3s;
        }

        .add-food-btn:hover {
            background: #218838;
        }

        .food-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .food-section h3 {
            color: #667eea;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .food-card {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .food-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .food-content {
            padding: 1.5rem;
        }

        .food-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .food-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .food-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .food-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #555;
        }

        .food-detail i {
            color: #667eea;
        }

        .food-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-reserved {
            background: #fff3cd;
            color: #856404;
        }

        .status-donated {
            background: #d1ecf1;
            color: #0c5460;
        }

        .food-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .edit-btn {
            background: #ffc107;
            color: #212529;
        }

        .edit-btn:hover {
            background: #e0a800;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .no-food {
            text-align: center;
            color: #666;
            padding: 3rem;
            font-size: 1.1rem;
        }

        .no-food i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
            }

            .restaurant-stats {
                grid-template-columns: 1fr;
            }

            .food-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="./OIP (4).jpg" alt="FoodShare Logo">
            <h1>FoodShare</h1>
        </div>
        <div class="user-info">
            <div style="text-align: right;">
                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($restaurant_name); ?></div>
                <?php if (!empty($restaurant_address)): ?>
                    <div style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($restaurant_address); ?></div>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <!-- Restaurant Information -->
        <div class="restaurant-info">
            <?php
            // Display success/error messages
            if (isset($_GET['success'])) {
                echo '<div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
            <h2><?php echo htmlspecialchars($restaurant_name); ?></h2>
            <?php if (!empty($restaurant_address)): ?>
                <p style="margin: 0.5rem 0; color: #666;">📍 Address: <?php echo htmlspecialchars($restaurant_address); ?></p>
            <?php endif; ?>
            <?php if (!empty($contact_no)): ?>
                <p style="margin: 0.5rem 0; color: #666;">📞 Contact: <?php echo htmlspecialchars($contact_no); ?></p>
            <?php endif; ?>
            <p style="margin: 0.5rem 0; color: #666;">✉️ Email: <?php echo htmlspecialchars($user_email); ?></p>
            <p style="margin: 0.5rem 0; color: #666;">Role: <?php echo ucfirst($user_role); ?></p>
            
            <div class="restaurant-stats">
                <?php
                // Count different types of food items
                $total_food = $food_items->num_rows;
                $available_food = 0;
                $reserved_food = 0;
                $donated_food = 0;
                
                // Reset the result pointer
                $food_items->data_seek(0);
                while ($item = $food_items->fetch_assoc()) {
                    switch ($item['status']) {
                        case 'available':
                            $available_food++;
                            break;
                        case 'reserved':
                            $reserved_food++;
                            break;
                        case 'donated':
                            $donated_food++;
                            break;
                    }
                }
                ?>
                
                <div class="stat-card">
                    <h3><?php echo $total_food; ?></h3>
                    <p>Total Food Items</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $available_food; ?></h3>
                    <p>Available</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $reserved_food; ?></h3>
                    <p>Reserved</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $donated_food; ?></h3>
                    <p>Donated</p>
                </div>
            </div>
        </div>

        <!-- Food Items Section -->
        <div class="food-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3>All Food Items</h3>
                <button class="add-food-btn" onclick="showAddFoodForm()">+ Add New Food Item</button>
            </div>

            <?php if ($total_food > 0): ?>
                <div class="food-grid">
                    <?php
                    // Reset the result pointer and display food items
                    $food_items->data_seek(0);
                    while ($item = $food_items->fetch_assoc()):
                    ?>
                        <div class="food-card">
                            <div class="food-image">
                                🍽️
                            </div>
                            <div class="food-content">
                                <div class="food-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                                <div class="food-description"><?php echo htmlspecialchars($item['description']); ?></div>
                                
                                <div class="food-details">
                                    <div class="food-detail">
                                        <span>📦</span>
                                        <span><?php echo $item['quantity']; ?> servings</span>
                                    </div>
                                    <div class="food-detail">
                                        <span>📅</span>
                                        <span><?php echo date('M j, Y', strtotime($item['expiry_date'])); ?></span>
                                    </div>
                                    <div class="food-detail">
                                        <span>🏷️</span>
                                        <span><?php echo htmlspecialchars($item['category']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="food-status status-<?php echo $item['status']; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </div>
                                
                                <?php if ($item['status'] === 'reserved' && !empty($item['reservation_id'])): ?>
                                    <div style="background: #fff3cd; padding: 0.6rem; border-radius: 8px; margin-top: 0.8rem; border-left: 3px solid #ffc107;">
                                        <div style="font-weight: 600; color: #856404; margin-bottom: 0.3rem;">📋 Reserved by:</div>
                                        <div style="color: #856404; font-size: 0.9rem;">
                                            <?php if (!empty($item['receiver_email'])): ?>
                                                <div>✉️ <?php echo htmlspecialchars($item['receiver_email']); ?></div>
                                            <?php endif; ?>
                                            <div>⏰ <?php echo date('M j, Y g:i A', strtotime($item['reserved_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="food-actions">
                                    <button class="action-btn edit-btn" onclick="editFood(<?php echo $item['id']; ?>)">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteFood(<?php echo $item['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-food">
                    <div>🍽️</div>
                    <p>No food items added yet. Click "Add New Food Item" to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showAddFoodForm() {
            // Create a simple form for adding food items
            const form = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                    <div style="background: white; padding: 2rem; border-radius: 15px; width: 90%; max-width: 500px;">
                        <h3 style="margin-bottom: 1rem; color: #667eea;">Add New Food Item</h3>
                        <form action="add_food.php" method="POST">
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem;">Food Name:</label>
                                <input type="text" name="food_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem;">Description:</label>
                                <textarea name="description" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; height: 80px;"></textarea>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem;">Quantity (servings):</label>
                                <input type="number" name="quantity" required min="1" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem;">Expiry Date:</label>
                                <input type="date" name="expiry_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem;">Category:</label>
                                <select name="category" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="">Select Category</option>
                                    <option value="Main Course">Main Course</option>
                                    <option value="Appetizer">Appetizer</option>
                                    <option value="Dessert">Dessert</option>
                                    <option value="Beverage">Beverage</option>
                                    <option value="Salad">Salad</option>
                                    <option value="Soup">Soup</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" style="flex: 1; padding: 0.8rem; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Add Food Item</button>
                                <button type="button" onclick="closeForm()" style="flex: 1; padding: 0.8rem; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', form);
        }

        function closeForm() {
            const form = document.querySelector('div[style*="position: fixed"]');
            if (form) form.remove();
        }

        function editFood(id) {
            alert('Edit functionality will be implemented. Food ID: ' + id);
        }

        function deleteFood(id) {
            if (confirm('Are you sure you want to delete this food item?')) {
                window.location.href = 'delete_food.php?id=' + id;
            }
        }
    </script>
</body>
</html>
