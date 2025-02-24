<?php
// Define a function to log connection events
function logConnectionEvent($event) {
    $logFile = 'connection.log'; // Specify the path to your log file
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $event\n";

    // Append the log entry to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$servername = "localhost";
//$servername = "";
//$servername = "";
$phpUsername = "root";
$phpPassword = "";
$dbname = "ki12570048_Fowlist";
$userDB  = "ki12570048_users";

// Create connection
if (!isset($pdo)) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $pdo = new PDO("mysql:host=$servername;dbname=$userDB;charset=utf8mb4", $phpUsername, $phpPassword, $options);
    } catch(PDOException $e) {
        $pdo = null;
        echo "<!--". "User DB Connection failed: " . $e->getMessage() . "-->";
    }
}


if (!isset($conn)) {
    $conn = new mysqli($servername, $phpUsername, $phpPassword, $dbname);
    $conn->set_charset("utf8mb4");
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);
}

// Check connection
if ($conn->connect_error) {
    $conn->close();
    $pdo = null;
    die("List Data Connection failed: " . $conn->connect_error);
}
