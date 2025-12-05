<?php
session_start();
include 'db_connect.php';

// Require login and admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_page.php?error=Admin access required");
    exit();
}

// Fetch users
$usersStmt = $conn->prepare("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
$usersStmt->execute();
$users = $usersStmt->get_result();

// Role counts
$counts = ['donor' => 0, 'receiver' => 0, 'admin' => 0];
$users->data_seek(0);
while ($row = $users->fetch_assoc()) {
    if (isset($counts[$row['role']])) {
        $counts[$row['role']]++;
    }
}
$users->data_seek(0);

$adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: #f5f6f8; color: #222; }
        .header { background: #fff; padding: 1rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .6rem; }
        .brand img { height: 40px; }
        .brand h1 { font-size: 1.25rem; color: #005792; margin: 0; }
        .admin-info { color: #555; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border-radius: 12px; padding: 1.25rem; }
        .stat h3 { margin: 0 0 .25rem 0; font-size: 1.75rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { margin: 0; font-size: 1.1rem; color: #333; }
        .card-body { padding: 1rem 1.25rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .75rem .6rem; border-bottom: 1px solid #eee; text-align: left; font-size: .95rem; }
        th { color: #666; font-weight: 600; }
        tr:hover td { background: #fafafa; }
        .role-select { padding: .4rem .5rem; border: 1px solid #ddd; border-radius: 6px; }
        .btn { padding: .45rem .75rem; border: 0; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn.primary { background: #00b8a9; color: #fff; }
        .btn.warn { background: #ffc107; color: #212529; }
        .btn.danger { background: #dc3545; color: #fff; }
        .btn.muted { background: #e9ecef; color: #495057; cursor: default; }
        .msg { margin-bottom: 1rem; padding: .8rem 1rem; border-radius: 8px; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .actions { display: flex; gap: .5rem; align-items: center; }
        .top-actions { display: flex; gap: .5rem; }
        a.logout { text-decoration: none; color: #dc3545; font-weight: 600; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <img src="./OIP (4).jpg" alt="FoodShare" />
            <h1>FoodShare Admin</h1>
        </div>
        <div class="admin-info">
            <?php echo htmlspecialchars($adminEmail); ?> · <a class="logout" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="msg success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="msg error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><h3><?php echo (int)$counts['donor']; ?></h3><div>Donors</div></div>
            <div class="stat"><h3><?php echo (int)$counts['receiver']; ?></h3><div>Receivers</div></div>
            <div class="stat"><h3><?php echo (int)$counts['admin']; ?></h3><div>Admins</div></div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Users</h2>
                <div class="top-actions">
                    <a class="btn primary" href="manage_delivery_applications.php">Manage Delivery Applications</a>
                    <a class="btn primary" href="dashboard.php">Go to Dashboards</a>
                </div>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $users->data_seek(0); while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <form action="update_user_role.php" method="POST" style="display:flex; gap:.5rem; align-items:center;">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>" />
                                        <select class="role-select" name="role">
                                            <option value="donor" <?php echo $u['role']==='donor'?'selected':''; ?>>Donor</option>
                                            <option value="receiver" <?php echo $u['role']==='receiver'?'selected':''; ?>>Receiver</option>
                                            <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                        </select>
                                        <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                            <button class="btn muted" type="button" title="Cannot change your own role">Update</button>
                                        <?php else: ?>
                                            <button class="btn warn" type="submit">Update</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>
                                <td>
                                    <form action="delete_user.php" method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>" />
                                        <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                                            <button class="btn muted" type="button" title="Cannot delete your own account">Delete</button>
                                        <?php else: ?>
                                            <button class="btn danger" type="submit">Delete</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
