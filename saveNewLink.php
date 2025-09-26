<?php
session_start();
header("Content-Type: application/json");
// Start output buffering
ob_start();

include "process.php";

// Clean the buffer and prevent any output from process.php
$obOutput = ob_get_clean();

// Return the new URL as a plain text response

    // Return the result as JSON
    echo json_encode([

        'success' => true,
        'query' => $linkQuery,
        'listList' => $usersListsList,
        'obOutput' => $obOutput

    ]);
    
exit();
?>