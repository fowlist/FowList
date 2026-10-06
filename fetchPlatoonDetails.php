<?php
header("Content-Type: application/json");

function queryToItems($query, $conn) {
    $items =[];
    // Prepare and execute the second query
    if ($stmt2 = $conn->prepare($query)) {
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $items = $result2->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    } else {
        throw new Exception("Failed to prepare " . $query);
    }
    return $items;
}

$data = json_decode(file_get_contents("php://input"), true)??[];
$platoonInfo = $data['platoonInfo']??false;
$codes = $data['codes']??false;

// Handle both new format (platoonInfo) and legacy format (codes)
if ($platoonInfo) {
    $codes = is_array($platoonInfo) && isset($platoonInfo['codes']) 
             ? $platoonInfo['codes'] 
             : (is_array($platoonInfo) && isset($platoonInfo['platoon']) 
                ? [$platoonInfo['platoon']] 
                : false);
    $period = $platoonInfo['period'] ?? null;
} else {
    $period = null;
}

// Allow pd to be provided directly in the payload; prefer explicit pd, then period
$pd = $data['pd'] ?? $period ?? null;

// If pd is provided in JSON body, inject it into REQUEST_URI so sqlServerinfo.php detects it
if ($pd) {
    if (strpos($_SERVER['REQUEST_URI'], 'pd=') === false) {
        $sep = (strpos($_SERVER['REQUEST_URI'], '?') === false) ? '?' : '&';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] . $sep . 'pd=' . urlencode($pd);
    }
}
// Also set $query['pd'] so other includes can read it
$query['pd'] = $pd ?? "";

if ($codes) {
    include_once "sqlServerinfo.php";
    include "functions.php";

$placeholders = "'" . implode("','", $codes) . "'";

try {

// Query for the platoonconfig table
    $platoonConfigQuery = " SELECT * 
            FROM platoonConfig 
            WHERE platoon IN ({$placeholders})";

    $configItems = queryToItems($platoonConfigQuery, $conn);

    // Query for the platoonsStats table
    $query4 = " SELECT DISTINCT description, code,teams
        FROM platoonOptions 
        WHERE code IN ({$placeholders})";

    $optionItems = queryToItems($query4, $conn);

    $teams =[];
    $options=[];
    foreach ($optionItems as $option) {
        $options[$option["code"]][$option["description"]] =$option;
        $teams[$option["teams"]] = $option["teams"];
    }


    foreach ($configItems as $configrow) {
        $explodedTeams = explode("|", $configrow["teams"]);
        foreach ($explodedTeams as $team) {
            $teams[$team] =$team;
        }
        if (!empty($configrow["attachment"])) {
            $codes[] = $configrow["attachment"];
            
        }
    }

    $placeholders = "'" . implode("','", $codes) . "'";

    // Query for the platoonsStats table
    $query1 = " SELECT * 
                FROM platoonsStats 
                WHERE code IN ({$placeholders})";

    $platoonItems = queryToItems($query1, $conn);



    $teamsString = "'" . implode("','", $teams) . "'";
        // Query for the platoonconfig table
    $query3 = "SELECT * 
    FROM weaponsLink 
        RIGHT JOIN weapons 
        ON weaponsLink.weapon = weapons.weapon
    WHERE team IN ({$teamsString})";


    $weaponItems = queryToItems($query3, $conn);

    $weapons=[];
    foreach ($weaponItems as $weapon) {
        $weapons[$weapon["team"]][$weapon["weapon"]] =$weapon;
    }


    // Process the items from both queries
    $html = "";
    foreach ($platoonItems as $key => $value) {
        $html .= printPlatoonStats($value,$configItems,$weapons,$options);
    }

    // Return the result as JSON
    echo json_encode([
        'success' => true,
        'platoon' => $platoonItems,
        'config' => $configItems,
        'weapons' => $weaponItems,
        'options' => $optionItems,
        'html' => $html
    ]);

} catch (Exception $e) {
    // Handle exceptions and errors
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
        

} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}