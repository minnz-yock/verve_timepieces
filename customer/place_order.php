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
// New address tables
$conn->exec("CREATE TABLE IF NOT EXISTS bill_address (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  country_region VARCHAR(100) NOT NULL,
  street_address VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255) NULL,
  city_town VARCHAR(100) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  UNIQUE KEY uniq_bill_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS ship_address (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  country_region VARCHAR(100) NOT NULL,
  street_address VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255) NULL,
  city_town VARCHAR(100) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  UNIQUE KEY uniq_ship_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Updated orders table with order_id, status, and FK references to address tables
$conn->exec("CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  billing_address_id INT NULL,
  shipping_address_id INT NULL,
  total DECIMAL(10,2) NOT NULL,
  shipping_method VARCHAR(100) NOT NULL,
  shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_orders_user (user_id),
  CONSTRAINT fk_orders_bill_addr FOREIGN KEY (billing_address_id) REFERENCES bill_address(id) ON DELETE SET NULL,
  CONSTRAINT fk_orders_ship_addr FOREIGN KEY (shipping_address_id) REFERENCES ship_address(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  brand_name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->exec("CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  method VARCHAR(100) NOT NULL,
  details VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'paid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
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
  // Upsert addresses to bill_address and ship_address, then create order referencing them
  $s = $state['shipping'];
  $b = $state['billing'];

  // Upsert shipping address
  $stmtShip = $conn->prepare('INSERT INTO ship_address (user_id, first_name, last_name, country_region, street_address, address_line2, city_town, phone)
                              VALUES (?,?,?,?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), country_region=VALUES(country_region), street_address=VALUES(street_address), address_line2=VALUES(address_line2), city_town=VALUES(city_town), phone=VALUES(phone)');
  $stmtShip->execute([$userId, $s['first_name'], $s['last_name'], $s['country_region'], $s['street_address'], ($s['address_line2']!==''?$s['address_line2']:null), $s['city_town'], $s['phone']]);
  // Fetch shipping address id
  $getShip = $conn->prepare('SELECT id FROM ship_address WHERE user_id = ?');
  $getShip->execute([$userId]);
  $shipId = (int)($getShip->fetchColumn() ?: 0);

  // Upsert billing address (if same_as_shipping, reuse shipping fields)
  if (!empty($b['same_as_shipping'])) {
    $bFirst = $s['first_name']; $bLast = $s['last_name']; $bCountry = $s['country_region']; $bStreet = $s['street_address']; $bLine2 = $s['address_line2']; $bCity = $s['city_town']; $bPhone = $s['phone'];
  } else {
    $bFirst = $b['first_name']; $bLast = $b['last_name']; $bCountry = $b['country_region']; $bStreet = $b['street_address']; $bLine2 = $b['address_line2']; $bCity = $b['city_town']; $bPhone = $b['phone'];
  }
  $stmtBill = $conn->prepare('INSERT INTO bill_address (user_id, first_name, last_name, country_region, street_address, address_line2, city_town, phone)
                              VALUES (?,?,?,?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), country_region=VALUES(country_region), street_address=VALUES(street_address), address_line2=VALUES(address_line2), city_town=VALUES(city_town), phone=VALUES(phone)');
  $stmtBill->execute([$userId, $bFirst, $bLast, $bCountry, $bStreet, ($bLine2!==''?$bLine2:null), $bCity, $bPhone]);
  $getBill = $conn->prepare('SELECT id FROM bill_address WHERE user_id = ?');
  $getBill->execute([$userId]);
  $billId = (int)($getBill->fetchColumn() ?: 0);

  // Insert order referencing address ids and set initial status to pending
  $o = $conn->prepare('INSERT INTO orders (user_id, billing_address_id, shipping_address_id, total, shipping_method, shipping_cost, status)
                       VALUES (?,?,?,?,?,?,?)');
  $o->execute([$userId, $billId, $shipId, $total, $s['method'], $shippingCost, 'pending']);
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


