<?php
require_once "../admin_login_check.php";
require_once "../dbconnect.php";
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

// Handle insert
if (isset($_POST["insertBtn"])) {
    $product_name = $_POST["product_name"];
    $price = $_POST["price"];
    $category_id = $_POST["category_id"];
    $brand_id = $_POST["brand_id"];
    $stock_quantity = $_POST["stock_quantity"];
    $description = $_POST["description"];
    $fileImage = $_FILES["product_image"];

    $filePath = "../images/product_images/" . basename($fileImage['name']);

    // upload to a specified directory
    $status = move_uploaded_file($fileImage['tmp_name'], $filePath);
    if ($status) {
        try {
            $sql = "INSERT INTO products (product_name, description, price, stock_quantity, category_id, brand_id, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $flag = $stmt->execute([
                $product_name,
                $description,
                $price,
                $stock_quantity,
                $category_id,
                $brand_id,
                $filePath
            ]);
            $id = $conn->lastInsertId();

            if ($flag) {
                $_SESSION["message"] = "New product with ID $id has been inserted successfully!";
                header("Location: see_all_products.php");
                exit;
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>File upload failed</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Insert Product - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #352826;
            min-height: 100vh;
            color: #DED2C8;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 450px;
            padding: 30px;
        }

        .card {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            border-radius: 22px;
            box-shadow: 0 8px 32px 0 rgba(39, 47, 68, 0.15);
            background: #785A49;
            border: none;
            padding: 0;
        }

        .card-header {
            background: #A57A5B;
            border-radius: 22px 22px 0 0;
            color: #DED2C8;
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
            color: #785A49;
            font-weight: 700;
            letter-spacing: 0.06rem;
            font-size: 1.09rem;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            background: #352826;
            border: 2px solid #A57A5B;
            color: #DED2C8;
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
            border-color: #A57A5B;
            box-shadow: 0 0 0 0.14rem #AECBE260;
        }

        .btn-primary-admin {
            background-color: #352826;
            border: none;
            color: #DED2C8;
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
            background-color: #A57A5B;
            color: #352826;
            box-shadow: 0 4px 24px rgba(67, 90, 138, 0.16);
        }

        .insert-form-row {
            display: flex;
            gap: 70px;
        }

        .insert-form-col {
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

            .insert-form-row {
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
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content insert-form-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Insert New Product
            </div>
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="insert-form-row">
                        <div class="insert-form-col">
                            <div class="mb-4">
                                <label class="form-label" for="product_name">Product Name</label>
                                <input type="text" class="form-control" name="product_name" id="product_name" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="brand_id">Brand</label>
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
                                <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                            </div>
                            <div>
                                <label class="form-label" for="stock_quantity">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" required>
                            </div>
                        </div>
                        <div class="insert-form-col">
                            <div class="mb-4">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="7" style="resize:vertical;"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="product_image">Product Image</label>
                                <input type="file" class="form-control" name="product_image" id="product_image" required>
                            </div>
                            <div class="d-flex justify-content-center mt-5">
                                <button type="submit" name="insertBtn" class="btn btn-primary-admin shadow">Insert Product</button>
                            </div>
                        </div>
                    </div><!-- insert-form-row -->
                </form>
            </div>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>


</html>