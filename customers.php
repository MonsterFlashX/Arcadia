<?php
require_once "auth_check.php";
require_once "db.php";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$result = $conn->query("SELECT * FROM Customers ORDER BY id ASC");

if (!$result) {
    die("Database error: " . h($conn->error));
}

$customers = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Arcadia — Customers</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{--bg:#080d16;--panel:#111827;--panel2:#162033;--text:#f8fafc;--muted:#94a3b8;--accent:#00e5ff;--border:rgba(255,255,255,.10)}
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
table{width:100%;border-collapse:collapse;min-width:950px}
th{color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:14px;border-bottom:1px solid var(--border)}
td{padding:16px 14px;border-bottom:1px solid var(--border)}
.email{color:var(--accent)}
.footer{color:var(--muted);padding:24px 0}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{position:relative;height:auto}.main{padding:20px}.topbar{flex-direction:column;align-items:flex-start;gap:15px}}
</style>
</head>

<body>
<div class="layout">

<aside class="sidebar">
    <div class="brand">Arcadia <span>ADMIN PANEL</span></div>

    <nav class="side-nav">
        <a href="admin.php">Dashboard</a>
        <a href="admin_orders.php">Orders</a>
        <a href="admin_customers.php">Customers Admin</a>
        <a class="active" href="customers.php">Customer Info</a>
        <a href="orders.php">Order History</a>
    </nav>

    <a class="logout" href="admin_logout.php">Logout</a>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">
            <h1>Customers</h1>
            <p>Read-only overview of all registered customers.</p>
        </div>

        <a class="back-btn" href="admin.php">← Back to Admin Panel</a>
    </div>

    <section class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Firstname</th>
                    <th>Lastname</th>
                    <th>Telephone</th>
                    <th>Address</th>
                    <th>Postal Code</th>
                    <th>City</th>
                    <th>Email</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= h($c['firstname']) ?></td>
                    <td><?= h($c['lastname']) ?></td>
                    <td><?= h($c['telephone']) ?></td>
                    <td><?= h($c['address']) ?></td>
                    <td><?= h($c['postal_code']) ?></td>
                    <td><?= h($c['city']) ?></td>
                    <td class="email"><?= h($c['email']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <footer class="footer">&copy; <?= date('Y') ?> Arcadia. All rights reserved.</footer>
</main>
</div>
</body>
</html>