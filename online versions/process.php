<?php 
include_once "functions.php";
include_once "sqlServerinfo.php";

$query = parseUrlQuery();

$linkQuery ="";

// -----------temp while using Post

foreach($query as $key => $row) {
    if ($key !== 'cost') {
        $_POST[$key]=$query[$key];
        $linkQuery .= ($key !== 'lsID') ? "&" . $key . "=" . $query[$key] : "";
    }
}

foreach($_POST as $key => $row) {
    if  (isset($_POST[$key]))
        $_SESSION[$key]=$_POST[$key];
}
// ------- populate post variables from session

if (isset($_SESSION["lastPage"])) {
    if ($_SESSION["lastPage"]<>$_SERVER['PHP_SELF']) {
        foreach($_SESSION as $key => $row) {
            $_POST[$key]=$_SESSION[$key];
        }
    }
}

$insignia = fetchData($conn, $query, "insigniaQuery", "SELECT * FROM insignia ORDER BY autonr");
$Books =    fetchData($conn, $query, "Books", "SELECT * FROM nationBooks");
$images =   fetchData($conn, $query, "images", "SELECT * FROM platoonImages");

$bookSelected = FALSE;
$Periods = [];
$Nations = [];
$nationBooks = [];
$key=0;
if (!isset($query['pd'])) {
    unset($query['Book']);
    unset($query['ntn']);

}
foreach ($Books as $eachBook) {
    $nation = $eachBook["Nation"];
    $period = $eachBook["period"];
    $periodLong = $eachBook["periodLong"];
    // Check if the combination of Nation and period exists in the unique array
    if (isset($query['pd'])&&$period == $query['pd']) {
        if (!in_array(["Nation" => $nation, "period" => $period], $Nations)) {
            $Nations[]  = ["Nation" => $nation, "period" => $period];
        }
    }
        if (!in_array([ "period" => $period,  "periodLong" => $periodLong], $Periods)) {
            $Periods[]  = [ "period" => $period,  "periodLong" => $periodLong];
        }
    if (isset($query['pd'])&&isset($query['ntn'])&&isset($query['Book'])) {
        if  (($eachBook["Nation"] == $query['ntn'])&&($eachBook["period"] == $query['pd'])&&($eachBook["code"] == $query['Book'])) {
            $bookSelected = true;
        }
    }
        if (isset($query['Book'])) {
        if (($eachBook["code"] == $query['Book'])||($eachBook["Book"] == $query['Book'])){
                    $bookCode = $eachBook["code"]; 
                    $bookTitle = $eachBook["Book"];
        }            
    }
    if (isset($query['pd'])&&isset($query['ntn'])&&$query['ntn'] == $eachBook["Nation"]&&($eachBook["period"] == $query['pd'])) {
        $nationBooks[$key] = $eachBook;
        if (isset($query['Book'])&&$query['Book'] == $eachBook["code"]) {
            $nationBooks[$key]["selected"] = 1;
        }
        $nationBooks[$key]["value"] = $eachBook["code"];
        $nationBooks[$key]["description"] = $eachBook["Book"];
        $key++;
    }
}
if (!$bookSelected) {
    $query['Book'] = null;
}
if (count($nationBooks) == 1) {
    $query['Book'] = $nationBooks[0]["code"];
    $bookCode = $nationBooks[0]["code"];
    $nationBooks[0]["selected"] = 1;
    $bookTitle = $nationBooks[0]["Book"];
    $bookSelected = true;
}
if (isset($query['pd'])) {
    foreach ($Nations as $key => $value) {
        if ($value["period"] == $query['pd']) {
            if (isset($query['ntn'])&&$query['ntn'] == $value["Nation"]) {
                $Nations[$key]["selected"] = 1;
            }
            $Nations[$key]["value"] = $value["Nation"];
            $Nations[$key]["description"] = $value["Nation"];
        }
    }
}
// --- User handling
include "login.php";

// Get the latest selected select element's ID

// Generate the targetLocation based on the latest select element's ID
$targetLocation = isset($lsID) ? (($lsID) ? '#' . $lsID . 'box' : "") : "";

$maxSupportBoxNr = 0;

if (isset($query['pd'])&&$bookSelected) {
    
    $Formations = $conn->query(
        "SELECT  * 
         FROM    formations 
         WHERE   formations.title NOT LIKE '%Support%'");  
 
     $platoonCards= $conn->query(
         "SELECT  *
         FROM    cmdCardPlatoonModDB
         WHERE   Book = '{$bookTitle}'");
 
     $unitCards= $conn->query(
         "SELECT  *
         FROM    cmdCardUnitModDB
         WHERE   Book = '{$bookTitle}'");
 
     $platoonOptionOptions= $conn->query(
        "SELECT  * 
         FROM    platoonOptions");    

    $supportConfig= $conn->query(
    "SELECT  *
        FROM    platoonConfigSupportDB
        WHERE formation LIKE '%{$bookTitle}%'
        ORDER BY cost DESC
    ");

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

    $supportPlatoonConfig =[];
    if ($supportConfig->num_rows > 0) {
        while ($row = $supportConfig->fetch_assoc()) {
            $supportPlatoonConfig[] = $row;
        }
        mysqli_data_seek($supportConfig ,0);
    }
}

// ----------------------------------------------------------------
//  ------------------- Formation Calculation ---------------------
// ----------------------------------------------------------------

if (isset($query['nOF'])) {
    $nrOfFormationsInForce = $query['nOF'];
} else {
if (isset($query['nOFoB'])) {
    if ($query['nOFoB']>0) {
        $nrOfFormationsInForce = 0;
    } else {
$nrOfFormationsInForce = 0;
        }
    } else {
        $nrOfFormationsInForce = 1;
    }
    
}
if (!isset($query['nOFoB'])) {
    $query['nOFoB'] = 0;
}

$formationTitle=[];
$formationNote=[];
$formationCost= [] ;
$boxCost=[];
$boxSections[][]=0;
$formationCardTitle =[];
$formationCardNote=[];
$formationHTML = [];
$supportHTML = [];
$blackBoxHTML =[];
$cmdCardsOfEntireFormation =[];
$cmdCardTitleOfEntireFormation =[];
$aliedBooks=[];
if (isset($query['pd'])) {
for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce+$query['nOFoB']; $formationNr++) { //  Formation 

    $lastFormation = "";
    $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
    $formationCardToggle =TRUE;
    $correctBook = FALSE;
    if (isset($Formations)) {
            
        foreach ($Formations as $row) {
            if (($row["Book"] == $bookTitle)) {
                if (isset($query[$currentFormation])&&$query[$currentFormation] == $row["code"]) {
                    $correctBook = TRUE;
                    break;
                }
            }
        }
    }
        if (isset($query["ntn"])) {
        $thisNation["ntn"] = $query["ntn"];
    }

    if (isset($Formations) &&$formationNr>$nrOfFormationsInForce) {
        $otherBookFormations =$Formations;
        $correctBook = TRUE;
        if (isset($query[$currentFormation . "Book"])) {
                foreach ($Books as $row) if (($row["code"] == $query[$currentFormation . "Book"])){
                    $bookCode = $row["code"]; 
                    $currentBookTitle[$currentFormation] = $row["Book"]; 
                }
        }

        // ------------------ allied formation book lookup
        $formationNation[$currentFormation . "Book"] = $query['ntn'];

        $optionsArrayKey=0;
        foreach ($Books as $bookRow) {
            if ((is_numeric(strpos($bookRow["Allies"],$query['ntn']))||($query['ntn']==$bookRow["Nation"]))&&($query['pd']==$bookRow["period"])&&($bookRow["code"] != $query['Book'])) {
                if (isset($query[$currentFormation . "Book"])&&($bookRow["code"] == $query[$currentFormation . "Book"])) {
                    $aliedBooks[$currentFormation][$optionsArrayKey]["selected"] = 1;
                    $formationNation[$currentFormation . "Book"] = $bookRow["Nation"];
                    $formationNation[$currentFormation . "BookTitle"] = $bookRow["Book"];
                } else {
                    $aliedBooks[$currentFormation][$optionsArrayKey]["selected"] = 0;
                }
                $aliedBooks[$currentFormation][$optionsArrayKey]["value"] = $bookRow["code"];
                $aliedBooks[$currentFormation][$optionsArrayKey]["description"] = $bookRow["Nation"] . ": " . $bookRow["Book"];
                $optionsArrayKey++;
                
            }
        }
                $nationArray = array_column($aliedBooks[$currentFormation], "description");
        array_multisort($nationArray, SORT_DESC, SORT_NUMERIC, $aliedBooks[$currentFormation]);
        $formationSelectButtonsHTML[$currentFormation] = generateDroppdownHTML($currentFormation . "Book", $currentFormation . "Book", $aliedBooks[$currentFormation]);

        if (isset($query[$currentFormation . "Book"])&&$bookSelected) {
            $thisNation["ntn"] = $formationNation[$currentFormation . "Book"];
            $otherBookFormations = [];
            if (!empty($formationNation[$currentFormation . "BookTitle"])&&$formationNation[$currentFormation . "BookTitle"]!=$bookTitle) {
                foreach ($Formations as $formationRow) {
                    if (!is_numeric(strpos($formationRow["code"],"CC"))&&$formationNation[$currentFormation . "BookTitle"]==$formationRow["Book"]) {
                        $otherBookFormations[] = $formationRow;
                    }
                }
            }
            $formationSelectButtonsHTML[$currentFormation] .= 
            generateFormationButtonsHTML($otherBookFormations, $currentBookTitle[$currentFormation], $thisNation, $currentFormation, isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia);
        }
    } elseif (isset($Formations)) {
        $formationSelectButtonsHTML[$currentFormation] = 
        generateFormationButtonsHTML($Formations, $bookTitle, $query, $currentFormation, isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia);
    }

    if ((!$correctBook)) {
        unset($query[$currentFormation]);
    }
    // - - Clear formation -----------
    if (isset($_POST["clearFormation" . $currentFormation])) {
        if ($_POST["clearFormation" . $currentFormation] <>"") {
            $_POST["clearFormation" . $currentFormation] ="";
            $lastFormation = $query[$currentFormation];
            foreach ($_POST as $key => $row) {
                if (is_numeric(strpos($key,$currentFormation))) {
                    $_POST[$key]="";
                    $_SESSION[$key]="";
                    $query[$key] ="";
                }
            }
        } 
    }
    
        if (isset($query['pd'])&&(isset($query[$currentFormation]))&&$correctBook)  {

        $Formation_DB=[];
        $Formation_DBSql = $conn->query(
           "SELECT  * 
            FROM    formation_DB 
            WHERE   formation LIKE '%" . $query[$currentFormation] . "%'");
        foreach ($Formation_DBSql as $key => $value) {
            $Formation_DB[] = $value;
        }
       
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
            WHERE   Book LIKE '%" . $bookTitle . "%'
            AND     formation LIKE '%{$query[$currentFormation]}%'");
    }

    if (isset($Formation_DB)&&
    query_exists($Formation_DB)&&
    query_exists($Formations)&&
        (isset($query['pd'])&&isset($query[$currentFormation])&&($query[$currentFormation] != ""))){//  Formation 
        
    //---- SQL
        $formationCards= $conn->query("
            SELECT  DISTINCT 
                    cmdCardFormationMod.Book AS Book, 
                    cmdCardFormationMod.formation AS formation, 
                    cmdCardFormationMod.card AS card, 
                    cmdCardCost.platoonTypes AS platoonTypes, 
                    cmdCardCost.pricePerTeam AS pricePerTeam,     
                    cmdCardCost.price AS cost,
                    cmdCardsText.code AS code,
                    cmdCardsText.title AS title,
                    CONCAT(cmdCardsText.notes, ' ', cmdCardsText.unitModifier, ' ', cmdCardsText.statsModifier) AS notes
            FROM    cmdCardFormationMod
                LEFT JOIN cmdCardCost
                    LEFT JOIN cmdCardsText
                    ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
                ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card 
            WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'
            AND     cmdCardFormationMod.formation LIKE '%" . $query[$currentFormation] . "%'");  

    // ---- formation title and text
    $formationSpecificPlatoonConfig = [];
    $platoonConfig= $conn->query(
        "SELECT  *
        FROM    platoonConfigDB
        WHERE formation = '" . $query[$currentFormation] . "'
        ORDER BY cost DESC");

    if ($platoonConfig->num_rows > 0) {
        while ($row = $platoonConfig->fetch_assoc()) {
            if ($row['formation'] == $query[$currentFormation]) {
                $formationSpecificPlatoonConfig[] = $row;
            }
        }
        mysqli_data_seek($platoonConfig ,0);
    }
    $formationTitle[$formationNr] ="";

    foreach ($Formations as $formationRow) {
        if ($formationRow["code"] == $query[$currentFormation]) {
            $formationTitle[$formationNr] = $formationRow["title"];         
            $formationNote[$formationNr] = $formationRow["Notes"]; 
        }
    }
    if_mysqli_reset($Formations);

    $query[$formationNr."title"]=$formationTitle[$formationNr];

    // ------ cmdCards of entire formation -------------    

    list($cmdCardsOfEntireFormation[$formationNr], $cmdCardTitleOfEntireFormation[$formationNr]) = processFormationCards($formationNr, $formationCards, $query, $currentFormation, $formationCost[$formationNr]);

    // --------- Generate code for formation boxes ------------
    //---------- boxes ---(FORM)-------
    $boxNrs = [];
    $boxTypes =[];

    if (isset($cardPlatoon)&&query_exists($cardPlatoon)) {
        $limitedUsed = false;
        $limitedUsedInBox = 0;
        foreach ($cardPlatoon as $value) {
            $replacePlatoonInBox = false;
            $foundit = false;
            $cardPrerequisiteEval = false;
            if (!empty($value["prerequisite"])) {
                $explodedPreReq = explode("|",$value["prerequisite"]);
                foreach ($explodedPreReq as $preReqKey => $eachPrerequisites) {
                    if (isset($query[$formationNr . "-Card"])&&$query[$formationNr . "-Card"] ==$eachPrerequisites) {
                        $cardPrerequisiteEval = true;
                    }
                    if ($eachPrerequisites== "Replace") {
                        $replacePlatoonInBox = $explodedPreReq[$preReqKey+1];
                    }
                }
            } else {
                $cardPrerequisiteEval = true;
            }
            if (!empty($value["prerequisite"])&&$value["prerequisite"]=="Limited"||$value["prerequisite"]=="Warrior") {
                
                $cardPrerequisiteEval = true;
                if (isset($query["F{$formationNr}-{$value["box_nr"]}"])&&$query["F{$formationNr}-{$value["box_nr"]}"]==$value["platoon"]&&$limitedUsedInBox==0) {
                    $limitedUsedInBox = $value["box_nr"];
                }
                if ($limitedUsedInBox!=$value["box_nr"]&&$limitedUsedInBox!=0) {
                    $limitedUsed = true;
                }
            }
            foreach ($Formation_DB as $key => $value2) {
                if ((($value["platoon"] == $value2["platoon"])&&($value["box_nr"] == $value2["box_nr"]&&$cardPrerequisiteEval)||($cardPrerequisiteEval&&$value2["platoon"]==$replacePlatoonInBox&&($value["box_nr"] == $value2["box_nr"])))) {
                    if (isset($value2["BlackBox"])&&$value2["BlackBox"]==1) {
                        $value["BlackBox"] =1;
                    }
                    $Formation_DB[$key] = $value;
                    $foundit = true;
                }
            }

            if (!$foundit&&$cardPrerequisiteEval&&!$limitedUsed) {
                
                $attributeList = [];
                foreach ($value as $attributeName => $attributes) {
                    if ($attributeName=="box_type"&&$value["box_nr"]=="1") {
                        $attributeList[$attributeName] = "Headquarters";
                    } else {
                        $attributeList[$attributeName] = $attributes;
                    }
                }
                $Formation_DB[] = $attributeList;               
            }
            $formationLookup1 ="";
            $cardPlatoonConfig= $conn->query(
                "SELECT  *
                FROM    platoonConfigDB
                WHERE platoon = '" . $value["platoon"] . "'
                ORDER BY cost DESC");
            foreach ($cardPlatoonConfig as $key1 => $value1) {
                if (($value['platoon'] == $value1['platoon'])&&(($formationLookup1=="")||($formationLookup1==$value1["formation"]))) {
                    $formationSpecificPlatoonConfig[] = $value1;
                    $formationLookup1 = $value1["formation"];
                }
            }
            if_mysqli_reset($cardPlatoonConfig);
        }
        if_mysqli_reset($cardPlatoon );
        array_multisort(array_column($Formation_DB,"box_nr"), SORT_ASC, SORT_NUMERIC,$Formation_DB);
    }

    foreach ($Formation_DB as $key => $value) {
        $boxNrs[] = $value["box_nr"];
        $boxTypes[$key]["box_nr"] = $value["box_nr"];
        $boxTypes[$key]["box_type"] = $value["box_type"];
    }
    $boxNrs = array_unique($boxNrs);
    $boxTypes = array_unique($boxTypes, SORT_REGULAR);

    $tempArr1 = array_unique(array_column($formationSpecificPlatoonConfig,"shortID"), SORT_REGULAR);
    $formationSpecificPlatoonConfig = array_intersect_key($formationSpecificPlatoonConfig, $tempArr1);
    foreach ($boxNrs as $BoxInSection){
// ------- set reused variables
        $currentBoxNr = $BoxInSection;
        foreach($boxTypes as $row4) {
            if ($row4["box_nr"] == 1 || $row4["box_nr"] =="1") {
                $thisBoxType = "Headquarters";
            } elseif (($row4["box_nr"] == $currentBoxNr)) {
                $thisBoxType = $row4["box_type"];
                break;
            }
        }     
        $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
        $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
        $platoonIsInThisFormation = false;
        foreach ($Formation_DB as $platoonInBox) {
            if($currentBoxNr == $platoonInBox["box_nr"]){
                if ($platoonInBox["platoon"] ==$thisBoxSelectedPlatoon) {
                    $platoonIsInThisFormation =true;
                }
            }
        }
        if (!$platoonIsInThisFormation) {
            $thisBoxSelectedPlatoon ="";
        }
        $currentPlatoonFormation = $query[$currentFormation];
        $formationHTML[$currentBoxInFormation] = "<div id='{$currentBoxInFormation}box' class='box'><b>{$thisBoxType}</b><br>";
        foreach ($Formation_DB as $platoonInBox) {
            // ---- reset $thisBoxType for each separate platoon -------------
            $thisBoxType = $platoonInBox["box_type"];
            if($currentBoxNr == $platoonInBox["box_nr"]){
                $currentPlatoon =   $platoonInBox["platoon"];
                $currentUnit =      $platoonInBox["unitType"] ??"";                
                $platoonTitle =     $platoonInBox["title"];
                $platoonHaveCards = FALSE;
                $tempOptions = "";
                if (!isset($boxCost[$formationNr][$currentBoxNr])) {
                    $boxCost[$formationNr][$currentBoxNr] =null;
                }
                if (!isset($platoonInBox["BlackBox"] )) {
                    if ($currentBoxNr ==2||$currentBoxNr==3) {
                        $platoonInBox["BlackBox"] = 1;
                    }
                }

//------- Black box
                $formationHTML[$currentBoxInFormation] .= "<div" .((($thisBoxType == "Headquarters")||($platoonInBox["BlackBox"] ?? ""== 1)) ? " class='blackbox" : " class='platoon") .(($currentPlatoon == $thisBoxSelectedPlatoon) ? " checkedBox" : ""). "'>";
                if ((($thisBoxType == "Headquarters")||($platoonInBox["BlackBox"] ?? ""== 1))&&($thisBoxSelectedPlatoon =="")) {
                    $thisBoxSelectedPlatoon = $currentPlatoon;
                    $query[$currentBoxInFormation] = $currentPlatoon;
                }
// ----- checked and set status from session variablse for the selected platoon in the box     
                $formationHTML[$currentBoxInFormation] .= "
                <input" . (($currentPlatoon == $thisBoxSelectedPlatoon) ? " checked" : "") . " id='{$currentBoxInFormation}box{$currentPlatoon}' 
                type='checkbox' 
                name='{$currentBoxInFormation}' 
                class='{$currentBoxInFormation}' 
                value='{$currentPlatoon}' " . <<<HTML
                onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                $platoonConfigChanged = configChangedGenerate($platoonInBox, $formationSpecificPlatoonConfig);
                $cardIndex =0;
                if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {
                    foreach ($platoonCards as $key => $value) {

                        if (($value["platoon"] == $currentPlatoon && isset($value["code"])) || ($value["platoon"] == $currentPlatoon && $value["code"] == $platoonInBox["cardNr"]) ) { // isset code is to not show incomplete cards (price and text)
                            $cardIndex++;
                            if ($value["code"] == $platoonInBox["cardNr"]) {
                                $query[$currentBoxInFormation . "Card" . $cardIndex] = $platoonInBox["cardNr"];
                                break;
                            }
                        }
                    }
                }
// ------ Config of platoon -------------       
               $boxConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
               
               list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
               $tempOptions = generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeadersChanged, $platoonOptionChanged, $formationNr, $currentBoxNr, $boxCost, $formationCost, $boxSections );
               
//------- check if the formation have cards--------------
                $formationCardHTML = processFormationCardHTML($formationCards, $query, $currentFormation, $formationNr, $currentBoxNr, $thisBoxType, $boxCost, $formationCost, $boxSections, $cmdCardTitleOfEntireFormation, $thisBoxSelectedPlatoon, $formationCardTitle, $currentPlatoon);
//------- check if the platoon have cards available --------------                   
                if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
                    $formationCardHTML .=  generateCardArrays($formationCards, $thisBoxType, $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
// ------------ platoon cards (pioneer etc.) ----------------------
                    $formationCardHTML .= generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr, $platoonTitle, $cmdCardTitleOfEntireFormation[$formationNr]);
//--- unit card (gun/infantry), soft skin etc. cost --------------------
                    $formationCardHTML .= generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr);
                }
//-----------image -------------------------
                $formationHTML[$currentBoxInFormation] .= generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation, $cmdCardTitleOfEntireFormation[$formationNr], $insignia);
                $formationHTML[$currentBoxInFormation] .= "<div  class='title'>\n";
                $formationHTML[$currentBoxInFormation] .= "<label for='{$currentBoxInFormation}box{$currentPlatoon}'><b>\n<span class='left'>";
                $formationHTML[$currentBoxInFormation] .= generateTitleImanges($insignia, $cmdCardTitleOfEntireFormation[$formationNr] . $platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["platoonNation"]<>"")?$platoonInBox["platoonNation"]:$thisNation["ntn"]) . "</span>\n <span>";
                if (($cmdCardTitleOfEntireFormation[$formationNr]<>"")&&(!is_numeric(strpos($platoonTitle, $cmdCardTitleOfEntireFormation[$formationNr])))) $formationHTML[$currentBoxInFormation] .= $cmdCardTitleOfEntireFormation[$formationNr]. ": ";
                $formationHTML[$currentBoxInFormation] .= $platoonTitle . "</b><br>
                            <span style='font-size: 0.7em;'>" . $currentPlatoon . "</span></span></label><br>";
                $formationHTML[$currentBoxInFormation] .= "
                        {$boxConfigHTML}
                        </div>
                    </div>";                
// ------ Options of platoon -------------                       
                $formationHTML[$currentBoxInFormation] .= $tempOptions;
// ------------ (FORM) ------------
                $formationCard =[];
                $platoonCard =[];
                $unitCard =[];

// ------ cmdCards of platoon ( from above )-------------                       
                $formationHTML[$currentBoxInFormation] .=  ($formationCardHTML=="")?"":"Cards: <br>{$formationCardHTML}";
                }
            }
        }
    }
}
}

// --------------- Formation generation to here ------------------

// ---------------------------------------------------
//------------------------Support:----------------------
// ---------------------------------------------------

//---- SQL


if ($bookSelected) //-Support
{

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
        WHERE   Book LIKE '%" . $bookTitle . "%'
        AND     formation LIKE '%". $bookTitle . "%Support'");


    $Support_DB = $conn->query(
        "SELECT  *
        FROM support_DB
        WHERE   Book = '{$bookTitle}'");
    
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
        if ((!$foundit)&&($value["ogBoxNrs"]!=null)) {
            $SupporboxNrs[]  = ["box_type" => $box_type,"box_nr" => $box_nr];
            if ($box_nr>=$maxSupportBoxNr) {
                $maxSupportBoxNr = $box_nr;
            }
        }
    }
    if_mysqli_reset($Support_DB);
        
    $Support = $conn->query("
        SELECT  * 
        FROM    formations 
        WHERE   formations.title LIKE '%Support%' AND Book LIKE '%" . $bookTitle . "%'");

}
if (empty($formationNr)) {
    $formationNr =1;
}
$currentFormation = "Sup";
$formationNr+=1;
$formationCost[$formationNr] = 0;
if (isset($Support_DB)) { //-Support
    $combinedSupportDB =[];
    foreach ($Support_DB as $value) {
        $combinedSupportDB[] = $value;
$currentPlatoonFormation = $value["formation"];
    }

    
    if (isset($cardPlatoon)&&query_exists($cardPlatoon)) {
        $limitedUsed = false;
        $limitedUsedInBox = 0;
        foreach ($cardPlatoon as $value) {
            $replacePlatoonInBox = false;
            $foundit = false;
            $cardPrerequisiteEval = false;
            if (!empty($value["prerequisite"])) {
                $explodedPreReq = explode("|",$value["prerequisite"]);
                foreach ($explodedPreReq as $preReqKey => $eachPrerequisites) {
                    if (isset($query[$formationNr . "-Card"])&&$query[$formationNr . "-Card"] ==$eachPrerequisites) {
                        $cardPrerequisiteEval = true;
                    }
                    if ($eachPrerequisites== "Replace") {
                        $replacePlatoonInBox = $explodedPreReq[$preReqKey+1];
                    }
                }
            } else {
                $cardPrerequisiteEval = true;
            }
            if (!empty($value["prerequisite"])&&$value["prerequisite"]=="Limited") {
                $cardPrerequisiteEval = true;
                if (isset($query["Sup-{$value["box_nr"]}"])&&$query["Sup-{$value["box_nr"]}"]==$value["platoon"]&&$limitedUsedInBox==0) {
                    $limitedUsedInBox = $value["box_nr"];
                }
                if ($limitedUsedInBox!=$value["box_nr"]&&$limitedUsedInBox!=0) {
                    $limitedUsed = true;
                }
            }
            foreach ($combinedSupportDB as $key => $value2) {
                if (!$limitedUsed&&(($value["platoon"] == $value2["platoon"])&&($value["box_nr"] == $value2["box_nr"]&&$cardPrerequisiteEval)||($cardPrerequisiteEval&&$value2["platoon"]==$replacePlatoonInBox&&($value["box_nr"] == $value2["box_nr"])))) {
                    $combinedSupportDB[$key] = $value;
                    $foundit = true;
                }
            }
            if (!$foundit&&$cardPrerequisiteEval&&!$limitedUsed) {
                $combinedSupportDB[] = $value;               
            }
            $formationLookup1 ="";
            $cardPlatoonConfig= $conn->query(
                "SELECT  *
                FROM    platoonConfigDB
                WHERE platoon = '" . $value["platoon"] . "'
                ORDER BY cost DESC");
            foreach ($cardPlatoonConfig as $key1 => $value1) {
                if (($value['platoon'] == $value1['platoon'])&&(($formationLookup1=="")||($formationLookup1==$value1["formation"]))) {
                    $supportPlatoonConfig[] = $value1;
                    $formationLookup1 = $value1["formation"];
                }
            }
            if_mysqli_reset($cardPlatoonConfig);
        }
        if_mysqli_reset($cardPlatoon);
    }
    
//----------Header ----- (sup)-----
$supportHTML[1] ="";
$tempArr1 = array_unique(array_column($supportPlatoonConfig,"shortID"), SORT_REGULAR);
$supportPlatoonConfig = array_intersect_key($supportPlatoonConfig, $tempArr1);
    foreach ($SupporboxNrs as $BoxInSection){ 

        $maxSupportBoxNr = $BoxInSection["box_nr"];
        $currentBoxNr =             $BoxInSection["box_nr"];
        $tempOptions = "";
        $thisBoxType =              $BoxInSection["box_type"];
        $currentBoxInFormation =    $currentFormation."-".$currentBoxNr;
        $thisBoxSelectedPlatoon =   isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
        $supportHTML[1] .=          "<div id='{$currentBoxInFormation}box' class='box'><b>{$thisBoxType}</b><br>";

        
        foreach ($combinedSupportDB as $platoonInBox) {
            
            if($currentBoxNr == $platoonInBox["box_nr"]){

                $platoonConfigChanged = []; 
                $formationCardHTML =  "";
                $currentPlatoon =   $platoonInBox["platoon"];
                if (isset($platoonInBox["unitType"])) {
                    $currentUnit =      $platoonInBox["unitType"];
                }
                
                $platoonTitle = $platoonInBox["title"];
                if (!isset($boxCost[$formationNr][$currentBoxNr])) {
                    $boxCost[$formationNr][$currentBoxNr] =null;
                }
                $supportHTML[1] .=  "<div class='platoon'>
                <input" . (($currentPlatoon == $thisBoxSelectedPlatoon) ? " checked" : "") . " id='{$currentBoxInFormation}box{$currentPlatoon}' type='checkbox' name='{$currentBoxInFormation}' class='{$currentBoxInFormation}' value='{$currentPlatoon}' " . <<<HTML
                onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                $platoonConfigChanged = configChangedGenerate($platoonInBox, $supportPlatoonConfig);
                $cardIndex =0;
                if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {
                    foreach ($platoonCards as $key => $value) {
                        if ($value["platoon"] == $currentPlatoon && isset($value["code"])) { // isset code is to not show incomplete cards (price and text)
                            $cardIndex++;
                            if ($value["code"] == $platoonInBox["cardNr"]) {
                                $query[$currentBoxInFormation . "Card" . $cardIndex] =  $platoonInBox["cardNr"];
                            }
                        }
                    }
                }
                $boxConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
                $platoonOptionChanged = [];
                $platoonOptionHeadersChanged = [];
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
               $tempOptions = generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeadersChanged, $platoonOptionChanged, $formationNr, $currentBoxNr, $boxCost, $formationCost, $boxSections );
               
// ------ cmdCards of platoon -------------              
                if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
//------- check if the platoon have cards available                    
                    $formationCardHTML .= generateCardArrays($formationCards ??[], $thisBoxType, $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
// ----------- if it has cards, print the heading 
                    $formationCardHTML .= generateFormCardsHTML($formationCard, $thisBoxType, $currentPlatoonFormation, $currentBoxInFormation, $boxSections, $formationNr, $currentBoxNr, $boxCost, $formationCost, $query, $formationCardTitle[$currentBoxInFormation]);
// ------------ platoon cards (pioneer etc.) --- Corrected
                    $formationCardHTML .= generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr, $platoonTitle,"");
//--- unit card (gun/infantry), soft skin etc. cost
                    $formationCardHTML .= generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr);
                }
//-----------image --------------
                $supportHTML[1] .= generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation,"", $insignia)
                . "<div  class='title'>
                <label for='{$currentBoxInFormation}box{$currentPlatoon}'> <span class='left'>";
                if (isset($query['ntn'])) {
                    
                    $supportHTML[1] .= generateTitleImanges($insignia,$platoonInBox["title"]??"", !empty($platoonInBox["platoonNation"])?$platoonInBox["platoonNation"]:$query['ntn']);
                } 
                $supportHTML[1] .= "</span><span><b>{$platoonTitle}</b><br>
                <span style='font-size: 0.7em;'>" . $currentPlatoon . "</span></span></label><br>"
// ------ Config of platoon -------------     
                . $boxConfigHTML
                . "
                </div>
            </div>"
// ------ Options of platoon -------------
                . $tempOptions
                . $formationCardHTML .  "
                <br>";  
            }
        }                
        if (isset($boxCost[$formationNr][$BoxInSection["box_nr"]])&&($boxCost[$formationNr][$BoxInSection["box_nr"]]!=0)) {
            $supportHTML[1] .= "
            <div class='Points'>
                <div>
                    " . $boxCost[$formationNr][$BoxInSection["box_nr"]] . " points
                </div>
            </div>";
        }
        $supportHTML[1] .= "
        </div>\n    ";
    }
} 

// ---------------------------------------------------
//  ------------------- Black box ---------------------
// ---------------------------------------------------

$currentFormation = "BlackBox";
$formationNr+=1;
$blackBoxHTML[1] = "";
$formationCost[$formationNr] = 0;
//---- SQL
if ($bookSelected) //  --Black box 
{
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
        WHERE   cmdCardFormationMod.Book LIKE '%{$bookTitle}%'");  

    $BBSupport_DB = $conn->query(
        "SELECT  DISTINCT platoon , title, alliedBook AS Book, unitType, Nation, motivSkillHitOn
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'
        GROUP by platoon
        ORDER BY relevance desc");
    
    
    $BBSupport_DBformation = $conn->query(
        "SELECT  DISTINCT platoon ,  formation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'");

    $BBSupport_type = $conn->query("
        SELECT  DISTINCT platoon , box_type
        FROM    formationSupport_DB
        WHERE Book = '{$bookTitle}'");


    $BBSupport_unique_type = [];
    foreach($BBSupport_type as $row4) {
        $BBSupport_unique_type[] = $row4["box_type"];
    }
    $BBSupport_unique_type = array_unique($BBSupport_unique_type);
}

if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)) {
    $otherNationBox =false;
    $mwAvantiGermanSupport =0;
    foreach ($BBSupport_unique_type as $unique_type) { //  --Black box 
        
        $blackBoxHTML[1] .= "
        <button type='button' class='collapsible'> <h3>{$unique_type}</h3></button>
        <div class='Formation'>
            <div class='grid'>";
    //---------- boxes (BB)
        foreach ($BBSupport_DB as $currentBoxNr => $platoonInBox){ 
    // ------- set reused variables
            $currentPlatoon = $platoonInBox["platoon"];

            foreach($BBSupport_type as $row4) {
                if ($row4["platoon"] == $currentPlatoon){
                    $thisBoxType = $row4["box_type"];
                }
            }
            if ($thisBoxType == $unique_type){
                $currentPlatoonFormation ="";
                foreach ($BBSupport_DBformation as $rowForm) {
                    if  ($rowForm['platoon'] == $currentPlatoon) {
                        $currentPlatoonFormation .= $rowForm["formation"];
                    }
                }
                $platoonTitle = $platoonInBox["title"];
                $blackBoxTempCardHTML = "";
                $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
                if (isset($query[$currentBoxInFormation])) {
                    $thisBoxSelectedPlatoon = $query[$currentBoxInFormation];
                } else {
                    $thisBoxSelectedPlatoon ="";
                }
                $tempOptions = "";
                $currentUnit = $platoonInBox["unitType"];
                $boxCost[$formationNr][$currentBoxNr] =null;
                if ($currentPlatoon == $thisBoxSelectedPlatoon) {
                    $platoonConfig = $conn->query(
                    "SELECT  platoonConfig.* , unitT.unitType AS unitType
                    FROM    platoonConfig
                    LEFT    JOIN    (   SELECT DISTINCT unitType, platoon
                                        FROM formation_DB )
                                    AS
                                    unitT
                            ON unitT.platoon = platoonConfig.platoon
                    WHERE platoonConfig.platoon = '{$currentPlatoon}'
                    ORDER BY platoonConfig.cost DESC");
                }
    // ----- checked and set status from session variablse for the selected platoon in the box
                $bbEvalAllowed = ($currentPlatoon == $thisBoxSelectedPlatoon)&&((!$otherNationBox)||($platoonInBox["Nation"] == $query['ntn']));

                // -- to restrict using bb platoons that is from current formation. should be changed to unit tupe ie normal sherman/veteran sherman combo not allowed
                foreach ($query as $key => $value) {
                    if ($value == $currentPlatoon&&$currentBoxInFormation !=$key) {
                        $query[$currentBoxInFormation]="";
                        $bbEvalAllowed = false;
                        break;
                    }
                }
                $blackBoxHTML[1] .= "
                <div id='{$currentBoxInFormation}box' class='box'> 
                <div class='platoon {$platoonInBox["Nation"]}'>
                    <input" . (($bbEvalAllowed) ? " checked":"")." id='{$currentBoxInFormation}box{$currentPlatoon}' type='checkbox' name='{$currentBoxInFormation}' class='{$currentBoxInFormation}' value='{$currentPlatoon}' " . <<<HTML
                        onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                    if ($otherNationBox&&($platoonInBox["Nation"] != $query['ntn'])) {
                        $query[$currentBoxInFormation]="";
                    }
                    if (($currentPlatoon == $thisBoxSelectedPlatoon)&&($platoonInBox["Nation"] != $query['ntn'])) {
                        if ($query['ntn'] != "Italian") {
                            $otherNationBox = true;
                        } else {
                            $mwAvantiGermanSupport++;
                            if ($mwAvantiGermanSupport>1) {
                                $otherNationBox = true;
                            }
                        }                       
                    }

    //------------ note BB specific ------------
                    $platoonConfigHTML = "";
                    if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
                        
                    $platoonConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfig, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
                    $platoonOptionChanged = [];
                    $platoonOptionHeadersChanged = [];
     
                    list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                    $tempOptions = generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeadersChanged, $platoonOptionChanged, $formationNr, $currentBoxNr, $boxCost, $formationCost, $boxSections );
                    
                    if (($platoonInBox['Book'] == $bookTitle)) {
                        $blackBoxTempCardHTML .= generateCardArrays($formationCards, $thisBoxType, $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                        $blackBoxTempCardHTML .= generateFormCardsHTML($formationCard, $thisBoxType, $currentPlatoonFormation, $currentBoxInFormation, $boxSections, $formationNr, $currentBoxNr, $boxCost, $formationCost, $query, $formationCardTitle[$currentBoxInFormation]);
                        $blackBoxTempCardHTML .= generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr, $platoonTitle,"");
                    }
//------- check if the platoon have cards available                    

                    }
    //-----------image -------------
                    $blackBoxHTML[1] .= generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation,isset($formationCardTitle[$currentBoxInFormation])?$formationCardTitle[$currentBoxInFormation]:"", $insignia)
                    . "<div  class='title'>
                        <label for='{$currentBoxInFormation}box{$currentPlatoon}''><b><span class='left'>" 
                    . generateTitleImanges($insignia,$platoonInBox["title"], ($platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$query['ntn']) . "</span><span>"
    // ------------ Specific for BB --------                
                    . $platoonInBox['Book'] .": "
    //--------------------------------------
                    . ((isset($formationCardTitle[$currentBoxInFormation])&&$formationCardTitle[$currentBoxInFormation]<>"")? "{$formationCardTitle[$currentBoxInFormation]}: " : "") . "{$platoonTitle}</b><br>
                    <span style='font-size: 0.7em;'>" . $currentPlatoon . "</span></span></label><br>";
    // ------ Config of platoon -------------                       
                    $blackBoxHTML[1] .= isset($platoonConfigHTML)?$platoonConfigHTML:"";
                    $blackBoxHTML[1] .= "\n</div>";
    // ------ Options of platoon -------------                       
                    $blackBoxHTML[1] .= $tempOptions;
    // ------ cmdCards of platoon -------------                       
    //------------ note BB specific ------------
                    if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {
    //------- check if the platoon have cards available                    
                        $blackBoxHTML[1] .= $blackBoxTempCardHTML;
    //--- unit card (gun/infantry), soft skin etc. cost
                        $blackBoxHTML[1] .= generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr);
                    }
                    $blackBoxHTML[1] .= "\n<br>";  
    // -------- To Here ---------

    // -------- points ----------
                if (isset($boxCost[$formationNr][$currentBoxNr])) {
                    $blackBoxHTML[1] .= "\n<div class='Points'>
                          <div>
                            " . $boxCost[$formationNr][$currentBoxNr] . " points
                          </div>
                        </div>\n";
                }
                $blackBoxHTML[1] .= "\n</div>";

    // -------- To Here ----------   
            $blackBoxHTML[1] .= "</div>\n";
            }
        }
    $blackBoxHTML[1] .= "\n</div>\n</div>";
    }
}

// ---------------------------------------------------
//  ------------------- Card platoons ---------------------
// ---------------------------------------------------

$currentFormation = "CdPl";
$formationNr+=1;
$cardPlatoonIndex = 0;
$query[$currentFormation . "Sel"] = 0;
$formationCost[$formationNr] = 0;
//---- SQL

if ($bookSelected) { //- Card platoons
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

    $listCards = $conn->query(
       "SELECT cmdCardsForceMod_link.card AS card, cmdCardCost.price AS cost
        FROM cmdCardsForceMod_link
        LEFT JOIN cmdCardCost
        ON cmdCardsForceMod_link.Book = cmdCardCost.Book AND cmdCardsForceMod_link.card = cmdCardCost.card
        WHERE cmdCardsForceMod_link.card NOT LIKE ''
        AND cmdCardsForceMod_link.Book LIKE '%" . $bookTitle . "%'");
}



if (isset($cardSupport)&&query_exists($cardSupport)) {
    $CardBoxHTML[1] ="";

    $CardBoxHTML[1] .= "
    <button type='button' class='collapsible'> <h3>Card Support</h3></button>
    <div class='Formation'>
        <div class='grid'>";
//---------- boxes 
    foreach ($cardSupport as $currentBoxNr => $platoonInBox){  //- Card platoons
// ------- set reused variables
        $currentPlatoon = $platoonInBox["platoon"];        
        $platoonTitle = $platoonInBox["title"];
        $blackBoxTempCardHTML = "";
        $thisBoxType = $platoonInBox["box_type"];

        $repetsOfPlatoon = (($platoonInBox["boxNr"]==99)? 99 :1 );

        for ($repeats=0; $repeats < $repetsOfPlatoon; $repeats++) {
            $blackBoxTempCardHTML = "";
            $cardPlatoonIndex++;
            if (!isset($boxCost[$formationNr][$cardPlatoonIndex])) {
                $boxCost[$formationNr][$cardPlatoonIndex] = null;
            }
            

            $currentBoxInFormation = $currentFormation ."-" . $cardPlatoonIndex;
            $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
            $currentUnit = isset($platoonInBox["unitType"])?$platoonInBox["unitType"]:"";
            
    // ----- checked and set status from session variablse for the selected platoon in the box
            if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)) {
                // $query[$currentBoxInFormation . "Cd"] =  $platoonInBox["cardNr"];
                $query[$currentFormation . "Sel"]++;
            }
            
            $CardBoxHTML[1] .= "
            <div id='{$currentBoxInFormation}box' class='box'> 
                <div class='platoon'>
                    <input" . ((($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)) ? " checked":"")." id='{$currentBoxInFormation}box{$currentPlatoon}' 
                    type='checkbox' 
                    name='{$currentBoxInFormation}' 
                    class='{$currentBoxInFormation}' 
                    value='{$currentPlatoon}' " . <<<HTML
                    onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
            if (isset($platoonCards)&&query_exists($platoonCards)&&(isset($query[$currentBoxInFormation])&&$currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {

                $cardIndex =0;
                foreach ($platoonCards as $key5 => $row5) {

                    if ($row5["platoon"] == $currentPlatoon) { // isset code is to not show incomplete cards (price and text)
                        $cardIndex++;

                        //reset if formation card is changed
                        if (($platoonInBox["title"] == $row5["card"])&&($row5["card"]!=="")) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] = $row5["code"];
                            // echo "<!-- {$row5["card"]}-->";
                            if ($row5["cost"]!= 0) {
                                if ($row5["pricePerTeam"] != 0) {
                                    if (isset($boxSections[$formationNr][$cardPlatoonIndex])) {
                                    $blackBoxTempCardHTML .= ceil($row5["cost"] * $boxSections[$formationNr][$cardPlatoonIndex] * $row5["pricePerTeam"]);
                                    }
                                
                                } else {
                                    $blackBoxTempCardHTML .= $row5["cost"]*1;
                                }
                                $blackBoxTempCardHTML .= " points: ";
                            }

                            $blackBoxTempCardHTML .= $row5["card"] . "<br>";
                        }
                    }
                }
            }
    //-----------image -------------
            $CardBoxHTML[1] .= generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation, "", $insignia)
            . "<div  class='title'>
                <label for='{$currentBoxInFormation}box{$currentPlatoon}''><b><span class='left'>" 
                . generateTitleImanges($insignia,$platoonInBox["title"], (isset($platoonInBox["platoonNation"])&&$platoonInBox["platoonNation"]<>"")?$platoonInBox["platoonNation"]:$query['ntn']) . "</span><span>"
            . ((isset($formationCardTitle[$currentBoxInFormation])&&$formationCardTitle[$currentBoxInFormation]<>"")? "{$formationCardTitle[$currentBoxInFormation]}: " : "") . "{$platoonTitle} </span></b><br>
                {$currentPlatoon}</label><br>";
                $platoonConfigChanged = [];
                if ($currentPlatoon == $thisBoxSelectedPlatoon) {
                    $platoonConfig = $conn->query(
                    "SELECT  platoonConfig.* , unitT.unitType AS unitType
                    FROM    platoonConfig
                    LEFT    JOIN    (   SELECT DISTINCT unitType, platoon
                                        FROM formation_DB )
                                    AS
                                    unitT
                            ON unitT.platoon = platoonConfig.platoon
                    WHERE platoonConfig.platoon = '{$currentPlatoon}'");
                
                $platoonConfigChanged = configChangedGenerate($platoonInBox, $platoonConfig);
}
    // ------ Config of platoon -------------
    
                    $CardBoxHTML[1] .= processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $cardPlatoonIndex, $query, $boxCost, $formationCost, $boxSections);
                    $platoonOptionChanged = [];
                    $platoonOptionHeadersChanged = [];
     
                    list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                    $tempOptions = generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeadersChanged, $platoonOptionChanged, $formationNr, $currentBoxNr, $boxCost, $formationCost, $boxSections );
                    
                    $blackBoxTempCardHTML .= generateCardArrays($formationCards, $thisBoxType, $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                    $blackBoxTempCardHTML .= generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonCard, $boxSections, $formationCost, $boxCost, $formationNr, $cardPlatoonIndex, $platoonTitle,"");
                    
                    $CardBoxHTML[1] .= "\n</div>\n</div>";
    // ------ Options of platoon -------------
                    if ($platoonInBox["optionChange"] != "Remove") {
                        $CardBoxHTML[1] .= $tempOptions;
                    }
                    
                    $currentPlatoonFormation ="";
                    foreach ($BBSupport_DBformation as $rowForm) {
                        if  ($rowForm['platoon'] == $currentPlatoon) {
                            $currentPlatoonFormation .= $rowForm["formation"];
                        }
                    }
    // ------ cmdCards of platoon -------------                       
    //------------ note BB specific ------------
                    if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {
    //------- check if the platoon have cards available                    
                        $CardBoxHTML[1] .= $blackBoxTempCardHTML;

    //--- unit card (gun/infantry), soft skin etc. cost
                        $CardBoxHTML[1] .= generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, $formationCost, $boxCost, $formationNr, $cardPlatoonIndex);
                    }
                    $CardBoxHTML[1] .= "\n<br>";  
    // -------- To Here ----------
                
    // -------- points ----------
                if (isset($boxCost[$formationNr][$cardPlatoonIndex])) {
                    $CardBoxHTML[1] .= "\n<div class='Points'>
                            <div>
                            " . $boxCost[$formationNr][$cardPlatoonIndex] . " points
                            </div>
                        </div>\n";
                }
    // -------- To Here ----------   
            $CardBoxHTML[1] .= "</div>\n";
            
            if (($repeats>=1)) {

                if (!isset($query[$currentFormation ."-" . ($cardPlatoonIndex-1)])) {
                break;
            }
if (!isset($query[$currentBoxInFormation])) {
                    break;
                }
                if (!($query[$currentBoxInFormation] == $query[$currentFormation ."-" . ($cardPlatoonIndex-1)])) {
                    break;
                }
                
            }
        }
        }
    $CardBoxHTML[1] .= "\n</div>\n</div>";
    
}
$conn->close();
//-------------------------------------------------
//--------------- Force Command cards ----------------------
//--------------------------------------------------
$listCardCost =0;
if (isset($listCards)) {
    list($forceCardHTML, $listCardCost)= generateForceCardHTML($listCards, $query);
}


//---- cost varibale transfer generation

if (isset($boxCost)) {
    
    $dataToTransfer = array(
    'bCt' => $boxCost,
    'fCt' => $formationCost,
    'lCt' => $listCardCost
    );

    // Serialize the data array
    $serializedData = serialize($dataToTransfer);

    // Encode the serialized data
    $costArrayStrig = "&cost=" .  rtrim(strtr(base64_encode(gzdeflate($serializedData, 9)), '+/', '-_'), '=');

    $linkQuery ="";
    foreach($query as $key => $row) {
        if (($key !== 'cost')&&($key !== 'loadedListName')&&(!is_numeric(strpos($key,'title')))&&($row !== "")) {
            $linkQuery .= ($key !== 'lsID') ? "&" . $key . "=" . $query[$key] : "";
        }
    }
}
$_SESSION["linkQuery"]= $linkQuery;