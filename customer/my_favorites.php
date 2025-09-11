<?php
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";
require_once "favorites_util.php";

function h($v)
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function money($n)
{
  return '$' . number_format((float)$n, 2);
}

$ids = fav_get_ids($conn);  // fixed name
$products = [];

if ($ids) {
  $in = implode(',', array_fill(0, count($ids), '?'));
  $sql = "SELECT p.product_id, p.product_name, p.price, p.image_url, b.brand_name
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          WHERE p.product_id IN ($in)
          ORDER BY p.product_id DESC";
  $st = $conn->prepare($sql);
  $st->execute($ids);
  $products = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>My Favorites</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    body { color: #352826; }
    .page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 12px 40px;
    }

    .head {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: 14px 0 6px;
    }

    .imgbox {
      width: 100%;
      aspect-ratio: 1/1;
      border: 1px solid #DED2C8;
      border-radius: 12px;
      display: grid;
      place-items: center;
      overflow: hidden;
    }

    .imgbox img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .cardlink {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .brand {
      margin-top: .65rem;
      font-weight: 800;
      letter-spacing: .6px;
      color: #352826;
      text-transform: uppercase;
      text-align: center;
    }

    .model { font-weight: 600; text-align: center; color: #785A49; }

    .price { font-weight: 700; text-align: center; color: #352826; }

    .cardwrap {
      position: relative;
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
      border: 1px solid #352826;
      cursor: pointer;
      z-index: 5;
    }

    .fav-pin.active { background: #352826; color: #fff; }
  </style>
</head>

<body>

  <div class="row"><?php include 'navbarnew.php'; ?></div>

  <div class="page">
    <div class="head">
      <h2 class="m-0">My Favorites</h2>
      <span class="text-muted">(<?= count($products) ?>)</span>
    </div>

    <?php if (empty($products)): ?>
      <div class="text-muted py-5">No favorites yet.</div>
    <?php else: ?>
      <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
        <?php foreach ($products as $p): ?>
          <div class="col">
            <div class="cardwrap">
              <button class="fav-pin js-fav active" data-id="<?= (int)$p['product_id'] ?>" aria-label="Toggle favorite">
                <i class="fa-regular fa-heart"></i>
              </button>
              <a class="cardlink" href="product_details.php?id=<?= (int)$p['product_id'] ?>">
                <div class="imgbox">
                  <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['product_name']) ?>">
                  <?php else: ?>
                    <img src="../images/placeholder_watch.png" alt="No image">
                  <?php endif; ?>
                </div>
                <div class="brand"><?= h(strtoupper($p['brand_name'])) ?></div>
                <div class="model"><?= h($p['product_name']) ?></div>
                <div class="price"><?= money($p['price']) ?></div>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
    document.querySelectorAll('.js-fav').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        const fd = new FormData();
        fd.append('product_id', id);
        const r = await fetch('toggle_favorite.php', {
          method: 'POST',
          body: fd
        });
        const j = await r.json();
        if (j.ok) {
          const nowActive = (j.status === 'added');
          btn.classList.toggle('active', nowActive);
          updateFavBadge(j.count);
          // if removed from favorites page, hide card
          if (!nowActive) {
            btn.closest('.col').remove();
          }
        }
      });
    });
  </script>
</body>

</html>