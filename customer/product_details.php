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
            color: #222;
            background: #fdfdfd;
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
            width: 100%;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            aspect-ratio: 1/1;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .badge-retailer {
            font-weight: 700;
            letter-spacing: .2px;
            color: #555;
            font-size: .9rem;
            margin-bottom: 2px;
        }

        .warranty {
            color: #555;
            font-size: .95rem;
            margin-bottom: 20px;
        }

        .brand {
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: .9rem;
        }

        .model {
            font-weight: 700;
            font-size: 2.2rem;
            margin-top: 4px;
            line-height: 1.2;
        }

        .desc {
            margin: 1rem 0;
            color: #555;
            font-size: 1rem;
            line-height: 1.5;
        }

        .price {
            font-weight: 700;
            font-size: 2rem;
            margin: 16px 0;
        }

        .btn-fav {
            border: 1px solid #222;
            border-radius: 8px;
        }

        .btn-bag {
            background: #222;
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-bag:disabled {
            background: #ccc;
            border-color: #ccc;
            color: #888;
        }

        .stock-msg {
            color: #d00;
            font-weight: 700;
            margin-top: .75rem;
        }

        .specs {
            padding-top: 48px;
        }

        .specs h3,
        .like h3 {
            font-weight: 800;
            letter-spacing: .5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .spec-table .row {
            padding: .8rem 0;
            border-bottom: 1px solid #eee;
        }

        .spec-table .row:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: #555;
        }

        .like {
            padding-top: 48px;
        }

        .card-watch {
            text-align: center;
            border: 0;
            background: #fdfdfd;
            position: relative;
        }

        .card-watch .imgbox {
            width: 100%;
            aspect-ratio: 1/1;
            border: 1px solid #eee;
            border-radius: 12px;
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .card-watch img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .card-watch .brand {
            font-weight: 800;
            margin-top: .8rem;
            font-size: .8rem;
            letter-spacing: 1px;
        }

        .card-watch .model {
            font-weight: 600;
            font-size: .9rem;
            color: #444;
            min-height: 2.2em;
            margin: .1rem 0 .35rem;
            line-height: 1.3;
        }

        .card-watch .price {
            font-weight: 700;
            font-size: 1rem;
            color: #222;
        }

        a.card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.2s ease-in-out;
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
            background: #fff;
            border: 1px solid #222;
            cursor: pointer;
            z-index: 5;
            transition: all 0.2s ease-in-out;
        }

        .fav-pin.active {
            background: #222;
            color: #fff;
        }

        hr {
            border: none;
            border-top: 1px solid #352826;
            margin: 24px 0;
            width: 100%;
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
                    <div class="price"><?= money($product['price']) ?></div>

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