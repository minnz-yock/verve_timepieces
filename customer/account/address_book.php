<?php // /customer/account/address_book.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../../dbconnect.php';
require_once '../../user_login_check.php';
if (empty($_SESSION['user_id'])) {
  header('Location: /login.php');
  exit;
}

// Load current user
$stmtUser = $conn->prepare('SELECT id, username, first_name, last_name, email FROM users WHERE id = ?');
$stmtUser->execute([(int)$_SESSION['user_id']]);
$user = $stmtUser->fetch();

// Fetch existing addresses (one per type)
$addr = ['billing' => null, 'shipping' => null];
$stmt = $conn->prepare('SELECT * FROM addresses WHERE user_id = ?');
$stmt->execute([$user['id']]);
foreach ($stmt as $r) {
  if (isset($r['address_type']) && isset($addr[$r['address_type']])) {
    $addr[$r['address_type']] = $r;
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <title>Address Book</title>
  <style>
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
      background: #111;
      color: #fff;
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
      color: #111;
      text-decoration: none;
      padding: 6px 0
    }

    .account-menu a.active {
      font-weight: 700
    }

    .account-menu a.logout {
      color: #b00020
    }

    .account-content {
      flex: 1
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
      font-weight: 600
    }

    .form input {
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 6px
    }

    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer
    }

    .btn.primary {
      background: #111;
      color: #fff;
      font-weight: 700;
      letter-spacing: .2px
    }

    .cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin: 16px 0
    }

    .card {
      background: #f8f8f8;
      border-radius: 10px
    }

    .card-title {
      font-weight: 800;
      padding: 14px 16px
    }

    .card-body {
      padding: 0 16px 16px 16px
    }

    .muted {
      color: #666
    }
  </style>
</head>

<body>
  <?php include '../navbarnew.php'; ?>
  <?php $section = 'address-book'; ?>

  <main class="account-wrapper">
    <?php include 'side_menu.php'; ?>

    <section class="account-content">
      <h2>ADDRESS BOOK</h2>
      <p class="muted">The following addresses will be used on the checkout page by default.</p>
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
              <a href="#" onclick="document.getElementById('billing-form').style.display='block';return false;">Edit</a>
            <?php else: ?>
              You have not set up this type of address yet.<br>
              <a href="#" onclick="document.getElementById('billing-form').style.display='block';return false;">Add</a>
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
              <a href="#" onclick="document.getElementById('shipping-form').style.display='block';return false;">Edit</a>
            <?php else: ?>
              You have not set up this type of address yet.<br>
              <a href="#" onclick="document.getElementById('shipping-form').style.display='block';return false;">Add</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- BILLING FORM -->
      <form id="billing-form" method="post" action="/customer/account/address_save.php" class="form" style="display:<?= $addr['billing'] ? 'none' : 'block' ?>;margin-top:24px">
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
      <form id="shipping-form" method="post" action="/customer/account/address_save.php" class="form" style="display:<?= $addr['shipping'] ? 'none' : 'block' ?>;margin-top:24px">
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
</body>

</html>
<h2>ADDRESS BOOK</h2>
<p class="muted">The following addresses will be used on the checkout page by default.</p>
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
        <a href="#" onclick="document.getElementById('billing-form').style.display='block';return false;">Edit</a>
      <?php else: ?>
        You have not set up this type of address yet.<br>
        <a href="#" onclick="document.getElementById('billing-form').style.display='block';return false;">Add</a>
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
        <a href="#" onclick="document.getElementById('shipping-form').style.display='block';return false;">Edit</a>
      <?php else: ?>
        You have not set up this type of address yet.<br>
        <a href="#" onclick="document.getElementById('shipping-form').style.display='block';return false;">Add</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- BILLING FORM -->
<form id="billing-form" method="post" action="/customer/account/address_save.php" class="form" style="display:<?= $addr['billing'] ? 'none' : 'block' ?>;margin-top:24px">
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
<form id="shipping-form" method="post" action="/customer/account/address_save.php" class="form" style="display:<?= $addr['shipping'] ? 'none' : 'block' ?>;margin-top:24px">
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