<?php
$sessionStatus = session_start();
header('Content-Type: text/html; charset=utf-8');
    // Include the database connection
    include_once 'sqlServerinfo.php'; // Make sure this file connects to the MySQL database

$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    echo "<form method='GET' action='manage_table.php'>";
    echo "<select name='table_name'>";
    while ($row = $result->fetch_array()) {
        echo "<option value='" . $row[0] . "'>" . $row[0] . "</option>";
    }
    echo "</select>";
    echo "<button type='submit'>Manage Table</button>";
    echo "</form>";
}
?>