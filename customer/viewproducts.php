<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once "../dbconnect.php";

// Fetching categories and brands for filtering
try {
    $sql = "SELECT * FROM categories";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

try {
    $sql = "SELECT * FROM brands";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $brands = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

// --- Filtering logic for GET and POST ---
$where = [];
$params = [];

// Handle Category filter
if (isset($_GET["category"]) && $_GET["category"] != "") {
    $where[] = "p.category_id = ?";
    $params[] = $_GET["category"];
}

// Handle Brand filter
if (isset($_GET["brand"]) && $_GET["brand"] != "") {
    $where[] = "p.brand_id = ?";
    $params[] = $_GET["brand"];
}

// Handle Price filter (POST)
if (isset($_POST["radioBtn"]) && isset($_POST["price"])) {
    $price_range = $_POST["price"];
    if ($price_range == "first") {
        $where[] = "p.price BETWEEN ? AND ?";
        $params[] = 200;
        $params[] = 350;
    } elseif ($price_range == "second") {
        $where[] = "p.price BETWEEN ? AND ?";
        $params[] = 350;
        $params[] = 500;
    } else if ($price_range == "third") {
        $where[] = "p.price BETWEEN ? AND ?";
        $params[] = 501;
        $params[] = 900;
    }
}

// Build the SQL with all selected filters
$sql = "SELECT p.product_id, p.product_name, p.price, p.description, p.stock_quantity, p.image_url, c.cat_name AS category_name, b.brand_name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.product_id ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer View - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'navbarnew.php'; ?>
    </div>
    <div class="row">
        <div class="col-md-2 py-3">
            <!-- Category Filter -->
            <div class="card">
                <form action="viewproducts.php" method="get">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['category_id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '' ?>>
                                <?= $category['cat_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($_GET['brand'])): ?>
                        <input type="hidden" name="brand" value="<?= htmlspecialchars($_GET['brand']) ?>">
                    <?php endif; ?>
                </form>
            </div>

            <!-- Brand Filter -->
            <div class="card my-3">
                <form action="viewproducts.php" method="get">
                    <select name="brand" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['brand_id'] ?>" <?= (isset($_GET['brand']) && $_GET['brand'] == $brand['brand_id']) ? 'selected' : '' ?>>
                                <?= $brand['brand_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($_GET['category']) ?>">
                    <?php endif; ?>
                </form>
            </div>

            <!-- Price Range Filter -->
            <div class="card my-3">
                <form action="viewproducts.php<?= (isset($_GET['category']) || isset($_GET['brand'])) ? '?' . http_build_query(array_filter(['category'=>@$_GET['category'],'brand'=>@$_GET['brand']])) : '' ?>" method="post">
                    <div class="form-check">
                        <input type="radio" name="price" value="first" class="form-check-input" <?php if (isset($_POST['price']) && $_POST['price']=='first') echo 'checked'; ?>>
                        <label class="form-check-label">$200-$350</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="price" value="second" class="form-check-input" <?php if (isset($_POST['price']) && $_POST['price']=='second') echo 'checked'; ?>>
                        <label class="form-check-label">$350-$500</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="price" value="third" class="form-check-input" <?php if (isset($_POST['price']) && $_POST['price']=='third') echo 'checked'; ?>>
                        <label class="form-check-label">$501-$900</label>
                    </div>
                    <button type="submit" name="radioBtn" class="btn btn-outline-primary rounded-pill">Search</button>
                </form>
            </div>

            <!-- Reset Filter Button -->
            <div class="card my-3">
                <form action="viewproducts.php" method="get">
                    <button type="submit" class="btn btn-outline-danger w-100">Reset Filters</button>
                </form>
            </div>
        </div>

        <!-- Products Display -->
        <div class="col-md-10 py-3">
            <?php if (isset($products) && count($products) > 0): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card">
                                <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                    <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</p>
                                    <p class="card-text"><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                                    <p class="card-text"><strong>Stock:</strong> <?= $product['stock_quantity'] ?></p>
                                    <p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($product['category_name']) ?></p>
                                    <p class="card-text"><strong>Brand:</strong> <?= htmlspecialchars($product['brand_name']) ?></p>
                                    <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No products found. Please try different filters.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>