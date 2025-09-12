<?php
session_start();

/* ---- SIMPLE ADMIN GUARD ---- */
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

function money($n) {
    return '$' . number_format((float)$n, 2);
}

/* ---- FORM HANDLERS ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {
        $orderId = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $validStatuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
        if (in_array($status, $validStatuses)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $orderId]);
            set_flash('success', 'Order status updated successfully.');
        } else {
            set_flash('danger', 'Invalid status.');
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ---- GET SHOWCASE DATA ---- */
$showcase = [];
$showcase['total'] = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$showcase['pending'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$showcase['processing'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
$showcase['shipped'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
$showcase['out_for_delivery'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'out_for_delivery'")->fetchColumn();
$showcase['delivered'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();

/* ---- GET ORDERS DATA ---- */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $whereConditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$ordersQuery = "
    SELECT o.order_id, o.order_number, o.total, o.status, o.created_at,
           u.first_name, u.last_name, u.email,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    $whereClause
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($ordersQuery);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---- GET ORDER DETAILS FOR POPUP ---- */
$orderDetails = null;
if (isset($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    
    $orderStmt = $conn->prepare('
        SELECT o.*, 
               u.first_name, u.last_name, u.email,
               ba.first_name as bill_first_name, ba.last_name as bill_last_name, ba.country_region as bill_country,
               ba.street_address as bill_street, ba.address_line2 as bill_line2, ba.city_town as bill_city, ba.phone as bill_phone,
               sa.first_name as ship_first_name, sa.last_name as ship_last_name, sa.country_region as ship_country,
               sa.street_address as ship_street, sa.address_line2 as ship_line2, sa.city_town as ship_city, sa.phone as ship_phone,
               p.method as payment_method, p.details as payment_details
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN bill_address ba ON o.billing_address_id = ba.id
        LEFT JOIN ship_address sa ON o.shipping_address_id = sa.id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = ?
    ');
    $orderStmt->execute([$orderId]);
    $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orderDetails) {
        $itemsStmt = $conn->prepare('
            SELECT oi.*, p.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ');
        $itemsStmt->execute([$orderId]);
        $orderDetails['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #352826;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            padding: 1.5rem;
            margin-left: 380px;
            max-width: calc(100vw - 235px);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #352826;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #785A49;
            font-size: 1.1rem;
        }

        .showcase-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .showcase-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
            transition: all 0.3s ease;
        }

        .showcase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .showcase-card .icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .showcase-card.total .icon { background: #e3f2fd; color: #1976d2; }
        .showcase-card.pending .icon { background: #fff3e0; color: #f57c00; }
        .showcase-card.processing .icon { background: #e8f5e8; color: #388e3c; }
        .showcase-card.shipped .icon { background: #e1f5fe; color: #0288d1; }
        .showcase-card.out-for-delivery .icon { background: #f3e5f5; color: #7b1fa2; }
        .showcase-card.delivered .icon { background: #e8f5e8; color: #2e7d32; }

        .showcase-card .title {
            font-size: 0.9rem;
            color: #785A49;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .showcase-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #352826;
        }

        .search-filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
        }

        .search-input {
            border: 1px solid #DED2C8;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .search-input:focus {
            border-color: #785A49;
            box-shadow: 0 0 0 0.2rem rgba(120, 90, 73, 0.25);
        }

        .filter-select {
            border: 1px solid #DED2C8;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            background: white;
        }

        .filter-select:focus {
            border-color: #785A49;
            box-shadow: 0 0 0 0.2rem rgba(120, 90, 73, 0.25);
        }

        .orders-table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid #DED2C8;
            color: #352826;
            font-weight: 600;
            padding: 1rem 0.75rem;
        }

        .table td {
            border-top: 1px solid #DED2C8;
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }

        .customer-info {
            font-size: 0.9rem;
        }

        .customer-name {
            font-weight: 600;
            color: #352826;
        }

        .customer-email {
            color: #785A49;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #d1ecf1; color: #0c5460; }
        .status-badge.shipped { background: #cce5ff; color: #004085; }
        .status-badge.out-for-delivery { background: #fff3cd; color: #856404; }
        .status-badge.delivered { background: #d4edda; color: #155724; }
        .status-badge.cancelled { background: #f8d7da; color: #721c24; }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .btn-view-details {
            background: #785A49;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .btn-view-details:hover {
            background: #A57A5B;
            color: white;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #785A49 0%, #A57A5B 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-title {
            font-weight: 600;
        }

        .order-status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .order-status-badge.pending { background: #fff3cd; color: #856404; }
        .order-status-badge.processing { background: #d1ecf1; color: #0c5460; }
        .order-status-badge.shipped { background: #cce5ff; color: #004085; }
        .order-status-badge.out-for-delivery { background: #fff3cd; color: #856404; }
        .order-status-badge.delivered { background: #d4edda; color: #155724; }
        .order-status-badge.cancelled { background: #f8d7da; color: #721c24; }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #DED2C8;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .order-item-image {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 6px;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #DED2C8;
        }

        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-item-image .placeholder {
            color: #DED2C8;
            font-size: 1.5rem;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-brand {
            color: #785A49;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.2rem;
        }

        .order-item-name {
            font-weight: 600;
            color: #352826;
            margin-bottom: 0.3rem;
        }

        .order-item-quantity {
            color: #785A49;
            font-size: 0.8rem;
        }

        .order-item-price {
            font-weight: 600;
            color: #352826;
            font-size: 1rem;
        }

        .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .summary-row.total {
            border-top: 1px solid #DED2C8;
            padding-top: 0.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .address-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .address-title {
            font-weight: 600;
            color: #352826;
            margin-bottom: 0.5rem;
        }

        .address-text {
            color: #785A49;
            line-height: 1.5;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 991.98px) {
            .content-area {
                margin-left: 0;
                max-width: 100vw;
                padding: 1rem;
            }
            
            .showcase-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .showcase-cards {
                grid-template-columns: 1fr;
            }
            
            .search-filter-section .row {
                flex-direction: column;
            }
            
            .search-filter-section .col-md-8,
            .search-filter-section .col-md-4 {
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .customer-info {
                font-size: 0.8rem;
            }
            
            .status-badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <?php include 'admin_sidebar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <h1 class="page-title">Order Management</h1>
                <p class="page-subtitle">All orders managed and tracked.</p>
            </div>

            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Showcase Cards -->
            <div class="showcase-cards">
                <div class="showcase-card total">
                    <div class="icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="title">Total Orders</div>
                    <div class="value"><?= $showcase['total'] ?></div>
                </div>
                
                <div class="showcase-card pending">
                    <div class="icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="title">Pending</div>
                    <div class="value"><?= $showcase['pending'] ?></div>
                </div>
                
                <div class="showcase-card processing">
                    <div class="icon">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div class="title">Processing</div>
                    <div class="value"><?= $showcase['processing'] ?></div>
                </div>
                
                <div class="showcase-card shipped">
                    <div class="icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="title">Shipped</div>
                    <div class="value"><?= $showcase['shipped'] ?></div>
                </div>
                
                <div class="showcase-card out-for-delivery">
                    <div class="icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div class="title">Out for Delivery</div>
                    <div class="value"><?= $showcase['out_for_delivery'] ?></div>
                </div>
                
                <div class="showcase-card delivered">
                    <div class="icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="title">Delivered</div>
                    <div class="value"><?= $showcase['delivered'] ?></div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control search-input" name="search" 
                                   placeholder="Search orders, customers..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select filter-select" name="status">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="out_for_delivery" <?= $statusFilter === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="orders-table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-box-seam" style="font-size: 2rem; color: #DED2C8;"></i>
                                        <div class="mt-2">No orders found</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($order['order_number'] ?? 'EC' . $order['order_id']) ?></strong>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <div class="customer-name">
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                </div>
                                                <div class="customer-email">
                                                    <?= htmlspecialchars($order['email']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <select name="status" class="status-badge <?= $order['status'] ?>" 
                                                        onchange="this.form.submit()" style="border: none; background: none; cursor: pointer;">
                                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="out_for_delivery" <?= $order['status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <strong><?= money($order['total']) ?></strong>
                                        </td>
                                        <td>
                                            <?= date('M j, Y, g:i A', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-view-details" 
                                                    onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                                <i class="bi bi-eye me-1"></i>View Details
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

    <!-- Order Details Modal -->
    <div class="modal fade <?= isset($_GET['order_id']) ? 'show' : '' ?>" id="orderDetailsModal" tabindex="-1" 
         <?= isset($_GET['order_id']) ? 'style="display: block;"' : '' ?>>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOrderNumber">
                        <?= isset($orderDetails) ? 'Order ' . htmlspecialchars($orderDetails['order_number'] ?? 'EC' . $orderDetails['order_id']) : 'Order Details' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            onclick="window.location.href='order_management.php'"></button>
                </div>
                <div class="modal-body" id="modalOrderContent">
                    <?php if (isset($orderDetails)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="order-status-badge <?= $orderDetails['status'] ?>">
                                <?= ucwords(str_replace('_', ' ', $orderDetails['status'])) ?>
                            </span>
                            <small class="text-muted">Ordered at: <?= date('M j, Y', strtotime($orderDetails['created_at'])) ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($orderDetails['first_name'] . ' ' . $orderDetails['last_name']) ?></p>
                            <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($orderDetails['email']) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Order Items</h6>
                            <?php foreach ($orderDetails['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="order-item-image">
                                        <?php if ($item['image_url'] && file_exists($item['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <?php else: ?>
                                            <i class="bi bi-image placeholder"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-item-details">
                                        <div class="order-item-brand"><?= htmlspecialchars($item['brand_name']) ?></div>
                                        <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="order-item-quantity">Quantity: <?= $item['quantity'] ?> × <?= money($item['price']) ?></div>
                                    </div>
                                    <div class="order-item-price"><?= money($item['price'] * $item['quantity']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span><?= money($orderDetails['total'] - $orderDetails['shipping_cost']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping Cost:</span>
                                <span><?= money($orderDetails['shipping_cost']) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span><?= money($orderDetails['total']) ?></span>
                            </div>
                        </div>
                        
                        <div class="address-section">
                            <div class="address-title">Shipping Address</div>
                            <div class="address-text">
                                <?= htmlspecialchars($orderDetails['ship_first_name'] . ' ' . $orderDetails['ship_last_name']) ?><br>
                                <?= htmlspecialchars($orderDetails['ship_street']) ?><br>
                                <?php if ($orderDetails['ship_line2']): ?>
                                    <?= htmlspecialchars($orderDetails['ship_line2']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($orderDetails['ship_city']) ?><br>
                                <?= htmlspecialchars($orderDetails['ship_country']) ?><br>
                                <?= htmlspecialchars($orderDetails['ship_phone']) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-box-seam" style="font-size: 2rem; color: #DED2C8;"></i>
                            <div class="mt-2">No order details available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($_GET['order_id'])): ?>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            // Load order details directly
            window.location.href = `?order_id=${orderId}`;
        }

        // Auto-submit search form on input
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        // Auto-submit filter form on change
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>
