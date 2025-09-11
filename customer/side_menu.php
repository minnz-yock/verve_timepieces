<?php // /customer/side_menu.php ?>
<aside class="account-menu">
  <div class="user-head">
    <div class="user-avatar">
      <?= strtoupper(substr(($user['first_name'] ?: ($user['username'] ?? 'U')), 0, 1) . substr(($user['last_name'] ?: ''), 0, 1)) ?>
    </div>
    <div>
      <div class="user-name">
        <?= htmlspecialchars(trim(($user['first_name'] ?: ($user['username'] ?? 'User')) . ' ' . ($user['last_name'] ?: ''))) ?>
      </div>
      <div class="user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
    </div>
  </div>

  <nav>
    <a href="/customer/account_details.php" class="<?= ($section ?? '') === 'account-details' ? 'active' : '' ?>">Account Details</a>
    <a href="/customer/address_book.php" class="<?= ($section ?? '') === 'address-book' ? 'active' : '' ?>">Address Book</a>
    <a href="/logout.php" class="logout">Log Out</a>
  </nav>
</aside>

