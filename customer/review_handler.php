<?php
// Review submission handler - include this in pages that have review forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['review_data'] = $_POST; // Store form data for after login
        $_SESSION['review_message'] = 'Please login to submit your review.';
        header('Location: signinform.php');
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $review_type = $_POST['review_type'];
    $star_rating = (int)$_POST['star_rating'];
    $review_title = trim($_POST['review_title']);
    $review_text = trim($_POST['review_text']);
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    
    // Validation
    if ($star_rating < 1 || $star_rating > 5) {
        $error_message = 'Please select a valid star rating.';
    } elseif (empty($review_text)) {
        $error_message = 'Review text is required.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, review_type, star_rating, review_title, review_text) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $review_type, $star_rating, $review_title, $review_text]);
            $success_message = 'Thanks! Your review was sent.';
            
            // Clear form data after successful submission
            unset($_POST);
        } catch (PDOException $e) {
            $error_message = 'Error submitting review. Please try again.';
        }
    }
}

// Handle login redirect with stored review data
if (isset($_SESSION['review_data']) && isset($_SESSION['user_id'])) {
    // User has logged in, restore form data
    $restored_data = $_SESSION['review_data'];
    unset($_SESSION['review_data']);
    
    // Auto-submit the review
    $user_id = $_SESSION['user_id'];
    $review_type = $restored_data['review_type'];
    $star_rating = (int)$restored_data['star_rating'];
    $review_title = trim($restored_data['review_title']);
    $review_text = trim($restored_data['review_text']);
    $product_id = !empty($restored_data['product_id']) ? (int)$restored_data['product_id'] : null;
    
    try {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, review_type, star_rating, review_title, review_text) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $review_type, $star_rating, $review_title, $review_text]);
        $success_message = 'Thanks! Your review was sent.';
    } catch (PDOException $e) {
        $error_message = 'Error submitting review. Please try again.';
    }
}
?>

