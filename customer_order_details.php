<?php
declare(strict_types=1);

session_start();
require_once "db.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$customerId = (int)$_SESSION["customer_id"];
$orderId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($orderId <= 0) {
    header("Location: customer_orders.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify the customer owns this order
|--------------------------------------------------------------------------
*/

$orderStmt = $conn->prepare("
SELECT
    id,
    customer_id,
    status,
    order_date,
    total_amount
FROM Orders
WHERE id = ?
AND customer_id = ?
LIMIT 1
");

$orderStmt->bind_param("ii", $orderId, $customerId);
$orderStmt->execute();

$order = $orderStmt->get_result()->fetch_assoc();

$orderStmt->close();

if (!$order) {
    header("Location: customer_orders.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Order items
|--------------------------------------------------------------------------
*/

$itemStmt = $conn->prepare("
SELECT
    product_name,
    quantity,
    price,
    amount
FROM OrderRows
WHERE order_id = ?
ORDER BY id
");

$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();

$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$itemStmt->close();

$statusClass = strtolower(
    preg_replace("/[^a-zA-Z]/", "", $order["status"])
);

$date = strtotime($order["order_date"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Order #<?= (int)$order["id"] ?>
</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{

font-family:Arial,Helvetica,sans-serif;

background:
radial-gradient(circle at top left,
rgba(0,229,255,.16),
transparent 35%),
linear-gradient(135deg,#050814,#0f172a);

color:white;

}

.container{

width:min(1100px,92%);
margin:auto;
padding:45px 0 70px;

}

.header{

display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:35px;

}

.header h1{

font-size:2.4rem;

}

.back{

padding:12px 18px;

background:#1e293b;

border-radius:12px;

text-decoration:none;

color:white;

font-weight:bold;

}

.back:hover{

background:#2563eb;

}

.card{

background:rgba(15,23,42,.96);

border:1px solid rgba(255,255,255,.08);

border-radius:22px;

padding:30px;

box-shadow:0 18px 55px rgba(0,0,0,.35);

margin-bottom:28px;

}

.info{

display:grid;

grid-template-columns:repeat(2,1fr);

gap:20px;

}

.info p{

color:#cbd5e1;

line-height:1.8;

}

.status{

display:inline-block;

padding:8px 14px;

border-radius:999px;

font-weight:bold;

margin-top:8px;

background:rgba(37,99,235,.2);

color:#bfdbfe;

}

table{

width:100%;

border-collapse:collapse;

margin-top:25px;

}

th{

background:#1e293b;

padding:16px;

text-align:left;

}

td{

padding:16px;

border-bottom:1px solid rgba(255,255,255,.08);

}

tfoot td{

font-size:1.15rem;

font-weight:bold;

color:#00e5ff;

}

@media(max-width:750px){

.info{

grid-template-columns:1fr;

}

table{

display:block;
overflow-x:auto;

}

}

</style>

</head>

<body>

<div class="container">

<div class="header">

<div>

<h1>

Order #<?= (int)$order["id"] ?>

</h1>

</div>

<a
class="back"
href="customer_orders.php">

← Back to Orders

</a>

</div>

<div class="card">

<div class="info">

<div>

<p>

<strong>Purchase Date</strong>

<br>

<?= date("F j, Y \a\t H:i",$date) ?>

</p>

</div>

<div>

<p>

<strong>Status</strong>

<br>

<span class="status">

<?= h($order["status"]) ?>

</span>

</p>

</div>

<div>

<p>

<strong>Total Amount</strong>

<br>

<?= number_format(
(float)$order["total_amount"],
0
) ?>

kr

</p>

</div>

<div>

<p>

<strong>Games Purchased</strong>

<br>

<?= count($items) ?>

</p>

</div>

</div>

</div>

<div class="card">

<h2>

Purchased Games

</h2>

<table>

<thead>

<tr>

<th>Game</th>

<th>Quantity</th>

<th>Price</th>

<th>Total</th>

</tr>

</thead>

<tbody>

<?php foreach($items as $item): ?>

<tr>

<td>

<?= h($item["product_name"]) ?>

</td>

<td>

<?= (int)$item["quantity"] ?>

</td>

<td>

<?= number_format(
(float)$item["price"],
0
) ?>

kr

</td>

<td>

<?= number_format(
(float)$item["amount"],
0
) ?>

kr

</td>

</tr>

<?php endforeach; ?>

</tbody>

<tfoot>

<tr>

<td colspan="3">

Grand Total

</td>

<td>

<?= number_format(
(float)$order["total_amount"],
0
) ?>

kr

</td>

</tr>

</tfoot>

</table>

</div>

</div>

</body>

</html>