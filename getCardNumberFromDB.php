<?php
header("Content-Type: application/json");

ob_start();
$data = json_decode(file_get_contents("php://input"), true)??[];

$book = $data['book']??false;
$cardTitle = $data['cardTitle']??false;

if ($book && $cardTitle) {
    include_once "sqlServerinfo.php";


    $cardQuery = 
    "SELECT code FROM cmdCardsText WHERE Book LIKE ? AND card LIKE ?";

try {


    $query1 = $conn->prepare($cardQuery);
    $bookParam = "%{$book}%";
    $cardParam = "%{$cardTitle}%";
    $query1->bind_param("ss", $bookParam, $cardParam);
    $query1->execute();
    $result1 = $query1->get_result();
    

    // Return the result as JSON
    echo json_encode([
        'success' => true,
        'card' => $result1->fetch_all(MYSQLI_ASSOC)
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