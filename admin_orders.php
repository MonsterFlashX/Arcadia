<?php
require_once "auth_check.php";
require_once "csrf.php";
require_once "db.php";

$sql = "
SELECT
    o.id AS order_id,
    o.customer_id,
    o.order_date,
    o.status,
    o.total_amount,
    c.firstname,
    c.lastname,
    c.email,
    GROUP_CONCAT(
        CONCAT(orw.product_name, ' (', orw.quantity, ')')
        SEPARATOR ', '
    ) AS products,
    COALESCE(SUM(orw.quantity), 0) AS total_qty
FROM Orders o
LEFT JOIN Customers c ON c.id = o.customer_id
LEFT JOIN OrderRows orw ON orw.order_id = o.id
GROUP BY
    o.id,
    o.customer_id,
    o.order_date,
    o.status,
    o.total_amount,
    c.firstname,
    c.lastname,
    c.email
ORDER BY o.order_date DESC;
";

$result = $conn->query($sql);
if (!$result) {
    die("Database error: " . htmlspecialchars($conn->error));
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$statuses = ['Ordered', 'Packed', 'Shipped', 'Paid'];
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'order_deleted'): ?>
    <div class="notice success">Order deleted successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notice error">Something went wrong. Please try again.</div>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Arcadia Admin — Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root { --bg:#080d16; --panel:#111827; --panel2:#162033; --text:#f8fafc; --muted:#94a3b8; --accent:#00e5ff; --danger:#ff4d5e; --border:rgba(255,255,255,.10); }
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;background:radial-gradient(circle at top left,rgba(0,229,255,.12),transparent 35%),var(--bg);color:var(--text);min-height:100vh}
.layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
.sidebar{background:#07101d;border-right:1px solid var(--border);padding:26px 18px;position:sticky;top:0;height:100vh}
.brand{font-size:1.45rem;font-weight:800;color:var(--accent);margin-bottom:35px}
.brand span{display:block;color:var(--muted);font-size:.72rem;letter-spacing:2px;margin-top:4px}
.side-nav{display:flex;flex-direction:column;gap:10px}
.side-nav a{color:var(--text);text-decoration:none;padding:13px 14px;border-radius:12px}
.side-nav a:hover,.side-nav a.active{background:rgba(0,229,255,.12);color:var(--accent)}
.logout{margin-top:35px;display:block;text-align:center;color:#fff;text-decoration:none;border:1px solid rgba(255,77,94,.5);padding:12px;border-radius:12px}
.main{padding:30px}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}
.page-title h1{font-size:2rem}
.page-title p{color:var(--muted);margin-top:6px}
.back-btn{color:var(--accent);text-decoration:none;border:1px solid rgba(0,229,255,.35);padding:11px 16px;border-radius:12px}
.card{background:linear-gradient(180deg,var(--panel2),var(--panel));border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:0 18px 45px rgba(0,0,0,.35);overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:980px}
th{color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:14px;border-bottom:1px solid var(--border)}
td{padding:16px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.muted{color:var(--muted);font-size:.9rem}
.money{color:var(--accent);font-weight:800}
.status-select{background:#0f172a;color:var(--text);border:1px solid var(--border);border-radius:10px;padding:8px}
.btn{border:none;border-radius:9px;padding:9px 12px;font-weight:700;cursor:pointer}
.btn-update{background:rgba(0,229,255,.15);color:var(--accent);border:1px solid rgba(0,229,255,.35)}
.btn-delete{background:rgba(255,77,94,.15);color:#ff9aa5;border:1px solid rgba(255,77,94,.35);margin-left:6px}
.footer{color:var(--muted);padding:24px 0}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{position:relative;height:auto}.main{padding:20px}.topbar{flex-direction:column;align-items:flex-start;gap:15px}}
.notice {
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 12px;
    font-weight: bold;
}

.notice.success {
    background: rgba(34,197,94,.15);
    color: #86efac;
    border: 1px solid rgba(34,197,94,.35);
}

.notice.error {
    background: rgba(255,77,94,.15);
    color: #ff9aa5;
    border: 1px solid rgba(255,77,94,.35);
}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="brand">Arcadia <span>ADMIN PANEL</span></div>
    <nav class="side-nav">
        <a href="admin.php">Dashboard</a>
        <a class="active" href="admin_orders.php">Orders</a>
        <a href="admin_customers.php">Customers Admin</a>
        <a href="customers.php">Customer Info</a>
        <a href="orders.php">Order History</a>
    </nav>
    <a class="logout" href="admin_logout.php">Logout</a>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">
            <h1>Admin — Orders</h1>
            <p>View, update, and manage all customer orders.</p>
        </div>
        <a class="back-btn" href="admin.php">← Back to Admin Panel</a>
    </div>

    <section class="card">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Customer ID</th>
                    <th>Products</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                $customerName = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                if ($customerName === '') $customerName = 'Unknown';
                ?>
                <tr>
                    <td>#<?= (int)$row['order_id'] ?></td>
                    <td>
                        <strong><?= h($customerName) ?></strong><br>
                        <span class="muted"><?= h($row['email']) ?></span>
                    </td>
                    <td><?= (int)$row['customer_id'] ?></td>
                    <td><?= $row['products'] ? h($row['products']) : '<span class="muted">No items</span>' ?></td>
                    <td><?= (int)$row['total_qty'] ?></td>
                    <td>
                        <form method="POST" action="update_order.php">
                                <?= csrf_input() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['order_id'] ?>">
                            <select class="status-select" name="status">
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= h($st) ?>" <?= $row['status'] === $st ? 'selected' : '' ?>>
                                        <?= h($st) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                    </td>
                    <td><?= date('Y-m-d H:i', strtotime($row['order_date'])) ?></td>
                    <td class="money"><?= number_format((float)$row['total_amount'], 0) ?> kr</td>
                    <td>
                            <button class="btn btn-update" type="submit">Update</button>
                        </form>

                        <form method="POST" action="delete_order.php" style="display:inline" onsubmit="return confirm('Delete this order?');">
                                <?= csrf_input() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['order_id'] ?>">
                            <button class="btn btn-delete" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <footer class="footer">&copy; <?= date('Y') ?> Arcadia. All rights reserved.</footer>
</main>
</div>
</body>
</html>