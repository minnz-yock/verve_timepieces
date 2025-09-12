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

$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
  header('Location: checkout.php');
  exit;
}

$o = $conn->prepare('SELECT order_id, order_number, total, shipping_method, created_at FROM orders WHERE order_id=? AND user_id=?');
$o->execute([(int)$orderId, (int)$_SESSION['user_id']]);
$order = $o->fetch(PDO::FETCH_ASSOC);
if (!$order) {
  header('Location: checkout.php');
  exit;
}

function money($n)
{
  return '$' . number_format((float)$n, 2);
}
// Derive delivery ETA from shipping_method label
$eta = '3–5 Business Days';
$m = (string)($order['shipping_method'] ?? '');
if (stripos($m, 'Express (1–2 days)') !== false) {
  $eta = '1–2 Business Days';
} elseif (stripos($m, 'Standard (3–7 days)') !== false) {
  $eta = '3–7 Business Days';
} elseif (stripos($m, 'Express Intl') !== false) {
  $eta = '3–7 Business Days (International)';
} elseif (stripos($m, 'Standard Intl') !== false || stripos($m, 'week') !== false) {
  $eta = '1–3 Weeks (International)';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <title>Order Confirmed</title>
  <style>
    body {
      color: #352826;
    }

    .wrap {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 12px
    }

    .card {
      border: 1px solid #DED2C8
    }

    .big {
      font-size: 1.4rem;
      font-weight: 800
    }

    .badge-soft {
      background: #E6F4EA;
      color: #26734D;
      border-radius: 999px;
      padding: 8px 14px;
      font-weight: 700
    }
  </style>
</head>

<body>
  <?php include 'navbarnew.php'; ?>
  <div class="wrap">
    <h2 class="text-center fw-bold">Checkout</h2>
    <p class="text-center text-muted" style="margin-top:-6px">Complete your order in just a few steps</p>
    <div class="card p-4 mt-3">
      <div class="d-flex justify-content-center mb-2">
        <span class="badge-soft"><i class="bi bi-check2-circle me-2"></i>Order Confirmed!</span>
      </div>
      <p class="text-center mb-4">Thank you for your purchase. Your order has been confirmed and will be shipped soon.</p>
      <div class="row g-3">
        <div class="col">
          <div class="p-3" style="background:#DED2C8; border-radius:8px;">
            <div class="small text-muted">Order Number:</div>
            <div class="big">#<?= htmlspecialchars($order['order_number'] ?? 'EC' . $order['order_id']) ?></div>
          </div>
        </div>
        <div class="col">
          <div class="p-3" style="background:#DED2C8; border-radius:8px;">
            <div class="small text-muted">Estimated Delivery:</div>
            <div class="big"><?= htmlspecialchars($eta) ?></div>
          </div>
        </div>
      </div>
      <div class="mt-4">
        <ul class="list-unstyled text-muted">
          <li class="mb-2"><i class="bi bi-box-seam me-2"></i>We'll prepare your order for shipping</li>
          <li class="mb-2"><i class="bi bi-envelope-check me-2"></i>You'll receive tracking information via email</li>
          <li class="mb-2"><i class="bi bi-house-door me-2"></i>Your order will arrive at your door</li>
        </ul>
      </div>
      <div class="mt-3 d-grid">
        <a class="btn btn-dark" href="view_products.php">Start New Order</a>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>