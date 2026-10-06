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

// 2. Hämta önskad databas från URL (?table=teams&db=fow_mid_war)
$targetDatabase = $_GET['db'] ?? '';

if (!$targetDatabase) {
    header('Content-Type: application/json', true, 400);
    echo json_encode(['success' => false, 'error' => 'Missing database parameter (&db=...)']);
    exit;
}

// 3. BRANDVÄGG: Kontrollera om den sökta databasen finns i användarens tillåtna lista i sessionen
if (!isset($_SESSION['allowed_databases']) || !in_array($targetDatabase, $_SESSION['allowed_databases'])) {
    header('Content-Type: application/json', true, 403); // 403 = Forbidden
    echo json_encode(['success' => false, 'error' => "Access denied. You do not have permission to access the database: '{$targetDatabase}'"]);
    exit;
}


require_once 'db.php';

// 5. SWITCHA DATABAS LIVE: Tvinga MySQLi att hoppa över till den validerade period-databasen!
if (!$db->select_db($targetDatabase)) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['success' => false, 'error' => "Failed to select database: " . $db->error]);
    exit;
}

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

$table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'data';


switch ($action) {
    case 'get_comments':
        $tableName = $_GET['target_table'] ?? '';
        $rowKey = $_GET['row_key'] ?? '';
        $columnName = $_GET['column_name'] ?? 'row';
        if (!$tableName || !$rowKey) {
            echo json_encode([]);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM admin_comments WHERE table_name = ? AND row_key = ? AND column_name = ? ORDER BY created_at ASC");
        $stmt->bind_param("sss", $tableName, $rowKey, $columnName);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) $comments[] = $row;
        header('Content-Type: application/json');
        echo json_encode($comments);
        exit;
    case 'get_all_comments':
        $tableName = $_GET['target_table'] ?? '';
        if (!$tableName) {
            echo json_encode([]);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM admin_comments WHERE table_name = ? ORDER BY created_at ASC");
        $stmt->bind_param("s", $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) $comments[] = $row;
        header('Content-Type: application/json');
        echo json_encode($comments);
        exit;

    case 'save_comment':
        $body = json_decode(file_get_contents('php://input'), true);
        $tableName  = $body['target_table']  ?? '';
        $rowKey     = $body['row_key']     ?? '';
        $columnName = $body['column_name'] ?? 'row';
        $comment    = $body['comment']     ?? '';
        $userId     = 0;
        $username   = $_SESSION['username']  ?? 'Unknown';
        if (!$tableName || !$rowKey || !$comment) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields', $body]);
            exit;
        }
    $stmt = $db->prepare("INSERT INTO admin_comments (table_name, row_key, column_name, comment, user_id, username) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE comment = VALUES(comment), username = VALUES(username), user_id = VALUES(user_id)
    ");
        $stmt->bind_param("ssssis", $tableName, $rowKey, $columnName, $comment, $userId, $username);
        $stmt->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $db->insert_id]);
        exit;

    case 'delete_comment':
        $body = json_decode(file_get_contents('php://input'), true);
        $commentId = (int)($body['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM admin_comments WHERE id = ?");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);
        exit;
    case 'upload_svg':
        upload_svg();
        break;
}



if (!$table) {

    jsonError('Missing table');
}

validateTable($db, $table);


try {
    switch ($action) {

        case 'schema':
            getSchema($db, $table);
            break;

        case 'data':
            getData($db, $table);
            break;
        case 'batch_save':
            batchSaveRows($db, $table);
            break;
        case 'save':
            saveRow($db, $table);
            break;

        case 'insert':
            insertRow($db,$table);
            break;
        case 'delete':
            deleteRow($db, $table);
            break;
        case 'update_column_width':
            updateColumnWidth($db,$table);
            break;
        case 'update_column_order':
            update_column_order($db,$table);
            break;
        case 'nextAutoId':
            createAndGetNextRow($db, $table);
            break;
        case 'dropdown_options':
            getDependentDropdownOptions($db, $table);
            break;
        case 'generators':
            getGenerators($db, $table);
            break;
        case 'rangeupdate':
            rangeUpdate($db, $table);
            break;
        case 'sync_column':
            syncColumnToDatabase($db, $table);
            break;
        default:
            jsonError('Unknown action');
    }
} catch (Throwable $e) {
    jsonError($e->getMessage());
}

function upload_svg() {
    if (!isset($_FILES['svg']) || $_FILES['svg']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Ingen fil mottagen.']);
        exit;
    }

    $file = $_FILES['svg'];
    
    // 1. Kontrollera filändelse
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'svg') {
        echo json_encode(['success' => false, 'error' => 'Bara SVG-filer tillåts.']);
        exit;
    }

    // 2. Läs filinnehållet och verifiera att det är giltig XML/SVG
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        echo json_encode(['success' => false, 'error' => 'Kunde inte läsa filen.']);
        exit;
    }

    // 3. Parsa som XML och kontrollera rotelementet
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);
    if ($xml === false) {
        echo json_encode(['success' => false, 'error' => 'Ogiltig XML/SVG-struktur.']);
        exit;
    }

    $rootName = strtolower($xml->getName());
    if ($rootName !== 'svg') {
        echo json_encode(['success' => false, 'error' => 'Filen är inte en SVG (felaktigt rootelement).']);
        exit;
    }

    // 4. Sök efter inbäddade rasterbilder — <image>-taggar och data: base64-blobs
    $xmlStr = $content;
    $forbiddenPatterns = [
        '/<image\s/i',                          // <image ...> element
        '/xlink:href\s*=\s*["\']data:/i',       // inline base64 via xlink
        '/href\s*=\s*["\']data:image/i',        // inline base64 via href
        '/data:image\/(png|jpe?g|gif|webp|bmp)/i', // rasterformat i data-URI
    ];
    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match($pattern, $xmlStr)) {
            echo json_encode(['success' => false, 'error' => 'SVG-filen innehåller inbäddade rasterbilder och är inte ren vektorgrafik.']);
            exit;
        }
    }

    // 5. Filnamnet skickas från JS (redan sanerat)
    $filename = $_POST['filename'] ?? '';
    if (!$filename || !preg_match('/^[a-zA-Z0-9_\-]+$/', $filename)) {
        echo json_encode(['success' => false, 'error' => 'Ogiltigt eller saknat filnamn.']);
        exit;
    }

    // 6. Spara till ../img/
    $targetDir = realpath(__DIR__ . '/../../img/');
    if (!$targetDir || !is_dir($targetDir)) {
        echo json_encode(['success' => false, 'error' => 'Målmappen ../img/ hittades inte.']);
        exit;
    }
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename . '.svg';

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'error' => 'Kunde inte spara filen.']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'filename' => $filename . '.svg']);
    exit;
}



function validateTable($db, $table)
{
    $stmt = $db->prepare("
        SELECT table_name
        FROM admin_tables
        WHERE table_name=?
    ");

    $stmt->bind_param('s', $table);

    $stmt->execute();

    $result = $stmt->get_result();

    if (!$result->fetch_assoc()) {

        jsonError('Invalid table');
    }
}
function syncColumnToDatabase($db, $table)
{
    $columnName = $_GET['column'] ?? '';

    if (!$columnName) {
        echo json_encode(['success' => false, 'error' => 'Saknar kolumnnamn']);
        exit;
    }

    // 1. Hämta radens inställningar från admin_columns för denna specifika kolumn
    $stmt = $db->prepare("
        SELECT mysql_type, nullable_field, default_value, generated_field
        FROM admin_columns
        WHERE table_name = ? AND column_name = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $table, $columnName);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc();

    if (!$meta) {
        echo json_encode(['success' => false, 'error' => "Hittade inte kolumnen '$columnName' i admin_columns"]);
        exit;
    }

    if ((bool)$meta['generated_field']) {
        echo json_encode(['success' => false, 'error' => 'Kolumnen är virtuell (genererad) och ska inte skapas i MySQL']);
        exit;
    }

    // 2. Kontrollera om kolumnen redan finns i den fysiska tabellen
    $checkPhysical = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$columnName}'");
    $columnExistsPhysically = ($checkPhysical->num_rows > 0);

    // 3. Bestäm MySQL-datatyp (t.ex. INT, VARCHAR(255) etc.)
    $mysqlType = !empty($meta['mysql_type']) ? trim($meta['mysql_type']) : 'VARCHAR(255)';
    
    // Hantera NULL / NOT NULL
    $nullDefinition = ((bool)$meta['nullable_field']) ? '' : 'NOT NULL';

    // Hantera DEFAULT-värde på ett felsäkert sätt
    $defaultDefinition = '';
    if ($meta['default_value'] !== null && strtolower($meta['default_value']) !== 'null') {
        $trimmedDefault = trim($meta['default_value']);
        
        // SÄKERHET: Om det är en tom sträng (antingen faktiskt tom eller texten '')
        if ($trimmedDefault === '' || $trimmedDefault === "''" || $trimmedDefault === '""') {
            $defaultDefinition = ''; // Sätt ett rent tomt MySQL-standardvärde
        } else {
            // För vanliga textvärden, escape:a strängen ordentligt
            $defaultDefinition = 'DEFAULT "' . $db->real_escape_string($trimmedDefault) . '"';
        }
    }

    // Dynamiska ord för framgångsmeddelandet
    $actionWord = $columnExistsPhysically ? "uppdaterats" : "skapats";
    $keyNote = "";

    // --- SPECIALHANTERING FÖR BYTING AV PRIMARY KEY TILL AUTO_INCREMENT ---
    if (strpos(strtolower($mysqlType), 'auto_increment') !== false) {
        $nullDefinition = 'NOT NULL'; 

        // 1. Kontrollera om tabellen har en befintlig primärnyckel
        $pkCheck = $db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        
        if ($pkCheck->num_rows > 0) {
            $pkRow = $pkCheck->fetch_assoc();
            $oldPkColumn = $pkRow['Column_name']; 

            // Om den gamla primärnyckeln INTE är samma kolumn som vi vill skapa nu
            if ($oldPkColumn !== $columnName) {
                // A. Ta bort den gamla primärnyckel-statusen från tabellen
                $db->query("ALTER TABLE `{$table}` DROP PRIMARY KEY");
                
                // B. Lägg till ett UNIQUE-index på den gamla nyckeln så att inga dubbletter skapas
                $db->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `unique_{$oldPkColumn}` (`{$oldPkColumn}`)");
            }
        }

        // 2. Rensa bort 'auto_increment' ordet så vi kan bygga SQL:en rent
        $cleanMysqlType = str_ireplace('auto_increment', '', $mysqlType);
        $cleanMysqlType = trim($cleanMysqlType); 

        // 3. Eftersom fältet ska bli AUTO_INCREMENT sätter vi den definitionen direkt
        $defaultDefinition = "AUTO_INCREMENT PRIMARY KEY";
        $mysqlType = $cleanMysqlType;
        $keyNote = " till att vara ny PRIMARY KEY";
    }

    // 4. Bygg ombyggnadsfrågan (ALTER TABLE) - Nu helt felsäker!
    if ($columnExistsPhysically) {
        $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$columnName}` {$mysqlType} {$nullDefinition} {$defaultDefinition}";
    } else {
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$columnName}` {$mysqlType} {$nullDefinition} {$defaultDefinition}";
    }

    // 5. Kör frågan mot MySQL
    if ($db->query($sql)) {
        echo json_encode([
            'success' => true,
            'message' => "Kolumnen '{$columnName}' har nu {$actionWord}{$keyNote} i tabellen '{$table}'!"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => "MySQL-fel vid synkronisering: " . $db->error . " (Körde: $sql)"
        ]);
    }
    exit;
}

function getPrimaryKey($db, $table)
{
    $result = $db->query("
        SHOW KEYS
        FROM {$table}
        WHERE Key_name = 'PRIMARY'
    ");

    $row = $result->fetch_assoc();

    return $row['Column_name'] ?? 'id';
}

function updateColumnWidth($db,$table) {

    // 1. Tvinga PHP att läsa rå JSON-data från JavaScript-anropet
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    // 2. Hämta värdena från JSON-objektet
    $tableName  = $table;
    $columnName = $data['column_name'] ?? null;
    $width      = isset($data['width']) ? (int)$data['width'] : null;

    // Felsökning: Om du vill se i din PHP-error-log exakt vad servern tog emot:
    // error_log("Mottaget i PHP: table=" . $tableName . ", col=" . $columnName . ", width=" . $width);

    // 3. Kontrollera att tabell och kolumn faktiskt skickades med

    if (!$tableName || !$columnName || $width === null) {
        echo json_encode([
            'success' => false, 
            'error' => 'Missing table, column or width data in backend'
        ]);
        exit;
    }

    // 4. Kör SQL-uppdateringen mot din admin_columns-tabell
    $stmt = $db->prepare("
        UPDATE admin_columns 
        SET width = ? 
        WHERE table_name = ? AND column_name = ?
    ");
    
    $stmt->bind_param('iss', $width, $tableName, $columnName);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
function update_column_order($db,$table) {

    $data = json_decode(file_get_contents('php://input'), true);
    

    $orders    = $data['orders'] ?? null; // Detta är vår array med kolumner och index

    if ($table && is_array($orders)) {
        $success = true;

        // Starta en loop för att uppdatera varje kolumns sort_order en efter en
        foreach ($orders as $item) {
            $columnName = $item['column_name'] ?? null;
            $sortOrder  = isset($item['sort_order']) ? (int)$item['sort_order'] : null;

            if ($columnName && $sortOrder !== null) {
                $stmt = $db->prepare("
                    UPDATE admin_columns 
                    SET sort_order = ? 
                    WHERE table_name = ? AND column_name = ?
                ");
                $stmt->bind_param('iss', $sortOrder, $table, $columnName);
                
                if (!$stmt->execute()) {
                    $success = false;
                }
            }
        }

        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Saknar parametrar eller ogiltig data']);
    }
    exit;
}


function getSchema($db,$table)
{

    $stmt=$db->prepare("
    SELECT *
    FROM admin_columns
    WHERE table_name=?
    ORDER BY sort_order
    ");

    $stmt->bind_param("s",$table);
    $stmt->execute();
    $result=$stmt->get_result();
    $columns=[];

while($row=$result->fetch_assoc()){
    
        // Sätt till skrivskyddat om det är ett genererat fält
        $isGenerated = (bool)$row['generated_field'];
        $isReadOnly = $row['readonly_field'] || $isGenerated;

        $col=[
            'title'=>$row['display_name'],
            'field'=>$row['column_name'],
            'editor'=>$row['editor'], // Ingen editor om beräknad
            'width'=>$row['width'],
            'readonly'=>$isReadOnly,
            'visible'=> (bool)$row['visible_field'],
            'parent_column' => $row['parent_column'],
            'separator_char' => $row['separator_char'],
            'parent_table_compare' => $row['parent_table_compare'],
            'parent_column_compare' => $row['parent_column_compare'],
            'conditional_formatting' => $row['conditional_formatting'],
            'relation_parent_column' => $row['relation_parent_column']
        ];

    /*
    RELATION DROPDOWN
    */
    if(
        $row['editor']=="list"
        &&
        $row['relation_table']
    ){

        $values=[];
        $relation=$db->query(" SELECT DISTINCT
            {$row['relation_value_column']} value
        FROM {$row['relation_table']}
        ORDER BY
            {$row['relation_value_column']} 
        ");

        while(
            $r=$relation->fetch_assoc()
        ){
            $values[]=$r['value'];
        }

        $lookup=[];
        foreach($values as $v){
            $lookup[$v]=$v;
        }
        $col['editor']='list';
        $col['values']=$values;
        $col['multiple'] =
            (bool)$row['multiple_select'];

        $col['separator'] =
            $row['separator_char'] ?: '|';

        $col['lookup']=$lookup;
    }

    $columns[]=$col;

    }

    echo json_encode([
        'columns'=>$columns,
        'primaryKey'=>getPrimaryKey(
            $db,
            $table
        )
    ]);
}

function getData($db, $table)
{
    $primaryKey = getPrimaryKey($db, $table);

    
    if ($table === 'admin_tables') {
        $userId = $_SESSION['user_id'] ?? null;
        $isAdmin = in_array($userId, [5]); // justera vilka user_id som är admins

        if ($isAdmin) {
            $result = $db->query("SELECT * FROM admin_tables ORDER BY default_sort ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM admin_tables WHERE access = 1 ORDER BY default_sort ASC");
            $stmt->execute();
            $result = $stmt->get_result();
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($rows);
        exit;
    }

    // 1. Hämta alla eventuella beräknade kolumner för denna tabell
    $stmt = $db->prepare("
        SELECT column_name, generated_sql 
        FROM admin_columns 
        WHERE table_name = ? AND generated_field = 1 AND generated_sql IS NOT NULL AND generated_sql != ''
    ");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $genResult = $stmt->get_result();

    $selectFields = ["{$table}.*"]; // Starta med att välja alla vanliga kolumner

    // 2. Loopa igenom och bygg dina beräknade SQL-fält
    while ($genRow = $genResult->fetch_assoc()) {
        // Exempel blir: "(pris * 1.25) AS moms_kolumn"
        $selectFields[] = "({$genRow['generated_sql']}) AS `{$genRow['column_name']}`";
    }

    // Slå ihop alla fält till en sträng avskild med kommatecken
    $selectString = implode(', ', $selectFields);

    // 3. Bygg den slutgiltiga SQL-frågan med de dynamiska beräkningarna
    $sql = "
        SELECT {$selectString}
        FROM {$table}
        ORDER BY {$table}.{$primaryKey} DESC
        LIMIT 10000
    ";

    $result = $db->query($sql);
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode($rows);
}

function createAndGetNextRow($db, $table)
{
    // 1. Hitta alla kolumner som är NOT NULL och saknar DEFAULT-värde eller AUTO_INCREMENT
    $schemaSql = "SELECT COLUMN_NAME, DATA_TYPE 
                  FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND IS_NULLABLE = 'NO' 
                    AND COLUMN_DEFAULT IS NULL 
                    AND EXTRA NOT LIKE '%auto_increment%'";

    $stmt = $db->prepare($schemaSql);
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $result = $stmt->get_result();

    $columns = [];
    $values = [];
    $types = "";
    $bindParams = [];

    // 2. Skapa smarta dummy-värden baserat på kolumnens datatyp
    while ($row = $result->fetch_assoc()) {
        $columns[] = "`" . $row['COLUMN_NAME'] . "`";
        $values[] = "?";
        
        // Matcha datatyper för att binda rätt i Prepared Statement
        if (in_array($row['DATA_TYPE'], ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'integer'])) {
            $types .= "i";
            $bindParams[] = 0; // Standardvärde för heltal
        } elseif (in_array($row['DATA_TYPE'], ['float', 'double', 'decimal'])) {
            $types .= "d";
            $bindParams[] = 0.0; // Standardvärde för decimaltal
        } else {
            $types .= "s";
            $bindParams[] = ""; // Standardvärde för strängar/text/datum
        }
    }

    // 3. Bygg SQL-frågan dynamiskt
    if (!empty($columns)) {
        $sql = "INSERT INTO `{$table}` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
        $insertStmt = $db->prepare($sql);
        
        // Bind parametrarna dynamiskt (kräver call_user_func_array i äldre PHP, men enkelt i PHP 8.1+)
        $insertStmt->bind_param($types, ...$bindParams);
        $insertStmt->execute();
        $nextId = $insertStmt->insert_id;
    } else {
        // Om inga kolumner krävde dummy-värden, kör en vanlig tom INSERT
        $sql = "INSERT INTO `{$table}` () VALUES ()";
        $db->query($sql);
        $nextId = $db->insert_id;
    }

    // 4. Skicka tillbaka ID på den nya raden
    if ($nextId > 0) {
        echo json_encode([
            'success' => true,
            'autoId' => $nextId,
            'debug_source' => 'PHP createAndGetNextRow() function'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Kunde inte skapa raden: ' . $db->error
        ]);
    }
}
function saveRow($db, $table)
{
    $primaryKey = getPrimaryKey($db, $table);

    // Hämta data EN gång här och skicka med till funktionerna
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Ingen data mottogs']);
        return;
    }
    // --- NYTT: FILTRERA BORT UTRÄKNADE / VIRTUELLA KOLUMNER ---
    // Hämta alla giltiga kolumnnamn från databasen för denna tabell
    $validColumns = [];
    $colsRes = $db->query("SHOW COLUMNS FROM `{$table}`");
    while ($col = $colsRes->fetch_assoc()) {
        $validColumns[] = $col['Field'];
    }

    // Gå igenom inskickad data och ta bort allt som inte matchar en riktig kolumn
    foreach ($data as $key => $value) {
        if (!in_array($key, $validColumns)) {
            unset($data[$key]); // Ta bort uträknade kolumner, knappar eller annat frontend-skräp
        }
    }
    // ---------------------------------------------------------

    $id = $data[$primaryKey] ?? null;
    $rowExists = false;

    // Kontrollera om raden finns (och att ID faktiskt är giltigt/inte tomt)
    if ($id !== null && $id !== '' && $id !== 0) {
        $sql = "SELECT 1 FROM `{$table}` WHERE `{$primaryKey}` = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $rowExists = true;
        }
        $stmt->close();
    }   

    if ($rowExists) {
        updateRow($db, $table, $primaryKey, $id, $data);
    } else {
        insertRow($db, $table);
    }
}

function updateRow($db, $table, $primaryKey, $id, $data) {
    // Ta bort primärnyckeln från datat som ska uppdateras (man uppdaterar inte ID)
    unset($data[$primaryKey]);

    if (empty($data)) {
        echo json_encode(['success' => true, 'message' => 'Ingen data att uppdatera']);
        return;
    }

        // 1. Fetch column types from the database to know which columns are integers/numbers
    $columnTypes = [];
    $colsRes = $db->query("SHOW COLUMNS FROM `{$table}`");
    while ($col = $colsRes->fetch_assoc()) {
        $field = $col['Field'];
        $type = strtolower($col['Type']);
        
        // Store true if it's an integer, decimal, float etc.
        $columnTypes[$field] = (bool)preg_match('/(int|decimal|float|double|number|tinyint)/', $type);

        // Fix NOT NULL columns without default values (Your existing logic)
        if ($field !== $primaryKey && !array_key_exists($field, $data) && strtoupper($col['Null']) === 'NO' && ($col['Default'] === null || strtoupper($col['Default']) === 'NULL')) {
            $data[$field] = $columnTypes[$field] ? 0 : '';
        }
    }

    $fields = [];
    $types = '';
    $values = [];

    foreach ($data as $field => $value) {
        $fields[] = "`{$field}`=?"; // Använd backticks runt kolumnnamn för säkerhet
        $types .= 's';
        if (isset($columnTypes[$field]) && $columnTypes[$field] === true) {
        if ($value === '' || $value === null) {
            $data[$field] = 0; // Prevent "Incorrect integer value" crash
        }
    }
        if ($value === false) {
            $values[] = 0;
        } elseif (is_array($value)) {
            // Om frontend skickar datat som en array (händer i vissa Jspreadsheet Pro-konfigurationer)
            // Gör om arrayen till en semikolon-separerad sträng innan den sparas i databasen
            $values[] = implode(';', $value); 
        } else {
            $values[] = $value; // Sparar den färdiga strängen "Val1;Val2" direkt
        }
    }

    $types .= 's';
    $values[] = $id;

    $sql = "UPDATE `{$table}` SET " . implode(',', $fields) . " WHERE `{$primaryKey}`=?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) {
        // --- NEW: RECALCULATE GENERATED COLUMNS FOR THIS ROW ---
        $recalculatedFields = [];
        
        // Fetch all generated columns and formulas for this specific table
        $genColsStmt = $db->prepare("
            SELECT column_name, generated_sql 
            FROM admin_columns 
            WHERE table_name = ? AND generated_field = 1 AND generated_sql IS NOT NULL AND generated_sql != ''
        ");
        $genColsStmt->bind_param('s', $table);
        $genColsStmt->execute();
        $genColsRes = $genColsStmt->get_result();

        while ($genCol = $genColsRes->fetch_assoc()) {
            $colName = $genCol['column_name'];
            $formula = html_entity_decode($genCol['generated_sql'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Run the formula targeted strictly to this specific row's primary key ID
            // Example: SELECT (CONCAT(...)) as val FROM teams WHERE team = 'Alpha'
            $calcSql = "SELECT ({$formula}) as val FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1";
            $calcStmt = $db->prepare($calcSql);
            $calcStmt->bind_param('s', $id);
            
            if ($calcStmt->execute()) {
                $calcRes = $calcStmt->get_result()->fetch_assoc();
                // Store the fresh value (fallback to empty string if null)
                $recalculatedFields[$colName] = $calcRes['val'] ?? '';
            }
            $calcStmt->close();
        }
        $genColsStmt->close();

        // Return success along with the newly computed live values!
        echo json_encode([
            'success' => true,
            'message' => "Row with ID {$id} updated",
            'debug_source' => 'PHP insertRow() function',
            'recalculated' => $recalculatedFields
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    exit;

}
function insertRow($db, $table) {
    $primaryKey = getPrimaryKey($db, $table);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        return;
    }

    // Check if primary key is auto_increment
    $pkCheck = $db->query("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$primaryKey}'");
    $pkInfo = $pkCheck->fetch_assoc();
    $isAutoIncrement = $pkInfo && strpos(strtolower($pkInfo['Extra']), 'auto_increment') !== false;

    if ($isAutoIncrement) {
        unset($data[$primaryKey]);
    }

    // 1. Fetch column types from the database to know which columns are integers/numbers
    $columnTypes = [];
    $colsRes = $db->query("SHOW COLUMNS FROM `{$table}`");
    while ($col = $colsRes->fetch_assoc()) {
        $field = $col['Field'];
        $type = strtolower($col['Type']);
        
        // Store true if it's an integer, decimal, float etc.
        $columnTypes[$field] = (bool)preg_match('/(int|decimal|float|double|number|tinyint)/', $type);

        // Fix NOT NULL columns without default values (Your existing logic)
        if ($field !== $primaryKey && !array_key_exists($field, $data) && strtoupper($col['Null']) === 'NO' && ($col['Default'] === null || strtoupper($col['Default']) === 'NULL')) {
            $data[$field] = $columnTypes[$field] ? 0 : '';
        }
    }

    if (count($data) === 0 && $isAutoIncrement) {
        $sql = "INSERT INTO `{$table}` () VALUES ()";
        $db->query($sql);
        $newId = $db->insert_id;
    } else {
        // --- FIX FOR INCORRECT INTEGER VALUE ---
        // 2. Loop through the data we are about to insert. 
        // If the column is a number type and Jspreadsheet sent an empty string, change it to 0!
        foreach ($data as $field => $value) {
            if (isset($columnTypes[$field]) && $columnTypes[$field] === true) {
                if ($value === '' || $value === null) {
                    $data[$field] = 0; // Converts empty cells to a valid 0 for MySQL
                }
            }
        }

        // Build dynamic INSERT (Your existing logic)
        $columns = array_map(function($col) { return "`{$col}`"; }, array_keys($data));
        $placeholders = array_fill(0, count($data), '?');
        $types = str_repeat('s', count($data));
        $values = array_values($data);

        foreach ($values as $key => $val) {
            if ($val === false) $values[$key] = 0;
            if ($val === true) $values[$key] = 1;
        }

        $sql = "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        if ($isAutoIncrement) {
            $newId = $stmt->insert_id;
        } else {
            $newId = $data[$primaryKey] ?? 0;
        }
    }

    echo json_encode([
        'success' => true,
        'autoId' => $newId,
        'message' => 'Row inserted successfully',
        'debug_source' => 'PHP insertRow() function'

    ]);
}

function batchSaveRows($db, $table) {
    $primaryKey = getPrimaryKey($db, $table);
    $input = json_decode(file_get_contents('php://input'), true);
    $rader = $input['rader'] ?? [];

    if (empty($rader)) {
        echo json_encode([
            'success' => true, 
            'message' => 'No rows to save',
            'debug_source' => 'PHP batchSaveRows() function'
        ]);
        exit;
    }

    // 1. Fetch valid physical columns AND detect their structural database data types
    $validColumns = [];
    $columnTypes = []; // Keeps track of numeric columns
    
    $colsRes = $db->query("SHOW COLUMNS FROM `{$table}`");
    while ($col = $colsRes->fetch_assoc()) {
        $field = $col['Field'];
        $validColumns[] = $field;
        
        // Track if this column type is an integer, decimal, float, or tinyint
        $type = strtolower($col['Type']);
        $columnTypes[$field] = (bool)preg_match('/(int|decimal|float|double|number|tinyint)/', $type);
    }

    $db->begin_transaction();
    try {
        // 2. Loop through each submitted row payload
        foreach ($rader as $data) {
            
            // 3. Clean out virtual fields AND convert empty numeric cells to 0
            foreach ($data as $key => $value) {
                if (!in_array($key, $validColumns)) {
                    unset($data[$key]); // Remove frontend garbage columns
                    continue;
                }

                // --- CRITICAL NUMERIC FIX FOR BATCH ACTIONS ---
                // If this column is a number in MySQL but Jspreadsheet sent an empty string ""
                if (isset($columnTypes[$key]) && $columnTypes[$key] === true) {
                    if ($value === '' || $value === null) {
                        $data[$key] = 0; // Enforce a valid zero to prevent MySQL Strict mode crash
                    }
                }
            }
            // Hämta JSON-kolumner dynamiskt så det fungerar för alla tabeller automatiskt
            static $jsonColsCache = []; // cache per tabell så vi inte kör samma query för varje rad
            if (!isset($jsonColsCache[$table])) {
                $jsonColsResult = $db->query("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = '{$table}' 
                        AND DATA_TYPE = 'json'
                ");
                $jsonColsCache[$table] = [];
                while ($r = $jsonColsResult->fetch_assoc()) {
                    $jsonColsCache[$table][] = $r['COLUMN_NAME'];
                }
            }
            foreach ($jsonColsCache[$table] as $jsonCol) {
                if (isset($data[$jsonCol]) && trim((string)$data[$jsonCol]) === '') {
                    $data[$jsonCol] = null;
                }
            }
            $id = $data[$primaryKey] ?? null;
            $rowExists = false;

            // 4. Check if the row exists
            if ($id !== null && $id !== '' && $id !== 0 && !str_starts_with((string)$id, 'new-')) {
                $sql = "SELECT 1 FROM `{$table}` WHERE `{$primaryKey}` = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('s', $id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $rowExists = true;
                }
                $stmt->close();
            }

            // 5. Execute processing pipeline
            if ($rowExists) {
                // Build dynamic UPDATE for this row
                $updateFields = [];
                $types = '';
                $values = [];

                foreach ($data as $key => $val) {
                    if ($val === false) $val = 0;
                    if ($val === true) $val = 1;
                    $updateFields[] = "`{$key}` = ?";
                    $types .= 's';
                    $values[] = $val;
                }
                $types .= 's';
                $values[] = $id;

                $sql = "UPDATE `{$table}` SET " . implode(', ', $updateFields) . " WHERE `{$primaryKey}` = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();
            } else {
                // If the row is new, execute insert processing within the batch
                $columns = array_map(function($col) { return "`{$col}`"; }, array_keys($data));
                $placeholders = array_fill(0, count($data), '?');
                $types = str_repeat('s', count($data));
                $values = array_values($data);

                foreach ($values as $key => $val) {
                    if ($val === false) $values[$key] = 0;
                    if ($val === true) $values[$key] = 1;
                }

                $sql = "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();
            }
        }

        // --- NEW: RECALCULATE GENERATED COLUMNS FOR ALL BATCH ROWS ---
        $batchRecalculated = [];

        // 1. Fetch all generated columns and formulas for this specific table
        $genColsStmt = $db->prepare("
            SELECT column_name, generated_sql 
            FROM admin_columns 
            WHERE table_name = ? AND generated_field = 1 AND generated_sql IS NOT NULL AND generated_sql != ''
        ");
        $genColsStmt->bind_param('s', $table);
        $genColsStmt->execute();
        $genColsRes = $genColsStmt->get_result();

        $generatedFormulas = [];
        while ($genCol = $genColsRes->fetch_assoc()) {
            $generatedFormulas[$genCol['column_name']] = html_entity_decode($genCol['generated_sql'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $genColsStmt->close();

        // 2. Only run calculations if the table actually has generated formulas configured
        if (!empty($generatedFormulas)) {
            foreach ($rader as $data) {
                $id = $data[$primaryKey] ?? null;
                if ($id === null || $id === '' || $id === 0) continue;

                $rowValues = [];
                foreach ($generatedFormulas as $colName => $formula) {
                    $calcSql = "SELECT ({$formula}) as val FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1";
                    $calcStmt = $db->prepare($calcSql);
                    $calcStmt->bind_param('s', $id);
                    
                    if ($calcStmt->execute()) {
                        $calcRes = $calcStmt->get_result()->fetch_assoc();
                        $rowValues[$colName] = $calcRes['val'] ?? '';
                    }
                    $calcStmt->close();
                }

                // Map the calculated fields using the row's primary key ID as the key index
                $batchRecalculated[$id] = $rowValues;
            }
        }

        // Commit all SQL insertions and updates safely
        $db->commit();
        
        // Return success along with the live row calculations grouped by row ID
        echo json_encode([
            'success' => true,
            'message' => 'Batch save completed successfully',
            'recalculated' => $batchRecalculated,
            'debug_source' => 'PHP batchSaveRows() function'
        ]);
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'debug_source' => 'PHP batchSaveRows() function'
        ]);
    }
    exit;
}


function deleteRow($db, $table)
{
    $primaryKey = getPrimaryKey($db, $table);

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );
    $pk_value = $data[$primaryKey] ?? null;

    if ($pk_value) {
        if (is_array($pk_value)) {
            if (!empty($pk_value)) {
                // Skapa frågetecknen för prepared statement (t.ex. "?,?,?")
                $placeholders = implode(',', array_fill(0, count($pk_value), '?'));
                
                $stmt = $db->prepare("
                    DELETE FROM {$table} 
                    WHERE {$primaryKey} IN ($placeholders)
                ");
                
                // Dynamisk bindning av parametrar för mysqli
                // Skapar en sträng med 'ssss...' beroende på antal ID:n
                $types = str_repeat('s', count($pk_value)); 
                $stmt->bind_param($types, ...$pk_value);
                
                if ($stmt->execute()) {
                    $success = true; 
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No IDs provided for deletion'
                ]);
                return;
            }
               
        } else {
            // Om det bara är ett enskilt ID (för bakåtkompatibilitet)

            $stmt = $db->prepare("
                DELETE
                FROM {$table}
                WHERE {$primaryKey}=?
            ");
            $stmt->bind_param(
                's',
                $data[$primaryKey]
            );
            if ($stmt->execute()) {
                $success = true; 
            }
        }
    }

    echo json_encode([
        'success' => $success ?? false,
    ]);
}

function rangeUpdate($db, $table)
{
    $primaryKey = getPrimaryKey($db, $table);

    $rows = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($rows)) {
        jsonError('Invalid data format, expected array of rows');
        return;
    }

    $updated = 0;
    $failed = 0;
    $errors = [];

    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            $failed++;
            $errors[] = "Row $idx is not an array";
            continue;
        }

        if (!isset($row[$primaryKey])) {
            $failed++;
            $errors[] = "Row $idx missing primary key '$primaryKey'";
            continue;
        }

        $id = $row[$primaryKey];
        $data = $row;
        unset($data[$primaryKey]);

        // Remove null or empty primary key
        $data = array_filter($data, function($key) use ($primaryKey) {
            return $key !== $primaryKey;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($data)) {
            $failed++;
            $errors[] = "Row $idx has no data to update";
            continue;
        }

        $fields = [];
        $types = '';
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "`{$field}`=?";
            $types .= 's';
            $values[] = $value;
        }

        $types .= 's';
        $values[] = $id;

        $sql = "
            UPDATE `{$table}`
            SET " . implode(',', $fields) . "
            WHERE `{$primaryKey}`=?
        ";

        try {
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                $failed++;
                $errors[] = "Row $idx prepare failed: " . $db->error;
                continue;
            }

            if (!$stmt->bind_param($types, ...$values)) {
                $failed++;
                $errors[] = "Row $idx bind_param failed: " . $stmt->error;
                $stmt->close();
                continue;
            }

            if (!$stmt->execute()) {
                $failed++;
                $errors[] = "Row $idx execute failed: " . $stmt->error;
                $stmt->close();
                continue;
            }

            $stmt->close();
            $updated++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = "Row $idx exception: " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'failed' => $failed,
        'errors' => $errors
    ]);
}
function getDependentDropdownOptions($db, $table)
{
    $columnName = $_GET['column'] ?? '';
    $parentVal  = $_GET['parent_val'] ?? '';

    if (!$columnName) {
        echo json_encode(['success' => false, 'error' => 'Missing column name']);
        exit;
    }

    // 1. Hämta ALLA relevanta relations-fält baserat på din NYA struktur
    $stmt = $db->prepare("
        SELECT relation_table, relation_value_column, relation_parent_column, parent_column 
        FROM admin_columns 
        WHERE table_name = ? AND column_name = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $table, $columnName);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc();

    // --- NY SÄKERHETSKONTROLL MED TYDLIG FELSÖKNING ---
    // Om något av dessa fält saknas i databasen berättar JSON-svaret exakt vad som är tomt!
    if (!$meta || empty($meta['relation_table'])) {
        echo json_encode([
            'success' => false, 
            'error' => "Column '{$columnName}' is not configured as a relation table in admin_columns.",
            'debug_database_row' => $meta // Visar dig exakt vad raden innehåller i F12 Network
        ]);
        exit;
    }

    // Fallback: Om du råkat spara föräldrakolumnen i 'parent_column' istället för 'relation_parent_column'
    $parentCol = !empty($meta['relation_parent_column']) ? $meta['relation_parent_column'] : ($meta['parent_column'] ?? '');

    if (empty($parentCol)) {
        echo json_encode([
            'success' => false,
            'error' => "Configuration error: Both 'relation_parent_column' and 'parent_column' are empty for {$table}.{$columnName}",
            'debug_database_row' => $meta
        ]);
        exit;
    }

    // Bestäm vilken kolumn som ska användas som värde (faller tillbaka på kolumnnamnet om tomt)
    $valCol = !empty($meta['relation_value_column']) ? $meta['relation_value_column'] : $columnName;
    $relTable = $meta['relation_table'];

    // 2. Bygg den dynamiska SQL-frågan med prepared statements
    $query = $db->prepare("
        SELECT DISTINCT `{$valCol}` AS value
        FROM `{$relTable}` 
        WHERE `{$parentCol}` = ? 
        ORDER BY `{$valCol}` ASC
    ");
    
    if (!$query) {
        echo json_encode([
            'success' => false,
            'error' => "MySQL Prepare Failed: " . $db->error,
            'attempted_sql' => "SELECT DISTINCT `{$valCol}` FROM `{$relTable}` WHERE `{$parentCol}` = ?"
        ]);
        exit;
    }

    $query->bind_param('s', $parentVal);
    $query->execute();
    $result = $query->get_result();

    $values = [];
    while ($row = $result->fetch_assoc()) {
        $values[] = $row['value'];
    }

    echo json_encode([
        'success' => true,
        'values' => $values,
        'debug_source' => 'getDependentDropdownOptions'
    ]);
    exit;
}

function getGenerators($db, $table)
{

    $stmt = $db->prepare("SELECT * FROM admin_generators WHERE table_name = ? OR table_name = '' ORDER BY sort_order ASC");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $result = $stmt->get_result();

    $generators = [];
    while ($row = $result->fetch_assoc()) {
        // extra_config lagras som JSON-text i databasen — packa upp den till ett riktigt objekt
        // så att JavaScript kan läsa config.extra_config.stripPatterns direkt utan att behöva JSON.parse igen
        if (!empty($row['extra_config'])) {
            $row['extra_config'] = json_decode($row['extra_config'], true);
        }
        $generators[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($generators);
    exit;
}

function jsonError($message)
{
    echo json_encode([
        'success' => false,
        'error' => $message,
        'debug_source' => 'PHP jsonError() function'
    ]);

    exit;
}