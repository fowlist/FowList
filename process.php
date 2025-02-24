<?php 
include_once "functions.php";
include_once "sqlServerinfo.php";

$query = parseUrlQuery();

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

$result = $conn->query("SELECT * FROM platoonImages");
$imageDB = [];
foreach ($result as $value) {
    $imageDB[$value["code"]] = $value["image"];
}


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
            $Nations[$nation]  = [
                "Nation" => $nation, 
                "period" => $period,
                "value" => $nation,
                "description" => $nation,
                "selected" => (($query['ntn']??null) == $nation) ? 1 : 0
            ];
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




if (isset($query["lsID"])) {
    unset($query["lsID"]);
}

// translate old list adress
$bBQuery = array_filter($query, function($key) {
    return is_numeric(strpos($key,"BlackBox"));
}, ARRAY_FILTER_USE_KEY);



foreach ($bBQuery as $key => $value) {
    $needles = [
        "c",
        "Option",
        "Card",
        "uCd"
    ];
    $eval = true;
    foreach ($needles as $needle) {
        $eval &= !is_numeric(strpos($key,$needle,6));
    }

    if ($eval) {
        $query[$value."box"] = $value;
        foreach ($bBQuery as $key2 => $value2) {
            foreach ($needles as $needle) {
                if (is_numeric(strpos($key2,$needle,6))&&is_numeric(strpos($key2,$key))&&!empty($value2)) {
                    $mod = substr($key2,strlen($key));

                    $query[$value."box".$mod]=$value2;
                    unset($query[$key2]);
                }
            }
        }
        unset($query[$key]);
    }
}


// Get the latest selected select element's ID

// Generate the targetLocation based on the latest select element's ID
$targetLocation = isset($lsID) ? (($lsID) ? '#' . $lsID . 'box' : "") : "";

$maxSupportBoxNr = 0;

if (isset($query['pd'])&&$bookSelected) {
    
    $thisBookFormations = $conn->query(
        "SELECT  * 
         FROM    formations 
         WHERE   Book = '{$bookTitle}'
         AND   formations.title NOT LIKE '%Support%'");  

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



    $platoonOptionHeaders = [];

    foreach ($platoonOptionOptions as $value) {
        $code = $value["code"];
        $description = $value["description"];
        
        if (!in_array(["code" => $code,"description" => $description, "oldNr" => $value["optCode"]], $platoonOptionHeaders[$code]??[])) {
            $platoonOptionHeaders[$code][]  = ["code" => $code,"description" => $description, "oldNr" => $value["optCode"]];
        }
    }
    mysqli_data_seek($platoonOptionOptions, 0);


}

// ----------------------------------------------------------------
//  ------------------- Formation Calculation ---------------------
// ----------------------------------------------------------------

if (isset($query['nOF'])) {
    $nrOfFormationsInForce = $query['nOF'];
} else {
    if (isset($query['nOFoB'])) {
        if ($query['nOFoB'] > 0) {
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



$formationCost= [] ;
$boxCost=[];
$boxesPlatoonsData =[];
$supportBoxesPlatoonsData = [];
$formSupBoxesPlatoonsData = [];
$teamsInFormations = "";
$unitsInFormtion = "";

if (isset($query['pd'])&&isset($query["ntn"])&&isset($query['Book'])) {
    for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce+$query['nOFoB']; $formationNr++) { //  Formation 
        $boxesPlatoonsData[$formationNr]["formCost"] = 0;
        $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
        $boxesPlatoonsData[$formationNr]["currentFormation"] = "F" . $formationNr;
        $correctBook = FALSE;
        if (isset($thisBookFormations)&&$formationNr<=$nrOfFormationsInForce) {
            $Formations = $thisBookFormations;
            foreach ($Formations as $row) {
                if (($row["Book"] == $bookTitle)) {
                    if (isset($query[$currentFormation])&&$query[$currentFormation] == $row["code"]) {
                        $correctBook = TRUE;
                        $row["selected"] = true;
                    }
                    $row["value"] = $row["code"];
                    $row["description"] = $row["title"];
                    $boxesPlatoonsData[$formationNr]["thisFormationList"][]=$row;
                    $boxesPlatoonsData[$formationNr]["book"] = $row["Book"];
                }
            }
        }

        $boxesPlatoonsData[$formationNr]["thisNation"] = $query["ntn"]??null;

        if ($formationNr>$nrOfFormationsInForce) {
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
                        $formationNation[$currentFormation . "Book"] = $bookRow["Nation"];
                        $formationNation[$currentFormation . "BookTitle"] = $bookRow["Book"];
                        $boxesPlatoonsData[$formationNr]["thisNation"] = $bookRow["Nation"];
                        $boxesPlatoonsData[$formationNr]["BookTitle"] = $bookRow["Book"];
                    } 
                    $boxesPlatoonsData[$formationNr]["books"][$optionsArrayKey] = [
                        "value" => $bookRow["code"],
                        "code" => $bookRow["code"],
                        "description" => $bookRow["Nation"] . ": " . $bookRow["Book"],
                        "Nation" => $bookRow["Nation"],
                        "period" => $query['pd'],
                        "Book" => $bookRow["Nation"] . ": " . $bookRow["Book"],
                        "alliedBook" => true,
                        "selected" => ($bookRow["code"] == ($query[$currentFormation . "Book"]??null)) ? 1 : 0
                    ];
                    $optionsArrayKey++;
                }
            }
            $nationArray = array_column($boxesPlatoonsData[$formationNr]["books"], "description");
            array_multisort($nationArray, SORT_DESC, SORT_NUMERIC, $boxesPlatoonsData[$formationNr]["books"]);

            if (isset($query[$currentFormation . "Book"])&&$bookSelected) {

                $boxesPlatoonsData[$formationNr]["thisNation"] = $formationNation[$currentFormation . "Book"];
                $otherBookFormations = [];
                if (!empty($formationNation[$currentFormation . "BookTitle"])&&$formationNation[$currentFormation . "BookTitle"]!=$bookTitle) {

                    $Formations = $conn->query(
                        "SELECT  * 
                        FROM    formations 
                        WHERE   Book = '{$formationNation[$currentFormation . "BookTitle"]}'
                        AND   formations.title NOT LIKE '%Support%'");  

                    foreach ($Formations as $formationRow) {

                        if (!is_numeric(strpos($formationRow["code"],"CC"))&&$formationNation[$currentFormation . "BookTitle"]==$formationRow["Book"]) {
                            if (isset($query[$currentFormation])&&$query[$currentFormation] == $formationRow["code"]) {
                                $correctBook = TRUE;
                                $formationRow["selected"] = true;
                            }
                            $otherBookFormations[] = $formationRow;
                            $formationRow["value"] = $formationRow["code"];
                            $formationRow["description"] = $formationRow["title"];
                            $boxesPlatoonsData[$formationNr]["thisFormationList"][]=$formationRow;
                        }
                    }
                }

                //generateFormationButtonsHTML($otherBookFormations, $currentBookTitle[$currentFormation], $thisNation, $currentFormation, isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia);
            }
        } 

        if ((!$correctBook)) {
            unset($query[$currentFormation]);
        }

        if (isset($query['pd'])&&(isset($query[$currentFormation]))&&$correctBook)  {

            $Formation_DB=[];
            $Formation_DBSql = $conn->query(
               "SELECT formation_DB.*, platoonsStats.teams
                FROM    formation_DB 
                    RIGHT JOIN platoonsStats
                    ON formation_DB.platoon = platoonsStats.code 
                WHERE formation_DB.formation LIKE '" . $query[$currentFormation] . "'
                ORDER BY formation_DB.box_nr");
            foreach ($Formation_DBSql as $key => $value) {
                $Formation_DB[] = $value;
            }
        }

        if (isset($Formation_DB)&&
        query_exists($Formation_DB)&&
        isset($Formations)&&
        (isset($query['pd'])&&!empty($query[$currentFormation]))) {//  Formation

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
                WHERE   cmdCardsText.Book LIKE '%" . $bookTitle . "%'
                AND     cmdCardFormationMod.formation LIKE '%" . $query[$currentFormation] . "%'");  

        // ---- formation title and text

        foreach ($Formations as $formationRow) {
            if ($formationRow["code"] == $query[$currentFormation]||isset($query[$currentFormation . "Book"])&&$formationRow["code"] == $query[$currentFormation . "Book"]) {
                $query[$formationNr."title"]= $boxesPlatoonsData[$formationNr]["formationTitle"] = $formationRow["title"];
                $boxesPlatoonsData[$formationNr]["formationCode"] = $formationRow["code"];
                $boxesPlatoonsData[$formationNr]["formationNote"] = !empty($formationRow["Notes"])?$formationRow["Notes"]:null;
                break;
            }
        }
        if_mysqli_reset($Formations);

        // ------ cmdCards of entire formation -------------

        list($boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormation"], $boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormationTitle"]) = processFormationCards($formationNr, $formationCards, $query, $currentFormation, $formationCost[$formationNr], $boxesPlatoonsData[$formationNr]);
        $formationCost[$formationNr] += $boxesPlatoonsData[$formationNr]["formCost"];
        if (!empty($boxesPlatoonsData[$formationNr]["formCard"])) {
            $forceTitleCardUsed= $boxesPlatoonsData[$formationNr]["formCard"];
            $currentFormationCard = $conn->query(
                "SELECT  *
                FROM    cmdCardsText
                WHERE code = '" . $boxesPlatoonsData[$formationNr]["formCard"]["code"] . "'
                ");
            $currentFormationCard = $currentFormationCard ->fetch_assoc();
            $boxesPlatoonsData[$formationNr]["formCard"]["unitModifier"] = $currentFormationCard["unitModifier"];
            $boxesPlatoonsData[$formationNr]["formCard"]["statsModifier"] =$currentFormationCard["statsModifier"];
        }

        // --------- Generate structures for formation boxes and configs ------------
        //---------- boxes ---(FORM)-------

        $platoonConfig= $conn->query(
            "SELECT  *
            FROM    platoonConfigDB
            WHERE formation = '" . $query[$currentFormation] . "'
            ORDER BY cost DESC");
    
        if ($platoonConfig->num_rows > 0) {
            while ($row = $platoonConfig->fetch_assoc()) {
                if ($row['formation'] == $query[$currentFormation]) {
                    $formationSpecificPlatoonConfig[$row["shortID"]] = $row;
                }
            }
            mysqli_data_seek($platoonConfig ,0);
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

        addCardPlatoonToSection($cardPlatoon,$Formation_DB,$formationSpecificPlatoonConfig,$formationNr,$query,$conn,'formation');
        list($boxNrs,$boxTypes) = createBoxArray($Formation_DB);
        
        foreach ($boxNrs as $currentBoxNr => $BoxInSection){
            $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["thisBoxType"] =  $boxTypes[$currentBoxNr]["box_type"];

            $currentBoxInFormation = $currentFormation ."-" . $currentBoxNr;
            $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
            
            $platoonIsInThisFormation = false;
            foreach ($BoxInSection as $platoonInBox) {
                if($currentBoxNr == $platoonInBox["box_nr"]){
                    if ($platoonInBox["platoon"] ==$thisBoxSelectedPlatoon) {
                        $platoonIsInThisFormation =true;
                        break;
                    }
                }
            }
            if (!$platoonIsInThisFormation) {
                $thisBoxSelectedPlatoon ="";
                unset($query[$currentBoxInFormation . "c"]);
                unset($query[$currentBoxInFormation]);
            }

            foreach ($BoxInSection as $platoonInBox) {
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["codes"][] = $platoonInBox["cardNr"]??$platoonInBox["platoon"];
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]] = array_merge($boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]??[],$platoonInBox);
                $currentPlatoon =   $platoonInBox["platoon"];
                $currentUnit =      $platoonInBox["unitType"] ??"";

                if (!isset($boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"])) {
                    $boxCost[$formationNr][$currentBoxNr] =null;
                    $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] =null;
                }
                if (!isset($platoonInBox["BlackBox"])) {
                    if ($currentBoxNr ==2||$currentBoxNr==3) {
                        $platoonInBox["BlackBox"] = 1;
                    }
                }
                if ((($platoonInBox["box_type"] == "Headquarters")||(($platoonInBox["BlackBox"] ?? "")== 1))&&(empty($thisBoxSelectedPlatoon))) {
                    $thisBoxSelectedPlatoon = $currentPlatoon;
                    $query[$currentBoxInFormation] = $currentPlatoon;
                    if (!isset($query[$currentBoxInFormation . "c"])) {
                        $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$query[$currentBoxInFormation]]["config"]["autoset"]=true;
                    }
                }

                if (!isset($platoonInBox["image"])) {
                    $thisimage = $conn->query(
                        "SELECT image
                         FROM    platoonImages  
                         WHERE   code LIKE '%" . $platoonInBox["platoon"] . "%'");
                         foreach ($thisimage as $image) {
                            $platoonInBox["image"] =$image["image"];
                         }
                }

                foreach (explode("|",$platoonInBox["image"]??"") as $image) {
                    $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["images"][]=$image;
                }
                
// ----- checked and set status from session variablse for the selected platoon in the box
                $platoonConfigChanged = configChangedGenerate($platoonInBox, $formationSpecificPlatoonConfig);

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

                    if (!empty($platoonInBox["teams"])&&($query[$currentBoxInFormation]??false == $currentPlatoon)&&($platoonInBox["box_type"] !== "Headquarters")) {
                        $teamsInFormations .= "<>".$platoonInBox["teams"]??"";
                        $unitsInFormtion .= "<>".$platoonInBox["title"]??"";
                    }
                    $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]["selected"]=true;

                    addConfigToBoxPlatoon($platoonConfigChanged,  $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                    list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                    addOptionsToBoxPlatoon($platoonOptionHeadersChanged, $platoonOptionOptions,$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]], $query, $currentBoxInFormation);
                    addFormationCardToBoxPlatoon($formationCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                    generateCardArrays([], $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                    addPlatoonCardToBoxPlatoon($platoonCards,$boxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                    addUnitCardsToBoxPlatoon($unitCards,$boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);

                    $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                    $boxCost[$formationNr][$currentBoxNr] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                    $boxesPlatoonsData[$formationNr]["formCost"] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
                    $formationCost[$formationNr] += $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
                }
                $boxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["insignia"] = generateTitleImanges($insignia, $boxesPlatoonsData[$formationNr]["cmdCardsOfEntireFormationTitle"] . $platoonInBox["title"] . $boxesPlatoonsData[$formationNr]["formationTitle"], (isset($platoonInBox["Nation"])&&$platoonInBox["platoonNation"]<>"")?$platoonInBox["platoonNation"]:$boxesPlatoonsData[$formationNr]["thisNation"]);
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
    $Support_DB=[];
    $Support_DBsql = $conn->query(
        "SELECT  *
        FROM support_DB
        WHERE   Book = '{$bookTitle}'");
    foreach ($Support_DBsql as $value) {
        $Support_DB[] = $value;
        $currentPlatoonFormation = $value["formation"];
    }

    $supportConfig= $conn->query(
        "SELECT  *
            FROM    platoonConfigSupportDB
            WHERE formation LIKE '%{$bookTitle}%'
            ORDER BY cost DESC
        ");
    $supportPlatoonConfig =[];
    if ($supportConfig->num_rows > 0) {
        while ($row = $supportConfig->fetch_assoc()) {
            $supportPlatoonConfig[$row["shortID"]] = $row;
        }
        mysqli_data_seek($supportConfig ,0);
    }
}

if (empty($formationNr)) {
    $formationNr =1;
}
$currentFormation = "Sup";
$formationNr+=1;
$formationCost[$formationNr] = 0;
$supportBoxesPlatoonsData[$formationNr]["currentFormation"] = "Sup";
$supportBoxesPlatoonsData[$formationNr]["formCard"]= $forceTitleCardUsed??null;

if (isset($Support_DB)) { //-Support

    $supportBoxesPlatoonsData[$formationNr]["thisNation"] = $query["ntn"]??null;
    $supportBoxesPlatoonsData[$formationNr]["formCost"] = 0;
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

    addCardPlatoonToSection($cardPlatoon,$Support_DB,$supportPlatoonConfig,$formationNr,$query,$conn,'support');

    $SupporboxNrs =[];
    $boxTypes =[];
    foreach ($Support_DB as $value) {

            $boxTypes[$value["box_nr"]]["box_type"] = isset($boxTypes[$value["box_nr"]]["box_type"])?$boxTypes[$value["box_nr"]]["box_type"]:$value["box_type"];
            if ($value["box_nr"]>=$maxSupportBoxNr&&!empty($value["ogBoxNrs"])) {
                $maxSupportBoxNr = $value["box_nr"];
            }
            if ($value["box_nr"]<=$maxSupportBoxNr) {
                $SupporboxNrs[$value["box_nr"]][] =$value;
            }
    }
    
    foreach ($SupporboxNrs as $currentBoxNr => $BoxInSection){
        $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["thisBoxType"] =  $boxTypes[$currentBoxNr]["box_type"];

        $thisBoxType =              $boxTypes[$currentBoxNr]["box_type"];
        $currentBoxInFormation =    $currentFormation."-".$currentBoxNr;
        $thisBoxSelectedPlatoon =   $query[$currentBoxInFormation]??"";

        foreach ($BoxInSection as $platoonInBox) {
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["codes"][] = $platoonInBox["cardNr"]??$platoonInBox["platoon"];
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]] = array_merge($supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]??[],$platoonInBox);
            $currentPlatoon =   $platoonInBox["platoon"];
            $currentUnit =      $platoonInBox["unitType"] ??"";

            if (!isset($supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"])) {
                $boxCost[$formationNr][$currentBoxNr] =null;
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] =null;
            }

            foreach (explode("|",$imageDB[$platoonInBox["platoon"]]) as $image) {
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["images"][]=$image;
            }

            $platoonConfigChanged = configChangedGenerate($platoonInBox, $supportPlatoonConfig);
            $cardIndex =0;
            if (($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {
                foreach ($platoonCards as $key => $value) {
                    if ($value["platoon"] == $currentPlatoon && isset($value["code"])) {
                        $cardIndex++;
                        if ($value["code"] == $platoonInBox["cardNr"]) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] =  $platoonInBox["cardNr"];
                        }
                    }
                }
            }

            if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]["selected"]=true;

                addConfigToBoxPlatoon($platoonConfigChanged,  $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                addOptionsToBoxPlatoon($platoonOptionHeadersChanged, $platoonOptionOptions,$supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]], $query, $currentBoxInFormation);
                //addFormationCardToBoxPlatoon($formationCards??[],$supportBoxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                generateCardArrays([], $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                addPlatoonCardToBoxPlatoon($platoonCards,$supportBoxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                addUnitCardsToBoxPlatoon($unitCards,$supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);

                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $boxCost[$formationNr][$currentBoxNr] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $supportBoxesPlatoonsData[$formationNr]["formCost"] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
                $formationCost[$formationNr] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
            }
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["insignia"] = generateTitleImanges($insignia, $platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["platoonNation"]<>"")?$platoonInBox["platoonNation"]:$supportBoxesPlatoonsData[$formationNr]["thisNation"]);
        }
    }
} 

// ---------------------------------------------------
//  ------------------- Black box ---------------------
// ---------------------------------------------------
$formationNr+=1;
$currentFormation = "BlackBox";

$formationCost[$formationNr] = 0;
//---- SQL
if ($bookSelected) //  --Formation supoport 
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
        "SELECT  DISTINCT platoon, formationSupport_DB.title, alliedBook AS Book, unitType, Nation, motivSkillHitOn, box_type, platoonsStats.teams
        FROM formationSupport_DB
            LEFT JOIN platoonsStats
                ON formationSupport_DB.platoon = platoonsStats.code 
        WHERE Book = '{$bookTitle}'
        GROUP by platoon
        ORDER BY relevance desc");
    
    
    $BBSupport_DBformation = $conn->query(
        "SELECT  DISTINCT platoon ,  formation
        FROM formationSupport_DB
        WHERE Book = '{$bookTitle}'");

    $BBSupport_unique_type = [];

    foreach($BBSupport_DB as $currentBoxNr => $platoonInBox) {
        if (!is_numeric(strpos($unitsInFormtion . "<>",($platoonInBox["title"]??"")."<>"))) {
            $BBSupport_unique_type[$platoonInBox["box_type"]][$platoonInBox["teams"]][$platoonInBox["platoon"]] = $platoonInBox;
        }
        /*if (!is_numeric(strpos($teamsInFormations . "<>",($platoonInBox["teams"]??"")."<>"))) {
            $BBSupport_unique_type[$platoonInBox["box_type"]][$platoonInBox["teams"]][$platoonInBox["platoon"]] = $platoonInBox;
        } */
    }
}

if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)) {

    $otherNationBox =false;
    $mwAvantiGermanSupport =0;
    foreach ($BBSupport_unique_type as $unique_type => $unique_types) { //  --Formation supoport 

        $formSupBoxesPlatoonsData[$unique_type]["formCost"] = 0;
        $formSupBoxesPlatoonsData[$unique_type]["currentFormation"] = "BlackBox";
        $formSupBoxesPlatoonsData[$unique_type]["formCard"] = $forceTitleCardUsed??null;

    //---------- boxes (BB)
    foreach ($unique_types as $currentTeamType => $platoons){
        
        foreach ($platoons as $currentBoxNr => $platoonInBox){ 
    // ------- set reused variables
            $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["codes"][] = $platoonInBox["cardNr"]??$platoonInBox["platoon"];
            $currentBoxInFormation = $platoonInBox["platoon"]."box";
            $thisBoxSelectedPlatoon =   isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
            $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["thisBoxType"] =  $platoonInBox["box_type"];
            $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]] = array_merge($formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]]??[],$platoonInBox);
            $currentPlatoon = $platoonInBox["platoon"];
            $thisBoxType = $platoonInBox["box_type"];
            $currentUnit = $platoonInBox["unitType"];
            $currentPlatoonFormation ="";
            foreach ($BBSupport_DBformation as $rowForm) {
                if  ($rowForm['platoon'] == $currentPlatoon) {
                    $currentPlatoonFormation .= $rowForm["formation"];
                }
            }

            foreach (explode("|",$imageDB[$platoonInBox["platoon"]]) as $image) {
                $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$currentPlatoon]["images"][]=$image;
            }
            
            if (!isset($formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["boxCost"])) {
                $boxCost[$formationNr][$currentPlatoon] =null;
                $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["boxCost"] =null;
            }
            
            // ----- checked and set status from session variablse for the selected platoon in the box
            $bbEvalAllowed = ($currentPlatoon == $thisBoxSelectedPlatoon)&&((!$otherNationBox)||($platoonInBox["Nation"] == $query['ntn']));
            

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

            $platoonConfigHTML = "";
            if (isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation])) {

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


                $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]]["selected"]=true;

                addConfigToBoxPlatoon($platoonConfig,  $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                addOptionsToBoxPlatoon($platoonOptionHeadersChanged, $platoonOptionOptions,$formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]], $query, $currentBoxInFormation);

                generateCardArrays($formationCards, $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);

                addPlatoonCardToBoxPlatoon($platoonCards,$formSupBoxesPlatoonsData[$unique_type],$currentTeamType,$platoonInBox["platoon"],$query,$formationNr,$currentBoxInFormation);
                addUnitCardsToBoxPlatoon($unitCards,$formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$platoonInBox["platoon"]],$query,$currentBoxInFormation);

                $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["boxCost"] += $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$currentPlatoon]["platoonCost"];
                $boxCost[$formationNr][$currentPlatoon] = $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$currentPlatoon]["platoonCost"];
                $formSupBoxesPlatoonsData[$unique_type]["formCost"] += $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["boxCost"];
                $formationCost[$formationNr] += $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType]["boxCost"];

            }

            $formSupBoxesPlatoonsData[$unique_type]["boxes"][$currentTeamType][$currentPlatoon]["insignia"] = generateTitleImanges($insignia, $platoonInBox["title"], $platoonInBox["Nation"]);
        }
        
        }
    }
}


// ---------------------------------------------------
//  ------------------- Card platoons ---------------------
// ---------------------------------------------------

$currentFormation = "CdPl";
$formationNr+=1;
$cardPlatoonIndex = 0;
$currentBoxNr = 0;
$query[$currentFormation . "Sel"] = 0;
$formationCost[$formationNr] = 0;

$supportBoxesPlatoonsData[$formationNr]["currentFormation"] = "CdPl";
$supportBoxesPlatoonsData[$formationNr]["formCard"]= $forceTitleCardUsed??null;
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

    $listCardsQuery = $conn->query(
       "SELECT cmdCardsForceMod_link.card AS card, cmdCardsForceMod_link.code, cmdCardCost.price AS cost
        FROM cmdCardsForceMod_link
        LEFT JOIN cmdCardCost
        ON cmdCardsForceMod_link.Book = cmdCardCost.Book AND cmdCardsForceMod_link.card = cmdCardCost.card
        WHERE cmdCardsForceMod_link.card NOT LIKE ''
        AND cmdCardsForceMod_link.Book LIKE '%" . $bookTitle . "%'");
}



if (isset($cardSupport)&&query_exists($cardSupport)) {

    $supportBoxesPlatoonsData[$formationNr]["thisNation"] = $query["ntn"]??null;
    $supportBoxesPlatoonsData[$formationNr]["formCost"] = 0;


    foreach ($cardSupport as $platoonInBox){  //- Card platoons
// ------- set reused variables
        
        $currentPlatoon = $platoonInBox["platoon"];        
        $platoonTitle = $platoonInBox["title"];
        $blackBoxTempCardHTML = "";
        $thisBoxType = $platoonInBox["box_type"];

        $repetsOfPlatoon = (($platoonInBox["boxNr"]==99)? 10 :1 );

        for ($repeats=0; $repeats < $repetsOfPlatoon; $repeats++) {
                        
            $blackBoxTempCardHTML = "";
            $cardPlatoonIndex++;
            $currentBoxNr++;
            
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["thisBoxType"] =  $platoonInBox["box_type"];
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]] = array_merge($supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]??[],$platoonInBox);
            if (!isset($supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"])) {
                $boxCost[$formationNr][$currentBoxNr] =null;
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] =null;
            }
            
            foreach (explode("|",$imageDB[$platoonInBox["platoon"]]) as $image) {
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["images"][]=$image;
            }

            $currentBoxInFormation = $currentFormation ."-" . $cardPlatoonIndex;
            $thisBoxSelectedPlatoon = isset($query[$currentBoxInFormation])?$query[$currentBoxInFormation]:"";
            $currentUnit = isset($platoonInBox["unitType"])?$platoonInBox["unitType"]:"";
            
    // ----- checked and set status from session variablse for the selected platoon in the box
            if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)) {
                // $query[$currentBoxInFormation . "Cd"] =  $platoonInBox["cardNr"];
                $query[$currentFormation . "Sel"]++;
            }
            

            if (isset($platoonCards)&&query_exists($platoonCards)&&(isset($query[$currentBoxInFormation])&&$currentPlatoon == $query[$currentBoxInFormation])&&($platoonInBox['Book'] == $bookTitle)) {
                $cardIndex =0;
                foreach ($platoonCards as $key5 => $row5) {
                    if ($row5["platoon"] == $currentPlatoon) { // isset code is to not show incomplete cards (price and text)
                        $cardIndex++;
                        //reset if formation card is changed
                        if (($platoonInBox["title"] == $row5["card"])&&($row5["card"]!=="")) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] = $row5["code"];
                        }
                    }
                }
            }

            $platoonConfigChanged = [];
            if (!empty($query[$currentBoxInFormation])&&$currentPlatoon == $thisBoxSelectedPlatoon) {
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
                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]]["selected"]=true;

                addConfigToBoxPlatoon($platoonConfigChanged,  $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);
                list($platoonOptionHeadersChanged, $platoonOptionChanged) = platoonOptionChangedAnalysis($platoonInBox, $platoonOptionHeaders,$platoonOptionOptions);
                addOptionsToBoxPlatoon($platoonOptionHeadersChanged, $platoonOptionOptions,$supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]], $query, $currentBoxInFormation);
                //addFormationCardToBoxPlatoon($formationCards,$supportBoxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                generateCardArrays([], $platoonInBox["box_type"], $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
                addPlatoonCardToBoxPlatoon($platoonCards,$supportBoxesPlatoonsData[$formationNr],$currentBoxNr,$platoonInBox["platoon"],$query,$formationNr);
                addUnitCardsToBoxPlatoon($unitCards,$supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$platoonInBox["platoon"]],$query,$currentBoxInFormation);

                $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $boxCost[$formationNr][$currentBoxNr] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["platoonCost"];
                $supportBoxesPlatoonsData[$formationNr]["formCost"] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];
                $formationCost[$formationNr] += $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr]["boxCost"];

            }
            $supportBoxesPlatoonsData[$formationNr]["boxes"][$currentBoxNr][$currentPlatoon]["insignia"] = generateTitleImanges($insignia, $platoonInBox["title"], (isset($platoonInBox["Nation"])&&$platoonInBox["platoonNation"]<>"")?$platoonInBox["platoonNation"]:$supportBoxesPlatoonsData[$formationNr]["thisNation"]);
            
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
    
}

//-------------------------------------------------
//--------------- Force Command cards ----------------------
//--------------------------------------------------
$listCardCost =0;

$listCards  =[];
if (!empty($listCardsQuery)) {

    foreach ($listCardsQuery as $key5 => $row5) {
        $listCards[$key5] = $row5;
        $listCards[$key5]["checked"] = false;
        if  (isset($query["fCd-{$key5}"])&&(
            (str_replace("'", "", $row5["card"]) === $query["fCd-{$key5}"])||
            ($row5["code"] === $query["fCd-{$key5}"]))) {

            $listCards[$key5]["checked"]=true;
            $listCardCost += $row5["cost"];
        }
    }
}

//---- cost varibale transfer generation

if (isset($boxCost)) {
    
    $dataToTransfer = array(
    'bCt' => $boxCost,
    'fCt' => $formationCost,
    'lCt' => $listCardCost
    );

    function cleanArray(&$array) {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                cleanArray($value); // Recursive call for nested arrays
                
                // Remove the array if it's empty after cleanup
                if (empty($value)) {
                    unset($array[$key]);
                }
            } elseif (empty($value)) {
                unset($array[$key]); // Remove if the value is empty
            }
        }
    }
    
    // Call the function
    cleanArray($dataToTransfer);
    // Serialize the data array
    $serializedData = serialize($dataToTransfer);

    // Encode the serialized data
    $costArrayStrig = "&cost=" .  rtrim(strtr(base64_encode(gzdeflate($serializedData, 9)), '+/', '-_'), '=');

    $linkQuery ="";
    $first = true;
    foreach($query as $key => $row) {
        if (($key !== 'cost')&&($key !== 'loadedListName')&&(!is_numeric(strpos($key,'title')))&&(!is_numeric(strpos($key,'loaded')))&&(!empty($row))) {
            $linkQuery .= ($key !== 'lsID') ? (!$first?"&":"") . $key . "=" . $query[$key] : "";
            $first = false;
        }
    }
        
}
$_SESSION["linkQuery"]= $linkQuery;
// --- User handling
include "login.php";
$conn->close();