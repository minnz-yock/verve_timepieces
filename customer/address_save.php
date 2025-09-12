<?php // /customer/address_save.php
require_once '../dbconnect.php';
require_once '../user_login_check.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /customer/address_book.php'); exit; }

$user_id = (int)$_SESSION['user_id'];
$type = $_POST['address_type'] ?? '';
if (!in_array($type, ['billing','shipping'], true)) { header('Location: /customer/address_book.php?ok=0&msg=' . urlencode('Invalid address type.')); exit; }

$first   = trim($_POST['first_name'] ?? '');
$last    = trim($_POST['last_name'] ?? '');
$country = trim($_POST['country_region'] ?? '');
$street  = trim($_POST['street_address'] ?? '');
$line2   = trim($_POST['address_line2'] ?? '');
$city    = trim($_POST['city_town'] ?? '');
$phone   = trim($_POST['phone'] ?? '');

$required = [ ['First name',$first], ['Last name',$last], ['Country / Region',$country], ['Street address',$street], ['Town / City',$city], ['Phone',$phone] ];
foreach ($required as [$label,$val]) { if ($val==='') { header('Location: /customer/address_book.php?ok=0&msg=' . urlencode("$label is required.")); exit; } }

try {
  if ($type === 'billing') {
    $sql = 'INSERT INTO bill_address (user_id, first_name, last_name, country_region, street_address, address_line2, city_town, phone)
            VALUES (:uid, :first, :last, :country, :street, :line2, :city, :phone)
            ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), country_region=VALUES(country_region), street_address=VALUES(street_address), address_line2=VALUES(address_line2), city_town=VALUES(city_town), phone=VALUES(phone)';
  } else {
    $sql = 'INSERT INTO ship_address (user_id, first_name, last_name, country_region, street_address, address_line2, city_town, phone)
            VALUES (:uid, :first, :last, :country, :street, :line2, :city, :phone)
            ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), country_region=VALUES(country_region), street_address=VALUES(street_address), address_line2=VALUES(address_line2), city_town=VALUES(city_town), phone=VALUES(phone)';
  }
  $stmt = $conn->prepare($sql);
  $stmt->execute([
    ':uid'=>$user_id, ':first'=>$first, ':last'=>$last, ':country'=>$country, ':street'=>$street, ':line2'=>($line2!==''?$line2:null), ':city'=>$city, ':phone'=>$phone
  ]);
  header('Location: /customer/address_book.php?ok=1&type=' . urlencode($type) . '&msg=' . urlencode(ucfirst($type).' address saved.')); exit;
} catch (Throwable $e) {
  header('Location: /customer/address_book.php?ok=0&msg=' . urlencode('Could not save address.')); exit;
}
?>

