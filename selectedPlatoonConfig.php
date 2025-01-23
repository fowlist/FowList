<?php
header("Content-Type: application/json");
function queryToItems($query, $conn, $placeholders = "") {
    $items =[];
    // Prepare and execute the second query
    if ($stmt = $conn->prepare($query)) {
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        throw new Exception("Failed to prepare " . $query);
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
$book = $platoonInfo['book']??"";
$formationNr =$boxPositionID[1]??"";
$currentFormation = "F" . $formationNr;  
$currentBoxInFormation = $boxPositionID = $platoonInfo['boxPositionID']??"";
$query[$currentBoxInFormation] = $platoon;
$query[$currentFormation] = $formation;
$query["dPs"] = $platoonInfo['dynamic']??"";
$query[$formationNr . "-Card"] = $platoonInfo['formCard']??"";
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
    $placeholders = is_array($platoon)?"'" . implode("','", $platoon) . "'":"'$platoon'";
    $bookPlaceholders = is_array($book)?"'" . implode("','", $book) . "'":"'$book'";

    // Query for the platoonconfig table
    $platoonConfigQuery = " SELECT * 
                FROM platoonConfig 
                WHERE platoon IN ({$placeholders})
                ORDER BY cost DESC";

try {
    $platoonConfig = queryToItems($platoonConfigQuery, $conn, $placeholders);
    
    $boxesPlatoonsData[$formationNr] =[];

    $boxesPlatoonsData[$formationNr]["formCost"] = 0;
    $formationCards =[];
    if ($type == "formation"||$type == "Sup") {
                // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
        $boxesPlatoonsData[$formationNr]["currentFormation"] = "F" . $formationNr;
        //---- SQL
        $formationCards= $conn->query(
            "SELECT  DISTINCT 
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
                            LEFT JOIN cmdCardsText
                            ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
                        ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card 
                    WHERE   cmdCardsText.Book LIKE '%" . $book . "%'
                    AND     cmdCardFormationMod.formation LIKE '%" . $formation . "%'");  
        
        // ---- formation title and text
    
        // ------ cmdCards of entire formation -------------    
    
    
        list($boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormation"], $boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormationTitle"]) = processFormationCards($formationNr, $formationCards, $query, $currentFormation, $formationCost[$formationNr], $boxesPlatoonsData[$formationNr]);
    }
    if ($type == "formation"||$type == "Sup"||$type == "CdPl") {
        $cardPlatoon = $conn->query(
            "SELECT  boxType as box_type, 
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
            WHERE   Book = '{$book}'
            AND     cardNr = '{$cardNr}'
            AND     platoon IN ({$placeholders})");

    
        foreach ($cardPlatoon??[] as $key => $value) {
            $BoxInSection[$value["platoon"]] = array_merge($BoxInSection[$value["platoon"]],$value);
        }
    }
    $boxesPlatoonsData[$formationNr]["thisNation"] = $forceNation??null;
    $formationCost[$formationNr] =0;
    $platoonCards =[];
    $unitCards =[];
    if ($nation == $forceNation) {
        $platoonCards= $conn->query(
            "SELECT  *
            FROM    cmdCardPlatoonModDB
            WHERE   Book IN ({$bookPlaceholders})
            AND     platoon IN ({$placeholders})");

        $unitCards= $conn->query(
            "SELECT  *
            FROM    cmdCardUnitModDB
            WHERE   unit = '{$unitType}'
            AND     Book = '{$book}'");
    }

    $platoonOptionOptions= $conn->query(
        "SELECT  * 
        FROM    platoonOptions
        WHERE   code IN ({$placeholders})");
                ob_start();
    $platoonOptionHeaders = [];
    foreach ($platoonOptionOptions as $value) {
        $code = $value["code"];
        $description = $value["description"];
        
        if (!in_array(["code" => $code,"description" => $description, "oldNr" => $value["optCode"]], $platoonOptionHeaders[$code]??[])) {
            $platoonOptionHeaders[$code][]  = ["code" => $code,"description" => $description, "oldNr" => $value["optCode"]];
        }
    }
    mysqli_data_seek($platoonOptionOptions, 0);

        $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["thisBoxType"] = "";

        $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";

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

                addConfigToBoxPlatoon($platoonConfigChanged,  $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                addOptionsToBoxPlatoon($platoonOptionHeadersChanged, $platoonOptionOptions,$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]], $query, $currentBoxInFormation);
                addFormationCardToBoxPlatoon($formationCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                
                generateCardArrays([], $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);


                addPlatoonCardToBoxPlatoon($platoonCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr,$currentBoxInFormation);
                $html = ob_get_clean();
/*
                // Return the result as JSON
                echo json_encode([
                    'success' => true,
                    'html' => $html
                ]);
                return;
*/
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