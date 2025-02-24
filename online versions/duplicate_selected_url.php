<?php
session_start(); 
// Fetch the input data
$data = json_decode(file_get_contents("php://input"), true);
$ids = $data['ids'] ?? [];

// Process the duplication
$response = ['success' => false, 'duplicates' => []];

include "showListsFunctions.php";
include_once 'sqlServerinfo.php';

// Iterate over each selected ID and duplicate it
$newEntrys =[];

foreach ($ids as $id) {

    // Get the original entry details
    $query = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ?");
    $query->execute([$id]);
    $original = $query->fetch(PDO::FETCH_ASSOC);

    // Check if the entry exists
    if ($original) {
        // Remove the ID before inserting it again (auto-increment will handle it)
        unset($original['id']); // This assumes `id` is an auto-increment field

        // Prepare the duplicate data
        $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?,?)");
        $query1->execute([$_SESSION['user_id'], $original['url'], $original['name'], $original['cost'], date("Y-m-d",time()), $original['tournament']]);
        $newId = $pdo->lastInsertId();

        if ($newId) {
            // Select the newly inserted row
            $query2 = $pdo->prepare("SELECT * FROM saved_lists WHERE id = ?");
            $query2->execute([$newId]);
            
            $newEntry = $query2->fetch(PDO::FETCH_ASSOC);
            $newEntrys[] = $newEntry;

            // Generate the list array and row HTML for the new entry
        }
    }
}
$listArray = generateListArray($newEntrys, $conn); // Assuming this converts DB rows to the desired format
$rowHtml = generateShowListRows($listArray); // Assuming this generates HTML for a row
// Return the generated HTML for the new row
$response['success'] = true;
$response['duplicates'] = $rowHtml;
$pdo = null;
$conn->close();
echo json_encode($response);



?>