<?php
session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    http_response_code(403);
    exit('Not logged in');
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('Invalid security token');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid list id');
}

include_once 'sqlServerinfo.php';

$query1 = $pdo->prepare("DELETE FROM saved_lists WHERE id = ? AND user_id = ?");
$query1->execute([$id, (int)$_SESSION['user_id']]);

$pdo = null;
$conn->close();
header("location: showLists.php");