<?php
$hostname = "localhost"; 
$username = "root";    
$password = "";     
$dbname = "verve_timepieces";

$dsn = "mysql:host=$hostname; dbname=$dbname; charset=utf8mb4"; 

try {
    $conn = new PDO($dsn, $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 

} catch(PDOException $e) {

    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>