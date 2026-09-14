<?php
date_default_timezone_set('Europe/Stockholm');

$servername = 'mysql'; // Docker service name for MySQL container
$username = "root";
$password = "rootpassword";
$dbname = "GameStation"; // Database name
// Port number for MySQL
// If you are using a different port, change it accordingly
$port = 3306;               

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);
$conn->query("SET time_zone = 'Europe/Stockholm'");

// Check connxection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
}
?>