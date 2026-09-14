<?php
session_start();
require_once "db.php";
require_once "csrf.php";

csrf_verify_request();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstname = trim($_POST["firstname"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $postal_code = trim($_POST["postal_code"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $firstname === "" ||
        $lastname === "" ||
        $email === "" ||
        $password === ""
    ) {
        $error = "All required fields must be filled.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";

    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO Customers
            (
                firstname,
                lastname,
                telephone,
                address,
                postal_code,
                city,
                email,
                password_hash
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssss",
            $firstname,
            $lastname,
            $telephone,
            $address,
            $postal_code,
            $city,
            $email,
            $passwordHash
        );

        try {
            if ($stmt->execute()) {
                header("Location: customer_login.php");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $error = "An account with this email already exists.";
            } else {
                $error = "Database error. Please try again.";
            }
        }
    }
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<title>
    Create Account - GameStation
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
            Customer Registration
        </div>

        <h1 class="auth-title">
            Create Account
        </h1>

        <p class="auth-subtitle">
            Join Arcadia, buy games,
            and build your personal game library.
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
                type="text"
                name="firstname"
                placeholder="Firstname"
                required
            >

            <input
                class="auth-input"
                type="text"
                name="lastname"
                placeholder="Lastname"
                required
            >

            <input
                class="auth-input"
                type="text"
                name="telephone"
                placeholder="Telephone"
            >

            <input
                class="auth-input"
                type="text"
                name="address"
                placeholder="Address"
            >

            <input
                class="auth-input"
                type="text"
                name="postal_code"
                placeholder="Postal Code"
            >

            <input
                class="auth-input"
                type="text"
                name="city"
                placeholder="City"
            >

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
                Create Account
            </button>

        </form>

        <div class="auth-link">
            Already have an account?

            <a href="customer_login.php">
                Login
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