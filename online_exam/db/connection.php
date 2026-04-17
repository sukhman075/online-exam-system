<?php
$host = "localhost";
$user = "root";      // XAMPP default MySQL user
$pass = "";          // XAMPP default password is empty
$dbname = "online_exam";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
