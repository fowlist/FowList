<?php
session_start(); 

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || !hash_equals($_SESSION['csrf'] ?? '', $data['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $data['ids'] ?? []), fn($id) => $id > 0));
$response = ['success' => false, 'duplicates' => []];

include "showListsFunctions.php";
include_once 'sqlServerinfo.php';

$newEntrys = [];

foreach ($ids as $id) {
    $query = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ? AND user_id = ?");
    $query->execute([$id, (int)$_SESSION['user_id']]);
    $original = $query->fetch(PDO::FETCH_ASSOC);

    if ($original) {
        unset($original['id']);
        $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?,?)");
        $query1->execute([$_SESSION['user_id'], $original['url'], $original['name'], $original['cost'], date("Y-m-d",time()), $original['tournament']]);
        $newId = $pdo->lastInsertId();

        if ($newId) {
            $query2 = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ?");
            $query2->execute([$newId]);
            $newEntry = $query2->fetch(PDO::FETCH_ASSOC);
            $newEntrys[] = $newEntry;
        }
    }
}
$listArray = generateListArray($newEntrys, $conn);
$rowHtml = generateShowListRows($listArray);
$response['success'] = true;
$response['duplicates'] = $rowHtml;
$pdo = null;
$conn->close();
echo json_encode($response);
?>