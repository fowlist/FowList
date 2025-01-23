<?php
session_start();

$id = $_POST['id'];
$name = $_POST['name'];
$tournament = $_POST['event'];

include_once 'sqlServerinfo.php';

if (isset($name)&&$name!= '') {
    $query = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ?");
    $query->execute([$name,$tournament,$id]);
// $query = "UPDATE saved_lists SET name='$name' WHERE id=$id";
// mysqli_query($conn, $query);
}
$conn->close();
$pdo = null;
header("location: showLists.php");