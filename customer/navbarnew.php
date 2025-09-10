<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../dbconnect.php";
require_once "favorites_util.php";
$favCount = fav_count($conn);

// Count cart items for the badge
$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
// $cart_count_stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ? OR session_id = ?");
$cart_count_stmt = $conn->prepare(
    "SELECT COALESCE(SUM(quantity),0) FROM cart WHERE (user_id = ? OR session_id = ?)"
);
$cart_count_stmt->execute([$user_id, $session_id]);
$cartCount = $cart_count_stmt->fetchColumn();

?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="../images/logo_image/logo1.png" alt="Verve Timepieces Logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="homepage.php">Home</a></li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">Buy Watch</a>
                    <ul class="dropdown-menu multi-column-dropdown" aria-labelledby="navbarDropdown">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Brands</h6>
                                <ul class="list-unstyled">
                                    <li><a class="dropdown-item" href="brand-a.php">Brand Name A</a></li>
                                    <li><a class="dropdown-item" href="brand-b.php">Brand Name B</a></li>
                                    <li><a class="dropdown-item" href="brand-c.php">Brand Name C</a></li>
                                    <li><a class="dropdown-item" href="all-brands.php">View All Brands</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Collections</h6>
                                <ul class="list-unstyled">
                                    <li><a class="dropdown-item" href="dress-watches.php">Dress Watches</a></li>
                                    <li><a class="dropdown-item" href="sport-watches.php">Sport Watches</a></li>
                                    <li><a class="dropdown-item" href="all-collections.php">View All Collections</a></li>
                                </ul>
                            </div>
                        </div>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
            </ul>

            <form class="d-flex me-auto ms-3 search-form" role="search" method="GET" action="search.php">
                <div class="input-group">
                    <input class="form-control search-input" type="search" name="q" placeholder="Search Watches..." aria-label="Search">
                    <button class="btn btn-search" type="submit" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text">
                                    <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong><br>
                                    <small><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                                </span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="accdetails.php">Account Details</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="signinform.php">Sign In</a></li>
                            <li><a class="dropdown-item" href="signupform.php">Create Account</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="nav-item position-relative">
                    <button class="nav-link" data-bs-toggle="modal" data-bs-target="#shoppingBagModal" aria-label="Shopping Bag">
                        <i class="bi bi-bag" style="font-size: 1.5rem;"></i>
                        <span id="bagCountBadge" class="bag-badge badge rounded-pill bg-dark <?= $cartCount ? '' : 'd-none' ?>">
                            <?= $cartCount ?: '' ?>
                        </span>
                    </button>
                </li>

                <li class="nav-item position-relative">
                    <a class="nav-link fav-link" href="my_favorites.php" title="Favorites">
                        <i class="fa-regular fa-heart"></i>
                        <span id="favCountBadge"
                            class="fav-badge badge rounded-pill bg-dark <?= $favCount ? '' : 'd-none' ?>">
                            <?= $favCount ?: '' ?>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Give the whole bar some side padding so the right-most icon isn’t on the edge */
    .navbar {
        background: #FFF;
        border-bottom: 1px solid #DED2C8;
        padding: 1rem 1rem;
        height: 100px;
        overflow: visible;
    }

    .navbar .container-fluid {
        padding-left: .5rem;
        padding-right: 1rem;
    }

    /* extra right breathing room */

    .navbar-brand img {
        height: 110px;
        margin-top: -50px;
        margin-bottom: -50px;
    }

    .nav-link {
        color: #785A49 !important;
        font-weight: 500;
        padding: .5rem 1rem;
        transition: color .2s;
    }

    .nav-link:hover,
    .nav-link.active {
        color: #352826 !important;
    }

    .dropdown-item {
        color: #A57A5B;
        font-size: .95rem;
        transition: background-color .2s, color .2s;
    }

    .dropdown-item:hover {
        background-color: #DED2C8;
        color: #352826;
    }

    .dropdown-menu.multi-column-dropdown {
        width: 450px;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .10);
        border: 1px solid #DED2C8;
        background: #FFF;
    }

    .dropdown-menu.multi-column-dropdown .row {
        margin-left: 0;
        margin-right: 0;
    }

    .dropdown-menu.multi-column-dropdown .col-md-6 {
        padding: 0 10px;
    }

    .dropdown-menu.multi-column-dropdown h6 {
        font-size: .85rem;
        color: #785A49;
        text-transform: uppercase;
        letter-spacing: .8px;
        margin-bottom: 15px;
        border-bottom: 1px solid #DED2C8;
        padding-bottom: 5px;
    }

    /* Search */
    .search-form {
        max-width: 320px;
    }

    .search-input {
        border: 1.5px solid #A57A5B;
        background: #FFF;
        color: #352826;
        border-radius: 6px 0 0 6px;
        font-size: .95rem;
        padding: .6rem .9rem;
    }

    .search-input::placeholder {
        color: #A57A5B;
        font-weight: 500;
    }

    .btn-search {
        background: #352826;
        border: 1.5px solid #352826;
        color: #DED2C8;
        border-radius: 0 6px 6px 0;
        padding: .6rem .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-search:hover {
        background: #785A49;
        border-color: #785A49;
        color: #fff;
    }

    .navbar-nav .nav-link .bi {
        color: #352826;
    }

    .navbar-nav .nav-link:hover .bi {
        color: #A57A5B;
    }

    .navbar-nav .nav-item+.nav-item {
        margin-left: 15px;
    }

    /* Keep the heart fully visible; place badge INSIDE the icon area */
    .fav-link {
        position: relative;
        padding-right: 1.25rem;
    }

    /* a bit more right padding */
    .fav-badge {
        position: absolute;
        top: 2px;
        right: 6px;
        /* inside the link, not hanging outside */
        transform: translate(40%, -40%);
        /* subtle offset up/right */
        font-size: .65rem;
        line-height: 1;
        padding: .25em .4em;
    }

    .bag-badge {
        position: absolute;
        top: 2px;
        right: 6px;
        transform: translate(40%, -40%);
        font-size: .65rem;
        line-height: 1;
        padding: .25em .4em;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 12px;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: 1px solid #ddd;
    }

    .modal-footer {
        border-top: 1px solid #ddd;
    }

    .bag-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .bag-item:last-child {
        border-bottom: none;
    }

    .bag-item-img {
        width: 60px;
        height: 60px;
        border: 1px solid #eee;
        border-radius: 4px;
        overflow: hidden;
        display: grid;
        place-items: center;
    }

    .bag-item-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .bag-item-details {
        flex-grow: 1;
    }

    .bag-item-details .brand-name {
        font-weight: 700;
        text-transform: uppercase;
        font-size: .9rem;
    }

    .bag-item-details .product-name {
        font-weight: 500;
        font-size: .85rem;
        color: #555;
    }

    .bag-item-details .qty-price {
        font-size: .85rem;
        color: #555;
        margin-top: 4px;
    }

    .modal-footer .total-price {
        font-size: 1.1rem;
    }

    .btn-checkout {
        background: #222;
        color: #fff;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 24px;
        text-transform: uppercase;
    }

    .modal-body-empty {
        text-align: center;
        padding: 30px;
        color: #888;
    }

    @media (max-width: 991px) {
        .dropdown-menu.multi-column-dropdown {
            width: 100%;
            padding: 10px;
        }

        .dropdown-menu.multi-column-dropdown .col-md-6 {
            padding: 0 5px;
        }

        .dropdown-menu.multi-column-dropdown h6 {
            margin-bottom: 10px;
        }

        .navbar-nav .nav-item+.nav-item {
            margin-left: 0;
        }

        .fav-link {
            padding-right: 1rem;
        }
    }
</style>

<div class="modal fade" id="shoppingBagModal" tabindex="-1" aria-labelledby="shoppingBagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="shoppingBagModalLabel">Shopping Bag</h5>
                <!-- <a href="cart.php" class="btn btn-link">View Bag</a> -->
                <div class="modal-footer">
                    <a href="card.php" id="checkoutBtn" class="btn btn-dark w-100">
                        <i class="bi bi-bag me-2"></i> View Bag
                    </a>
                </div>
            </div>
            <div class="modal-body p-4">
                <div id="bag-modal-body">
                    <div class="text-center text-muted">Your Shopping Bag is empty.</div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <div class="total-price">
                    <strong>Total:</strong>
                    <span id="bag-total-price">$0.00</span>
                </div>
                <a href="cart.php" class="btn btn-checkout">
                    <i class="bi bi-bag-fill me-2"></i> Checkout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function updateBagBadge(n) {
        const b = document.getElementById('bagCountBadge');
        if (!b) return;
        if (n > 0) {
            b.textContent = n;
            b.classList.remove('d-none');
        } else {
            b.textContent = '';
            b.classList.add('d-none');
        }
    }

    function formatMoney(amount) {
        return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    async function fetchBagData() {
        
            try {
                const res = await fetch('get_card_data.php'); // match actual filename
                if (!res.ok) throw new Error('Failed to load cart');
                const data = await res.json();

                // TODO: render the full cart table/list here
                // e.g., document.getElementById('cart-container').innerHTML = renderCartHTML(data);

            } catch (e) {
                document.getElementById('cart-container').innerHTML =
                    '<div class="text-danger">Could not load your cart.</div>';
                console.error(e);
            }


        document.addEventListener('DOMContentLoaded', fetchBagData);

        const response = await fetch('get_card_data.php');
        const data = await response.json();
        const modalBody = document.getElementById('bag-modal-body');
        const totalFooter = document.getElementById('bag-total-price');
        const checkoutBtn = document.querySelector('.btn-checkout');

        if (data.items.length === 0) {
            modalBody.innerHTML = '<div class="modal-body-empty">Your Shopping Bag is empty.</div>';
            totalFooter.textContent = '$0.00';
            checkoutBtn.classList.add('d-none');
        } else {
            let itemsHtml = '';
            data.items.forEach(item => {
                itemsHtml += `
                    <div class="bag-item">
                        <div class="bag-item-img">
                            <img src="${item.image_url || '../images/placeholder_watch.png'}" alt="${item.product_name}">
                        </div>
                        <div class="bag-item-details">
                            <div class="brand-name">${item.brand_name}</div>
                            <div class="product-name">${item.product_name}</div>
                            <div class="qty-price">
                                Qty: ${item.quantity} | ${formatMoney(item.price)}
                            </div>
                        </div>
                    </div>
                `;
            });
            modalBody.innerHTML = itemsHtml;
            totalFooter.textContent = formatMoney(data.total);
            checkoutBtn.classList.remove('d-none');
        }
        updateBagBadge(data.items.length);
    }

    // Call fetchBagData when the modal is about to be shown
    document.getElementById('shoppingBagModal').addEventListener('show.bs.modal', function() {
        fetchBagData();
    });
</script>