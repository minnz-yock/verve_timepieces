<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";
require_once "favorites_util.php";

/* helpers */
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
    if ($n === null || $n === '') return '—';
    $s = number_format((float)$n, 2, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return $s . 'mm';
}

// Fetch all active discounts from the database
function get_all_discounts($conn)
{
    $sql = "SELECT d.discount_id, d.kind, d.value, d.brand_id, d.category_id, pd.product_id
            FROM discounts d
            LEFT JOIN product_discounts pd ON d.discount_id = pd.discount_id
            WHERE d.is_active = 1 AND d.starts_at <= NOW() AND d.ends_at >= NOW()";
    $stmt = $conn->query($sql);
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($discounts as $d) {
        if ($d['product_id']) {
            $map['product'][$d['product_id']] = $d;
        } else if ($d['brand_id']) {
            $map['brand'][$d['brand_id']] = $d;
        } else if ($d['category_id']) {
            $map['category'][$d['category_id']] = $d;
        }
    }
    return $map;
}

// Find the best applicable discount for a product
function find_best_discount($discounts, $product_id, $brand_id, $category_id)
{
    if (isset($discounts['product'][$product_id])) {
        return $discounts['product'][$product_id];
    }
    if (isset($discounts['brand'][$brand_id])) {
        return $discounts['brand'][$brand_id];
    }
    if (isset($discounts['category'][$category_id])) {
        return $discounts['category'][$category_id];
    }
    return null;
}


$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT p.product_id, p.product_name, p.description, p.price, p.stock_quantity, p.image_url,
               p.case_size, b.brand_id, b.brand_name, c.category_id, c.cat_name,
               cm.case_material_id, cm.material AS case_material,
               g.gender_id, g.gender, dc.dial_color_id, dc.dial_color
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        JOIN categories c ON p.category_id = c.category_id
        JOIN case_materials cm ON p.case_material_id = cm.case_material_id
        JOIN genders g ON p.gender_id = g.gender_id
        JOIN dial_colors dc ON p.dial_color_id = dc.dial_color_id
        WHERE p.product_id = ?";
$st = $conn->prepare($sql);
$st->execute([$product_id]);
$product = $st->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    echo "Not found";
    exit;
}

$allDiscounts = get_all_discounts($conn);

$originalPrice = (float)$product['price'];
$finalPrice = $originalPrice;
$isDiscounted = false;

$discount = find_best_discount(
    $allDiscounts,
    (int)$product['product_id'],
    (int)$product['brand_id'],
    (int)$product['category_id']
);
if ($discount) {
    $isDiscounted = true;
    if ($discount['kind'] === 'percent') {
        $finalPrice = $originalPrice * (1 - ($discount['value'] / 100));
    } else { // fixed
        $finalPrice = $originalPrice - $discount['value'];
        if ($finalPrice < 0) $finalPrice = 0;
    }
}

$isFav = fav_is_favorited($conn, (int)$product['product_id']);

$likeSt = $conn->prepare("SELECT product_id, product_name, price, image_url
                          FROM products WHERE brand_id=? AND product_id<>?
                          ORDER BY product_id DESC LIMIT 4");
$likeSt->execute([(int)$product['brand_id'], $product_id]);
$also = $likeSt->fetchAll(PDO::FETCH_ASSOC);

$outOfStock = ((int)$product['stock_quantity'] <= 0);
$warrantyText = $product['brand_name'] . " official 2 year warranty included";
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= h($product['brand_name'] . ' ' . $product['product_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: #352826;
            background: #FDFBF9;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }


        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 12px 48px;
        }

        .hero {
            padding-top: 24px;
        }


        .img-wrap {
            width: 70%;
            background: #FFFFFF;
            border: 1px solid #E7DAD1;
            border-radius: 12px;
            aspect-ratio: 1/1;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .img-wrap img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            margin-top: -70px;
        }


        .badge-retailer {
            font-weight: 700;
            letter-spacing: .2px;
            color: #6B5A54;
            font-size: .9rem;
            margin-bottom: 2px;
        }

        .warranty {
            color: #6B5A54;
            font-size: .95rem;
            margin-bottom: 20px;
        }


        .brand {
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: .9rem;
            color: #A57A5B;
        }

        .model {
            font-weight: 800;
            font-size: 2.2rem;
            margin-top: 4px;
            line-height: 1.2;
            color: #352826;
        }

        .desc {
            margin: 1rem 0;
            color: #6B5A54;
            font-size: 1rem;
            line-height: 1.5;
        }


        .price {
            font-weight: 800;
            font-size: 2rem;
            margin: 16px 0;
            color: #352826;
        }

        .btn-fav {
            border: 1.5px solid #352826;
            color: #352826;
            background: transparent;
            border-radius: 10px;
            padding: .7rem .95rem;
            transition: transform .15s ease, background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .btn-fav:hover {
            background: #352826;
            color: #FFFFFF;
        }

        .btn-bag {
            background: #352826;
            color: #FFFFFF;
            border: 1.5px solid #352826;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: .2px;
            padding: .9rem 1.4rem;
            transition: transform .15s ease, background-color .15s ease, border-color .15s ease;
        }

        .btn-bag:hover {
            background: #2A201D;
            border-color: #2A201D;
        }

        .btn-bag:disabled {
            background: #CFC7C2;
            border-color: #CFC7C2;
            color: #7A716D;
            cursor: not-allowed;
        }

        .stock-msg {
            color: #D12C2C;
            font-weight: 800;
            margin-top: .75rem;
        }


        .specs {
            padding-top: 48px;
        }

        .specs h3,
        .like h3 {
            font-weight: 900;
            letter-spacing: .6px;
            margin-bottom: 16px;
            text-transform: uppercase;
            color: #352826;
        }

        .spec-table .row {
            padding: .8rem 0;
            border-bottom: 1px solid #E7DAD1;
        }

        .spec-table .row:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: #A57A5B;
            font-weight: 700;
        }


        .like {
            padding-top: 48px;
        }

        .card-watch {
            text-align: center;
            border: 0;
            background: transparent;
            position: relative;
        }

        .card-watch .imgbox {
            width: 100%;
            aspect-ratio: 1/1;
            border: 1px solid #E7DAD1;
            border-radius: 12px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: #FFFFFF;
        }

        .card-watch img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            margin-top: -20px;
        }

        .card-watch .brand {
            font-weight: 800;
            margin-top: .8rem;
            font-size: .8rem;
            letter-spacing: 1px;
            color: #A57A5B;
        }

        .card-watch .model {
            font-weight: 600;
            font-size: .95rem;
            color: #6B5A54;
            min-height: 2.2em;
            margin: .15rem 0 .4rem;
            line-height: 1.3;
        }

        .card-watch .price {
            font-weight: 800;
            font-size: 1rem;
            color: #352826;
        }


        a.card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform .18s ease, box-shadow .18s ease;
            will-change: transform;
        }

        a.card-link:hover {
            transform: translateY(-5px);
        }


        .fav-pin {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #FFFFFF;
            border: 1.5px solid #352826;
            color: #352826;
            cursor: pointer;
            z-index: 5;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
        }

        .fav-pin:hover {
            transform: translateY(-2px);
        }

        .fav-pin.active {
            background: #352826;
            color: #FFFFFF;
            border-color: #352826;
        }

        hr {
            border: none;
            border-top: 1px solid #352826;
            margin: 24px 0;
            width: 100%;
        }


        .text-muted {
            color: #6B5A54 !important;
        }

        .badge.bg-danger {
            color: #FFFFFF !important;
        }


        @media (max-width: 991px) {
            .img-wrap {
                width: 100%;
            }

            .model {
                font-size: 1.8rem;
            }

            .price {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 575px) {
            .model {
                font-size: 1.6rem;
            }

            .btn-bag {
                padding: .75rem 1.1rem;
            }

            .btn-fav {
                padding: .6rem .85rem;
            }
        }
    </style>
</head>

<body>

    <div class="row">
        <?php include 'navbarnew.php'; ?>
    </div>

    <div class="page-wrap">

        <section class="hero">
            <div class="row g-5 align-items-start">
                <div class="col-12 col-lg-7">
                    <div class="img-wrap">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['product_name']) ?>">
                        <?php else: ?>
                            <img src="../images/placeholder_watch.png" alt="No image">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="brand"><?= h($product['brand_name']) ?></div>
                    <div class="model"><?= h($product['product_name']) ?></div>
                    <div class="desc"><?= h(mm($product['case_size'])) ?> &nbsp;•&nbsp; <?= h($product['case_material']) ?></div>
                    <div class="price">
                        <?php if ($isDiscounted): ?>
                            <span style="text-decoration: line-through; color: #999;"><?= money($originalPrice) ?></span>
                            <span style="font-weight:bold; color:#c94c4c; margin-left:7px;"><?= money($finalPrice) ?></span>
                            <?php if ($discount['kind'] === 'percent'): ?>
                                <span class="badge bg-danger ms-2"><?= (float)$discount['value'] ?>% OFF</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2"><?= money($discount['value']) ?> OFF</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span><?= money($finalPrice) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-bag px-5 py-3 js-add-to-bag" type="button" data-id="<?= (int)$product['product_id'] ?>" <?= $outOfStock ? 'disabled' : '' ?>>
                            ADD TO BAG
                        </button>
                        <button class="btn btn-fav px-4 py-3 js-fav-one <?= $isFav ? 'btn-dark text-white' : '' ?>"
                            data-id="<?= (int)$product['product_id'] ?>" type="button" aria-label="Toggle favorite">
                            <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </button>
                    </div>
                    <div id="add-to-bag-message" class="mt-2 text-danger fw-bold"></div>

                    <?php if ($outOfStock): ?>
                        <div class="stock-msg">Out of Stock</div>
                    <?php endif; ?>

                    <div class="warranty mt-4">
                        <div class="badge-retailer">Official Authorized Retailer</div>
                        <?= h($warrantyText) ?>
                    </div>
                </div>
            </div>
        </section>

        <hr>

        <section class="specs">
            <h3>TECHNICAL SPECIFICATIONS</h3>
            <div class="container-fluid spec-table">
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Brand:</span></div>
                    <div class="col-12 col-md-6"><?= h($product['brand_name']) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Model:</span></div>
                    <div class="col-12 col-md-6"><?= h($product['product_name']) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Case Material:</span></div>
                    <div class="col-12 col-md-6"><?= h($product['case_material']) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Case Size:</span></div>
                    <div class="col-12 col-md-6"><?= h(mm($product['case_size'])) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Dial Color:</span></div>
                    <div class="col-12 col-md-6"><?= h($product['dial_color']) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Gender:</span></div>
                    <div class="col-12 col-md-6"><?= h($product['gender']) ?></div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6"><span class="spec-label">Warranty:</span></div>
                    <div class="col-12 col-md-6">Two Year Warranty</div>
                </div>
            </div>
        </section>

        <hr>

        <section class="like">
            <h3>OTHER WATCHES YOU MAY LIKE</h3>
            <?php if (empty($also)): ?>
                <div class="text-muted">No other watches from <?= h($product['brand_name']) ?> yet.</div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-4 g-4">
                    <?php foreach ($also as $a): ?>
                        <div class="col">
                            <a class="card-link" href="product_details.php?id=<?= (int)$a['product_id'] ?>">
                                <div class="card-watch">
                                    <button class="fav-pin js-fav <?= fav_is_favorited($conn, (int)$a['product_id']) ? 'active' : '' ?>"
                                        data-id="<?= (int)$a['product_id'] ?>" aria-label="Toggle favorite">
                                        <i class="fa-heart <?= fav_is_favorited($conn, (int)$a['product_id']) ? 'fa-solid' : 'fa-regular' ?>"></i>
                                    </button>
                                    <div class="imgbox">
                                        <?php if (!empty($a['image_url'])): ?>
                                            <img src="<?= h($a['image_url']) ?>" alt="<?= h($a['product_name']) ?>">
                                        <?php else: ?>
                                            <img src="../images/placeholder_watch.png" alt="No image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="brand"><?= h(strtoupper($product['brand_name'])) ?></div>
                                    <div class="model"><?= h($a['product_name']) ?></div>
                                    <div class="price"><?= money($a['price']) ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateFavBadge(n) {
            const b = document.getElementById('favCountBadge');
            if (!b) return;
            if (n > 0) {
                b.textContent = n;
                b.classList.remove('d-none');
            } else {
                b.textContent = '';
                b.classList.add('d-none');
            }
        }

        // main favorite button
        document.querySelector('.js-fav-one')?.addEventListener('click', async function() {
            const id = this.dataset.id;
            const fd = new FormData();
            fd.append('product_id', id);
            const r = await fetch('toggle_favorite.php', {
                method: 'POST',
                body: fd
            });
            const j = await r.json();
            if (j.ok) {
                const icon = this.querySelector('i');
                if (j.status === 'added') {
                    this.classList.add('btn-dark', 'text-white');
                    icon.classList.replace('fa-regular', 'fa-solid');
                } else {
                    this.classList.remove('btn-dark', 'text-white');
                    icon.classList.replace('fa-solid', 'fa-regular');
                }
                updateFavBadge(j.count);
            }
        });

        // hearts on "You may like"
        document.querySelectorAll('.js-fav').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                const id = btn.dataset.id;
                const fd = new FormData();
                fd.append('product_id', id);
                const r = await fetch('toggle_favorite.php', {
                    method: 'POST',
                    body: fd
                });
                const j = await r.json();
                if (j.ok) {
                    btn.classList.toggle('active', j.status === 'added');
                    const icon = btn.querySelector('i');
                    if (j.status === 'added') {
                        icon.classList.replace('fa-regular', 'fa-solid');
                    } else {
                        icon.classList.replace('fa-solid', 'fa-regular');
                    }
                    updateFavBadge(j.count);
                }
            });
        });
        // for add to card
        // At the end of your script block in product_details.php
        document.querySelector('.js-add-to-bag')?.addEventListener('click', async function() {
            const productId = this.dataset.id;
            const messageDiv = document.getElementById('add-to-bag-message');
            messageDiv.textContent = ''; // Clear previous messages
            messageDiv.classList.remove('d-block');

            const fd = new FormData();
            fd.append('product_id', productId);

            const response = await fetch('add_to_cart.php', {
                method: 'POST',
                body: fd
            });
            const result = await response.json();

            if (result.ok) {
                updateBagBadge(result.cart_count);
                // Display success message or hide the div
                messageDiv.textContent = 'Product added to your bag!';
                messageDiv.classList.add('text-success', 'd-block');
            } else {
                messageDiv.textContent = result.message;
                messageDiv.classList.add('text-danger', 'd-block');
            }
        });

        // Helper function to update the bag badge
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
    </script>

    <!-- Product Reviews Section -->
    <?php include 'review_handler.php'; ?>
    <?php include 'product_reviews_section.php'; ?>
</body>

</html>