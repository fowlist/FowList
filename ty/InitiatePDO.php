<?php
include_once "sqlServerinfo{$beta}.php";
// Create connection
if (!isset($pdo)) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ];
        $pdo = new PDO("mysql:host=$servername;dbname=$userDB;charset=utf8mb4", $phpUsername, $phpPassword, $options);
        // Log connection establishment event
        logConnectionEvent("PDO user Connection established");
    } catch(PDOException $e) {
        $pdo = null;
        echo "<!--". "User DB Connection failed: " . $e->getMessage() . "-->";
        // Log connection error
        logConnectionEvent("PDO user Connection failed: " . $e->getMessage());
    }
}