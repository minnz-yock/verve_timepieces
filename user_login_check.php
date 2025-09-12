<?php
// session_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['first_name'])) {
    // from customer/* pages this must jump back to the project root
    header("Location: ../signinform.php"); // was: "signinform.php"
    exit();
}
?>