<?php
session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

include_once 'sqlServerinfo.php';
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || !hash_equals($_SESSION['csrf'] ?? '', $data['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

$selectedIds = array_values(array_filter(array_map('intval', $data['selectedIds'] ?? []), fn($id) => $id > 0));
$response = ['success' => false];

if (!empty($selectedIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $query = $pdo->prepare("DELETE FROM saved_lists WHERE user_id = ? AND id IN ($placeholders)");
        $query->execute([(int)$_SESSION['user_id'], ...$selectedIds]);
        $response['success'] = true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $response['error'] = "Failed to delete entries.";
    }
}

$conn->close();
$pdo = null;

echo json_encode($response);
?>