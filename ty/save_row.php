<?php
session_start();
header("Content-Type: application/json");

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

try {
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
        http_response_code(403);
        throw new Exception("Not logged in.");
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!is_array($data) || !isset($data['id'], $data['name'], $data['event'])) {
        throw new Exception("Invalid input: Missing required fields.");
    }

    if (!hash_equals($_SESSION['csrf'] ?? '', $data['csrf'] ?? '')) {
        http_response_code(403);
        throw new Exception("Invalid security token.");
    }

    $id = intval($data['id']);
    $name = $data['name'];
    $event = $data['event'];
    $userId = (int)$_SESSION['user_id'];

    include_once 'sqlServerinfo.php';

    $stmt = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ? AND user_id = ?");
    $success = $stmt->execute([$name, $event, $id, $userId]);

    if ($success) {
        echo json_encode(["success" => true]);
    } else {
        throw new Exception("Failed to update the database.");
    }
    $pdo = null;
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}