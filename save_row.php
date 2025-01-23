<?php
header("Content-Type: application/json");

try {
    // Fetch the input data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['id'], $data['name'], $data['event'])) {
        throw new Exception("Invalid input: Missing required fields.");
    }

    $id = intval($data['id']);
    $name = $data['name'];
    $event = $data['event'];

    // Include database connection
    include_once 'sqlServerinfo.php';

    // Update the row in the database
    $stmt = $pdo->prepare("UPDATE saved_lists SET name = ?, tournament = ? WHERE id = ?");
    $success = $stmt->execute([$name, $event, $id]);

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