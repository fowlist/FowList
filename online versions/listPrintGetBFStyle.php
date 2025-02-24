<?php  header('Content-Type: text/html; charset=utf-8');
// Start the session
session_start();

include "sqlServerinfo.php";
include "functions.php";
include "login.php";
include "cssVersion.php";

//---- session varibale creation from GET

$parts = parse_url($_SERVER['REQUEST_URI']);
parse_str($parts['query'], $query);
$backQuery = "";
if (isset($_SESSION['loadedListName'])) {
    $loadedListName = $_SESSION['loadedListName'];
    if (isset($_SESSION['loadedListNumber'])) {
        $loadedListNumber = $_SESSION['loadedListNumber'];
            }            
        }
// -- all general tables 
if (empty($_SESSION["insigniaQuery"])) {
    include_once "sqlServerinfo.php";
    $insigniaResult = $conn->query(
        "SELECT  * 
    FROM    insignia
         ORDER BY autonr");
    $insignia = [];
    foreach ($insigniaResult as $value) {
        $insignia[]=$value;
    }
    $insigniaResult -> free();
    $_SESSION["insigniaQuery"] = $insignia;
    
} else {
    $insignia = $_SESSION["insigniaQuery"];
}

if (empty($_SESSION["Books"])) {
    include_once "sqlServerinfo.php";
    $BooksQuery = $conn->query(
       "SELECT  * 
    FROM    nationBooks");
    $Books = [];
    foreach ($BooksQuery as $value) {
        $Books[]=$value;
    }
    $BooksQuery -> free();
    $_SESSION["Books"] = $Books;
} else {
    $Books =$_SESSION["Books"];
}

if (empty($_SESSION["images"])) {
    include_once "sqlServerinfo.php";
    $imagesQuery = $conn->query(
       "SELECT  * 
        FROM    platoonImages");
    $images = [];
    foreach ($imagesQuery as $value) {
        $images[]=$value;
    }
    $imagesQuery -> free();
    $_SESSION["images"] = $images;
} else {
    $images = $_SESSION["images"];
}
$_SESSION=[];
if (isset($loadedListName)) {
    $_SESSION['loadedListName'] = $loadedListName;
}

if (isset($loadedListNumber)) {
    $_SESSION['loadedListNumber'] = $loadedListNumber;
}

$linkQuery ="";


foreach($query as $key => $row) {;
    if ($key !== "cost") {
        $_SESSION[$key]= (($key == "latestSelectID")? "": $query[$key]);
        $linkQuery .= ($key !== 'lsID') ? "&" . $key . "=" . $query[$key] : "";
        $backQuery .= "&" . $key . "=" . (($key == "latestSelectID")? "": $query[$key]);
    }
}

if ($_GET['cost'] != "") {
    $encodedData = $_GET['cost'];

    // Decode the encoded data
    $decodedData = gzinflate(base64_decode(strtr($encodedData, '-_', '+/')));

    // Unserialize the data back into an associative array
    $dataToTransfer = unserialize($decodedData);

    // Now you can work with $dataToTransfer['bCt'], $dataToTransfer['fCt'], and $dataToTransfer['lCt'] as needed
    $boxCost = $dataToTransfer['bCt'];
    $FormationCost = $dataToTransfer['fCt'];
    $listCost = $dataToTransfer['lCt'];

    foreach($query as $key => $row) {
        for ($i = 0; $i<= 20; $i++) {
            if  (($key == "fCd-".$i)){
                $forceCard[$i]=$row;
            }            
        }
    }
}
$bookSelected = FALSE;



    foreach ($Books as $row) if (($row["code"] == $query['Book'])||($row["Book"] == $query['Book'])){
        $bookCode = $row["code"]; 
        $bookTitle = $row["Book"]; 
        $bookSelected = true;
    }
if ($Books instanceof mysqli_result) {
    mysqli_data_seek($Books ,0);
}
    

// -- all book specific tables 

if ($bookSelected && !empty($bookTitle)) {
    $Support = $conn->query(
       "SELECT  *
        FROM    formations 
        WHERE   formations.title LIKE '%Support%' AND Book LIKE '%" . $bookTitle . "%'");

    $Support_DB = $conn->query(
        "SELECT  *
        FROM support_DB
        WHERE   Book = '{$bookTitle}'");

    $SupporboxNrs =[];
    $combinedSupportDB =[];
    $sqlTempSupportStatement = "";
    foreach ($Support_DB as $key => $value) {
        $combinedSupportDB[] = $value;
        $box_type = $value["box_type"];
        $box_nr = $value["box_nr"];
        if (!empty($value["platoon"])&&!strpos($sqlTempSupportStatement,$value["platoon"])) {
            $sqlTempSupportStatement .= (($key != 0)?" OR ":"") . "code LIKE '". $value["platoon"] . "'";
        }
        //
        $foundit =false;
        foreach ($SupporboxNrs as $value1) {
            if ($value1["box_nr"]==$box_nr) {
                $foundit =TRUE;
                break;
            }
        }
$maxSupportBoxNr = 0;
        if ((!$foundit)&&($value["ogBoxNrs"]!=null)) {
            $SupporboxNrs[]  = ["box_type" => $box_type,"box_nr" => $box_nr];
            if ($box_nr>=$maxSupportBoxNr) {
                $maxSupportBoxNr = $box_nr;
            }
        }
    }
    mysqli_data_seek($Support_DB ,0);


    $platoonCards= $conn->query(
        "SELECT  *
        FROM    cmdCardPlatoonModForPrintDB
        WHERE   Book = '{$bookTitle}'");

    $unitCards= $conn->query(
        "SELECT  *
        FROM    cmdCardUnitModForPrintDB
        WHERE   Book = '{$bookTitle}'");


    $forceCards= $conn->query(
        "SELECT  DISTINCT 
            cmdCardsForceMod_link.Book AS Book,
            cmdCardsForceMod_link.card AS card,  
            cmdCardsText.code AS code,
            cmdCardsText.notes AS notes,
            cmdCardsText.title AS title,
            cmdCardCost.price AS cost
        FROM    cmdCardsForceMod_link
            LEFT JOIN cmdCardCost
                LEFT JOIN cmdCardsText
                ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
            ON cmdCardCost.Book = cmdCardsForceMod_link.Book AND cmdCardCost.card = cmdCardsForceMod_link.card         
        WHERE   cmdCardsForceMod_link.Book LIKE '%" . $bookTitle . "%'");  

        
    $weapons = $conn->query(
       "SELECT  *
        FROM    weapons 
        LEFT    JOIN weaponsLink
            ON      weapons.weapon = weaponsLink.weapon");

    $rules = $conn->query(
       "SELECT  *
        FROM    rules ");    

    $cards = $conn->query(
       "SELECT  * 
        FROM    cmdCardsText 
        WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'");
}

if ($bookSelected && $query['Book'] <> "")  {

    $platoonOptionOptions= $conn->query(
"SELECT  * 
        FROM    platoonOptions");
    $platoonOptionHeaders = [];
    $headerMap = [];
    foreach ($platoonOptionOptions as $value) {
        $code = $value["code"];
        $description = $value["description"];
        $key = $code . '|' . $description; // Create a unique key for the combination
        if (isset($headerMap[$key])) {
            $headerMap[$key]["nrOfOptions"]++;
        } else {
            $headerMap[$key] = ["code" => $code, "description" => $description, "nrOfOptions" => 1];
        }
    }
    $platoonOptionHeaders = array_values($headerMap);
    mysqli_data_seek($platoonOptionOptions, 0);
}

$platoonsInForce=[];
$attachmentsInForce=[];
$weaponsTeamsInForce=[];
$rulesInForce=[];
$SummaryOfCards = [];

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=0.32">
    <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/listPrintBF.css?v=<?=$cssVersion?>'>
    <script src="jquery-3.7.0.min.js"></script>
    <?php
    // Menu
    if (isset($query['F1'])) {
        $firstFormationTitle = $query['F1'];
    } else {
        $firstFormationTitle = "No formation";
    }
    ?>


    <title>FOW List - <?php echo "{$bookTitle} - {$firstFormationTitle}" ?></title>
    <link rel="icon" type="image/x-icon" href="/img/<?=$query["ntn"]?>.svg">
</head>
<body>
<?php include "menu.php"; ?>

    <div id="page-container" class="page-container">
    
    <?php
    //foreach ($platoonCards as $row) {echo $row["card"];}
    // ----- temporary button during dev.. --------
    if (!empty($bookTitle)) {
        
        echo !empty($query['loadedListName'])?"List Name:{$query['loadedListName']}":"";
    ?>
        <div class="header">
        <h2 class="<?=$query['ntn']?>"> 
            <?=$bookTitle?>    
            <div class='Points'>
                <div class='Points1'>
                    <span id="totalPoints"><?php echo ($FormationCost != "") ? array_sum($FormationCost) + $listCost : "" ?></span> points
                </div>
            </div>
                <div class='Points' style="width: fit-content;">
                    <div class='Points1' style="background-color: blue">
                    Reserves: <span id="reservesPoints"><?php echo ($FormationCost != "") ? round((array_sum($FormationCost) + $listCost)*0.4) : "" ?> points</span>
                    </div>
                </div>
            </h2> 
            <button value="" onClick="printPage();">Print the page</button>
    </div>
    <?php
    }

// ---------------------------------------------------
//  ------------------- Formation --------------------
// ---------------------------------------------------
if ($bookSelected) { //   Formation
    $nrOfFormationsInForce = $query['nOF']+$query['nOFoB'];
    $formationTitle=[];
    $formationCardTitley=[];
    $formationCardNote=[];
    $platoonImages = [];
    $platoonCardMod = []; 
    $attachmentCardMod = []; 
    $platoonIndex = 0;
    $CardsInList = []; 
    $platoonCardChange=[];
    $boxAllHTML =[];
    $platoonSoftStatsTotal = [];
    $configCost =0;
    $configHTML ="";
    $optionsHTML = "";
    $formationCardHTML ="";
    $cardsHTML ="";
    //Formation loop
    for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce; $formationNr++) {

        $lastFormation = "";
        $currentFormation = "F" . $formationNr;
        $currentFormationCode = $query[$currentFormation] ??"";
        if (isset($query[$currentFormation])) {
        $formationFullName = $currentFormation . $currentFormationCode;
        $Formations = $conn->query(
            "SELECT  * 
             FROM    formations 
             WHERE   formations.code = '{$currentFormationCode}'");  

        $platoonConfigQuery= $conn->query(
            "SELECT  *
            FROM    platoonConfigDB
            WHERE formation = '{$currentFormationCode}'");
        $platoonConfig =[];
        if ($platoonConfigQuery->num_rows > 0) {
            while ($row = $platoonConfigQuery->fetch_assoc()) {
                if ($row['formation'] == $query[$currentFormation]) {
                    $platoonConfig[] = $row;
                }
            }
            mysqli_data_seek($platoonConfigQuery ,0);
        }
        $sqlTempStatement = "";
        $Formation_DB=[];
        $Formation_DBSql = $conn->query(
    "SELECT  * 
            FROM    formation_DB 
            WHERE   formation LIKE '%{$currentFormationCode}%'");
        foreach ($Formation_DBSql as $key => $value) {
            $Formation_DB[] = $value;
            if (!empty($value["platoon"])&&!strpos($sqlTempStatement,$value["platoon"])) {
                $sqlTempStatement .= (($key != 0)?" OR ":"") . "code LIKE '". $value["platoon"] . "'";
        }
        }

if (isset($query[$currentFormation])) {
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
                    replaceUnitStats,
                    unitType,
                    platoonNation
            FROM    cmdCardAddToBox  
            WHERE   Book LIKE '%" . $bookTitle . "%'
            AND     formation LIKE '%{$query[$currentFormation]}%'");
}
    

    if ($Formation_DBSql->num_rows > 0) {

    //---- SQL
    $formationCards= $conn->query(
        "SELECT  DISTINCT 
    cmdCardFormationMod.Book AS Book, 
    cmdCardFormationMod.formation AS formation, 
    cmdCardFormationMod.card AS card, 
    cmdCardCost.platoonTypes AS platoonTypes, 
    cmdCardCost.price AS cost, 
    cmdCardCost.pricePerTeam AS pricePerTeam,
    cmdCardFormationMod.motivation AS motivation, 
    cmdCardFormationMod.replaceMotivation AS replaceMotivation, 
    cmdCardFormationMod.skill AS skill, 
    cmdCardFormationMod.replaceSkill AS replaceSkill, 
    cmdCardFormationMod.isHitOn AS isHitOn,
    cmdCardsText.notes AS notes,
    cmdCardsText.code AS code,
    cmdCardsText.title AS title
    FROM    cmdCardFormationMod
        LEFT JOIN cmdCardCost
            LEFT JOIN cmdCardsText
            ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
        ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card 
    WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'
    AND     cmdCardFormationMod.formation LIKE '%" . $query[$currentFormation] . "%'");  
    }        

    if ($Formation_DBSql->num_rows > 0) {
    // ------- Formation Title and Note lookup -------------
        if (isset($Formations)) {
            foreach ($Formations as $formationRow) 
                if ($formationRow["code"] == $query[$currentFormation]) {

                    if ($formationRow["card"] != "") {
                        $formationRow['notes'] = $formationRow['Notes'];
                        $CardsInList[] = $formationRow;
                    }
                    $formationTitle[$formationNr] = $formationRow["title"];         
                    foreach ($Books as $book) {
                        if ($formationRow['Book']==$book["Book"]) {
                            $formationNation[$formationNr] = $book["Nation"];
                        }
                    }
                    
                }
            mysqli_data_seek($Formations ,0); 
        }
        if ($cardPlatoon->num_rows > 0) {
            foreach ($cardPlatoon as $value) {
                $foundit = false;
                foreach ($Formation_DB as $key => $value2) {
                    if (($value["platoon"] == $value2["platoon"])&&($value["box_nr"] == $value2["box_nr"])) {
                        $Formation_DB[$key] = $value;
                        $foundit = true;
                    }
                }
                if (!$foundit) {
                    $attributeList = [];
                    foreach ($value as $attributeName => $attributes) {
                        if ($attributeName=="box_type"&&$value["box_nr"]=="1") {
                            $attributeList[$attributeName] = "Headquarters";
                        } else {
                            $attributeList[$attributeName] = $attributes;
                        }
                    }
                    $Formation_DB[] = $attributeList;               
                    if (!empty($value["platoon"])&&!strpos($sqlTempStatement,$value["platoon"])) {
                        $sqlTempStatement .= (($sqlTempStatement != "")?" OR ":"") . "code LIKE '". $value["platoon"] . "'";
                    }
                }
                $formationLookup1 ="";
                $cardPlatoonConfig= $conn->query(
                    "SELECT  *
                    FROM    platoonConfigDB
                    WHERE platoon = '" . $value["platoon"] . "'");
                    
                foreach ($cardPlatoonConfig as $key1 => $value1) {
                    if (($value['platoon'] == $value1['platoon'])&&(($formationLookup1=="")||($formationLookup1==$value1["formation"]))) {

                        $platoonConfig[] = $value1;
                        $formationLookup1 = $value1["formation"];
                    }
                }
                mysqli_data_seek($cardPlatoonConfig ,0);
            }
            mysqli_data_seek($cardPlatoon ,0);
            array_multisort(array_column($Formation_DB,"box_nr"), SORT_ASC, SORT_NUMERIC,$Formation_DB);
            $tempArr = array_unique(array_column($platoonConfig, 'shortID'));
            $platoonConfig = array_intersect_key($platoonConfig,$tempArr);    
        }
        $platoonSoftStats= $conn->query(
            "SELECT  * 
                    FROM    platoonsStats
                    WHERE {$sqlTempStatement}");
    
            foreach ($platoonSoftStats as $key => $value) {
                if (!in_array($value, $platoonSoftStatsTotal)) {
                    $platoonSoftStatsTotal[] = $value;
                }
        }
    //------------------------------------
        $formationCardNote[$formationNr] = "";
    // ------- Card Title and Note lookup -------------
        if (isset($formationCards)&&isset($query[$formationNr. "-Card"])) {    
            foreach ($formationCards as $formationCardRow) 
                if ((str_replace("'", "", $formationCardRow["code"]) == $query[$formationNr. "-Card"])||($formationCardRow["code"] == $query[$formationNr. "-Card"])) {
                    $formationCardTitle[$formationNr] = trim($formationCardRow["title"]);
                    //$query[$currentFormation. "-Card"] =  str_replace("'", "", $formationCardRow["card"]);        
                    $formationCardNote[$formationNr] .= $formationCardRow["notes"];
                }
            mysqli_data_seek($formationCards ,0); 
        }
if (!isset($formationCardTitle[$formationNr])) {
                $formationCardTitle[$formationNr] =null;
            }
    //------------------------------------

    // print_r($_SESSION);
    ?>
    <table class="arsenal list" style='page-break-inside:avoid;'>
        <THEAD>
            <tr><th class="<?=$formationNation[$formationNr]?>"><?php 
    //---- Title print 
        if ((isset($formationCardTitle[$formationNr]))||(is_numeric(strpos($query[$currentFormation],"C")))) {
            echo "<img class='card' src='img/cardSmall.svg'>";
        }
        echo ((isset($formationCardTitle[$formationNr])&&($formationCardTitle[$formationNr]!=$formationTitle[$formationNr])&&(!is_numeric(strpos($formationTitle[$formationNr],$formationCardTitle[$formationNr])))) ? "\n \t {$formationCardTitle[$formationNr]}: ": "");
        
        echo $formationTitle[$formationNr]; ?></th><th class='<?=$query['ntn']?>'><?=$query['ntn']?></th><th><?=$query['F1']?></th><th class='<?=$query['ntn']?> pointsColumn'><?=$FormationCost[$formationNr]?></th></tr>                    
        </THEAD>
        <TBODY>   
        <?php
    //----- generate boxes
    $boxNrInFormation = 0;
        foreach ($Formation_DB as $row) {
    // --- set general box nr variable
            $currentBoxNr = $row["box_nr"];
            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            if (($row["formation"] == $currentFormationCode) && isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                $boxNrInFormation++;
                $boxAllHTML[$currentBoxInFormation] = "";
                $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                $platoonsInForce[$platoonIndex]["title"] = $row["title"];
                $platoonConfigChanged = configChangedGenerate($row, $platoonConfig);

                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

                foreach ($platoonConfigChanged as $row3) {
                    
                        if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                        $cardsHTML = "";
                        actualSectionsEval($row3,$row);
                        printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                        list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                        list($configCost, $configHTML) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
    // ------------------- image print -----------
                        
                        list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
    //------- check if the formation have cards for the box --------------
                        $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                        // ------------ platoon cards (pioneer etc.)
                        $flag =  generateTitleImanges($insignia, $formationCardTitle[$formationNr] . $platoonsInForce[$platoonIndex]["title"] , $query['ntn']);
                        

                    }
                }
                if ($platoonConfigChanged instanceof mysqli_result) {
                        mysqli_data_seek($platoonConfigChanged ,0);  
} 
    //--------------------------------------------
    //---------------- name etc. -------------  
                if (isset($platoonCardMod[$platoonIndex])) {
                        $platoonsInForce[$platoonIndex]["title"] = trim(((isset($platoonCardMod[$platoonIndex]["title"])&&$platoonCardMod[$platoonIndex]["title"]!=""&&
                                                                      (trim($platoonCardMod[$platoonIndex]["title"]) != 
                                                                       trim($platoonsInForce[$platoonIndex]["title"]))&&(!is_numeric(strpos(trim($platoonsInForce[$platoonIndex]["title"]),trim($platoonCardMod[$platoonIndex]["title"])))))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
                    } 

                $boxAllHTML[$currentBoxInFormation] .= "<tr><td colspan='3'>
                            {$platoonsInForce[$platoonIndex]["title"]}
                        " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i></td><td class='pointsColumn'><div class='Points' onclick='togglePoints(this)'>
                    <div>" . $boxCost[$formationNr][$currentBoxNr] . "</div>
                </div></td></tr>";

    // ------ Config of platoon -------------      
                $boxAllHTML[$currentBoxInFormation] .= "<tr><td>".$configHTML. $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML . "</td></tr>";
                    // -------- Points ----------  

                //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 



                echo $boxAllHTML[$currentBoxInFormation] ;
                $platoonIndex++;
            }

        }
if ($platoonConfig instanceof mysqli_result) {
        mysqli_data_seek($platoonConfig ,0); 
}
?>
        </TBODY>
    </table>

<?php

    }
}

                
    }
}
// ----------- support 

$formationNr+=1;
$currentFormation="Sup";
$arrayCdPl =[];
$currentPl = 0;
$formationCardTitle = "";
if ($bookSelected && $bookTitle !="") { // ----------- support 
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
                replaceUnitStats,
                platoonNation
        FROM    cmdCardAddToBox  
        WHERE   Book LIKE '%{$bookTitle}%'
        AND     formation LIKE '%Support%'");
$platoonConfigQuery = $conn->query(
            "SELECT  *
            FROM    platoonConfigSupportDB
            WHERE formation LIKE '%{$bookTitle}%'");
$platoonConfig  = [];
foreach ($platoonConfigQuery as $kwy => $value) {
    $platoonConfig[] = $value;
}
$formationCards = [];

//Support Card platoons ( outside of numbered boxes)
    foreach ($query as $key => $value) {
        $position = strpos(" ". $key,"CdPl");
        if (is_numeric($position))  {
            if (is_numeric(substr($key, $position + strlen("CdPl"),2))) {
                $currentPl = substr($key, $position + strlen("CdPl"),2);
            } elseif (is_numeric(substr($key, $position + strlen("CdPl"),1))) {
                $currentPl = substr($key, $position + strlen("CdPl"),1);
            }
            if ("CdPl-" . $currentPl == $key) {
                $arrayCdPl[$currentPl]["platoon"] = $query["CdPl-" . $currentPl];
                $arrayCdPl[$currentPl]["box_type"]= "card";
                for ($i=0; $i < 7; $i++) { 
                     if (isset($query["CdPl-" . $currentPl ."Card".$i])) {
                        $arrayCdPl[$currentPl]["cardNr"]= $query["CdPl-" . $currentPl ."Card".$i];
                     }
                }
                
            }
            if ("CdPl-" . $currentPl . "c"== $key) {
                $arrayCdPl[$currentPl]["shortID"] = $query["CdPl-" . $currentPl . "c"];
            }
            if ("CdPl-" . $currentPl . "Cd"== $key) {
                $arrayCdPl[$currentPl]["addCard"] = $query["CdPl-" . $currentPl . "Cd"];
            }
        }
    }

    //----------------------------new sup
    

    if ($cardPlatoon->num_rows > 0) { // ----------- support 
        foreach ($cardPlatoon as $value) {
            $foundit = false;
            foreach ($combinedSupportDB as $key => $value2) {
                if (($value["platoon"] == $value2["platoon"])&&($value["box_nr"] == $value2["box_nr"])) {
                    $combinedSupportDB[$key] = $value;
                    $foundit = true;
                }
            }
            if (!$foundit) {
                $combinedSupportDB[] = $value;
            }
            $formationLookup1 ="";
            $cardPlatoonConfig= $conn->query(
                "SELECT  *
                FROM    platoonConfigDB
                WHERE platoon = '" . $value["platoon"] . "'");
                
            foreach ($cardPlatoonConfig as $key1 => $value1) {
                if (($value['platoon'] == $value1['platoon'])&&!in_array($value1["shortID"], array_column($platoonConfig,"shortID") )) {
                    $platoonConfig[] = $value1;
                    $formationLookup1 = $value1["formation"];
                }
            }
            if (!empty($value["platoon"])&&!strpos($sqlTempSupportStatement,$value["platoon"])) {
                $sqlTempSupportStatement .= (($key != 0)?" OR ":"") . "code LIKE '". $value["platoon"] . "'";
            }
        }
        mysqli_data_seek($cardPlatoon ,0);
    }
    $platoonSoftStats= $conn->query(
        "SELECT  * 
                FROM    platoonsStats
                WHERE {$sqlTempSupportStatement}");
        foreach ($platoonSoftStats as $key => $value) {
            if (!in_array($value, $platoonSoftStatsTotal)) {
                $platoonSoftStatsTotal[] = $value;
            }
        }

    $bbEval = (is_numeric(strpos($parts['query'], "BlackBox")));  

    $supEval = (count($combinedSupportDB) > 0)&&(((is_numeric(strpos($parts['query'], "Sup")))));

    if ((count($arrayCdPl) > 0)||$supEval||$bbEval) {
    ?>
<br>
    <table class="arsenal list" style='page-break-inside:avoid;'>
        <THEAD>
            <tr><th class='<?=$query['ntn']?>'><?php foreach ($Support as $row) echo $row["title"]; mysqli_data_seek($Support ,0); ?></th><th class='<?=$query['ntn']?>'><?=$query['ntn']?></th><th></th><th class='<?=$query['ntn']?>'><?=$FormationCost[$formationNr]?></th></tr>                    
        </THEAD>
        <TBODY>   
        <?php
    }

    if ($supEval) { //--new sup
    //----- generate boxes
        foreach ($combinedSupportDB as $row) {

            $currentBoxNr = $row["box_nr"];
            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            $boxAllHTML[$currentBoxInFormation] ="";
            if (isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                
                $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                $platoonsInForce[$platoonIndex]["title"] = $row["title"];


                $platoonConfigChanged = configChangedGenerate($row, $platoonConfig);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

                foreach ($platoonConfigChanged as $row3) {
                    if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                        $cardsHTML = "";
                        $row["nrOfTeams"] = $row3["nrOfTeams"];
                        $row["teams"] = $row3["teams"];
                        $row["sections"] = $row3["sections"];
                        printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                        list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                        list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
    // ------------------- image print -----------
                        list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
    //------- check if the formation have cards for the box --------------
                        $formationCardHTML = printFormationCardsHTML([], $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                        // ------------ platoon cards (pioneer etc.)
                        $flag =  generateTitleImanges($insignia, (isset($platoonCardMod[$platoonIndex])? $platoonCardMod[$platoonIndex]["title"]??"":"") . (isset($platoonsInForce[$platoonIndex])?$platoonsInForce[$platoonIndex]["title"]:"") , $query['ntn']);
                        
                    }
                }

    //--------------------------------------------

    //---------------- name etc. -------------  
                if (isset($platoonCardMod[$platoonIndex])) {
        $platoonsInForce[$platoonIndex]["title"] = trim(((isset($platoonCardMod[$platoonIndex]["title"])&&$platoonCardMod[$platoonIndex]["title"]!=""&&
                                                      (trim($platoonCardMod[$platoonIndex]["title"]) != 
                                                       trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
} 

                $boxAllHTML[$currentBoxInFormation] .= "<tr><td colspan='3'>
{$platoonsInForce[$platoonIndex]["title"]}
                " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i></td><td class='pointsColumn'><div class='Points' onclick='togglePoints(this)'>
                    <div>" . $boxCost[$formationNr][$currentBoxNr] . "</div>
                </div></td></tr>";

// ------ Config of platoon -------------      
$boxAllHTML[$currentBoxInFormation] .= "<tr><td>".$currentConfig. $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML . "</td></tr>";
                    //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 

                echo $boxAllHTML[$currentBoxInFormation];
                $platoonIndex++;

            }
        }
    //echo "</div>";    
    //echo "</div>";   

    }

}
// ----------- BB support 
if ($bbEval) {
    $BBSupport_DB = $conn->query(
        "SELECT  DISTINCT platoon , title, alliedBook AS Book, unitType, Nation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'
    GROUP by platoon
    ORDER BY relevance desc");
}
if ((isset($BBSupport_DB)&&$BBSupport_DB->num_rows > 0)&&$bbEval) { //  BB support 

$formationNr+=1;
$currentFormation="BlackBox";


    $BBSupport_DBUsed = [];
    $sqlTempStatement ="";
    $sqlStatsTempStatement = "";
    foreach ($BBSupport_DB as $currentBoxNr => $row) {
        $currentBoxInFormation = "{$currentFormation}-{$currentBoxNr}";
        if ((isset($query[$currentBoxInFormation])&&$query[$currentBoxInFormation] === $row["platoon"])) {
            $sqlTempStatement .= ((!empty($BBSupport_DBUsed))?" OR ":"") . "platoon LIKE '". $row["platoon"] . "'";
            $sqlStatsTempStatement .= ((!empty($BBSupport_DBUsed))?" OR ":"") . "code LIKE '". $row["platoon"] . "'";
            $BBSupport_DBUsed[$currentBoxNr] = $row;
        }
        
    }
    $sqlTempStatement1 = empty($sqlTempStatement)?"":"AND   ({$sqlTempStatement})";
    $BBSupport_DBformation = $conn->query(
        "SELECT  DISTINCT platoon ,  formation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'
        {$sqlTempStatement1}");

    $BBSupport_type = $conn->query(
        "SELECT  DISTINCT platoon , box_type
        FROM    formationSupport_DB
        WHERE Book = '{$bookTitle}'
        {$sqlTempStatement1}");
        
    $platoonSoftStats= $conn->query(
        "SELECT  * 
                FROM    platoonsStats
                WHERE {$sqlStatsTempStatement}");
        foreach ($platoonSoftStats as $key => $value) {
            if (!in_array($value, $platoonSoftStatsTotal)) {
                $platoonSoftStatsTotal[] = $value;
            }
        }

    $sqlTempStatement = "";
    foreach ($BBSupport_DBUsed as $key => $row) {
        foreach($BBSupport_type as $row4) {
            if ($row4["platoon"] == $row["platoon"]){
                //$thisBoxType = $row4["box_type"];
                $BBSupport_DBUsed[$key]["box_type"] = $row4["box_type"];
                $BBSupport_DBUsed[$key]["box_nr"] = $currentBoxNr;
            }
        }
        mysqli_data_seek($BBSupport_type ,0);  
// ------------ Specific for BB --------
        
        foreach ($BBSupport_DBformation as $row4) {
            if ($row4["platoon"] == $row["platoon"]) {
                $sqlTempStatement .= ((!empty($sqlTempStatement))?" OR ":"") . "cmdCardFormationMod.formation LIKE '". $row4["formation"] . "'";
                if (empty($row["formation"])) {
                    $BBSupport_DBUsed[$key]["formation"] = $row4["formation"];
                } else {
                    $BBSupport_DBUsed[$key]["formation"] .= $row4["formation"];
                }
            }
        }
        mysqli_data_seek($BBSupport_DBformation ,0);  
    }

    if ($bookSelected && !empty($bookTitle)&&(is_numeric(strpos(substr($parts['query'],strpos($parts['query'],"Black")), "Card")))) { // -- BB support 
    $formationCards= $conn->query(
        "SELECT  DISTINCT 
    cmdCardFormationMod.Book AS Book, 
    cmdCardFormationMod.formation AS formation, 
    cmdCardFormationMod.card AS card, 
    cmdCardCost.platoonTypes AS platoonTypes, 
    cmdCardCost.price AS cost, 
    cmdCardFormationMod.motivation AS motivation, 
    cmdCardFormationMod.replaceMotivation AS replaceMotivation, 
    cmdCardFormationMod.skill AS skill, 
    cmdCardFormationMod.replaceSkill AS replaceSkill, 
    cmdCardFormationMod.isHitOn AS isHitOn,
    cmdCardsText.notes AS notes,
    cmdCardsText.code AS code,
    cmdCardsText.title AS title
    FROM    cmdCardFormationMod
        LEFT JOIN cmdCardCost
            LEFT JOIN cmdCardsText
            ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
        ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card 
        WHERE   cmdCardsText.Book LIKE '%{$bookTitle}%'
        AND ({$sqlTempStatement})");
}
    foreach ($BBSupport_DBUsed as $currentBoxNr => $row) { //  BB support 
        $currentFormationCode ="";
// --- set general box nr variable
        $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
        if ((isset($query[$currentBoxInFormation])&&$query[$currentBoxInFormation] === $row["platoon"])) {

//----------------------------------------------
$platoonConfig = $conn->query(
                    "SELECT  *
                    FROM    platoonConfig
                    LEFT    JOIN    (   SELECT DISTINCT unitType, platoon
                                        FROM formation_DB )
                                    AS
                                    unitT
                            ON unitT.platoon = platoonConfig.platoon
                    WHERE platoonConfig.platoon = '{$row["platoon"]}'");

            list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

            $boxAllHTML[$currentBoxInFormation] 
            = "";
            $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
            $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
            $platoonsInForce[$platoonIndex]["title"] = $row["title"];
            $platoonsInForce[$platoonIndex]["Nation"] = $row["Nation"];
            foreach ($platoonConfig as $row3) {
                if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                    $cardsHTML = "";
                    $row["nrOfTeams"] = $row3["nrOfTeams"];
                    $row["box_nr"] = $currentBoxNr;
                    $row["teams"] = $row3["teams"];
                    $row["sections"] = $row3["sections"];
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, (isset($platoonCardMod[$platoonIndex])?($platoonCardMod[$platoonIndex]["title"]??""):"") . $platoonsInForce[$platoonIndex]["title"], $row["Nation"]);
                }
            }
            mysqli_data_seek($platoonConfig ,0);  

//--------------------------------------------

//---------------- name etc. -------------  
            if (isset($platoonCardMod[$platoonIndex])) {
    $platoonsInForce[$platoonIndex]["title"] = trim(((isset($platoonCardMod[$platoonIndex]["title"])&&$platoonCardMod[$platoonIndex]["title"]!=""&&
                                                  (trim($platoonCardMod[$platoonIndex]["title"]) != 
                                                   trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
} 

            $boxAllHTML[$currentBoxInFormation] .= "<tr><td colspan='3'>
{$platoonsInForce[$platoonIndex]["title"]}
            " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i></td><td class='pointsColumn'><div class='Points' onclick='togglePoints(this)'>
                    <div>" . $boxCost[$formationNr][$currentBoxNr] . "</div>
            </div></td></tr>";

// ------ Config of platoon -------------      
$boxAllHTML[$currentBoxInFormation] .= "<tr><td>".$currentConfig. $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML . "</td></tr>";
            //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 
            echo $boxAllHTML[$currentBoxInFormation];
            $platoonIndex++;
        }
    }

}


if ($bookSelected && $bookTitle <> "") { // ----------- card support 

$formationNr+=1;
$currentFormation="CdPl";
$currentBoxNr = 0;
    $cardSupport = $conn->query(
   "SELECT  boxType as box_type, 
            platoon,
            cardNr,
            Book,
            boxNr,
            configChange,
                 optionChange,
            card as title,
            unitType
    FROM    cmdCardAddToBox  
    WHERE   Book LIKE '%" . $bookTitle . "%'
         AND     formation LIKE '%Support%'
         AND     boxNr > " . $maxSupportBoxNr);
}


// Find the position of the substring



if ($bookSelected && (count($arrayCdPl) > 0)) {
$cardPlatoonIndex = 0;
    foreach ($arrayCdPl as $currentBoxNr => $row) {
        $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
        $currentFormationCode ="";

// --- set general box nr variable
//----------------------------------------------
            foreach ($cardSupport as $value) {
                if ($row["platoon"]==$value["platoon"]) {
                    if (isset($row["cardNr"])&&($row["cardNr"] == $value["cardNr"])) {
                        $platoonsInForce[$platoonIndex]["title"] = $value["title"];
                        $row["configChange"] = $value["configChange"];
                    } elseif (!isset($row["cardNr"])) {
                     $platoonsInForce[$platoonIndex]["title"] = $value["title"];
                     $row["configChange"] = $value["configChange"];
                    }
                }
            }
            $boxAllHTML[$currentBoxInFormation] 
            = "";
            $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
            $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
            $platoonConfig = $conn->query(
                "SELECT  platoonConfig.*,unitT.unitType
                FROM    platoonConfig
                LEFT    JOIN    (   SELECT DISTINCT unitType, platoon
                                    FROM formation_DB )
                                AS
                                unitT
                        ON unitT.platoon = platoonConfig.platoon
                WHERE platoonConfig.platoon = '{$row["platoon"]}'");

            $platoonSoftStats= $conn->query(
        "SELECT  * 
                FROM    platoonsStats
                WHERE   code = '{$row["platoon"]}'");
        foreach ($platoonSoftStats as $key => $value) {
            if (!in_array($value, $platoonSoftStatsTotal)) {
                $platoonSoftStatsTotal[] = $value;
                }
            }
                
            $platoonConfigChanged = configChangedGenerate($row, $platoonConfig);
            list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

            foreach ($platoonConfigChanged as $row3) {
                if (($row["platoon"]==$row3["platoon"])&&($row3["shortID"] === $row["shortID"])) {
                    $cardsHTML = "";
                    
                    $row["nrOfTeams"] = $row3["nrOfTeams"]??0;
                    $row["box_nr"] = $currentBoxNr;
                    $row["teams"] = $row3["teams"];
                    $row["sections"] = $row3["sections"];
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $formationCardTitle, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, ($formationCardTitle[$formationNr]??"") . (isset($platoonsInForce[$platoonIndex])?$platoonsInForce[$platoonIndex]["title"]:""), $query['ntn']);

                }
            }
            mysqli_data_seek($platoonConfig ,0);  

//--------------------------------------------
           // echo "<!--here: {$formationCardHTML} -->";
//---------------- name etc. -------------  

            
            $boxAllHTML[$currentBoxInFormation] .= "<tr><td colspan='3'>
{$platoonsInForce[$platoonIndex]["title"]}
            " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i></td><td class='pointsColumn'><div class='Points' onclick='togglePoints(this)'>
                    <div>" . $boxCost[$formationNr][$currentBoxNr] . "</div>
            </div></td></tr>";


// ------ Config of platoon -------------      
$boxAllHTML[$currentBoxInFormation] .= "<tr><td>".$currentConfig. $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML . "</td></tr>";

            //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 
            echo $boxAllHTML[$currentBoxInFormation];
            $platoonIndex++;
        }
    }



    if ((count($arrayCdPl) > 0)||($Support_DB->num_rows > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||(is_numeric(strpos($parts['query'], "BlackBox"))))) {
        ?>
        </TBODY>
    </table>
    <?php 
}

 // ------- cards ----------

 if  (isset($forceCard)) { // ------- cards ----------
    if (($forceCards->num_rows > 0)) {
        $temp = $forceCards->num_rows;
        foreach($forceCard as $row){
            foreach ($forceCards as $key5 => $row5) {
                if ((str_replace("'", "", $row5["card"]) == $row )||($row5["card"] == $row )) { 
                    $row5["thisCost"] = $row5["cost"];
                    array_push($CardsInList,$row5);
                }
            }
        }
        mysqli_data_seek($forceCards ,0);
    }
    }

if (isset($CardsInList)&&count($CardsInList) > 0) { // ------- cards ----------
    foreach ($CardsInList as $key => $value) {
        if (strtok($value["card"], ":")) {
            $CardsInList[$key]["card"] = strtok($value["card"], ":");
        }
        if (isset($CardsInList[$key]["thisCost"])) {
            $SummaryOfCards[$CardsInList[$key]["card"]][] = $CardsInList[$key]["thisCost"];
        }
        
    }
    $tempArr = array_unique(array_column($CardsInList, 'card'));
    $CardsInList = array_intersect_key($CardsInList,$tempArr);     
?>
<div></div>
<div style='break-inside:avoid;'> 
<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Command Cards</h3>
</button>
<div class="Formation">
<?php  
    foreach ($CardsInList as $key1 => $row1) {
        if ( !empty($row1["card"])) {   
        foreach ($cards as $row2) {
            if (( $row1["card"] != "")&&(($row1["card"] == $row2["card"] )||( $row1["card"] == strtok($row2["card"], ":") ))) {
                    $cardStats = [];
                    $cardStats["title"]= $row1["card"];
                    $useStatRowInput = !empty($row2["statsModifier"])?$row2["statsModifier"]:(!empty($row2["unitModifier"])?$row2["unitModifier"]:"");
                    $replace = [
                        [": ",  "MOTIVATION",   "SKILL",    "IS HIT ON",    "SAVE", "ARMOUR",   "TACTICAL", "CROSS|\n", "\n|",  "|:EOS:",   "COUNTRY",  "Weapon|",   "WEAPON|"],
                        [":",   ":MOTIVATION",  ":SKILL",   ":IS HIT ON",   ":SAVE",":ARMOUR",  ":TACTICAL","CROSS:",   "\n",   ":EOS:",    "C.",       "\n:Weapon|","\n:WEAPON|"]
                    ];
                    $useStatRow = str_replace($replace[0],$replace[1],(empty($useStatRowInput)?"":$useStatRowInput. ":EOS:"));
                    $statsExplode = !empty($useStatRow)?explode(":",$useStatRow. ":EOS:"):[];
                    foreach ($statsExplode as $key => $value) {
                        if (str_replace(["MOTIVATION"],"",$value)!=$value) {
                            $cardStats["motivation"] = $cardStats["motivation"]??"" . motivationBox(trim(str_replace("|","",$statsExplode[$key+1])??"","\n\r\t"));
                }
                        if (str_replace(["SKILL"],"",$value)!=$value) {
                            $explodedSkill = explode("/", $statsExplode[$key+1]);
                            foreach ($explodedSkill as $rowNr =>  $explodedValue) {
                                $cardStats["skill{$rowNr}"] = motivationBox(trim(str_replace("|","",$explodedValue)??"","\n\r\t"));
                            }
                        }
                        if (str_replace("IS HIT ON","",$value)!=$value) {
                            $cardStats["isHitOn"] ??=  motivationBox(trim(str_replace("|","",$statsExplode[$key+1])??"","\n\r\t"));
                        }
                        if (str_replace(["SAVE"],"",$value)!=$value) {
                            $cardStats["save"] ??= saveBox(trim(str_replace("|","",$statsExplode[$key+1])??"","\n\r\t"));
                        }
                        if (str_replace(["TACTICAL"],"",$value)!=$value) {
                            $cardStats["TACTICAL"] ??=  generateHtmlGrid("{$value}\n{$statsExplode[$key+1]}"??"");
                        }
                        if (str_replace(["Weapon|"],"",$value)!=$value) {
                            $cardStats["Weapon"] ??=  generateHtmlGrid("{$value}\n{$statsExplode[$key+1]}"??"");
                }
                    }
                    $notesExploded  =explode("\n",str_replace(["\t","||","|\n","|MOTIVATION","|SKILL","|IS HIT ON","for:","follows:","below:","teams:"],
                                                                                ["|","|","\n","\nMOTIVATION","\nSKILL","\nIS HIT ON","for:\n\n","follows:\n","below:\n","teams:\n"  ],str_replace($useStatRowInput,"",$row2["notes"])));
                    $notesTable = "";
                    foreach ($notesExploded as $key => &$value) {
                        if (str_replace(["|"],"",$value)!=$value) {
                            $notesTable .= "{$value}\n";
                            $value ="";
                        }
                    }
                    foreach ($notesExploded as $key => &$value) {
                        if ($value ==""&&!empty($notesTable)) {
                            $value = generateHtmlGrid($notesTable);
                            $notesTable ="";
                        } elseif ($value =="") {
                            unset($value);
                        } else {
                            $value = $value . "<br>";
                        }
                    }
                    $cardStats["notes"]= implode("\n",$notesExploded) ."\n" . ($cardStats["TACTICAL"]??"")."\n" . ($cardStats["Weapon"]??"");
                    unset($cardStats["TACTICAL"]);
                    unset($cardStats["Weapon"]);

                    if (isset($SummaryOfCards[$row1["card"]])) {
                        foreach ($SummaryOfCards[$row1["card"]] as $rowNr => $cardRow){
                            if ($cardRow != 0) {
                                $cardStats["points{$rowNr}"] = "
                                <div class='Points' onclick='togglePoints(this)'>
                                    <div>
                                        {$cardRow} points
                                    </div>
                                </div>";
                            }
                        }
                    }
                    generateDynamicGrid($cardStats);
                break;
            }
        }
    }
    }
    ?>
</div></div>
<?php
}

if ($bookSelected) { // ------- soft stats ----------
//------------------------------------
// ------- soft stats ----------
//------------------------------------
$platoonSoftStatsInput = [];
    foreach ($attachmentsInForce as $value) {
        if (!in_array(["code" => $value["code"]],$platoonSoftStatsInput)) {
            $platoonSoftStatsInput[]["code"] = $value["code"];
        }
    }
    $sqlTempStatement = "";
    $first = true;
    foreach ($platoonSoftStatsInput as $key => $value) {
        if (is_string($value["code"])) {
            $sqlTempStatement .= (!$first?" OR ":"") . "code LIKE '". $value["code"] . "'";
            $first = False;
        }
    }
    if (!empty($sqlTempStatement)) {
    $platoonSoftStats= $conn->query(
    "SELECT  * 
            FROM    platoonsStats
            WHERE {$sqlTempStatement}");
    }
    
    foreach ($platoonSoftStats as $key => $value) {
        if (!in_array($value, $platoonSoftStatsTotal)) {
            $platoonSoftStatsTotal[] = $value;
        }
    }
    ?>
    <div style='page-break-inside:avoid;'>
    <button type="button" class="collapsible">  
        <h3 class="<?=$query['ntn']?>">Arsenal</h3>
    </button>
    <?php

    foreach ($attachmentsInForce as $key => $attachmentRow) {// ------- soft stats ----------
$attachmentsInForce[$key]["attachment"] = "attachment";
        if (is_array($attachmentRow["code"])) {
            $attachmetCodes = $attachmentRow["code"];
        } else {
            $attachmetCodes[0] = $attachmentRow["code"];
        }

        foreach ($platoonSoftStatsTotal as $row3) {
            if ($attachmentRow["code"]==$row3["code"]) {
                $attachmentsInForce[$key]["title"] = $row3["title"];
                        if (!empty($platoonCardMod[$attachmentRow['platoonIndex']]["title"])){
                            $attachmentsInForce[$key]["title"] = $platoonCardMod[$attachmentRow['platoonIndex']]["title"] . ": " . $attachmentsInForce[$key]["title"];
                } 
            }
        }
    }
    foreach ($platoonsInForce as $key => $platoonRow) {// ------- soft stats ----------
        foreach ($rules as $ruleRow) {
            if (isset($platoonRow["option"])) {
                foreach ($platoonRow["option"] as $optionsOfPlatoon) {
                    if (is_numeric(strrpos(strtoupper($optionsOfPlatoon), strtoupper($ruleRow["name"]),-1))) {
                        $rulesInForce[] =  $ruleRow["name"];
                    }
                        } 
            }
        }
    }

    $platoonsInForce = array_merge($platoonsInForce , $attachmentsInForce); // could add $attachmentCardMod but not working i guess
    $tempArr = array_unique(array_column($platoonsInForce, "title"));

    $platoonsInForce = array_intersect_key($platoonsInForce,$tempArr); 
    //$platoonsInForce = array_unique($platoonsInForce, SORT_REGULAR);
}
if (isset($platoonSoftStatsTotal)&&count($platoonSoftStatsTotal) > 0) {// ------- soft stats ----------
    echo  "
        <table class='arsenal' style='page-break-inside:avoid; '>
        <THEAD>
            <tr><th class='{$query['ntn']}' rowspan='2'>Image</th><th class='{$query['ntn']}'>TACT.</th><th class='{$query['ntn']}'>TERR. DASH</th><th class='{$query['ntn']}'>CROSS C. DASH</th><th class='{$query['ntn']}'>ROAD DASH</th><th class='{$query['ntn']}'>CROSS</th><th class='{$query['ntn']}' rowspan='2'>MOTIVATION </th><th class='{$query['ntn']}' rowspan='2'> SKILL</th><th class='{$query['ntn']}' rowspan='2'>ARMOUR/SAVE</th></tr>
        </THEAD>
        <TBODY>";            
foreach ($platoonsInForce as $key => $platoonRow) {
    if (isset($platoonRow['originalPlatoonCode'])||isset($platoonRow["replaceImgWith"])) {
        $needles = ["x3","x4","x5"];
        foreach ($needles as $needle) {
            if (is_numeric(strpos($platoonRow["replaceImgWith"],$needle))) {
                $platoonImages[$key] = substr($platoonRow["replaceImgWith"],0,strpos($platoonRow["replaceImgWith"],$needle));
                break;
            }
        }
    } 
    if (empty($platoonImages[$key])) {
        foreach ($images as $row2) {
            if  (($platoonRow['code']==$row2['code'])) {
                $platoonImages[$key] = $row2['image'];
                break;
            }
        }
        
        if (isset($platoonCardMod[$platoonRow['platoonIndex']])&&isset($platoonCardMod[$platoonRow['platoonIndex']]['ReplaceImg'])&&isset($platoonRow["replaceImgWith"])) {
            $platoonImages[$key] = str_replace($platoonCardMod[$platoonRow['platoonIndex']]['ReplaceImg'],$platoonCardMod[$platoonRow['platoonIndex']]["replaceImgWith"],$platoonImages[$key]);
    }
    }


if ($images instanceof mysqli_result) {
    mysqli_data_seek($images ,0);
}
    foreach ($platoonSoftStatsTotal as $platoonSoftStatRow) { 
        if (( $platoonRow['code'] === $platoonSoftStatRow["code"])&&($platoonSoftStatRow["TACTICAL"]!="")) {
            if (!empty($platoonRow["addToKeyword"])) {
                $platoonSoftStatRow["Keywords"] .= ", {$platoonRow["addToKeyword"]}";
            }
            if ((isset($platoonCardMod[$platoonRow['platoonIndex']])&&isset($platoonCardMod[$platoonRow['platoonIndex']]["replaceKeyword"])&&$platoonCardMod[$platoonRow['platoonIndex']]["replaceKeyword"] == "Keyword")){
                $platoonKeyword["Keywords"] = str_replace(", ,",", ",str_replace($platoonCardMod[$platoonRow['platoonIndex']]["removeKeyword"],"",$platoonSoftStatRow["Keywords"]) . ", ". $platoonCardMod[$platoonRow['platoonIndex']]["addKeyword"]);
                $platoonKeyword["keywordCardChange"] = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>";
            } else {
                $platoonKeyword = $platoonSoftStatRow;
                $platoonKeyword["keywordCardChange"]="";
            }
            if ($platoonKeyword["Keywords"] != "") {
                foreach ($rules as $ruleRow) {
                    if (isset($platoonRow["option"])) {
                        foreach ($platoonRow["option"] as $optionsOfPlatoon) {
                            if (is_numeric(strrpos(strtoupper($optionsOfPlatoon), strtoupper($ruleRow["name"]),-1))) {
                                $rulesInForce[] =  $ruleRow["name"];
                            }
                        }
                    }

                    if (is_numeric(strrpos(strtoupper($platoonKeyword["Keywords"]), strtoupper($ruleRow["name"]),-1))) {
                        
                        foreach (explode(",",$platoonKeyword["Keywords"]) as $eachKeyword) {
                            if (trim($ruleRow["name"]) == trim($eachKeyword)) {
                                $rulesInForce[] =  $ruleRow["name"];
                            }
                        }
                    }
                }
            }

            if (!empty($platoonCardMod[$platoonRow['platoonIndex']]["title"])){
                $cardTitle = " <img src='img/cardSmall.svg'>" 
                . ((!is_numeric(strrpos($platoonRow["title"], $platoonCardMod[$platoonRow['platoonIndex']]["title"])))?$platoonCardMod[$platoonRow['platoonIndex']]["title"] . ": ":"");
            } 
            else  {
                $cardTitle ="";
            }
            echo "
            <tr style='page-break-inside:avoid;'>
                <td class='imagerow' rowspan='2' style='page-break-inside:avoid; max-width: 160px;'>";
                if (isset($platoonImages[$key])) {
            foreach (explode("|",$platoonImages[$key] ,7) as $key1 => $boxImage) 
                    echo  "<img src='img/{$boxImage}.svg'>";
                }

            echo "<br> " . 
            ((!is_numeric(strpos($platoonSoftStatRow["code"],"CP")))?$platoonSoftStatRow["code"]: $platoonRow["originalPlatoonCode"]??"") . "\n";

            echo "</td><td class='statsrow' colspan='5' style='text-align: left;'>
            <b><span class='left'>" . generateTitleImanges($insignia, $cardTitle . (($platoonRow["title"]=="")? $platoonSoftStatRow["title"] : $platoonRow["title"] ), (isset($platoonRow["Nation"]))?$platoonRow["Nation"]:$query['ntn']) ."</span><span>".$cardTitle .(($platoonRow["title"]=="")? $platoonSoftStatRow["title"] : $platoonRow["title"] )." </span></b><br>
                ".  $platoonKeyword["Keywords"].$platoonKeyword["keywordCardChange"]."</td>
            <td class='statsrow' rowspan='2'>";
//------------------ Motivation ------------------------
            $platoonMotivation = processPlatoonAttribute("motivation", $platoonRow, $platoonSoftStatRow, $platoonCardMod);
            echo motivationBox($platoonMotivation);
            echo "</td>";

//---------------------Skill ------------------------------
            echo "<td class='statsrow' rowspan='2'>";
            $platoonSkill = processPlatoonAttribute("skill", $platoonRow, $platoonSoftStatRow, $platoonCardMod);
            echo motivationBox($platoonSkill);
            echo "</td>";
//-------------------Save ------------------------------------

            echo "<td class='statsrow' rowspan='2'>";
            
                if (isset($platoonCardMod[$platoonRow['platoonIndex']])&&isset($platoonCardMod[$platoonRow['platoonIndex']]['isHitOn'])&&$platoonCardMod[$platoonRow['platoonIndex']]['isHitOn']!=""&&(!isset($platoonRow["attachment"]))) {
                    $platoonIsHitOn = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$platoonRow['platoonIndex']]['isHitOn'];
                } elseif (isset($platoonCardMod[$platoonRow['platoonIndex']])&&isset($platoonCardMod[$platoonRow['platoonIndex']]["attachment"])&&is_array($platoonCardMod[$platoonRow['platoonIndex']]["attachment"])&&$platoonCardMod[$platoonRow['platoonIndex']]["attachment"]['isHitOn']!=""&&(isset($platoonRow["attachment"]))) {
                    $platoonIsHitOn = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$platoonRow['platoonIndex']]["attachment"]['isHitOn'];
                } else {
                    $platoonIsHitOn = $platoonSoftStatRow["IS_HIT_ON"];
                }
                echo motivationBox($platoonIsHitOn);

                if (isset($platoonCardMod[$platoonRow['platoonIndex']])&&(isset($platoonCardMod[$platoonRow['platoonIndex']]["replaceSave"])&&$platoonCardMod[$platoonRow['platoonIndex']]["replaceSave"] == "save")){
                    $platoonSave["ARMOUR_SAVE"] = "";
                    $originalSave = explode("\n",$platoonSoftStatRow["ARMOUR_SAVE"]);
                    foreach (explode("\n", $platoonCardMod[$platoonRow['platoonIndex']]["ARMOUR_SAVE"]) as $savedKey => $saveValue) {
                        if (!empty($saveValue)) {
                            $platoonSave["ARMOUR_SAVE"] .= (($savedKey>0)?"\n":""). $saveValue;
                        } else {
                            $platoonSave["ARMOUR_SAVE"] .= (($savedKey>0)?"\n":""). $originalSave[$savedKey];
                        }
                    }
                    //$platoonSave = $platoonCardMod[$platoonRow['platoonIndex']];
                    $platoonSave["statCardChangeIcon"] = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>";
                } else {
                    $platoonSave = $platoonSoftStatRow;
                    $platoonSave["statCardChangeIcon"] = "";
                }

                echo  saveBox($platoonSave["ARMOUR_SAVE"],$platoonSave["statCardChangeIcon"]) . "    
            </td>
        </tr>";

//--------------------Speed --------------------------------------
            if (empty($platoonRow["attachment"])&&(isset($platoonCardMod[$platoonRow['platoonIndex']])&&isset($platoonCardMod[$platoonRow['platoonIndex']]["replaceMovement"])&&$platoonCardMod[$platoonRow['platoonIndex']]["replaceMovement"] == "movement")){
                $platoonMovement = $platoonSoftStatRow;
                foreach ($platoonCardMod[$platoonRow['platoonIndex']] as $speedKey => $speedValue) {
                    if (!empty($speedValue)) {
                        $platoonMovement[$speedKey] = $speedValue;
                    }
                }
                $platoonMovement = $platoonCardMod[$platoonRow['platoonIndex']];
                $platoonMovement["statCardChangeIcon"] = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>";
            } else {
                $platoonMovement = $platoonSoftStatRow;
                $platoonMovement["statCardChangeIcon"]="";
            }


                $smaller = ($platoonMovement["TACTICAL"]=="UNLIMITED")?" style='font-size: x-small;' ":"";
                echo "
                <tr>
                    <td>
                        <b $smaller>". $platoonMovement["statCardChangeIcon"].str_replace("/", " / ", $platoonMovement["TACTICAL"])." </b>
                    </td>
                    <td>
                        <b $smaller>". str_replace("/", " / ", $platoonMovement["TERRAIN_DASH"])." </b>
                    </td>                    
                    <td>
                        <b $smaller>".str_replace("/", " / ", $platoonMovement["CROSS_COUNTRY_DASH"])." </b>
                    </td>
                    <td>
                        <b $smaller>".str_replace("/", " / ", $platoonMovement["ROAD_DASH"])." </b>
                    </td>
                    <td>
                        <b $smaller>".$platoonMovement["CROSScheck"]." </b>
                    </td>
                </tr>"
                ;
            //foreach ($platoonSoftStatRow as $col => $val) {
                    
              //  echo  $col." = ".$val."<br>";
            //}

        }
    }
} ?>
            </TBODY>
            </table>
<?php }?>
 
</div>
<div></div>
<div style="break-inside: avoid-page;">
<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Weapons</h3>
</button>
    <div><?php //------- weapons ----------?>
<?php            
$weaponsTeamsInForce =array_unique($weaponsTeamsInForce);
            
if (isset($weapons)&&$weapons->num_rows > 0) {

            echo  "
                    <table style='break-inside:avoid;'>
                    <THEAD>
                        <tr><th class='{$query['ntn']}'>Image</th><th class='{$query['ntn']}' >Weapon</th><th class='{$query['ntn']}'>Range</th><th class='{$query['ntn']}'>Halted ROF</th><th class='{$query['ntn']}'>Moving ROF</th><th class='{$query['ntn']}'>Anti Tank</th><th class='{$query['ntn']}'>Firepower</th><th class='{$query['ntn']}'>Notes</th></tr>                    
                    </THEAD>
                    <TBODY>";              
foreach ($weaponsTeamsInForce as $row1) if (( $row1 <> "")&&( $row1 <> "Komissar team")&&( $row1 <> "Komissar Team")&&( $row1 <> "komissar team")) {               
    $teamImage = "";
    $waponsRow=array();
    $weaponsPerTeam=2;
    foreach ($weapons as $key => $row2) 
        if ( $row1 === $row2["team"]) {
            $weaponsRow[$key]=$row2;
            $weaponsPerTeam++;
            $teamImage = $row2["image"];
            $teamTeam = $row2["team"];
    }

    mysqli_data_seek($weapons ,0);
    if ($teamImage != "") {
        echo "
    <tr style='page-break-inside:avoid;'>
        <td class='imagerow'  style='page-break-inside:avoid;' rowspan='{$weaponsPerTeam}'><img src='img/{$teamImage}.svg'></td>

    </tr>
            <tr style='page-break-inside:avoid;'>
            <td class='teamHeader' style='text-align: left; background-color: #eeeeee;' colspan='8'>{$teamTeam}
            </td>
    </tr>";
    }
    if (isset($weaponsRow)) {

        foreach ($weaponsRow as $row2) {
        if ( ($row1 === $row2["team"])||isset($row2["teams"])&&($row1 === $row2["teams"]) ) {
                    
                    foreach ($rules as $row3) {
                        if (isset($row2["notes"])&&$row2["notes"] != ""&&isset($row3["name"])&&is_numeric(strrpos(strtoupper($row2["notes"]), strtoupper($row3["name"]),-1))) {
                            foreach (explode(",",$row2["notes"]) as $eachKeyword) {
                                if (trim($row3["name"]) == trim($eachKeyword)) {
                            $rulesInForce[] =  $row3["name"];
                                }
                            }
                            
                        }
                    }                
                    
                    echo "
                    <tr>
                        <td class='firstWeaponrow' style='text-align: left;'>
                            <b>".$row2["weapon"]." </b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b>".$row2["ranges"]." </b>
                        </td>" .
                        ((($row2["haltedROF"] == "ARTILLERY")||($row2["haltedROF"] == "SALVO")) ? "
                        <td  class='firstWeaponrow' colspan='2'>
                            <b>{$row2["haltedROF"]}</b>
                        </td>" :"
                        <td class='firstWeaponrow' >
                            <b>{$row2["haltedROF"]}</b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b>{$row2["movingROF"]}</b>
                        </td>") . "
                        <td class='firstWeaponrow' >
                            <b>".$row2["antiTank"]." </b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b>".$row2["firePower"]." </b>
                        </td>
                        <td class='firstWeaponrow'  style='text-align: left;'>
                            <b>".$row2["notes"]." </b>
                        </td>
                    </tr>";
                }

            }
        }
    }
}
            echo "
            </TBODY>
            </table>
            </div></div>   "; 
           

 
// ------- rules ----------
echo "
<div style='page-break-inside:avoid;'>  
";
?>


<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Rules</h3>
</button>

<?php  


   
$rulesInForce = array_unique($rulesInForce);        

if (isset($rules)&&$rules->num_rows > 0) {

            echo  "                    
                <table>
                    <THEAD>                 
                    </THEAD>
                    <TBODY>";
    foreach ($rulesInForce as $row1) {
        if ( $row1 <> "")  {
            foreach ($rules as $row2) {
                if (($row2["text"] <> "")&&( $row1 <> "")&&( $row1 === $row2["name"] )) {
                    echo "
                <tr style='page-break-inside:avoid;'>
                    <td style='text-align: left;'>
                    <b>{$row1}</b>
                    </td>
                    <td style='text-align: left;'>
                        ".str_replace("\n","<br>", $row2["text"]) ."
                    </td>
                </tr>";
            //}
        }
    }
    }
    mysqli_data_seek($rules ,0);
}
}
            echo "
            <TBODY>
            </table>
            </div>   ";   
            $_GET["lastPage"] = $_SESSION["lastPage"] = $_SERVER['PHP_SELF'];
            $pdo = null;
            $conn->close();
?>
    
</div>
<div class="searchstring"><?=$linkQuery?></div>

    <script>
        function printPage() {
            // Open the print dialog
            window.print();
        }
    </script>

<script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.display === "none") {
      content.style.display = "inline-block";
    } else {
      content.style.display = "none";
    }
  });
};
var linkElements = document.getElementsByClassName('slowLink');

var myLoadingFunction = function() {
    $('<div class=loadingDiv>loading...<br><div class="loader"></div></div>').prependTo(document.body);
};

for (var i = 0; i < linkElements.length; i++) {
    linkElements[i].addEventListener('click', myLoadingFunction, false);
}
</script>

<script>
        function shrinkImagesForPrint() {
            const images = document.querySelectorAll('img');
            images.forEach(image => {
                // Store original dimensions as dataset attributes for later restoration
                if (typeof image.dataset.originalWidth == 'undefined') {
                    image.dataset.originalWidth = image.offsetWidth;
                    image.dataset.originalHeight = image.offsetHeight;
                    // Set new dimensions
                    
                    image.style.width = image.dataset.originalWidth * 0.55 + 'px';
                    image.style.height = image.dataset.originalHeight * 0.55 + 'px';
                } 
            });
        }

        function restoreImageSizes() {
            const images = document.querySelectorAll('img');
            images.forEach(image => {
                // Remove inline styles to restore original dimensions
                image.style.width = '';
                image.style.height = '';
            });
        }


setTimeout(function() { shrinkImagesForPrint(); }, 10);

    function togglePoints(element) {
        // Get the total points div

        var totalPoints = document.getElementById('reservesPoints');
        // Get the current points
        var currentPoints = parseInt(totalPoints.textContent);
        // Get the points in the clicked div
        var pointsInDiv = parseInt(element.textContent.trim());

        // If the points in the div are not a number, return
        if (isNaN(pointsInDiv)) {
            return;
        }
        // Toggle the selected class to change color
        element.classList.toggle('selected');

        // If the div is selected (blue), add its points to the total, else subtract its points from the total
        if (element.classList.contains('selected')) {
        totalPoints.textContent = currentPoints - pointsInDiv + ' Points';
        } else {
        totalPoints.textContent = currentPoints + pointsInDiv + ' Points';
        }
    }

        

</script>

</body>
</html>
