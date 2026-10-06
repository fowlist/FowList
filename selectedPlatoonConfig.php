<?php
header("Content-Type: application/json");

function queryToItems($query, $conn, $params = []) {
    $items = [];
    
    if ($stmt = $conn->prepare($query)) {
        if (!empty($params)) {
            // Skapa typ-strängen (t.ex. "sss") baserat på antal element
            $types = str_repeat('s', count($params)); 
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        throw new Exception("Failed to prepare: " . $conn->error);
    }
    return $items;
}
ob_start();
$data = json_decode(file_get_contents("php://input"), true)??[];
$platoonInfo = $data['platoonInfo']??"";
$BoxInSection =[];

$platoon = $platoonInfo['platoon']??"";
$title = $platoonInfo['title']??"";
$team = $platoonInfo['teams']??"";
$unitType = $platoonInfo['unitType']??"";
$box_type = $platoonInfo['box_type']??"";
$currentBoxNr = $box_nr = $platoonInfo['']??"";
$formation = $platoonInfo['formation']??"";
$nation = $platoonInfo['nation']??"";
$forceNation = $platoonInfo['forceNation']??"";
$forceBook = $platoonInfo['forceBook']??"";
$book = $platoonInfo['book']??"";
$sameBookFormationSupport = true;
if (isset($forceBook)&&!empty($forceBook)) {
    $sameBookFormationSupport = $book == $forceBook;
}
$formationNr =$boxPositionID[1]??"";
$currentFormation = "F" . $formationNr;  
$currentBoxInFormation = $boxPositionID = $platoonInfo['boxPositionID']??"";
$query[$currentBoxInFormation] = $platoon;
$query[$currentFormation] = $formation;
$query['dpVer'] = $platoonInfo['dynamic']??"";
$query["dPs"] = isset($platoonInfo['dynamic'])&&($platoonInfo['dynamic'] !="Book")?"True":"";
$query[$formationNr . "-Card"] = $platoonInfo['formCard']??"";
$query["pd"] = $platoonInfo["pd"]??"";
$cardNr = $platoonInfo['cardNr']??null;

$type = $platoonInfo["currentFormation"];

$BoxInSection[$platoon] = [
    "platoon" => $platoon,
    "title" => $title,
    "unitType" => $unitType,
    "box_type" => $box_type,
    "box_nr" => $box_nr,
    "boxPositionID" => $boxPositionID,
    "currentBoxInFormation" => $currentBoxInFormation,
    "formation" => $formation,
    "book" => $book,
    "team" => $team,
    "cardNr" => $cardNr,
    "Nation" => $nation
];



if (!empty($platoon)) {
    include_once "sqlServerinfo.php";
    include "functions.php";
    include "htmlFunctions.php";
    // Säkerställ att det är en array
    $platoonArray = is_array($platoon) ? $platoon : [$platoon];
    $bookArray = is_array($book) ? $book : [$book];
    // Skapa strängen med frågetecken: "?,?,?"
    $pqs = implode(',', array_fill(0, count($platoonArray), '?'));
    $bookPqs = implode(',', array_fill(0, count($bookArray), '?'));

    $platoonConfigQuery = "SELECT * 
                FROM platoonConfig 
                        WHERE platoon IN ($pqs)
                ORDER BY cost DESC";

try {
    $platoonConfig = queryToItems($platoonConfigQuery, $conn, $platoonArray);

    $dpVersions =  $conn->query( "SELECT DISTINCT year FROM dpDatabase");
    $latestdp = 0;
    $setDpVersion ="";
    foreach ($dpVersions as $key => $value) {
        if ($value["year"] > $latestdp) {
            $latestdp = $value["year"];
        }
        if (($query['dpVer']??"") === $value["year"]) {
            $setDpVersion = $value["year"];
            $query['dPs'] = "true";
        }
    }
    
    $setDpVersion = $setDpVersion==""?$latestdp:$setDpVersion;
    
    $platoonOptiondpArray =  [];
    $platoonConfigdpArray = [];
    if (isset($query['dpVer'])||isset($query['dPs'])) {

    $platoonOptiondp = $conn->query(
            "SELECT  * 
            FROM    dpDatabase
            WHERE   type = 'option'
            AND     year = {$setDpVersion}");
    $platoonConfigdp = $conn->query(
            "SELECT  * 
            FROM    dpDatabase
            WHERE   type = 'config'
            AND     year = {$setDpVersion}");
    
    $platoonCardgdp = $conn->query(
            "SELECT  * 
            FROM    dpDatabase
            WHERE   type = 'card'
            AND     year = {$setDpVersion}");
    
    
        foreach ($platoonOptiondp as $key => $value) {
            $platoonOptiondpArray[$value["configCode"]] = $value;
        }
    
        foreach ($platoonConfigdp as $key => $value) {
            $platoonConfigdpArray[$value["configCode"]] = $value;
        }
    
        foreach ($platoonCardgdp as $key => $value) {
            $platoonCarddpArray[$value["code"]] = $value;
        }
    }
    
    foreach ($platoonConfig as $key => &$row) {
        $platoonConfig[$key]["dynamicPoints"] = $platoonConfigdpArray[$row["shortID"]]["cost"]??$platoonConfig[$key]["dynamicPoints"];
    }

    $boxesPlatoonsData[$formationNr] =[];

    $boxesPlatoonsData[$formationNr]["formCost"] = 0;
    $formationCards =[];
    if ($type == "formation"||$type == "Sup") {
                // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
        $boxesPlatoonsData[$formationNr]["currentFormation"] = "F" . $formationNr;
        //---- SQL
        $formationCardsQueryStr = "SELECT DISTINCT 
                            cmdCardFormationMod.Book AS Book, 
                            cmdCardFormationMod.formation AS formation, 
                            cmdCardFormationMod.card AS card, 
                            cmdCardCost.platoonTypes AS platoonTypes,
                            cmdCardCost.pricePerTeam AS pricePerTeam,     
                            cmdCardCost.price AS cost,
                            cmdCardsText.code AS code,
                            cmdCardsText.title AS title,
                            cmdCardsText.notes AS notes
                    FROM    cmdCardFormationMod
                        LEFT JOIN cmdCardCost
                        ON cmdCardCost.Book = cmdCardFormationMod.Book 
                        AND cmdCardCost.card = cmdCardFormationMod.card 
                            LEFT JOIN cmdCardsText
                        ON cmdCardCost.Book = cmdCardsText.Book 
                        AND cmdCardCost.card = cmdCardsText.card 
                WHERE   cmdCardsText.Book LIKE ?
                AND     cmdCardFormationMod.formation LIKE ?";

        // Förbered värdena med % för LIKE-sökning
        $bookParam = "%" . $book . "%";
        $formationParam = "%" . $formation . "%";
        try {
            $formationCards = queryToItems($formationCardsQueryStr, $conn, [$bookParam, $formationParam]);
        } catch (Exception $e) {
            // Hantera eventuella fel
            error_log($e->getMessage());
        }
        
        // ---- formation title and text
    
        // ------ cmdCards of entire formation -------------    
    
    
        list($boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormation"], $boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormationTitle"]) = processFormationCards($formationNr, $formationCards, $query, $currentFormation, $formationCost[$formationNr], $boxesPlatoonsData[$formationNr]);
    }
    if ($type == "formation"||$type == "Sup"||$type == "CdPl") {

        $cardPlatoonQueryStr = "SELECT 
                        boxType as box_type, 
                    platoon,
                    cardNr,
                    Book,
                    formation,
                    configChange,
                    optionChange,
                    boxNr as box_nr,
                    card as title,
                    unitType,
                    platoonNation,
                    prerequisite
            FROM    cmdCardAddToBox  
                WHERE   Book = ?
                AND     cardNr = ?
                AND     platoon IN ($pqs)";

        // 3. Slå ihop parametrarna i exakt den ordning de förekommer i SQL-koden
        // Ordning: Book (?), cardNr (?), platoon (?, ?, ?)
        $cardPlatoonParams = array_merge([$book, $cardNr], $platoonArray);

        try {
            $cardPlatoon = queryToItems($cardPlatoonQueryStr, $conn, $cardPlatoonParams);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    
        foreach ($cardPlatoon??[] as $key => $value) {
            $BoxInSection[$value["platoon"]] = array_merge($BoxInSection[$value["platoon"]],$value);
        }
    }
    $boxesPlatoonsData[$formationNr]["thisNation"] = $forceNation??null;
    $formationCost[$formationNr] =0;
    $platoonCards =[];
    $unitCards =[];
    if ($nation == $forceNation&&$sameBookFormationSupport) {
        // 2. Query för platoonCards
        $pcSql = "SELECT *
            FROM    cmdCardPlatoonModDB
                WHERE   Book IN ($bookPqs)
                AND     platoon IN ($pqs)";
        
        // Slå ihop alla parametrar: först alla böcker, sen alla platoons
        $pcParams = array_merge($bookArray, $platoonArray);
        $platoonCardsData = queryToItems($pcSql, $conn, $pcParams);
                
        $platoonCards = [];
        foreach ($platoonCardsData as $key => $value) {
            $platoonCards[$key] = $value;
            $platoonCards[$key]["dynamicPoints"] = $platoonCarddpArray[$value["code"]]["cost"] ?? "";
        }
        // 3. Query för unitCards
        $ucSql = "SELECT *
            FROM    cmdCardUnitModDB
                WHERE   unit = ?
                AND     Book = ?";
        
        $unitCards = queryToItems($ucSql, $conn, [$unitType, $book]);
        }

        $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["thisBoxType"] = "";

        $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
        $platoonOptionOptions = [];
        foreach ($BoxInSection as $platoonInBox) {

            $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]] = array_merge($boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]??[],$platoonInBox);
            $currentPlatoon =   $platoonInBox["platoon"];
            $currentUnit =      $platoonInBox["unitType"] ??"";

            if (!isset($boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"])) {
                $boxCost[$formationNr][$currentBoxNr] =null;
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] =null;
            }

// ----- checked and set status from session variablse for the selected platoon in the box
            $platoonConfigChanged = configChangedGenerate($platoonInBox, $platoonConfig);
            foreach ($platoonConfigChanged as $key => &$value) {
                $value["dynamicPoints"] = $platoonConfigdpArray[$value["shortID"]]["cost"]??$value["cost"];
            }


            $cardIndex =0;
            if ((!empty($currentPlatoon))&&($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {
                foreach ($platoonCards as $key => $thisplatoonCard) {
                    if (($thisplatoonCard["platoon"] == $currentPlatoon && isset($thisplatoonCard["code"])) || ($thisplatoonCard["platoon"] == $currentPlatoon && $thisplatoonCard["code"] == $platoonInBox["cardNr"]) ) { // isset code is to not show incomplete cards (price and text)
                        $cardIndex++;
                        if ($thisplatoonCard["code"] == $platoonInBox["cardNr"]) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] = $platoonInBox["cardNr"];
                            break;
                        }
                    }
                }
            }
            
            if (!empty($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]["selected"]=true;

                $platoonOptionHeaders = [];
                $platoonOptionQuery= $conn->query(
                    "SELECT  * 
                        FROM    platoonoptionsnew
                        wHERE   code = '{$platoonInBox["platoon"]}'
                        ORDER by optionID ASC"); 
                foreach ($platoonOptionQuery as $key => $optValue) {
                    $optValue["dynamicPoints"] = $platoonOptiondpArray[$currentPlatoon."|".$optValue["optionID"]]["cost"]??"";
                    $platoonOptionHeaders[] = $optValue;
                }

                addConfigToBoxPlatoon($platoonConfigChanged,  $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = newPlatoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders);
                newAddOptionsToBoxPlatoon($platoonOptionHeadersChanged, $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]], $query, $currentBoxInFormation);
                addFormationCardToBoxPlatoon($formationCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                generateCardArrays([], $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                addPlatoonCardToBoxPlatoon($platoonCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr,$currentBoxInFormation);
                addUnitCardsToBoxPlatoon($unitCards,$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $boxCost[$formationNr][$currentBoxNr] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $boxesPlatoonsData[$formationNr]["formCost"] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
                $formationCost[$formationNr] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
            }
        }
            
ob_start();

    foreach ($boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr] as $platoonInBox) {

        if (isset($platoonInBox["platoon"])) { 
        ?>
        <?=platoonConfigHTML($platoonInBox,$boxPositionID)?>
        <?=boxOptionPrintHTML($platoonInBox,$boxPositionID)?>
        <?=boxformCardPrintHTML($platoonInBox)?>
        <?=boxPlatoonCardPrintHTML($platoonInBox,$boxPositionID)?>
        <?=boxUnitCardPrintHTML($platoonInBox,$boxPositionID)?>
        <div class="Points">
            <div>
            <?=$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"]?> Point<?=$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr] ["boxCost"]>1?"s":""?>
            </div>
        </div>
    <?php
        }
    }
$html = ob_get_clean();

    // Return the result as JSON
    echo json_encode([
        'success' => true,
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