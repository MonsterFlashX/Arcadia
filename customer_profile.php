<?php
declare(strict_types=1);

session_start();
require_once "csrf.php";
require_once "db.php";

csrf_verify_request();


if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$customerId = (int)$_SESSION["customer_id"];

$profileError = "";
$profileSuccess = "";
$passwordError = "";
$passwordSuccess = "";

/*
|--------------------------------------------------------------------------
| LOAD CUSTOMER
|--------------------------------------------------------------------------
*/

$customerStmt = $conn->prepare("
    SELECT
        id,
        firstname,
        lastname,
        telephone,
        address,
        postal_code,
        city,
        email,
        password_hash
    FROM Customers
    WHERE id = ?
    LIMIT 1
");

$customerStmt->bind_param("i", $customerId);
$customerStmt->execute();

$customer = $customerStmt->get_result()->fetch_assoc();

$customerStmt->close();

if (!$customer) {
    unset(
        $_SESSION["customer_id"],
        $_SESSION["customer_name"],
        $_SESSION["customer_email"]
    );

    header("Location: customer_login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {
    $firstname = trim($_POST["firstname"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $postalCode = trim($_POST["postal_code"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if (
        $firstname === "" ||
        $lastname === "" ||
        $email === ""
    ) {
        $profileError = "Firstname, lastname and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileError = "Please enter a valid email address.";
    } else {
        $emailStmt = $conn->prepare("
            SELECT id
            FROM Customers
            WHERE email = ?
              AND id <> ?
            LIMIT 1
        ");

        $emailStmt->bind_param("si", $email, $customerId);
        $emailStmt->execute();

        $emailAlreadyUsed = $emailStmt->get_result()->fetch_assoc();

        $emailStmt->close();

        if ($emailAlreadyUsed) {
            $profileError = "That email address is already in use.";
        } else {
            try {
                $updateStmt = $conn->prepare("
                    UPDATE Customers
                    SET
                        firstname = ?,
                        lastname = ?,
                        telephone = ?,
                        address = ?,
                        postal_code = ?,
                        city = ?,
                        email = ?
                    WHERE id = ?
                ");

                $updateStmt->bind_param(
                    "sssssssi",
                    $firstname,
                    $lastname,
                    $telephone,
                    $address,
                    $postalCode,
                    $city,
                    $email,
                    $customerId
                );

                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION["customer_name"] =
                    trim($firstname . " " . $lastname);

                $_SESSION["customer_email"] = $email;

                $customer["firstname"] = $firstname;
                $customer["lastname"] = $lastname;
                $customer["telephone"] = $telephone;
                $customer["address"] = $address;
                $customer["postal_code"] = $postalCode;
                $customer["city"] = $city;
                $customer["email"] = $email;

                $profileSuccess = "Your profile has been updated successfully.";
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $profileError = "Your profile could not be updated.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {
    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $currentPassword === "" ||
        $newPassword === "" ||
        $confirmPassword === ""
    ) {
        $passwordError = "All password fields are required.";
    } elseif (
        !password_verify(
            $currentPassword,
            (string)$customer["password_hash"]
        )
    ) {
        $passwordError = "Your current password is incorrect.";
    } elseif (strlen($newPassword) < 8) {
        $passwordError = "The new password must contain at least 8 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "The new passwords do not match.";
    } elseif ($currentPassword === $newPassword) {
        $passwordError = "The new password must be different from the current password.";
    } else {
        try {
            $newPasswordHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $passwordStmt = $conn->prepare("
                UPDATE Customers
                SET password_hash = ?
                WHERE id = ?
            ");

            $passwordStmt->bind_param(
                "si",
                $newPasswordHash,
                $customerId
            );

            $passwordStmt->execute();
            $passwordStmt->close();

            $customer["password_hash"] = $newPasswordHash;

            session_regenerate_id(true);

            $passwordSuccess = "Your password has been changed successfully.";
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $passwordError = "Your password could not be changed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>My Profile — Arcadia</title>

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
            --input: #162033;
            --border: rgba(255, 255, 255, 0.09);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #00e5ff;
            --blue: #2563eb;
            --green: #22c55e;
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
            width: min(1180px, 92%);
            margin: 0 auto;
            padding: 42px 0 70px;
        }

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

        .profile-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(300px, 0.8fr);
            gap: 26px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 22px;
            background: var(--surface);
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }

        .card-header {
            margin-bottom: 24px;
        }

        .card-header h2 {
            margin-bottom: 8px;
            font-size: 1.55rem;
        }

        .card-header p {
            color: var(--muted);
            line-height: 1.6;
        }

        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 13px;
            font-weight: 700;
            line-height: 1.5;
        }

        .message-success {
            color: #bbf7d0;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.35);
        }

        .message-error {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
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
            color: #cbd5e1;
            font-size: 0.94rem;
            font-weight: 700;
        }

        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--input);
            color: var(--text);
            font-size: 0.98rem;
            outline: none;
            transition: 0.2s ease;
        }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(0, 229, 255, 0.11);
        }

        .primary-button {
            width: 100%;
            margin-top: 22px;
            padding: 14px 18px;
            border: none;
            border-radius: 13px;
            color: #020617;
            background:
                linear-gradient(
                    135deg,
                    var(--accent),
                    var(--blue)
                );
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .primary-button:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .account-summary {
            display: grid;
            gap: 18px;
        }

        .profile-avatar {
            width: 84px;
            height: 84px;
            display: grid;
            place-items: center;
            border-radius: 22px;
            color: #020617;
            background:
                linear-gradient(
                    135deg,
                    var(--accent),
                    var(--blue)
                );
            font-size: 2rem;
            font-weight: 900;
        }

        .customer-name {
            font-size: 1.45rem;
            font-weight: 800;
        }

        .customer-email {
            color: var(--muted);
            word-break: break-word;
        }

        .summary-list {
            display: grid;
            gap: 13px;
            padding-top: 6px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 13px;
            border-bottom: 1px solid var(--border);
        }

        .summary-item span {
            color: var(--muted);
        }

        .summary-item strong {
            text-align: right;
            word-break: break-word;
        }

        .security-card {
            margin-top: 26px;
        }

        .password-form {
            display: grid;
            gap: 16px;
        }

        .password-note {
            margin-top: 16px;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 620px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: span 1;
            }

            .page-navigation {
                width: 100%;
            }

            .nav-link {
                flex: 1;
                text-align: center;
            }

            .summary-item {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

            .summary-item strong {
                text-align: left;
            }
        }
    </style>
</head>

<body>

<main class="page">

    <header class="page-header">

        <div class="page-heading">
            <h1>My Profile</h1>

            <p>
                Manage your personal information, delivery details
                and account password.
            </p>
        </div>

        <nav class="page-navigation">
            <a class="nav-link" href="customer_dashboard.php">
                Dashboard
            </a>

            <a class="nav-link" href="library.php">
                My Library
            </a>

            <a class="nav-link" href="customer_orders.php">
                My Orders
            </a>

            <a class="nav-link logout-link" href="customer_logout.php">
                Logout
            </a>
        </nav>

    </header>

    <section class="profile-layout">

        <div>

            <article class="card">

                <header class="card-header">
                    <h2>Personal information</h2>

                    <p>
                        Update the contact and address details connected
                        to your Arcadia account.
                    </p>
                </header>

                <?php if ($profileError): ?>
                    <div class="message message-error">
                        <?= h($profileError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($profileSuccess): ?>
                    <div class="message message-success">
                        <?= h($profileSuccess) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <?= csrf_input() ?>


                    <div class="form-grid">

                        <div class="form-group">
                            <label for="firstname">Firstname</label>

                            <input
                                type="text"
                                id="firstname"
                                name="firstname"
                                value="<?= h($customer["firstname"] ?? "") ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="lastname">Lastname</label>

                            <input
                                type="text"
                                id="lastname"
                                name="lastname"
                                value="<?= h($customer["lastname"] ?? "") ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="telephone">Telephone</label>

                            <input
                                type="text"
                                id="telephone"
                                name="telephone"
                                value="<?= h($customer["telephone"] ?? "") ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= h($customer["email"] ?? "") ?>"
                                required
                            >
                        </div>

                        <div class="form-group full">
                            <label for="address">Address</label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= h($customer["address"] ?? "") ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="postal_code">Postal code</label>

                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                value="<?= h($customer["postal_code"] ?? "") ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="city">City</label>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?= h($customer["city"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <button
                        type="submit"
                        name="update_profile"
                        class="primary-button"
                    >
                        Save Profile Changes
                    </button>

                </form>

            </article>

            <article class="card security-card">

                <header class="card-header">
                    <h2>Change password</h2>

                    <p>
                        Enter your current password before choosing
                        a new account password.
                    </p>
                </header>

                <?php if ($passwordError): ?>
                    <div class="message message-error">
                        <?= h($passwordError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($passwordSuccess): ?>
                    <div class="message message-success">
                        <?= h($passwordSuccess) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="password-form">

                    <?= csrf_input() ?>


                    <div class="form-group">
                        <label for="current_password">
                            Current password
                        </label>

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            New password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            Confirm new password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        name="change_password"
                        class="primary-button"
                    >
                        Change Password
                    </button>

                </form>

                <p class="password-note">
                    Use at least eight characters. A longer password
                    or passphrase is recommended.
                </p>

            </article>

        </div>

        <aside class="card">

            <div class="account-summary">

                <div class="profile-avatar">
                    <?= h(
                        strtoupper(
                            substr(
                                (string)($customer["firstname"] ?? "C"),
                                0,
                                1
                            ) .
                            substr(
                                (string)($customer["lastname"] ?? ""),
                                0,
                                1
                            )
                        )
                    ) ?>
                </div>

                <div>
                    <p class="customer-name">
                        <?= h(
                            trim(
                                ($customer["firstname"] ?? "") .
                                " " .
                                ($customer["lastname"] ?? "")
                            )
                        ) ?>
                    </p>

                    <p class="customer-email">
                        <?= h($customer["email"] ?? "") ?>
                    </p>
                </div>

                <div class="summary-list">

                    <div class="summary-item">
                        <span>Customer ID</span>

                        <strong>
                            #<?= $customerId ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Telephone</span>

                        <strong>
                            <?= h(
                                $customer["telephone"]
                                ?: "Not provided"
                            ) ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Address</span>

                        <strong>
                            <?= h(
                                $customer["address"]
                                ?: "Not provided"
                            ) ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Postal code</span>

                        <strong>
                            <?= h(
                                $customer["postal_code"]
                                ?: "Not provided"
                            ) ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>City</span>

                        <strong>
                            <?= h(
                                $customer["city"]
                                ?: "Not provided"
                            ) ?>
                        </strong>
                    </div>

                </div>

            </div>

        </aside>

    </section>

</main>

</body>
</html>