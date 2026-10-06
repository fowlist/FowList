<?php
require_once "session_init.php";


// 2. Kontrollera om användaren är inloggad
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Om någon försöker anropa API:et utan att vara inloggad, neka direkt med JSON
    header('Content-Type: application/json', true, 401); // 401 = Unauthorized
    echo json_encode([
        'success' => false,
        'error' => 'Auktorisering misslyckades. Du måste vara inloggad för att använda detta API.'
    ]);
    exit;
}

header('Content-Type: application/json');

require_once 'db.php';

// Convert PHP errors/warnings to exceptions so we return JSON on failure
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Catch fatal errors on shutdown and return JSON
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . ($err['message'] ?? 'unknown')
        ]);
        exit;
    }
});

$nrOfTables =0;
$nrOfColumns =0;
/*
---------------------------------
SYNC TABLES
---------------------------------
*/
$tables = $db->query("
    SELECT TABLE_NAME
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
");

while ($table = $tables->fetch_assoc()) {
    $name   = $table['TABLE_NAME'];
    $exists = $db->query("
        SELECT id
        FROM admin_tables
        WHERE table_name = '$name'
    ");

    if ($exists->num_rows == 0) {
        $tableType = "editable";
        $nrOfTables++;

        if (str_contains($name, "_DB")) {
            $tableType = "generated";
        }
        if (str_contains($name, "Support")) {
            $tableType = "cache";
        }

        $db->query("
            INSERT INTO admin_tables (table_name, display_name, table_type, hidden)
            VALUES ('$name', '$name', '$tableType', 0)
        ");
    }
}

/*
---------------------------------
SYNC COLUMNS
---------------------------------
*/
$cols = $db->query("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE, EXTRA
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
");

while ($c = $cols->fetch_assoc()) {
    $table  = $c['TABLE_NAME'];
    $column = $c['COLUMN_NAME'];
    $nrOfColumns++;

    $exists = $db->query("
        SELECT autoid
        FROM admin_columns
        WHERE table_name = '$table' AND column_name = '$column'
    ");

    if ($exists->num_rows > 0) {
        continue;
    }

    /*
    Infer editor
    */
    $editor = "input";
    $type   = $c['DATA_TYPE'];

    if (str_contains($type, "text")) {
        $editor = "textarea";
    } elseif (str_contains($type, "int")) {
        $editor = "number";
    } elseif (str_contains($type, "tinyint")) {
        $editor = "tickCross";
    }

    /*
    Readonly auto_increment
    */
    $readonly = 0;
    if (str_contains($c['EXTRA'], "auto_increment")) {
        $readonly = 1;
    }

    /*
    Insert metadata
    */
    $is_nullable = ($c['IS_NULLABLE'] == "YES") ? 1 : 0;
    $default_val = $c['COLUMN_DEFAULT'] ? "'" . $db->real_escape_string($c['COLUMN_DEFAULT']) . "'" : "NULL";

    $db->query("
        INSERT INTO admin_columns (
            table_name, column_name, display_name, editor, visible_field,
            readonly_field, required_field, width, sort_order, formatter,
            tooltip, mysql_type, nullable_field, default_value
        )
        VALUES (
            '$table', '$column', '$column', '$editor', 1,
            $readonly, 0, 150, 999, '',
            '', '{$c['DATA_TYPE']}', $is_nullable, $default_val
        )
    ");
}

header('Content-Type: application/json');
echo json_encode([
    "success" => 1,
    "tables" => $nrOfTables,
    "columns" => $nrOfColumns
]);