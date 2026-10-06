<?php
require_once "session_init.php";


// 2. Kontrollera om användaren är inloggad
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Om någon försöker anropa API:et utan att vara inloggad, neka direkt med JSON
    header('Content-Type: application/json', true, 401); // 401 = Unauthorized
    echo json_encode([
        'success' => false,
        'error' => 'Authorization failed. You must be logged in to use this API.'
    ]);
    exit;
}

// Tvinga PHP att visa alla eventuella interna PHP-fel på skärmen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require "db.php";

try {
    // 1. Hämta alla unika tabeller som ska byggas om
    $stmt = $db->prepare("SELECT table_name 
    FROM admin_generated_tables 
    WHERE is_primary = 1
    GROUP BY table_name
");
    if (!$stmt) {
        throw new Exception("Failed to prepare main query against admin_generated_tables: " . $db->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $rebuiltTablesLog = [];

    // 2. Loopa igenom varje genererad tabell
    while ($tableRow = $result->fetch_assoc()) {
        $table = $tableRow['table_name'];

        // Hämta ALLA union-delar för denna tabell, sorterade
        $stmtParts = $db->prepare("
            SELECT rebuild_sql, union_order, is_primary, column_overrides, raw_sql
            FROM admin_generated_tables 
            WHERE table_name = ? 
            ORDER BY union_order ASC
        ");
        $stmtParts->bind_param("s", $table);
        $stmtParts->execute();
        $parts = $stmtParts->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($parts)) continue;

        // Hämta kolumndefinitioner från admin_columns (samma som innan)
        $stmtCols = $db->prepare("
            SELECT column_name, generated_field, generated_sql 
            FROM admin_columns 
            WHERE table_name = ? 
            ORDER BY sort_order ASC
        ");
        $stmtCols->bind_param("s", $table);
        $stmtCols->execute();
        $colsResult = $stmtCols->get_result();
        $allCols = $colsResult->fetch_all(MYSQLI_ASSOC);

        if (empty($allCols)) {
            $rebuiltTablesLog[$table] = "Ignored: No columns found in admin_columns.";
            continue;
        }

        // AUTO_INCREMENT-koll (samma som innan)
        $pkCheck = $db->query("SHOW COLUMNS FROM `{$table}` WHERE Extra LIKE '%auto_increment%'");
        $autoIncrementColumn = null;
        if ($pkRow = $pkCheck->fetch_assoc()) {
            $autoIncrementColumn = $pkRow['Field'];
        }

        // Bygg en INSERT-sats per UNION-del
        $insertStatements = [];

        foreach ($parts as $part) {
            // NYTT: om raw_sql är ifylld, använd den direkt utan dynamisk byggnad
            if (!empty($part['raw_sql'])) {
                $insertStatements[] = "INSERT INTO `{$table}` " . trim($part['raw_sql']);
                continue; // hoppa över hela den dynamiska kolumn-bygglogiken
            }
            $fromClause = trim($part['rebuild_sql']);

            // Plocka ut källtabeller för just denna del
            preg_match_all('/(?:FROM|JOIN|,)\s*`?([a-zA-Z0-9_]+)`?/i', $fromClause, $matches);
            $allowedTables = $matches[1] ?? [];

            $insertColumns = [];
            $selectValues = [];
            // Parsa overrides för just denna UNION-del
            $columnOverrides = [];
            if (!empty($part['column_overrides'])) {
                $columnOverrides = json_decode($part['column_overrides'], true) ?? [];
            }

            foreach ($allCols as $col) {
                $colName = $col['column_name'];
                if ($colName === $autoIncrementColumn) continue;

                $insertColumns[] = "`{$colName}`";

                // NYTT: kolla om denna del har en explicit override för kolumnen
                if (isset($columnOverrides[$colName])) {
                    $overrideValue = $columnOverrides[$colName];
                    
                    // Kolla om override-värdet är ett "tabell.kolumn"-format som pekar på en beräknad kolumn
                    if (preg_match('/^`?([a-zA-Z0-9_]+)`?\.`?([a-zA-Z0-9_]+)`?$/', $overrideValue, $overrideMatch)) {
                        $overrideSourceTable = $overrideMatch[1];
                        $overrideColName = $overrideMatch[2];
                        
                        // Sök efter generated_sql för den angivna källtabellen och kolumnen
                        $stmtOverrideCol = $db->prepare("
                            SELECT generated_sql, generated_field 
                            FROM admin_columns 
                            WHERE table_name = ? 
                                AND column_name = ? 
                            LIMIT 1
                        ");
                        $stmtOverrideCol->bind_param("ss", $overrideSourceTable, $overrideColName);
                        $stmtOverrideCol->execute();
                        $overrideColInfo = $stmtOverrideCol->get_result()->fetch_assoc();
                        
                        if ($overrideColInfo && (bool)$overrideColInfo['generated_field'] && !empty($overrideColInfo['generated_sql'])) {
                            // Kolumnen är beräknad — använd dess formel istället för kolumnreferensen
                            $selectValues[] = "(" . $overrideColInfo['generated_sql'] . ")";
                        } else {
                            // Kolumnen är fysisk — använd override-värdet rakt av
                            $selectValues[] = $overrideValue;
                        }
                    } else {
                        // Override är ett direkt SQL-uttryck (t.ex. "NULL", "CONCAT(...)" etc.)
                        $selectValues[] = $overrideValue;
                    }
                    
                    continue;
                }

                // Resten är exakt samma som innan...
                $foundFormula = null;
                if ((bool)$col['generated_field']) {
                    $searchTables = array_merge($allowedTables, [$table]); // NYTT: lägg till måltabellen
                    $placeholders = implode(',', array_fill(0, count($searchTables), '?'));
                    $stmtSourceCol = $db->prepare("
                        SELECT generated_sql FROM admin_columns 
                        WHERE table_name IN ($placeholders) 
                            AND column_name = ? 
                            AND generated_field = 1 
                        ORDER BY FIELD(table_name, $placeholders) ASC
                        LIMIT 1
                    ");
                    
                    // Vi måste skicka med rulltrappan av parametrar två gånger till placeholders (både i WHERE och i FIELD)
                    $types = str_repeat('s', count($searchTables)) . 's' . str_repeat('s', count($searchTables));
                    $params = array_merge($searchTables, [$colName], $searchTables);
                    
                    $stmtSourceCol->bind_param($types, ...$params);
                    $stmtSourceCol->execute();
                    $sourceColInfo = $stmtSourceCol->get_result()->fetch_assoc();
                    if ($sourceColInfo && !empty($sourceColInfo['generated_sql'])) {
                        $foundFormula = $sourceColInfo['generated_sql'];
                    }
                }

                if ($foundFormula) {
                    $selectValues[] = "({$foundFormula})";
                } else {
                    $prefixedColumn = null;
                    foreach ($allowedTables as $sourceTable) {
                        $checkColStmt = $db->prepare("
                            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = ? 
                                AND COLUMN_NAME = ? 
                            LIMIT 1
                        ");
                        $checkColStmt->bind_param('ss', $sourceTable, $colName);
                        $checkColStmt->execute();
                        if ($checkColStmt->get_result()->num_rows > 0) {
                            $prefixedColumn = "`{$sourceTable}`.`{$colName}`";
                            break;
                        }
                    }
                    $selectValues[] = $prefixedColumn ?? 'NULL';
                }
            }

            $insertString = implode(', ', $insertColumns);
            $selectString = implode(', ', $selectValues);
            $insertStatements[] = "INSERT INTO `{$table}` ({$insertString}) SELECT {$selectString} {$fromClause}";
        }

        // Kör allt i EN transaktion per måltabell
        $db->begin_transaction();

        // DELETE bara EN gång, innan första INSERT
        if (!$db->query("DELETE FROM `{$table}`")) {
            $db->rollback();
            throw new Exception("[$table] DELETE failed: " . $db->error);
        }

        // Kör varje INSERT-del (en per UNION-rad)
        foreach ($insertStatements as $insertSql) {
            if (!$db->query($insertSql)) {
                $db->rollback();
                throw new Exception("[$table] INSERT failed: " . $db->error . 
                    "<br><br><strong>SQL:</strong><br><pre>" . htmlspecialchars($insertSql) . "</pre>");
            }
        }

        $db->commit();
        $rebuiltTablesLog[$table] = "Completed! (" . count($insertStatements) . " part(s) inserted)";
    }

    // Om allt lyckades för alla tabeller, skicka framgångsrapporten
    echo json_encode([
        'success' => true,
        'message' => 'All tables have been rebuilt without errors!',
        'results' => $rebuiltTablesLog
    ]);

} catch (Exception $e) {
    // Om något kraschar någonstans i loopen, fånga det direkt och skicka ett tydligt felsökningsmeddelande
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'detected_tables' => $allowedTables ?? 'No tables were able to be analyzed',
        'rebuilt_tables_log' => $rebuiltTablesLog ?? []
    ]);
}
