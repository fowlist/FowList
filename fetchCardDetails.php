<?php
header("Content-Type: application/json");

function queryToItems($query, $conn) {
    $items =[];
    // Prepare and execute the second query
    if ($stmt2 = $conn->prepare($query)) {
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $items = $result2->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    } else {
        throw new Exception("Failed to prepare " . $query);
    }
    return $items;
}

$data = json_decode(file_get_contents("php://input"), true)??[];
$codes = $data['codes']??false;

if ($codes) {
    include_once "sqlServerinfo.php";
    include "functions.php";
    $placeholders = "'" . implode("','", $codes) . "'";

    // Query for the platoonconfig table
    $cardQuery = 
            "   SELECT * 
                FROM cmdCardsText
                WHERE code IN ({$placeholders})";

try {

    $cardItems = queryToItems($cardQuery, $conn);
    ob_start();
    foreach ($cardItems as $key => $value) {
        
        ?>
        <tr>
            <td class='statsrow'>
                <span class='left'><b><?=$value["card"]?></b></span>
                <br>
                <span class='left'><?=cardNoteParse($value,false)?></span>
            </td>
        </tr>
        <?php
    }

    $html = ob_get_clean();

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