<?php
session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    echo "You are not logged in.";
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo "Invalid security token.";
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "Invalid list id.";
    exit;
}

include_once 'sqlServerinfo.php';

$query = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ? AND user_id = ?");
$query->execute([$id, (int)$_SESSION['user_id']]);
$original = $query->fetch(PDO::FETCH_ASSOC);

if ($original) {
    unset($original['id']);
    $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?,?)");
    $query1->execute([$_SESSION['user_id'], $original['url'], $original['name'], $original['cost'], date("Y-m-d",time()), $original['tournament']]);

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