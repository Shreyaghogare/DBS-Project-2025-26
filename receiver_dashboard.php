<?php
session_start();
include 'db_connect.php';

// Require login and receiver role
if (!isset($_SESSION['user_id'])) {
    header("Location: receiver_login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receiver') {
    header("Location: receiver_login.php?error=Please login with a receiver account");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_email = $_SESSION['email'];

// Fetch available food items with restaurant information
$stmt = $conn->prepare("
    SELECT 
        fi.id, 
        fi.restaurant_name, 
        fi.food_name, 
        fi.description, 
        fi.quantity, 
        fi.expiry_date, 
        fi.category, 
        fi.status, 
        fi.created_at,
        fi.user_id,
        u.restaurant_name as donor_restaurant_name,
        u.address as restaurant_address,
        u.contact_no as restaurant_contact
    FROM food_items fi
    LEFT JOIN users u ON fi.user_id = u.id
    WHERE fi.status = 'available' 
    ORDER BY fi.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

// Fetch reserved food items for this receiver
$reservedStmt = $conn->prepare("
    SELECT 
        fi.id, 
        fi.restaurant_name, 
        fi.food_name, 
        fi.description, 
        fi.quantity, 
        fi.expiry_date, 
        fi.category, 
        fi.status, 
        fi.created_at,
        r.reserved_at,
        u.restaurant_name as donor_restaurant_name,
        u.address as restaurant_address,
        u.contact_no as restaurant_contact
    FROM reservations r
    INNER JOIN food_items fi ON r.food_id = fi.id
    LEFT JOIN users u ON fi.user_id = u.id
    WHERE r.receiver_id = ? AND r.status = 'reserved'
    ORDER BY r.reserved_at DESC
");
$reservedStmt->bind_param("i", $user_id);
$reservedStmt->execute();
$reservedResult = $reservedStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiver Dashboard - FoodShare</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: .5rem; }
        .logo img { height: 40px; }
        .logo h1 { color: #005792; font-size: 1.5rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #dc3545; color: #fff; padding: .5rem 1rem; border: 0; border-radius: 6px; text-decoration: none; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .intro { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); overflow: hidden; transition: transform .2s, box-shadow .2s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.12); }
        .thumb { height: 180px; display: flex; align-items: center; justify-content: center; background: linear-gradient(45deg, #00b8a9, #005792); color: #fff; font-size: 2.5rem; }
        .content { padding: 1rem 1.2rem; }
        .title { font-size: 1.2rem; font-weight: 700; margin-bottom: .3rem; }
        .desc { color: #555; margin-bottom: .6rem; line-height: 1.5; height: 60px; overflow: hidden; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; color: #444; margin-bottom: .6rem; }
        .tag { display: inline-block; padding: .25rem .6rem; border-radius: 999px; background: #eef6ff; color: #005792; font-size: .8rem; }
        .restaurant-info { background: #f8f9fa; padding: .8rem; border-radius: 8px; margin-bottom: .6rem; border-left: 3px solid #00b8a9; }
        .restaurant-info h4 { color: #005792; font-size: 1rem; margin-bottom: .4rem; font-weight: 600; }
        .restaurant-info p { color: #555; font-size: .85rem; margin: .2rem 0; line-height: 1.4; }
        .actions { display: flex; gap: .5rem; margin-top: .5rem; }
        .btn { flex: 1; padding: .6rem; border: 0; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .primary { background: #00b8a9; color: #fff; }
        .secondary { background: #f1f3f5; }
        .empty { text-align: center; color: #666; padding: 3rem; }
        .section-title { font-size: 1.5rem; color: #005792; margin: 2rem 0 1rem 0; font-weight: 600; }
        .reserved-badge { background: #fff3cd; color: #856404; padding: .3rem .8rem; border-radius: 20px; font-size: .85rem; font-weight: 600; display: inline-block; margin-bottom: .5rem; }
        .reserved-card { border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="./OIP (4).jpg" alt="FoodShare Logo" />
            <h1>FoodShare</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user_email); ?></span>
            <a class="logout-btn" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="container">
        <section class="intro">
            <h2>Available Food Near You</h2>
            <p>Browse donations from restaurants. Reserve items to pick up before they expire.</p>
            
            <?php
            // Display success/error messages
            if (isset($_GET['success'])) {
                echo '<div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-top: 1rem; border: 1px solid #c3e6cb;">✅ ' . htmlspecialchars($_GET['success']) . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-top: 1rem; border: 1px solid #f5c6cb;">❌ ' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </section>

        <?php if ($result->num_rows > 0): ?>
            <section class="grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <article class="card">
                        <div class="thumb">🍲</div>
                        <div class="content">
                            <div class="title"><?php echo htmlspecialchars($row['food_name']); ?></div>
                            <div class="desc"><?php echo htmlspecialchars($row['description']); ?></div>
                            
                            <!-- Restaurant Information -->
                            <div class="restaurant-info">
                                <h4>🏪 <?php echo htmlspecialchars(!empty($row['donor_restaurant_name']) ? $row['donor_restaurant_name'] : $row['restaurant_name']); ?></h4>
                                <?php if (!empty($row['restaurant_address'])): ?>
                                    <p>📍 <?php echo htmlspecialchars($row['restaurant_address']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($row['restaurant_contact'])): ?>
                                    <p>📞 <?php echo htmlspecialchars($row['restaurant_contact']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="meta">
                                <div>📦 <?php echo (int)$row['quantity']; ?> servings</div>
                                <div>📅 <?php echo date('M j, Y', strtotime($row['expiry_date'])); ?></div>
                                <div>🏷️ <span class="tag"><?php echo htmlspecialchars($row['category']); ?></span></div>
                            </div>
                            <div class="actions">
                                <form action="reserve_food.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="food_id" value="<?php echo (int)$row['id']; ?>" />
                                    <button class="btn primary" type="submit">Reserve</button>
                                </form>
                                <button class="btn secondary" onclick="alert('Contact details will be available after reservation')">Details</button>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php else: ?>
            <div class="empty">No food available at the moment. Please check back later.</div>
        <?php endif; ?>

        <!-- Reserved Food Section -->
        <?php if ($reservedResult->num_rows > 0): ?>
            <h2 class="section-title">My Reservations</h2>
            <section class="grid">
                <?php while ($reservedRow = $reservedResult->fetch_assoc()): ?>
                    <article class="card reserved-card">
                        <div class="thumb">🍲</div>
                        <div class="content">
                            <span class="reserved-badge">✅ Reserved</span>
                            <div class="title"><?php echo htmlspecialchars($reservedRow['food_name']); ?></div>
                            <div class="desc"><?php echo htmlspecialchars($reservedRow['description']); ?></div>
                            
                            <!-- Restaurant Information -->
                            <div class="restaurant-info">
                                <h4>🏪 <?php echo htmlspecialchars(!empty($reservedRow['donor_restaurant_name']) ? $reservedRow['donor_restaurant_name'] : $reservedRow['restaurant_name']); ?></h4>
                                <?php if (!empty($reservedRow['restaurant_address'])): ?>
                                    <p>📍 <?php echo htmlspecialchars($reservedRow['restaurant_address']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($reservedRow['restaurant_contact'])): ?>
                                    <p>📞 <?php echo htmlspecialchars($reservedRow['restaurant_contact']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="meta">
                                <div>📦 <?php echo (int)$reservedRow['quantity']; ?> servings</div>
                                <div>📅 <?php echo date('M j, Y', strtotime($reservedRow['expiry_date'])); ?></div>
                                <div>🏷️ <span class="tag"><?php echo htmlspecialchars($reservedRow['category']); ?></span></div>
                                <div>⏰ Reserved: <?php echo date('M j, Y g:i A', strtotime($reservedRow['reserved_at'])); ?></div>
                            </div>
                            <div class="actions">
                                <button class="btn secondary" style="width: 100%;" onclick="alert('Contact the restaurant using the details above to arrange pickup.')">Contact Restaurant</button>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
