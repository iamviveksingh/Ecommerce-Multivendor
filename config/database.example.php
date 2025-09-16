<?php
// Copy this file to database.php and fill with your credentials
$host = 'localhost';
$username = 'your_db_user';
$password = 'your_db_password';
$database = 'ecommerce_multivendor';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>

