<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";
require_once "favorites_util.php"; // Assuming you have this helper

// Helpers for this page
function h($v)
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function money($n)
{
    return '$' . number_format((float)$n, 2);
}
function mm($n)
{
    return (is_numeric($n) ? rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.') : '—') . 'mm';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Shopping Bag</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: #352826;
            background: #fdfdfd;
        }

        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 12px 48px;
        }

        .cart-title {
            font-weight: 800;
            letter-spacing: .5px;
            margin-bottom: 32px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 16px 0;
            border-bottom: 1px solid #DED2C8;
        }

        .cart-item-img {
            width: 120px;
            height: 120px;
            border: 1px solid #DED2C8;
            border-radius: 8px;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .cart-item-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-details .brand { font-weight: 700; font-size: 1.1rem; color: #352826; }

        .item-details .model { font-weight: 600; font-size: 1rem; color: #785A49; }

        .item-details .price { font-weight: 700; font-size: 1.2rem; margin-top: 8px; color: #352826; }

        .remove-btn {
            color: #888;
            text-decoration: none;
            font-size: .9rem;
        }

        .order-summary {
            background: #fafafa;
            border: 1px solid #DED2C8;
            padding: 24px;
            border-radius: 8px;
        }

        .order-summary h4 {
            font-weight: 800;
            letter-spacing: .5px;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 24px;
        }

        .btn-checkout { background: #352826; color: #fff; font-weight: 600; padding: 12px 24px; }

        .btn-return { background: #785A49; color: #fff; font-weight: 600; padding: 12px 24px; }

        .empty-cart-message {
            border: 1px solid #DED2C8;
            padding: 48px;
            text-align: center;
            border-radius: 8px;
            background: #fff;
        }
    </style>
</head>

<body>
    <div class="row">
        <?php include 'navbarnew.php'; ?>
    </div>
    <div class="page-wrap">
        <h1 class="cart-title">CART</h1>
        <div id="cart-content">
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const cartContent = document.getElementById('cart-content');

        async function fetchCartData() {

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
            renderCart(data);
        }

        function renderCart(data) {
            if (data.items.length === 0) {
                cartContent.innerHTML = `
                    <div class="empty-cart-message">
                        <p>Your cart is currently empty.</p>
                        <a href="view_products.php" class="btn btn-return mt-3">Return To Shop</a>
                    </div>
                `;
                return;
            }

            let cartItemsHtml = '';
            let subtotal = 0;

            data.items.forEach(item => {
                const itemPrice = parseFloat(item.price) * parseInt(item.quantity);
                subtotal += itemPrice;
                cartItemsHtml += `
                    <div class="cart-item" data-product-id="${item.product_id}">
                        <div class="cart-item-img">
                            <img src="${item.image_url || '../images/placeholder_watch.png'}" alt="${item.product_name}">
                        </div>
                        <div class="item-details">
                            <div class="brand">${item.brand_name}</div>
                            <div class="model">${item.product_name}</div>
                            <div class="price">${formatMoney(itemPrice)}</div>
                            <button class="btn btn-sm btn-link p-0 remove-btn" data-product-id="${item.product_id}">
                                <i class="fa-solid fa-times"></i> Remove from bag
                            </button>
                        </div>
                        <div class="item-quantity d-flex align-items-center">
                            <select class="form-select quantity-select" data-product-id="${item.product_id}" style="width: 80px;">
                                ${[...Array(parseInt(item.stock_quantity)).keys()].map(i => `<option value="${i + 1}" ${i + 1 === parseInt(item.quantity) ? 'selected' : ''}>${i + 1}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                `;
            });

            const orderSummaryHtml = `
                <div class="row mt-4">
                    <div class="col-12 col-lg-8">
                        ${cartItemsHtml}
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="order-summary">
                            <h4>ORDER SUMMARY</h4>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>${formatMoney(subtotal)}</span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="cart-total">${formatMoney(subtotal)}</span>
                            </div>
                            <a href="checkout.php" class="btn btn-checkout w-100 mt-4">
                                <i class="fa-solid fa-lock"></i> PROCEED TO CHECKOUT
                            </a>
                        </div>
                    </div>
                </div>
            `;

            cartContent.innerHTML = orderSummaryHtml;
            attachEventListeners();
        }

        function formatMoney(amount) {
            return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        function attachEventListeners() {
            document.querySelectorAll('.quantity-select').forEach(select => {
                select.addEventListener('change', async (e) => {
                    const productId = e.target.dataset.productId;
                    const newQuantity = e.target.value;
                    await updateCart(productId, newQuantity, 'update');
                });
            });

            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const productId = e.target.closest('button').dataset.productId;
                    await updateCart(productId, 0, 'remove');
                });
            });
        }

        async function updateCart(productId, quantity, action) {
            const fd = new FormData();
            fd.append('product_id', productId);
            fd.append('action', action);
            if (action === 'update') {
                fd.append('quantity', quantity);
            }
            const response = await fetch('update_cart.php', {
                method: 'POST',
                body: fd
            });
            const result = await response.json();
            if (result.ok) {
                fetchCartData(); // Re-render the cart
            } else {
                alert(result.message);
                fetchCartData(); // Re-render to show correct data
            }
        }

        document.addEventListener('DOMContentLoaded', fetchCartData);
    </script>
</body>

</html>