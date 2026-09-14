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

/*
|--------------------------------------------------------------------------
| GET CUSTOMER ORDERS
|--------------------------------------------------------------------------
|
| GROUP_CONCAT collects all products belonging to the same order.
|
*/

$stmt = $conn->prepare("
    SELECT
        o.id AS order_id,
        o.order_date,
        o.status,
        o.total_amount,
        o.quantity AS total_quantity,

        GROUP_CONCAT(
            CONCAT(
                COALESCE(orow.product_name, p.name, 'Unknown product'),
                ' × ',
                orow.quantity
            )
            ORDER BY orow.id
            SEPARATOR ', '
        ) AS products

    FROM Orders o

    LEFT JOIN OrderRows orow
        ON orow.order_id = o.id

    LEFT JOIN Products p
        ON p.id = orow.product_id

    WHERE o.customer_id = ?

    GROUP BY
        o.id,
        o.order_date,
        o.status,
        o.total_amount,
        o.quantity

    ORDER BY o.order_date DESC, o.id DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalOrders = count($orders);
$totalSpent = 0.0;
$totalGames = 0;

foreach ($orders as $order) {
    $totalSpent += (float)$order["total_amount"];
    $totalGames += (int)$order["total_quantity"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>My Orders — Arcadia</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --background: #050814;
            --surface: rgba(15, 23, 42, 0.96);
            --surface-light: #1e293b;
            --border: rgba(255, 255, 255, 0.08);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #00e5ff;
            --blue: #2563eb;
            --green: #22c55e;
            --yellow: #f59e0b;
            --red: #ef4444;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at top left,
                    rgba(0, 229, 255, 0.16),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    var(--background),
                    #0f172a
                );
        }

        .page {
            width: min(1200px, 92%);
            margin: 0 auto;
            padding: 42px 0 70px;
        }

        /* HEADER */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            margin-bottom: 34px;
        }

        .page-heading h1 {
            margin-bottom: 9px;
            font-size: clamp(2rem, 4vw, 3rem);
        }

        .page-heading p {
            color: var(--muted);
            line-height: 1.6;
        }

        .page-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-link {
            display: inline-block;
            padding: 12px 17px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-light);
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .nav-link:hover {
            border-color: rgba(0, 229, 255, 0.45);
            background: var(--blue);
            transform: translateY(-2px);
        }

        .logout-link:hover {
            background: var(--red);
            border-color: var(--red);
        }

        /* STATISTICS */

        .statistics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--surface);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            margin-bottom: 10px;
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--accent);
        }

        /* ORDERS */

        .orders-list {
            display: grid;
            gap: 20px;
        }

        .order-card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: var(--surface);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            transition: 0.2s ease;
        }

        .order-card:hover {
            border-color: rgba(0, 229, 255, 0.38);
            transform: translateY(-3px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            background: rgba(30, 41, 59, 0.55);
        }

        .order-number {
            font-size: 1.25rem;
            font-weight: 800;
        }

        .order-date {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 13px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 800;
        }

        .status-ordered {
            color: #bfdbfe;
            background: rgba(37, 99, 235, 0.18);
            border: 1px solid rgba(37, 99, 235, 0.35);
        }

        .status-packed {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .status-shipped,
        .status-paid,
        .status-completed {
            color: #bbf7d0;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.35);
        }

        .status-cancelled,
        .status-refunded {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
        }

        .order-body {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 30px;
            padding: 24px;
        }

        .order-products h2 {
            margin-bottom: 10px;
            font-size: 1rem;
            color: var(--muted);
        }

        .products-text {
            line-height: 1.7;
            color: #dbeafe;
        }

        .order-summary {
            min-width: 180px;
            display: grid;
            align-content: start;
            gap: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 22px;
            color: var(--muted);
        }

        .summary-row strong {
            color: var(--text);
        }

        .summary-total {
            padding-top: 13px;
            border-top: 1px solid var(--border);
            color: var(--accent);
            font-size: 1.1rem;
            font-weight: 800;
        }

        .order-footer {
            padding: 0 24px 24px;
        }

        .details-button {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 12px;
            color: #020617;
            background: linear-gradient(
                135deg,
                var(--accent),
                var(--blue)
            );
            text-decoration: none;
            font-weight: 800;
        }

        .details-button:hover {
            filter: brightness(1.08);
        }

        /* EMPTY STATE */

        .empty-state {
            padding: 55px 25px;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: var(--surface);
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }

        .empty-icon {
            margin-bottom: 18px;
            font-size: 3.5rem;
        }

        .empty-state h2 {
            margin-bottom: 12px;
            font-size: 1.7rem;
        }

        .empty-state p {
            margin-bottom: 24px;
            color: var(--muted);
            line-height: 1.6;
        }

        .store-button {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 12px;
            color: #020617;
            background: var(--accent);
            text-decoration: none;
            font-weight: 800;
        }

        @media (max-width: 800px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .statistics {
                grid-template-columns: 1fr;
            }

            .order-body {
                grid-template-columns: 1fr;
            }

            .order-summary {
                min-width: 0;
            }
        }

        @media (max-width: 540px) {
            .page-navigation {
                width: 100%;
            }

            .nav-link {
                flex: 1;
                text-align: center;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<main class="page">

    <header class="page-header">

        <div class="page-heading">
            <h1>My Orders</h1>

            <p>
                Review your purchases, order totals and current
                order status.
            </p>
        </div>

        <nav class="page-navigation">
            <a
                class="nav-link"
                href="customer_dashboard.php"
            >
                Dashboard
            </a>

            <a
                class="nav-link"
                href="library.php"
            >
                My Library
            </a>

            <a
                class="nav-link"
                href="menu.php"
            >
                Store
            </a>

            <a
                class="nav-link logout-link"
                href="customer_logout.php"
            >
                Logout
            </a>
        </nav>

    </header>

    <section class="statistics">

        <article class="stat-card">
            <p class="stat-label">Total Orders</p>

            <p class="stat-value">
                <?= $totalOrders ?>
            </p>
        </article>

        <article class="stat-card">
            <p class="stat-label">Games Purchased</p>

            <p class="stat-value">
                <?= $totalGames ?>
            </p>
        </article>

        <article class="stat-card">
            <p class="stat-label">Total Spent</p>

            <p class="stat-value">
                <?= number_format($totalSpent, 0) ?> kr
            </p>
        </article>

    </section>

    <?php if ($orders): ?>

        <section class="orders-list">

            <?php foreach ($orders as $order): ?>

                <?php
                $status = strtolower(
                    preg_replace(
                        "/[^a-zA-Z]/",
                        "",
                        (string)$order["status"]
                    )
                );

                $date = strtotime(
                    (string)$order["order_date"]
                );
                ?>

                <article class="order-card">

                    <header class="order-header">

                        <div>
                            <p class="order-number">
                                Order #<?= (int)$order["order_id"] ?>
                            </p>

                            <p class="order-date">
                                <?= $date
                                    ? h(date("F j, Y \a\t H:i", $date))
                                    : "Unknown date"
                                ?>
                            </p>
                        </div>

                        <span
                            class="status status-<?= h($status) ?>"
                        >
                            <?= h($order["status"]) ?>
                        </span>

                    </header>

                    <div class="order-body">

                        <div class="order-products">
                            <h2>Purchased games</h2>

                            <p class="products-text">
                                <?= h(
                                    $order["products"]
                                    ?: $order["product_name"]
                                    ?: "No products found"
                                ) ?>
                            </p>
                        </div>

                        <div class="order-summary">

                            <div class="summary-row">
                                <span>Quantity</span>

                                <strong>
                                    <?= (int)$order["total_quantity"] ?>
                                </strong>
                            </div>

                            <div class="summary-row summary-total">
                                <span>Total</span>

                                <strong>
                                    <?= number_format(
                                        (float)$order["total_amount"],
                                        0
                                    ) ?> kr
                                </strong>
                            </div>

                        </div>

                    </div>

                    <footer class="order-footer">

                        <a
                            class="details-button"
                            href="customer_order_details.php?id=<?= (int)$order["order_id"] ?>"
                        >
                            View Order Details
                        </a>

                    </footer>

                </article>

            <?php endforeach; ?>

        </section>

    <?php else: ?>

        <section class="empty-state">
            <div class="empty-icon">📦</div>

            <h2>No orders yet</h2>

            <p>
                Your completed purchases will appear here after
                checkout.
            </p>

            <a class="store-button" href="menu.php">
                Browse Games
            </a>
        </section>

    <?php endif; ?>

</main>

</body>
</html>