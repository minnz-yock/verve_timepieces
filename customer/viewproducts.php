<?php
// if (!isset($_SESSION)) {
//     session_start();
// }

require_once "../dbconnect.php";
require_once('../user_login_check.php');
// Fetching categories, brands, sizes, case materials, genders, and dial colors for filtering
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

try {
    $sql = "SELECT * FROM sizes";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $sizes = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

try {
    $sql = "SELECT * FROM case_materials";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $case_materials = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

try {
    $sql = "SELECT * FROM genders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $genders = $stmt->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage();
}

try {
    $sql = "SELECT * FROM dial_colors";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dial_colors = $stmt->fetchAll();
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

// Handle Size filter
if (isset($_GET["size"]) && $_GET["size"] != "") {
    $where[] = "p.size_id = ?";
    $params[] = $_GET["size"];
}

// Handle Case Material filter
if (isset($_GET["case_material"]) && $_GET["case_material"] != "") {
    $where[] = "p.case_material_id = ?";
    $params[] = $_GET["case_material"];
}

// Handle Gender filter
if (isset($_GET["gender"]) && $_GET["gender"] != "") {
    $where[] = "p.gender_id = ?";
    $params[] = $_GET["gender"];
}

// Handle Dial Color filter
if (isset($_GET["dial_color"]) && $_GET["dial_color"] != "") {
    $where[] = "p.dial_color_id = ?";
    $params[] = $_GET["dial_color"];
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
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN sizes s ON p.size_id = s.size_id
        LEFT JOIN case_materials cm ON p.case_material_id = cm.case_material_id
        LEFT JOIN genders g ON p.gender_id = g.gender_id
        LEFT JOIN dial_colors dc ON p.dial_color_id = dc.dial_color_id";

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

    <!-- Filter Section - moved to top -->
    <div class="row my-3">
        <div class="col-md-12 py-3">
            <form action="viewproducts.php" method="get">
                <!-- Category Filter -->
                <div class="d-inline-block me-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['category_id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '' ?>>
                                <?= $category['cat_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Brand Filter -->
                <div class="d-inline-block me-3">
                    <select name="brand" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['brand_id'] ?>" <?= (isset($_GET['brand']) && $_GET['brand'] == $brand['brand_id']) ? 'selected' : '' ?>>
                                <?= $brand['brand_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Size Filter -->
                <div class="d-inline-block me-3">
                    <select name="size" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Size</option>
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?= $size['size_id'] ?>" <?= (isset($_GET['size']) && $_GET['size'] == $size['size_id']) ? 'selected' : '' ?>>
                                <?= $size['size'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Case Material Filter -->
                <div class="d-inline-block me-3">
                    <select name="case_material" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Case Material</option>
                        <?php foreach ($case_materials as $material): ?>
                            <option value="<?= $material['case_material_id'] ?>" <?= (isset($_GET['case_material']) && $_GET['case_material'] == $material['case_material_id']) ? 'selected' : '' ?>>
                                <?= $material['material'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gender Filter -->
                <div class="d-inline-block me-3">
                    <select name="gender" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Gender</option>
                        <?php foreach ($genders as $gender): ?>
                            <option value="<?= $gender['gender_id'] ?>" <?= (isset($_GET['gender']) && $_GET['gender'] == $gender['gender_id']) ? 'selected' : '' ?>>
                                <?= $gender['gender'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dial Color Filter -->
                <div class="d-inline-block me-3">
                    <select name="dial_color" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Dial Color</option>
                        <?php foreach ($dial_colors as $color): ?>
                            <option value="<?= $color['dial_color_id'] ?>" <?= (isset($_GET['dial_color']) && $_GET['dial_color'] == $color['dial_color_id']) ? 'selected' : '' ?>>
                                <?= $color['dial_color'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Filters Button -->
                <div class="d-inline-block ms-3">
                    <a href="viewproducts.php" class="btn btn-outline-danger">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Display -->
    <div class="row">
        <?php if (isset($products) && count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['brand_name']) ?></h5>
                            <p class="card-title"><?= htmlspecialchars($product['product_name']) ?></p> <!-- Product Name below brand -->
                            <p class="card-text"><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                            <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info w-100">No products found. Please try different filters.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
