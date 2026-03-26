<?php
$host = "localhost";
$dbname = "fd";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Connected successfully"; ❌ remove this

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>