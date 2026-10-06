<?php  header('Content-Type: text/html; charset=utf-8');
// Start the session
session_start();
$beta = ((is_numeric(strpos($_SERVER['PHP_SELF'],"Beta")))? "Beta": "");
include "sqlServerinfo{$beta}.php";
include "functions".$beta.".php";
include "login{$beta}.php";

//---- session varibale creation from GET

$parts = parse_url($_SERVER['REQUEST_URI']);
parse_str($parts['query'], $query);
$backQuery = "";
$_SESSION=[];
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

// -- all general tables 
if (!isset($_SESSION["insigniaQuery"])) {
    include_once "sqlServerinfo{$beta}.php";
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

if (!isset($_SESSION["Books"])) {
    include_once "sqlServerinfo{$beta}.php";
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

if (!isset($_SESSION["images"])) {
    include_once "sqlServerinfo{$beta}.php";
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

    foreach ($Books as $row) if (($row["code"] == $query['Book'])||($row["Book"] == $query['Book'])){
        $bookCode = $row["code"]; 
        $bookTitle = $row["Book"]; 
        $bookSelected = true;
    }
    if ($Books instanceof mysqli_result) {
        mysqli_data_seek($Books ,0);
    }
    

// -- all book specific tables 

if ($bookSelected && $bookTitle <> "")
{
    $Support = $conn->query(
       "SELECT  *
        FROM    formations 
        WHERE   formations.title LIKE '%Support%' AND Book LIKE '%" . $bookTitle . "%'");

    $Support_DB = $conn->query(
        "SELECT  *
        FROM support_DB
        WHERE   Book = '{$bookTitle}'");

    
    $BBSupport_DB = $conn->query(
        "SELECT  DISTINCT platoon , title, alliedBook AS Book, unitType, Nation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'
    GROUP by platoon
    ORDER BY relevance desc");


    $BBSupport_DBformation = $conn->query(
        "SELECT  DISTINCT platoon ,  formation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'");


    $BBSupport_type = $conn->query(
        "SELECT  DISTINCT platoon , box_type
        FROM    formationSupport_DB
        WHERE Book = '{$bookTitle}'");

    $SupporboxNrs =[];
    foreach ($Support_DB as $value) {
        $box_type = $value["box_type"];
        $box_nr = $value["box_nr"];
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

    $addToBoxCards= $conn->query("
        SELECT  DISTINCT 
            cmdCardAddToBox.Book AS Book, 
            cmdCardAddToBox.platoon AS platoon, 
            cmdCardAddToBox.cardNr AS cardNr, 
            cmdCardAddToBox.card AS card, 
            cmdCardAddToBox.formation AS formation,
            cmdCardCost.price AS cost, 
            cmdCardsText.code AS code,
            cmdCardCost.pricePerTeam AS pricePerTeam,    
            cmdCardsText.notes AS notes,
            cmdCardsText.title AS title
        FROM    cmdCardAddToBox
            LEFT JOIN cmdCardCost
                LEFT JOIN cmdCardsText
                ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
            ON cmdCardCost.Book = cmdCardAddToBox.Book AND cmdCardCost.card = cmdCardAddToBox.card 
        WHERE   cmdCardAddToBox.Book LIKE '%" . $bookTitle . "%'");  

    $platoonCards= $conn->query(
        "SELECT  *
        FROM    cmdCardPlatoonModForPrintDB
        WHERE   Book = '{$bookTitle}'");

    $unitCards= $conn->query(
        "SELECT  *
        FROM    cmdCardUnitModForPrintDB
        WHERE   Book = '{$bookTitle}'");


    $forceCards= $conn->query("
        SELECT  DISTINCT 
            cmdCardsForceMod_link.Book AS Book,
            cmdCardsForceMod_link.card AS card,  
            cmdCardsText.code AS code,
            cmdCardsText.notes AS notes,
            cmdCardsText.title AS title
        FROM    cmdCardsForceMod_link
                LEFT JOIN cmdCardsText
            ON cmdCardsText.Book = cmdCardsForceMod_link.Book AND cmdCardsText.card = cmdCardsForceMod_link.card 
        WHERE   cmdCardsForceMod_link.Book LIKE '%" . $bookTitle . "%'");  

        
    $weapons = $conn->query(
       "SELECT  *
        FROM    weapons 
        LEFT    JOIN weaponsLink
            ON      weapons.weapon = weaponsLink.weapon");

    $rules = $conn->query("
        SELECT  *
        FROM    rules ");    

    $cards = $conn->query("
        SELECT  * 
        FROM    cmdCardsText 
        WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'");
}

if ($bookSelected && $query['Book'] <> "")  {

    $platoonSoftStats= $conn->query("
        SELECT  * 
        FROM    platoonsStats");

        $platoonOptionOptions= $conn->query("
        SELECT  * 
        FROM    platoonOptions");
$platoonOptionHeaders = [];
    foreach ($platoonOptionOptions as $value) {
        $code = $value["code"];
        $description = $value["description"];
        // Check if the combination of Nation and period exists in the unique array
        if (!in_array(["code" => $code,"description" => $description], $platoonOptionHeaders)) {
            $platoonOptionHeaders[]  = ["code" => $code,"description" => $description];
        }
    }
    mysqli_data_seek($platoonOptionOptions, 0);
}

$platoonsInForce=[];
$attachmentsInForce=[];
$weaponsTeamsInForce=[];
$rulesInForce=[];

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=0.35">
    <link rel='stylesheet' href='css/listPrint.css'>
 
    <script src="jquery-3.7.0.min.js"></script>

    <script>
        const resizeOps = () => {
            document.documentElement.style.setProperty("--vw", window.innerWidth * 0.01 + "px");
            document.documentElement.style.setProperty("--vh", window.innerHeight * 0.01 + "px");
        };
        resizeOps();
        window.addEventListener("resize", resizeOps);
    </script>

    <?php
    // Menu
    ?>


    <title>FOW List - <?php echo "{$bookTitle} - {$query['F1']}" ?></title>
    <link rel="icon" type="image/x-icon" href="/img/<?=$query["ntn"]?>.svg">
</head>
<body>
<?php include "menu.php"; ?>

    <div class="page-container">
    <button onclick="printPage()">Print This Page</button>reserves: <span id="reservesPoints"><?php echo ($FormationCost != "") ? round((array_sum($FormationCost) + $listCost)*0.4) : "" ?></span>
    <?php
    //foreach ($platoonCards as $row) {echo $row["card"];}
    // ----- temporary button during dev.. --------
    ?>

    <div class="header">
        <h2 class="<?=$query['ntn']?>"> 
            <?=$bookTitle?>    
            <div class='Points'>
                <div class='Points1'>
                    <span id="totalPoints"><?php echo ($FormationCost != "") ? array_sum($FormationCost) + $listCost : "" ?></span> points
                </div>
            </div>
        </h2>
    </div>
    <?php
// ---------------------------------------------------
//  ------------------- Formation --------------------
// ---------------------------------------------------
if ($bookSelected) {
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
    
            $Formation_DB=[];
            $Formation_DBSql = $conn->query("
                SELECT  * 
                FROM    formation_DB 
                WHERE   formation LIKE '%{$currentFormationCode}%'");
            foreach ($Formation_DBSql as $key => $value) {
                $Formation_DB[] = $value;
            }
            if (isset($query[$currentFormation])) {
                $cardPlatoon = $conn->query(
                    "SELECT  boxType as box_type, 
                             platoon,
                             cardNr,
                             Book,
                             formation,
                             configChange,
                             boxNr as box_nr,
                             card as title
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
                        $Formation_DB[] = $value;
                    }
                    $formationLookup1 ="";
                    $cardPlatoonConfig= $conn->query(
                        "SELECT  *
                        FROM    platoonConfigDB
                        WHERE platoon = '" . $value["platoon"] . "'");
                        
                    foreach ($cardPlatoonConfig as $key1 => $value1) {
                        echo "<!--a asdf asdf {$value1['platoon']}-->";
                        if (($value['platoon'] == $value1['platoon'])&&(($formationLookup1=="")||($formationLookup1==$value1["formation"]))) {
                            
                            $platoonConfig[] = $value1;
                            $formationLookup1 = $value1["formation"];
                        }
                    }
                    mysqli_data_seek($cardPlatoonConfig ,0);
                }
                mysqli_data_seek($cardPlatoon ,0);
                $tempArr = array_unique(array_column($platoonConfig, 'shortID'));
                $platoonConfig = array_intersect_key($platoonConfig,$tempArr);
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
        <button type="button" class="collapsible">  
            <h3 class="<?=$query['ntn']?>"> <?php 
        //---- Title print 
            if ((isset($formationCardTitle[$formationNr]))||(is_numeric(strpos($query[$currentFormation],"C")))) {
                echo "<div class='left'><img class='card' src='img/Card.svg'>". "</div>";
            }
            echo "<div class='left'>" . generateTitleImanges($insignia, $formationCardTitle[$formationNr]
                . $formationTitle[$formationNr], $query["ntn"]) . "</div>"
                . "<div class='mid'>" . ((isset($formationCardTitle[$formationNr])&&($formationCardTitle[$formationNr]!=$formationTitle[$formationNr])) ? "\n \t {$formationCardTitle[$formationNr]}: ": "");
            
            echo $formationTitle[$formationNr] . "</div>"; ?>
            </h3>
            <span class="right">    
                <div class='Points'>
                    <div class='Points1'>
                        <?=$FormationCost[$formationNr]?> points 
                    </div>
                </div> 
            </span>
    
        </button>
        <div class='Formation'>
            <div class="grid">
        <?php
        //----- generate boxes
        $boxOpen = FALSE;
        $secondOpen =false;
        $boxNrInFormation = 0;
    
            foreach ($Formation_DB as $row) {
        // --- set general box nr variable
                $currentBoxNr = $row["box_nr"];
                $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
                if (($row["formation"] == $currentFormationCode) && isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                    $boxNrInFormation++;
                    $boxAllHTML[$currentBoxInFormation]
                    = "<div class='box'>
                    <div class='platoon {$query["ntn"]}'>{$row["box_type"]}<br>";
                    $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                    $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                    $platoonsInForce[$platoonIndex]["title"] = $row["title"];
                    $configCost =0;
                    $configHTML ="";
                    $optionsHTML = "";
                    $formationCardHTML ="";
                    $cardsHTML ="";
                    foreach ($addToBoxCards as $key => $value) {
                        if (($row["formation"] ==$value["formation"])&&($row["platoon"]==$value["platoon"])) {
                            //echo "<!-- debug -->";
                            $CardsInList[] = $value;
                        }
                    }
                    mysqli_data_seek($addToBoxCards ,0); 
                    
                    $platoonConfigChanged = [];
                    if (isset($row["configChange"])&&$row["configChange"]!="") {
                        echo "<!--here {$row["configChange"]}-->";
                        $configChangeRow = explode("\n",$row["configChange"],10);
                        foreach ($configChangeRow as $key => $value) {
                            $temp = explode("|",$value);
                            $platoonConfigChanged[] = array(
                                "platoon" => $row["platoon"], 
                                "configuration" => str_replace("//","\n",$temp[0]), 
                                "cost" => $temp[1], 
                                "sections" => str_replace("!","|",$temp[2]), 
                                "shortID" => $temp[3],
                                "image" => str_replace("!","|",$temp[4]),
                                "teams" => str_replace("!","|",$temp[5])
                            );
                        }
                    }
                    else {
                        $platoonConfigChanged = $platoonConfig;
                    }
                    foreach ($platoonConfigChanged as $row3) {
                        
                        if (($row["platoon"]==$row3["platoon"])&&isset($query[$currentBoxInFormation . "c"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                            $row["nrOfTeams"] = $row3["nrOfTeams"];
                            $cardsHTML = "";

                            printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                            list($configCost, $configHTML) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
        // ------------------- image print -----------
                            list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                            list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
        //------- check if the formation have cards for the box --------------
                            $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                            // ------------ platoon cards (pioneer etc.)
                            $flag =  generateTitleImanges($insignia, $formationCardTitle[$formationNr] . $platoonsInForce[$platoonIndex]["title"] , $query['ntn']);
                            
                            $boxAllHTML[$currentBoxInFormation] .= 
                            $flag . $temp1 . 
                            (isset($platoonCardMod[$platoonIndex])?$platoonCardMod[$platoonIndex]["image"]??"":"");
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
                                                                       trim($platoonsInForce[$platoonIndex]["title"])))? $platoonCardMod[$platoonIndex]["title"] . ": ": "") . $platoonsInForce[$platoonIndex]["title"]);
                    } 

                    $boxAllHTML[$currentBoxInFormation] .= "
                        <div  class='title'>
                            <b> 
                                {$platoonsInForce[$platoonIndex]["title"]}
                            </b>
                            <br>
                            " . ((!is_numeric(strpos($row["platoon"],"CP")))?$row["platoon"]:"") . " <i>({$configCost} points)</i>
                            <br>
                        </div>
                    </div>";
    
        // ------ Config of platoon -------------      
                    $boxAllHTML[$currentBoxInFormation] .= $configHTML . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
                        // -------- Points ----------  
                    $boxAllHTML[$currentBoxInFormation] .= "
                    <div class='Points' onclick='togglePoints(this)'>
                        <div>
                            " . $boxCost[$formationNr][$currentBoxNr] . " points
                        </div>
                    </div>";
                    //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 
    
                    $boxAllHTML[$currentBoxInFormation] .= "</div>";
    
    
                    echo $boxAllHTML[$currentBoxInFormation] ;
                    $platoonIndex++;
                }
    
            } 
            if ($platoonConfig instanceof mysqli_result) {
                mysqli_data_seek($platoonConfig ,0);
            }
            echo "</div>";
            echo "</div>    
            <br>";  
    
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
if ($bookSelected && $bookTitle !="") {
        $cardPlatoon = $conn->query("
        SELECT  boxType as box_type, 
                platoon,
                cardNr,
                Book,
                formation,
                configChange,
                boxNr as box_nr,
                card as title
        FROM    cmdCardAddToBox  
        WHERE   Book LIKE '%" . $bookTitle . "%'
        AND     formation LIKE '%Support%'");

$platoonConfig= $conn->query(
            "SELECT  *
            FROM    platoonConfigSupportDB
            WHERE formation LIKE '%{$bookTitle}%'");

//Card platoon 
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
    $combinedSupportDB =[];
            
    foreach ($Support_DB as $value) {
        $combinedSupportDB[] = $value;
    }
    if ($cardPlatoon->num_rows > 0) {
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
        }
        mysqli_data_seek($cardPlatoon ,0);
    }

    if ((count($arrayCdPl) > 0)||(count($combinedSupportDB) > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||(is_numeric(strpos($parts['query'], "BlackBox"))))) {
    ?>
    <button type="button" class="collapsible">  
        <h3  class="<?=$query['ntn']?>">     
            <?php foreach ($Support as $row) echo $row["title"]; mysqli_data_seek($Support ,0); ?>
        </h3>
        <span class="right">    
            <div class='Points'>
                <div class='Points1'>
                    <?php echo ($FormationCost[$formationNr] + ($FormationCost[$formationNr + 1 ])+ ($FormationCost[$formationNr + 2])) ?> points 
                </div>
            </div> 
        </span>
    </button>

    <div class='Formation'>
    <div class="grid">
    <?php
    }
    
    if ((count($combinedSupportDB) > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||(is_numeric(strpos($parts['query'], "BlackBox"))))) {
    //----- generate boxes
        foreach ($combinedSupportDB as $row) {

            $currentBoxNr = $row["box_nr"];
            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            $boxAllHTML[$currentBoxInFormation] ="";
            if (isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {
                
                $boxAllHTML[$currentBoxInFormation] 
                .= "<div class='box'>
                <div class='platoon {$query["ntn"]}'>{$row["box_type"]}<br>";
                $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
                $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
                $platoonsInForce[$platoonIndex]["title"] = $row["title"];

                $platoonConfigChanged = [];
                if (isset($row["configChange"])&&($row["configChange"] !="")) {
                    $configChangeRow = explode("\n",$row["configChange"],10);
                    foreach ($configChangeRow as $key => $value) {
                        echo "<!--{$value}-->";
                        $temp = explode("|",$value);
                        $platoonConfigChanged[] = array(
                            "platoon" => $row["platoon"], 
                            "configuration" => str_replace("//","\n",$temp[0]), 
                            "cost" => $temp[1], 
                            "sections" => str_replace("!","|",$temp[2]), 
                            "shortID" => $temp[3],
                            "image" => str_replace("!","|",$temp[4]),
                            "teams" => str_replace("!","|",$temp[5])
                        );
                    }
                }
                else {
                    $platoonConfigChanged = $platoonConfig;
                }
                foreach ($platoonConfigChanged as $row3) {
                    if (($row["platoon"]==$row3["platoon"])&&($row3["shortID"] === $query[$currentBoxInFormation . "c"])) {
                        $cardsHTML = "";
                        $row["nrOfTeams"] = $row3["nrOfTeams"];
                        printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                        list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
    // ------------------- image print -----------
                        list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                        list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
    //------- check if the formation have cards for the box --------------
                        $formationCardHTML = printFormationCardsHTML([], $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                        // ------------ platoon cards (pioneer etc.)
                        $flag =  generateTitleImanges($insignia, (isset($platoonCardMod[$platoonIndex])? $platoonCardMod[$platoonIndex]["title"]:"") . (isset($platoonsInForce[$platoonIndex])?$platoonsInForce[$platoonIndex]["title"]:"") , $query['ntn']);
                        $boxAllHTML[$currentBoxInFormation] .= $flag . $temp1 . (isset($platoonCardMod[$platoonIndex])?($platoonCardMod[$platoonIndex]["image"]??""):"");
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
                    $boxAllHTML[$currentBoxInFormation] .= $currentConfig . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
                    $boxAllHTML[$currentBoxInFormation] .= "
                <div class='Points' onclick='togglePoints(this)'>
                    <div>
                        " . $boxCost[$formationNr][$currentBoxNr] . " points
                    </div>
                </div>";
                    //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 

                echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
                $platoonIndex++;

            }
        }
    //echo "</div>";    
    //echo "</div>";   

    }
}
// ----------- BB support 

$formationNr+=1;
$currentFormation="BlackBox";

if ($bookSelected && $bookTitle <> "") {
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
    WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'");  
}

if (($BBSupport_DB->num_rows > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||(is_numeric(strpos($parts['query'], "BlackBox"))))) {

    foreach ($BBSupport_DB as $currentBoxNr => $row) {
        $currentFormationCode ="";
        foreach($BBSupport_type as $row4) {
            if ($row4["platoon"] == $row["platoon"]){
                //$thisBoxType = $row4["box_type"];
                $row["box_type"] = $row4["box_type"];
                $row["box_nr"] = $currentBoxNr;
            }
        }
// --- set general box nr variable
        $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
        if (isset($query[$currentBoxInFormation])&&($query[$currentBoxInFormation] === $row["platoon"])) {

// ------------ Specific for BB --------                
            foreach ($BBSupport_DBformation as $row4) {
                if ($row4["platoon"] == $row["platoon"]) {
                    if (!isset($row["formation"])) {
                        $row["formation"] = $row4["formation"];
                    } else {
                        $row["formation"] .= $row4["formation"];
                    } 
                }
            }
            mysqli_data_seek($BBSupport_DBformation ,0);  
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
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $platoonCardMod, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, (isset($platoonCardMod[$platoonIndex])?($platoonCardMod[$platoonIndex]["title"]??""):"") . $platoonsInForce[$platoonIndex]["title"], $row["Nation"]);
                    $boxAllHTML[$currentBoxInFormation] .= $flag . $temp1 . (isset($platoonCardMod[$platoonIndex])?$platoonCardMod[$platoonIndex]["image"]??"":"");
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
            $boxAllHTML[$currentBoxInFormation] .= $currentConfig . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
            $boxAllHTML[$currentBoxInFormation] .= "
            <div class='Points' onclick='togglePoints(this)'>
                <div>
                    " . $boxCost[$formationNr][$currentBoxNr] . " points
                </div>
            </div>";
            //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 
            echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
            $platoonIndex++;
        }
    }
}

// ----------- card support 

$formationNr+=1;
$currentFormation="CdPl";
$currentBoxNr = 0;


if ($bookSelected && $bookTitle <> "")
{
    $cardSupport = $conn->query("
    SELECT  boxType as box_type, 
            platoon,
            cardNr,
            Book,
            boxNr,
            configChange,
            card as title
    FROM    cmdCardAddToBox  
    WHERE   Book LIKE '%" . $bookTitle . "%'
    AND     formation LIKE '%Support%'");
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
                     $platoonsInForce[$platoonIndex]["title"] = $value["title"];
                     $row["configChange"] = $value["configChange"];
                }
            }
            $boxAllHTML[$currentBoxInFormation] 
            = "<div class='box'> 
            <div class='platoon {$query["ntn"]}'>{$row["box_type"]}<br>";
            $platoonsInForce[$platoonIndex]["code"] = $row["platoon"];
            $platoonsInForce[$platoonIndex]["platoonIndex"] = $platoonIndex;
            $platoonConfigChanged = [];
$platoonConfig = $conn->query(
                "SELECT  *
                FROM    platoonConfig
                LEFT    JOIN    (   SELECT DISTINCT unitType, platoon
                                    FROM formation_DB )
                                AS
                                unitT
                        ON unitT.platoon = platoonConfig.platoon
                WHERE platoonConfig.platoon = '{$row["platoon"]}'");
            if ($row["configChange"]!="") {
                $configChangeRow = explode("\n",$row["configChange"]);
                foreach ($configChangeRow as $key => $value) {
                    $temp = explode("|",$value);
                    $platoonConfigChanged[] = array(
                        "platoon" => $row["platoon"], 
                        "configuration" => str_replace("//","\n",$temp[0]), 
                        "cost" => $temp[1], 
                        "sections" => str_replace("!","|",$temp[2]), 
                        "shortID" => $temp[3],
                        "image" => str_replace("!","|",$temp[4]),
                        "teams" => str_replace("!","|",$temp[5])
                    );
                }
            }
            else {
                $platoonConfigChanged = $platoonConfig;
            }
            foreach ($platoonConfigChanged as $row3) {
                if (($row["platoon"]==$row3["platoon"])&&($row3["shortID"] === $row["shortID"])) {
                    $cardsHTML = "";
                    
                    $row["nrOfTeams"] = $row3["nrOfTeams"]??0;
                    printPlatoonCardHTML($platoonCards, $row, $query, $currentBoxInFormation, $platoonIndex, $platoonCardChange, $platoonCardMod, $CardsInList, $platoonsInForce, $formationCardTitle, $attachmentsInForce);
                    list($configCost, $currentConfig) = configPrintHTML($row3, $platoonIndex, $weaponsTeamsInForce, $attachmentsInForce, $platoonCardMod);
// ------------------- image print -----------
                    list($cardImage, $cardsHTML) = printPlatoonUnitCardHTML($unitCards, $row, $query, $formationCardTitle, $attachmentsInForce, $CardsInList, $currentBoxInFormation, $platoonIndex);
                    list($temp1,$optionsHTML) = printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions, $row, $row3, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $cardImage, $platoonCardMod);
//------- check if the formation have cards for the box --------------
                    $formationCardHTML = printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, $platoonCardMod, $row, $row3, $query, $CardsInList, $platoonsInForce, $attachmentsInForce);
                    // ------------ platoon cards (pioneer etc.)
                    $flag =  generateTitleImanges($insignia, ($formationCardTitle[$formationNr]??"") . (isset($platoonsInForce[$platoonIndex])?$platoonsInForce[$platoonIndex]["title"]:""), $query['ntn']);
                    $boxAllHTML[$currentBoxInFormation] .= $flag . $temp1 . (isset($platoonCardMod[$platoonIndex])?($platoonCardMod[$platoonIndex]["image"]??""):"");
                }
            }
            mysqli_data_seek($platoonConfig ,0);  

//--------------------------------------------
           // echo "<!--here: {$formationCardHTML} -->";
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
            $boxAllHTML[$currentBoxInFormation] .= $currentConfig . $optionsHTML . $formationCardHTML . (isset($platoonCardChange[$platoonIndex])?$platoonCardChange[$platoonIndex]["html"]:"") . $cardsHTML;
            $boxAllHTML[$currentBoxInFormation] .= "
            <div class='Points' onclick='togglePoints(this)'>
                <div>
                    " . $boxCost[$formationNr][$currentBoxNr] . " points
                </div>
            </div>";
            //$boxAllHTML[$currentBoxInFormation] .= printPointsAndIsHitOnHTML($platoonSoftStats, $row, $platoonCardMod, $platoonIndex); 
            echo $boxAllHTML[$currentBoxInFormation] .= "</div>";
            $platoonIndex++;
        }
    }



    if ((count($arrayCdPl) > 0)||($Support_DB->num_rows > 0)&&(((is_numeric(strpos($parts['query'], "Sup"))))||(is_numeric(strpos($parts['query'], "BlackBox"))))) {
    echo "</div>";    
    echo "</div>";   
}

 // ------- cards ----------

 if  (isset($forceCard)) {
    if (($forceCards->num_rows > 0)) {
        foreach($forceCard as $row){
            foreach ($forceCards as $key5 => $row5) {
                if ((str_replace("'", "", $row5["card"]) == $row )||($row5["card"] == $row )) { 
                    array_push($CardsInList,$row5);
                }
            }
        }
        mysqli_data_seek($forceCards ,0);
    }


    foreach ($CardsInList as $key => $value) {
        if (strtok($value["card"], ":")) {
            $CardsInList[$key]["card"] = strtok($value["card"], ":");
        }
    }
}
if (count($CardsInList) > 0) {

    $tempArr = array_unique(array_column($CardsInList, 'card'));
    $CardsInList = array_intersect_key($CardsInList,$tempArr);     
?>

<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Command Cards</h3>
</button>

<?php  

echo "
<div>  
";
            echo  "                    
                    <table>
                    <THEAD>           
                    </THEAD>
                    <TBODY>";
    foreach ($CardsInList as $row1) {
        if ( $row1["card"] != "")    {    echo "
                <tr>
                    <td style='text-align: left;'>";
                    echo "
                    <b>".$row1["card"]." </b>
                    </td>";
        foreach ($cards as $row2) {

            if (( $row1["card"] != "")&&(($row1["card"] == $row2["card"] )||( $row1["card"] == strtok($row2["card"], ":") ))) {
                $colSpanNr = 3;
                if ($row2["unitModifier"]) {
                    $colSpanNr--;
                }
                if ($row2["statsModifier"]) {
                    $colSpanNr--;
                }
                echo "
                    <td colspan='" . ($colSpanNr) . "' style='text-align: left;'>
                        <b>".str_replace("\n","<br>", $row2["notes"]) ." </b>
                    </td>";
                if ($row2["unitModifier"]) {
                    echo "<td style='text-align: left;'>
                    <b>".str_replace("\n","<br>", $row2["unitModifier"]) ." </b>
                </td>";
                }
                if ($row2["statsModifier"]) {
                    echo "                    <td style='text-align: left;'>
                    <b>".str_replace("\n","<br>", $row2["statsModifier"]) ." </b>
                </td>   ";
                }
                    
                 echo "
                </tr>";
                break;
            }
        }
    }
    mysqli_data_seek($cards ,0);
}
            echo "
            <TBODY>
            </table>

            </div>   ";  
}

if ($bookSelected) {
//------------------------------------
// ------- soft stats ----------
//------------------------------------
            
    ?>
    <div style='page-break-inside:avoid;'>
    <button type="button" class="collapsible">  
        <h3 class="<?=$query['ntn']?>">Arsenal</h3>
    </button>
    <?php

    foreach ($attachmentsInForce as $key => $attachmentRow) {
$attachmentsInForce[$key]["attachment"] = "attachment";
        foreach ($platoonConfig as $row3) {
            if ($attachmentRow["code"]==$row3["platoon"]) {
                
                if ($row3["teams"] !="") {
                    foreach (explode("|",$row3["teams"],7) as $key1 => $boxTeams){
                        $weaponsTeamsInForce[] = $boxTeams;
                    }
                }
            }
        }

        foreach ($platoonSoftStats as $row3) {
            if ($attachmentRow["code"]==$row3["code"]) {
                $attachmentsInForce[$key]["title"] = $row3["title"];
                        if (!empty($platoonCardMod[$attachmentRow['platoonIndex']]["title"])){
                            $attachmentsInForce[$key]["title"] = $platoonCardMod[$attachmentRow['platoonIndex']]["title"] . ": " . $attachmentsInForce[$key]["title"];
                        } 
            }
        }
    }

    $platoonsInForce = array_merge($platoonsInForce , $attachmentsInForce); // could add $attachmentCardMod but not working i guess
    $tempArr = array_unique(array_column($platoonsInForce, "title"));

    $platoonsInForce = array_intersect_key($platoonsInForce,$tempArr); 
    //$platoonsInForce = array_unique($platoonsInForce, SORT_REGULAR);
}
if ($platoonSoftStats->num_rows > 0) {
    echo  "
        <table class='arsenal' style='page-break-inside:avoid; '>
        <THEAD>
            <tr><th class='{$query['ntn']}' rowspan='2'>Image</th><th class='{$query['ntn']}'>TACT.</th><th class='{$query['ntn']}'>TERR. DASH</th><th class='{$query['ntn']}'>CROSS C. DASH</th><th class='{$query['ntn']}'>ROAD DASH</th><th class='{$query['ntn']}'>CROSS</th><th class='{$query['ntn']}' rowspan='2'>MOTIVATION </th><th class='{$query['ntn']}' rowspan='2'> SKILL</th><th class='{$query['ntn']}' rowspan='2'>ARMOUR/SAVE</th></tr>
        </THEAD>
        <TBODY>";            
foreach ($platoonsInForce as $key => $row1) {
    if (isset($row1['originalPlatoonCode'])||isset($row1["replaceImgWith"])) {
        $platoonImages[$key] = $row1["replaceImgWith"];
    } else {
        foreach ($images as $row2) {
            if  (($row1['code']==$row2['code'])) $platoonImages[$key] = $row2['image'];
        }
    }
    if ($images instanceof mysqli_result) {
        mysqli_data_seek($images ,0);
    }
    foreach ($platoonSoftStats as $row2) { 
        if (( $row1['code'] === $row2["code"])&&($row2["TACTICAL"]!="")) {

            foreach ($rules as $row3) {
                if (is_numeric(strrpos(strtoupper($row2["Keywords"]), strtoupper($row3["name"]),-1))) {
                    $rulesInForce[] =  $row3["name"];
                }
            }
            if (!empty($platoonCardMod[$row1['platoonIndex']]["title"])){
                $cardTitle = " <img src='img/cardSmall.svg'>" 
                . ((!is_numeric(strrpos($row1["title"], $platoonCardMod[$row1['platoonIndex']]["title"])))?$platoonCardMod[$row1['platoonIndex']]["title"] . ": ":"");
            } 
            else  {
                $cardTitle ="";
            }
            echo "
            <tr style='page-break-inside:avoid;'>
                <td rowspan='2' style='page-break-inside:avoid; max-width: 160px;'>";
            foreach (explode("|",$platoonImages[$key] ,7) as $key1 => $boxImage) 
                echo  "<img src='img/" . $boxImage . ".svg'>";
            echo "<br> " . 
            ((!is_numeric(strpos($row2["code"],"CP")))?$row2["code"]: $row1["originalPlatoonCode"]??"") . "\n";

            echo "</td><td colspan='5' style='text-align: left;'>
            <b><span class='left'>" . generateTitleImanges($insignia, $cardTitle . (($row1["title"]=="")? $row2["title"] : $row1["title"] ), (isset($row1["Nation"]))?$row1["Nation"]:$query['ntn']) ."</span><span>".$cardTitle .(($row1["title"]=="")? $row2["title"] : $row1["title"] )." </span></b><br>
                " . $row2["Keywords"]."</td>
            <td rowspan='2'>";
//------------------ Motivation ------------------------
            
            if (!empty($platoonCardMod[$row1['platoonIndex']]["replaceMotivation"])&&(!isset($row1["attachment"]))){
                $platoonMotivation = $row2["MOTIVATION"];
 
                foreach (explode("|",$platoonCardMod[$row1['platoonIndex']]["replaceMotivation"] ,7) as $key1 => $motivationOldParts) {
                    $motivationReplaceParts = explode("|",$platoonCardMod[$row1['platoonIndex']]["motivation"] ,7);
                    $platoonMotivation = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" .  str_replace($motivationOldParts, $motivationReplaceParts[$key1], $platoonMotivation);
                }
                
            } elseif (!empty($platoonCardMod[$row1['platoonIndex']]["motivation"])&&(!isset($row1["attachment"]))){

                $platoonMotivation = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]["motivation"];
            } elseif (!empty($platoonCardMod[$row1['platoonIndex']]["attachment"]["motivation"])&&(isset($row1["attachment"]))){

                $platoonMotivation = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]["attachment"]["motivation"];
            } elseif (!empty($platoonCardMod[$row1['platoonIndex']]["attachment"]["replaceMotivation"]) && (isset($row1["attachment"]))) {
                $platoonMotivation = $row2["MOTIVATION"];
 
                foreach (explode("|",$platoonCardMod[$row1['platoonIndex']]["attachment"]["replaceMotivation"] ,7) as $key1 => $motivationOldParts) {
                    $motivationReplaceParts = explode("|",$platoonCardMod[$row1['platoonIndex']]["attachment"]["motivation"] ,7);
                    $platoonMotivation = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" .  str_replace($motivationOldParts, $motivationReplaceParts[$key1], $platoonMotivation);
                }
                
            }
            else  {
                $platoonMotivation = $row2["MOTIVATION"];
            }
            motivationBox($platoonMotivation);
            echo "</td>";

//---------------------Skill ------------------------------

            echo "<td rowspan='2'>";
            if (!empty($platoonCardMod[$row1['platoonIndex']]["replaceSkill"])&&(!isset($row1["attachment"]))) {

                $platoonSkill = $row2["SKILL"];
                foreach (explode("|",$platoonCardMod[$row1['platoonIndex']]["replaceSkill"] ,7) as $key1 => $skillOldParts) {
                    $skillReplaceParts = explode("|",$platoonCardMod[$row1['platoonIndex']]["skill"] ,7);
                    $platoonSkill =  "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . str_replace($skillOldParts, $skillReplaceParts[$key1], $platoonSkill);
                }
            } elseif (!empty($platoonCardMod[$row1['platoonIndex']]["skill"])&&(!isset($row1["attachment"]))) {
                echo "<!--{$platoonCardMod[$row1['platoonIndex']]["skill"]}-->";
                $platoonSkill =     "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]["skill"];
} elseif (!empty($platoonCardMod[$row1['platoonIndex']]["attachment"]["skill"])&&(isset($row1["attachment"]))) {

                $platoonSkill =     "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]["attachment"]["skill"];
                echo "<!--{$platoonSkill}-->";
            } else  {
                $platoonSkill = $row2["SKILL"];
            }
            motivationBox($platoonSkill);
            echo "
                
            </td>";

//-------------------Save ------------------------------------

            echo "<td rowspan='2'>";
            
                if (isset($platoonCardMod[$row1['platoonIndex']])&&$platoonCardMod[$row1['platoonIndex']]['isHitOn']!=""&&(!isset($row1["attachment"]))) {
                    $platoonIsHitOn = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]['isHitOn'];
                } elseif (isset($platoonCardMod[$row1['platoonIndex']])&&isset($platoonCardMod[$row1['platoonIndex']]["attachment"])&&is_array($platoonCardMod[$row1['platoonIndex']]["attachment"])&&$platoonCardMod[$row1['platoonIndex']]["attachment"]['isHitOn']!=""&&(isset($row1["attachment"]))) {
                    $platoonIsHitOn = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $platoonCardMod[$row1['platoonIndex']]["attachment"]['isHitOn'];
                } else {
                    $platoonIsHitOn = $row2["IS_HIT_ON"];
                }
                motivationBox($platoonIsHitOn);

                if (isset($platoonCardMod[$row1['platoonIndex']])&&(isset($platoonCardMod[$row1['platoonIndex']]["replaceSave"])&&$platoonCardMod[$row1['platoonIndex']]["replaceSave"] == "save")){
                    $platoonSave = $platoonCardMod[$row1['platoonIndex']];
                    $platoonSave["movementCardChange"] = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>";
                } else {
                    $platoonSave = $row2;
                    $platoonSave["movementCardChange"] = "";
                }

                echo "<div class='Armour'>".$platoonSave["movementCardChange"];
            foreach (explode("\n",$platoonSave["ARMOUR_SAVE"],3) as $key1 => $row3){
                    echo "<div>
                        <span class='bgBlack'>". substr($row3, 0 , strrpos($row3, " ")) . " </span> <span class='right'>" . substr($row3, strrpos($row3, ' ') + 1) . "</span><br>
                    </div>";
            }
            echo "
                </div>
            </td>
        </tr>";

//--------------------Speed --------------------------------------
            if ((isset($platoonCardMod[$row1['platoonIndex']])&&isset($platoonCardMod[$row1['platoonIndex']]["replaceMovement"])&&$platoonCardMod[$row1['platoonIndex']]["replaceMovement"] == "movement")){
                $platoonMovement = $platoonCardMod[$row1['platoonIndex']];
                $platoonMovement["movementCardChange"] = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>";
            } else {
                $platoonMovement = $row2;
                $platoonMovement["movementCardChange"]="";
            }


                $smaller = ($platoonMovement["TACTICAL"]=="UNLIMITED")?" style='font-size: x-small;' ":"";
                echo "
                <tr>
                    <td>
                        <b $smaller>". $platoonMovement["movementCardChange"].str_replace("/", " / ", $platoonMovement["TACTICAL"])." </b>
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
            //foreach ($row2 as $col => $val) {
                    
              //  echo  $col." = ".$val."<br>";
            //}

        }
    }
    mysqli_data_seek($platoonSoftStats ,0);
}
}
//            echo "            </TBODY>            </table>";
            echo "
            </TBODY>
            </table>
"; 
 
 // ------- weapons ----------

?>
</div>
<br>
<div style="break-inside: avoid-page;">
<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Weapons</h3>
</button>

<?php            
echo "            
<div>

   ";
$weaponsTeamsInForce =array_unique($weaponsTeamsInForce);
            
if ($weapons->num_rows > 0) {

            echo  "
                    <table style='page-break-inside:avoid;'>
                    <THEAD>
                        <tr><th class='{$query['ntn']}'>Image</th><th class='{$query['ntn']}' >Weapon</th><th class='{$query['ntn']}'>Range</th><th class='{$query['ntn']}'>Halted ROF</th><th class='{$query['ntn']}'>Moving ROF</th><th class='{$query['ntn']}'>Anti Tank</th><th class='{$query['ntn']}'>Firepower</th><th class='{$query['ntn']}'>Notes</th></tr>                    
                    </THEAD>
                    <TBODY>";              
foreach ($weaponsTeamsInForce as $row1) if (( $row1 <> "")&&( $row1 <> "Komissar team")&&( $row1 <> "Komissar Team")&&( $row1 <> "komissar team")) {               
    $teamImage = "";
    $waponsRow=array();
    $weaponsPerTeam=1;
    foreach ($weapons as $key => $row2) 
        if ( $row1 === $row2["team"]) {
            $weaponsRow[$key]=$row2;
            $weaponsPerTeam++;
            $teamImage = $row2["image"];
    }

    mysqli_data_seek($weapons ,0);
    if ($teamImage != "") {
        echo "
    <tr style='page-break-inside:avoid;'>
        <td class='imagerow'  style='page-break-inside:avoid;' rowspan='".$weaponsPerTeam . "'><img src='img/" . $teamImage . ".svg'></td>

    </tr>";
    }
    if (isset($weaponsRow)) {

        foreach ($weaponsRow as $row2) {
        if ( ($row1 === $row2["team"])||isset($row2["teams"])&&($row1 === $row2["teams"]) ) {
                    
                    foreach ($rules as $row3) {
                        if (isset($row2["notes"])&&$row2["notes"] != ""&&isset($row3["name"])&&is_numeric(strrpos(strtoupper($row2["notes"]), strtoupper($row3["name"]),-1))) {
                            $rulesInForce[] =  $row3["name"];
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

    mysqli_data_seek($platoonSoftStats ,0);
}
            echo "
            </TBODY>
            </table>
            </div></div>   "; 
           

 
// ------- rules ----------

?>


<button type="button" class="collapsible">  
    <h3 class="<?=$query['ntn']?>">Rules</h3>
</button>

<?php  

echo "
<div>  
";
   
$rulesInForce = array_unique($rulesInForce);        

if ($rules->num_rows > 0) {

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
                <tr>
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
setTimeout(function() {
var grids = document.querySelectorAll(".grid");
grids.forEach(function(grid) {
    var boxes = grid.querySelectorAll(".box");

    // Delay the measurement
    var gridHeight = 25;
    for (var i = 0; i < boxes.length; i++) {
        var box = boxes[i];
        box.style.gridRowEnd = "span 1";
        var height = box.scrollHeight;
        console.log(height);
        console.log(box);
        // Set the grid-row property based on the height
        for (var index = 1; index <  Math.floor(height/gridHeight)+3; index++) {
            
            if ((height+20) > ((gridHeight*index))) {
                box.style.gridRowEnd = "span " + (index+1);
                
            }
        }
    }
    });
},700);
</script>

<script>
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
            totalPoints.textContent = currentPoints - pointsInDiv;
        } else {
            totalPoints.textContent = currentPoints + pointsInDiv;
        }
    }
</script>

</body>
</html>
