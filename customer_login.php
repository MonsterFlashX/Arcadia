<?php
session_start();
require_once "db.php";
require_once "csrf.php";

csrf_verify_request();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("
        SELECT
            id,
            firstname,
            lastname,
            email,
            password_hash
        FROM Customers
        WHERE email = ?
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    if (
        $customer &&
        password_verify(
            $password,
            $customer["password_hash"]
        )
    ) {

        session_regenerate_id(true);

        $_SESSION["customer_id"] =
            $customer["id"];

        $_SESSION["customer_name"] =
            $customer["firstname"] .
            " " .
            $customer["lastname"];

        $_SESSION["customer_email"] =
            $customer["email"];

        header(
            "Location: customer_dashboard.php"
        );

        exit;
    }

    $error = "Invalid email or password.";
}

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
    Customer Login - Arcadia
</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<link
    rel="stylesheet"
    href="css/customer_auth.css"
>

</head>

<body>

<div class="auth-wrapper">

    <main class="auth-card">

        <div class="auth-badge">
            Customer Login
        </div>

        <h1 class="auth-title">
            Welcome Back
        </h1>

        <p class="auth-subtitle">
            Login to access your purchased
            games and Arcadia account.
        </p>

        <?php if ($error): ?>

            <div class="auth-error">
                <?= h($error) ?>
            </div>

        <?php endif; ?>

        <form
    method="POST"
    class="auth-form"
>

    <?= csrf_input() ?>

    <input
        class="auth-input"
        type="email"
        name="email"
        placeholder="Email"
        required
    >

    <input
        class="auth-input"
        type="password"
        name="password"
        placeholder="Password"
        required
    >

    <button
        class="auth-btn"
        type="submit"
    >
        Login
    </button>

</form>

        <div class="auth-link">

            No account?

            <a href="customer_register.php">
                Create one
            </a>

        </div>

        <div class="auth-link">

            <a
                href="index.html"
                class="back-home-btn"
            >
                ← Back to Home
            </a>

        </div>

    </main>

</div>

</body>
</html>