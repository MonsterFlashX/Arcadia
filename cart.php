<?php
session_start();
require_once "csrf.php";

csrf_verify_request();


function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$notice = "";

/* ===== CLEAR CART ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empty_cart'])) {

    $_SESSION['cart'] = [];

    $notice = "Cart cleared.";
}

/* ===== REMOVE SINGLE ITEM ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {

    $removeId = (int)$_POST['remove_item'];

    foreach ($_SESSION['cart'] as $key => $item) {

        if ((int)$item['id'] === $removeId) {

            unset($_SESSION['cart'][$key]);

            $_SESSION['cart'] = array_values($_SESSION['cart']);

            $notice = "Item removed from cart.";

            break;
        }
    }
}

$cartItems = $_SESSION['cart'];

$total = 0;

foreach ($cartItems as $item) {

    $price = (float)($item['price'] ?? 0);

    $qty = (int)($item['quantity'] ?? 1);

    $total += $price * $qty;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>GameStation Cart</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="css/style.css">

<style>

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #0b0e14;
    color: white;
}

.cart-header {
    background: #000;
    padding: 35px 20px;
    text-align: center;
}

.cart-header h1 {
    margin: 0;
    font-size: 2.2rem;
}

.cart-page {
    min-height: 70vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 20px;
}

.cart-card {
    width: 100%;
    max-width: 1000px;
    background: #1a2033;
    border-radius: 18px;
    padding: 35px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.45);
}

.cart-card h2 {
    text-align: center;
    margin-bottom: 25px;
}

.cart-message {
    text-align: center;
    color: #00ffff;
    font-weight: bold;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}

.cart-table th {
    background: #00ffff;
    color: #000;
    padding: 14px;
    text-align: left;
}

.cart-table td {
    padding: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    vertical-align: middle;
}

.cart-image {
    width: 90px;
    border-radius: 10px;
}

.cart-total {
    text-align: right;
    font-size: 1.3rem;
    font-weight: bold;
    margin: 20px 0;
}

.cart-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}

.cart-actions form {
    margin: 0;
}

.cart-btn {
    padding: 12px 22px;
    border: none;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
    font-size: 1rem;
}

.checkout-btn {
    background: #00ffff;
    color: #000;
}

.checkout-btn:hover {
    background: #00cccc;
}

.clear-btn {
    background: #ff4d4d;
    color: white;
}

.clear-btn:hover {
    background: #cc0000;
}

.remove-btn {
    background: orange;
    color: black;
}

.remove-btn:hover {
    background: darkorange;
}

.back-btn {
    background: #333;
    color: white;
}

.back-btn:hover {
    background: #555;
}

.empty-cart {
    text-align: center;
    font-size: 1.1rem;
}

footer {
    background: #000;
    text-align: center;
    padding: 20px;
    color: #aaa;
}

@media (max-width: 700px) {

    .cart-card {
        padding: 20px;
    }

    .cart-table {
        font-size: 0.9rem;
    }

    .cart-actions {
        flex-direction: column;
    }

    .cart-btn {
        width: 100%;
    }
}

</style>

</head>

<body>

<header class="cart-header">

    <h1>Arcadia Cart</h1>

</header>

<main class="cart-page">

<section class="cart-card">

<h2>Your Cart</h2>

<?php if (!empty($notice)): ?>

<p class="cart-message">
    <?= h($notice) ?>
</p>

<?php endif; ?>

<?php if (!empty($cartItems)): ?>

<table class="cart-table">

<thead>

<tr>
    <th>Image</th>
    <th>Product ID</th>
    <th>Name</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Subtotal</th>
    <th>Action</th>
</tr>

</thead>

<tbody>

<?php foreach ($cartItems as $item): ?>

<?php

$price = (float)($item['price'] ?? 0);

$qty = (int)($item['quantity'] ?? 1);

$subtotal = $price * $qty;

?>

<tr>

<td>
    <img
        src="<?= h($item['image'] ?? '') ?>"
        alt="<?= h($item['name'] ?? 'Game') ?>"
        class="cart-image"
    >
</td>

<td><?= h($item['id'] ?? 'N/A') ?></td>

<td><?= h($item['name'] ?? 'Unnamed') ?></td>

<td><?= number_format($price, 0) ?> kr</td>

<td><?= $qty ?></td>

<td><?= number_format($subtotal, 0) ?> kr</td>

<td>

<form method="POST">

    <?= csrf_input() ?>


<input
    type="hidden"
    name="remove_item"
    value="<?= h($item['id']) ?>"
>

<button
    type="submit"
    class="cart-btn remove-btn"
>
    Remove
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<div class="cart-total">

Total:
<?= number_format($total, 0) ?> kr

</div>

<div class="cart-actions">

<form method="GET" action="checkout.php">

<button
    type="submit"
    class="cart-btn checkout-btn"
>
    💳 Checkout
</button>

</form>

<form method="POST">

    <?= csrf_input() ?>


<button
    type="submit"
    name="empty_cart"
    class="cart-btn clear-btn"
>
    ❌ Clear Cart

</button>

</form>

<button
    onclick="window.location.href='menu.php'"
    class="cart-btn back-btn"
>
    ← Back to Shop
</button>

</div>

<?php else: ?>

<div class="empty-cart">

<p>
    Your cart is currently empty.
    Maybe time to add some games? 🎮
</p>

<button
    onclick="window.location.href='menu.php'"
    class="cart-btn checkout-btn"
>
    Back to Shop
</button>

</div>

<?php endif; ?>

</section>

</main>

<footer>

<p>
    &copy; <?= date("Y") ?> Arcadia.
    All rights reserved.
</p>

</footer>

</body>
</html>