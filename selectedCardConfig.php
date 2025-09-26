<?php
header("Content-Type: application/json");
function queryToItems($query, $conn, $placeholders = "") {
    $items =[];
    // Prepare and execute the second query
    if ($stmt = $conn->prepare($query)) {
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        throw new Exception("Failed to prepare " . $query);
    }
    return $items;
}
ob_start();
$data = json_decode(file_get_contents("php://input"), true)??[];

$codes = $data['code']??false;

if ($codes) {
    include_once "sqlServerinfo.php";
    include "htmlFunctions.php";
    $placeholders = "'" . implode("','", $codes) . "'";

    // Query for the platoonconfig table
    $cardQuery = 
            "   SELECT * 
                FROM cmdCardsText
                    LEFT JOIN cmdCardCost
                    ON cmdCardsText.Book = cmdCardCost.Book AND cmdCardsText.card = cmdCardCost.card
                WHERE code IN ({$placeholders})";

try {

    $cardItems = queryToItems($cardQuery, $conn);

    $html = cardConfigHTML($cardItems[0],$data['numbers']??1);

    // Return the result as JSON
    echo json_encode([
        'success' => true,
        'card' => $cardItems,
        'html' => $html
    ]);

} catch (Exception $e) {
    // Handle exceptions and errors
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
        

} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}