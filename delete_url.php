<?php
session_start();

$id = $_POST['id'];
$name = $_POST['name'];

include_once 'sqlServerinfo.php';


    $query1 = $pdo->prepare("DELETE FROM saved_lists WHERE id=?");
    $query1->execute([$id]);
 //$query = "DELETE FROM saved_lists WHERE id=$id";
 //mysqli_query($conn, $query);

$pdo = null;
$conn->close();
header("location: showLists.php");