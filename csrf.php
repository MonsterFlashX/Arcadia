<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GameStation CSRF Protection
|--------------------------------------------------------------------------
|
| Include this file after session_start():
|
| require_once "csrf.php";
|
| Then add this inside every POST form:
|
| <?= csrf_input() ?>
|
| And verify at the start of every POST request:
|
| csrf_verify_request();
|
*/

function csrf_token(): string
{
    if (
        !isset($_SESSION["csrf_token"]) ||
        !is_string($_SESSION["csrf_token"]) ||
        strlen($_SESSION["csrf_token"]) < 64
    ) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_input(): string
{
    $token = htmlspecialchars(
        csrf_token(),
        ENT_QUOTES,
        "UTF-8"
    );

    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_is_valid(?string $submittedToken): bool
{
    if (
        $submittedToken === null ||
        $submittedToken === "" ||
        !isset($_SESSION["csrf_token"]) ||
        !is_string($_SESSION["csrf_token"])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $submittedToken
    );
}

function csrf_verify_request(): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    $submittedToken = $_POST["csrf_token"] ?? null;

    if (!is_string($submittedToken) || !csrf_is_valid($submittedToken)) {
        http_response_code(403);

        exit(
            "Invalid or expired form request. " .
            "Please return to the previous page and try again."
        );
    }
}

function csrf_regenerate(): void
{
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}