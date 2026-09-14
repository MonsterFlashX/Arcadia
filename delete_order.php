<?php
require_once "auth_check.php";
require_once "db.php";
require_once "csrf.php";
csrf_verify_request();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["order_id"])) {
    header("Location: admin_orders.php?error=invalid_request");
    exit;
}

$order_id = (int) $_POST["order_id"];

$stmt = $conn->prepare("DELETE FROM Orders WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: admin_orders.php?success=order_deleted");
    exit;
}

$stmt->close();
$conn->close();

header("Location: admin_orders.php?error=delete_failed");
exit;
?>