<?php
require_once "auth_check.php";
require_once "db.php";

$sql = "SELECT id, name, price, image FROM Products ORDER BY name";
$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$products = $result->fetch_all(MYSQLI_ASSOC);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Arcadia Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #0b0e14;
    color: white;
}

.admin-header {
    background: #05070d;
    padding: 25px 40px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.8rem;
    font-weight: bold;
    color: #00ffff;
}

.admin-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.admin-actions a {
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    background: #151b2b;
}

.admin-actions a.logout {
    background: #ff4d4d;
}

.admin-actions a:hover {
    opacity: 0.85;
}

.hero {
    padding: 45px 40px;
    text-align: center;
    background: linear-gradient(135deg, #101827, #151b2b);
}

.hero h1 {
    font-size: 2.4rem;
    margin-bottom: 10px;
}

.hero p {
    color: rgba(255,255,255,0.65);
}

.admin-nav {
    display: flex;
    justify-content: center;
    gap: 18px;
    padding: 18px;
    background: #111827;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.admin-nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    padding: 12px 18px;
    border-radius: 10px;
}

.admin-nav a:hover {
    background: #00ffff;
    color: #000;
}

.dashboard {
    max-width: 1250px;
    margin: 40px auto;
    padding: 0 25px;
}

.section-title {
    margin-bottom: 25px;
}

.section-title h2 {
    font-size: 1.8rem;
}

.section-title p {
    color: rgba(255,255,255,0.65);
    margin-top: 5px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
}

.product-card {
    background: #1a2033;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 18px;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.product-card:hover {
    transform: translateY(-6px);
    border-color: #00ffff;
}

.product-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 12px;
    background: #111;
}

.product-card h3 {
    margin-top: 15px;
    font-size: 1.15rem;
}

.product-card .price {
    margin-top: 8px;
    color: #00ffff;
    font-weight: bold;
}

.footer {
    margin-top: 60px;
    padding: 25px;
    text-align: center;
    background: #05070d;
    color: rgba(255,255,255,0.6);
}

@media (max-width: 700px) {
    .admin-header {
        flex-direction: column;
        gap: 15px;
    }

    .admin-nav {
        flex-direction: column;
        align-items: center;
    }
}
</style>
</head>

<body>

<header class="admin-header">
    <div class="logo">Arcadia Admin</div>

    <div class="admin-actions">
        <a href="menu.php">Back to Store</a>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>
</header>

<section class="hero">
    <h1>Admin Dashboard</h1>
    <p>Manage products, customers, orders and store activity.</p>
</section>

<nav class="admin-nav">
    <a href="admin_orders.php">Orders Admin</a>
    <a href="admin_customers.php">Customers Admin</a>
    <a href="admin_products.php">Products Admin</a>
    <a href="customers.php">Customer Info</a>
    <a href="orders.php">Order History</a>
</nav>

<main class="dashboard">

    <section class="section-title">
        <h2>Products Overview</h2>
        <p>All products currently available in your store.</p>
    </section>

    <section class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <img src="<?= h($product['image']) ?>" alt="<?= h($product['name']) ?>">

                <h3><?= h($product['name']) ?></h3>

                <p class="price">
                    <?= number_format((float)$product['price'], 0) ?> kr
                </p>
            </article>
        <?php endforeach; ?>
    </section>

</main>

<footer class="footer">
    &copy; <?= date("Y") ?> Arcadia. All rights reserved.
</footer>

</body>
</html>