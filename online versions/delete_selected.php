<?php
session_start();
include_once 'sqlServerinfo.php';
// Fetch the input data
$data = json_decode(file_get_contents("php://input"), true);
$selectedIds = $data['selectedIds'] ?? [];

$response = ['success' => false];

if (!empty($selectedIds)) {

    try {
        // Prepare placeholders for the query
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $query = $pdo->prepare("DELETE FROM saved_lists WHERE id IN ($placeholders)");
        $query->execute($selectedIds);
        $response['success'] = true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $response['error'] = "Failed to delete entries.";
    }
}
// Close the database connection
$conn->close();
$pdo = null;

// Return JSON response
echo json_encode($response);
?>