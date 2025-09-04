<?php

require_once('../admin_login_check.php');

require_once('../dbconnect.php');


try {

    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.price,
                p.stock_quantity,
                c.cat_name AS category_name,
                b.brand_name AS brand_name,
                p.image_url
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            ORDER BY p.product_id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {

    echo "<div class='alert alert-danger' role='alert'>Error fetching products: " . $e->getMessage() . "</div>";
    $products = [];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #352826;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
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

        .card {
            background: #785A49;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(27, 45, 68, 0.20);
            border: 1px solid #A57A5B;
            margin-bottom: 30px;
        }

        .card-header {
            background: #A57A5B;
            border-bottom: 1px solid #785A49;
            border-radius: 12px 12px 0 0;
            padding: 15px 20px;
            font-weight: 600;
            color: #DED2C8;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #A57A5B;
            color: #DED2C8;
            font-weight: 600;
            border-bottom: 2px solid #785A49;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            vertical-align: middle;
            color: #352826;
            font-size: 0.95rem;
            border-top: 1px solid #785A49;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-hover tbody tr:hover {
    background-color: #785A49;
}

.action-buttons button,
.action-buttons a {
    margin-right: 5px;
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
    border-radius: 5px;
}

.action-buttons .btn-edit {
    background-color: #A57A5B;
    border-color: #A57A5B;
    color: white;
}

.action-buttons .btn-edit:hover {
    background-color: #785A49;
    border-color: #785A49;
}

.action-buttons .btn-delete {
    background-color: #e74c3c;
    border-color: #e74c3c;
    color: white;
}

.action-buttons .btn-delete:hover {
    background-color: #c0392b;
    border-color: #c0392b;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    color: #DED2C8;
    font-weight: 600;
    font-size: 0.85rem;
}

.form-control {
    background: #352826;
    border: 1px solid #A57A5B;
    color: #DED2C8;
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    border-color: #A57A5B;
    box-shadow: 0 0 0 2px rgba(167, 122, 91, 0.20);
}

.form-control::placeholder {
    color: #DED2C8;
}

.btn-primary-admin {
    background: #A57A5B;
    border: none;
    color: #DED2C8;
    font-weight: 700;
    letter-spacing: 0.8px;
    border-radius: 6px;
    padding: 0.6rem 1rem;
    transition: background 0.2s;
}

.btn-primary-admin:hover {
    background: #785A49;
}

.nav-item.active>a {
    background-color: #A57A5B !important;
    color: #DED2C8 !important;
    border-left: 4px solid #785A49 !important;
}

@media (max-width: 991.98px) {
    .sidebar {
        display: none;
    }

    .main-content {
        margin-left: 0;
    }
}
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <h1>All Products</h1>

        <?php if (empty($products)): ?>
            <div class="alert alert-info" role="alert">
                No products found. Add some products to get started!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <!-- Display product image -->
                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100px; height: auto; border-radius: 4px;">
                                    <?php else: ?>
                                        <!-- Placeholder if no image -->
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Edit Link -->
                                        <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-edit btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>

                                        <!-- Delete Button (triggers modal) -->
                                        <button type="button" class="btn btn-delete btn-sm" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-product-id="<?php echo $product['product_id']; ?>" data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Product Modal Structure -->
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
                    <form id="deleteProductForm" action="delete_product.php" method="post" style="display: inline;">
                        <input type="hidden" name="product_id" id="productToDeleteId">
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script to highlight active menu item
        document.addEventListener('DOMContentLoaded', (event) => {
            const currentFile = window.location.pathname.split('/').pop(); // Get the current file name (e.g., 'admin_dashboard.php')

            // Highlight the active main navigation link
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                // Check if the link's href matches the current file name
                if (link.getAttribute('href') === currentFile) {
                    link.classList.add('active');
                }
            });
        });

        // --- Modal handling for delete confirmation ---
        // When the delete button is clicked, populate the modal with product details
        document.getElementById('deleteProductModal').addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            // Extract product info from data-attributes
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');

            // Update the modal's content fields
            const modal = this;
            modal.querySelector('#productToDeleteName').textContent = productName;
            modal.querySelector('#productToDeleteId').value = productId;
        });
    </script>
</body>

</html>