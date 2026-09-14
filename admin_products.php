<?php
declare(strict_types=1);

require_once "auth_check.php";
require_once "csrf.php";
require_once "db.php";

csrf_verify_request();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function normalizeCategory(string $category): string
{
    $allowed = [
        "Action",
        "Adventure",
        "Arcade",
        "Horror",
        "Platformer",
        "Puzzle",
        "Racing",
        "Simulation",
        "Sports",
        "Strategy"
    ];

    return in_array($category, $allowed, true)
        ? $category
        : "Puzzle";
}

function normalizeGameType(string $gameType): string
{
    return in_array($gameType, ["browser", "download"], true)
        ? $gameType
        : "browser";
}

function saveUploadedImage(array $file, string $existingPath = ""): string
{
    if (
        !isset($file["error"]) ||
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return $existingPath;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("The image upload failed.");
    }

    if (($file["size"] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException("The image must be 5 MB or smaller.");
    }

    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException("Only JPG, PNG and WEBP images are allowed.");
    }

    $uploadDir = __DIR__ . "/images";

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new RuntimeException("Could not create the images folder.");
    }

    $fileName = bin2hex(random_bytes(16)) . "." . $allowedMimeTypes[$mimeType];
    $absolutePath = $uploadDir . "/" . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $absolutePath)) {
        throw new RuntimeException("Could not save the uploaded image.");
    }

    return "images/" . $fileName;
}

$notice = "";
$error = "";

try {
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["add_product"])
    ) {
        $name = trim((string)($_POST["name"] ?? ""));
        $price = (float)($_POST["price"] ?? 0);
        $description = trim((string)($_POST["description"] ?? ""));
        $category = normalizeCategory((string)($_POST["category"] ?? ""));
        $gameType = normalizeGameType((string)($_POST["game_type"] ?? ""));
        $gameFile = trim((string)($_POST["game_file"] ?? ""));
        $featured = isset($_POST["featured"]) ? 1 : 0;

        if ($name === "" || $description === "" || $price < 0) {
            throw new RuntimeException(
                "Name, description and a valid non-negative price are required."
            );
        }

        $imagePath = saveUploadedImage($_FILES["image"] ?? []);

        $stmt = $conn->prepare("
            INSERT INTO Products
            (
                name,
                price,
                image,
                description,
                category,
                game_type,
                game_file,
                featured
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sdsssssi",
            $name,
            $price,
            $imagePath,
            $description,
            $category,
            $gameType,
            $gameFile,
            $featured
        );

        $stmt->execute();
        $stmt->close();

        header("Location: admin_products.php?success=added");
        exit;
    }

    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["update_product"])
    ) {
        $productId = (int)($_POST["product_id"] ?? 0);
        $name = trim((string)($_POST["name"] ?? ""));
        $price = (float)($_POST["price"] ?? 0);
        $description = trim((string)($_POST["description"] ?? ""));
        $category = normalizeCategory((string)($_POST["category"] ?? ""));
        $gameType = normalizeGameType((string)($_POST["game_type"] ?? ""));
        $gameFile = trim((string)($_POST["game_file"] ?? ""));
        $featured = isset($_POST["featured"]) ? 1 : 0;

        if ($productId < 1) {
            throw new RuntimeException("Invalid product.");
        }

        if ($name === "" || $description === "" || $price < 0) {
            throw new RuntimeException(
                "Name, description and a valid non-negative price are required."
            );
        }

        $oldImageStmt = $conn->prepare("
            SELECT image
            FROM Products
            WHERE id = ?
            LIMIT 1
        ");

        $oldImageStmt->bind_param("i", $productId);
        $oldImageStmt->execute();

        $oldProduct = $oldImageStmt
            ->get_result()
            ->fetch_assoc();

        $oldImageStmt->close();

        if (!$oldProduct) {
            throw new RuntimeException("Product not found.");
        }

        $imagePath = saveUploadedImage(
            $_FILES["image"] ?? [],
            (string)$oldProduct["image"]
        );

        $stmt = $conn->prepare("
            UPDATE Products
            SET
                name = ?,
                price = ?,
                image = ?,
                description = ?,
                category = ?,
                game_type = ?,
                game_file = ?,
                featured = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sdsssssii",
            $name,
            $price,
            $imagePath,
            $description,
            $category,
            $gameType,
            $gameFile,
            $featured,
            $productId
        );

        $stmt->execute();
        $stmt->close();

        header("Location: admin_products.php?success=updated");
        exit;
    }

    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["delete_product"])
    ) {
        $productId = (int)($_POST["product_id"] ?? 0);

        if ($productId < 1) {
            throw new RuntimeException("Invalid product.");
        }

        $stmt = $conn->prepare("
            DELETE FROM Products
            WHERE id = ?
        ");

        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_products.php?success=deleted");
        exit;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = $e->getMessage();
}

if (isset($_GET["success"])) {
    $messages = [
        "added" => "Product added successfully.",
        "updated" => "Product updated successfully.",
        "deleted" => "Product deleted successfully."
    ];

    $notice = $messages[$_GET["success"]] ?? "";
}

$result = $conn->query("
    SELECT
        id,
        name,
        price,
        image,
        description,
        category,
        game_type,
        game_file,
        featured
    FROM Products
    ORDER BY id DESC
");

$products = $result
    ? $result->fetch_all(MYSQLI_ASSOC)
    : [];

$categories = [
    "Action",
    "Adventure",
    "Arcade",
    "Horror",
    "Platformer",
    "Puzzle",
    "Racing",
    "Simulation",
    "Sports",
    "Strategy"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Products — GameStation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #07101f;
            --surface: #111c2f;
            --surface-light: #1b2940;
            --border: rgba(255,255,255,.08);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #00e5ff;
            --blue: #2563eb;
            --green: #22c55e;
            --red: #ef4444;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(0,229,255,.14), transparent 35%),
                linear-gradient(135deg, var(--bg), #0f172a);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: min(1400px, 92%);
            margin: 0 auto;
            padding: 40px 0 70px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
        }

        .title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
        }

        .back-btn {
            padding: 13px 18px;
            border-radius: 12px;
            background: var(--blue);
            color: white;
            text-decoration: none;
            font-weight: 800;
        }

        .message {
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 13px;
            font-weight: 700;
        }

        .message.success {
            color: #bbf7d0;
            background: rgba(34,197,94,.14);
            border: 1px solid rgba(34,197,94,.35);
        }

        .message.error {
            color: #fecaca;
            background: rgba(239,68,68,.14);
            border: 1px solid rgba(239,68,68,.35);
        }

        .form-box {
            margin-bottom: 38px;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(17,28,47,.96);
            box-shadow: 0 20px 50px rgba(0,0,0,.3);
        }

        .form-box h2 {
            margin-bottom: 20px;
        }

        .product-form,
        .edit-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            color: #cbd5e1;
            font-size: .92rem;
            font-weight: 700;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-light);
            color: white;
            font: inherit;
            outline: none;
        }

        .field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(0,229,255,.1);
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 30px;
        }

        .checkbox-field input {
            width: 20px;
            height: 20px;
        }

        .submit-btn,
        .update-btn,
        .delete-btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: white;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
        }

        .submit-btn {
            grid-column: 1 / -1;
            background: var(--green);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .product-card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(17,28,47,.96);
            box-shadow: 0 18px 45px rgba(0,0,0,.28);
        }

        .product-card > img {
            display: block;
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: white;
        }

        .product-content {
            padding: 22px;
        }

        .product-content h2 {
            margin-bottom: 8px;
        }

        .price {
            color: var(--accent);
            font-size: 1.45rem;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .metadata {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .badge {
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(0,229,255,.1);
            border: 1px solid rgba(0,229,255,.25);
            color: #cffafe;
            font-size: .8rem;
            font-weight: 800;
        }

        .badge.featured {
            color: #422006;
            background: #facc15;
            border-color: #facc15;
        }

        .description {
            color: #cbd5e1;
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .edit-form {
            margin-top: 16px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .edit-form .field,
        .edit-form .checkbox-field {
            grid-column: 1 / -1;
        }

        .update-btn {
            grid-column: 1 / -1;
            background: var(--blue);
        }

        .delete-btn {
            margin-top: 12px;
            background: var(--red);
        }

        @media (max-width: 1000px) {
            .product-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .top-bar {
                align-items: flex-start;
                flex-direction: column;
            }

            .product-form {
                grid-template-columns: 1fr;
            }

            .field.full,
            .submit-btn {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>

<main class="container">

    <header class="top-bar">
        <h1 class="title">🎮 Admin Products</h1>

        <a href="admin.php" class="back-btn">
            ← Back to Admin
        </a>
    </header>

    <?php if ($notice !== ""): ?>
        <div class="message success"><?= h($notice) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="form-box">
        <h2>Add Product</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="product-form"
        >

            <?= csrf_input() ?>

            <div class="field">
                <label for="name">Product name</label>
                <input id="name" type="text" name="name" required>
            </div>

            <div class="field">
                <label for="price">Price</label>
                <input
                    id="price"
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    required
                >
            </div>

            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category) ?>">
                            <?= h($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="game_type">Game type</label>
                <select id="game_type" name="game_type" required>
                    <option value="browser">Browser</option>
                    <option value="download">Download</option>
                </select>
            </div>

            <div class="field">
                <label for="game_file">Game file path</label>
                <input
                    id="game_file"
                    type="text"
                    name="game_file"
                    placeholder="Games/Runner.html"
                >
            </div>

            <div class="field">
                <label for="image">Cover image</label>
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >
            </div>

            <label class="checkbox-field">
                <input type="checkbox" name="featured" value="1">
                Featured game
            </label>

            <div class="field full">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    required
                ></textarea>
            </div>

            <button
                type="submit"
                name="add_product"
                class="submit-btn"
            >
                Add Product
            </button>
        </form>
    </section>

    <section class="products-grid">

        <?php foreach ($products as $product): ?>

            <article class="product-card">

                <img
                    src="<?= h($product["image"]) ?>"
                    alt="<?= h($product["name"]) ?>"
                >

                <div class="product-content">

                    <h2><?= h($product["name"]) ?></h2>

                    <p class="price">
                        <?= number_format((float)$product["price"], 0) ?> kr
                    </p>

                    <div class="metadata">
                        <span class="badge">
                            <?= h($product["category"]) ?>
                        </span>

                        <span class="badge">
                            <?= h(ucfirst($product["game_type"])) ?>
                        </span>

                        <?php if ((int)$product["featured"] === 1): ?>
                            <span class="badge featured">
                                Featured
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="description">
                        <?= h($product["description"]) ?>
                    </p>

                    <button
                        type="button"
                        class="update-btn"
                        onclick="toggleEditForm(<?= (int)$product["id"] ?>)"
                    >
                        Edit Product
                    </button>

                    <div
                        id="edit-form-<?= (int)$product["id"] ?>"
                        style="display:none;"
                    >
                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            class="edit-form"
                        >

                            <?= csrf_input() ?>

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int)$product["id"] ?>"
                            >

                            <div class="field">
                                <label>Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    value="<?= h($product["name"]) ?>"
                                    required
                                >
                            </div>

                            <div class="field">
                                <label>Price</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="price"
                                    value="<?= h($product["price"]) ?>"
                                    required
                                >
                            </div>

                            <div class="field">
                                <label>Category</label>
                                <select name="category" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option
                                            value="<?= h($category) ?>"
                                            <?= $product["category"] === $category
                                                ? "selected"
                                                : ""
                                            ?>
                                        >
                                            <?= h($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label>Game type</label>
                                <select name="game_type" required>
                                    <option
                                        value="browser"
                                        <?= $product["game_type"] === "browser"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >
                                        Browser
                                    </option>

                                    <option
                                        value="download"
                                        <?= $product["game_type"] === "download"
                                            ? "selected"
                                            : ""
                                        ?>
                                    >
                                        Download
                                    </option>
                                </select>
                            </div>

                            <div class="field">
                                <label>Game file path</label>
                                <input
                                    type="text"
                                    name="game_file"
                                    value="<?= h($product["game_file"]) ?>"
                                >
                            </div>

                            <div class="field">
                                <label>Replace cover image</label>
                                <input
                                    type="file"
                                    name="image"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >
                            </div>

                            <label class="checkbox-field">
                                <input
                                    type="checkbox"
                                    name="featured"
                                    value="1"
                                    <?= (int)$product["featured"] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >
                                Featured game
                            </label>

                            <div class="field">
                                <label>Description</label>
                                <textarea
                                    name="description"
                                    required
                                ><?= h($product["description"]) ?></textarea>
                            </div>

                            <button
                                type="submit"
                                name="update_product"
                                class="update-btn"
                            >
                                Save Changes
                            </button>
                        </form>
                    </div>

                    <form
                        method="POST"
                        onsubmit="return confirm('Delete this product?');"
                    >

                        <?= csrf_input() ?>

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int)$product["id"] ?>"
                        >

                        <button
                            type="submit"
                            name="delete_product"
                            class="delete-btn"
                        >
                            Delete Product
                        </button>
                    </form>

                </div>
            </article>

        <?php endforeach; ?>

    </section>

</main>

<script>
function toggleEditForm(productId) {
    const form = document.getElementById("edit-form-" + productId);

    form.style.display =
        form.style.display === "none" ? "block" : "none";
}
</script>

</body>
</html>