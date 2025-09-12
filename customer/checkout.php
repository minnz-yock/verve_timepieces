<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../dbconnect.php';
require_once '../user_login_check.php';
if (empty($_SESSION['user_id'])) {
  header('Location: /login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];

// Load saved addresses for prefills from new tables
function fetch_ship(PDO $conn, int $userId)
{
  $st = $conn->prepare('SELECT first_name, last_name, country_region, street_address, address_line2, city_town, phone FROM ship_address WHERE user_id = ?');
  $st->execute([$userId]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}
function fetch_bill(PDO $conn, int $userId)
{
  $st = $conn->prepare('SELECT first_name, last_name, country_region, street_address, address_line2, city_town, phone FROM bill_address WHERE user_id = ?');
  $st->execute([$userId]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

$shippingSaved = fetch_ship($conn, $userId);
$billingSaved  = fetch_bill($conn, $userId);

// Initialize checkout state in session
$_SESSION['checkout'] = $_SESSION['checkout'] ?? [
  'shipping' => [
    'first_name' => $shippingSaved['first_name'] ?? '',
    'last_name' => $shippingSaved['last_name'] ?? '',
    'country_region' => $shippingSaved['country_region'] ?? '',
    'street_address' => $shippingSaved['street_address'] ?? '',
    'address_line2' => $shippingSaved['address_line2'] ?? '',
    'city_town' => $shippingSaved['city_town'] ?? '',
    'phone' => $shippingSaved['phone'] ?? '',
    'method' => '',
    'cost' => 0.0,
  ],
  'billing' => [
    'same_as_shipping' => false,
    'first_name' => $billingSaved['first_name'] ?? '',
    'last_name' => $billingSaved['last_name'] ?? '',
    'country_region' => $billingSaved['country_region'] ?? '',
    'street_address' => $billingSaved['street_address'] ?? '',
    'address_line2' => $billingSaved['address_line2'] ?? '',
    'city_town' => $billingSaved['city_town'] ?? '',
    'phone' => $billingSaved['phone'] ?? '',
    'payment_method' => '',
    'payment_meta' => '',
  ]
];

$state = &$_SESSION['checkout'];

// Handle POST for steps
$step = $_GET['step'] ?? 'shipping';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($step === 'shipping') {
    $s = &$state['shipping'];
    $s['first_name'] = trim($_POST['first_name'] ?? '');
    $s['last_name']  = trim($_POST['last_name'] ?? '');
    $s['country_region'] = trim($_POST['country_region'] ?? '');
    $s['street_address'] = trim($_POST['street_address'] ?? '');
    $s['address_line2']  = trim($_POST['address_line2'] ?? '');
    $s['city_town'] = trim($_POST['city_town'] ?? '');
    $s['phone'] = trim($_POST['phone'] ?? '');
    $s['method'] = trim($_POST['shipping_method'] ?? '');
    // compute cost
    $isMm = strcasecmp($s['country_region'], 'Myanmar') === 0;
    if ($isMm) {
      $s['cost'] = ($s['method'] === 'express') ? 15.0 : 0.0;
      $s['method'] = ($s['method'] === 'express') ? 'Express (1–2 days)' : 'Standard (3–7 days)';
    } else {
      if ($s['method'] === 'express_intl') {
        $s['cost'] = 50.0;
        $s['method'] = 'Express Intl (3–7 days, DHL/UPS)';
      } else {
        $s['cost'] = 20.0;
        $s['method'] = 'Standard Intl (1–3 weeks)';
      }
    }
    header('Location: checkout.php?step=billing');
    exit;
  }
  if ($step === 'billing') {
    $b = &$state['billing'];
    $same = isset($_POST['same_as_shipping']);
    $b['same_as_shipping'] = $same;
    if ($same) {
      $b['first_name'] = $state['shipping']['first_name'];
      $b['last_name']  = $state['shipping']['last_name'];
      $b['country_region'] = $state['shipping']['country_region'];
      $b['street_address'] = $state['shipping']['street_address'];
      $b['address_line2']  = $state['shipping']['address_line2'];
      $b['city_town'] = $state['shipping']['city_town'];
      $b['phone'] = $state['shipping']['phone'];
    } else {
      $b['first_name'] = trim($_POST['first_name'] ?? '');
      $b['last_name']  = trim($_POST['last_name'] ?? '');
      $b['country_region'] = trim($_POST['country_region'] ?? '');
      $b['street_address'] = trim($_POST['street_address'] ?? '');
      $b['address_line2']  = trim($_POST['address_line2'] ?? '');
      $b['city_town'] = trim($_POST['city_town'] ?? '');
      $b['phone'] = trim($_POST['phone'] ?? '');
    }
    $b['payment_method'] = trim($_POST['payment_method'] ?? '');
    $b['payment_meta']   = trim($_POST['payment_meta'] ?? '');
    header('Location: checkout.php?step=review');
    exit;
  }
}

// Fetch cart for review
$sessionId = session_id();
$stCart = $conn->prepare('SELECT c.product_id, c.quantity, p.product_name, p.price, p.image_url, b.brand_name FROM cart c JOIN products p ON c.product_id=p.product_id JOIN brands b ON p.brand_id=b.brand_id WHERE (c.user_id = ? OR c.session_id = ?)');
$stCart->execute([$userId, $sessionId]);
$cartItems = $stCart->fetchAll(PDO::FETCH_ASSOC);

function money($n)
{
  return '$' . number_format((float)$n, 2);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <title>Checkout</title>
  <style>
    body {
      color: #352826;
    }

    .container-narrow {
      max-width: 980px;
      margin: 0 auto;
      padding: 16px 12px 40px;
    }

    .steps {
      display: flex;
      gap: 20px;
      align-items: center;
      justify-content: center;
      margin: 8px 0 20px;
    }

    .step {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #785A49;
    }

    .step .dot {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      border: 1px solid #DED2C8;
    }

    .step.active .dot {
      background: #352826;
      color: #fff;
      border-color: #352826
    }

    .card {
      border: 1px solid #DED2C8;
    }

    .card-header {
      font-weight: 800;
      color: #352826
    }

    .form-control,
    .form-select {
      border-color: #A57A5B;
      color: #352826
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #785A49;
      box-shadow: 0 0 0 .2rem rgba(120, 90, 73, .15)
    }

    .btn-dark {
      background: #352826;
      border-color: #352826
    }

    .btn-dark:hover {
      background: #785A49;
      border-color: #785A49
    }

    .btn-outline-dark {
      color: #352826;
      border-color: #352826
    }

    .btn-outline-dark:hover {
      background: #352826;
      color: #fff
    }

    .summary-card {
      border: 1px solid #DED2C8;
      border-radius: 8px;
    }

    .img-thumb {
      width: 48px;
      height: 48px;
      border: 1px solid #DED2C8;
      border-radius: 6px;
      display: grid;
      place-items: center;
      overflow: hidden;
      background: #fff
    }
  </style>
</head>

<body>
  <?php include 'navbarnew.php'; ?>

  <div class="container-narrow">
    <h2 class="text-center fw-bold">Checkout</h2>
    <p class="text-center text-muted" style="margin-top:-6px">Complete your order in just a few steps</p>
    <div class="steps">
      <div class="step <?= $step === 'shipping' ? 'active' : '' ?>">
        <div class="dot">1</div>
        <div>Shipping Address</div>
      </div>
      <div class="step <?= $step === 'billing' ? 'active' : '' ?>">
        <div class="dot">2</div>
        <div>Billing & Payment</div>
      </div>
      <div class="step <?= $step === 'review' ? 'active' : '' ?>">
        <div class="dot">3</div>
        <div>Order Review</div>
      </div>
    </div>

    <?php if ($step === 'shipping'): $s = $state['shipping'];
      $isMm = strcasecmp($s['country_region'], 'Myanmar') === 0; ?>
      <div class="card">
        <div class="card-header p-3">SHIPPING ADDRESS</div>
        <div class="card-body">
          <form method="post">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">First name *</label><input class="form-control" name="first_name" value="<?= htmlspecialchars($s['first_name']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Last name *</label><input class="form-control" name="last_name" value="<?= htmlspecialchars($s['last_name']) ?>" required></div>
              <div class="col-12"><label class="form-label">Country / Region *</label><input class="form-control" name="country_region" value="<?= htmlspecialchars($s['country_region']) ?>" required></div>
              <div class="col-12"><label class="form-label">Street address *</label><input class="form-control" name="street_address" value="<?= htmlspecialchars($s['street_address']) ?>" required></div>
              <div class="col-12"><label class="form-label">Apartment, unit, etc. (optional)</label><input class="form-control" name="address_line2" value="<?= htmlspecialchars($s['address_line2']) ?>"></div>
              <div class="col-md-6"><label class="form-label">Town / City *</label><input class="form-control" name="city_town" value="<?= htmlspecialchars($s['city_town']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" name="phone" value="<?= htmlspecialchars($s['phone']) ?>" required></div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">SHIPPING METHOD</h6>
            <div id="shipping-methods">
              <!-- Methods will be toggled by JS based on country -->
              <div class="form-check mb-2 mm-only">
                <input class="form-check-input" type="radio" name="shipping_method" id="mm_std" value="standard" <?= ($isMm && strpos($s['method'], 'Standard') !== false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="mm_std">Standard (3–7 days) – Free</label>
              </div>
              <div class="form-check mb-2 mm-only">
                <input class="form-check-input" type="radio" name="shipping_method" id="mm_exp" value="express" <?= ($isMm && strpos($s['method'], 'Express (1–2 days)') !== false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="mm_exp">Express (1–2 days) – $15</label>
              </div>

              <div class="form-check mb-2 intl-only">
                <input class="form-check-input" type="radio" name="shipping_method" id="intl_std" value="standard_intl" <?= (!$isMm && strpos($s['method'], 'Standard Intl') !== false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="intl_std">Standard Intl (1–3 weeks) – $20</label>
              </div>
              <div class="form-check mb-2 intl-only">
                <input class="form-check-input" type="radio" name="shipping_method" id="intl_exp" value="express_intl" <?= (!$isMm && strpos($s['method'], 'Express Intl') !== false) ? 'checked' : '' ?>>
                <label class="form-check-label" for="intl_exp">Express Intl (3–7 days, DHL/UPS) – $50</label>
              </div>
            </div>
            <div class="d-flex gap-2 mt-4">
              <a class="btn btn-outline-dark" href="card.php">Back to Bag</a>
              <button class="btn btn-dark" type="submit">Continue to Billing</button>
            </div>
          </form>
        </div>
      </div>
    <?php elseif ($step === 'billing'): $b = $state['billing']; ?>
      <div class="card">
        <div class="card-header p-3">BILLING INFORMATION</div>
        <div class="card-body">
          <form method="post">
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="sameAs" name="same_as_shipping" <?= $b['same_as_shipping'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="sameAs">Same as shipping address</label>
            </div>
            <div id="billing-fields" class="row g-3 <?= $b['same_as_shipping'] ? 'd-none' : '' ?>">
              <div class="col-md-6"><label class="form-label">First name *</label><input class="form-control" name="first_name" value="<?= htmlspecialchars($b['first_name']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Last name *</label><input class="form-control" name="last_name" value="<?= htmlspecialchars($b['last_name']) ?>" required></div>
              <div class="col-12"><label class="form-label">Country / Region *</label><input class="form-control" name="country_region" value="<?= htmlspecialchars($b['country_region']) ?>" required></div>
              <div class="col-12"><label class="form-label">Street address *</label><input class="form-control" name="street_address" value="<?= htmlspecialchars($b['street_address']) ?>" required></div>
              <div class="col-12"><label class="form-label">Apartment, unit, etc. (optional)</label><input class="form-control" name="address_line2" value="<?= htmlspecialchars($b['address_line2']) ?>"></div>
              <div class="col-md-6"><label class="form-label">Town / City *</label><input class="form-control" name="city_town" value="<?= htmlspecialchars($b['city_town']) ?>" required></div>
              <div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" name="phone" value="<?= htmlspecialchars($b['phone']) ?>" required></div>
            </div>

            <hr class="my-4">
            <h6 class="mb-3">PAYMENT METHOD</h6>
            <div class="row g-3">
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="payment_method" id="pm_card" value="Card" <?= $b['payment_method'] === 'Card' ? 'checked' : '' ?> required>
                  <label class="form-check-label" for="pm_card"><i class="bi bi-credit-card-2-front"></i> Credit / Debit Card</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="Cash on Delivery" <?= $b['payment_method'] === 'Cash on Delivery' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="pm_cod"><i class="bi bi-cash"></i> Cash on Delivery</label>
                </div>
              </div>
              <div class="col-12"><label class="form-label">Payment details (masked or notes)</label><input class="form-control" name="payment_meta" value="<?= htmlspecialchars($b['payment_meta']) ?>" placeholder="e.g., Card ending in 4242"></div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <a class="btn btn-outline-dark" href="checkout.php?step=shipping">Back to Shipping</a>
              <button class="btn btn-dark" type="submit">Continue to Review</button>
            </div>
          </form>
        </div>
      </div>
    <?php else: $s = $state['shipping'];
      $b = $state['billing'];
      $subtotal = 0;
      foreach ($cartItems as $it) {
        $subtotal += ((float)$it['price'] * (int)$it['quantity']);
      }
      $total = $subtotal + (float)$s['cost']; ?>
      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <div class="summary-card p-3">
            <h5 class="fw-bold mb-3">ORDER SUMMARY</h5>
            <?php foreach ($cartItems as $it): ?>
              <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f0eae4">
                <div class="d-flex align-items-center gap-2">
                  <div class="img-thumb"><?php if (!empty($it['image_url'])): ?><img src="<?= htmlspecialchars($it['image_url']) ?>" style="max-width:100%;max-height:100%;object-fit:contain"><?php else: ?>IMG<?php endif; ?></div>
                  <div>
                    <div class="text-uppercase small" style="letter-spacing:.6px; font-weight:800; color:#352826"><?= htmlspecialchars($it['brand_name']) ?></div>
                    <div style="font-weight:600; color:#785A49"><?= htmlspecialchars($it['product_name']) ?></div>
                    <div class="text-muted small">Qty: <?= (int)$it['quantity'] ?></div>
                  </div>
                </div>
                <div class="fw-bold" style="color:#352826"><?= money((float)$it['price'] * (int)$it['quantity']) ?></div>
              </div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between pt-3"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="d-flex justify-content-between"><span>Shipping</span><span><?= $s['cost'] > 0 ? money($s['cost']) : 'Free' ?></span></div>
            <div class="d-flex justify-content-between fw-bold fs-5 mt-2"><span>Total</span><span><?= money($total) ?></span></div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
          <div class="summary-card p-3">
            <h5 class="fw-bold mb-3">SHIPPING & BILLING</h5>
            <div class="mb-3">
              <div class="fw-bold">Shipping Address</div>
              <div><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
              <div><?= htmlspecialchars($s['street_address']) ?></div>
              <?php if (!empty($s['address_line2'])): ?><div><?= htmlspecialchars($s['address_line2']) ?></div><?php endif; ?>
              <div><?= htmlspecialchars($s['city_town'] . ', ' . $s['country_region']) ?></div>
              <div><?= htmlspecialchars($s['phone']) ?></div>
              <div class="text-muted small mt-1">Method: <?= htmlspecialchars($s['method']) ?></div>
            </div>
            <div class="mb-3">
              <div class="fw-bold">Billing Address</div>
              <?php if ($b['same_as_shipping']): ?>
                <div>Same as shipping address</div>
              <?php else: ?>
                <div><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                <div><?= htmlspecialchars($b['street_address']) ?></div>
                <?php if (!empty($b['address_line2'])): ?><div><?= htmlspecialchars($b['address_line2']) ?></div><?php endif; ?>
                <div><?= htmlspecialchars($b['city_town'] . ', ' . $b['country_region']) ?></div>
                <div><?= htmlspecialchars($b['phone']) ?></div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
              <div class="fw-bold">Payment Method</div>
              <div><?= htmlspecialchars($b['payment_method'] ?: '—') ?></div>
              <?php if ($b['payment_meta']): ?><div class="text-muted small"><?= htmlspecialchars($b['payment_meta']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex gap-2 mt-2">
              <a class="btn btn-outline-dark" href="checkout.php?step=billing">Back to Billing</a>
              <form method="post" action="place_order.php">
                <button class="btn btn-dark" type="submit">Place Order</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle Myanmar vs International shipping methods by country input
    (function() {
      var country = document.querySelector('input[name=country_region]');
      if (!country) return;

      function refresh() {
        var isMm = (country.value || '').trim().toLowerCase() === 'myanmar';
        document.querySelectorAll('.mm-only').forEach(function(el) {
          el.style.display = isMm ? 'block' : 'none';
        });
        document.querySelectorAll('.intl-only').forEach(function(el) {
          el.style.display = isMm ? 'none' : 'block';
        });
      }
      country.addEventListener('input', refresh);
      refresh();
    })();

    // Billing: same as shipping toggle
    (function() {
      var same = document.getElementById('sameAs');
      var fields = document.getElementById('billing-fields');
      if (!same || !fields) return;
      same.addEventListener('change', function() {
        fields.classList.toggle('d-none', same.checked);
      });
    })();
  </script>
</body>

</html>