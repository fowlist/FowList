<?php

header('Content-Type: application/json');

// Get input data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$codes = $data['codes'];

if (!empty($codes)) {
    // Include the database connection
    include_once 'sqlServerinfo.php'; // Make sure this file connects to the MySQL database
    
    // Sanitize and prepare query placeholders
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $query = "SELECT * FROM platoonsStats WHERE code IN ({$placeholders})";
    
    // Prepare the statement
    if ($stmt = $conn->prepare($query)) {
        // Dynamically bind parameters based on the number of items in $codes array
        $types = str_repeat('s', count($codes)); // 's' stands for string (adjust if using other types)
        $stmt->bind_param($types, ...$codes); // Bind the parameters (note the use of ...)

        // Execute the query
        $stmt->execute();
        
        // Get the result
        $result = $stmt->get_result();
        
        // Fetch all the rows
        $items = $result->fetch_all(MYSQLI_ASSOC);
        
        // Return the result as JSON
        echo json_encode([
            'success' => true,
            'items' => $items,
            'cards' => "test"
        ]);
        
        // Close the statement
        $stmt->close();
    } else {
        // If the query preparation fails
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed.'
        ]);
    }

} else {
    // If no codes were provided
    echo json_encode([
        'success' => false,
        'error' => 'No codes provided.'
    ]);
}

// Close the database connection
$conn->close();
$pdo = null;