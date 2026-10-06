<?php
session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || !hash_equals($_SESSION['csrf'] ?? '', $data['csrf'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    if (!empty($data['updates'])) {
        include_once 'sqlServerinfo.php';

        foreach ($data['updates'] as $update) {
            $id = (int)($update['id'] ?? 0);
            $name = trim($update['name'] ?? '');
            $event = $update['event'] ?? '';
            if ($id <= 0 || $name === '') {
                continue;
            }
            $stmt = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $event, $id, (int)$_SESSION['user_id']]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
    }
}
$conn->close();
$pdo = null;
?>