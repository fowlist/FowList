<?php
$sessionStatus = session_start();
header('Content-Type: text/html; charset=utf-8');

// Start the session

 //globa Carrage return 
$CR = "\n";
$parts = parse_url($_SERVER['REQUEST_URI']);
if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
} else {
    $query=[];
}

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

$beta = ((is_numeric(strpos($_SERVER['PHP_SELF'],"Beta")))? "Beta": "");
include_once "functions{$beta}.php";
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

$bookSelected = FALSE;
$Periods = [];
$Nations = [];
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
    if (isset($query['ntn'])&&isset($query['Book'])) {
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
include "login{$beta}.php";

// Get the latest selected select element's ID

// Generate the targetLocation based on the latest select element's ID
if (isset($lsID)) {
    $targetLocation = (($lsID)?'#' . $lsID . 'box':"");
} else {
    $targetLocation ="";
}


//------ clear unused session variables

/*
foreach($_SESSION as $key => $row) {
    if  (!isset($_POST[$key])) $_SESSION[$key]="";
}
*/

$maxSupportBoxNr = 0;

if ($bookSelected) {
    include_once "sqlServerinfo{$beta}.php";

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
    
    $supportConfig= $conn->query(
        "SELECT  *
        FROM    platoonConfigSupportDB
        WHERE formation LIKE '%{$bookTitle}%'");
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
for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce+$query['nOFoB']; $formationNr++) {

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
            if ((is_numeric(strpos($bookRow["Allies"],$query['ntn']))||($query['ntn']==$bookRow["Nation"]))&&($query['pd']==$bookRow["period"])) {
                if (isset($query[$currentFormation . "Book"])&&($bookRow["code"] == $query[$currentFormation . "Book"])) {
                    $aliedBooks[$currentFormation][$optionsArrayKey]["selected"] = 1;
                    $formationNation[$currentFormation . "Book"] = $bookRow["Nation"];
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

        if (isset($query[$currentFormation . "Book"])) {
            $thisNation["ntn"] = $formationNation[$currentFormation . "Book"];
            $formationSelectButtonsHTML[$currentFormation] .= 
            generateFormationButtonsHTML($Formations, $currentBookTitle[$currentFormation], $thisNation, $currentFormation, $currentPlatoon, $currentUnit, $insignia);
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

    if ((isset($query[$currentFormation]))&&$correctBook)  {
        
        $Formation_DB=[];
        $Formation_DBSql = $conn->query(
           "SELECT  * 
            FROM    formation_DB 
            WHERE   formation LIKE '%" . $query[$currentFormation] . "%'");
        foreach ($Formation_DBSql as $key => $value) {
            $Formation_DB[] = $value;
        }
        
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
            AND     formation LIKE '%{$query[$currentFormation]}%'");
    }

    if (isset($Formation_DB)&&
    (!($Formation_DB instanceof mysqli_result)&&count($Formation_DB) > 0||($Formation_DB instanceof mysqli_result)&&$Formation_DB -> num_rows > 0)&&
    (!($Formations instanceof mysqli_result)&&count($Formations)> 0||($Formations instanceof mysqli_result)&&$Formations -> num_rows > 0)&&
    (isset($query[$currentFormation])&&($query[$currentFormation] != ""))){
        
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
    mysqli_data_seek($Formations ,0);

    $query[$formationNr."title"]=$formationTitle[$formationNr];

    // ------ cmdCards of entire formation -------------    

    list($cmdCardsOfEntireFormation[$formationNr], $cmdCardTitleOfEntireFormation[$formationNr]) = processFormationCards($formationNr, $formationCards, $query, $currentFormation, $formationCost[$formationNr]);

    // --------- Generate code for formation boxes ------------
    //---------- boxes ---(FORM)-------
    $boxNrs = [];
    $boxTypes =[];

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
                WHERE platoon = '" . $value["platoon"] . "'
                ORDER BY cost DESC");
            foreach ($cardPlatoonConfig as $key1 => $value1) {
                if (($value['platoon'] == $value1['platoon'])&&(($formationLookup1=="")||($formationLookup1==$value1["formation"]))) {
                    $formationSpecificPlatoonConfig[] = $value1;
                    $formationLookup1 = $value1["formation"];
                }
            }
            mysqli_data_seek($cardPlatoonConfig ,0);
        }
        mysqli_data_seek($cardPlatoon ,0);
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
            if (($row4["box_nr"] == $currentBoxNr)) {
                $thisBoxType = $row4["box_type"];
                break;
            }
        }     
        $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
        $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:""; 
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
                if (!isset($boxCost[$formationNr][$currentBoxNr])) {
                    $boxCost[$formationNr][$currentBoxNr] =null;
                }

//------- Black box
                $formationHTML[$currentBoxInFormation] .= "<div" .((($thisBoxType == "Headquarters")||($platoonInBox["BlackBox"] ?? ""== 1)) ? " class='blackbox" : " class='platoon") .(($currentPlatoon == $thisBoxSelectedPlatoon) ? " checkedBox" : ""). "'>";
// ----- checked and set status from session variablse for the selected platoon in the box 
                $formationHTML[$currentBoxInFormation] .= "
                <input" . (($currentPlatoon == $thisBoxSelectedPlatoon) ? " checked" : "") . " id='{$currentBoxInFormation}box{$currentPlatoon}' 
                type='checkbox' 
                name='{$currentBoxInFormation}' 
                class='{$currentBoxInFormation}' 
                value='{$currentPlatoon}' " . <<<HTML
                onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                if (isset($platoonInBox["configChange"])&&$platoonInBox["configChange"]!="") {
                    $configChangeRow = explode("\n",$platoonInBox["configChange"]);
                    foreach ($configChangeRow as $key => $value) {
                        $temp = explode("|",$value);
                        $platoonConfigChanged[] = array("platoon" => $currentPlatoon, "configuration" => str_replace("//","\n",$temp[0]), "cost" => $temp[1], "sections" => $temp[2], "shortID" => $temp[3]);
                    }
                }
                else {
                    $platoonConfigChanged = $formationSpecificPlatoonConfig;
                }

                $cardIndex =0;
                if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {

                    foreach ($platoonCards as $key => $value) {
                        
                        if ($value["platoon"] == $currentPlatoon && isset($value["code"]) || ($value["code"] == $platoonInBox["cardNr"]) ) { // isset code is to not show incomplete cards (price and text)
                            $cardIndex++;
                            if ($value["code"] == $platoonInBox["cardNr"]) {
                                $query[$currentBoxInFormation . "Card" . $cardIndex] = $platoonInBox["cardNr"];
                            }
                        }
                    }
                }
// ------ Config of platoon -------------       
               $boxConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
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
                $formationHTML[$currentBoxInFormation] .= generateTitleImanges($insignia, $cmdCardTitleOfEntireFormation[$formationNr] . $platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$thisNation["ntn"]) . "</span>\n <span>";
                if (($cmdCardTitleOfEntireFormation[$formationNr]<>"")&&(!is_numeric(strpos($platoonTitle, $cmdCardTitleOfEntireFormation[$formationNr])))) $formationHTML[$currentBoxInFormation] .= $cmdCardTitleOfEntireFormation[$formationNr]. ": ";
                $formationHTML[$currentBoxInFormation] .= $platoonTitle . "</b><br>
                            <span style='font-size: 0.7em;'>" . $currentPlatoon . "</span></span></label><br>";
                $formationHTML[$currentBoxInFormation] .= "
                        {$boxConfigHTML}
                        </div>
                    </div>";                
// ------ Options of platoon -------------                       
                $formationHTML[$currentBoxInFormation] .= generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $currentBoxNr, $boxCost, $formationCost);
// ------------ (FORM) ------------
                $formationCard =[];
                $platoonCard =[];
                $unitCard =[];

// ------ cmdCards of platoon ( from above )-------------                       
                $formationHTML[$currentBoxInFormation] .=  $formationCardHTML;
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


if ($bookSelected)
{

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
        AND     formation LIKE '". $bookTitle . "%Support'");

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
    mysqli_data_seek($Support_DB ,0);
        
    $Support = $conn->query("
        SELECT  * 
        FROM    formations 
        WHERE   formations.title LIKE '%Support%' AND Book LIKE '%" . $bookTitle . "%'");


// formation | box_nr | box_type |platoon | title | unitType | code | Book | icon        
/*         
    $formationCards= $conn->query("
        SELECT  DISTINCT 
                cmdCardFormationMod.Book AS Book, 
                cmdCardFormationMod.formation AS formation, 
                cmdCardFormationMod.card AS card, 
                cmdCardCost.platoonTypes AS platoonTypes, 
                cmdCardCost.pricePerTeam AS pricePerTeam,
                cmdCardsText.code AS code,
                cmdCardCost.price AS cost
        FROM    cmdCardFormationMod
            LEFT JOIN cmdCardCost
                    LEFT JOIN cmdCardsText
                    ON cmdCardCost.Book = cmdCardsText.Book AND cmdCardCost.card = cmdCardsText.card 
            ON cmdCardCost.Book = cmdCardFormationMod.Book AND cmdCardCost.card = cmdCardFormationMod.card 
        WHERE   cmdCardFormationMod.Book LIKE '%" . $bookTitle . "%'
        AND     cmdCardFormationMod.formation LIKE '%Support%'");  
        */
}

$currentFormation = "Sup";
$formationNr+=1;
$formationCost[$formationNr] = 0;
if (isset($Support_DB)) {
    $combinedSupportDB =[];
    foreach ($Support_DB as $value) {
        $combinedSupportDB[] = $value;
        $currentPlatoonFormation = $value["formation"];
    }
    
    /*
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
    */
    //----------Header ----- (sup)-----
    $supportHTML[1] ="";
    foreach ($SupporboxNrs as $BoxInSection){ 
        
        $maxSupportBoxNr = $BoxInSection["box_nr"];
        $currentBoxNr =             $BoxInSection["box_nr"];
        
        $thisBoxType =              $BoxInSection["box_type"];
        $currentBoxInFormation =    $currentFormation."-".$currentBoxNr;
        $thisBoxSelectedPlatoon =   isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
        $supportHTML[1] .=          "<div id='{$currentBoxInFormation}box' class='box'><b>{$thisBoxType}</b><br>";

        
        foreach ($combinedSupportDB as $platoonInBox) {
            
            if($currentBoxNr == $platoonInBox["box_nr"]){
                
                $platoonConfigChanged = []; 
                $formationCardHTML =  "";
                $currentPlatoon =   $platoonInBox["platoon"];
                $currentUnit =      $platoonInBox["unitType"];
                $platoonTitle = $platoonInBox["title"];
                if (!isset($boxCost[$formationNr][$currentBoxNr])) {
                    $boxCost[$formationNr][$currentBoxNr] =null;
                }
                $supportHTML[1] .=  "<div class='platoon'>
                <input" . (($currentPlatoon == $thisBoxSelectedPlatoon) ? " checked" : "") . " id='{$currentBoxInFormation}box{$currentPlatoon}' type='checkbox' name='{$currentBoxInFormation}' class='{$currentBoxInFormation}' value='{$currentPlatoon}' " . <<<HTML
                onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                if (isset($platoonInBox["configChange"])&&$platoonInBox["configChange"]!="") {
                    $configChangeRow = explode("\n",$platoonInBox["configChange"]);
                    foreach ($configChangeRow as $key => $value) {
                        $temp = explode("|",$value);
                        $platoonConfigChanged[] = array("platoon" => $currentPlatoon, "configuration" => str_replace("//","\n",$temp[0]), "cost" => $temp[1], "sections" => $temp[2], "shortID" => $temp[3]);
                    }
                }
                else {
                    $platoonConfigChanged = $supportConfig;
                }
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
                    $supportHTML[1] .= generateTitleImanges($insignia,$platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$query['ntn']);
                } 
                $supportHTML[1] .= "</span><span><b>{$platoonTitle}</b><br>
                <span style='font-size: 0.7em;'>" . $currentPlatoon . "</span></span></label><br>"
// ------ Config of platoon -------------     
                . $boxConfigHTML
                . "
                </div>
            </div>"
// ------ Options of platoon -------------
                . generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $currentBoxNr, $boxCost, $formationCost)
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
if ($bookSelected)
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
        "SELECT  DISTINCT platoon , title, alliedBook AS Book, unitType, Nation
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

if (isset($BBSupport_DB)&&($BBSupport_DB instanceof mysqli_result)&&$BBSupport_DB -> num_rows > 0) {
    $otherNationBox =false;
    foreach ($BBSupport_unique_type as $unique_type) {
        
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
                    WHERE platoonConfig.platoon = '{$currentPlatoon}'");
                }
    // ----- checked and set status from session variablse for the selected platoon in the box
                $blackBoxHTML[1] .= "
                <div id='{$currentBoxInFormation}box' class='box'> 
                <div class='platoon {$platoonInBox["Nation"]}'>
                    <input" . (($currentPlatoon == $thisBoxSelectedPlatoon)&&((!$otherNationBox)||($platoonInBox["Nation"] == $query['ntn'])) ? " checked":"")." id='{$currentBoxInFormation}box{$currentPlatoon}' type='checkbox' name='{$currentBoxInFormation}' class='{$currentBoxInFormation}' value='{$currentPlatoon}' " . <<<HTML
                        onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
                    if ($otherNationBox) {
                        $query[$currentBoxInFormation]="";
                    }
                    if (($currentPlatoon == $thisBoxSelectedPlatoon)&&($platoonInBox["Nation"] != $query['ntn'])) {
                        $otherNationBox = true;
                    }
                    
    //------------ note BB specific ------------
                    $platoonConfigHTML = "";
                    if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
                        
                        $platoonConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfig, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
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
                    $blackBoxHTML[1] .= "{$CR}</div>";
    // ------ Options of platoon -------------                       
                    $blackBoxHTML[1] .= generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $currentBoxNr, $boxCost, $formationCost);

    // ------ cmdCards of platoon -------------                       
    //------------ note BB specific ------------
                    if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {
    //------- check if the platoon have cards available                    
                        $blackBoxHTML[1] .= $blackBoxTempCardHTML;
    //--- unit card (gun/infantry), soft skin etc. cost
                        $blackBoxHTML[1] .= generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, $formationCost, $boxCost, $formationNr, $currentBoxNr);
                    }
                    $blackBoxHTML[1] .= "{$CR}<br>";  
    // -------- To Here ---------

    // -------- points ----------
                if (isset($boxCost[$formationNr][$currentBoxNr])) {
                    $blackBoxHTML[1] .= "\n<div class='Points'>
                          <div>
                            " . $boxCost[$formationNr][$currentBoxNr] . " points
                          </div>
                        </div>\n";
                }
                $blackBoxHTML[1] .= "{$CR}</div>";

    // -------- To Here ----------   
            $blackBoxHTML[1] .= "</div>{$CR}";
            }
        }
    $blackBoxHTML[1] .= "{$CR}</div>{$CR}</div>";
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

if ($bookSelected) {
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

$CardBoxHTML[1] ="";

if (isset($cardSupport)&&($cardSupport instanceof mysqli_result)&&$cardSupport -> num_rows > 0||isset($cardSupport)&&!($cardSupport instanceof mysqli_result)&&count($cardSupport) > 0) {

    $CardBoxHTML[1] .= "
    <button type='button' class='collapsible'> <h3>Card Support</h3></button>
    <div class='Formation'>
        <div class='grid'>";
//---------- boxes 
    foreach ($cardSupport as $currentBoxNr => $platoonInBox){ 
// ------- set reused variables
        
        $currentPlatoon = $platoonInBox["platoon"];        
        $platoonTitle = $platoonInBox["title"];
        $blackBoxTempCardHTML = "";

        $repetsOfPlatoon = (($platoonInBox["boxNr"]==99)? 99 :1 );
        
        for ($repeats=0; $repeats < $repetsOfPlatoon; $repeats++) {
            $blackBoxTempCardHTML = "";
            $cardPlatoonIndex++;
            if (!isset($boxCost[$formationNr][$currentBoxNr])) {
                $boxCost[$formationNr][$cardPlatoonIndex] = null;
            }
            

            $currentBoxInFormation = $currentFormation ."-" . $cardPlatoonIndex;
            $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
            $currentUnit = isset($platoonInBox["unitType"])?$platoonInBox["unitType"]:"";
            
    // ----- checked and set status from session variablse for the selected platoon in the box
            if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)) {
                echo $query[$currentBoxInFormation . "Cd"] =  $platoonInBox["cardNr"];
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
            if (($platoonCards instanceof mysqli_result)&&($platoonCards -> num_rows > 0)&&(isset($query[$currentBoxInFormation])&&$currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {
                
                foreach ($platoonCards as $key5 => $row5) {

                    if ($row5["platoon"] == $currentPlatoon) { // isset code is to not show incomplete cards (price and text)
                        $cardIndex++;

                        //reset if formation card is changed
                        if (($platoonInBox["title"] == $row5["card"])&&($row5["card"]!=="")) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] = $row5["code"];
                            // echo "<!-- {$row5["card"]}-->";
                            if ($row5["pricePerTeam"] <> 0) {
                                $blackBoxTempCardHTML .= ceil($row5["cost"] * $boxSections[$formationNr][$cardPlatoonIndex] * $row5["pricePerTeam"]);
                            } else {
                                $blackBoxTempCardHTML .= $row5["cost"]*1;
                            }
                            $blackBoxTempCardHTML .= " points: " . $row5["card"] . "<br>";
                        }
                    }
                }
            }
    //-----------image -------------
            $CardBoxHTML[1] .= generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation, "", $insignia)
            . "<div  class='title'>
                <label for='{$currentBoxInFormation}box{$currentPlatoon}''><b><span class='left'>" 
                . generateTitleImanges($insignia,$platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$query['ntn']) . "</span><span>"
            . ((isset($formationCardTitle[$currentBoxInFormation])&&$formationCardTitle[$currentBoxInFormation]<>"")? "{$formationCardTitle[$currentBoxInFormation]}: " : "") . "{$platoonTitle} {$platoonInBox["cardNr"]}</span></b><br>
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
                
                
                    if ($platoonInBox["configChange"]!="") {
                        $configChangeRow = explode("\n",$platoonInBox["configChange"]);
                        foreach ($configChangeRow as $key => $value) {
                            $temp = explode("|",$value);
                            $platoonConfigChanged[] = array("platoon" => $currentPlatoon, "configuration" => str_replace("//","\n",$temp[0]), "cost" => $temp[1], "sections" => $temp[2], "shortID" => $temp[3]);
                        }
                    }
                    else {
                        $platoonConfigChanged = $platoonConfig;
                    }
                }
    // ------ Config of platoon -------------                       

                    $CardBoxHTML[1] .= processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $cardPlatoonIndex, $query, $boxCost, $formationCost, $boxSections);
                    $CardBoxHTML[1] .= "{$CR}</div>{$CR}</div>";
    // ------ Options of platoon -------------                       
                    $CardBoxHTML[1] .= generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $cardPlatoonIndex, $boxCost, $formationCost);

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
                    $CardBoxHTML[1] .= "{$CR}<br>";  
    // -------- To Here ----------
                
    // -------- points ----------
                if (isset($boxCost[$formationNr][$cardPlatoonIndex])) {
                    $CardBoxHTML[1] .= "{$CR}<div class='Points'>
                            <div>
                            " . $boxCost[$formationNr][$cardPlatoonIndex] . " points
                            </div>
                        </div>{$CR}";
                }
    // -------- To Here ----------   
            $CardBoxHTML[1] .= "</div>{$CR}";

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
    $CardBoxHTML[1] .= "{$CR}</div>{$CR}</div>";
    
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
        if (($key !== 'cost')&&(!is_numeric(strpos($key,'title')))&&($row !== "")) {
            $linkQuery .= ($key !== 'lsID') ? "&" . $key . "=" . $query[$key] : "";
        }
    }
}
$_SESSION["linkQuery"]= $linkQuery;

//-----------------------------------------------------------------------------
//------------------- HTML print-----------------------------------------------
//-----------------------------------------------------------------------------
echo "<!DOCTYPE html>";
?>
<html>
<head>
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">

    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <script src="jquery-3.7.0.min.js"></script>

    <script>
    $('#submit').click(function(e){ 
        e.preventDefault();
    });
    </script>

    <link rel='stylesheet' href='css/index<?=$beta?>.css'>
    <title>FOW List<?=((isset($bookTitle))?" - ". $bookTitle:"") . ((isset($formationTitle[1]))?" - " . $formationTitle[1]:"") . ((isset($formationTitle[2]))?" - " . $formationTitle[2]:"")?></title>
    <link rel="icon" type="image/x-icon" href="/img/<?=((isset($query["ntn"]))?$query["ntn"]:"")?>.svg">

</head>
<body>

    <br>

<?php include "menu{$beta}.php"; 
$pdo = null;?>
    <form name="form" id="form" method="get" action="<?=$_SERVER['PHP_SELF']. $targetLocation ?>">    
<br>
    <div class="page-container">
    <div id="backToTopButton">
        <a href="#top">Back to Top</a>

    </div>
    <div id="pointsOnTop">
        <div class='Points'>
            <div>
                <?=array_sum($formationCost)+$listCardCost?> points 
            </div>
        </div>
    </div>
    <input type="hidden" name="lsID" id="lsID" value="">
    <a href="listPrintGet<?=$beta?>.php?<?=$linkQuery . $costArrayStrig?>">
            <div id="viewlistOnTop">
                    View List
                    </div>
        </a>
        <br>
        <div class="disclaimer">
        Disclamer:<br>
This is a free unoficial alternative tool to generate lists for flames of war as a complement to the books and cards, that you need to have bought either physical or from here: <a href="https://forces.flamesofwar.com">the official tool</a> should always be used. This is especially true for validating lists for tournament play.</div>
    <?php
// -----------------------------------------------------
// ----------- Formation print -------------------------
// -----------------------------------------------------
if (isset($query['Book'])) {
    ?>

    <div class="header">
        <h2 class="<?=$query["ntn"]?>">
            <?php
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true);
                //echo dropdown($Nations,"","Nation","Nation",'ntn',true,"period",$query['pd'],false,"","",$query); 
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
                echo dropdown($Books,"","code","Book",'Book',true,"Nation",$query['ntn'],true,"period",$query['pd'],$query);
                echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
            ?>
            <br>
            <?=$bookTitle?>         
        </h2><br>
        <?php 
if ($query['pd']=="MW") {
    ?>
    <label for="dPs">
    <input type="checkbox" name="dPs" id="dPs" value="true" 
    <?php
    if (isset($query["dPs"])&&$query["dPs"]=="true") {
        echo "checked";
    }
    ?>
    onchange='this.form.submit();'>
Enable Dynamic Points</label>
<button type="submit" />Update</button>

        <?php 
}
?>
Select number of formation from this book: <select name='nOF' id='nOF' onchange='this.form.submit();'>
<!--                <option value='' selected disabled hidden>number of formations</option> -->
                <?php
        for ($i = 0; $i <=3; $i++) {
            echo "
                <option " . ((isset($query['nOF'])&&($i == $query['nOF'])||((!isset($query['nOF']))&&($i==1))) ? " selected " : "") . "value={$i}>{$i}</option>";
            }  
                ?>
            </select>
    </div>

    <?php 
}    
for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce; $formationNr++) {
    $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
    if (isset($query[$currentFormation])){
        
// -----------Formation Title print --------------------
    ?>
    <button type="button" class="collapsible <?=$query["ntn"]?>">
        <h3> <?php 
            if ((isset($cmdCardTitleOfEntireFormation[$formationNr])&&($cmdCardTitleOfEntireFormation[$formationNr]!="")||(is_numeric(strpos($query[$currentFormation],"C"))))) {
                echo "<div class='left'>
                    <img class='card' src='img/Card.svg'>" . generateTitleImanges($insignia, $cmdCardTitleOfEntireFormation[$formationNr] . $formationTitle[$formationNr], $query["ntn"]) . "
                </div>";
            }
        
            if (isset($cmdCardTitleOfEntireFormation[$formationNr])) {
                echo  (((isset($cmdCardTitleOfEntireFormation[$formationNr])&&$cmdCardTitleOfEntireFormation[$formationNr]!="")&&($cmdCardTitleOfEntireFormation[$formationNr]!=$formationTitle[$formationNr])) ? "
                {$cmdCardTitleOfEntireFormation[$formationNr]}: ": "");
            }
            echo isset($formationTitle[$formationNr])?$formationTitle[$formationNr]:""?>

        </h3>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:""?> points 
            </div>
        </div> 
    </button>
    <?php 
// -----------------------------------------------------
    ?>    
    <div class="Formation">
        <br> 
        <?php 
        echo "<!--{$currentFormation}-->";
        echo dropdown($Formations,"","code","title",$currentFormation,true,"Book",$bookTitle,false,"",'',$query); ?>

        
        <button type='submit' value='' onClick='<?=$currentFormation?>.value =0; this.form.submit();'>Clear</button>

        <?php
        echo  isset($cmdCardsOfEntireFormation[$formationNr])?$cmdCardsOfEntireFormation[$formationNr]:"";
        echo ((isset($formationNote[$formationNr])&&$formationNote[$formationNr]<>"")? "
        <br>" .$formationNote[$formationNr] : "" );
        echo "
        <div class='grid'>";

// ------------ Boxes print -------------- 
        foreach ($formationHTML as $formationKey => $htmlOutputRow){
            
            $position = strpos($formationKey ,"-" ); 
            if ($position !== false) {
                $boxKey = substr($formationKey, $position + strlen("-"));
                $formationKey = strstr($formationKey, "-",true);
                if ($formationKey == $currentFormation) {
                    echo $htmlOutputRow;
                }
                
     // -------- points ----------
                
                if (($formationKey == $currentFormation)&&(isset($boxCost[$formationNr][$boxKey]))) {
                     echo "
                    <div class='Points'>
                      <div>
                        {$boxCost[$formationNr][$boxKey]} points
                      </div>
                    </div>
                    ";
                }
    // -------- To Here ----------             
                if ($formationKey == $currentFormation) {
                    echo "</div>";
                }
            }
        }
        echo "
            </div>
        </div>";
    } else {
        if ($bookSelected&&isset($formationSelectButtonsHTML[$currentFormation])){
            echo $formationSelectButtonsHTML[$currentFormation];
        }
    }
}     
    
// -----------------------------------------------------
// -------from other book Formation title print --------
// -----------------------------------------------------
if (isset($query['Book'])) {
?>

   <div class="header">
        Formation from other book         

            <select name='nOFoB' id='nOFoB' onchange='this.form.submit();'>
                
                <?php
        for ($i = 0; $i <=3; $i++) {
            echo "
                <option " . ((($i == $query['nOFoB'])) ? " selected " : "") . "value={$i}>{$i} formation" . (($i!=1)?"s":"") . "</option>";
            }  
                ?>
            </select>
        
    </div>

    <?php 
}
for ($formationNr = $nrOfFormationsInForce+1; $formationNr <= $nrOfFormationsInForce+$query['nOFoB']; $formationNr++) {
    $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
echo "<!--{$currentFormation}-->";
    if (isset($query[$currentFormation])){
    ?>
    <button type="button" class="collapsible <?=$formationNation[$currentFormation . "Book"]?>">
        <h3> <?=$formationTitle[$formationNr]?>
        </h3>
            <div class='Points'>
                <div>
                    <?=$formationCost[$formationNr]?> points 
                </div>
            </div> 
    </button>
    <?php 
// -----------------------------------------------------
    ?>    
    <div class="Formation">
        <br> 
        <?php
        echo generateDroppdownHTML($currentFormation . "Book", $currentFormation . "Book", $aliedBooks[$currentFormation],true);
        //echo dropdown($Books,       "","code","Book",   $currentFormation . "Book", true,"Nation",  $query['ntn']                       ,true,"period",$query['pd'],$query);
        echo dropdown($Formations,  "","code","title",  $currentFormation,          true,"Book",    $currentBookTitle[$currentFormation]   ,false,"",'',$query); ?>
        
        <button type='submit' value='' onClick='<?=$currentFormation?>.value =0; this.form.submit();'>Clear</button>

        <?php
        echo  (($formationNote[$formationNr]<>"")? "
        <br>" .$formationNote[$formationNr] : "" );
        echo "
        <div class='grid'>";

// ------------ Boxes print -------------- 
        foreach ($formationHTML as $formationKey => $htmlOutputRow){
            
            $position = strpos($formationKey ,"-" ); 
            if ($position !== false) {
                $boxKey = substr($formationKey, $position + strlen("-"));
                $formationKey = strstr($formationKey, "-",true);
                if ($formationKey == $currentFormation) {
                    echo $htmlOutputRow;
                }
     // -------- points ----------
                
                if (($formationKey == $currentFormation)&&(isset($boxCost[$formationNr][$boxKey]))) {
                     echo "
                    <div class='Points'>
                      <div>
                        {$boxCost[$formationNr][$boxKey]} points
                      </div>
                    </div>
                    ";
                }
    // -------- To Here ----------             
                if ($formationKey == $currentFormation) {
                    echo "</div>";
                }
            }
        }
        echo "
            </div>
        </div>";
    } else {

        if (isset($query[$currentFormation . "Book"])){ 

            echo $formationSelectButtonsHTML[$currentFormation];

        } else {
            echo generateDroppdownHTML($currentFormation . "Book", $currentFormation . "Book", $aliedBooks[$currentFormation]);
            //echo dropdown($Books,       "","code","Book",   $currentFormation . "Book", true,"Nation",  $query['ntn']                       ,true,"period",$query['pd'],$query);
        }
        
    }
}
if (isset($forceCardHTML)) {
    echo $forceCardHTML;
}


//-------------------------------------------------------------------
//--------------------- Support print -------------------------------
//-------------------------------------------------------------------
if (isset($BBSupport_DB)&&!($BBSupport_DB instanceof mysqli_result)&&count($BBSupport_DB) > 0||
isset($BBSupport_DB)&&($BBSupport_DB instanceof mysqli_result)&&$BBSupport_DB -> num_rows > 0){    
    
$currentFormation = "Sup";
$formationNr+=1;

?>
<button type="button" class="collapsible"> <h3><?=$bookTitle?> Support</h3>
    <div class='Points'>
        <div>
            <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
        </div>
    </div>  
</button>

<div class='Formation'>
    <br>
    <div class="grid">
        <?=$supportHTML[1]?>
    </div> 
</div>

<?php
$currentFormation = "BlackBox";
$formationNr+=1;

}

if (isset($BBSupport_DB)&&!($BBSupport_DB instanceof mysqli_result)&&count($BBSupport_DB) > 0||isset($BBSupport_DB)&&($BBSupport_DB instanceof mysqli_result)&&$BBSupport_DB -> num_rows > 0) {

    ?>
    <div class="header">
        <h2>Formation Support</h2>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
            </div>
        </div> 
    </div>
    <?php
    echo $blackBoxHTML[1];
}

if (isset($Formation_DB)&&(count($Formation_DB) > 0)&&(!($BBSupport_DB instanceof mysqli_result)&&count($Formations) > 0)){    
    
} else {

    // ------- Selection buttons for book / nation / period ----------


    if ($bookSelected){ 
        
    } else{
        if  (isset($query['ntn'])) {
            ?>
                    <br><br>
                    <?php
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true);
                //echo dropdown($Nations,"","Nation","Nation",'ntn',true,"period",$query['pd'],false,"","",$query); 
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
                echo dropdown($Books,"","code","Book",'Book',true,"Nation",$query['ntn'],true,"period",$query['pd'],$query);
                echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
            ?>
                    <button type="button" class="collapsible"><h3>No Book selected:</h3></button>
                    <div class='Formation'>
                        <div class="grid">
        <?php
            if (count($Books)> 0) {
                foreach ($Books as $row) { 
                    if  (($row["Nation"] == $query['ntn'])&&($row["period"] == $query['pd'])) {
                        echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:90px;'>
                                    <button type='submit' name='Book' value='{$row["code"]}'>" . ((isset($query['ntn']))? "<span class='nation'><img src='img/{$query['ntn']}.svg'></span><br>" : "" ) . "{$row["Book"]}</button> <br>
                                </div>
                            </div>
                        </div>";
                    }
                }
                echo "
        </div>
        </div>
        ";
            }
        } else {       
            if  (isset($query['pd'])) {
                            ?>
        <br><br>
        <?php
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true);
                //echo dropdown($Nations,"","Nation","Nation",'ntn',true,"period",$query['pd'],false,"","",$query); 
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
                if (isset($query['ntn'])) {
                    echo dropdown($Books,"","code","Book",'Book',true,"Nation",$query['ntn'],true,"period",$query['pd'],$query);
                    echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
                }
                
            ?>
        <button type="button" class="collapsible"><h3>No nation selected <?php echo $query['pd']?> </h3></button>
        <div class='Formation'>
        <div class="grid">
        <?php
                    if (count($Nations) > 0) {
                    foreach ($Nations as $row) { 
                        if  ($row["period"] == $query['pd']) {
                            echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:70px;'>
                                    <button type='submit' name='ntn' value='{$row["Nation"]}'><span class='nation'>" . (isset($row["title"])&&(is_numeric(strpos($row["title"],"SS")))? "<img src='img/shuts.svg'>" : "<img src='img/{$row["Nation"]}.svg'>" ) . "</span><br>{$row["Nation"]}</button> <br>
                                </div>
                            </div>
                        </div>";
                        }
                    }
                    echo "
        </div>
        </div>
        ";
                }
            } else {
                echo "<br><br><br><br><button type=\"button\" class=\"collapsible\">
                <h3>No period selected</h3></button>
                <div class=\"Formation\">
                <div class=\"grid\">";
                    foreach ($Periods as $row) { 
                        echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:70px;'>
                                    <button type='submit' name='pd' value='" . $row["period"] . "'>" . $row["periodLong"] . "</button> <br>
                                </div>
                            </div>
                        </div>";
                            }
                    echo "
        </div></div>
        ";
            }
        }
    }
}


$currentFormation = "CdPl";
$formationNr+=1;

if (isset($CardBoxHTML)&&count($CardBoxHTML) > 0) {

    ?>
    <div class="header">
        <h2>Card support platoons</h2>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
            </div>
        </div> 
    </div>
    <?php
    echo $CardBoxHTML[1];
}

if (isset($boxCost)){

$_SESSION["lastPage"] = $_SERVER['PHP_SELF'];}

?>
</div>
</form>



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
}

// Select all the select elements with the 'select-element' class
const selectElements = document.querySelectorAll('form input[type="checkbox"], form select');

// Initialize the lsID with the first select element's ID
let lsID = selectElements[0].id;


// Add change event listeners to all select elements
selectElements.forEach((select) => {
    select.addEventListener('change', function () {
        // Update the lsID with the ID of the changed select element
lsID = select.id;
var parts = lsID.split("box");
lsID = parts[0];

        // Update the hidden input field's value with the lsID
        document.getElementById('lsID').value = lsID;

        // Update the hash based on the name of the last changed select element
        //window.location.hash = lsID + 'box';

        // Store the lsID in Session Storage
        sessionStorage.setItem('lsID', lsID);
 
    });
});

// On page load, retrieve the value from Session Storage
lsID = sessionStorage.getItem('lsID');

// Update the hidden input field's value
document.getElementById('lsID').value = lsID;

// Update the hash on page load
window.location.hash = lsID + 'box';
</script>
<script>

var grids = document.querySelectorAll(".grid");
grids.forEach(function(grid) {
    var boxes = grid.querySelectorAll(".box");

    // Delay the measurement
    var gridHeight = 37;
    for (var i = 0; i < boxes.length; i++) {
        var box = boxes[i];
        box.style.gridRowEnd = "span 1";
        var height = box.scrollHeight;
        // Set the grid-row property based on the height
        for (var index = 1; index <  Math.floor(height/gridHeight)+3; index++) {
            
            if ((height+12) > ((gridHeight*index))) {
                box.style.gridRowEnd = "span " + (index+1);
                
            }
        }
    }
    });

</script>
<script>
        /* Toggle between showing and hiding the navigation menu links when the user clicks on the hamburger menu / bar icon */
        function myFunction() {
          var x = document.getElementById("myLinks");
          if (x.style.display === "block") {
            x.style.display = "none";
          } else {
            x.style.display = "block";
          }
        }
    </script>

<script>
    // Show/hide the button based on scroll position
    window.onscroll = function () {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("backToTopButton").style.display = "block";
        } else {
            document.getElementById("backToTopButton").style.display = "none";
        }
    };

    // Scroll to the top when the button is clicked
    document.getElementById("backToTopButton").onclick = function () {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    };
    document.getElementById("viewlistOnTop").onclick = function () {
        $('<div class=loadingDiv>loading...<br><div class="loader"></div></div>').prependTo(document.body); 
    };
    var linkElements = document.getElementsByClassName('slowLink');
    var myLoadingFunction = function() {
        $('<div class=loadingDiv>loading...<br><div class="loader"></div></div>').prependTo(document.body);
    };
    for (var i = 0; i < linkElements.length; i++) {
        linkElements[i].addEventListener('click', myLoadingFunction, false);
    }
</script>

</body>
</html>
