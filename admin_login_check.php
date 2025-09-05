<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signinform.php"); // was: "signinform.php"
    exit();
}
?>