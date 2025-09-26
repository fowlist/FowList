<?php
header("Content-Type: application/json");
include_once "sqlServerinfo.php";

$book = $_GET['book'] ?? "";

if (!$book) {
    echo json_encode(["success" => false, "error" => "Missing book"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT DISTINCT card FROM cmdCardsText WHERE Book LIKE ? ORDER BY card ASC");
    $bookParam = "%{$book}%";
    $stmt->bind_param("s", $bookParam);
    $stmt->execute();
    $result = $stmt->get_result();

    $cards = [];
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row['card'];
    }

    echo json_encode(["success" => true, "cards" => $cards]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}