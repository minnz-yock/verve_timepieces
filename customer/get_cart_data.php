<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";

header('Content-Type: application/json');

$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// If user is logged in, always use AND, not OR
if ($user_id) {
    $stmt = $conn->prepare("SELECT 
        c.product_id, 
        c.quantity, 
        p.product_name, 
        p.price, 
        p.image_url, 
        p.stock_quantity,
        b.brand_name
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    JOIN brands b ON p.brand_id = b.brand_id
    WHERE c.user_id = ? AND c.session_id = ?");
    $stmt->execute([$user_id, $session_id]);
} else {
    // For guests, only use session_id
    $stmt = $conn->prepare("SELECT 
        c.product_id, 
        c.quantity, 
        p.product_name, 
        p.price, 
        p.image_url, 
        p.stock_quantity,
        b.brand_name
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    JOIN brands b ON p.brand_id = b.brand_id
    WHERE c.session_id = ?");
    $stmt->execute([$session_id]);
}

$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

echo json_encode(['items' => $cart_items, 'total' => $total]);
?>