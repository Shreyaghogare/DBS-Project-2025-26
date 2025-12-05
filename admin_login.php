<?php
session_start();

// If user is already logged in, log them out first
if (isset($_SESSION['user_id'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the login page
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FoodShare</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #ff6a00, #ee0979); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo img { height: 60px; }
        .logo h1 { color: #333; font-size: 26px; margin-top: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        .alert { padding: 10px; margin-bottom: 16px; border-radius: 6px; font-size: 14px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 16px; }
        label { display: block; color: #444; margin-bottom: 6px; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; }
        input:focus { outline: none; border-color: #ee0979; }
        .login-btn { width: 100%; padding: 12px; background: #ee0979; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform .2s; }
        .login-btn:hover { transform: translateY(-1px); }
        .back-home { text-align: center; margin-top: 14px; }
        .back-home a { color: #ee0979; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="./OIP (4).jpg" alt="FoodShare Logo" />
            <h1>Admin Login</h1>
        </div>
        <p class="subtitle">Administrator access to manage users and the platform.</p>

        <?php if (isset($_GET['error'])) { echo '<div class="alert alert-error">' . htmlspecialchars($_GET['error']) . '</div>'; } ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="redirect" value="admin_login" />
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required />
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required />
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="back-home">
            <a href="food.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>
