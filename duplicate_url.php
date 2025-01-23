<?php
session_start();
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit;
}

// Get the ID of the entry to duplicate
$id = $_POST['id'];

include_once 'sqlServerinfo.php';

// Get the original entry details
$query = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ?");
$query->execute([$id]);
$original = $query->fetch(PDO::FETCH_ASSOC);

// Check if the entry exists
if ($original) {
    // Remove the ID before inserting it again (auto-increment will handle it)
    unset($original['id']); // This assumes `id` is an auto-increment field
    
    // Prepare the duplicate data
    $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?,?)");
    $query1->execute([$_SESSION['user_id'], $original['url'], $original['name'], $original['cost'], date("Y-m-d",time()), $original['tournament']]);

    // Redirect back to the list page after duplication
    header("Location: showLists.php");
    $pdo = null;
    $conn->close();
    exit;
} else {
    echo "Entry not found.";
    $pdo = null;
    $conn->close();
    exit;
}


?>