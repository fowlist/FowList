<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'sqlServerinfo.php';

$username = $_SESSION['username'];
$saved_url = $_SERVER['REQUEST_URI'];
$listName = $_POST['listName'];


if (isset($_POST['save_url'])) {
    $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost) VALUES (?, ?, ?, ?)");
    $query1->execute([$userID, $saved_url, $listName, $saveCost]);
    //$query = "INSERT INTO saved_lists (user_id, url, name) VALUES ('$username', '$saved_url', '$listName')";
    //mysqli_query($conn, $query);
    echo "URL saved.";
}
$conn->close();
$pdo = null;