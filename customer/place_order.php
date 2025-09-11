<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../dbconnect.php';
require_once '../user_login_check.php';
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

$userId = (int)$_SESSION['user_id'];
$sessionId = session_id();
$state = $_SESSION['checkout'] ?? null;
if (!$state || empty($state['shipping']['method'])) { header('Location: checkout.php'); exit; }

// Ensure tables exist (idempotent). You can move these to migrations if desired.
$conn->exec("CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  shipping_method VARCHAR(100) NOT NULL,
  shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping_first_name VARCHAR(100), shipping_last_name VARCHAR(100), shipping_country VARCHAR(100), shipping_street VARCHAR(255), shipping_line2 VARCHAR(255), shipping_city VARCHAR(100), shipping_phone VARCHAR(50),
  billing_first_name VARCHAR(100), billing_last_name VARCHAR(100), billing_country VARCHAR(100), billing_street VARCHAR(255), billing_line2 VARCHAR(255), billing_city VARCHAR(100), billing_phone VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  brand_name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  method VARCHAR(100) NOT NULL,
  details VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'paid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Load cart
$st = $conn->prepare('SELECT c.product_id, c.quantity, p.product_name, p.price, b.brand_name FROM cart c JOIN products p ON c.product_id=p.product_id JOIN brands b ON p.brand_id=b.brand_id WHERE (c.user_id = ? OR c.session_id = ?)');
$st->execute([$userId, $sessionId]);
$items = $st->fetchAll(PDO::FETCH_ASSOC);
if (!$items) { header('Location: card.php'); exit; }

$subtotal = 0;
foreach ($items as $it) { $subtotal += (float)$it['price'] * (int)$it['quantity']; }
$shippingCost = (float)$state['shipping']['cost'];
$total = $subtotal + $shippingCost;

$conn->beginTransaction();
try {
  // Insert order
  $o = $conn->prepare('INSERT INTO orders (user_id, total, shipping_method, shipping_cost, shipping_first_name, shipping_last_name, shipping_country, shipping_street, shipping_line2, shipping_city, shipping_phone, billing_first_name, billing_last_name, billing_country, billing_street, billing_line2, billing_city, billing_phone) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $s = $state['shipping'];
  $b = $state['billing'];
  $o->execute([
    $userId, $total, $s['method'], $shippingCost,
    $s['first_name'], $s['last_name'], $s['country_region'], $s['street_address'], $s['address_line2'], $s['city_town'], $s['phone'],
    $b['first_name'], $b['last_name'], $b['country_region'], $b['street_address'], $b['address_line2'], $b['city_town'], $b['phone']
  ]);
  $orderId = (int)$conn->lastInsertId();

  // Insert items
  $oi = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_name, brand_name, price, quantity) VALUES (?,?,?,?,?,?)');
  foreach ($items as $it) {
    $oi->execute([$orderId, (int)$it['product_id'], $it['product_name'], $it['brand_name'], (float)$it['price'], (int)$it['quantity']]);
  }

  // Insert payment (mark paid or pending based on method; here we mark paid for demo)
  $pm = $conn->prepare('INSERT INTO payments (order_id, method, details, amount, status) VALUES (?,?,?,?,?)');
  $pm->execute([$orderId, ($b['payment_method'] ?: 'Unknown'), ($b['payment_meta'] ?: null), $total, 'paid']);

  // Clear cart
  $clear = $conn->prepare('DELETE FROM cart WHERE (user_id = ? OR session_id = ?)');
  $clear->execute([$userId, $sessionId]);

  $conn->commit();
  $_SESSION['last_order_id'] = $orderId;
  header('Location: checkout_confirm.php');
  exit;
} catch (Throwable $e) {
  $conn->rollBack();
  header('Location: checkout.php?step=review&err=1');
  exit;
}
?>

