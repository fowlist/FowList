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


foreach($query as $key => $queryRow) {;
    if ($key !== "cost"&&!empty($queryRow)) {
        $_SESSION[$key]= (($key == "latestSelectID")? "": $queryRow);
        $linkQuery .= ($key !== 'lsID') ? "&" . $key . "=" . $queryRow : "";
        $backQuery .= "&" . $key . "=" . (($key == "latestSelectID")? "": $queryRow);
    }
}

if ($_GET['cost'] != "") {
    $encodedData = $_GET['cost'];
    $costArrayStrig = "&cost=" . $encodedData;
    // Decode the encoded data
    $decodedData = gzinflate(base64_decode(strtr($encodedData, '-_', '+/')));

    // Unserialize the data back into an associative array
    $dataToTransfer = unserialize($decodedData);

    // Now you can work with $dataToTransfer['bCt'], $dataToTransfer['fCt'], and $dataToTransfer['lCt'] as needed
    $boxCost = $dataToTransfer['bCt']??[];
    $FormationCost = $dataToTransfer['fCt']??[];
    $listCost = $dataToTransfer['lCt']??0;

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
$counts = [];
$seen = []; // Hash map to track unique combinations

// Step 1: Count occurrences of each unique key
foreach ($platoonOptionOptions as $value) {
    $code = $value["code"];
    $description = $value["description"];
    $oldNr = $value["optCode"];

    // Create a unique key for the current combination
    $uniqueKey = "{$code}|{$description}|{$oldNr}";

    // Increment the count for this unique key
    $counts[$uniqueKey] = ($counts[$uniqueKey] ?? 0) + 1;
}

// Step 2: Build the headers with counts
foreach ($platoonOptionOptions as $value) {
    $code = $value["code"];
    $description = $value["description"];
    $oldNr = $value["optCode"];

    // Create a unique key for the current combination
    $uniqueKey = "{$code}|{$description}|{$oldNr}";

    // Add to headers only if not already processed
    if (!isset($seen[$uniqueKey])) {
        $platoonOptionHeaders[$code][] = [
            "code" => $code,
            "description" => $description,
            "oldNr" => $oldNr,
            "nrOfOptions" => $counts[$uniqueKey] // Use the precomputed count for this unique key
        ];

        $seen[$uniqueKey] = true; // Mark this combination as processed
    }
}

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
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=0.5">
    <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/listPrnt.css?v=<?=$cssVersion?>'>
    <script src="jquery-3.7.0.min.js"></script>
    <script src="packery.pkgd.min.js"></script>
    <script src="draggabilly.pkgd.min.js"></script>
    <script src="listPrint.js?v=<?=$cssVersion?>"></script>
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
    if (!empty($bookTitle)) {?>
    

        <div class="header  <?=$query['ntn']?>">
            <div class="formHeader">
                <b><?=$bookTitle?></b>    
            </div>
            <div>
                <div class='Points'>
                    <div class='Points1'>
                        <span id="totalPoints"><?php echo (!empty($FormationCost)) ? array_sum($FormationCost) + $listCost : "" ?></span> points
                    </div>

                </div>
                <div class='Points' style="width: fit-content;">
                    <div class='reservesPoints Points1'>
                    Reserves: <span id="reservesPoints"><?php echo (!empty($FormationCost)) ? round((array_sum($FormationCost) + $listCost)*0.4) : "" ?> points</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="Formation">
            <?php if (!empty($query['dPs'])) :?>
                Using Dynamic Points
            <?php else :?>
                Using Book Points
            <?php endif ?>
            <?php if (!empty($query['loadedListName'])) :?>
                | List Name:<span id="listName"><?=$query['loadedListName']?></span>
            <?php endif ?>
        </div>

        <?php
    }

// ---------------------------------------------------
//  ------------------- Formation --------------------
// ---------------------------------------------------
if ($bookSelected) { //   Formation
    $nrOfFormationsInForce = ($query['nOF']??0)+($query['nOFoB']??0);
    $formationsContainer = array_fill(1, ($query['nOF']??0)+($query['nOFoB']??0),[]);
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
    foreach ($formationsContainer as $formationNr => $formationEachRow) {

        $currentFormation = "F" . $formationNr;
        $currentFormationCode = $query[$currentFormation] ??"";
        $formationsContainer[$formationNr]["FormationCode"] = $query[$currentFormation] ??"";

        if (isset($query[$currentFormation])) {
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
                    $formationsContainer[$formationNr]["FormationTitle"] = $formationRow["title"];
                    foreach ($Books as $book) {
                        if ($formationRow['Book']==$book["Book"]) {
                            $formationNation[$formationNr] = $book["Nation"];
                            $formationsContainer[$formationNr]["formationNation"] = $book["Nation"];
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
    ?>
    <div class="collapsible <?=$formationNation[$formationNr]?>">  
        <div class="formHeader"> <?php 
    //---- Title print 
        if ((isset($formationCardTitle[$formationNr]))||(is_numeric(strpos($query[$currentFormation],"C")))) {
            echo "<img class='card' src='img/Card.svg'>";
        }
        echo generateTitleImanges($insignia, $formationCardTitle[$formationNr] 
            . $formationsContainer[$formationNr]["FormationTitle"], $formationNation[$formationNr])
            . ((isset($formationCardTitle[$formationNr])&&($formationCardTitle[$formationNr]!=$formationsContainer[$formationNr]["FormationTitle"])&&(!is_numeric(strpos($formationsContainer[$formationNr]["FormationTitle"],$formationCardTitle[$formationNr])))) ? "\n \t {$formationCardTitle[$formationNr]}: ": "");
        echo $formationsContainer[$formationNr]["FormationTitle"]; ?>
        </div>
            <div class='Points'>
                <div class='Points1'>
                    <?=$FormationCost[$formationNr]?> points 
                </div>
            </div> 
    </div>
    <div class='Formation'>
        <div class="grid">
    <?php
    //----- generate boxes

        foreach ($Formation_DB as $row) { //   Formation
    // --- set general box nr variable
            $currentBoxNr = $row["box_nr"];
            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            $formationsContainer[$formationNr][$currentBoxInFormation] ="";
            if (($row["formation"] == $currentFormationCode) && isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                if (isset($formationNation[$formationNr])) {
                    $platoonsInForce[$platoonIndex]["platoonNation"] = $formationNation[$formationNr];
                }
                $formationsContainer[$formationNr][$currentBoxInFormation]
                .= "<div class='box'>
                <div class='platoon {$formationNation[$formationNr]}'>{$row["box_type"]}
                <br>";
                $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                $platoonsInForce[$platoonIndex]["title"] = $row["title"];
                $platoonsInForce[$platoonIndex]["FormationCardTitle"] = $formationsContainer[$formationNr]["FormationTitle"] . $formationCardTitle[$formationNr]??"" ;
                $platoonConfigChanged = configChangedGenerate($row, $platoonConfig);

                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

                foreach ($platoonConfigChanged as $row3) {
                    if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                        $cardsHTML = "";
                        $optionsHTML = generatePlatoonOptionsPrintHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $platoonsInForce);
                        actualSectionsEval($row3,$row);
                        printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                        list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                        list($configCost, $configHTML) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                        //
                        $boxImageHTML = printBoxImageHTML($row, $row3, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                        //
                        $formationCardHTMLtemp = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                        if (isset($platoonCardChange[$platoonIndex])&&isset($platoonCardChange[$platoonIndex]["html"])) {
                            $formationCardHTML = ((!is_numeric(strpos($platoonCardChange[$platoonIndex]["html"],$formationCardHTMLtemp)))?$formationCardHTMLtemp:"");
                        } else {
                            $formationCardHTML = $formationCardHTMLtemp;
                        }
// ------------ platoon cards (pioneer etc.)
                        //
                        $flag =  generateTitleImanges($insignia, $formationCardTitle[$formationNr] . $formationsContainer[$formationNr]["FormationTitle"] . $platoonsInForce[$platoonIndex]["title"] , ((!empty($row["platoonNation"]))?$row["platoonNation"]:$formationNation[$formationNr]));
                    
                        $formationsContainer[$formationNr][$currentBoxInFormation] .= 
                        "<span class='nation'>" . $flag . "</span><div class='images'>" . $boxImageHTML  . 
                        "</div>";
                    }
                }
                $msi = processPlatoonStats($row, $platoonIndex, $platoonSoftStats, $platoonCardMod);

                if ($platoonConfigChanged instanceof mysqli_result) {
                        mysqli_data_seek($platoonConfigChanged ,0);  
                } 
    //--------------------------------------------
    //---------------- name etc. -------------  
                if (isset($platoonCardMod[$platoonIndex])) {
                    
                    $platoonsInForce[$platoonIndex]["title"] = 
                    trim(
                        (
                        (isset($platoonCardMod[$platoonIndex]["title"]) &&
                            $platoonCardMod[$platoonIndex]["title"] !="" &&
                        (trim( $platoonCardMod[$platoonIndex]["title"]) != trim($platoonsInForce[$platoonIndex]["title"]))&&
                        (!is_numeric(strpos(trim($platoonsInForce[$platoonIndex]["title"]),trim($platoonCardMod[$platoonIndex]["title"])))))? 
                        $platoonCardMod[$platoonIndex]["title"] . ": ": "") . 
                        $platoonsInForce[$platoonIndex]["title"]);
                }

                $formationsContainer[$formationNr][$currentBoxInFormation] .= "
                <div class='title'>
                        <b> 
                            {$platoonsInForce[$platoonIndex]["title"]}
                        </b>
                        <br>
                        " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i>
                        <br>
                    </div>
                </div>";

    // ------ Config of platoon -------------      
                $formationsContainer[$formationNr][$currentBoxInFormation] .= $msi . $configHTML . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
                    // -------- Points ----------  
                $formationsContainer[$formationNr][$currentBoxInFormation] .= "
                <div class='Points'>
                    <div>
                        " . $boxCost[$formationNr][$currentBoxNr] . " points
                    </div>
                </div>";
                $formationsContainer[$formationNr][$currentBoxInFormation] .= "</div>";
                echo $formationsContainer[$formationNr][$currentBoxInFormation];
                $platoonIndex++;
            }

        }
        if ($platoonConfig instanceof mysqli_result) {
                mysqli_data_seek($platoonConfig ,0); 
        }
        echo "</div>";
        echo "</div>";  
    }
}
    }
}

if ($bookSelected && $bookTitle !="") { // ----------- support 
    $formationNr+=2;
    $currentFormation="Sup";
    $arrayCdPl =[];
    $currentPl = 0;
    $formationCardTitle = "";
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

    $bbEval = is_numeric(strpos($parts['query'], "BlackBox"))||is_numeric(strpos($parts['query'], "boxc"));  

    $supEval = (count($combinedSupportDB) > 0)&&(((is_numeric(strpos($parts['query'], "Sup")))));

    if ((count($arrayCdPl) > 0)||$supEval||$bbEval) {
    ?>

    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
            <?php foreach ($Support as $row) echo $row["title"]; mysqli_data_seek($Support ,0); ?>
        </div>
  
            <div class='Points'>
                <div class='Points1'>
                    <?php echo (($FormationCost[$formationNr]??0) + ($FormationCost[$formationNr + 1 ]??0) + ($FormationCost[$formationNr+ 2]??0) + ($FormationCost[$formationNr+ 3]??0) ) ?> points 
                </div>
            </div> 
    </div>

    <div class='Formation'>
    <div class="grid">        
    <?php
    }

    if ($supEval) { //--new sup
    //----- generate boxes
        foreach ($combinedSupportDB as $row) {

            $currentBoxNr = $row["box_nr"];            
            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            if (isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                $boxAllHTML[$currentBoxInFormation] ="";
                $formationCardHTML ="";
                $boxAllHTML[$currentBoxInFormation] 
                .= "<div class='box'>
                <div class='platoon {$query["ntn"]}'>{$row["box_type"]}<br>";
                $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                $platoonsInForce[$platoonIndex]["title"] = $row["title"];


                $platoonConfigChanged = configChangedGenerate($row, $platoonConfig);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($row, $platoonOptionHeaders,$platoonOptionOptions);

                foreach ($platoonConfigChanged as $row3) {
                    if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                        $cardsHTML = "";
                        $optionsHTML = generatePlatoonOptionsPrintHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $platoonsInForce);
                        $row["nrOfTeams"] = $row3["nrOfTeams"];
                        $row["teams"] = $row3["teams"];
                        $row["sections"] = $row3["sections"];
                        printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                        list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                        list($configCost, $configHTML) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
    // ------------------- image print -----------
                        $boxImageHTML = printBoxImageHTML($row, $row3, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
    //------- check if the formation have cards for the box --------------
                        $formationCardHTMLtemp = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                        if (isset($platoonCardChange[$platoonIndex])&&isset($platoonCardChange[$platoonIndex]["html"])) {
                            $formationCardHTML .= ((!is_numeric(strpos($platoonCardChange[$platoonIndex]["html"],$formationCardHTMLtemp)))?$formationCardHTMLtemp:"");
                        } else {
                            $formationCardHTML .= $formationCardHTMLtemp;
                        }

                        // ------------ platoon cards (pioneer etc.)
                        $flag =  generateTitleImanges($insignia, $platoonCardMod[$platoonIndex]["title"]??"" . ($platoonsInForce[$platoonIndex]["title"]??"") ,(empty($row["platoonNation"])?$query['ntn']:$row["platoonNation"]));
                        $boxAllHTML[$currentBoxInFormation] .= "<span class='nation'>" . $flag . "</span><div class='images'>" . $boxImageHTML  . 
                        "</div>";
                    }
                }
                $msi = processPlatoonStats($row, $platoonIndex, $platoonSoftStats, $platoonCardMod);
    //--------------------------------------------

    //---------------- name etc. -------------  
                if (isset($platoonCardMod[$platoonIndex])) {
        $platoonsInForce[$platoonIndex]["title"] = trim(((isset($platoonCardMod[$platoonIndex]["title"])&&$platoonCardMod[$platoonIndex]["title"]!=""&&
                                                      (trim($platoonCardMod[$platoonIndex]["title"]) != 
                                                       trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
} 
                $boxAllHTML[$currentBoxInFormation] .= "
                    <div  class='title'>
                        <b> 
                        {$platoonsInForce[$platoonIndex]["title"]}
                        </b>
                        <br>
                        " . ((!is_numeric(strpos($platoonsInForce[$platoonIndex]["code"],"CP")))?$platoonsInForce[$platoonIndex]["code"]:"") . " <i>({$configCost} points)</i>
                        <br>
                    </div>
                </div>";
            
    // ------ Config of platoon -------------      
                    $boxAllHTML[$currentBoxInFormation] .= $msi . $configHTML . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
                    $boxAllHTML[$currentBoxInFormation] .= "
                <div class='Points'>
                    <div>
                        " . $boxCost[$formationNr][$currentBoxNr] . " points
                    </div>
                </div>";

                    
                echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
                $platoonIndex++;

            }
        }
    //echo "</div>";    
    //echo "</div>";   
    }
}
$formationNr+=1;
// ----------- BB support 
if ($bbEval) {
        $BBSupport_DB = $conn->query(
            "SELECT  DISTINCT platoon , title, alliedBook AS Book, unitType, Nation, relevance 
            FROM formationSupport_DB
            WHERE Book = '{$bookTitle}'
        ORDER BY platoon, relevance  desc");

    } 
if ((isset($BBSupport_DB)&&$BBSupport_DB->num_rows > 0)&&$bbEval) { //  BB support 

    
    $currentFormation="BlackBox";


    $BBSupport_DBUsed = [];
    $sqlTempStatement ="";
    $sqlStatsTempStatement = "";
    foreach ($BBSupport_DB as $currentBoxNr => $row) {
        $currentBoxInFormationOld = "{$currentFormation}-{$currentBoxNr}";
        $currentBoxInFormation = $row["platoon"]."box";
        if ((isset($query[$currentBoxInFormation])&&$query[$currentBoxInFormation] === $row["platoon"])||
            (isset($query[$currentBoxInFormationOld])&&$query[$currentBoxInFormationOld] === $row["platoon"])) {
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
        
    $platoonSoftStats= (empty($sqlStatsTempStatement))?[]:$conn->query(
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
        $formationCards= (empty($sqlStatsTempStatement))?[]:$conn->query(
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
        $currentBoxInFormationOld = $currentFormation ."-" . $currentBoxNr;
        $currentBoxInFormation = $row["platoon"]."box";

        if ((isset($query[$currentBoxInFormation])&&$query[$currentBoxInFormation] === $row["platoon"])||
            (isset($query[$currentBoxInFormationOld])&&$query[$currentBoxInFormationOld] === $row["platoon"])) {

            if (isset($query[$currentBoxInFormationOld])) {
                $currentBoxInFormation = $currentBoxInFormationOld;
            }


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
            = "<div class='box'> 
            <div class='platoon {$row["Nation"]}'>{$row["box_type"]}<br>";
            $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
            $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
            $platoonsInForce[$platoonIndex]["title"] = $row["title"];
            $platoonsInForce[$platoonIndex]["Nation"] = $row["Nation"];
            foreach ($platoonConfig as $row3) {
                if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                    $cardsHTML = "";
                    $row["nrOfTeams"] = $row3["nrOfTeams"];
                    $row["box_nr"] = $currentBoxNr;
                    $optionsHTML = generatePlatoonOptionsPrintHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $platoonsInForce, $currentBoxInFormation);
                    $row["teams"] = $row3["teams"];
                    $row["sections"] = $row3["sections"];
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    $boxImageHTML = printBoxImageHTML($row, $row3, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, (isset($platoonCardMod[$platoonIndex])?($platoonCardMod[$platoonIndex]["title"]??""):"") . $platoonsInForce[$platoonIndex]["title"], $row["Nation"]);
                    $boxAllHTML[$currentBoxInFormation] .= "<span class='nation'>" . $flag . "</span><div class='images'>" . $boxImageHTML  . 
                        "</div>";
                }
            }
            mysqli_data_seek($platoonConfig ,0);  
            $msi = processPlatoonStats($row, $platoonIndex, $platoonSoftStats, $platoonCardMod);
//--------------------------------------------

//---------------- name etc. -------------  
            if (isset($platoonCardMod[$platoonIndex])) {
    $platoonsInForce[$platoonIndex]["title"] = trim(((isset($platoonCardMod[$platoonIndex]["title"])&&$platoonCardMod[$platoonIndex]["title"]!=""&&
                                                  (trim($platoonCardMod[$platoonIndex]["title"]) != 
                                                   trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
} 
            //$platoonsInForce[$platoonIndex]["title"] = trim((((trim($platoonCardMod[$platoonIndex]["title"]) !=="")&&(trim($platoonCardMod[$platoonIndex]["title"]) !== trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
            $boxAllHTML[$currentBoxInFormation] .= "
                <div  class='title'>
                    <b>
                        " 
// ------------ Specific for BB --------                
                . $row['Book'] .": "
//--------------------------------------  
                 . "{$platoonsInForce[$platoonIndex]["title"]}
                    </b>
                    <br>
                    " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i>
                    <br>
                </div>
            </div>";
                    
// ------ Config of platoon -------------      
            $boxAllHTML[$currentBoxInFormation] .= $msi . $currentConfig . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex]["html"])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
            $boxAllHTML[$currentBoxInFormation] .= "
            <div class='Points'>
                <div>
                    " . ($boxCost[$formationNr][$row["platoon"]]??($boxCost[$formationNr][$currentBoxNr]??"")) . " points
                </div>
            </div>";
            echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
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

if ($bookSelected && (count($arrayCdPl) > 0)) { // card support 
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
            = "<div class='box'> 
            <div class='platoon {$query["ntn"]}'>{$row["box_type"]}<br>";
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
                    $optionsHTML = generatePlatoonOptionsPrintHTML($platoonOptionHeadersChanged, $platoonOptionChanged, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $platoonsInForce);
                    $row["teams"] = $row3["teams"];
                    $row["sections"] = $row3["sections"];
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $formationCardTitle, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    $boxImageHTML = printBoxImageHTML($row, $row3, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, ($formationCardTitle[$formationNr]??"") . (isset($platoonsInForce[$platoonIndex])?$platoonsInForce[$platoonIndex]["title"]:""), ((!empty($row["platoonNation"]))?$row["platoonNation"]:$query['ntn']));
                    $boxAllHTML[$currentBoxInFormation] .= "<span class='nation'>" . $flag . "</span><div class='images'>" . $boxImageHTML  . 
                        "</div>";
                }
            }
            mysqli_data_seek($platoonConfig ,0);  
            $msi = processPlatoonStats($row, $platoonIndex, $platoonSoftStats, $platoonCardMod);

//---------------- name etc. -------------  
            $boxAllHTML[$currentBoxInFormation] .= "
                <div  class='title'>
                    <b>
                        "
                 . "{$platoonsInForce[$platoonIndex]["title"]}
                    </b>
                    <br>
                        " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i>
                    <br>
                </div>
            </div>";
                    
// ------ Config of platoon -------------      
            $boxAllHTML[$currentBoxInFormation] .= $msi. $currentConfig . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
            $boxAllHTML[$currentBoxInFormation] .= "
            <div class='Points'>
                <div>
                    " . ($boxCost[$formationNr][$row["platoon"]]??($boxCost[$formationNr][$currentBoxNr]??"")) .  " points
                </div>
            </div>";

            echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
            $platoonIndex++;
        }
    }

    if ((isset($arrayCdPl)&&count($arrayCdPl) > 0)||(isset($Support_DB)&&$Support_DB->num_rows > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||$bbEval)) {
    echo "</div>";    
    echo "</div>";   
}

 // ------- cards ----------

 if  (isset($forceCard)) { // ------- cards ----------
    if (($forceCards->num_rows > 0)) {
        $temp = $forceCards->num_rows;
        foreach($forceCard as $row){
            foreach ($forceCards as $key5 => $row5) {
                if ((str_replace("'", "", $row5["card"]) == $row )||($row5["card"] == $row )||($row5["code"] == $row )) { 
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
<div class='break-inside-avoid'>
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Command Cards
        </div>
    </div>

<div class="Formation">
    <?php
    foreach ($CardsInList as $key1 => $row1) {
        if ( !empty($row1["card"])) {   
            foreach ($cards as $row2) { 
                if (( $row1["card"] != "")&&(($row1["card"] == $row2["card"] )||( $row1["card"] == strtok($row2["card"], ":") ))) {

                    if (isset($SummaryOfCards[$row1["card"]])) {

                        foreach ($SummaryOfCards[$row1["card"]] as $rowNr => $cardRow){

                            if ($cardRow != 0) {
                                $row2["points{$rowNr}"] = "
                                <div class='Points'>
                                    <div>
                                        {$cardRow} points
                                    </div>
                                </div>";
                            }
                        }
                    }
                    cardNoteParse($row2);
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
    <div class='break-inside-avoid'>
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Arsenal
        </div>
    </div>

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
        <table class='arsenal break-inside-avoid'>
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
            <tr class='break-inside-avoid'>
                <td class='imagerow break-inside-avoid' rowspan='2'  max-width: 160px;'>";
                if (isset($platoonImages[$key])) {
                    foreach (explode("|",$platoonImages[$key] ,7) as $key1 => $boxImage) 
                    echo  "<img src='img/{$boxImage}.svg'>";
                }

            echo "<br> " . 
            ((!is_numeric(strpos($platoonSoftStatRow["code"],"CP")))?$platoonSoftStatRow["code"]: $platoonRow["originalPlatoonCode"]??"") . "\n";

            echo "</td><td class='statsrow' colspan='5' style='text-align: left;'>
            <b><span class='left nation'>" . generateTitleImanges($insignia, $cardTitle . (($platoonRow["title"]=="")? $platoonSoftStatRow["title"] : $platoonRow["title"] ) . ($platoonRow["FormationCardTitle"]??""), (isset($platoonRow["Nation"]))?$platoonRow["Nation"]:(isset($platoonRow["platoonNation"])?$platoonRow["platoonNation"]:$query['ntn'])) ."</span><span>".$cardTitle .(($platoonRow["title"]=="")? $platoonSoftStatRow["title"] : $platoonRow["title"] )." </span></b><br>
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
<div class='break-inside-avoid'>
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Weapons
        </div>
    </div>        
    <div><?php //------- weapons ----------?>
   <?php         
$weaponsTeamsInForce = array_unique($weaponsTeamsInForce);

if (isset($weapons)&&$weapons->num_rows > 0) {
            echo  "
                    <table class='break-inside-avoid'>
                    <THEAD>
                        <tr><th class='{$query['ntn']}'>Image</th><th class='{$query['ntn']}' >Weapon</th><th class='{$query['ntn']}'>Range</th><th class='{$query['ntn']}'>Halted ROF</th><th class='{$query['ntn']}'>Moving ROF</th><th class='{$query['ntn']}'>Anti Tank</th><th class='{$query['ntn']}'>Firepower</th><th class='{$query['ntn']}'>Notes</th></tr>                    
                    </THEAD>
                    <TBODY>";              
foreach ($weaponsTeamsInForce as $row1) if (($row1 != "")&&(strtolower($row1)  != "komissar team")) {
    $teamImage = "";
    $waponsRow=array();
    $weaponsPerTeam=2;
    foreach ($weapons as $key => $row2) 
        if ( strtolower($row1) === strtolower($row2["team"])) {

            $weaponsRow[$key]=$row2;
            $weaponsPerTeam++;
            $teamImage = $row2["image"];
            $teamTeam = $row2["team"];
    }

    mysqli_data_seek($weapons ,0);
    if ($teamImage != "") {
        echo "
    <tr class='break-inside-avoid'>
        <td class='imagerow'  style='page-break-inside:avoid;' rowspan='{$weaponsPerTeam}'><img src='img/{$teamImage}.svg'></td>

    </tr>
            <tr style=class='break-inside-avoid'>
            <td class='teamHeader' colspan='8'>{$teamTeam}
            </td>
        </tr>";
    }
    if (isset($weaponsRow)) {

        foreach ($weaponsRow as $row2) {
        if ( (strtolower($row1) === strtolower($row2["team"]))||isset($row2["teams"])&&(strtolower($row1) === strtolower($row2["teams"])) ) {
                    
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
<div class='break-inside-avoid'>  
";
?>

<div class="collapsible <?=$query['ntn']?>">
    <div class="formHeader"> 
    Rules
    </div>
</div>    


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
                <tr class='break-inside-avoid'>
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
</body>
<script>
    let lastScrollTop = 0;
    const header = document.getElementById('main-header');
    window.addEventListener('scroll', () => {
        const currentScroll = window.scrollY || document.documentElement.scrollTop;
        if (currentScroll > lastScrollTop) {
            // Scrolling down
            header.classList.add('hiddenM');

        } else {
            // Scrolling up
            header.classList.remove('hiddenM');

        }
        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // For mobile or negative scrolling
    });



</script>
</html>
