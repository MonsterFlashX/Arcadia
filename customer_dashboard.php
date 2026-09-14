<?php
session_start();

if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit;
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard - Arcadia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/customer_auth.css">
</head>

<body>

<div class="dashboard-wrapper">
    <div class="dashboard-shell">

        <section class="dashboard-top">
            <div>
                <h1 class="dashboard-title">Welcome Back</h1>
                <p class="dashboard-subtitle">
                    <?= h($_SESSION["customer_name"]) ?>
                </p>
            </div>

            <a href="customer_logout.php" class="logout-btn">
                Logout
            </a>
        </section>

        <section class="dashboard-grid">

            <article class="dashboard-card">
                <h3>🎮 My Library</h3>
                <p>View and play your purchased games from your personal Arcadia library.</p>
                <a href="library.php">Open Library</a>
            </article>

            <article class="dashboard-card">
                <h3>🛒 Store</h3>
                <p>Browse new titles, add games to your cart, and continue shopping.</p>
                <a href="menu.php">Visit Store</a>
            </article>
            
            <article class="dashboard-card">
                <h3>📦 View Orders</h3>
                <p>View your order history, purchased products, and order details.</p>
                <a href="customer_orders.php">View Orders</a>
            </article>

            <article class="dashboard-card">
                <h3>👤 Profile</h3>
                <p>Manage your account settings and personal information.</p>
                <a href="customer_profile.php">View Profile</a>
            </article>

        </section>

    </div>
</div>

</body>
</html>