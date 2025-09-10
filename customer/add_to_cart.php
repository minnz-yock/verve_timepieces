<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";

header('Content-Type: application/json');

$response = ['ok' => false, 'message' => '', 'cart_count' => 0];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if ($product_id <= 0) {
    $response['message'] = "Invalid product ID.";
    echo json_encode($response);
    exit;
}

// Get the product's brand ID
$stmt = $conn->prepare("SELECT brand_id FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product_brand_id = $stmt->fetchColumn();
if (!$product_brand_id) {
    $response['message'] = "Product not found.";
    echo json_encode($response);
    exit;
}

$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Check for existing items in the cart
$cart_stmt = $conn->prepare("SELECT p.brand_id FROM cart c JOIN products p ON c.product_id = p.product_id WHERE (c.user_id = ? OR c.session_id = ?) LIMIT 1");
$cart_stmt->execute([$user_id, $session_id]);
$current_cart_brand_id = $cart_stmt->fetchColumn();

// Enforce one brand per order rule
if ($current_cart_brand_id && $current_cart_brand_id != $product_brand_id) {
    $response['message'] = "You can only purchase from one brand per order. If you wish to purchase from two or more separate brands then these will need to be done as separate orders.";
    echo json_encode($response);
    exit;
}

// Check if product is already in cart
$check_stmt = $conn->prepare("SELECT quantity FROM cart WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
$check_stmt->execute([$product_id, $user_id, $session_id]);
$current_quantity = $check_stmt->fetchColumn();

if ($current_quantity) {
    // Product already in cart, check stock limit
    $stock_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stock_stmt->execute([$product_id]);
    $stock = $stock_stmt->fetchColumn();

    if ($current_quantity >= $stock) {
        $response['message'] = "Cannot add more items. Stock limit reached.";
        echo json_encode($response);
        exit;
    }

    $update_stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
    $update_stmt->execute([$product_id, $user_id, $session_id]);
} else {
    // Add new product to cart
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, 1)");
    $insert_stmt->execute([$user_id, $session_id, $product_id]);
}

// Recalculate cart count
$count_stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? OR session_id = ?");
$count_stmt->execute([$user_id, $session_id]);
$cart_count = $count_stmt->fetchColumn();

$response['ok'] = true;
$response['cart_count'] = $cart_count;
$response['message'] = "Product added to cart.";

echo json_encode($response);
?>