<?php
session_start();
require_once "csrf.php";
require_once "db.php";

csrf_verify_request();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("
        SELECT id, username, password_hash, role
        FROM Admins
        WHERE username = ?
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin["password_hash"])) {
        session_regenerate_id(true);

        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_username"] = $admin["username"];
        $_SESSION["admin_role"] = $admin["role"];

        header("Location: admin.php");
        exit;
    }

    $error = "Invalid username or password.";
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GameStation Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(0,255,255,0.18), transparent 35%),
        linear-gradient(135deg, #080b12, #121827);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: rgba(26, 32, 51, 0.95);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px;
    padding: 40px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.45);
}

.login-logo {
    text-align: center;
    font-size: 1.8rem;
    font-weight: bold;
    color: #00ffff;
    margin-bottom: 8px;
}

.login-subtitle {
    text-align: center;
    color: rgba(255,255,255,0.65);
    margin-bottom: 30px;
}

.login-card h1 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.7rem;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: rgba(255,255,255,0.8);
    font-size: 0.95rem;
}

.form-group input {
    width: 100%;
    padding: 13px 14px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.12);
    background: #0f1422;
    color: white;
    font-size: 1rem;
    outline: none;
}

.form-group input:focus {
    border-color: #00ffff;
    box-shadow: 0 0 0 3px rgba(0,255,255,0.12);
}

.login-btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: #00ffff;
    color: #000;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 8px;
}

.login-btn:hover {
    background: #00cccc;
}

.error-message {
    background: rgba(255,77,77,0.12);
    border: 1px solid rgba(255,77,77,0.35);
    color: #ff8080;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 18px;
    text-align: center;
}

.login-footer {
    margin-top: 22px;
    text-align: center;
}

.login-footer a {
    color: #00ffff;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<main class="login-card">

    <div class="login-logo">Arcadia</div>
    <p class="login-subtitle">Secure admin access</p>

    <h1>Admin Login</h1>

    <?php if ($error): ?>
        <div class="error-message">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        
            <?= csrf_input() ?>


        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter username"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter password"
                required
            >
        </div>

        <button type="submit" class="login-btn">
            Login
        </button>

    </form>

    <div class="login-footer">
        <a href="menu.php">← Back to Store</a>
    </div>

</main>

</body>
</html>