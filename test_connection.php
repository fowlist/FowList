<?php
$servername = "localhost";
$username = "root";
$password = "8ods398O"; // Use the correct root password
$dbname = "ki12570048_Fowlist";

// Try to connect using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to the database!";
$conn->close();
?>