<?php
session_start();

// Get the JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Update the session variable
if (isset($data['listId'])) {
    $_SESSION['loadedListNumber'] = intval($data['listId']);
    echo json_encode(["success" => true, "message" => $_SESSION['loadedListNumber'] ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
}
?>