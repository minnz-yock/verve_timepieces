<?php
session_start();

/* ---- SIMPLE ADMIN GUARD & DEPENDENCIES ---- */
if (!isset($_SESSION['first_name']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signinform.php");
    exit();
}
require_once "../dbconnect.php";

/* ---- FLASH HELPERS ---- */
function set_flash($type, $msg)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash()
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---- FORM HANDLERS (CRUD) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Function to handle database transactions for atomicity
    function handle_discount_transaction($conn, $callback)
    {
        try {
            $conn->beginTransaction();
            $callback();
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            set_flash('danger', "Database Error: " . $e->getMessage());
            return false;
        }
    }

    if ($action === 'create_discount' || $action === 'update_discount') {
        $discount_id = (int)($_POST['discount_id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $kind = ($_POST['kind'] === 'percentage') ? 'percent' : 'fixed';
        $value = $_POST['value'] ?? 0.00;
        $starts_at = $_POST['starts_at'] ?? '';
        $ends_at = $_POST['ends_at'] ?? '';
        $is_active = (int)(($_POST['is_active'] ?? 'draft') === 'active');
        $allow_stacking = isset($_POST['allow_stacking']) ? 1 : 0;

        // New logic for targets
        $brand_id = !empty($_POST['brand_target']) ? (int)$_POST['brand_target'] : NULL;
        $category_id = !empty($_POST['category_target']) ? (int)$_POST['category_target'] : NULL;
        $targets = $_POST['targets'] ?? [];

        // Decide which target type to use
        $use_brand = $brand_id !== NULL;
        $use_category = $category_id !== NULL;
        $use_products = !empty($targets);
        if ($use_brand || $use_category) {
            $use_products = false; // If brand/category is set, ignore product targets
        }

        handle_discount_transaction($conn, function () use ($conn, $action, $discount_id, $name, $kind, $value, $starts_at, $ends_at, $is_active, $allow_stacking, $brand_id, $category_id, $targets, $use_products) {
            if ($action === 'create_discount') {
                $sql = "INSERT INTO discounts (name, kind, value, starts_at, ends_at, is_active, allow_stacking, brand_id, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $kind, $value, $starts_at, $ends_at, $is_active, $allow_stacking, $brand_id, $category_id]);
                $discount_id = $conn->lastInsertId();
                set_flash('success', "Discount '{$name}' created successfully!");
            } else { // update_discount
                $sql = "UPDATE discounts SET name=?, kind=?, value=?, starts_at=?, ends_at=?, is_active=?, allow_stacking=?, brand_id=?, category_id=? WHERE discount_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $kind, $value, $starts_at, $ends_at, $is_active, $allow_stacking, $brand_id, $category_id, $discount_id]);
                set_flash('success', "Discount '{$name}' updated successfully!");
            }

            // Manage product-specific targets
            $conn->prepare("DELETE FROM product_discounts WHERE discount_id=?")->execute([$discount_id]);
            if ($use_products && !empty($targets)) {
                $sql_product_discount = "INSERT INTO product_discounts (discount_id, product_id) VALUES (?, ?)";
                $stmt_product_discount = $conn->prepare($sql_product_discount);
                foreach ($targets as $product_id) {
                    $stmt_product_discount->execute([$discount_id, $product_id]);
                }
            }
        });
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'delete_discount') {
        $discount_id = (int)$_POST['id'];
        handle_discount_transaction($conn, function () use ($conn, $discount_id) {
            $stmt = $conn->prepare("DELETE FROM discounts WHERE discount_id = ?");
            $stmt->execute([$discount_id]);
            set_flash('success', "Discount deleted successfully.");
        });
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

/* ---- FETCH DATA FOR DISPLAY ---- */
$flash = get_flash();
$search = $_GET['search'] ?? '';

// Build the SQL query for listing discounts
$sql = "SELECT 
            d.discount_id, d.name, d.kind, d.value, d.starts_at, d.ends_at, d.is_active, d.allow_stacking, d.brand_id, d.category_id,
            b.brand_name, c.cat_name, 
            GROUP_CONCAT(p.product_name SEPARATOR ', ') as targets_str
        FROM discounts d
        LEFT JOIN product_discounts pd ON d.discount_id = pd.discount_id
        LEFT JOIN products p ON pd.product_id = p.product_id
        LEFT JOIN brands b ON d.brand_id = b.brand_id
        LEFT JOIN categories c ON d.category_id = c.category_id";

$params = [];
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "d.name LIKE ?";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm]);
}

$group_by = "GROUP BY d.discount_id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " " . $group_by . " ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products, brands, and categories for the form dropdowns
$products = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
$brands = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT category_id, cat_name FROM categories ORDER BY cat_name")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discounts Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        /* Updated Brown Color Theme */
        body {
            background-color: #DED2C8;
            color: #352826;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: 100%;
        }

        .main-content h1 {
            color: #352826;
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 2.2rem;
            letter-spacing: 0.5px;
        }

        .card {
            background: #785A49;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(53, 40, 38, 0.08);
            border: 1px solid #A57A5B;
            margin-bottom: 30px;
        }

        .card-header {
            background: #A57A5B;
            border-bottom: 1px solid #785A49;
            border-radius: 12px 12px 0 0;
            color: #DED2C8;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
            color: #DED2C8;
        }

        .table-custom {
            background-color: #785A49;
            border-radius: 12px;
            overflow: hidden;
            color: #DED2C8;
            border-collapse: collapse;
        }

        .table-custom thead tr th {
            background-color: #A57A5B;
            color: #DED2C8;
            border-bottom: 1px solid #352826;
            border-right: 1px solid #352826;
        }

        .table-custom thead tr th:last-child {
            border-right: none;
        }

        .table-custom tbody tr {
            border-bottom: 1px solid #352826;
        }

        .table-custom tbody tr:last-child {
            border-bottom: none;
        }

        .table-custom tbody tr td {
            color: #A57A5B;
            border-right: 1px solid #352826;
        }

        .table-custom tbody tr td:last-child {
            border-right: none;
        }

        .btn-custom-primary {
            background-color: #A57A5B ;
            border-color: #A57A5B required;
            color: #352826 required;
        }

        .btn-custom-primary:hover {
            background-color: #785A49 required;
            border-color: #785A49 required;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include('admin_sidebar.php'); ?>

        <div class="main-content">
            <h1>Discounts Management</h1>

            <?php if ($flash) : ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <form class="d-flex" action="" method="get">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search by name" aria-label="Search" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </form>
                <button type="button" class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#addEditDiscountModal">
                    <i class="bi bi-plus-lg me-2"></i> Add New Discount
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-tag me-2"></i> All Discounts
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th>Active Window</th>
                                    <th>Status</th>
                                    <th>Targets</th>
                                    <th>Stacking</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($discounts) > 0) : ?>
                                    <?php foreach ($discounts as $discount) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($discount['name'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if ($discount['kind'] === 'percent') : ?>
                                                    <?= htmlspecialchars($discount['value']) ?>%
                                                <?php else : ?>
                                                    $<?= number_format($discount['value'], 2) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars(date('M d, Y', strtotime($discount['starts_at']))) ?>
                                                -
                                                <?= htmlspecialchars(date('M d, Y', strtotime($discount['ends_at']))) ?>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?= $discount['is_active'] ? 'success' : 'warning' ?>">
                                                    <?= $discount['is_active'] ? 'Active' : 'Draft' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if (!empty($discount['brand_name'])) {
                                                    echo 'Brand: ' . htmlspecialchars($discount['brand_name']);
                                                } else if (!empty($discount['cat_name'])) {
                                                    echo 'Category: ' . htmlspecialchars($discount['cat_name']);
                                                } else if (!empty($discount['targets_str'])) {
                                                    echo 'Products: ' . htmlspecialchars($discount['targets_str']);
                                                } else {
                                                    echo 'All Products';
                                                }
                                                ?>
                                            </td>
                                            <td><?= $discount['allow_stacking'] ? 'Yes' : 'No' ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#addEditDiscountModal"
                                                    data-discount-id="<?= $discount['discount_id'] ?>"
                                                    data-name="<?= htmlspecialchars($discount['name']) ?>"
                                                    data-kind="<?= htmlspecialchars($discount['kind']) ?>"
                                                    data-value="<?= htmlspecialchars($discount['value']) ?>"
                                                    data-starts-at="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($discount['starts_at']))) ?>"
                                                    data-ends-at="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($discount['ends_at']))) ?>"
                                                    data-is-active="<?= $discount['is_active'] ?>"
                                                    data-allow-stacking="<?= $discount['allow_stacking'] ?>"
                                                    data-brand-id="<?= htmlspecialchars($discount['brand_id'] ?? '') ?>"
                                                    data-category-id="<?= htmlspecialchars($discount['category_id'] ?? '') ?>"
                                                    data-targets='<?= json_encode(explode(', ', $discount['targets_str'])) ?>'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteDiscountModal" data-discount-id="<?= $discount['discount_id'] ?>" data-discount-name="<?= htmlspecialchars($discount['name']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No discounts found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addEditDiscountModal" tabindex="-1" aria-labelledby="addEditDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEditDiscountModalLabel">Add New Discount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addEditForm" action="discounts_management.php" method="POST">
                        <input type="hidden" name="action" id="modalAction" value="create_discount">
                        <input type="hidden" name="discount_id" id="discountId">

                        <div class="mb-3">
                            <label for="name" class="form-label">Discount Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="kind" class="form-label">Type</label>
                                <select class="form-select" id="kind" name="kind" required>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed_amount">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="value" class="form-label">Value</label>
                                <input type="number" step="0.01" class="form-control" id="value" name="value" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="starts_at" class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ends_at" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="allow_stacking" name="allow_stacking">
                            <label class="form-check-label" for="allow_stacking">Allow with other discounts?</label>
                        </div>
                        <div class="mb-3">
                            <label for="targets" class="form-label">Apply to specific products</label>
                            <select class="form-select" id="targets" name="targets[]" multiple>
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?= htmlspecialchars($product['product_id']) ?>"><?= htmlspecialchars($product['product_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">To select multiple products, hold down Ctrl (Windows) or Cmd (Mac).</small>
                        </div>
                        <div class="mb-3">
                            <p class="form-label">Or apply to a Brand or Category (selecting one will override product selections):</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="brand_target" class="form-label">Brand</label>
                                    <select class="form-select" id="brand_target" name="brand_target">
                                        <option value="">Select a brand</option>
                                        <?php foreach ($brands as $brand) : ?>
                                            <option value="<?= htmlspecialchars($brand['brand_id']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="category_target" class="form-label">Category</label>
                                    <select class="form-select" id="category_target" name="category_target">
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['cat_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-primary">Save Discount</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="deleteDiscountModal" tabindex="-1" aria-labelledby="deleteDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDiscountModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the discount: <b id="discountToDeleteName"></b>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="discounts_management.php" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete_discount">
                        <input type="hidden" name="id" id="discountToDeleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script to handle modal behavior for create/edit
        document.getElementById('addEditDiscountModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            const form = modal.querySelector('#addEditForm');
            const productSelect = modal.querySelector('#targets');

            if (button && button.hasAttribute('data-discount-id')) {
                // Edit mode
                const discountId = button.getAttribute('data-discount-id');
                const name = button.getAttribute('data-name');
                const kind = button.getAttribute('data-kind');
                const value = button.getAttribute('data-value');
                const starts_at = button.getAttribute('data-starts-at');
                const ends_at = button.getAttribute('data-ends-at');
                const is_active = button.getAttribute('data-is-active');
                const allow_stacking = button.getAttribute('data-allow-stacking') === '1';
                const brandId = button.getAttribute('data-brand-id');
                const categoryId = button.getAttribute('data-category-id');
                const targets = JSON.parse(button.getAttribute('data-targets'));

                modal.querySelector('#modalAction').value = 'update_discount';
                modal.querySelector('.modal-title').textContent = 'Edit Discount';
                modal.querySelector('#discountId').value = discountId;
                modal.querySelector('#name').value = name;
                modal.querySelector('#kind').value = kind === 'percent' ? 'percentage' : 'fixed_amount';
                modal.querySelector('#value').value = value;
                modal.querySelector('#starts_at').value = starts_at;
                modal.querySelector('#ends_at').value = ends_at;
                modal.querySelector('#is_active').value = is_active === '1' ? 'active' : 'draft';
                modal.querySelector('#allow_stacking').checked = allow_stacking;

                // Set brand and category targets, and disable product selection
                modal.querySelector('#brand_target').value = brandId;
                modal.querySelector('#category_target').value = categoryId;
                if (brandId || categoryId) {
                    productSelect.disabled = true;
                } else {
                    productSelect.disabled = false;
                }

                // Handle multi-select for products
                Array.from(productSelect.options).forEach(option => {
                    option.selected = targets.includes(option.textContent.trim());
                });

            } else {
                // Add mode
                modal.querySelector('#modalAction').value = 'create_discount';
                modal.querySelector('.modal-title').textContent = 'Add New Discount';
                form.reset();
                // Clear any pre-selected options from a previous edit
                Array.from(productSelect.options).forEach(option => {
                    option.selected = false;
                });
                productSelect.disabled = false;
            }
        });

        // Script to handle delete confirmation modal
        document.getElementById('deleteDiscountModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const discountId = button.getAttribute('data-discount-id');
            const discountName = button.getAttribute('data-discount-name');
            const modal = this;
            modal.querySelector('#discountToDeleteName').textContent = discountName;
            modal.querySelector('#discountToDeleteId').value = discountId;
        });

        // Script to handle brand/category selection overriding product selection
        document.getElementById('brand_target').addEventListener('change', function() {
            const productSelect = document.getElementById('targets');
            const categorySelect = document.getElementById('category_target');
            if (this.value) {
                productSelect.disabled = true;
                categorySelect.value = '';
                Array.from(productSelect.options).forEach(option => option.selected = false);
            } else {
                if (!categorySelect.value) {
                    productSelect.disabled = false;
                }
            }
        });

        document.getElementById('category_target').addEventListener('change', function() {
            const productSelect = document.getElementById('targets');
            const brandSelect = document.getElementById('brand_target');
            if (this.value) {
                productSelect.disabled = true;
                brandSelect.value = '';
                Array.from(productSelect.options).forEach(option => option.selected = false);
            } else {
                if (!brandSelect.value) {
                    productSelect.disabled = false;
                }
            }
        });

        document.getElementById('targets').addEventListener('change', function() {
            const brandSelect = document.getElementById('brand_target');
            const categorySelect = document.getElementById('category_target');
            if (Array.from(this.options).some(option => option.selected)) {
                brandSelect.value = '';
                categorySelect.value = '';
            }
        });

        // Script to highlight active menu item
        document.addEventListener('DOMContentLoaded', (event) => {
            const currentFile = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href').includes(currentFile)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>