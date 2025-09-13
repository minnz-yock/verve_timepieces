<?php
session_start();

/* ---- SIMPLE ADMIN GUARD ---- */
if (!isset($_SESSION['first_name']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signinform.php");
    exit();
}
require_once "../dbconnect.php";

/* ---- FLASH HELPERS ---- */
function set_flash($type, $msg)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash()
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---- FORM HANDLERS ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_review' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'Review deleted successfully.');
        header("Location: review_management.php");
        exit();
    }
}

/* ---- FETCH REVIEWS ---- */
$review_types = [
    'All Reviews',
    'Product Reviews',
    'Customer Service Reviews',
    'Purchase/Delivery Experience Reviews',
    'Usability Reviews',
    'Over all Store Reviews'
];

$selected_type = $_GET['type'] ?? 'All Reviews';
$sort_by = $_GET['sort'] ?? 'latest';

// Base SQL query
$sql = "SELECT r.*, u.email, u.first_name, u.last_name, p.product_name 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN products p ON r.product_id = p.product_id
        WHERE 1=1";

$params = [];

// Filtering
if ($selected_type !== 'All Reviews') {
    $sql .= " AND r.review_type = ?";
    $params[] = $selected_type;
}

// Sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY r.created_at ASC";
        break;
    case 'rate_high_to_low':
        $sql .= " ORDER BY r.star_rating DESC";
        break;
    case 'rate_low_to_high':
        $sql .= " ORDER BY r.star_rating ASC";
        break;
    case 'latest':
    default:
        $sql .= " ORDER BY r.created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$flash = get_flash();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - Verve Timepieces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #DED2C8;
            color: #352826;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: 100%;
        }

        .main-content h1 {
            color: #352826;
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 2.2rem;
            letter-spacing: 0.5px;
        }

        .card {
            border-color: #A57A5B;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .filter-sort-bar {
            background-color: #fff;
            padding: 1rem;
            /* Decreased padding */
            border-radius: 10px;
            /* Slightly smaller radius */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #DED2C8;
            margin-bottom: 1.5rem;
            /* Decreased margin */
        }

        .filter-sort-bar label {
            font-weight: 600;
            color: #785A49;
        }

        .form-select,
        .form-control {
            border: 1px solid #DED2C8;
            border-radius: 6px;
            /* Slightly smaller radius */
            font-size: 0.9rem;
            /* Slightly smaller font */
        }

        .review-card {
            background-color: #fff;
            padding: 1rem;
            /* Decreased padding */
            margin-bottom: 1rem;
            /* Decreased margin */
            border-radius: 10px;
            /* Slightly smaller radius */
            border: 1px solid #EAE3DD;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .review-card:hover {
            transform: translateY(-3px);
            /* Smaller lift effect */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            /* Decreased margin */
            border-bottom: 1px dashed #DED2C8;
            padding-bottom: 0.8rem;
            /* Decreased padding */
        }

        .review-user-info {
            font-size: 1rem;
            /* Slightly smaller font */
        }

        .review-user-info small {
            display: block;
            color: #785A49;
            font-size: 0.85rem;
            /* Slightly smaller font */
        }

        .review-rating {
            font-size: 1.1rem;
            /* Slightly smaller font */
            color: #ffc107;
            letter-spacing: 1px;
            /* Slightly smaller spacing */
        }

        .review-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            /* Decreased gap */
        }

        .review-detail-item {
            font-size: 0.9rem;
            /* Slightly smaller font */
            color: #352826;
        }

        .review-detail-item strong {
            font-weight: 600;
            color: #785A49;
            display: block;
            margin-bottom: 0.3rem;
            /* Decreased margin */
        }

        .review-text-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.5;
            /* Slightly smaller line height */
            font-size: 0.9rem;
            /* Slightly smaller font */
        }

        .delete-btn-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            /* Decreased margin */
        }

        .btn-danger {
            background-color: #C24646;
            border-color: #C24646;
            font-size: 0.9rem;
        }

        /* Smaller font */
        .btn-danger:hover {
            background-color: #A03A3A;
            border-color: #A03A3A;
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <h1>Reviews Management</h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4 filter-sort-bar">
            <form method="GET" action="review_management.php" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="reviewTypeSelect" class="form-label">Filter by Review Type</label>
                    <select class="form-select" id="reviewTypeSelect" name="type" onchange="this.form.submit()">
                        <?php foreach ($review_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $selected_type === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="sortSelect" class="form-label">Sort By</label>
                    <select class="form-select" id="sortSelect" name="sort" onchange="this.form.submit()">
                        <option value="latest" <?= $sort_by === 'latest' ? 'selected' : '' ?>>Latest</option>
                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="rate_high_to_low" <?= $sort_by === 'rate_high_to_low' ? 'selected' : '' ?>>Rate: High to Low</option>
                        <option value="rate_low_to_high" <?= $sort_by === 'rate_low_to_high' ? 'selected' : '' ?>>Rate: Low to High</option>
                    </select>
                </div>
            </form>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                <p class="mt-2">No reviews found.</p>
            </div>
        <?php else: ?>
            <div class="reviews-container">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-user-info">
                                <strong><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></strong>
                                <small><?= htmlspecialchars($review['email']) ?></small>
                            </div>
                            <div class="review-rating">
                                <?= str_repeat('⭐', (int)$review['star_rating']) ?>
                            </div>
                        </div>

                        <div class="row review-details">
                            <div class="col-md-3 review-detail-item">
                                <strong>Review Type</strong>
                                <?= htmlspecialchars($review['review_type']) ?>
                            </div>
                            <?php if ($review['product_name']): ?>
                                <div class="col-md-3 review-detail-item">
                                    <strong>Product</strong>
                                    <?= htmlspecialchars($review['product_name']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3 review-detail-item">
                                <strong>Reviewed Date</strong>
                                <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                            </div>
                            <div class="col-md-3 review-detail-item">
                                <strong>Title</strong>
                                <?= htmlspecialchars($review['review_title']) ?>
                            </div>
                        </div>

                        <div class="review-text-content mt-3">
                            <p class="mb-0"><small><?= htmlspecialchars($review['review_text']) ?></small></p>
                        </div>

                        <div class="delete-btn-container">
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteReviewModal" data-bs-review-id="<?= htmlspecialchars($review['review_id']) ?>" data-bs-review-text="<?= htmlspecialchars($review['review_text']) ?>">
                                <i class="bi bi-trash"></i> Delete Review
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="deleteReviewModal" tabindex="-1" aria-labelledby="deleteReviewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteReviewModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this review?</p>
                        <p class="text-muted small"><strong>Review text:</strong> "<span id="reviewToDeleteText"></span>"</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="review_management.php">
                            <input type="hidden" name="action" value="delete_review">
                            <input type="hidden" name="id" id="reviewToDeleteId">
                            <button type="submit" class="btn btn-danger">Delete Review</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- Modal handling for delete confirmation ---
        document.getElementById('deleteReviewModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reviewId = button.getAttribute('data-bs-review-id');
            const reviewText = button.getAttribute('data-bs-review-text');
            const modal = this;
            modal.querySelector('#reviewToDeleteText').textContent = reviewText;
            modal.querySelector('#reviewToDeleteId').value = reviewId;
        });

        // --- Script to highlight active menu item ---
        document.addEventListener('DOMContentLoaded', (event) => {
            const currentFile = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href').includes(currentFile)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>