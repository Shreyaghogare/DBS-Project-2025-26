<?php
session_start();
include 'db_connect_delivery.php';
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

$successMessage = '';
$errorMessage = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['application_id']) && isset($_POST['status'])) {
        $appId = (int)$_POST['application_id'];
        $status = $_POST['status'];
        
        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $stmt = $delivery_conn->prepare("UPDATE delivery_applications SET status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('si', $status, $appId);
                if ($stmt->execute()) {
                    $successMessage = 'Application status updated successfully.';
                } else {
                    $errorMessage = 'Failed to update application status.';
                }
                $stmt->close();
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['application_id'])) {
        $appId = (int)$_POST['application_id'];
        $stmt = $delivery_conn->prepare("DELETE FROM delivery_applications WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $appId);
            if ($stmt->execute()) {
                $successMessage = 'Application deleted successfully.';
            } else {
                $errorMessage = 'Failed to delete application.';
            }
            $stmt->close();
        }
    }
}

// Get filter status
$filterStatus = $_GET['status'] ?? 'all';
$statusFilter = '';
if ($filterStatus !== 'all' && in_array($filterStatus, ['pending', 'approved', 'rejected'])) {
    $statusFilter = "WHERE status = '" . $delivery_conn->real_escape_string($filterStatus) . "'";
}

// Fetch applications
$query = "SELECT * FROM delivery_applications $statusFilter ORDER BY created_at DESC";
$applications = $delivery_conn->query($query);

// Get statistics
$statsQuery = "SELECT status, COUNT(*) as count FROM delivery_applications GROUP BY status";
$statsResult = $delivery_conn->query($statsQuery);
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = (int)$row['count'];
    $stats['total'] += (int)$row['count'];
}

$adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Delivery Applications</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: #f5f6f8; color: #222; }
        .header { background: #fff; padding: 1rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .6rem; }
        .brand img { height: 40px; }
        .brand h1 { font-size: 1.25rem; color: #005792; margin: 0; }
        .admin-info { color: #555; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border-radius: 12px; padding: 1.25rem; }
        .stat h3 { margin: 0 0 .25rem 0; font-size: 1.75rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { margin: 0; font-size: 1.1rem; color: #333; }
        .card-body { padding: 1rem 1.25rem; overflow-x: auto; }
        .filters { display: flex; gap: .5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .filter-btn { padding: .5rem 1rem; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; text-decoration: none; color: #333; font-size: .9rem; }
        .filter-btn.active { background: #005792; color: #fff; border-color: #005792; }
        .filter-btn:hover { background: #f0f0f0; }
        .filter-btn.active:hover { background: #003d5c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .75rem .6rem; border-bottom: 1px solid #eee; text-align: left; font-size: .9rem; }
        th { color: #666; font-weight: 600; background: #f8f9fa; }
        tr:hover td { background: #fafafa; }
        .status-badge { padding: .25rem .6rem; border-radius: 12px; font-size: .8rem; font-weight: 600; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .btn { padding: .4rem .75rem; border: 0; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: .85rem; }
        .btn.success { background: #28a745; color: #fff; }
        .btn.danger { background: #dc3545; color: #fff; }
        .btn.warn { background: #ffc107; color: #212529; }
        .btn:hover { opacity: 0.9; }
        .msg { margin-bottom: 1rem; padding: .8rem 1rem; border-radius: 8px; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .top-actions { display: flex; gap: .5rem; }
        a.logout { text-decoration: none; color: #dc3545; font-weight: 600; }
        .back-link { text-decoration: none; color: #005792; font-weight: 600; }
        .message-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .message-cell:hover { white-space: normal; overflow: visible; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <img src="./OIP (4).jpg" alt="FoodShare" />
            <h1>FoodShare Admin - Delivery Applications</h1>
        </div>
        <div class="admin-info">
            <?php echo htmlspecialchars($adminEmail); ?> · <a class="logout" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="top-actions" style="margin-bottom: 1rem;">
            <a class="back-link" href="admin_dashboard.php">&larr; Back to Admin Dashboard</a>
        </div>

        <?php if ($successMessage): ?>
            <div class="msg success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="msg error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><h3><?php echo $stats['total']; ?></h3><div>Total Applications</div></div>
            <div class="stat"><h3><?php echo $stats['pending']; ?></h3><div>Pending</div></div>
            <div class="stat"><h3><?php echo $stats['approved']; ?></h3><div>Approved</div></div>
            <div class="stat"><h3><?php echo $stats['rejected']; ?></h3><div>Rejected</div></div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Delivery Applications</h2>
            </div>
            <div class="card-body">
                <div class="filters">
                    <a href="?status=all" class="filter-btn <?php echo $filterStatus === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=pending" class="filter-btn <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=approved" class="filter-btn <?php echo $filterStatus === 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="?status=rejected" class="filter-btn <?php echo $filterStatus === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>

                <?php if ($applications && $applications->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>City</th>
                                <th>Experience</th>
                                <th>Availability</th>
                                <th>Transport</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($app = $applications->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int)$app['id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                                    <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($app['city']); ?></td>
                                    <td><?php echo htmlspecialchars($app['experience']); ?></td>
                                    <td><?php echo htmlspecialchars($app['availability']); ?></td>
                                    <td><?php echo htmlspecialchars($app['transport']); ?></td>
                                    <td class="message-cell" title="<?php echo htmlspecialchars($app['message'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($app['message'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($app['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="application_id" value="<?php echo (int)$app['id']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <select name="status" onchange="this.form.submit()" style="padding: .3rem; border: 1px solid #ddd; border-radius: 4px; font-size: .85rem;">
                                                    <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $app['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this application? This cannot be undone.');">
                                                <input type="hidden" name="application_id" value="<?php echo (int)$app['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No applications found.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

