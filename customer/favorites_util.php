
<?php
// if (!isset($_SESSION)) session_start();
// require_once "../dbconnect.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dbconnect.php'; 

/**
 * Return the logged-in user id from your auth.
 * If your login stores it at a different key, change this.
 */
function fav_current_user_id(): ?int {
  return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
function fav_current_session_id(): string { return session_id(); }

/**
 * Call this right AFTER a successful login to merge any guest (session) favorites
 * into the user's account once, then clear session rows.
 */
function fav_merge_session_into_user(PDO $conn): void {
  $uid = fav_current_user_id();
  if (!$uid) return;
  $sid = fav_current_session_id();

  // copy session -> user (ignore dupes)
  $sql = "INSERT IGNORE INTO favorites (user_id, product_id, created_at)
          SELECT ?, product_id, NOW() FROM favorites WHERE session_id = ?";
  $st = $conn->prepare($sql);
  $st->execute([$uid, $sid]);

  // clear session rows
  $conn->prepare("DELETE FROM favorites WHERE session_id = ?")->execute([$sid]);
}

/** Is product already favorited by this identity? */
function fav_is_favorited(PDO $conn, int $productId): bool {
  $uid = fav_current_user_id();
  if ($uid) {
    $st = $conn->prepare("SELECT 1 FROM favorites WHERE user_id=? AND product_id=? LIMIT 1");
    $st->execute([$uid, $productId]);
  } else {
    $sid = fav_current_session_id();
    $st = $conn->prepare("SELECT 1 FROM favorites WHERE session_id=? AND product_id=? LIMIT 1");
    $st->execute([$sid, $productId]);
  }
  return (bool)$st->fetchColumn();
}

/** Return all favorited product ids for this identity */
function fav_get_ids(PDO $conn): array {
  $uid = fav_current_user_id();
  if ($uid) {
    $st = $conn->prepare("SELECT product_id FROM favorites WHERE user_id=?");
    $st->execute([$uid]);
  } else {
    $sid = fav_current_session_id();
    $st = $conn->prepare("SELECT product_id FROM favorites WHERE session_id=?");
    $st->execute([$sid]);
  }
  return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

/** Count favorites for this identity (used by navbar badge) */
function fav_count(PDO $conn): int {
  $uid = fav_current_user_id();
  if ($uid) {
    $st = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id=?");
    $st->execute([$uid]);
  } else {
    $sid = fav_current_session_id();
    $st = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE session_id=?");
    $st->execute([$sid]);
  }
  return (int)$st->fetchColumn();
}

/* Back-compat alias if older code used fav_get_set() */
if (!function_exists('fav_get_set')) {
  function fav_get_set(PDO $conn): array { return fav_get_ids($conn); }
}
