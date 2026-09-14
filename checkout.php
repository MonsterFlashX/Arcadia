<?php
declare(strict_types=1);

session_start();

require_once "csrf.php";
require_once "db.php";

csrf_verify_request();

if (!isset($_SESSION["customer_id"])) {
    $_SESSION["after_login_redirect"] = "checkout.php";
    header("Location: customer_login.php");
    exit;
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$error = "";
$cart = $_SESSION["cart"] ?? [];
$customerId = (int)$_SESSION["customer_id"];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$customerStmt = $conn->prepare("SELECT firstname, lastname, telephone, address, postal_code, city, email FROM Customers WHERE id = ?");
$customerStmt->bind_param("i", $customerId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();

if (!$customer) {
    unset($_SESSION["customer_id"], $_SESSION["customer_name"], $_SESSION["customer_email"]);
    header("Location: customer_login.php");
    exit;
}

// Always trust current product prices from the database, never hidden/session prices.
$productStmt = $conn->prepare("SELECT id, name, price FROM Products WHERE id = ?");
$verifiedCart = [];
$total_amount = 0.0;

foreach ($cart as $item) {
    $productId = (int)($item["id"] ?? 0);
    $quantity = max(1, (int)($item["quantity"] ?? 1));
    if ($productId < 1) continue;

    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $product = $productStmt->get_result()->fetch_assoc();
    if (!$product) continue;

    $price = (float)$product["price"];
    $verifiedCart[] = [
        "id" => (int)$product["id"],
        "name" => $product["name"],
        "price" => $price,
        "quantity" => $quantity,
    ];
    $total_amount += $price * $quantity;
}
$productStmt->close();

if (!$verifiedCart) {
    $_SESSION["cart"] = [];
    header("Location: cart.php");
    exit;
}
$cart = $verifiedCart;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["place_order"])) {
    $firstname   = trim($_POST["firstname"] ?? "");
    $lastname    = trim($_POST["lastname"] ?? "");
    $telephone   = trim($_POST["telephone"] ?? "");
    $address     = trim($_POST["address"] ?? "");
    $postal_code = trim($_POST["postal_code"] ?? "");
    $city        = trim($_POST["city"] ?? "");
    $email       = trim($_POST["email"] ?? "");

    // Keep the submitted values visible if validation fails.
    $customer = [
        "firstname"   => $firstname,
        "lastname"    => $lastname,
        "telephone"   => $telephone,
        "address"     => $address,
        "postal_code" => $postal_code,
        "city"        => $city,
        "email"       => $email,
    ];

    if (
        $firstname === "" ||
        $lastname === "" ||
        $telephone === "" ||
        $address === "" ||
        $postal_code === "" ||
        $city === "" ||
        $email === ""
    ) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Store verified checkout details temporarily for payment.php.
        // The order is not created until the simulated payment succeeds.
        $_SESSION["checkout"] = [
            "customer_id" => $customerId,
            "firstname"   => $firstname,
            "lastname"    => $lastname,
            "telephone"   => $telephone,
            "address"     => $address,
            "postal_code" => $postal_code,
            "city"        => $city,
            "email"       => $email,
            "total"       => $total_amount,
            "created_at"  => time(),
        ];

        // Store the verified cart snapshot for the payment step.
        $_SESSION["checkout_cart"] = $cart;

        header("Location: payment.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GameStation Checkout</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background:
        radial-gradient(circle at top left, rgba(0,229,255,0.16), transparent 35%),
        linear-gradient(135deg, #050814, #0f172a);
    color: white;
    font-family: Arial, Helvetica, sans-serif;
}

.checkout-header {
    text-align: center;
    padding: 50px 20px;
}

.checkout-header h1 {
    font-size: 3rem;
    margin-bottom: 10px;
}

.checkout-header p {
    color: #94a3b8;
}

.checkout-page {
    min-height: 75vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 30px 20px 70px;
}

.checkout-wrapper {
    width: 100%;
    max-width: 1150px;
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 30px;
}

.checkout-card,
.summary-card {
    background: rgba(15,23,42,0.96);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    padding: 35px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.45);
}

.checkout-card h2,
.summary-card h2 {
    margin-bottom: 25px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: span 2;
}

.form-group label {
    margin-bottom: 8px;
    color: #94a3b8;
    font-weight: bold;
}

.form-group input {
    padding: 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.08);
    background: #1e293b;
    color: white;
    outline: none;
}

.form-group input:focus {
    border-color: #00e5ff;
    box-shadow: 0 0 0 4px rgba(0,229,255,0.12);
}

.checkout-btn {
    width: 100%;
    margin-top: 25px;
    padding: 15px;
    background: linear-gradient(135deg, #00e5ff, #2563eb);
    color: #020617;
    border: none;
    border-radius: 14px;
    font-weight: 800;
    cursor: pointer;
}

.msg-error,
.msg-success {
    padding: 14px;
    border-radius: 14px;
    margin-bottom: 20px;
    font-weight: bold;
}

.msg-error {
    background: rgba(239,68,68,0.15);
    color: #fecaca;
    border: 1px solid rgba(239,68,68,0.35);
}

.msg-success {
    background: rgba(34,197,94,0.15);
    color: #86efac;
    border: 1px solid rgba(34,197,94,0.35);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.summary-item span {
    color: #cbd5e1;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    margin-top: 24px;
    font-size: 1.35rem;
    font-weight: bold;
    color: #00e5ff;
}

.checkout-links {
    margin-top: 25px;
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}

.checkout-links a {
    color: #00e5ff;
    text-decoration: none;
    font-weight: bold;
}

.checkout-footer {
    text-align: center;
    padding: 25px;
    color: #94a3b8;
}

@media (max-width: 850px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: span 1;
    }
}
</style>
</head>

<body>

<header class="checkout-header">
    <h1>Arcadia Checkout</h1>
    <p>Complete your order securely</p>
</header>

<main class="checkout-page">

    <div class="checkout-wrapper">

        <section class="checkout-card">

            <h2>Checkout Details</h2>

            <?php if ($error): ?>
                <p class="msg-error"><?= h($error) ?></p>
            <?php endif; ?>

            <form method="post">
                    <?= csrf_input() ?>
                    <div class="form-grid">

                        <div class="form-group">
                            <label>Firstname</label>
                            <input type="text" name="firstname" value="<?= h($customer["firstname"] ?? "") ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Lastname</label>
                            <input type="text" name="lastname" value="<?= h($customer["lastname"] ?? "") ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Telephone</label>
                            <input type="text" name="telephone" value="<?= h($customer["telephone"] ?? "") ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= h($customer["email"] ?? "") ?>" required>
                        </div>

                        <div class="form-group full">
                            <label>Address</label>
                            <input type="text" name="address" value="<?= h($customer["address"] ?? "") ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" value="<?= h($customer["postal_code"] ?? "") ?>" required>
                        </div>

                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= h($customer["city"] ?? "") ?>" required>
                        </div>

                    </div>

                <button type="submit" name="place_order" class="checkout-btn">
                    Continue to Payment
                </button>

            </form>

            <div class="checkout-links">
                <a href="menu.php">Back to Store</a>
                <a href="cart.php">Back to Cart</a>
            </div>

        </section>

        <aside class="summary-card">

            <h2>Order Summary</h2>

            <?php foreach ($cart as $item): ?>
                <?php
                $name = $item["name"] ?? "Unknown";
                $qty = (int)($item["quantity"] ?? 1);
                $price = (float)($item["price"] ?? 0);
                $subtotal = $qty * $price;
                ?>

                <div class="summary-item">
                    <span><?= h($name) ?> × <?= $qty ?></span>
                    <strong><?= number_format($subtotal, 0) ?> kr</strong>
                </div>

            <?php endforeach; ?>

            <div class="summary-total">
                <span>Total</span>
                <strong><?= number_format($total_amount, 0) ?> kr</strong>
            </div>

        </aside>

    </div>

</main>

<footer class="checkout-footer">
    &copy; <?= date("Y") ?> Arcadia. All rights reserved.
</footer>

</body>
</html>