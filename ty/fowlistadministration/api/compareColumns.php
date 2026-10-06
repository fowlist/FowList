<?php
require_once "session_init.php";


if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Content-Type: text/plain', true, 401);
    echo "Unauthorized access.";
    exit;
}

require "db.php";

$table  = $_GET['table'] ?? '';
$column = $_GET['column'] ?? '';

if (!$table || !$column) {
    header('Content-Type: text/plain', true, 400);
    echo "Missing parameters. Please provide table and column.";
    exit;
}

// 1. Slå upp din nya admin_columns-struktur för att se vad denna kolumn ska jämföras mot!
$stmt = $db->prepare("
    SELECT parent_table_compare, parent_column_compare 
    FROM admin_columns 
    WHERE table_name = ? AND column_name = ?
    LIMIT 1
");
$stmt->bind_param('ss', $table, $column);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();

if (!$meta || empty($meta['parent_table_compare']) || empty($meta['parent_column_compare'])) {
    header('Content-Type: text/plain', true, 404);
    echo "No compare metadata configured in admin_columns for {$table}.{$column}";
    exit;
}

$tgtTable = $meta['parent_table_compare'];
$tgtCol   = $meta['parent_column_compare'];

// 2. SAFETY CHECK (White-listing tables and columns against INFORMATION_SCHEMA to prevent SQL Injection)
$allowed = [];
$check = $db->query("
    SELECT TABLE_NAME, COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
");
while ($row = $check->fetch_assoc()) {
    $allowed[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
}

// Verifiera att både källan och det nya målet existerar fysiskt i MySQL
if (!isset($allowed[$table]) || !in_array($column, $allowed[$table]) ||
    !isset($allowed[$tgtTable]) || !in_array($tgtCol, $allowed[$tgtTable])) {
    header('Content-Type: text/plain', true, 403);
    echo "Invalid or safe-restricted table/column configuration detected.";
    exit;
}

// 3. KÖR DEN SMARTA MATRIS-JÄMFÖRELSEN
// Obs: Eftersom du vill hitta vad du har MISSAT att lägga till i din nuvarande tabell från källan, 
// så kastar vi om Left Antijoinen! Den letar efter värden i din TARGET-tabell (formation_db_source) 
// som INTE existerar i din aktiva SOURCE-tabell (platoonsstats_source).
$sql = "
    SELECT DISTINCT b.`{$tgtCol}` AS val
    FROM `{$tgtTable}` b
    WHERE b.`{$tgtCol}` NOT IN (SELECT a.`{$column}` FROM `{$table}` a WHERE a.`{$column}` IS NOT NULL)
      AND b.`{$tgtCol}` IS NOT NULL
      AND b.`{$tgtCol}` != ''
    ORDER BY b.`{$tgtCol}` ASC
";

$result = $db->query($sql);

header('Content-Type: text/plain; charset=utf-8');

if (!$result) {
    echo "Database error during cross-reference mapping: " . $db->error;
    exit;
}

if ($result->num_rows === 0) {
    echo "All data successfully synced! Every key in {$tgtTable}.{$tgtCol} is present in your active sheet.";
} else {
    echo "Missing records from {$tgtTable}.{$tgtCol} ({$result->num_rows} found):\n";
    echo "--------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['val'] . "\n";
    }
}
exit;