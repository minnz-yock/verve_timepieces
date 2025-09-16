<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// require_once "../dbconnect.php";
// require_once "favorites_util.php";

$root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
require_once $root . '/dbconnect.php';
require_once $root . '/customer/favorites_util.php';
$favCount = fav_count($conn);


$brand_rows = [];
$category_rows = [];


try {
    // Match your schema used in view_products.php:
    // brands(brand_id, brand_name), categories(category_id, cat_name)
    $brand_stmt = $conn->query("
        SELECT brand_id AS id, brand_name AS name
        FROM brands
        ORDER BY brand_name
    ");
    $brand_rows = $brand_stmt ? $brand_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $cat_stmt = $conn->query("
        SELECT category_id AS id, cat_name AS name
        FROM categories
        ORDER BY cat_name
    ");
    $category_rows = $cat_stmt ? $cat_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    // Optional: error_log("Nav fetch error: " . $e->getMessage());
    $brand_rows = [];
    $category_rows = [];
}

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
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownBuy" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Buy Watch
                    </a>
                    <ul class="dropdown-menu multi-column-dropdown p-3" aria-labelledby="navbarDropdownBuy" style="min-width: 480px;">
                        <div class="row gx-4">
                            <!-- Brands -->
                            <div class="col-12 col-md-6">
                                <h6 class="dropdown-header">Brands</h6>
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($brand_rows)): ?>
                                        <?php foreach ($brand_rows as $b): ?>
                                            <a
                                                class="dropdown-item"
                                                href="/customer/view_products.php?<?php echo http_build_query(['brand' => [(int)$b['id']]]); ?>">
                                                <?php echo htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="dropdown-item text-muted">No brands</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Categories -->
                            <div class="col-12 col-md-6">
                                <h6 class="dropdown-header">Categories</h6>
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($category_rows)): ?>
                                        <?php foreach ($category_rows as $c): ?>
                                            <a
                                                class="dropdown-item"
                                                href="/customer/view_products.php?<?php echo http_build_query(['category' => [(int)$c['id']]]); ?>">
                                                <?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="dropdown-item text-muted">No categories</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link" href="discounts.php">Discounts</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
            </ul>

            <form class="d-flex ms-3" method="get" action="/customer/view_products.php">
                <input
                    class="form-control"
                    type="search"
                    name="search"
                    placeholder="Search watches or brands..."
                    aria-label="Search"
                    required>
                <button class="btn btn-outline-light" type="submit"> <i class="bi bi-search"></i></button>
            </form>


            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                            <?php echo htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text">
                                    <strong><?php echo htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?></strong><br>
                                    <small><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                                </span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/customer/account_details.php">Account Details</a></li>
                            <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
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
    /* color palette */
    /* Give the whole bar some side padding so the right-most icon isn’t on the edge */
    .navbar {
        background: #fff;
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
        background: #fff;
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


    /* Search form container */
    .navbar .search-form,
    .navbar form.d-flex {
        max-width: 280px;
        /* not too wide, not too narrow */
        margin-left: 0.75rem;
    }

    /* Search input */
    .navbar .form-control[type="search"] {
        border: 1.5px solid #A57A5B;
        background: #fff;
        color: #352826;
        border-radius: 6px 0 0 6px;
        font-size: 0.9rem;
        padding: .45rem .7rem;
        height: 39px;
        box-shadow: none;
        margin-right: 0 !important;
    }

    .navbar .form-control[type="search"]::placeholder {
        color: #A57A5B;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .navbar .form-control[type="search"]:focus {
        width: 300px;
        transition: width .3s ease;
    }

    /* Search button */
    .navbar .btn[type="submit"] {
        background: #352826;
        border: 1.5px solid #352826;
        color: #DED2C8;
        border-radius: 0 6px 6px 0;
        padding: .45rem .7rem;
        height: 39px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all .2s;
    }

    .navbar .btn[type="submit"]:hover {
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
        max-width: 70%;
        max-height: 70%;
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
        background: #352826;
        color: #fff;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 24px;
        text-transform: uppercase;
    }

    .btn-checkout:hover {
        background: #785A49
    }

    .modal-body-empty {
        text-align: center;
        padding: 30px;
        color: #888;
    }

    @media (max-width: 991px) {

        /* Drawer container */
        .navbar {
            height: auto;
            padding: .75rem 1rem;
        }

        .navbar .navbar-toggler {
            z-index: 1060;
            border: 1px solid #DED2C8;
        }

        .navbar .navbar-collapse {
            position: fixed;
            top: 0;
            right: 0;
            /* slide in from right */
            bottom: 0;
            width: 88%;
            max-width: 380px;
            background: #fff;
            /* solid panel, not transparent */
            border-left: 1px solid #DED2C8;
            box-shadow: -16px 0 30px rgba(0, 0, 0, .15);
            padding: 84px 18px 24px;
            /* comfy padding */
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform .28s ease;
            z-index: 1050;
        }

        .navbar .navbar-collapse.show {
            transform: translateX(0);
        }

        /* Section spacing + dividers to improve scanability */
        .navbar .navbar-nav {
            gap: .25rem;
        }

        .navbar .navbar-nav .nav-link {
            display: block;
            padding: .9rem .6rem;
            border-radius: 10px;
            font-size: 1.05rem;
            color: #352826 !important;
        }

        .navbar .navbar-nav .nav-link:hover {
            background: #F5EEE9;
            /* subtle highlight */
            color: #352826 !important;
        }

        /* Headings inside drawer */
        .dropdown-menu.multi-column-dropdown {
            position: static;
            float: none;
            width: 100%;
            margin-top: .5rem;
            border: 1px solid #DED2C8;
            border-radius: 12px;
            padding: 12px;
            box-shadow: none;
        }

        .dropdown-menu.multi-column-dropdown h6 {
            color: #785A49;
            border-bottom: 1px solid #DED2C8;
            padding-bottom: 6px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        /* 3) Search block: full-width, pill style */
        .navbar form.d-flex {
            max-width: 100%;
            margin: .25rem 0 1rem 0;
        }

        .navbar .form-control[type="search"] {
            width: 100%;
            height: 42px;
            border: 1.5px solid #A57A5B;
            border-right: 0;
            border-radius: 999px 0 0 999px;
            /* pill left */
            padding: .55rem .9rem;
            font-size: .95rem;
        }

        .navbar .btn[type="submit"] {
            height: 42px;
            border-radius: 0 999px 999px 0;
            /* pill right */
            padding: .55rem .9rem;
            border-width: 1.5px;
        }

        /* 4) Account row: icon + name align nicely */
        #userDropdown .bi-person-circle {
            font-size: 1.6rem;
            vertical-align: -3px;
        }

        #userDropdown {
            padding-left: .25rem;
        }

        /* 5) Icon rows (bag & favorites) with badges close */
        .navbar .nav-item.position-relative {
            margin: .25rem 0;
        }

        .navbar .nav-item.position-relative .nav-link {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .6rem .4rem;
            border-radius: 10px;
        }

        .navbar .nav-item.position-relative .nav-link:hover {
            background: #F5EEE9;
        }

        .navbar .nav-item.position-relative i {
            font-size: 1.35rem;
        }

        .navbar .nav-item .nav-link {
            padding: .55rem .35rem;
        }

        .bag-badge,
        .fav-badge {
            top: -2px;
            /* even tighter on mobile */
            right: -2px;
            font-size: .70rem;
        }

        /* Brand image smaller so it doesn’t push the drawer */
        .navbar-brand img {
            height: 64px;
            margin: 0;
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
                <a href="checkout.php" class="btn btn-checkout">
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
            const res = await fetch('get_cart_data.php'); // match actual filename
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

        const response = await fetch('get_cart_data.php');
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