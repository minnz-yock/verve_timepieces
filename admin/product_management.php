<?php
require_once('../admin_login_check.php');
require_once('../dbconnect.php');
if (!isset($_SESSION)) session_start();

/* ---------- Helpers ---------- */
function format_mm($n)
{
    if ($n === null || $n === '') return '—';
    $s = number_format((float)$n, 2, '.', '');
    $s = rtrim(rtrim($s, '0'), '.'); // 40.00->40 ; 34.30->34.3
    return $s . ' mm';
}

/* ---------- INSERT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
    $product_name     = $_POST["product_name"] ?? '';
    $price            = $_POST["price"] ?? 0;
    $category_id      = $_POST["category_id"] ?? null;
    $brand_id         = $_POST["brand_id"] ?? null;
    $case_material_id = $_POST["case_material_id"] ?? null;
    $gender_id        = $_POST["gender_id"] ?? null;
    $dial_color_id    = $_POST["dial_color_id"] ?? null;
    $stock_quantity   = $_POST["stock_quantity"] ?? 0;
    $description      = $_POST["description"] ?? '';
    $case_size        = isset($_POST["case_size"]) ? filter_var($_POST["case_size"], FILTER_VALIDATE_FLOAT) : null;

    // validate case size (mm)
    if ($case_size === false || $case_size <= 0 || $case_size > 80) {
        $_SESSION["error"] = "Please enter a valid case size in millimeters (e.g., 40 or 34.33).";
        header("Location: product_management.php");
        exit;
    }

    // image upload
    $filePath = "";
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]['error'] == 0) {
        $target_dir = "../images/product_images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES["product_image"]['name']);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["product_image"]['tmp_name'], $target_file)) {
            $filePath = $target_file;
        }
    }

    if ($filePath !== "") {
        try {
            // INSERT with case_size (no size_id)
            $sql = "INSERT INTO products
                    (product_name, description, price, stock_quantity, category_id, brand_id, image_url,
                     case_material_id, gender_id, dial_color_id, case_size)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $ok = $stmt->execute([
                $product_name,
                $description,
                $price,
                $stock_quantity,
                $category_id,
                $brand_id,
                $filePath,
                $case_material_id,
                $gender_id,
                $dial_color_id,
                $case_size
            ]);
            if ($ok) {
                $_SESSION["message"] = "Product inserted successfully!";
                header("Location: product_management.php");
                exit;
            }
            $_SESSION["error"] = "Insert failed (DB error)";
        } catch (PDOException $e) {
            $_SESSION["error"] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION["error"] = "File upload failed";
    }
}

/* ---------- UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id       = $_POST['product_id'];
    $product_name     = $_POST['product_name'];
    $brand_id         = $_POST['brand_id'];
    $category_id      = $_POST['category_id'];
    $case_material_id = $_POST['case_material_id'];
    $gender_id        = $_POST['gender_id'];
    $dial_color_id    = $_POST['dial_color_id'];
    $description      = $_POST['description'] ?? '';
    $price            = $_POST['price'];
    $stock_quantity   = $_POST['stock_quantity'];
    $case_size        = isset($_POST["case_size"]) ? filter_var($_POST["case_size"], FILTER_VALIDATE_FLOAT) : null;

    if ($case_size === false || $case_size <= 0 || $case_size > 80) {
        $_SESSION["error"] = "Please enter a valid case size in millimeters (e.g., 40 or 34.33).";
        header("Location: product_management.php");
        exit;
    }

    // current image
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id=?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $image_url = $product['image_url'];

    // optional new image
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../images/product_images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    }

    try {
        $sql = "UPDATE products SET
                    product_name=?, description=?, price=?, stock_quantity=?, category_id=?, brand_id=?,
                    case_material_id=?, gender_id=?, dial_color_id=?, image_url=?, case_size=?
                WHERE product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $product_name,
            $description,
            $price,
            $stock_quantity,
            $category_id,
            $brand_id,
            $case_material_id,
            $gender_id,
            $dial_color_id,
            $image_url,
            $case_size,
            $product_id
        ]);
        $_SESSION['updateSuccess'] = "Product updated successfully!";
        header("Location: product_management.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating: " . $e->getMessage();
    }
}

/* ---------- FETCH DROPDOWNS & PRODUCTS ---------- */
try {
    $catStmt = $conn->prepare("SELECT * FROM categories ORDER BY cat_name");
    $catStmt->execute();
    $categories = $catStmt->fetchAll();

    $brandStmt = $conn->prepare("SELECT * FROM brands ORDER BY brand_name");
    $brandStmt->execute();
    $brands = $brandStmt->fetchAll();

    $caseStmt = $conn->prepare("SELECT * FROM case_materials ORDER BY material");
    $caseStmt->execute();
    $case_materials = $caseStmt->fetchAll();

    $genderStmt = $conn->prepare("SELECT * FROM genders ORDER BY gender");
    $genderStmt->execute();
    $genders = $genderStmt->fetchAll();

    $dialStmt = $conn->prepare("SELECT * FROM dial_colors ORDER BY dial_color");
    $dialStmt->execute();
    $dial_colors = $dialStmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    exit;
}

try {
    // Select case_size and drop sizes join entirely
    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.price,
                p.stock_quantity,
                c.cat_name AS category_name,
                b.brand_name AS brand_name,
                p.case_size,
                cm.material AS case_material_name,
                g.gender AS gender_name,
                dc.dial_color AS dial_color_name,
                p.image_url,
                p.category_id,
                p.brand_id,
                p.case_material_id,
                p.gender_id,
                p.dial_color_id,
                p.description
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN case_materials cm ON p.case_material_id = cm.case_material_id
            LEFT JOIN genders g ON p.gender_id = g.gender_id
            LEFT JOIN dial_colors dc ON p.dial_color_id = dc.dial_color_id
            ORDER BY p.product_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Error fetching products: " . $e->getMessage() . "</div>";
    $products = [];
}

/* ---------- Flash ---------- */
$flash = null;
if (!empty($_SESSION['message'])) {
    $flash = ['type' => 'success', 'msg' => $_SESSION['message']];
    unset($_SESSION['message']);
}
if (!empty($_SESSION['updateSuccess'])) {
    $flash = ['type' => 'success', 'msg' => $_SESSION['updateSuccess']];
    unset($_SESSION['updateSuccess']);
}
if (!empty($_SESSION['error'])) {
    $flash = ['type' => 'danger',  'msg' => $_SESSION['error']];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Product Management — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #352826;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            color: #DED2C8;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: 100%;
        }

        .main-content h1 {
            color: #785A49;
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 2.2rem;
            letter-spacing: 0.5px;
        }

        .table {
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #A57A5B;
        }

        .table thead th {
            background-color: #352826;
            color: #DED2C8;
            font-weight: 400;
            border-right: 1px solid #A57A5B;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.4px;
        }

        .table tbody tr {
            background-color: #DED2C8;
            color: #352826;
        }

        .table tbody td {
            vertical-align: middle;
            color: #352826;
            font-size: 0.95rem;
            border-top: 1px solid #785A5B;
            border-right: 1px solid #785A5B;
        }

        .table-hover tbody tr:hover {
            background-color: #A57A5B;
            color: #DED2C8;
        }

        .table-hover tbody tr:hover a,
        .table-hover tbody tr:hover i {
            color: #fff;
        }

        .table-hover tbody tr:hover td {
            border-right-color: #DED2C8;
            border-top-color: #DED2C8;
        }

        .product-image-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        }

        .action-buttons button {
            font-size: .9rem;
            padding: .5rem .9rem;
            border-radius: 5px;
            margin-right: 5px;
        }

        .action-buttons .btn-edit {
            background-color: #785A49;
            border-color: #785A49;
            color: #352826;
        }

        .action-buttons .btn-edit:hover {
            background-color: #DED2C8;
            border-color: #A57A5B;
        }

        .action-buttons .btn-delete {
            background-color: #e74c3c;
            border-color: #DED2C8;
            color: #fff;
        }

        .action-buttons .btn-delete:hover {
            background-color: #352826;
            border-color: #DED2C8;
        }

        .modal-header,
        .modal-footer {
            background-color: #352826;
            color: #DED2C8;
            border-color: #785A49;
        }

        .modal-body {
            background-color: #DED2C8;
            color: #352826;
        }

        .form-control,
        .form-select {
            background-color: #fff;
            color: #352826;
            border: 1px solid #A57A5B;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #785A49;
            box-shadow: 0 0 0 .25rem rgba(120, 90, 73, .25);
        }

        .product-image-preview {
            max-width: 100px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .btn-primary-custom {
            background-color: #785A49 !important;
            border-color: #785A49 !important;
            color: #DED2C8 !important;
        }

        .btn-primary-custom:hover {
            background-color: #352826 !important;
            border-color: #A57A5B !important;
            color: #DED2C8 !important;
        }

        .modal-body .row>div {
            padding: .5rem;
        }

        .modal-body label {
            font-weight: 500;
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }

        .input-group-text {
            background: #fff;
            border-left: 0;
        }

        .input-mm input {
            border-right: 0;
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="m-0"><i class="bi bi-tags me-2"></i>Product Management</h1>
            <button class="btn btn-primary-custom" type="button" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fa fa-plus me-1"></i> Add New Product
            </button>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead>
                            <tr>
                                <th style="width:80px;">ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Case size (mm)</th>
                                <th>Case Material</th>
                                <th>Gender</th>
                                <th>Dial Color</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted p-4">No products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['product_id']) ?></td>
                                        <td>
                                            <?php if ($p['image_url']): ?>
                                                <img src="<?= htmlspecialchars($p['image_url']) ?>" class="product-image-thumbnail" alt="Product Image">
                                            <?php else: ?><i class="fas fa-image text-muted"></i><?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                                        <td>$<?= number_format($p['price'], 2) ?></td>
                                        <td><?= htmlspecialchars($p['stock_quantity']) ?></td>
                                        <td><?= htmlspecialchars($p['brand_name']) ?></td>
                                        <td><?= htmlspecialchars($p['category_name']) ?></td>
                                        <td><?= htmlspecialchars(format_mm($p['case_size'])) ?></td>
                                        <td><?= htmlspecialchars($p['case_material_name']) ?></td>
                                        <td><?= htmlspecialchars($p['gender_name']) ?></td>
                                        <td><?= htmlspecialchars($p['dial_color_name']) ?></td>
                                        <td class="action-buttons">
                                            <button
                                                type="button"
                                                class="btn btn-edit btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editProductModal"
                                                data-id="<?= (int)$p['product_id'] ?>"
                                                data-name="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>"
                                                data-price="<?= htmlspecialchars($p['price'], ENT_QUOTES) ?>"
                                                data-stock="<?= htmlspecialchars($p['stock_quantity'], ENT_QUOTES) ?>"
                                                data-brand-id="<?= (int)$p['brand_id'] ?>"
                                                data-category-id="<?= (int)$p['category_id'] ?>"
                                                data-case-size="<?= htmlspecialchars($p['case_size'], ENT_QUOTES) ?>"
                                                data-case-id="<?= (int)$p['case_material_id'] ?>"
                                                data-gender-id="<?= (int)$p['gender_id'] ?>"
                                                data-dial-id="<?= (int)$p['dial_color_id'] ?>"
                                                data-image-url="<?= htmlspecialchars($p['image_url'], ENT_QUOTES) ?>"
                                                aria-label="Edit product <?= (int)$p['product_id'] ?>">
                                                <i class="fa fa-pen-to-square"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-delete btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteProductModal"
                                                data-product-id="<?= (int)$p['product_id'] ?>"
                                                data-product-name="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>"
                                                aria-label="Delete product <?= (int)$p['product_id'] ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="" method="post" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="insert">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel"><i class="fa fa-plus me-1"></i> Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="product_name_add">Product Name</label>
                            <input type="text" class="form-control" id="product_name_add" name="product_name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="price_add">Price</label>
                            <input type="number" class="form-control" id="price_add" name="price" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="stock_quantity_add">Stock</label>
                            <input type="number" class="form-control" id="stock_quantity_add" name="stock_quantity" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="brand_id_add">Brand</label>
                            <select class="form-select" id="brand_id_add" name="brand_id" required>
                                <option value="" selected disabled>-- Select Brand --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= htmlspecialchars($brand['brand_id']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="category_id_add">Category</label>
                            <select class="form-select" id="category_id_add" name="category_id" required>
                                <option value="" selected disabled>-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['cat_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Numeric case size input with 'mm' suffix -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="case_size_add">Case size</label>
                            <div class="input-group input-mm">
                                <input type="number" class="form-control" id="case_size_add" name="case_size" step="0.01" min="10" max="80" placeholder="e.g., 40 or 34.33" required>
                                <span class="input-group-text">mm</span>
                            </div>
                            <small class="text-muted">Enter millimeters; decimals allowed.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="case_material_id_add">Case Material</label>
                            <select class="form-select" id="case_material_id_add" name="case_material_id" required>
                                <option value="" selected disabled>-- Select Case Material --</option>
                                <?php foreach ($case_materials as $material): ?>
                                    <option value="<?= htmlspecialchars($material['case_material_id']) ?>"><?= htmlspecialchars($material['material']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="gender_id_add">Gender</label>
                            <select class="form-select" id="gender_id_add" name="gender_id" required>
                                <option value="" selected disabled>-- Select Gender --</option>
                                <?php foreach ($genders as $gender): ?>
                                    <option value="<?= htmlspecialchars($gender['gender_id']) ?>"><?= htmlspecialchars($gender['gender']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="dial_color_id_add">Dial Color</label>
                            <select class="form-select" id="dial_color_id_add" name="dial_color_id" required>
                                <option value="" selected disabled>-- Select Dial Color --</option>
                                <?php foreach ($dial_colors as $color): ?>
                                    <option value="<?= htmlspecialchars($color['dial_color_id']) ?>"><?= htmlspecialchars($color['dial_color']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_image_add">Product Image</label>
                            <input type="file" class="form-control" id="product_image_add" name="product_image" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="" method="post" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" id="product_id_edit">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel"><i class="fa fa-pen-to-square me-1"></i> Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="product_name_edit">Product Name</label>
                            <input type="text" class="form-control" id="product_name_edit" name="product_name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="price_edit">Price</label>
                            <input type="number" class="form-control" id="price_edit" name="price" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="stock_quantity_edit">Stock</label>
                            <input type="number" class="form-control" id="stock_quantity_edit" name="stock_quantity" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="brand_id_edit">Brand</label>
                            <select class="form-select" id="brand_id_edit" name="brand_id" required>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= htmlspecialchars($brand['brand_id']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="category_id_edit">Category</label>
                            <select class="form-select" id="category_id_edit" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['cat_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="case_size_edit">Case size</label>
                            <div class="input-group input-mm">
                                <input type="number" class="form-control" id="case_size_edit" name="case_size" step="0.01" min="10" max="80" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="case_material_id_edit">Case Material</label>
                            <select class="form-select" id="case_material_id_edit" name="case_material_id" required>
                                <?php foreach ($case_materials as $material): ?>
                                    <option value="<?= htmlspecialchars($material['case_material_id']) ?>"><?= htmlspecialchars($material['material']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="gender_id_edit">Gender</label>
                            <select class="form-select" id="gender_id_edit" name="gender_id" required>
                                <?php foreach ($genders as $gender): ?>
                                    <option value="<?= htmlspecialchars($gender['gender_id']) ?>"><?= htmlspecialchars($gender['gender']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="dial_color_id_edit">Dial Color</label>
                            <select class="form-select" id="dial_color_id_edit" name="dial_color_id" required>
                                <?php foreach ($dial_colors as $color): ?>
                                    <option value="<?= htmlspecialchars($color['dial_color_id']) ?>"><?= htmlspecialchars($color['dial_color']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_image_edit">Product Image</label>
                            <img id="current_image_preview" src="" alt="Product Image" class="product-image-preview" style="display:none;">
                            <input type="file" class="form-control" id="product_image_edit" name="product_image">
                            <small style="color:#A57A5B;">Leave blank to keep the current image.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the product "<strong id="productToDeleteName"></strong>"? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteProductForm" method="post" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="productToDeleteId">
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Highlight active menu item
            const currentFile = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href').includes(currentFile)) link.classList.add('active');
            });

            // Delete modal data
            const deleteModal = document.getElementById('deleteProductModal');
            deleteModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const productId = button.getAttribute('data-product-id');
                const productName = button.getAttribute('data-product-name');
                const modal = event.currentTarget;
                modal.querySelector('#productToDeleteName').textContent = productName || '';
                modal.querySelector('#productToDeleteId').value = productId || '';
            });

            // Edit modal data (pre-fill all fields, including selects and image)
            const editModal = document.getElementById('editProductModal');
            editModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) return;

                // read data-* via dataset
                const id = button.dataset.id || '';
                const name = button.dataset.name || '';
                const price = button.dataset.price || '';
                const stock = button.dataset.stock || '';
                const brandId = button.dataset.brandId || '';
                const categoryId = button.dataset.categoryId || '';
                const caseSize = button.dataset.caseSize || '';
                const caseId = button.dataset.caseId || '';
                const genderId = button.dataset.genderId || '';
                const dialId = button.dataset.dialId || '';
                const imageUrl = button.dataset.imageUrl || '';

                const modal = editModal;
                modal.querySelector('#product_id_edit').value = id;
                modal.querySelector('#product_name_edit').value = name;
                modal.querySelector('#price_edit').value = price;
                modal.querySelector('#stock_quantity_edit').value = stock;

                const sel = (q) => modal.querySelector(q);
                sel('#brand_id_edit').value = brandId;
                sel('#category_id_edit').value = categoryId;
                sel('#case_material_id_edit').value = caseId;
                sel('#gender_id_edit').value = genderId;
                sel('#dial_color_id_edit').value = dialId;

                modal.querySelector('#case_size_edit').value = caseSize;

                const imagePreview = modal.querySelector('#current_image_preview');
                if (imageUrl && imageUrl !== 'null') {
                    imagePreview.src = imageUrl;
                    imagePreview.style.display = 'block';
                    imagePreview.alt = name || 'Product Image';
                } else {
                    imagePreview.removeAttribute('src');
                    imagePreview.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>