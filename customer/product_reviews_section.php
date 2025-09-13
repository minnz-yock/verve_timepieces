<?php
// Product Reviews Section for Product Details Page
require_once 'review_functions.php';

// Get product_id from the parent page's $product array
$product_id = isset($product['product_id']) ? $product['product_id'] : null;
$reviews = getProductReviews($conn, $product_id, 3);
$rating_data = getProductAverageRating($conn, $product_id);
$avg_rating = $rating_data['avg_rating'] ?? 0;
$review_count = $rating_data['review_count'] ?? 0;
?>

<style>
.reviews-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #DED2C8;
}

.review-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #DED2C8;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.star-rating {
    color: #ffc107;
    font-size: 1rem;
}

.review-title {
    font-weight: 600;
    color: #352826;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.review-text {
    color: #666;
    line-height: 1.5;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.review-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #785A49;
}

.review-type-badge {
    background: #785A49;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
}

.btn-primary-custom {
    background: #785A49;
    border-color: #785A49;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.btn-primary-custom:hover {
    background: #A57A5B;
    border-color: #A57A5B;
    color: white;
}

.form-control, .form-select {
    border: 1px solid #DED2C8;
    border-radius: 6px;
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    border-color: #785A49;
    box-shadow: 0 0 0 0.2rem rgba(120, 90, 73, 0.25);
}

.average-rating {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.average-rating .stars {
    font-size: 2rem;
    color: #ffc107;
    margin-bottom: 0.5rem;
}

.average-rating .rating-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: #352826;
}

.reviews-carousel {
    position: relative;
}

.carousel-control-prev, .carousel-control-next {
    width: 40px;
    height: 40px;
    background: #785A49;
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
}

.carousel-control-prev {
    left: -20px;
}

.carousel-control-next {
    right: -20px;
}

.carousel-control-prev-icon, .carousel-control-next-icon {
    width: 20px;
    height: 20px;
}
</style>

<div class="reviews-section">
    <h4 class="mb-4"><i class="bi bi-star-fill me-2"></i>Product Reviews</h4>
    
    <!-- Average Rating -->
    <?php if ($review_count > 0): ?>
        <div class="average-rating">
            <div class="stars">
                <?= renderStarRating(round($avg_rating)) ?>
            </div>
            <div class="rating-text">
                <?= number_format($avg_rating, 1) ?> out of 5 stars
            </div>
            <div class="text-muted">Based on <?= $review_count ?> review<?= $review_count > 1 ? 's' : '' ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Reviews Carousel -->
    <?php if (!empty($reviews)): ?>
        <div id="reviewsCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach (array_chunk($reviews, 2) as $index => $review_chunk): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="row">
                            <?php foreach ($review_chunk as $review): ?>
                                <div class="col-md-6">
                                    <?= renderReviewCard($review, false) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($reviews) > 2): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-3">
            <i class="bi bi-star" style="font-size: 2rem;"></i>
            <p class="mt-2">No reviews yet for this product</p>
        </div>
    <?php endif; ?>
    
    <!-- Write Review Form -->
    <div class="mt-4">
        <h5>Write a Review</h5>
        <?= renderReviewForm('Product Reviews', $product_id) ?>
    </div>
    
    <!-- Read More Reviews Link -->
    <div class="text-center mt-3">
        <a href="reviews.php?type=Product Reviews<?= $product_id ? '&product=' . $product_id : '' ?>" class="btn btn-outline-primary">
            Read more reviews <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<script>
// Handle review form submission
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.querySelector('.review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                e.preventDefault();
                // Store form data and redirect to login
                const formData = new FormData(this);
                sessionStorage.setItem('review_data', JSON.stringify(Object.fromEntries(formData)));
                window.location.href = 'signinform.php';
            <?php endif; ?>
        });
    }
});
</script>
