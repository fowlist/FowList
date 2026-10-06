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
$name = trim($_POST['name'] ?? '');
$tournament = $_POST['event'] ?? '';

if ($id <= 0 || $name === '') {
    http_response_code(400);
    exit('Invalid input');
}

include_once 'sqlServerinfo.php';

$query = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ? AND user_id = ?");
$query->execute([$name, $tournament, $id, (int)$_SESSION['user_id']]);

$conn->close();
$pdo = null;
header("location: showLists.php");