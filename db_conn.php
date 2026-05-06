<?php
// db_conn.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "jvdatabase2";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>
