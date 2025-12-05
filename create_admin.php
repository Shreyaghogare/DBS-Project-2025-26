<?php
session_start();
include 'db_connect.php';

// If an admin already exists, optionally block access for safety
$adminExists = false;
$check = $conn->query("SELECT 1 FROM users WHERE role='admin' LIMIT 1");
if ($check && $check->num_rows > 0) {
    $adminExists = true;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $conn->begin_transaction();

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $cleanupStmt = $conn->prepare("DELETE FROM users WHERE role='admin' AND email != ?");

        if (!$cleanupStmt) {
            $error = 'Failed to prepare admin cleanup.';
        } else {
            $cleanupStmt->bind_param('s', $email);
            if (!$cleanupStmt->execute()) {
                $error = 'Failed to remove existing admin accounts.';
            }
            $cleanupStmt->close();
        }

        $existingId = null;
        $existingUser = false;

        if (empty($error)) {
            $lookupStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            if (!$lookupStmt) {
                $error = 'Failed to prepare user lookup.';
            } else {
                $lookupStmt->bind_param('s', $email);
                if ($lookupStmt->execute()) {
                    $lookupStmt->bind_result($existingId);
                    if ($lookupStmt->fetch()) {
                        $existingUser = true;
                    }
                } else {
                    $error = 'Failed to check existing user account.';
                }
                $lookupStmt->close();
            }
        }

        if (empty($error) && $existingUser) {
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, role='admin' WHERE id = ?");
            if (!$updateStmt) {
                $error = 'Failed to prepare admin update.';
            } else {
                $updateStmt->bind_param('si', $hashed, $existingId);
                if ($updateStmt->execute()) {
                    $message = 'Existing user password reset and set as sole admin.';
                } else {
                    $error = 'Failed to reset existing admin user.';
                }
                $updateStmt->close();
            }
        } elseif (empty($error)) {
            $insertStmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
            if (!$insertStmt) {
                $error = 'Failed to prepare admin creation.';
            } else {
                $insertStmt->bind_param('ss', $email, $hashed);
                if ($insertStmt->execute()) {
                    $message = 'Admin user created and previous admins cleared.';
                } else {
                    $error = 'Failed to create admin user.';
                }
                $insertStmt->close();
            }
        }

        if (empty($error)) {
            $conn->commit();
            $adminExists = true;
        } else {
            $conn->rollback();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Admin - One-time Setup</title>
    <style>
        body { font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: #f5f6f8; margin: 0; padding: 0; }
        .container { max-width: 460px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.08); padding: 24px; }
        h1 { margin: 0 0 8px 0; font-size: 22px; color: #005792; }
        p.hint { color: #6c757d; margin: 0 0 16px 0; font-size: 14px; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; font-size: 14px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        label { display: block; font-weight: 600; margin: 10px 0 6px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        button { margin-top: 14px; width: 100%; padding: 12px; background: #00b8a9; border: 0; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer; }
        .note { margin-top: 14px; color: #6c757d; font-size: 13px; }
        .danger { color: #dc3545; font-weight: 600; }
        .footer-links { margin-top: 16px; font-size: 14px; }
        .footer-links a { color: #005792; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Setup</h1>
        <p class="hint">Create a new admin or promote an existing user by email.</p>

        <?php if (!empty($message)): ?>
            <div class="msg success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($adminExists): ?>
            <div class="msg" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;">An admin already exists. You can still create/promote another admin, but remember to delete this file afterwards.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Admin Email</label>
            <input type="email" id="email" name="email" placeholder="admin@example.com" required />

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="At least 6 characters" required />

            <button type="submit">Create/Promote Admin</button>
        </form>

        <p class="note"><span class="danger">Important:</span> For security, delete <code>create_admin.php</code> from the server after you finish setting up an admin.</p>
        <div class="footer-links">
            <a href="admin_login.php">Go to Admin Login</a> · <a href="food.html">Home</a>
        </div>
    </div>
</body>
</html>

