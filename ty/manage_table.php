<?php

$sessionStatus = session_start();
header('Content-Type: text/html; charset=utf-8');
    // Include the database connection
    include_once 'sqlServerinfo.php'; // Make sure this file connects to the MySQL database

if (isset($_GET['table_name'])) {
    $table = $_GET['table_name'];
    $result = $conn->query("SELECT * FROM `$table`");

    echo "<form method='POST' action='update_table.php'>";
    echo "<table border='1'>";
    while ($fieldInfo = $result->fetch_field()) {
        echo "<th>" . $fieldInfo->name . "</th>";
    }
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td><input type='text' name='{$key}[]' value='{$value}'></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "<button type='submit'>Save Changes</button>";
    echo "</form>";
}
?>