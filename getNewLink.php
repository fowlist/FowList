<?php
header("Content-Type: application/json");
// Start output buffering
ob_start();

include "process.php";

// Clean the buffer and prevent any output from process.php
ob_end_clean();

// After processing, build the new URL you want to redirect to
$loadedListName = isset($query["loadedListName"]) ? $query["loadedListName"] : "";

// Build the new URL with the necessary query parameters
$newUrl = "listPrintGet.php?$linkQuery&loadedListName=$loadedListName$costArrayStrig";
$updateUrl = "index.php?$linkQuery&loadedListName=$loadedListName";
$redirectUrl = "listPrintGet.php?$linkQuery&loadedListName=$loadedListName$costArrayStrig";

// Return the new URL as a plain text response

    // Return the result as JSON
    echo json_encode([

        'success' => true,
        'query' => $linkQuery,
        'url' => $newUrl,
        'updateUrl' => $updateUrl,
        'redirectUrl' => $redirectUrl
    ]);
    
exit();
?>