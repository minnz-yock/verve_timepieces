<?php // /customer/account_details.php
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
$stmt = $conn->prepare('SELECT id, username, first_name, last_name, email FROM users WHERE id = ?');
$stmt->execute([(int)$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle POST (update profile + optional password)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $cur   = $_POST['current_password'] ?? '';
  $new   = $_POST['new_password'] ?? '';
  $conf  = $_POST['confirm_password'] ?? '';

  $errors = [];
  if ($first === '') $errors[] = 'First name is required.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
  if ($new !== '' || $conf !== '') {
    if ($new === '' || $conf === '') $errors[] = 'Fill both new and confirm password.';
    if ($new !== $conf) $errors[] = 'Confirm password does not match new password.';
  }

  if ($errors) {
    $msg = implode(' ', $errors);
  } else {
    try {
      // unique email check
      $s = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
      $s->execute([$email, $user['id']]);
      if ($s->fetch()) {
        $msg = 'That email is already in use.';
      } else {
        if ($new !== '') {
          $s = $conn->prepare('SELECT password FROM users WHERE id = ?');
          $s->execute([$user['id']]);
          $row = $s->fetch();
          if (!$row || !password_verify($cur, $row['password'])) {
            $msg = 'Current password is incorrect.';
          } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]);
            $u = $conn->prepare('UPDATE users SET first_name=?, last_name=?, email=?, password=? WHERE id=?');
            $u->execute([$first, $last, $email, $hash, $user['id']]);
            $msg = 'Your account details have been updated.';
          }
        } else {
          $u = $conn->prepare('UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?');
          $u->execute([$first, $last, $email, $user['id']]);
          $msg = 'Your account details have been updated.';
        }
        // refresh user
        $stmt = $conn->prepare('SELECT id, username, first_name, last_name, email FROM users WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
      }
    } catch (Throwable $e) {
      $msg = 'Update failed. Please try again.';
    }
  }
}
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
  <title>Account Details</title>
  <style>
    body { color: #352826; }

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

    .account-menu a:hover { color: #352826 }

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
    .form input:focus { outline: 2px solid #785A49; border-color: #785A49 }

    .btn {
      padding: 10px 16px;
      border: 1px solid #352826;
      border-radius: 8px;
      cursor: pointer;
      background: #352826;
      color: #ffffff
    }

    .btn:hover { background: #785A49; border-color: #785A49 }

    .btn.primary {
      background: #352826;
      color: #ffffff;
      font-weight: 700;
      letter-spacing: .2px
    }
    .btn.primary:hover { background: #785A49 }

    .pw-toggle {
      margin-top: 8px;
      color: #A57A5B;
      cursor: pointer;
      text-decoration: underline;
      width: max-content
    }

    .pw-fields {
      display: none
    }
  </style>
</head>

<body>
  <?php include 'navbarnew.php'; ?>
  <?php $section = 'account-details'; ?>

  <main class="account-wrapper">
    <?php include 'side_menu.php'; ?>

    <section class="account-content">
      <?php if (!empty($msg)): ?>
        <div class="alert <?= (strpos($msg, 'failed') !== false || strpos($msg, 'incorrect') !== false || strpos($msg, 'already') !== false) ? 'error' : '' ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <h2>ACCOUNT DETAILS</h2>
      <form method="post" action="account_details.php" class="form" id="account-form">
        <div class="grid">
          <label>First name *
            <input type="text" name="first_name" value="<?= htmlspecialchars(($user['first_name'] ?: ($user['username'] ?? ''))) ?>" required>
          </label>
          <label>Last name
            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
          </label>
        </div>

        <label>Email address *
          <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </label>

        <div class="pw-toggle" id="pw-toggle">Change password</div>
        <div class="pw-fields" id="pw-fields">
          <div class="grid">
            <label>Current password
              <input type="password" name="current_password" autocomplete="current-password">
            </label>
            <label>New password
              <input type="password" name="new_password" autocomplete="new-password">
            </label>
          </div>
          <label>Confirm new password
            <input type="password" name="confirm_password" autocomplete="new-password">
          </label>
        </div>

        <button type="submit" class="btn primary">SAVE CHANGES</button>
      </form>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('pw-toggle').addEventListener('click', function() {
      var el = document.getElementById('pw-fields');
      el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    });
  </script>
</body>

</html>