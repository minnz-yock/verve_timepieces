<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../dbconnect.php';
require_once '../user_login_check.php';

if (empty($_SESSION['user_id'])) {
  header('Location: /login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

// Get user info for sidebar
$stmtUser = $conn->prepare('SELECT id, first_name, last_name, email FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Get order ID from URL parameter
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if (!$orderId) {
  // If no specific order, show all orders
  $orders = $conn->prepare('
    SELECT o.order_id, o.order_number, o.total, o.shipping_method, o.status, o.created_at,
           COUNT(oi.id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
  ');
  $orders->execute([$userId]);
  $allOrders = $orders->fetchAll(PDO::FETCH_ASSOC);
} else {
  // Get specific order details
  $orderStmt = $conn->prepare('
    SELECT o.*, 
           ba.first_name as bill_first_name, ba.last_name as bill_last_name, ba.country_region as bill_country,
           ba.street_address as bill_street, ba.address_line2 as bill_line2, ba.city_town as bill_city, ba.phone as bill_phone,
           sa.first_name as ship_first_name, sa.last_name as ship_last_name, sa.country_region as ship_country,
           sa.street_address as ship_street, sa.address_line2 as ship_line2, sa.city_town as ship_city, sa.phone as ship_phone,
           p.method as payment_method, p.details as payment_details
    FROM orders o
    LEFT JOIN bill_address ba ON o.billing_address_id = ba.id
    LEFT JOIN ship_address sa ON o.shipping_address_id = sa.id
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.order_id = ? AND o.user_id = ?
  ');
  $orderStmt->execute([$orderId, $userId]);
  $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$order) {
    header('Location: order_tracking.php');
    exit;
  }
  
  // Get order items - FIXED: use image_url instead of image_path
  $itemsStmt = $conn->prepare('
    SELECT oi.*, p.image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
  ');
  $itemsStmt->execute([$orderId]);
  $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

function money($n) {
  return '$' . number_format((float)$n, 2);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <title>Order Tracking</title>
  <style>
    body { color: #352826; }

    .account-wrapper {
      display: flex;
      gap: 28px;
      align-items: flex-start;
      padding: 24px;
      max-width: 1200px;
      margin: 0 auto
    }

    .account-menu {
      width: 250px
    }

    .user-head {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 12px
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #352826;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700
    }

    .user-name {
      font-weight: 800;
      letter-spacing: .3px
    }

    .user-email {
      color: #666;
      font-size: 14px
    }

    .account-menu nav {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 8px
    }

    .account-menu a {
      color: #785A49;
      text-decoration: none;
      padding: 6px 0
    }

    .account-menu a:hover { color: #352826 }

    .account-menu a.active {
      font-weight: 700;
      color: #352826
    }

    .account-menu a.logout {
      color: #352826
    }

    .account-content {
      flex: 1
    }

    .order-card {
      background: white;
      border: 1px solid #DED2C8;
      border-radius: 10px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .order-header {
      background: #785A49;
      color: white;
      padding: 16px 20px;
    }

    .order-number {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .order-date {
      opacity: 0.9;
      font-size: 0.9rem;
    }

    .order-content {
      padding: 20px;
    }

    .status-timeline {
      margin-bottom: 32px;
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #DED2C8;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .timeline-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #352826;
      margin-bottom: 24px;
      text-align: center;
      position: relative;
    }

    .timeline-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #785A49, #A57A5B);
      border-radius: 2px;
    }

    .timeline {
      position: relative;
      padding-left: 32px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 16px;
      top: 20px;
      bottom: 20px;
      width: 3px;
      background: linear-gradient(180deg, #26734D 0%, #785A49 50%, #DED2C8 100%);
      border-radius: 2px;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 28px;
      padding-left: 24px;
      background: white;
      border-radius: 8px;
      padding: 16px 16px 16px 40px;
      transition: all 0.3s ease;
    }

    .timeline-item.completed {
      background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
      border: 1px solid #26734D;
      box-shadow: 0 2px 8px rgba(38, 115, 77, 0.1);
    }

    .timeline-item.current {
      background: linear-gradient(135deg, #fff8f0 0%, #f5e8d8 100%);
      border: 2px solid #785A49;
      box-shadow: 0 4px 12px rgba(120, 90, 73, 0.2);
      transform: scale(1.02);
    }

    .timeline-item.pending {
      background: #f8f9fa;
      border: 1px solid #DED2C8;
    }

    .timeline-icon {
      position: absolute;
      left: -20px;
      top: 50%;
      transform: translateY(-50%);
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .timeline-icon.completed {
      background: linear-gradient(135deg, #26734D 0%, #2d8f5a 100%);
      animation: pulse-green 2s infinite;
    }

    .timeline-icon.current {
      background: linear-gradient(135deg, #785A49 0%, #A57A5B 100%);
      animation: pulse-brown 2s infinite;
    }

    .timeline-icon.pending {
      background: linear-gradient(135deg, #DED2C8 0%, #c4b5a0 100%);
      color: #785A49;
    }

    @keyframes pulse-green {
      0%, 100% { box-shadow: 0 2px 8px rgba(38, 115, 77, 0.3); }
      50% { box-shadow: 0 4px 16px rgba(38, 115, 77, 0.5); }
    }

    @keyframes pulse-brown {
      0%, 100% { box-shadow: 0 2px 8px rgba(120, 90, 73, 0.3); }
      50% { box-shadow: 0 4px 16px rgba(120, 90, 73, 0.5); }
    }

    .timeline-content h4 {
      font-weight: 700;
      color: #352826;
      margin-bottom: 6px;
      font-size: 1.1rem;
    }

    .timeline-content p {
      color: #785A49;
      margin: 0;
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .timeline-item.completed .timeline-content h4 {
      color: #26734D;
    }

    .timeline-item.current .timeline-content h4 {
      color: #785A49;
    }

    .section-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #352826;
      margin-bottom: 20px;
      text-align: center;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #785A49, #A57A5B);
      border-radius: 2px;
    }

    .item-card {
      display: flex;
      align-items: center;
      padding: 16px;
      border: 1px solid #DED2C8;
      border-radius: 12px;
      margin-bottom: 16px;
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .item-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-color: #A57A5B;
    }

    .item-image {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 8px;
      margin-right: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border: 2px solid #DED2C8;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .item-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .item-image .placeholder {
      color: #DED2C8;
      font-size: 2rem;
    }

    .order-items {
      margin-bottom: 32px;
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #DED2C8;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .item-details {
      flex: 1;
    }

    .item-brand {
      color: #785A49;
      font-size: 0.8rem;
      font-weight: 500;
      margin-bottom: 2px;
    }

    .item-name {
      font-weight: 600;
      color: #352826;
      margin-bottom: 4px;
      font-size: 0.9rem;
    }

    .item-quantity {
      color: #785A49;
      font-size: 0.8rem;
    }

    .item-price {
      font-weight: 600;
      color: #352826;
      font-size: 1rem;
    }

    .order-summary {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 20px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }

    .summary-row.total {
      border-top: 1px solid #DED2C8;
      padding-top: 8px;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .address-payment {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .address-card, .payment-card {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 16px;
    }

    .card-title {
      font-weight: 600;
      color: #352826;
      margin-bottom: 12px;
    }

    .address-text, .payment-text {
      color: #785A49;
      line-height: 1.5;
      font-size: 0.9rem;
    }

    .orders-list {
      display: grid;
      gap: 12px;
    }

    .order-summary-card {
      background: white;
      border: 1px solid #DED2C8;
      border-radius: 8px;
      padding: 16px;
      transition: all 0.3s ease;
    }

    .order-summary-card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .order-summary-card a {
      text-decoration: none;
      color: inherit;
    }

    .order-summary-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .order-summary-number {
      font-size: 1.2rem;
      font-weight: 600;
      color: #352826;
    }

    .order-summary-status {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: 500;
      text-transform: uppercase;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-processing { background: #d1ecf1; color: #0c5460; }
    .status-shipped { background: #cce5ff; color: #004085; }
    .status-out-for-delivery { background: #fff3cd; color: #856404; }
    .status-delivered { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .order-summary-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      color: #785A49;
      font-size: 0.8rem;
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #785A49;
    }

    .empty-state i {
      font-size: 3rem;
      color: #DED2C8;
      margin-bottom: 16px;
    }

    .btn {
      padding: 10px 16px;
      border: 1px solid #352826;
      border-radius: 8px;
      cursor: pointer;
      background: #352826;
      color: #ffffff;
      text-decoration: none;
      display: inline-block;
    }

    .btn:hover {
      background: #785A49;
      border-color: #785A49;
      color: white;
    }

    @media (max-width: 768px) {
      .account-wrapper {
        flex-direction: column;
      }
      
      .account-menu {
        width: 100%;
      }
      
      .address-payment {
        grid-template-columns: 1fr;
      }
      
      .order-summary-details {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php include 'navbarnew.php'; ?>
  <?php $section = 'order-tracking'; ?>

  <main class="account-wrapper">
    <?php include 'side_menu.php'; ?>

    <section class="account-content">
      <h2>ORDER TRACKING</h2>
      
      <?php if (!$orderId): ?>
        <!-- Orders List View -->
        <?php if (empty($allOrders)): ?>
          <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <h3 style="color: #352826; margin-bottom: 12px;">No Orders Found</h3>
            <p style="margin-bottom: 20px;">You haven't placed any orders yet.</p>
            <a href="view_products.php" class="btn">Start Shopping</a>
          </div>
        <?php else: ?>
          <div class="orders-list">
            <?php foreach ($allOrders as $orderItem): ?>
              <div class="order-summary-card">
                <a href="order_tracking.php?order_id=<?= $orderItem['order_id'] ?>">
                  <div class="order-summary-header">
                    <div class="order-summary-number">#<?= htmlspecialchars($orderItem['order_number'] ?? 'EC' . $orderItem['order_id']) ?></div>
                    <span class="order-summary-status status-<?= str_replace('_', '-', $orderItem['status']) ?>"><?= ucwords(str_replace('_', ' ', $orderItem['status'])) ?></span>
                  </div>
                  <div class="order-summary-details">
                    <div><strong>Order Date:</strong> <?= date('M j, Y', strtotime($orderItem['created_at'])) ?></div>
                    <div><strong>Total:</strong> <?= money($orderItem['total']) ?></div>
                    <div><strong>Items:</strong> <?= $orderItem['item_count'] ?> item(s)</div>
                    <div><strong>Shipping:</strong> <?= htmlspecialchars($orderItem['shipping_method']) ?></div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
      <?php else: ?>
        <!-- Single Order Detail View -->
        <div class="order-card">
          <div class="order-header">
            <div class="order-number">#<?= htmlspecialchars($order['order_number'] ?? 'EC' . $order['order_id']) ?></div>
            <div class="order-date">Ordered on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></div>
          </div>
          
          <div class="order-content">
            <!-- Order Status Timeline -->
            <div class="status-timeline">
              <h3 class="timeline-title">Order Status</h3>
              <div class="timeline">
                <div class="timeline-item completed">
                  <div class="timeline-icon completed">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="timeline-content">
                    <h4>Order Confirmed</h4>
                    <p>Your order has been received and confirmed</p>
                  </div>
                </div>
                
                <div class="timeline-item <?= in_array($order['status'], ['processing', 'shipped', 'out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'pending' ? 'current' : 'pending') ?>">
                  <div class="timeline-icon <?= in_array($order['status'], ['processing', 'shipped', 'out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'pending' ? 'current' : 'pending') ?>">
                    <i class="bi bi-gear"></i>
                  </div>
                  <div class="timeline-content">
                    <h4>Processing</h4>
                    <p>Your watch is being carefully prepared and quality checked</p>
                  </div>
                </div>
                
                <div class="timeline-item <?= in_array($order['status'], ['shipped', 'out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'processing' ? 'current' : 'pending') ?>">
                  <div class="timeline-icon <?= in_array($order['status'], ['shipped', 'out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'processing' ? 'current' : 'pending') ?>">
                    <i class="bi bi-truck"></i>
                  </div>
                  <div class="timeline-content">
                    <h4>Shipped</h4>
                    <p>Your order is on its way to the delivery address</p>
                  </div>
                </div>
                
                <div class="timeline-item <?= in_array($order['status'], ['out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'shipped' ? 'current' : 'pending') ?>">
                  <div class="timeline-icon <?= in_array($order['status'], ['out_for_delivery', 'delivered']) ? 'completed' : ($order['status'] === 'shipped' ? 'current' : 'pending') ?>">
                    <i class="bi bi-geo-alt"></i>
                  </div>
                  <div class="timeline-content">
                    <h4>Out for Delivery</h4>
                    <p>Your package is out for delivery and will arrive today</p>
                  </div>
                </div>
                
                <div class="timeline-item <?= $order['status'] === 'delivered' ? 'completed' : ($order['status'] === 'out_for_delivery' ? 'current' : 'pending') ?>">
                  <div class="timeline-icon <?= $order['status'] === 'delivered' ? 'completed' : ($order['status'] === 'out_for_delivery' ? 'current' : 'pending') ?>">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="timeline-content">
                    <h4>Delivered</h4>
                    <p>Your watch has been successfully delivered</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Order Items -->
            <div class="order-items">
              <h3 class="section-title">Order Items</h3>
              <?php foreach ($orderItems as $item): ?>
                <div class="item-card">
                  <div class="item-image">
                    <?php 
                    $imagePath = '';
                    if ($item['image_url']) {
                      // The image_url in database already includes the full path like "../images/product_images/filename.jpg"
                      $imagePath = $item['image_url'];
                    }
                    ?>
                    <?php if ($imagePath && file_exists($imagePath)): ?>
                      <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" loading="lazy">
                    <?php else: ?>
                      <i class="bi bi-image placeholder"></i>
                    <?php endif; ?>
                  </div>
                  <div class="item-details">
                    <div class="item-brand"><?= htmlspecialchars($item['brand_name']) ?></div>
                    <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="item-quantity">Quantity: <?= $item['quantity'] ?></div>
                  </div>
                  <div class="item-price"><?= money($item['price'] * $item['quantity']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
              <h3 class="section-title">Order Summary</h3>
              <div class="summary-row">
                <span>Subtotal:</span>
                <span><?= money($order['total'] - $order['shipping_cost']) ?></span>
              </div>
              <div class="summary-row">
                <span>Shipping Cost:</span>
                <span><?= money($order['shipping_cost']) ?></span>
              </div>
              <div class="summary-row total">
                <span>Total:</span>
                <span><?= money($order['total']) ?></span>
              </div>
            </div>
            
            <!-- Shipping Address & Payment Method -->
            <div class="address-payment">
              <div class="address-card">
                <h4 class="card-title">Shipping Address</h4>
                <div class="address-text">
                  <?= htmlspecialchars($order['ship_first_name'] . ' ' . $order['ship_last_name']) ?><br>
                  <?= htmlspecialchars($order['ship_street']) ?><br>
                  <?php if ($order['ship_line2']): ?>
                    <?= htmlspecialchars($order['ship_line2']) ?><br>
                  <?php endif; ?>
                  <?= htmlspecialchars($order['ship_city']) ?><br>
                  <?= htmlspecialchars($order['ship_country']) ?><br>
                  <?= htmlspecialchars($order['ship_phone']) ?>
                </div>
              </div>
              
              <div class="payment-card">
                <h4 class="card-title">Payment Method</h4>
                <div class="payment-text">
                  <strong><?= htmlspecialchars($order['payment_method'] ?? 'Unknown') ?></strong><br>
                  <?php if ($order['payment_details']): ?>
                    <?= htmlspecialchars($order['payment_details']) ?><br>
                  <?php endif; ?>
                  Amount: <?= money($order['total']) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Order Tracking Reviews Section -->
  <?php include 'review_handler.php'; ?>
  <?php include 'order_tracking_reviews_section.php'; ?>
</body>

</html>