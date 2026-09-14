<?php
require_once "auth_check.php";
require_once "csrf.php";
require_once "db.php";
csrf_verify_request();
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getAllCustomers() {
    global $conn;

    $sql = "SELECT * FROM Customers ORDER BY id ASC";
    $result = $conn->query($sql);

    if (!$result) {
        die("Database error: " . h($conn->error));
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateCustomer($id, $firstname, $lastname, $telephone, $address, $postal_code, $city, $email) {
    global $conn;

    $stmt = $conn->prepare("
        UPDATE Customers
        SET firstname=?, lastname=?, telephone=?, address=?, postal_code=?, city=?, email=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssssi",
        $firstname,
        $lastname,
        $telephone,
        $address,
        $postal_code,
        $city,
        $email,
        $id
    );

    $stmt->execute();
}

function deleteCustomer($id) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM Customers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    updateCustomer(
        $_POST['id'],
        $_POST['firstname'],
        $_POST['lastname'],
        $_POST['telephone'],
        $_POST['address'],
        $_POST['postal_code'],
        $_POST['city'],
        $_POST['email']
    );

    header("Location: admin_customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    deleteCustomer($_POST['id']);

    header("Location: admin_customers.php");
    exit;
}

$customers = getAllCustomers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Arcadia Admin — Customers</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{--bg:#080d16;--panel:#111827;--panel2:#162033;--text:#f8fafc;--muted:#94a3b8;--accent:#00e5ff;--danger:#ff4d5e;--border:rgba(255,255,255,.10)}
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
table{width:100%;border-collapse:collapse;min-width:1150px}
th{color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:14px;border-bottom:1px solid var(--border)}
td{padding:14px;border-bottom:1px solid var(--border);vertical-align:middle}
input{width:100%;background:#0f172a;color:var(--text);border:1px solid var(--border);border-radius:9px;padding:9px;outline:none}
input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,229,255,.10)}
.btn{border:none;border-radius:9px;padding:9px 12px;font-weight:700;cursor:pointer}
.btn-update{background:rgba(0,229,255,.15);color:var(--accent);border:1px solid rgba(0,229,255,.35)}
.btn-delete{background:rgba(255,77,94,.15);color:#ff9aa5;border:1px solid rgba(255,77,94,.35);margin-left:6px}
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
        <a class="active" href="admin_customers.php">Customers Admin</a>
        <a href="customers.php">Customer Info</a>
        <a href="orders.php">Order History</a>
    </nav>

    <a class="logout" href="admin_logout.php">Logout</a>
</aside>

<main class="main">
    <div class="topbar">
        <div class="page-title">
            <h1>Admin — Customer Management</h1>
            <p>Update customer details and manage customer records.</p>
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
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($customers as $cust): ?>
                <tr>
                    <form method="POST">
                            <?= csrf_input() ?>
                        <td>
                            #<?= (int)$cust['id'] ?>
                            <input type="hidden" name="id" value="<?= (int)$cust['id'] ?>">
                        </td>

                        <td><input type="text" name="firstname" value="<?= h($cust['firstname'] ?? '') ?>"></td>
                        <td><input type="text" name="lastname" value="<?= h($cust['lastname'] ?? '') ?>"></td>
                        <td><input type="text" name="telephone" value="<?= h($cust['telephone'] ?? '') ?>"></td>
                        <td><input type="text" name="address" value="<?= h($cust['address'] ?? '') ?>"></td>
                        <td><input type="text" name="postal_code" value="<?= h($cust['postal_code'] ?? '') ?>"></td>
                        <td><input type="text" name="city" value="<?= h($cust['city'] ?? '') ?>"></td>
                        <td><input type="email" name="email" value="<?= h($cust['email'] ?? '') ?>"></td>

                        <td>
                            <button class="btn btn-update" type="submit" name="update">Update</button>
                            <button class="btn btn-delete" type="submit" name="delete" onclick="return confirm('Delete this customer?')">Delete</button>
                        </td>
                    </form>
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