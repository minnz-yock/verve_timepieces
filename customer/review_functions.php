<?php
// customer/review_functions.php

/**
 * Get product reviews for a single product.
 * - Validates LIMIT (no bound placeholder)
 * - Whitelists ORDER BY pieces (no bound placeholder)
 * - Always returns email, first_name, last_name
 */
function getProductReviews(PDO $conn, int $product_id, int $limit = 5, string $sort = 'latest'): array
{
    // Validate limit (1..50)
    $limit = max(1, min(50, (int)$limit));

    // Whitelist sort → ORDER BY snippet
    switch ($sort) {
        case 'oldest':
            $orderBy = 'r.created_at ASC';
            break;
        case 'rating_desc':
            $orderBy = 'r.star_rating DESC, r.created_at DESC';
            break;
        case 'rating_asc':
            $orderBy = 'r.star_rating ASC, r.created_at DESC';
            break;
        default: // latest
            $orderBy = 'r.created_at DESC';
    }

    $sql = "
        SELECT
            r.review_id, r.review_type, r.star_rating, r.review_title, r.review_text, r.created_at,
            u.email, u.first_name, u.last_name,
            p.product_name
        FROM reviews r
        LEFT JOIN users    u ON r.user_id = u.id
        LEFT JOIN products p ON r.product_id = p.product_id
        WHERE r.product_id = :pid
          AND r.review_type = 'Product Reviews'
          AND r.is_approved = 1
        ORDER BY {$orderBy}
        LIMIT {$limit}";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':pid', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get average rating and count for a product’s product-reviews.
 */
function getProductAverageRating(PDO $conn, int $product_id): array
{
    $stmt = $conn->prepare("
        SELECT AVG(star_rating) AS avg_rating, COUNT(*) AS review_count
        FROM reviews
        WHERE product_id = :pid
          AND review_type = 'Product Reviews'
          AND is_approved = 1
    ");
    $stmt->execute([':pid' => $product_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avg_rating' => null, 'review_count' => 0];

    // Normalize output types
    return [
        'avg_rating'   => $row['avg_rating'] !== null ? (float)$row['avg_rating'] : null,
        'review_count' => (int)$row['review_count'],
    ];
}

/**
 * Get reviews filtered by a review type (optionally for a product).
 * - Validates LIMIT
 * - Whitelists ORDER BY
 * - Always returns email, first_name, last_name, product_name
 */
function getReviewsByType(
    PDO $db,
    string $type,
    int $limit = 5,
    string $sort = 'latest',
    ?int $productId = null
): array {
    $limit = max(1, min(50, (int)$limit));

    // Only allow stored types (as in the DB ENUM)
    $allowedTypes = [
        'Product Reviews',
        'Customer Service Reviews',
        'Purchase/Delivery Experience Reviews',
        'Usability Reviews',
        'Overall Store Reviews',
    ];
    if (!in_array($type, $allowedTypes, true)) {
        return [];
    }

    switch ($sort) {
        case 'oldest':
            $orderBy = 'r.created_at ASC';
            break;
        case 'rating_desc':
            $orderBy = 'r.star_rating DESC, r.created_at DESC';
            break;
        case 'rating_asc':
            $orderBy = 'r.star_rating ASC, r.created_at DESC';
            break;
        default:
            $orderBy = 'r.created_at DESC';
    }

    $sql = "
        SELECT
            r.review_id, r.review_type, r.star_rating, r.review_title, r.review_text, r.created_at,
            u.email, u.first_name, u.last_name,
            p.product_name
        FROM reviews r
        JOIN users    u ON u.id = r.user_id
        LEFT JOIN products p ON p.product_id = r.product_id
        WHERE r.is_approved = 1
          AND r.review_type = :type" . ($productId ? " AND r.product_id = :pid" : "") . "
        ORDER BY {$orderBy}
        LIMIT {$limit}";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    if ($productId) {
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a mixed list of recent reviews (for home page blocks, etc.).
 * - No bound LIMIT
 * - Normalized fields returned
 */
function getMixedReviews(PDO $conn, int $limit = 6): array
{
    $limit = max(1, min(50, (int)$limit));

    $sql = "
        SELECT
            r.review_id, r.review_type, r.star_rating, r.review_title, r.review_text, r.created_at,
            u.email, u.first_name, u.last_name,
            p.product_name
        FROM reviews r
        LEFT JOIN users    u ON r.user_id = u.id
        LEFT JOIN products p ON r.product_id = p.product_id
        WHERE r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT {$limit}";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Render star rating (plain Bootstrap Icons markup).
 */
function renderStarRating(int $rating): string
{
    $rating = max(0, min(5, (int)$rating));
    $html = '<div class="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'bi-star-fill' : 'bi-star';
        $html .= '<i class="bi ' . $class . '"></i>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Render a single review card.
 * - Safe fallbacks for first_name/last_name/email/product_name
 * - Avoids “Undefined array key” warnings
 */
function renderReviewCard(array $review, bool $show_product = true): string
{
    $type        = $review['review_type']   ?? '';
    $title       = $review['review_title']  ?? '';
    $text        = $review['review_text']   ?? '';
    $rating      = isset($review['star_rating']) ? (int)$review['star_rating'] : 0;
    $createdRaw  = $review['created_at']    ?? '';
    $createdDate = $createdRaw ? date('Y-m-d', strtotime($createdRaw)) : '';

    $first = $review['first_name']  ?? '';
    $last  = $review['last_name']   ?? '';
    $email = $review['email']       ?? '';
    $name  = trim("$first $last");
    $displayName = $name !== '' ? $name : ($email !== '' ? $email : 'Anonymous');

    $productName = $review['product_name'] ?? '';

    $html  = '<div class="review-card">';
    $html .= '  <div class="review-header">';
    $html .= '    <div>';
    $html .= '      <span class="review-type-badge">' . htmlspecialchars($type) . '</span>';
    if ($show_product && $productName !== '') {
        $html .= '      <small class="text-muted ms-2">for ' . htmlspecialchars($productName) . '</small>';
    }
    $html .= '    </div>';
    $html .=      renderStarRating($rating);
    $html .= '  </div>';

    if ($title !== '') {
        $html .= '  <div class="review-title">' . htmlspecialchars($title) . '</div>';
    }

    $html .= '  <div class="review-text">' . nl2br(htmlspecialchars($text)) . '</div>';
    $html .= '  <div class="review-meta">';
    $html .= '    <div><strong>' . htmlspecialchars($displayName) . '</strong>';
    if ($email !== '') {
        $html .= '      <small class="text-muted">(' . htmlspecialchars($email) . ')</small>';
    }
    $html .= '    </div>';
    if ($createdDate !== '') {
        $html .= '    <div>' . $createdDate . '</div>';
    }
    $html .= '  </div>';
    $html .= '</div>';

    return $html;
}

/**
 * Render the “Write a review” form.
 * - If $review_type provided, it’s fixed via hidden input.
 * - Otherwise show a select of all types.
 */
function renderReviewForm(string $review_type = '', ?int $product_id = null, string $button_text = 'Send reviews to Verve Timepieces'): string
{
    $html = '<form method="POST" class="review-form">';

    if ($product_id) {
        $html .= '<input type="hidden" name="product_id" value="' . (int)$product_id . '">';
    }

    $html .= '<div class="row g-3">';

    if ($review_type === '') {
        $html .= '<div class="col-md-6">';
        $html .= '  <label class="form-label">Review Type</label>';
        $html .= '  <select name="review_type" class="form-select" required>';
        $html .= '    <option value="">Select review type</option>';
        $html .= '    <option value="Product Reviews">Product Reviews</option>';
        $html .= '    <option value="Customer Service Reviews">Customer Service Reviews</option>';
        $html .= '    <option value="Purchase/Delivery Experience Reviews">Purchase/Delivery Experience Reviews</option>';
        $html .= '    <option value="Usability Reviews">Usability Reviews</option>';
        $html .= '    <option value="Overall Store Reviews">Overall Store Reviews</option>';
        $html .= '  </select>';
        $html .= '</div>';
    } else {
        $html .= '<input type="hidden" name="review_type" value="' . htmlspecialchars($review_type) . '">';
    }

    $html .= '<div class="col-md-6">';
    $html .= '  <label class="form-label">Star Rating</label>';
    $html .= '  <select name="star_rating" class="form-select" required>';
    $html .= '    <option value="">Select rating</option>';
    $html .= '    <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>';
    $html .= '    <option value="4">⭐⭐⭐⭐ (4 stars)</option>';
    $html .= '    <option value="3">⭐⭐⭐ (3 stars)</option>';
    $html .= '    <option value="2">⭐⭐ (2 stars)</option>';
    $html .= '    <option value="1">⭐ (1 star)</option>';
    $html .= '  </select>';
    $html .= '</div>';

    $html .= '<div class="col-12">';
    $html .= '  <label class="form-label">Review Title (Optional)</label>';
    $html .= '  <input type="text" name="review_title" class="form-control" placeholder="Enter a title for your review">';
    $html .= '</div>';

    $html .= '<div class="col-12">';
    $html .= '  <label class="form-label">Review Text <span class="text-danger">*</span></label>';
    $html .= '  <textarea name="review_text" class="form-control" rows="3" placeholder="Share your experience with us..." required></textarea>';
    $html .= '</div>';

    $html .= '<div class="col-12">';
    $html .= '  <button type="submit" name="submit_review" class="btn btn-primary-custom">';
    $html .= '    <i class="bi bi-send me-2"></i>' . htmlspecialchars($button_text);
    $html .= '  </button>';
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</form>';

    return $html;
}
