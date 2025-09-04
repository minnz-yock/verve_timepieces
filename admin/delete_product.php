<?php
require_once('../admin_login_check.php');
require_once('../dbconnect.php');

if (!isset($_SESSION)) session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    try {
        // Optionally: Fetch and remove image file from server here if needed

        $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
        $stmt->execute([$product_id]);
        $_SESSION['deleteSuccess'] = "Product deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['deleteError'] = "Failed to delete product: " . $e->getMessage();
    }
}

header("Location: see_all_products.php");
exit;