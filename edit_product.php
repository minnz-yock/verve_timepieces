<?php
require_once('admin_login_check.php');
require_once('dbconnect.php');

if (!isset($_SESSION)) session_start();

// Fetch categories and brands for the dropdowns
try {
    $catStmt = $conn->prepare("SELECT * FROM categories");
    $catStmt->execute();
    $categories = $catStmt->fetchAll();

    $brandStmt = $conn->prepare("SELECT * FROM brands");
    $brandStmt->execute();
    $brands = $brandStmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    exit;
}

// Fetch product info for edit
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            echo "<div class='alert alert-danger'>Product not found.</div>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>Invalid product ID.</div>";
    exit;
}

// Handle Update
if (isset($_POST['updateBtn'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $brand_id = $_POST['brand_id'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    // Handle image upload if a new image is selected
    $image_url = $product['image_url'];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "images/product_images/";
        $filename = basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    }

    try {
        $sql = "UPDATE products SET product_name=?, description=?, price=?, stock_quantity=?, category_id=?, brand_id=?, image_url=? WHERE product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $product_name,
            $description,
            $price,
            $stock_quantity,
            $category_id,
            $brand_id,
            $image_url,
            $product_id
        ]);
        $_SESSION['updateSuccess'] = "Product updated successfully!";
        header("Location: see_all_products.php");
        exit;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error updating: " . $e->getMessage() . "</div>";
    }
}

// Get previous selected category and brand
$prevCategory = '';
$prevBrand = '';
if ($product && !empty($categories)) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $product['category_id']) {
            $prevCategory = $cat['cat_name'];
            break;
        }
    }
}
if ($product && !empty($brands)) {
    foreach ($brands as $brand) {
        if ($brand['brand_id'] == $product['brand_id']) {
            $prevBrand = $brand['brand_name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #272F44 0%, #4D6CA8 100%);
            min-height: 100vh;
            color: #F3F8FB;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 450px;
            padding: 30px;
        }

        .centered-form-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
            width: 100%;
        }

        .card {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            border-radius: 22px;
            box-shadow: 0 8px 32px 0 rgba(39, 47, 68, 0.15);
            background: #3A4B6E;
            border: none;
            padding: 0;
        }

        .card-header {
            background: #4D6CA8;
            border-radius: 22px 22px 0 0;
            color: #F3F8FB;
            font-size: 1.45rem;
            font-weight: 700;
            padding: 1.7rem 2.5rem 1.3rem 2.5rem;
            border-bottom: none;
            letter-spacing: 0.04rem;
        }

        .card-body {
            padding: 3rem 3rem 2.2rem 3rem;
        }

        .form-label {
            color: #435A8A;
            font-weight: 700;
            letter-spacing: 0.06rem;
            font-size: 1.09rem;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            background: #272F44;
            border: 2px solid #87AFD3;
            color: #F3F8FB;
            border-radius: 10px;
            font-size: 1.07rem;
            padding: 0.9rem 1.15rem;
            transition: border 0.18s, box-shadow 0.18s;
            min-height: 48px;
            width: 100%;
            box-shadow: none;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6792C5;
            box-shadow: 0 0 0 0.14rem #AECBE260;
        }

        .brand-preview,
        .category-preview {
            color: #577EB9;
            font-size: 1.01rem;
            background: #AECBE2;
            border-radius: 7px;
            display: inline-block;
            padding: 4px 14px 4px 14px;
            margin-bottom: 11px;
            font-weight: 550;
            letter-spacing: 0.03rem;
        }

        .product-image-preview {
            width: 160px;
            border-radius: 10px;
            background: #272F44;
            border: 2px solid #435A8A;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(57, 89, 146, 0.19);
        }

        .btn-primary-admin {
            background: linear-gradient(90deg, #6792C5 55%, #87AFD3 100%);
            border: none;
            color: #F3F8FB;
            font-weight: 700;
            font-size: 1.18rem;
            border-radius: 14px;
            padding: 15px 54px;
            letter-spacing: 0.07rem;
            transition: background 0.22s, color 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 14px 0 rgba(103, 146, 197, 0.19);
        }

        .btn-primary-admin:hover,
        .btn-primary-admin:focus {
            background: linear-gradient(90deg, #577EB9 20%, #4D6CA8 100%);
            color: #272F44;
            box-shadow: 0 4px 24px rgba(67, 90, 138, 0.16);
        }

        .edit-form-row {
            display: flex;
            gap: 70px;
        }

        .edit-form-col {
            flex: 1 1 0;
            min-width: 0;
        }

        @media (max-width: 1200px) {
            .card {
                max-width: 99%;
            }

            .card-body {
                padding: 2rem 1rem 1.5rem 1rem;
            }

            .edit-form-row {
                flex-direction: column;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .card {
                width: 100%;
            }

            .card-body {
                padding: 0.7rem 0.4rem 1.2rem 0.4rem;
            }

            .brand-preview,
            .category-preview {
                margin-bottom: 6px;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content edit-form-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Edit Product
            </div>
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
                    <div class="edit-form-row">
                        <div class="edit-form-col">
                            <div class="mb-4">
                                <label class="form-label" for="product_name">Product Name</label>
                                <input type="text" class="form-control" name="product_name" id="product_name"
                                    required value="<?= htmlspecialchars($product['product_name']) ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="brand_id">Brand</label>
                                <div class="mb-1">
                                    <div class="brand-preview">
                                        Previous Brand: <?= htmlspecialchars($prevBrand) ?>
                                    </div>
                                </div>
                                <select class="form-select" name="brand_id" id="brand_id" required>
                                    <option value="" selected>-- Select Brand --</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?= $brand['brand_id'] ?>">
                                            <?= htmlspecialchars($brand['brand_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="category_id">Category</label>
                                <div class="mb-1">
                                    <div class="category-preview">
                                        Previous Category: <?= htmlspecialchars($prevCategory) ?>
                                    </div>
                                </div>
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="" selected>-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>">
                                            <?= htmlspecialchars($cat['cat_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="price">Price</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="price" required value="<?= htmlspecialchars($product['price']) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="stock_quantity">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" required value="<?= htmlspecialchars($product['stock_quantity']) ?>">
                            </div>
                        </div>
                        <div class="edit-form-col">
                            <div class="mb-4">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="7" style="resize:vertical;"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="product_image">Product Image</label>
                                <?php if ($product['image_url'] && file_exists($product['image_url'])): ?>
                                    <div>
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image" class="product-image-preview">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="product_image" id="product_image">
                                <small style="color:#AECBE2;">Leave blank to keep the current image.</small>
                            </div>
                            <div class="d-flex justify-content-center mt-5">
                                <button type="submit" name="updateBtn" class="btn btn-primary-admin shadow">Update Product</button>
                            </div>
                        </div>
                    </div><!-- edit-form-row -->
                </form>
            </div>
        </div>
    </div>
</body>

</html>