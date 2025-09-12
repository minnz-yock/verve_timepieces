<?php // /customer/address_book.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../dbconnect.php';
require_once '../user_login_check.php';
if (empty($_SESSION['user_id'])) {
  header('Location: /login.php');
  exit;
}

// Load current user
$stmtUser = $conn->prepare('SELECT id, first_name, last_name, email FROM users WHERE id = ?');
$stmtUser->execute([(int)$_SESSION['user_id']]);
$user = $stmtUser->fetch();

// Fetch existing addresses from new tables
$addr = ['billing' => null, 'shipping' => null];
// Billing
$stmtBill = $conn->prepare('SELECT first_name, last_name, country_region, street_address, address_line2, city_town, phone FROM bill_address WHERE user_id = ?');
$stmtBill->execute([$user['id']]);
$rowBill = $stmtBill->fetch(PDO::FETCH_ASSOC);
if ($rowBill) { $addr['billing'] = $rowBill; }
// Shipping
$stmtShip = $conn->prepare('SELECT first_name, last_name, country_region, street_address, address_line2, city_town, phone FROM ship_address WHERE user_id = ?');
$stmtShip->execute([$user['id']]);
$rowShip = $stmtShip->fetch(PDO::FETCH_ASSOC);
if ($rowShip) { $addr['shipping'] = $rowShip; }
// Flash message
$ok  = isset($_GET['ok']) ? (int)$_GET['ok'] : null;
$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
$typeJustSaved = isset($_GET['type']) ? (string)$_GET['type'] : '';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <title>Address Book</title>
  <style>
    body {
      color: #352826;
    }

    .account-wrapper {
      display: flex;
      gap: 28px;
      align-items: flex-start;
      padding: 24px;
      max-width: 1200px;
      margin: 0 auto
    }

    .account-menu {
      width: 250px
    }

    .user-head {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 12px
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #352826;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700
    }

    .user-name {
      font-weight: 800;
      letter-spacing: .3px
    }

    .user-email {
      color: #666;
      font-size: 14px
    }

    .account-menu nav {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 8px
    }

    .account-menu a {
      color: #785A49;
      text-decoration: none;
      padding: 6px 0
    }

    .account-menu a:hover {
      color: #352826
    }

    .account-menu a.active {
      font-weight: 700;
      color: #352826
    }

    .account-menu a.logout {
      color: #352826
    }

    .account-content {
      flex: 1
    }

    .alert {
      padding: 10px 12px;
      border-radius: 8px;
      margin: 0 0 14px 0;
      background: #DED2C8;
      border: 1px solid #A57A5B;
      color: #352826
    }

    .alert.error {
      background: #f9e0e0;
      border-color: #e0b3b3
    }

    .form {
      display: flex;
      flex-direction: column;
      gap: 14px;
      max-width: 820px
    }

    .form .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px
    }

    .form label {
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-weight: 600;
      color: #352826
    }

    .form input {
      padding: 10px 12px;
      border: 1px solid #A57A5B;
      border-radius: 6px;
      color: #352826
    }

    .form input:focus {
      outline: 2px solid #785A49;
      border-color: #785A49
    }

    .btn {
      padding: 10px 16px;
      border: 1px solid #352826;
      border-radius: 8px;
      cursor: pointer;
      background: #352826;
      color: #ffffff
    }

    .btn:hover {
      background: #785A49;
      border-color: #785A49
    }

    .btn.primary {
      background: #352826;
      color: #ffffff;
      font-weight: 700;
      letter-spacing: .2px
    }

    .btn.primary:hover {
      background: #785A49
    }

    .cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin: 16px 0
    }

    .card {
      background: #DED2C8;
      border-radius: 10px;
      border: 1px solid #A57A5B
    }

    .card-title {
      font-weight: 800;
      padding: 14px 16px;
      color: #352826
    }

    .card-body {
      padding: 0 16px 16px 16px
    }

    .muted {
      color: #71797E;
    }

    .hidden {
      display: none
    }
  </style>
</head>

<body>
  <?php include 'navbarnew.php'; ?>
  <?php $section = 'address-book'; ?>

  <main class="account-wrapper">
    <?php include 'side_menu.php'; ?>

    <section class="account-content">
      <?php if ($msg !== ''): ?>
        <div class="alert <?= $ok === 1 ? 'success' : 'error' ?>" id="flashMsg"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <h2>ADDRESS BOOK</h2>
      <p class="muted">The addresses provided below will be utilized by default on the page where you check out.</p>
      <div class="cards">
        <div class="card">
          <div class="card-title">BILLING ADDRESS</div>
          <div class="card-body">
            <?php if ($addr['billing']): ?>
              <?= htmlspecialchars(trim(($addr['billing']['first_name'] ?? '') . ' ' . ($addr['billing']['last_name'] ?? ''))) ?><br>
              <?= htmlspecialchars($addr['billing']['street_address'] ?? '') ?><br>
              <?= htmlspecialchars($addr['billing']['city_town'] ?? '') ?><br>
              <?= htmlspecialchars($addr['billing']['country_region'] ?? '') ?><br>
              <?= htmlspecialchars($addr['billing']['phone'] ?? '') ?><br>
              <a href="#" onclick="return showForm('billing')">Update</a>
            <?php else: ?>
              You have not set up this type of address yet.<br>
              <a href="#" onclick="return showForm('billing')">Add</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="card">
          <div class="card-title">SHIPPING ADDRESS</div>
          <div class="card-body">
            <?php if ($addr['shipping']): ?>
              <?= htmlspecialchars(trim(($addr['shipping']['first_name'] ?? '') . ' ' . ($addr['shipping']['last_name'] ?? ''))) ?><br>
              <?= htmlspecialchars($addr['shipping']['street_address'] ?? '') ?><br>
              <?= htmlspecialchars($addr['shipping']['city_town'] ?? '') ?><br>
              <?= htmlspecialchars($addr['shipping']['country_region'] ?? '') ?><br>
              <?= htmlspecialchars($addr['shipping']['phone'] ?? '') ?><br>
              <a href="#" onclick="return showForm('shipping')">Update</a>
            <?php else: ?>
              You have not set up this type of address yet.<br>
              <a href="#" onclick="return showForm('shipping')">Add</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- BILLING FORM -->
      <form id="billing-form" method="post" action="/customer/address_save.php" class="form hidden" style="margin-top:24px">
        <input type="hidden" name="address_type" value="billing" />
        <h2>BILLING ADDRESS</h2>
        <div class="grid">
          <label>First name *
            <input type="text" name="first_name" value="<?= htmlspecialchars($addr['billing']['first_name'] ?? ($user['first_name'] ?? '')) ?>" required>
          </label>
          <label>Last name *
            <input type="text" name="last_name" value="<?= htmlspecialchars($addr['billing']['last_name'] ?? ($user['last_name'] ?? '')) ?>" required>
          </label>
        </div>
        <div class="grid">
          <label>Country / Region *
            <input type="text" name="country_region" value="<?= htmlspecialchars($addr['billing']['country_region'] ?? '') ?>" required>
          </label>
        </div>
        <label>Street address *
          <input type="text" name="street_address" value="<?= htmlspecialchars($addr['billing']['street_address'] ?? '') ?>" required>
        </label>
        <label>Apartment, unit, etc. (optional)
          <input type="text" name="address_line2" value="<?= htmlspecialchars($addr['billing']['address_line2'] ?? '') ?>">
        </label>
        <label>Town / City *
          <input type="text" name="city_town" value="<?= htmlspecialchars($addr['billing']['city_town'] ?? '') ?>" required>
        </label>
        <label>Phone *
          <input type="tel" name="phone" value="<?= htmlspecialchars($addr['billing']['phone'] ?? '') ?>" required>
        </label>
        <button class="btn primary" type="submit">SAVE ADDRESS</button>
      </form>

      <!-- SHIPPING FORM -->
      <form id="shipping-form" method="post" action="/customer/address_save.php" class="form hidden" style="margin-top:24px">
        <input type="hidden" name="address_type" value="shipping" />
        <h2>SHIPPING ADDRESS</h2>
        <div class="grid">
          <label>First name *
            <input type="text" name="first_name" value="<?= htmlspecialchars($addr['shipping']['first_name'] ?? '') ?>" required>
          </label>
          <label>Last name *
            <input type="text" name="last_name" value="<?= htmlspecialchars($addr['shipping']['last_name'] ?? '') ?>" required>
          </label>
        </div>
        <div class="grid">
          <label>Country / Region *
            <input type="text" name="country_region" value="<?= htmlspecialchars($addr['shipping']['country_region'] ?? '') ?>" required>
          </label>
        </div>
        <label>Street address *
          <input type="text" name="street_address" value="<?= htmlspecialchars($addr['shipping']['street_address'] ?? '') ?>" required>
        </label>
        <label>Apartment, unit, etc. (optional)
          <input type="text" name="address_line2" value="<?= htmlspecialchars($addr['shipping']['address_line2'] ?? '') ?>">
        </label>
        <label>Town / City *
          <input type="text" name="city_town" value="<?= htmlspecialchars($addr['shipping']['city_town'] ?? '') ?>" required>
        </label>
        <label>Phone *
          <input type="tel" name="phone" value="<?= htmlspecialchars($addr['shipping']['phone'] ?? '') ?>" required>
        </label>
        <button class="btn primary" type="submit">SAVE ADDRESS</button>
      </form>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function showForm(which) {
      var bf = document.getElementById('billing-form');
      var sf = document.getElementById('shipping-form');
      bf.classList.add('hidden');
      sf.classList.add('hidden');
      if (which === 'billing') bf.classList.remove('hidden');
      if (which === 'shipping') sf.classList.remove('hidden');
      window.scrollTo({
        top: document.getElementById(which + '-form').offsetTop - 80,
        behavior: 'smooth'
      });
      return false;
    }

    // If redirected after save, lightly highlight the updated card
    (function(){
      var params = new URLSearchParams(window.location.search);
      var type = params.get('type');
      if (type === 'billing' || type === 'shipping') {
        var title = type === 'billing' ? 'BILLING ADDRESS' : 'SHIPPING ADDRESS';
        var titles = document.querySelectorAll('.card-title');
        titles.forEach(function(t){
          if (t.textContent.trim() === title) {
            var card = t.parentElement;
            card.style.boxShadow = '0 0 0 3px #A57A5B inset';
            setTimeout(function(){ card.style.boxShadow = ''; }, 1500);
          }
        });
      }
    })();
  </script>
</body>

</html>