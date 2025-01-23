<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data['updates'])) {

        include_once 'sqlServerinfo.php';

        foreach ($data['updates'] as $update) {
            $stmt = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ?");
            $stmt->execute([$update['name'], $update['event'], $update['id']]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
    }
}
$conn->close();
$pdo = null;
?>