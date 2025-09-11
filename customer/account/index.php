<?php // /customer/account/index.php
// Keep this as a simple section router with no __DIR__ includes and no external CSS file
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../dbconnect.php';
require_once '../../user_login_check.php';
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

$section = $_GET['section'] ?? 'account-details';
if ($section === 'address-book') {
  require 'address_book.php';
  exit;
}
// default to account details
require 'account_details.php';
exit;
