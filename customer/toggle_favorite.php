<?php
header('Content-Type: application/json');
if (!isset($_SESSION)) session_start();
require_once "../dbconnect.php";

/* read user/session identity */
$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$sid = session_id();

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'Method not allowed']); exit;
  }
  $pid = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
  if ($pid <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid product']); exit; }

  // ensure product exists
  $chk = $conn->prepare("SELECT 1 FROM products WHERE product_id=?");
  $chk->execute([$pid]);
  if (!$chk->fetchColumn()) { echo json_encode(['ok'=>false,'msg'=>'Product not found']); exit; }

  if ($uid) {
    $sel = $conn->prepare("SELECT favorite_id FROM favorites WHERE user_id=? AND product_id=?");
    $sel->execute([$uid, $pid]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $conn->prepare("DELETE FROM favorites WHERE favorite_id=?")->execute([$row['favorite_id']]);
      $status = 'removed';
    } else {
      $conn->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)")->execute([$uid, $pid]);
      $status = 'added';
    }
    $cs = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id=?");
    $cs->execute([$uid]);
    $count = (int)$cs->fetchColumn();
  } else {
    $sel = $conn->prepare("SELECT favorite_id FROM favorites WHERE session_id=? AND product_id=?");
    $sel->execute([$sid, $pid]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $conn->prepare("DELETE FROM favorites WHERE favorite_id=?")->execute([$row['favorite_id']]);
      $status = 'removed';
    } else {
      $conn->prepare("INSERT IGNORE INTO favorites (session_id, product_id) VALUES (?, ?)")->execute([$sid, $pid]);
      $status = 'added';
    }
    $cs = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE session_id=?");
    $cs->execute([$sid]);
    $count = (int)$cs->fetchColumn();
  }

  echo json_encode(['ok'=>true, 'status'=>$status, 'count'=>$count]);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['ok'=>false,'msg'=>'Server error']);
}
