<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";

header('Content-Type: application/json');

$response = ['ok' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
    $stmt->execute([$product_id, $user_id, $session_id]);
    $response['ok'] = true;
    $response['message'] = "Product removed from cart.";
    echo json_encode($response);
    exit;
}

if ($action === 'update' && $quantity > 0) {
    // Check stock quantity
    $stock_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stock_stmt->execute([$product_id]);
    $stock = $stock_stmt->fetchColumn();

    if ($quantity > $stock) {
        $response['message'] = "Cannot add more than " . $stock . " items. Stock limit reached.";
        echo json_encode($response);
        exit;
    }

    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE product_id = ? AND (user_id = ? OR session_id = ?)");
    $update_stmt->execute([$quantity, $product_id, $user_id, $session_id]);
    $response['ok'] = true;
    $response['message'] = "Cart updated successfully.";
    echo json_encode($response);
    exit;
}

$response['message'] = "Invalid action or quantity.";
echo json_encode($response);

?>