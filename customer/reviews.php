<?php
session_start();
require_once '../dbconnect.php';

// Handle review submission
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
        } catch (PDOException $e) {
            $error_message = 'Error submitting review. Please try again.';
        }
    }
}

// Get filter and sort parameters
$review_type_filter = $_GET['type'] ?? 'all';
$product_filter = $_GET['product'] ?? null;
$sort_by = $_GET['sort'] ?? 'latest';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["r.is_approved = 1"];
$params = [];

if ($review_type_filter !== 'all') {
    $where_conditions[] = "r.review_type = ?";
    $params[] = $review_type_filter;
}

if ($product_filter) {
    $where_conditions[] = "r.product_id = ?";
    $params[] = (int)$product_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'oldest':
        $order_clause .= "r.created_at ASC";
        break;
    case 'rating_high':
        $order_clause .= "r.star_rating DESC, r.created_at DESC";
        break;
    case 'rating_low':
        $order_clause .= "r.star_rating ASC, r.created_at DESC";
        break;
    default: // latest
        $order_clause .= "r.created_at DESC";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM reviews r $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_reviews = $count_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

// Get reviews
$reviews_query = "
    SELECT r.*, u.email, u.first_name, u.last_name, p.product_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN products p ON r.product_id = p.product_id
    $where_clause
    $order_clause
    LIMIT $per_page OFFSET $offset
";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->execute($params);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get review statistics
$stats_query = "
    SELECT 
        review_type,
        COUNT(*) as count,
        AVG(star_rating) as avg_rating
    FROM reviews 
    WHERE is_approved = 1 
    GROUP BY review_type
";
$stats_stmt = $conn->query($stats_query);
$review_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Verve Timepieces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #352826;
        }
        
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .review-type-badge {
            background: #785A49;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .star-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .review-title {
            font-weight: 600;
            color: #352826;
            margin-bottom: 0.5rem;
        }
        
        .review-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #785A49;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
        }
        
        .write-review-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #DED2C8;
        }
        
        .btn-primary-custom {
            background: #785A49;
            border-color: #785A49;
            color: white;
            font-weight: 600;
        }
        
        .btn-primary-custom:hover {
            background: #A57A5B;
            border-color: #A57A5B;
            color: white;
        }
        
        .form-control, .form-select {
            border: 1px solid #DED2C8;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #785A49;
            box-shadow: 0 0 0 0.2rem rgba(120, 90, 73, 0.25);
        }
        
        .stats-card {
            background: #785A49;
            color: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .pagination .page-link {
            color: #785A49;
            border-color: #DED2C8;
        }
        
        .pagination .page-link:hover {
            background-color: #785A49;
            border-color: #785A49;
            color: white;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #785A49;
            border-color: #785A49;
        }
    </style>
</head>
<body>
    <?php include 'navbarnew.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="bi bi-star-fill me-2"></i>Customer Reviews</h1>
                
                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Review Statistics -->
                <div class="row mb-4">
                    <?php foreach ($review_stats as $stat): ?>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stat['count'] ?></div>
                                <div class="small"><?= $stat['review_type'] ?></div>
                                <div class="small">Avg: <?= number_format($stat['avg_rating'], 1) ?> ⭐</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Filters and Sort -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filter by Type</label>
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $review_type_filter === 'all' ? 'selected' : '' ?>>All Reviews</option>
                                <option value="Product Reviews" <?= $review_type_filter === 'Product Reviews' ? 'selected' : '' ?>>Product Reviews</option>
                                <option value="Customer Service Reviews" <?= $review_type_filter === 'Customer Service Reviews' ? 'selected' : '' ?>>Customer Service Reviews</option>
                                <option value="Purchase/Delivery Experience Reviews" <?= $review_type_filter === 'Purchase/Delivery Experience Reviews' ? 'selected' : '' ?>>Purchase/Delivery Experience Reviews</option>
                                <option value="Usability Reviews" <?= $review_type_filter === 'Usability Reviews' ? 'selected' : '' ?>>Usability Reviews</option>
                                <option value="Overall Store Reviews" <?= $review_type_filter === 'Overall Store Reviews' ? 'selected' : '' ?>>Overall Store Reviews</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort by</label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="latest" <?= $sort_by === 'latest' ? 'selected' : '' ?>>Latest</option>
                                <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                                <option value="rating_high" <?= $sort_by === 'rating_high' ? 'selected' : '' ?>>Rating: High to Low</option>
                                <option value="rating_low" <?= $sort_by === 'rating_low' ? 'selected' : '' ?>>Rating: Low to High</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="reviews.php" class="btn btn-outline-secondary">Clear Filters</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Write Review Section -->
                <div class="write-review-section">
                    <h4><i class="bi bi-pencil-square me-2"></i>Write a Review</h4>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Review Type</label>
                                <select name="review_type" class="form-select" required>
                                    <option value="">Select review type</option>
                                    <option value="Product Reviews">Product Reviews</option>
                                    <option value="Customer Service Reviews">Customer Service Reviews</option>
                                    <option value="Purchase/Delivery Experience Reviews">Purchase/Delivery Experience Reviews</option>
                                    <option value="Usability Reviews">Usability Reviews</option>
                                    <option value="Overall Store Reviews">Overall Store Reviews</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Star Rating</label>
                                <select name="star_rating" class="form-select" required>
                                    <option value="">Select rating</option>
                                    <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                                    <option value="3">⭐⭐⭐ (3 stars)</option>
                                    <option value="2">⭐⭐ (2 stars)</option>
                                    <option value="1">⭐ (1 star)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Review Title (Optional)</label>
                                <input type="text" name="review_title" class="form-control" placeholder="Enter a title for your review">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Review Text <span class="text-danger">*</span></label>
                                <textarea name="review_text" class="form-control" rows="4" placeholder="Share your experience with us..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="submit_review" class="btn btn-primary-custom">
                                    <i class="bi bi-send me-2"></i>Send reviews to Verve Timepieces
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Reviews List -->
                <div class="reviews-section">
                    <h4 class="mb-3">Reviews (<?= $total_reviews ?> total)</h4>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="empty-state">
                            <i class="bi bi-star" style="font-size: 3rem; color: #DED2C8;"></i>
                            <h5 class="mt-3">No reviews yet</h5>
                            <p>Be the first to share your experience with Verve Timepieces!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <span class="review-type-badge"><?= htmlspecialchars($review['review_type']) ?></span>
                                        <?php if ($review['product_name']): ?>
                                            <small class="text-muted ms-2">for <?= htmlspecialchars($review['product_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="star-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['star_rating'] ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <?php if ($review['review_title']): ?>
                                    <div class="review-title"><?= htmlspecialchars($review['review_title']) ?></div>
                                <?php endif; ?>
                                
                                <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                                
                                <div class="review-meta">
                                    <div>
                                        <strong><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></strong>
                                        <small class="text-muted">(<?= htmlspecialchars($review['email']) ?>)</small>
                                    </div>
                                    <div>
                                        <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Reviews pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
