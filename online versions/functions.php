<?php

function if_mysqli_reset($qurey) {
    if ($qurey instanceof mysqli_result) {
        mysqli_data_seek($qurey ,0);
    }
}

function query_exists($query) {
    return (!($query instanceof mysqli_result)&&count($query) > 0)||
            ($query instanceof mysqli_result)&&$query -> num_rows > 0;
}

function fetchData($conn, $query, $sessionKey, $sql) {
    if (!isset($_SESSION[$sessionKey])||count($_SESSION[$sessionKey]) < 2) {
        $result = $conn->query($sql);
        $data = [];
        foreach ($result as $value) {
            $data[] = $value;
        }
        $result->free();
        $_SESSION[$sessionKey] = $data;
    } else {
        $data = $_SESSION[$sessionKey];
    }
    return $data;
}

function fetchDataAjax($conn, $sql) {
        $result = $conn->query($sql);
        $data = [];
        foreach ($result as $value) {
            $data[] = $value;
        }
        $result->free();
    return $data;
}

function parseUrlQuery() {
    $parts = parse_url($_SERVER['REQUEST_URI']);
    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    return $query;
}

function populatePostFromQuery($query) {
    foreach ($query as $key => $value) {
        if ($key !== 'cost') {
            $_POST[$key] = $value;
        }
    }
}

function populateSessionFromPost() {
    foreach ($_POST as $key => $value) {
        if (isset($_POST[$key])) {
            $_SESSION[$key] = $_POST[$key];
        }
    }
}

function populatePostFromSession() {
    if (isset($_SESSION["lastPage"]) && $_SESSION["lastPage"] !== $_SERVER['PHP_SELF']) {
        foreach ($_SESSION as $key => $value) {
            $_POST[$key] = $_SESSION[$key];
        }
    }
}

function dropdown($source,$valueindex,$collumnvalue,$collumnText,$globalVar,$condition1enable,$condition1_1,$condition1_2,$condition2enable,$condition2_1,$condition2_2,$query){
    $output ="";
    if (isset($source)&&query_exists($source)) {
        $output .= "
        <select name='" . $globalVar . "' id='" . $globalVar . "' onchange='if(this.value != 0) { this.form.submit(); }'>
            <option value='' selected disabled hidden>Choose here</option>";
        // output data of each row
        foreach ($source as $row) {
            if  ((isset($row[$condition1_1])&&($row[$condition1_1] == $condition1_2)||!$condition1enable)&&
                     (isset($row[$condition2_1])&&($row[$condition2_1] == $condition2_2)||!$condition2enable)) { 
                $output .=  "
            <option " . (isset($query[$globalVar])&&($row[$collumnvalue] === $query[$globalVar]) ? "selected='selected' ": "") . "value='{$row[$collumnvalue]}'>{$row[$collumnText]}</option>";
            }

        }
            if_mysqli_reset($source);
        $output .=  "
        </select>";
    } 
    return $output;
}

function generateDroppdownHTML($selectName, $selectID, $optionsArray, $onChange = true, $displayText = "Choose here") {
    $output =""; 


    if (count($optionsArray) > 0) {

        $output .= "<select name='" . $selectName . "' id='" . $selectID . (($onChange)?"' onchange='if(this.value != 0) { this.form.submit(); }":"'")."'>
            <option value='' selected disabled hidden>{$displayText}</option>\n";
        foreach ($optionsArray as $option) {

            $output .=  "<option " . ((isset($option["selected"])&&$option["selected"]==1) ? "selected='selected' ": "") . "value='{$option["value"]}'>{$option["description"]}</option>\n";
        }
        $output .=  "</select>\n";
    } else {
        $output .=  "0 results\n";
    }
    return $output;
}

function displayFormations($Formations, $bookTitle) {
    if ($bookTitle !== "" && $Formations->num_rows > 0) {
        echo "<button type='button' class='collapsible'><h3>No formation selected:</h3></button><div class='Formation'>";
        foreach ($Formations as $row) {
            if ($row["Book"] == $bookTitle) {
                echo "<div class='box'><div class='platoon'>";
                foreach (explode("|", $row["icon"], 7) as $boxImage) {
                    echo "<img src='img/" . $boxImage . ".svg'>";
                }
                echo "<div class='title'><button type='submit' name='{$row["code"]}' value='{$row["code"]}'>{$row["title"]}<br>\n{$row["code"]}</button></div></div></div>";
            }
        }
        echo "</div>";
        if_mysqli_reset($Formations);
        }
    }

function displayBooks($Books, $selectedNation, $selectedPeriod) {
    if ($selectedNation !== "" && $selectedPeriod !== "" && $Books->num_rows > 0) {
        echo "<button type='button' class='collapsible'><h3>No Book selected:</h3></button><div class='Formation'>";
        foreach ($Books as $row) {
            if ($row["Nation"] == $selectedNation && $row["period"] == $selectedPeriod) {
                echo "<div class='box'><div class='platoon'><div class='title'><button type='submit' name='Book' value='" . $row["code"] . "'>" . $row["Book"] . "</button></div></div></div>";
            }
        }
        if_mysqli_reset($Books);
    }
}

function displayNations($Nations, $selectedPeriod) {
    if ($selectedPeriod !== "" && $Nations->num_rows > 0) {
        echo "<button type='button' class='collapsible'><h3>No nation selected $selectedPeriod</h3></button><div class='Formation'>";
        foreach ($Nations as $row) {
            if ($row["period"] == $selectedPeriod) {
                echo "<div class='box'><div class='platoon'><div class='title'><button type='submit' name='Nation' value='{$row["Nation"]}'>{$row["Nation"]}</button></div></div></div>";
            }
        }
        if_mysqli_reset($Nations);
    }
}

function displayPeriods($Periods) {
    echo "<div class='Formation'><h3>No period selected</h3><br>";
    foreach ($Periods as $row) {
        echo "<div class='box'><div class='platoon'><div class='title'><button type='submit' name='period' value='" . $row["period"] . "'>" . $row["period"] . "</button></div></div></div>";
    }
    if_mysqli_reset($Periods);
}

function getCommandCards($bookTitle, $conn) {
    if (empty($bookTitle)) {
        return [];
    }
    
    $query = $conn->query("
        SELECT cmdCardsForceMod_link.card AS card, cmdCardCost.price AS cost
        FROM cmdCardsForceMod_link
        LEFT JOIN cmdCardCost
        ON cmdCardsForceMod_link.Book = cmdCardCost.Book AND cmdCardsForceMod_link.card = cmdCardCost.card
        WHERE cmdCardsForceMod_link.card NOT LIKE ''
        AND cmdCardsForceMod_link.Book LIKE '%" . $bookTitle . "%'");
    
    return $query->fetch_all(MYSQLI_ASSOC);
}

function generateForceCardHTML($listCards, $query) {
    $forceCardHTML = "";
    $listCardCost = 0;

    if (!empty($listCards)) {
        $forceCardHTML .= "<button type='button' class='collapsible'><h3>Force Command cards</h3></button>
            <div class='Formation'>
            <div class='grid'>";

        foreach ($listCards as $key5 => $row5) {
            $forceCardHTML .= "<div class='box'>
            <label for='" . "fCd" . "-" . $key5 . "'>
                <img src='img/Card.svg' style='width:36.5px;height:32.9px;'><br>
                <input ";
            if (isset($query["fCd" . "-" . $key5])&&str_replace("'", "", $row5["card"]) === $query["fCd" . "-" . $key5]) {
                $forceCardHTML .= " checked ";
                $listCardCost += $row5["cost"];
            }
            $forceCardHTML .= " type='checkbox' id='" . "fCd" . "-" . $key5 . "' name='" . "fCd" . "-" . $key5 . "' class='" . "fCd" . "-" . $key5 . "' value='" . str_replace("'", "", $row5["card"]) . "' onchange='this.form.submit();'>" . $row5["card"];
            $forceCardHTML .= "<br></label>\n<div class='Points'>
                          <div>
                            " . $row5["cost"] . " points
                          </div>
                        </div>\n";
            $forceCardHTML .= "</div>";
        }
        $forceCardHTML .=  "</div>";
        $forceCardHTML .=  "</div>";
    }
    return [$forceCardHTML,$listCardCost];
}

function generateCardArrays($formationCards, $thisBoxType, &$formationCard, $unitCards, $currentUnit, &$unitCard, $platoonCards, $currentPlatoon, &$platoonCard) {
    if (!($formationCards instanceof mysqli_result)&&count($formationCards) > 0||($formationCards instanceof mysqli_result)&&$formationCards -> num_rows > 0) {
        foreach($formationCards as $key4 =>  $row4) {
            if  (($row4["platoonTypes"] == $thisBoxType)) { 
                $formationCard[$key4] =$row4;
            } elseif ($row4["platoonTypes"] == "" || $row4["platoonTypes"] == "All") {
                $formationCard[$key4] =$row4;
            } elseif ($row4["platoonTypes"] == "Formation") {
                $formationCard[$key4] =$row4;
            }
        }
    }
    if_mysqli_reset($formationCards);

    if (isset($unitCards)&&query_exists($unitCards)) {
        foreach ($unitCards as $key4 =>  $row4) {
            if ($row4["unit"] == $currentUnit) {
                $unitCard[$key4] = $row4;
            }
        }
    }
    if_mysqli_reset($unitCards);
    if (isset($platoonCards)&&query_exists($platoonCards)) {
        foreach ($platoonCards as $key4 => $row4) {
            if ($row4["platoon"] == $currentPlatoon&&($row4["platoonTypes"]==$thisBoxType||$row4["platoonTypes"]==""||$row4["platoonTypes"]=="Platoon")) {
                $platoonCard[$key4] = $row4;
            }
        }    
    }
    if_mysqli_reset($platoonCards);

// ----------- if it has cards, print the heading 
    return  "";
}

function processFormationCards($formationNr, $formationCards, &$query, $currentFormation, &$formationCost) {
    $formationHaveCards = FALSE;
    $cardText = "";
    $HTML = "";
    $usedCards = "";
$cmdCardTitleOfEntireFormation ="";
    if (isset($formationCards)&&query_exists($formationCards)) {
        $currentCardIsAvailable = false;
        foreach ($formationCards as $row5) {
            if ($query[$currentFormation] == $row5["formation"]&&isset($query[$formationNr . "-Card"])&&$query[$formationNr . "-Card"]==$row5["code"]) {
                $currentCardIsAvailable = true;
            }
        }
        if (!$currentCardIsAvailable) {
            $query[$formationNr . "-Card"] ="";
        }
        if_mysqli_reset($formationCards);
        foreach ($formationCards as $row5) {

            if ($row5['formation'] == $query[$currentFormation] && !$formationHaveCards) {
                $HTML .= "<div> <img src='img/cardSmall.svg'> Select a card for this formation:
                <select name='{$formationNr}-Card' class='{$formationNr}Card' onchange=' this.form.submit(); '>
                    <option value=''>No card selected</option>";
                $formationHaveCards = true;
            }
            $selected = "";
            $cardValue = $row5["code"];
            if (isset($query[$formationNr . "-Card"])&&($row5["code"] === $query[$formationNr . "-Card"] && $query[$formationNr . "-Card"] != "")|| 
                (empty($query[$formationNr . "-Card"])&&!empty($query[$currentFormation])&&$query[$currentFormation] == $row5["formation"]&&is_numeric(strpos($query[$currentFormation],"C")))) {
                $selected = "selected";
                $cmdCardTitleOfEntireFormation = $row5['title']; // Set the card title
                $query[$formationNr . "-Card"] =$row5["code"];
            }

            if ($row5["platoonTypes"] == "Headquarters" && $row5["title"] !== "" && (!is_numeric(strpos($usedCards,$row5["title"])))) {
                $usedCards .= $row5["title"];
                $cardText = ($selected !== "") ? str_replace("/n", "<br>", $row5["notes"]) : $cardText;
                $HTML .= "<option value='{$cardValue}' $selected>Platoon specific cost, {$row5['title']}</option>";
            } elseif ($row5["platoonTypes"] == "" || $row5["platoonTypes"] == "All") {
                $cardText = ($selected !== "") ? str_replace("/n", "<br>", $row5["notes"]) : $cardText;
                $HTML .= "<option value='{$cardValue}' $selected>".(($row5['cost']==0)?"":"{$row5['cost']} points per platoon: " ). "{$row5['card']}</option>";
            } elseif ($row5["platoonTypes"] == "Formation") {
                $cardText = ($selected !== "") ? str_replace("/n", "<br>", $row5["notes"]) : $cardText;
                $formationCost += ($selected !== "") ? $row5["cost"] : 0;
                $HTML .= "<option value='{$cardValue}' $selected>".(($row5['cost']==0)?"":"{$row5['cost']} points for formation: ") . "{$row5['card']}</option>";
            }
        }
        if_mysqli_reset($formationCards);

        if ($formationHaveCards) {
            $HTML .= "
                </select>
                <br>{$cardText}
            </div>";
        }
    }
    $HTML .= "<br>";
    return [$HTML, $cmdCardTitleOfEntireFormation]; // Return as a single string
} 

function generateFormCardsHTML($formationCards, $thisBoxType, $currentPlatoonFormation, $currentBoxInFormation, $boxSections, $formationNr, $currentBoxNr, &$boxCost, &$formationCost, $query, &$cmdCardTitle) {
    $CR = "\n";
    $HTML = "";
    $formationHaveCards = FALSE;
    if (!isset($formationCards)||!query_exists($formationCards)) {
        return;
    }
    $usedCards = "";
    foreach ($formationCards as $key5 => $row5) {
        if ((is_numeric(strpos($currentPlatoonFormation,$row5['formation']))) && ($row5["code"] != "") && (!is_numeric(strpos($usedCards,$row5["code"]))) ) {
            if (!$formationHaveCards) {
                $HTML .= "{$CR}<label><img src='img/cardSmall.svg'>
                    <select name='{$formationNr}-{$currentBoxNr}-Card' class='{$formationNr}-{$currentBoxNr}-Card' onchange=' this.form.submit(); '>
                        <option value=''>No card selected</option>";
                $formationHaveCards = true;
            }
            $usedCards .= $row5["code"];
            $selected = "";
            $cardValue = $row5["code"];
            $thisCost = round($row5["cost"] * $boxSections[$formationNr][$currentBoxNr]["total"] * $row5["pricePerTeam"]);
            $thisCost = max($thisCost,1.0);
            if (isset($query[$formationNr ."-". $currentBoxNr . "-Card"] )&&$row5["code"] == $query[$formationNr ."-". $currentBoxNr . "-Card"] && $query[$formationNr ."-". $currentBoxNr . "-Card"] != "") {
                $selected = "selected";
                $cmdCardTitle = $row5['title'];
                if ($row5["pricePerTeam"]<>0) {

                    $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                    $formationCost[$formationNr] += $thisCost;
                } else {
                    $boxCost[$formationNr][$currentBoxNr] += $row5["cost"];
                    $formationCost[$formationNr] += $row5["cost"];
                }
            }

            if ($row5["platoonTypes"] == $thisBoxType && $row5["title"] !== "") {
                $HTML .= "<option value='{$cardValue}' $selected>{$row5['cost']} points {$row5['title']}</option>";
            } elseif ($row5["platoonTypes"] == "" || $row5["platoonTypes"] == "All") {
                $HTML .= "<option value='{$cardValue}' $selected>{$row5['cost']} points {$row5['card']}</option>";
            } elseif ($row5["platoonTypes"] == "Formation") {
                $HTML .= "<option value='{$cardValue}' $selected>{$row5['cost']} points {$row5['card']}</option>";
            }
        }
    }
    if ($formationHaveCards) {
        $HTML .= "</select></label><br>";                            
    }
    return $HTML;
}



function actualSectionsEval($platoonConfigRow, &$boxSections) {
    $boxSections["total"] = 0;
    $boxSections["sections"] ="";
    $boxSections["teams"] = $platoonConfigRow["teams"];
    // Modify the boxSections variable by reference
    if (isset($platoonConfigRow["actualSections"])) {
        foreach (explode("|", $platoonConfigRow["actualSections"], 15) as $key1 => $boxSection) {
            $parts = explode("x", $boxSection, 15);
            if (count($parts) > 1) {
                $numbersOfTeams = intval($parts[0])*intval($parts[1]);
            } else {
                $numbersOfTeams = $boxSection;
            }
            $boxSections["total"] += $numbersOfTeams;
            $boxSections["sections"] .= (($boxSections["sections"]=="")?"":"|") . $numbersOfTeams;
        }
    }
    $boxSections["nrOfTeams"] = array_sum(explode("|",$boxSections["sections"]));

}


function processPlatoonConfig($currentPlatoon, $platoonConfig, $currentBoxInFormation, $formationNr, $currentBoxNr, &$query, &$boxCost, &$formationCost, &$boxSections) {
    $boxConfigHTML = "";
    $boxSections[$formationNr][$currentBoxNr]["total"] = 0;
    $boxSections[$formationNr][$currentBoxNr]["sections"] ="";

    if (isset($platoonConfig)&&query_exists($platoonConfig)&& isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation]) && (isset($query[$currentBoxInFormation]))) {
        $boxConfigHTML .= "<select id='{$currentBoxInFormation}' name='{$currentBoxInFormation}c' class='select-element' onchange='{ this.form.submit(); }'>\n";
        $configSelected = FALSE;
        if (!isset($query[$currentBoxInFormation . "c"])||(!is_numeric(strpos($query[$currentBoxInFormation . "c"],$query[$currentBoxInFormation])))) {
            $query[$currentBoxInFormation . "c"] =null;
        }
        foreach ($platoonConfig as $row4) {
            if ($row4["platoon"] == $currentPlatoon) {
                $boxConfigHTML .= "\n<option ";
                if ((!isset($query[$currentBoxInFormation . "c"]) && (!$configSelected)) || isset($query[$currentBoxInFormation . "c"])&&($row4["shortID"] === $query[$currentBoxInFormation . "c"])) {
                    $query[$currentBoxInFormation . "c"] = $row4["shortID"];
                    $configSelected = true;
                    $boxConfigHTML .= "selected ";

                    // Modify the boxCost variable by reference
                    if (isset($query["dPs"])&&($query["dPs"]=="true")&&($row4["dynamicPoints"]!="")&&(isset($row4["dynamicPoints"]
                    ))) {
                        $boxCost[$formationNr][$currentBoxNr] += $row4["dynamicPoints"] * 1;
                        $formationCost[$formationNr] += $row4["dynamicPoints"] * 1;
                    } else {
                        $boxCost[$formationNr][$currentBoxNr] += $row4["cost"] * 1;
                        $formationCost[$formationNr] += $row4["cost"] * 1;
                    }
                    actualSectionsEval($row4, $boxSections[$formationNr][$currentBoxNr]);

                }
                
                $boxConfigHTML .= "value='" . str_replace("\n", " ", $row4["shortID"]) . "'>" . str_replace("\n", "<br>", $row4["configuration"]) . ": ".((isset($query["dPs"])&&($query["dPs"]=="true")&&($row4["dynamicPoints"]!=""))? $row4["dynamicPoints"] : $row4["cost"]) . " points</option>\n";
            }
            
        }
        if_mysqli_reset($platoonConfig);
        $boxConfigHTML .= "</select><br>\n";
    }

    return $boxConfigHTML;
}

function processFormationCardHTML($formationCards, $query, $currentFormation, $formationNr, $currentBoxNr, $thisBoxType, &$boxCost, &$formationCost, $boxSections, $cmdCardTitleOfEntireFormation, $thisBoxSelectedPlatoon, &$formationCardTitle, $currentPlatoon) {
    $CR = "\n"; // Define the carriage return character
    $formationCardHTML = "";
    $formationCardToggle =TRUE;
    if ((isset($formationCards)&&query_exists($formationCards)) && isset($query[$formationNr . "-Card"])&&($query[$formationNr . "-Card"] <> "") && ($thisBoxSelectedPlatoon == $currentPlatoon)) {
        $formationCardTitle[$currentFormation ."-" . $currentBoxNr] = $cmdCardTitleOfEntireFormation[$formationNr];

        foreach ($formationCards as $row4) {
            $thisCost = round($row4["cost"] * $boxSections[$formationNr][$currentBoxNr]["total"] * $row4["pricePerTeam"]);
            if  ($thisCost >= 0 ) {
                $thisCost = max($thisCost,1.0);
            } else {
                $thisCost = min($thisCost,-1.0);
            }
            if ($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"] && (($thisBoxType == $row4["platoonTypes"]) || ($row4["platoonTypes"] == "All")) && ($row4["code"] != "")) {
                // -- box command card summary cost --
                if ($row4["pricePerTeam"] <> 0) {
                    $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                    $formationCost[$formationNr] += $thisCost;
                } else {
                    $boxCost[$formationNr][$currentBoxNr] += $row4["cost"];
                    $formationCost[$formationNr] += $row4["cost"];
                }
            }

            // -- formation card cost --
            if (($row4["platoonTypes"] == "Formation") && (($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"]) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["code"] != "")) {
                // -- save title to print in each box --
                $formationCardTitle[$currentBoxNr] = $row4["title"];
                if (($row4["pricePerTeam"] != 0) && ($row4["platoonTypes"] != "Formation")) {
                    $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                    $formationCost[$formationNr] += $thisCost;
                } else if (($row5["platoonTypes"] = "Formation") && ($formationCardToggle == TRUE)) {
                                        //$formationCost[$formationNr] += $row4["cost"];
                    $formationCardToggle = FALSE;
                } else if ($row5["platoonTypes"] != "Formation") {
                    $formationCost[$formationNr] += $row4["cost"];
                    $boxCost[$formationNr][$currentBoxNr] += $row4["cost"];
                }
            }
            if ((($query[$formationNr . "-Card"] == str_replace("'", "", $row4["card"])) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == $thisBoxType) && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . (($row4["pricePerTeam"] <> 0) ? " at: " .$thisCost : ($row4["cost"]== 0?"":" at: " .$row4["cost"]. " point"))  . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            } elseif ((($query[$formationNr . "-Card"] == str_replace("'", "", $row4["card"])) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == "Formation") && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . "<br>";
            } elseif ((($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"]) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == $thisBoxType) && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . (($row4["pricePerTeam"] <> 0) ? " at: " .$thisCost : ($row4["cost"]== 0?"":" at: " .$row4["cost"]. " point")) . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            } elseif ((($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"]) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == "All") && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . " at: " . (($row4["pricePerTeam"] <> 0) ? " at: " .$thisCost : ($row4["cost"]== 0?"":" at: " .$row4["cost"]. " point")) . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            }
        }
        if_mysqli_reset($formationCards);
    }
    return $formationCardHTML;
}

function generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $currentBoxNr, &$boxCost, &$formationCost, &$boxSections =[]) {
    $CR = "\n";
    $formationHTML = "";
    $platoonOption = [];

    if (isset($query[$currentBoxInFormation])&&(count($platoonOptionHeaders) > 0) && $currentPlatoon == $query[$currentBoxInFormation]) {
        foreach ($platoonOptionHeaders as $key => $row4) {
            if ($row4["code"] == $currentPlatoon) $platoonOption[$key] = $row4;
        }
        if (count($platoonOption) > 0) {
            foreach ($platoonOption as $key5 => $row5) {
                if ($row5["code"] == $currentPlatoon) {
                    $OptionsCount = [];
                    foreach ($platoonOptionOptions as $row4) {
                        if ($row4["code"] == $currentPlatoon && $row5["description"] == $row4["description"]) {
                            $OptionsCount[] = $row4;
                        }
                    }
                    if_mysqli_reset($platoonOptionOptions);
                    if (count($OptionsCount) > 1) {
                        $formationHTML .= "{$CR}<br>{$row5["description"]}" . ((empty($query["dPs"])||$OptionsCount[0]["dynamicPoints"]=="")?"":"(dynamic: {$OptionsCount[0]["dynamicPoints"]} point)") . "<br>
                        <select 
                        name='{$currentBoxInFormation}Option{$key5}' 
                        id='{$currentBoxInFormation}box-Option{$key5}' 
                        class='{$currentBoxInFormation}Option' 
                        onchange='this.form.submit(); '>
                        <option value=''>No option selected</option>";
                        foreach ($OptionsCount as $row4) {
                            $formationHTML .= "\n<option ";
                            if (str_replace("\n", " ", $row4["optionSelection"]) === ($query[$currentBoxInFormation . "Option" . $key5]?? "")) {
                                $formationHTML .= "selected ";
                                if (isset($query["dPs"])&&($query["dPs"]=="true")&&($row4["dynamicPoints"]!="")) {
                                    $boxCost[$formationNr][$currentBoxNr] += $row4["dynamicPoints"] * 1;
                                    $formationCost[$formationNr] += $row4["dynamicPoints"] * 1;
                                } else {
                                    $boxCost[$formationNr][$currentBoxNr] += $row4["price"];
                                    $formationCost[$formationNr] += $row4["price"];
                                }
        
                            }
                            $formationHTML .= "value='" . str_replace("\n", " ", $row4["optionSelection"]) . "'>{$row4["optionSelection"]} (" . ((empty($query["dPs"])||$row4["dynamicPoints"]=="")?"{$row4["price"]}":"dynamic: {$row4["dynamicPoints"]}") . " points)</option>";
                        }
                        $formationHTML .= "{$CR}</select>{$CR}<br>";
                    } else {
                        foreach ($OptionsCount as $row4) {
                            $formationHTML .= "\n<label><input ";
                            if (isset($query[$currentBoxInFormation . "Option" . $key5])&&str_replace("\n", " ", $row4["optionSelection"]) === $query[$currentBoxInFormation . "Option" . $key5]) {
                                $formationHTML .= " checked ";
                                $boxCost[$formationNr][$currentBoxNr] += $row4["price"];
                                $formationCost[$formationNr] += $row4["price"];
                            }
                            $formationHTML .= " 
                            type='checkbox' 
                            name='{$currentBoxInFormation}Option{$key5}' 
                            id='{$currentBoxInFormation}box-Option{$key5}' 
                            class='{$currentBoxInFormation}Option' 
                            value='" . str_replace("\n", " ", $row4["optionSelection"]) . "' 
                            onchange=' this.form.submit(); '>{$row5["description"]}</label><br>";
                        }
                    }
                    if ($platoonOptionOptions instanceof mysqli_result) {
                        mysqli_data_seek($platoonOptionOptions ,0);
}
                }
            }
        }
        $formationHTML .= "
                <br>";
    }
    return $formationHTML;
}
function generateDynamicGrid($data) {
    echo '<div class="row-container">';
        echo "<div class='cell cell-title'><b>{$data["title"]}</b></div>";

        // Optional content for the third column
        $optionalContent = '';
        $stringLength = strlen($data["notes"])<250?" style='flex-wrap: nowrap'":"";
        $stringLength = strlen($data["notes"])>400?" style='max-width:180px'":$stringLength;
        $stringLength = strlen($data["notes"])>1100?" style='max-width:90px'":$stringLength;
        foreach ($data as $key => $value) {
            if ($key != "notes"&& $key != "title"&&!empty($value)&&!is_numeric(strpos($key,'points'))) {
                $optionalContent .= !empty($value)?"<div class='optional-item'>{$value}</div>":"";
            }
        }
        // If there’s content, display it in the third column
        if ($optionalContent) {
            echo "<div class='cell cell-optional'{$stringLength}>{$optionalContent}</div>";
        }
        $optionalContent = '';
        foreach ($data as $key => $value) {
            if (is_numeric(strpos($key,'points'))) {
                $optionalContent .= !empty($value)?"<div class='optional-item'>{$value}</div>":"";
            }
        }
        if ($optionalContent) {
            echo "<div class='cell cell-optional'{$stringLength}>{$optionalContent}</div>";
        }
        echo "<div class='cell cell-notes'>{$data["notes"]}</div>";
    echo '</div>';
}

function motivationBox($platoonMotivation) {
    $motivationArray =[];
    $noCols =0;
    $textSeparatedArray=[];
    $HTML = "";
    foreach (explode("\n",$platoonMotivation) as $line) {
        if ((strrpos($line, "|") != FALSE)) {
            $columns = explode("|", $line);
        } else {
            $columns = [$line];
        }
        $noCols = max($noCols, count($columns));
        $motivationArray[] = $columns;
    }
    $rows =  count(array_column($motivationArray,0));
    $diceRolls = ["Auto","1+","2+","3+","4+","5+","6"];

    foreach ($motivationArray as $rowKey => $motivationRow) {
        foreach ($motivationRow as $columnKey => $motivationColumn) {
            if (($rowKey == 0)) {
                $textSeparatedArray[$rowKey][$columnKey]['class']="grid-heading";
            } elseif ($rowKey == $rows-1) {
                $textSeparatedArray[$rowKey][$columnKey]['class']= "grid-bottom";
            } else {
                $textSeparatedArray[$rowKey][$columnKey]['class']= "mot-grid-item";
            }
            if (($rowKey > 0) && ($columnKey > 0)) {
                $textSeparatedArray[$rowKey][$columnKey]['class'] = "grid-addon";
                $textSeparatedArray[$rowKey][$columnKey]['text'] = $motivationColumn;
            } 
            //if ((strlen($motivationColumn) - strrpos($motivationColumn, " ")<4)&&(!is_numeric(strrpos(substr($motivationColumn,-4),"MG")))){
            $isDiceRollNeeded =false;
            foreach ($diceRolls as $needle) {
                if (is_numeric(strrpos($motivationColumn,$needle))) {
                    $isDiceRollNeeded = true;
                }
            }
            if ($isDiceRollNeeded){
                if (is_numeric(strrpos($motivationColumn, " "))) {
                    $textSeparatedArray[$rowKey][$columnKey]['class'] .= " b";
                    $textSeparatedArray[$rowKey][$columnKey]['text'] = substr($motivationColumn, 0 , strrpos($motivationColumn, " ")) . " </span> <span class='right'>" . substr($motivationColumn, strrpos($motivationColumn, ' ') + 1,(strlen($motivationColumn))-1);
                  }else {
                    $textSeparatedArray[$rowKey][$columnKey]['text'] =  $motivationColumn;
                }
            } else {
                $textSeparatedArray[$rowKey][$columnKey]['text'] =  $motivationColumn;
            }
            
        }
    }
    $HTML = "<div " . (($noCols > 1)?" class='doubleGrids'":" class='grids'") . ">";
    foreach ($textSeparatedArray as $rowKey => $textSeparatedRow) {
        $HTML .= "<div class='{$textSeparatedRow[0]["class"]}'>" . (($rowKey == 0)?"<div>":"") . "<span>{$textSeparatedRow[0]["text"]}</span></div>" . (($rowKey == 0)?"</div>":"");
        if ($noCols>1) {
            if (isset($textSeparatedRow[1])&&$textSeparatedRow[1]["text"] != "") {
                $HTML .= "<div class='{$textSeparatedRow[1]["class"]}'>" . (($rowKey == 0)?"<div>":"") . "<span>" . $textSeparatedRow[1]["text"] . (($noCols>2)?"|".$textSeparatedRow[2]["text"]:"") . "</span></div>" . (($rowKey == 0)?"</div>":"");
            } elseif ($rowKey % 2 == 1&&$rowKey>2) {
                $HTML .= "<div class='grid-addon'></div>";
            } else {
                $HTML .= "<div></div>";
            }
        }
    }   
    $HTML .= "</div>";
    return $HTML;
}

function saveBox($platoonSaveText, $platoonSaveChanged = ""){
    $HTML = "<div class='Armour'>{$platoonSaveChanged}";
    foreach (explode("\n",$platoonSaveText,3) as $key1 => $row3){
        $HTML .= "<div>
                <span class='bgBlack'>". substr($row3, 0 , strrpos($row3, " ")) . " </span> <span class='right'>" . substr($row3, strrpos($row3, ' ') + 1) . "</span><br>
            </div>";
    }
    $HTML .=  "
        </div>";
    return $HTML;
}


function generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, &$query, $platoonCard, $boxSections, &$formationCost, &$boxCost, $formationNr, $currentBoxNr, &$replacePlatoonTitle, $formationCardTitle) {
    $formationHTML = "";
    $cardsForBox = [];
    $cardsPrerequisitAddOnEvalSource ="";
    for ($cardPrerequisitIndex=0; $cardPrerequisitIndex < 20; $cardPrerequisitIndex++) { 
        $cardsPrerequisitAddOnEvalSource .= isset($query[$currentBoxInFormation . "Card" . $cardPrerequisitIndex])? (($cardPrerequisitIndex>0&&$cardsPrerequisitAddOnEvalSource!=""?"|":"") . $query[$currentBoxInFormation . "Card" . $cardPrerequisitIndex]):"";
    }
    $cardsPrerequisitEvalSource = isset($query["{$formationNr}-Card"])?$query["{$formationNr}-Card"]:"";
    $cardsPrerequisitEvalSource .= isset($query["1-Card"])?$query["1-Card"]:"";
    for ($i=0; $i < 6; $i++) { 
        $cardsPrerequisitEvalSource .= isset($query["{$currentBoxInFormation }uCd{$i}"])?$query["{$currentBoxInFormation }uCd{$i}"]:"";
    }
    $cardIndex = 0; 

    if (isset($platoonCard)&&query_exists($platoonCard)) {
        foreach ($platoonCard as $PlatoonCardRow) {
            $cardPlatoonEval = (!$PlatoonCardRow["onlyAddCard"]||$PlatoonCardRow["card"]==$replacePlatoonTitle);
            $thisCardIsLimitedaAndUsedSomewhereElse = (!empty($query[$PlatoonCardRow["code"]]) && $query[$PlatoonCardRow["code"]]!=$currentBoxInFormation);
            $generalEval = !$thisCardIsLimitedaAndUsedSomewhereElse&&$PlatoonCardRow["platoon"] == $currentPlatoon && isset($PlatoonCardRow["code"])&&$cardPlatoonEval;
            if ($generalEval) {
                if (isset($query["dPs"])&&($query["dPs"]=="true")&&(!empty($PlatoonCardRow["dynamicPoints"]))) {
                    $useCost = $PlatoonCardRow["dynamicPoints"];
                } else {
                    $useCost = $PlatoonCardRow["cost"];
                }


                $cardIndex++;
            }
            $cardsPrerequisitEval = true;
            if ((!empty($PlatoonCardRow["prerequisite"])&&!is_numeric(strpos($currentBoxInFormation,"BlackBox")))&&$PlatoonCardRow["prerequisite"]!="Warrior"&&$PlatoonCardRow["prerequisite"]!="AddOn"&&$PlatoonCardRow["prerequisite"]!="Limited") {
                $cardsPrerequisitEval =false;

                foreach (explode("|",$PlatoonCardRow["prerequisite"]) as $value) {
                    $cardsPrerequisitEval = is_numeric(strpos($cardsPrerequisitEvalSource,$value)) || $cardsPrerequisitEval;
                    }
                }
            if (!empty($PlatoonCardRow["prerequisite"])&&substr($PlatoonCardRow["prerequisite"],0,5)=="AddOn") {
                foreach (explode("|",$PlatoonCardRow["prerequisite"]) as $value) {
                    $cardsPrerequisitEval = is_numeric(strpos($cardsPrerequisitAddOnEvalSource,$value)) || $cardsPrerequisitEval;
                }
            }

            if ($cardsPrerequisitEval&&$generalEval) { // isset code is to not show incomplete cards (price and text)
                $thisCardEval = isset($query[$currentBoxInFormation . "Card" . $cardIndex])&&($PlatoonCardRow["code"] === $query[$currentBoxInFormation . "Card" . $cardIndex]) || (($PlatoonCardRow["title"] != "")&&($formationCardTitle == $PlatoonCardRow["title"]));
                //reset if formation card is changed
                if (($formationCardTitle != $PlatoonCardRow["title"])&&($formationCardTitle != "")&&($PlatoonCardRow["title"] != "")) {
                    $query[$currentBoxInFormation . "Card" . $cardIndex] ="";
                }
                
                if ($PlatoonCardRow["multiSelect"]!="") {
                    $optionsInCard = explode("|",$PlatoonCardRow["multiSelect"]);
                    $cardsForBox["{$currentBoxInFormation}Card{$cardIndex}"]["optionsInCardNr"] = $optionsInCard[0]+1;
                    $cardsForBox["{$currentBoxInFormation}Card{$cardIndex}"]["value"] = $PlatoonCardRow['code'];
                    $formationHTML .= "\n<label><img src='img/cardSmall.svg'>{$PlatoonCardRow["card"]}<br>
                    <select 
                    name='{$currentBoxInFormation}Card{$cardIndex}' 
                    id='{$currentBoxInFormation}box-Card{$cardIndex}' 
                    class='{$currentBoxInFormation}{$currentPlatoon}Card' 
                    value='{$PlatoonCardRow['code']}' 
                    onchange='this.form.submit();'>
                    <option value=''>No option selected</option>";
                    for ($optionNumber=1; $optionNumber < $optionsInCard[0]+1; $optionNumber++) { 
                        $formationHTML .= "\n<option ";
                        
                        if ((empty($query["Warrior"])||(isset($query["Warrior"])&&$PlatoonCardRow["code"]==$query["Warrior"]||($PlatoonCardRow["prerequisite"]!="Warrior")))&&isset($query[$currentBoxInFormation . "Card" . $cardIndex])&&($PlatoonCardRow["code"].$optionNumber === $query[$currentBoxInFormation . "Card" . $cardIndex])) {
                            $formationHTML .= "selected ";
                            $cardsForBox["{$currentBoxInFormation}Card{$cardIndex}"]["optionsInCard"][$optionNumber]["selected"]= true;

                            if ($PlatoonCardRow["pricePerTeam"]!=0) {
                                $boxCost[$formationNr][$currentBoxNr] += round($useCost * $optionNumber * $PlatoonCardRow["pricePerTeam"]);
                                $formationCost[$formationNr] +=  round($useCost * $optionNumber * $PlatoonCardRow["pricePerTeam"]);
                            } else {
                                $boxCost[$formationNr][$currentBoxNr] += round($useCost * $optionNumber);
                                $formationCost[$formationNr] +=  round($useCost * $optionNumber);
                            }

                            if ($PlatoonCardRow["prerequisite"]=="Warrior") {
                                $query["Warrior"] = $PlatoonCardRow["code"];
                            }
                            if (!empty($PlatoonCardRow["limited"])) {
                                $query[$PlatoonCardRow["code"]] = $currentBoxInFormation;
                            }
                            if (($formationCardTitle == $PlatoonCardRow["title"])) {
                                $formationCardTitle ="";
                            }
                        }
                        if (!empty($query["Warrior"])&&$PlatoonCardRow["code"]!=$query["Warrior"]&&$PlatoonCardRow["prerequisite"]=="Warrior") {
                            unset($query[$currentBoxInFormation . "Card" . $cardIndex]);
                        }
                        $cardsForBox["{$currentBoxInFormation}Card{$cardIndex}"]["optionsInCard"][$optionNumber]["value"] = '{$PlatoonCardRow["code"]}{$optionNumber}';
                        $cardsForBox["{$currentBoxInFormation}Card{$cardIndex}"]["optionsInCard"][$optionNumber]["price"] = (($PlatoonCardRow["pricePerTeam"]!=0)?round($useCost * $optionNumber * $PlatoonCardRow["pricePerTeam"]):round($useCost * $optionNumber));
                        $formationHTML .= "value='{$PlatoonCardRow["code"]}{$optionNumber}'>
                        $optionsInCard[1] {$optionNumber} (" . (($PlatoonCardRow["pricePerTeam"]!=0)?round($useCost * $optionNumber * $PlatoonCardRow["pricePerTeam"]):round($useCost * $optionNumber))." points)</option>";
                    }
                    $formationHTML .= "\n</select></label>";
                } 

                if (empty($PlatoonCardRow["multiSelect"])) {
                    $formationHTML .= "
                    <label><img src='img/cardSmall.svg'><input ";
                    $thisCost = round($useCost * 
                    $boxSections[$formationNr][$currentBoxNr]["total"] * 
                    $PlatoonCardRow["pricePerTeam"]);

                $thisCost = ($thisCost==0)?1:$thisCost;
                    if ((empty($query["Warrior"])||(isset($query["Warrior"])&&$PlatoonCardRow["code"]==$query["Warrior"]||($PlatoonCardRow["prerequisite"]!="Warrior")))&&$thisCardEval) {
                    $formationHTML .= " checked ";
                        $query[$currentBoxInFormation . "Card" . $cardIndex] = $PlatoonCardRow["code"];
                        if ($PlatoonCardRow["pricePerTeam"] <> 0) {
                        $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                        $formationCost[$formationNr] += $thisCost;
                    } else {
                            $boxCost[$formationNr][$currentBoxNr] += $useCost;
                            $formationCost[$formationNr] += $useCost;
                    }
                        if ($PlatoonCardRow["prerequisite"]=="Warrior") {
                            $query["Warrior"] = $PlatoonCardRow["code"];
                        }
                        if (!empty($PlatoonCardRow["limited"])) {
                            $query[$PlatoonCardRow["code"]] = $currentBoxInFormation;
                        }
                }
                $formationHTML .= " type='checkbox' 
                    name='" . $currentBoxInFormation . "Card" . $cardIndex . "' 
                    id='" . $currentBoxInFormation . "box-Card" . $cardIndex . "' 
                    class='" . $currentBoxInFormation . $currentPlatoon . "Card" . "' 
                    value='" . $PlatoonCardRow['code'] . "' 
                    onchange='this.form.submit();'>";
                    if ($useCost!= 0) {
                    
                        if ($PlatoonCardRow["pricePerTeam"] != 0) {
                    $formationHTML .= $thisCost;
                } else {
                            $formationHTML .= $useCost;
                }
                        $formationHTML .= " points: ";
                    }
                    $formationHTML .=  $PlatoonCardRow["card"] . "</label><br>";
            }
                if ($thisCardEval) {
                    if (($formationCardTitle == $PlatoonCardRow["title"])) {
                        $formationCardTitle ="";
                    }
                    if ($PlatoonCardRow["replacedText"] == "After") {
                        $replacePlatoonTitle = $replacePlatoonTitle . $PlatoonCardRow["replaceWith"];
                    } elseif (($PlatoonCardRow["replacedText"] == "") && ($PlatoonCardRow["replaceWith"] != "")) {
                        $replacePlatoonTitle = $PlatoonCardRow["replaceWith"] . $replacePlatoonTitle;
                    } elseif ($PlatoonCardRow["replaceWith"] != "") {
                        $replacePlatoonTitle = str_replace($PlatoonCardRow["replacedText"] , $PlatoonCardRow["replaceWith"], $replacePlatoonTitle);
                    }
                    if (($formationCardTitle == "")&&($PlatoonCardRow["title"] != "")) {
                        $replacePlatoonTitle = "{$PlatoonCardRow["title"]}: {$replacePlatoonTitle}";
                    }
                }
            }
            if (!empty($query["Warrior"])&&$PlatoonCardRow["code"]!=$query["Warrior"]&&$PlatoonCardRow["prerequisite"]=="Warrior") {
                unset($query[$currentBoxInFormation . "Card" . $cardIndex]);
            }
            if (!empty($query[$currentBoxInFormation . "Card" . $cardIndex])&&!$cardsPrerequisitEval&&($PlatoonCardRow["code"] === $query[$currentBoxInFormation . "Card" . $cardIndex])) {
                unset($query[$currentBoxInFormation . "Card" . $cardIndex]);
            }
        }
    }
    return $formationHTML;
}

function generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, &$formationCost, &$boxCost, $formationNr, $currentBoxNr) {
    $CR = "\n";
    $formationHTML = "";
    $cardIndex = 0; // Initialize the card index.
if (isset($unitCard)&&query_exists($unitCard)) {
        foreach ($unitCard as $row5) {
            if ($row5["unit"] == $currentUnit && $row5["code"] != "") {
                $cardIndex++;
                $formationHTML .= "{$CR}<label><img src='img/cardSmall.svg'><input ";
                $thisCost = round($row5["cost"] * $boxSections[$formationNr][$currentBoxNr]["total"] * $row5["pricePerTeam"]);
                $thisCost = max($thisCost,1.0);
                if (isset($query[$currentBoxInFormation . "uCd" . $cardIndex])&&$row5["code"] === $query[$currentBoxInFormation . "uCd" . $cardIndex]) {
                    $formationHTML .= " checked ";
                    if ($row5["pricePerTeam"] <> 0) {
                        $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                        $formationCost[$formationNr] += $thisCost;
                    } else {
                        $boxCost[$formationNr][$currentBoxNr] += $row5["cost"];
                        $formationCost[$formationNr] += $row5["cost"];
                    }
                }
                $formationHTML .= " 
                type='checkbox' 
                name='{$currentBoxInFormation}uCd{$cardIndex}' 
                id='" . $currentBoxInFormation . "box-CarduCd" . $cardIndex . "' 
                class='{$currentBoxInFormation}uCd{$cardIndex}' 
                value='{$row5["code"]}' onchange='this.form.submit();'>";
                if ($row5["pricePerTeam"] <> 0) {
                    $formationHTML .= $thisCost;
                } else {
                    $formationHTML .= $row5["cost"];
                }
                $formationHTML .= " points: {$row5["card"]}</label><br>";
            }
        }
    }
    return $formationHTML;
}

function generateTitleImanges($insignia, $title, $nation) {
    $HTML = "";

    foreach ($insignia as $row) {
        if ((strpos($title,$row["term"]) !== false)&&(($row["ntn"] == "")||(($nation == $row["ntn"])))) {
            $HTML  = "<span class='nation'><img src='img/{$row['img']}.svg'></span>";
            break;
        }
    }

    if ($insignia instanceof mysqli_result) {
    mysqli_data_seek($insignia ,0);
}
    if (($HTML == "")&&(isset($nation))) {
        $HTML  .= "<span class='nation'><img src='img/{$nation}.svg'></span>";
    }
    return $HTML;
}

function generatePlatoonImageHTML($platoonInBox, $query, $images, $currentPlatoon, $currentBoxInFormation, $formationCardTitle, $insignia) {

    $HTML = "<label for='{$currentBoxInFormation}box{$currentPlatoon}'><span class='platoonImageSpan'>";
    if (isset($platoonInBox["motivSkillHitOn"])) {
        $HTML .= "
        <span class='topright'>
        <div class='MSI'>
            <div>
                {$platoonInBox["motivSkillHitOn"]}
            </div>
        </div>
    </span>";
    }
    //$HTML .= generateTitleImanges($insignia, $formationCardTitle . $platoonInBox["title"], ($platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$query['ntn']);
    foreach ($images as $row3) {
        if ($row3["code"] == $currentPlatoon) {
           
            foreach (explode("|",$row3["image"],7) as $key1 => $boxImage) 
                if (file_exists("img/{$boxImage}.svg")) {
                    $firstSvgFile = simplexml_load_file("img/{$boxImage}.svg");
                    $width = substr($firstSvgFile["width"],0,-2)*3.78;
                    $height = substr($firstSvgFile["height"],0,-2)*3.78;
                    $HTML .= "<img src='img/{$boxImage}.svg' style='width:{$width}px;height:{$height}px;'>";
                } else {
                    $HTML .= "<img src='img/0.svg'>";
                }
        }
    }
    $HTML .= "</span></label><br>\n";
if ($images instanceof mysqli_result) {
    mysqli_data_seek($images ,0);
}
    return $HTML;
}

function generateFormationButtonsHTML($Formations, $bookTitle, $thisNation, $currentFormation, $currentPlatoon, $currentUnit, $insignia) {
    $html = "<button type='button' class='collapsible'><h3>No formation selected:</h3></button>";
    $html .= "<div class='Formation'>
    <div class='grid'>";

    if (isset($Formations)&&query_exists($Formations)) {
        foreach ($Formations as $row) {
            if ($row["Book"] == $bookTitle) {
                //$html .= "\n\t<div class='box'>";
                $html .= "\n\t\t<button type='submit' name='{$currentFormation}' value='{$row["code"]}' class='platoon {$thisNation['ntn']}'>
                <span class='topright'>
                    <div class='MSI'>
                        <div>
                            {$row["motivSkillHitOn"]}
                        </div>
                    </div>
                </span>";
                

                $allImages = explode("|", $row["icon"], 7);
                $firstImage = $allImages[0];
                $lastImage = $allImages[count($allImages)-1];
                $width =0;
                $height  = 0;
                if (file_exists("img/" . $firstImage . ".svg")) {
                    $html .= "<img src='img/" . $firstImage . ".svg'>";
                    $firstSvgFile = simplexml_load_file("img/" . $firstImage . ".svg");
                    $width = substr($firstSvgFile["width"],0,-2);
                    $height = substr($firstSvgFile["height"],0,-2);
                } else {
                    $html .= "<img src='img/0.svg'>";
                }
                if (file_exists("img/" . $lastImage . ".svg")) {
                    $lastSvgFile = simplexml_load_file("img/" . $lastImage . ".svg");
                    $width += substr($lastSvgFile["width"],0,-2);
                }

                if (($width < 60)&&($lastImage!=$firstImage)) {
                    $html .= "<img src='img/" . $lastImage . ".svg'>";
                }

                $html .= "
                <div class='title'>
                <span class='left'>" . generateTitleImanges($insignia, $row["title"], $thisNation['ntn']) . "</span>
                " . ((is_numeric(strpos($row["code"],"CC")))?"<div class='floatingImg'><img src='img/Card.svg'></div>":"") . "

                <span>{$row["title"]}<br>{$row["code"]}</span>

                </div>
                </button>";
                //$html .= "\n\t</div>";
            }
        }

        $html .= "</div>
        </div>";
        if ($Formations instanceof mysqli_result) {
            mysqli_data_seek($Formations ,0);
}
    }
    return $html;
}


function platoonOptionChangedAnalysis($row, $platoonOptionHeaders, $platoonOptionOptions) {
    $platoonOptionChanged = [];
    $platoonOptionHeadersChanged = [];
    if (!empty($row["optionChange"])&&$row["optionChange"] != "Remove") {
        $optionChangeRow = explode("\n",$row["optionChange"]);
        foreach ($optionChangeRow as $replaceOptionValue) {
            $temp = explode("|",$replaceOptionValue);
            $platoonOptionChanged[] = array(
                "code" => $row["platoon"],
                "description" =>     (empty($temp[0])?"":$temp[0]),
                "nrOfOptions" =>    (empty($temp[1])?0:$temp[1]),
                "optionSelection" => (empty($temp[2])?0:$temp[2]),
                "price" =>           (empty($temp[3])?0:$temp[3]),
                "teams" =>           (empty($temp[4])?"":$temp[4]),
                "removeTeam" =>      (empty($temp[5])?"":$temp[5]),
                "image" =>           (empty($temp[6])?"":$temp[6]),
                "addUnit" =>         (empty($temp[7])?"":$temp[7]),
                "replaceText" =>     (empty($temp[8])?"":$temp[8]),
                "ReplacementOrSufix" =>  (empty($temp[9])?"":$temp[9]),
                "addSufixTo" =>          (empty($temp[10])?0:$temp[10]),
                "dynamicPoints" =>       (empty($temp[11])?null:$temp[11])
            );
            $code = $row["platoon"];
            $description = $temp[0];
            // Check if the combination of Nation and period exists in the unique array
            if (!in_array(["code" => $code,"description" => $description], $platoonOptionHeadersChanged)) {
                $platoonOptionHeadersChanged[]  = ["code" => $code,"description" => $description];
            }
        }
    } else {
        $platoonOptionHeadersChanged = $platoonOptionHeaders;
        $platoonOptionChanged = $platoonOptionOptions;
    }
    return [$platoonOptionHeadersChanged, $platoonOptionChanged];
}

// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------- list print functions -------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------

function generateHtmlGrid($dataString) {
    // Split the data string into rows
    $rows = explode("\n", trim($dataString));

    // First row contains headers, the rest are data rows
    $headers = explode("|", array_shift($rows));
    $dataRows = array_map(fn($row) => explode("|", $row), $rows);

    // Calculate the number of columns
    $columnCount = count($headers);

    // Start building the HTML grid
    $html = "<div class='inside-grid-container' style='display: grid; grid-template-columns: repeat({$columnCount}, auto);'>\n";

    // Add header cells
    foreach ($headers as $header) {
        $html .= "<div class='inside-grid-header'>{$header}</div>\n";
    }

    // Add data cells
    foreach ($dataRows as $dataRow) {
        foreach ($dataRow as $data) {
            if (is_numeric(strpos($data,"EOS"))) {
                break;
            }
            $html .= "<div class='inside-grid-cell'>{$data}</div>\n";
        }
    }

    // Close the grid container
    $html .= "</div>";

    return $html;
}

function configChangedGenerate($formationDbRow, $platoonConfig) {
    $platoonConfigChanged = [];
    if (isset($formationDbRow["configChange"])&&$formationDbRow["configChange"]!="") {
        $configChangeRow = explode("\n",$formationDbRow["configChange"],14);

        foreach ($configChangeRow as $key => $value) {
            $temp = explode("|",$value);
            $platoonConfigChanged[] = array(
                "platoon" => $formationDbRow["platoon"], 
                "configuration" => str_replace("//","\n",$temp[0]), 
                "cost" => $temp[1], 
                "sections" => str_replace("!","|",$temp[2]),
                "actualSections" => str_replace("!","|",$temp[3]),
                "shortID" => $temp[4],
                "image" => str_replace("!","|",$temp[5]),
                "teams" => str_replace("!","|",$temp[6]),
                "nrOfTeams" => array_sum(explode("!",$temp[2],15)),
                "attachment" => isset($temp[7])?str_replace("!","|",$temp[7]):"",
                "dynamicPoints" =>(!empty($temp[8])?str_replace("!","|",$temp[8]):null)
            );
        }
    }
    else {
        $platoonConfigChanged = $platoonConfig;
    }
    return $platoonConfigChanged;
}


// Helper function to update replaceUnitStats
function updateReplaceUnitStats(&$platoonCardMod, $source, $separator = "|") {
    $attributes = [
        "movement" =>  ["replaceMovement", 
                        "TACTICAL",
                        "TERRAIN_DASH",
                        "CROSS_COUNTRY_DASH",
                        "ROAD_DASH",
                        "CROSScheck"],
        "save" =>       ["replaceSave",     "ARMOUR_SAVE"],
        "Keyword" =>    ["replaceKeyword",  "removeKeyword",    "addKeyword"],
        "Motivation" => ["replaceMotivation"]
    ];
    $exploded = explode($separator,$source);
    $attribute = $exploded[0];
    if (isset($attributes[$attribute])) {
        foreach ($attributes[$attribute] as $index => $attr) {
            if (isset($attributes[$attribute])) {
                foreach ($attributes[$attribute] as $index => $attr) {
                    $platoonCardMod[$attr] = ($attr == "ARMOUR_SAVE") ? str_replace($separator, "\n", substr($source, strpos($source, $separator) + 1)) : $exploded[$index];
                }
            }
        }
    }
}

function updateNonEmptyPlatoonCardMod(&$platoonCardMod, $platoonIndex, $CardRow, $attachment = false) {
    foreach ($CardRow as $subKey => $subRow) {
        if (!empty($subRow)) {
            if ($attachment) {
                if (empty($platoonCardMod[$platoonIndex]["Warrior"])&&empty($platoonCardMod[$platoonIndex]["attachment"][$subKey])) {
                    $platoonCardMod[$platoonIndex]["attachment"][$subKey] = $subRow;
                }
            } else {
                if (empty($platoonCardMod[$platoonIndex]["Warrior"]) || empty($platoonCardMod[$platoonIndex][$subKey])) {
                    $platoonCardMod[$platoonIndex][$subKey] = $subRow;
                }
            }
        }
    }
}


function calculateCost($formCardRow, $boxRow) {
    $thisCost = $formCardRow["cost"];
    if (!empty($formCardRow["pricePerTeam"]) && $formCardRow["pricePerTeam"] != 0.0) {
        $thisCost *= $boxRow["nrOfTeams"] * $formCardRow["pricePerTeam"];
    }
    $thisCost = round($thisCost > 0.0 ? max($thisCost, 1.0) : ($thisCost == 0.0 ? 0 : min($thisCost, -1.0)));
    return $thisCost;
}

function calculatePlatoonCost($cardRowPlatoon, $boxRow) {
    $thisCost = $cardRowPlatoon["cost"];
    
    if ($cardRowPlatoon["pricePerTeam"] != 0.0) {
        $sections = explode("|", $boxRow["sections"]);
        $nrOfTeams = array_sum($sections);
        $thisCost *= ($nrOfTeams > 0 ? $nrOfTeams : $boxRow["nrOfTeams"]) * $cardRowPlatoon["pricePerTeam"];
                        }
    return round($thisCost > 0.0 ? max($thisCost, 1.0) : ($thisCost == 0.0 ? 0 : min($thisCost, -1.0)));
}

function updatePlatoonAttribute(&$platoonCardMod, $platoonIndex, $cardRowPlatoon, $attribute) {
    if ($cardRowPlatoon[$attribute] !== "") {
        if ($platoonCardMod[$platoonIndex][$attribute] == "") {
            $platoonCardMod[$platoonIndex][$attribute] = $cardRowPlatoon[$attribute];
        }
        if (!empty($platoonCardMod[$platoonIndex]["Warrior"])) {
            $platoonCardMod[$platoonIndex][$attribute] = !empty($cardRowPlatoon[$attribute]) ? $cardRowPlatoon[$attribute] : $platoonCardMod[$platoonIndex][$attribute];
                            } 
                        }
                    }

function appendImage($platoonCardMod, $platoonIndex, $image, $imageNumbers) {
    return (array_key_exists("image", $platoonCardMod[$platoonIndex]) ? 
            $platoonCardMod[$platoonIndex]["image"] : "") . 
            str_repeat("<img src='img/{$image}.svg'>", $imageNumbers);
}

function createAttachment($exploded, $platoonIndex, $cardRowPlatoon) {
    return ["code" => $exploded[2], "platoonIndex" => $platoonIndex, "title" => $cardRowPlatoon["title"]];
}

function printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions,$boxRow, &$configRow, $query, &$weaponsTeamsInForce, &$attachmentsInForce, $platoonIndex, $currentFormation, $unitCardImage, &$platoonCardMod) {
    $optionsHTML = generatePlatoonOptionsPrintHTML($platoonOptionHeaders, $platoonOptionOptions,$boxRow, $configRow, $query, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $platoonsInForce);
    $boxImageHTML = "";//printBoxImageHTML($boxRow, $configRow, $weaponsTeamsInForce, $attachmentsInForce, $platoonIndex, $currentFormation, $unitCardImage, $platoonCardMod);
    return  [$boxImageHTML,$optionsHTML];
}

function generatePlatoonOptionsPrintHTML($platoonOptionHeaders, $platoonOptionOptions,$boxRow, &$configRow, $query, &$weaponsTeamsInForce, &$attachmentsInForce, $platoonIndex, $currentFormation, &$platoonsInForce =[]) {

    $sections = explode("|",$configRow["sections"],15);
    $configImage = explode("|",$configRow["image"],15);
    $configTeams = explode("|",$configRow["teams"],15);
    $actualSections = explode("|",$configRow["actualSections"]??$configRow["sections"],15);
    $actualSectionsMultiplyer = [];
    foreach ($actualSections as $key => $value) {
        $actualSectionsMultiplyer[$key] = substr($value,strpos($value,"x")+1);
    }

    $tempImage = [];
    $tempConfigImage = [];
    $tempTeams = [];
    $tempConfigTeams = [];
    $tempSections = [];
    $tempSectionsEval = [];
    $tempActualSections = [];
    $tempActualSectionsEval = [];

    foreach ($configImage as $key => $boxImage) {
        $tempConfigImage = array_merge($tempConfigImage, array_fill(0, $sections[$key], $boxImage));
        $tempConfigTeams = array_merge($tempConfigTeams, array_fill(0, $sections[$key], $configTeams[$key]??""));
        $tempActualSections = array_merge($tempActualSections, array_fill(0, $sections[$key], $actualSectionsMultiplyer[$key]??""));
    }
    $currentPlatoon = $boxRow["platoon"];
    $currentBoxInFormation = $currentFormation ."-" . ($boxRow["box_nr"]??"");
    $optionsHTML ="";

    $optionImageReplaceNumber = 0;
    foreach ($platoonOptionHeaders as $key5 => $row5) {
        if  ($row5["code"] == $currentPlatoon){

            foreach($platoonOptionOptions as $row4) {

                if  (($row4["code"] == $currentPlatoon)&&($row5["description"] == $row4["description"])) { 

                    if (isset($query[$currentBoxInFormation . "Option" .$key5])&&$row4["optionSelection"] == $query[$currentBoxInFormation . "Option" .$key5] ) {
                        $optionTeamReplaceNumber = $row4["nrOfOptions"];
                        $platoonsInForce[$platoonIndex]["option"][] = $row4["description"];
                        if ($row4["replaceText"] != "") {
                            $optionImageReplaceNumber = $row4["nrOfOptions"];
                        }
                        else if (($row4["ReplacementOrSufix"] == "")&&$row4["image"] != "") {
                            for ($i=0; $i < $row4["optionSelection"]; $i++) { 
                                $tempConfigTeams[] = $row4["teams"];
                                $tempConfigImage[] = $row4["image"];
                            } 
                        } 
                        if ($row4["teams"] != "") {
                            $weaponsTeamsInForce[] = $row4["teams"];
                        }

                        $optionsHTML .= "{$row5["description"]}". (isset($row5["nrOfOptions"])&&$row5["nrOfOptions"]>1?" ({$row4["optionSelection"]} selected)":"") . "<br>\n";
                        if ($row4["addUnit"] != "") {
                            $configRow["attachmentArray"][] = array("code" => $row4["addUnit"], "platoonIndex" => $platoonIndex, "title" => $row4["teams"]);
                        }
                        foreach ($tempConfigTeams as $key1 => $boxTeam){
                            if (($boxTeam == $row4["removeTeam"])&&($optionTeamReplaceNumber>0)) {
                                $tempConfigTeams[$key1]=$row4["teams"];
                                $optionTeamReplaceNumber--;
                            } elseif (is_numeric(strpos($boxTeam . "EOL", $row4["removeTeam"]. "EOL"))&&($optionTeamReplaceNumber>0)) {
                                $tempConfigTeams[$key1] = str_replace($row4["teams"],$row4["removeTeam"], $boxTeam );
                                $optionTeamReplaceNumber--;
                            } 
                        }
                        foreach ($tempConfigImage as $key1 => $boxImage){

                            if (($boxImage == $row4["replaceText"])&&($optionImageReplaceNumber>0)) {
                                $tempConfigImage[$key1]=$row4["ReplacementOrSufix"];
                                $optionImageReplaceNumber--;

                            } elseif (is_numeric(strpos($boxImage . "EOL", $row4["replaceText"]. "EOL"))&&($optionImageReplaceNumber>0)) {
                                $tempConfigImage[$key1] = str_replace($row4["replaceText"],$row4["ReplacementOrSufix"], $boxImage );
                                $optionImageReplaceNumber--;
                            } elseif ( $row4["addSufixTo"] != "") {
                                if (is_numeric(strpos($boxImage, $row4["addSufixTo"]))) {
                                    $tempConfigImage[$key1] .= $row4["ReplacementOrSufix"];
                                }
                            } 
                        }
                    }
                }                                  
            }
            if ($platoonOptionOptions instanceof mysqli_result) {
            mysqli_data_seek($platoonOptionOptions ,0);
        }
            
        }
    }
    
    
    foreach ($tempConfigImage as $key1 => $boxImage){
        $tempSectionsEval[$key1] = $boxImage.$tempConfigTeams[$key1];
        $tempActualSectionsEval[$key1]=((empty($tempActualSections[$key1]))?"":"x".$tempActualSections[$key1]);
    }
    $tempActualSections =[];
    foreach (array_unique($tempSectionsEval) as $key1 => $value1) {
        $tempSections[$key1] = 0;
        foreach ($tempSectionsEval as $key2 => $value2) {
            if ($value2 ==$value1) {
                $tempSections[$key1]++;
                $tempTeams[$key1] = $tempConfigTeams[$key2];
                $tempImage[$key1] = $tempConfigImage[$key2];
                $tempActualSections[$key1] = $tempActualSectionsEval[$key2];
            }
        }
        $tempActualSections[$key1] =$tempSections[$key1]. $tempActualSections[$key1];
    }
    $configRow["sections"] = is_array($tempSections)?implode("|", $tempSections):$tempSections;
    $configRow["teams"] = is_array($tempTeams)?implode("|",$tempTeams):$tempTeams;
    $configRow["image"] = is_array($tempImage)?implode("|",$tempImage):$tempImage;
    $configRow["actualSections"] = is_array($tempActualSections)?implode("|",$tempActualSections):$tempActualSections;
    return $optionsHTML;

}


function printBoxImageHTML($boxRow, &$configRow, &$weaponsTeamsInForce, &$attachmentsInForce, $platoonIndex, $currentFormation, $unitCardImage, &$platoonCardMod) {
    $cardImages = [];
    if (!empty($platoonCardMod[$platoonIndex]["image"])) {
        if (is_numeric(strpos($platoonCardMod[$platoonIndex]["image"], "img src"))) {
            $cardImages = explode("<img src='img/", $platoonCardMod[$platoonIndex]["image"]);
            $temp = array_shift($cardImages);
            foreach ($cardImages as $key => &$value) {
                $value = substr($value,0,strpos($value,"."));
            }
        } else {
            $cardImages = explode("|", $platoonCardMod[$platoonIndex]["image"]);
        }
    }
    $cardImagestwo=[];
    foreach ($cardImages as $key => $value) {
        $cardImagestwo[] = $value;
    }
    $sections = explode("|",$configRow["sections"],15);
    $configImage = explode("|",$configRow["image"],15);
    $configImageOG = $configImage;
    $actualSections = explode("|",$configRow["actualSections"],15);
    $thisImageFiguresNumbers=[];
    $legitAddons = ["-Pf"];
    $illegalAddons = ["-Flamm","-2inch","-Bazooka","-LMG"];
    $baseImageName = "";
    $thisImageAddon=[];

    foreach ($actualSections as $key => $value) {
        $total = explode("x",$value);
        $total = is_numeric($total[1]??"")?intval($total[1])*intval($total[0]):intval($value);
        $sections[$key] = $total;
        $thisImage  = $configImage[$key];
        
        if (is_numeric(strpos($thisImage, "Infantry"))) {
            $thisImageFirst = substr($thisImage,0,strpos($thisImage,"5")?strpos($thisImage,"5"):(strpos($thisImage,"4")?strpos($thisImage,"4"):strpos($thisImage,"3")));
            if ($thisImageFirst =="") {
                $thisImageFirst = explode("-Pf",$thisImage);
                $thisImageFirst = $thisImageFirst[0];
                $thisImageFiguresNumbers[$key][1] = "3";
                $thisImageFiguresNumbers[$key][2] =$thisImageFiguresNumbers[$key][1];
            } else {
                $thisImageFiguresNumbers[$key][1] = strlen($thisImageFirst) == 0?"3":$thisImage[strlen($thisImageFirst)];
                $thisImageFiguresNumbers[$key][2] = strlen($thisImageFirst) == 0?"3":$thisImage[strlen($thisImageFirst)+2]??$thisImageFiguresNumbers[$key][1]-1;
                if (str_replace($illegalAddons,"",$thisImage)!=$thisImage) {
                    foreach ($illegalAddons as $addon) {
                        if (is_numeric(strpos($thisImage, $addon))) {
                            $thisImageFiguresNumbers[$key][2] = $addon;
                            break;
                        }
                    }
                }
            }

            if ($thisImageFiguresNumbers[$key][1]==$thisImageFiguresNumbers[$key][2]) {
                $thisImageFiguresNumbers[$key][1] = $thisImageFiguresNumbers[$key][1] . "a";
                $thisImageFiguresNumbers[$key][2] = $thisImageFiguresNumbers[$key][2] . "b";
            }
            $thisImageAddon[$key] =  substr($thisImage,strrpos($thisImage,"-"));
            if (str_replace($legitAddons,"",$thisImageAddon[$key])==$thisImageAddon[$key]) {
                $thisImageAddon[$key] ="";
            }
            $configImage[$key] = ($thisImageFirst == "")?$configImage[$key]:$thisImageFirst;
            $baseImageName = substr($configImage[$key],0,strpos($configImage[$key],"-",2));
            $baseImageName = $baseImageName==""?$configImage[$key]:$baseImageName;
        }
    }

    $tempConfigImage = [];
    $tempOGConfigImage = [];
    foreach ($configImage as $key => $boxImage) {

        for ($i=0; $i < $sections[$key]; $i++) { 
            if (is_numeric(strpos($boxImage, "Infantry"))) {
                $temp = "".(fmod($i,2)==0?$thisImageFiguresNumbers[$key][1]??"":$thisImageFiguresNumbers[$key][2]??"");
                if (is_numeric(strpos($boxImage, "Kom"))) {
                    $pC = $tempConfigImage[0];
                    unset($tempConfigImage[0]);
                    array_unshift($tempConfigImage,$pC, $boxImage . $temp . ($thisImageAddon[$key]??""));
                } else {
                    $tempConfigImage[] = $boxImage . $temp . ($thisImageAddon[$key]??"");
                }
                

            } else {
            $tempConfigImage[] = $boxImage;
            }
            
            $tempOGConfigImage[] = $configImageOG[$key];
        } 
    }
    $boxImageHTML = "";
    if (isset($configRow["attachmentArray"])&&is_array($configRow["attachmentArray"])) {
        foreach ($configRow["attachmentArray"] as $attachments) {
            $attachmentsInForce[] = $attachments;
        }  
    } elseif (isset($configRow["attachmentArray"])&&$configRow["attachmentArray"]!="") {
        $attachmentsInForce[] = array("code" => $configRow["attachmentArray"]["addUnit"], "platoonIndex" => $platoonIndex, "title" => $configRow["attachmentArray"]["teams"] . ($platoonCardMod[$platoonIndex]["card"]??""));
    }

    foreach ($tempConfigImage as $key1 => $boxImage){
        if (isset($platoonCardMod[$platoonIndex])&&
           (isset($platoonCardMod[$platoonIndex]["ReplaceImg"])&&
           ($platoonCardMod[$platoonIndex]["ReplaceImg"] == $tempOGConfigImage[$key1])&&
           (($platoonCardMod[$platoonIndex]["numbers"]>0)||(is_string($platoonCardMod[$platoonIndex]["numbers"]))&&($platoonCardMod[$platoonIndex]["numbers"][1]>0)))) {
            if (isset($platoonCardMod[$platoonIndex]["perTeam"])) {
                unset($tempConfigImage[$key1]);
                for ($i=0; $i < $platoonCardMod[$platoonIndex]["numbers"][1]; $i++) { 
                    $addonSet = false;
                    foreach ($legitAddons as $addon) {
                        if (is_numeric(strpos($platoonCardMod[$platoonIndex]["replaceImgWith"],$addon))) {
                            $tempConfigImage[] = $baseImageName . $addon;
                            $addonSet = true;
                        }
                    }
                    if (!$addonSet) {
                    $tempConfigImage[] = $platoonCardMod[$platoonIndex]["replaceImgWith"];
                    }
                }
            } else {
                $addonSet = false;
                foreach ($legitAddons as $addon) {
                    if (is_numeric(strpos($platoonCardMod[$platoonIndex]["replaceImgWith"],$addon))) {
                        $tempConfigImage[$key1] = $baseImageName . $addon;
                        $addonSet = true;
                    }
                }
                if (!$addonSet) {
                $tempConfigImage[$key1] = $platoonCardMod[$platoonIndex]["replaceImgWith"];
                }
                $platoonCardMod[$platoonIndex]["numbers"]--;
            }
        }
    }

    $tempConfigImage = array_merge($tempConfigImage,$cardImagestwo);

    $sectionsSum = array_sum($sections);
    $previousImageIsInfantry = false;
    $isHQ = false;
    $lookupInfantryTerms = ["Infantry","MG","Bazooka","mortar","2inch","Piat"];
    $thisIsKomi = False;
    foreach ($tempConfigImage as $key1 => $boxImage){
        $isHQ = is_numeric(strpos($boxImage,"HQ"))||$isHQ;
        $thisImageISInfantry = (str_replace($lookupInfantryTerms,"",$boxImage)!=$boxImage)&&!is_numeric(strpos($boxImage,"Jeep"));
        $afterLastInfantry = $previousImageIsInfantry&&!$thisImageISInfantry;
        $previousImageIsInfantry= $thisImageISInfantry;
        $thisIsKomi = is_numeric(strpos($tempConfigImage[$key1+1]??"","Kom"))||$thisIsKomi;
        $infantryPC = ($key1 == 0)&&($sections[$key1] == 1)&&!is_numeric(strpos($tempConfigImage[$key1+1]??"","Kom"))&&is_numeric(strpos($boxImage,"Infantry"))&&!$isHQ||is_numeric(strpos($boxImage,"Kom"));
        $infantryX = is_numeric(strpos($boxImage,"Infantry"))&&is_numeric(strpos($boxImage,"x"));
        $threeOfFive = ($key1 == 2)&&($sectionsSum>4)&&!$thisImageISInfantry;
        $lastGun = ($sectionsSum == $key1)&&($configRow["unitType"] == "Gun");
        $breakBefore = ($afterLastInfantry&&!$isHQ||$lastGun)?"\n<br>\n":"";
        $break = ($infantryPC||$infantryX||$threeOfFive)?"\n<br>\n":"";
        $scaleClass = $sectionsSum>14?" scale":"";
        $komiClass = $thisIsKomi?" kom":"";
        $infantryClass = $thisImageISInfantry?"class='inf{$scaleClass}{$komiClass}' ":"";
        $boxImageHTML .= "{$breakBefore}{$unitCardImage}<img {$infantryClass}src='img/{$boxImage}.svg'>{$break}";
        }
    
    return  str_replace("<br>\n\n<br>","<br>",$boxImageHTML) ; //.(isset($platoonCardMod[$platoonIndex])?$platoonCardMod[$platoonIndex]["image"]??"":"")
}
function configPrintHTML(&$configRow, $platoonIndex, &$weaponsTeamsInForce, &$attachmentsInForce, &$platoonCardMod) {
    $configHTML = [];
    $configCost = empty($configRow['dynamicPoints'])?$configRow['cost']:$configRow['dynamicPoints'];
    // addWeapon is alwauýs available from the database query

    if (!empty($platoonCardMod[$platoonIndex]["addWeapon"])) {
        $explodedTeam = explode("|",$platoonCardMod[$platoonIndex]["addWeapon"],5);
        if (count($explodedTeam)==2) {
            $replaceTeam = explode("!",$explodedTeam[1],7);          
            foreach (explode("!",$explodedTeam[0],7) as $key => $searchTeam) {
                $configRow["teams"] = str_replace($searchTeam, $replaceTeam[$key], $configRow["teams"]);
            }
        } elseif (count($explodedTeam)==1) {
            $configRow["teams"] .= "|".$explodedTeam[0];
        }
    }
    $explodeBaseTeams = explode("|",$configRow["teams"],10);
    $explodeNumberTeams = explode("|",$configRow["sections"],10);

    if (isset($platoonCardMod[$platoonIndex]["ReplaceTeam"])&&!isset($platoonCardMod[$platoonIndex]["transport"])||isset($platoonCardMod[$platoonIndex]["transport"])&&(!$platoonCardMod[$platoonIndex]["transport"])) {

        foreach ($explodeBaseTeams as $key => $value) {
            if ($platoonCardMod[$platoonIndex]["originalTeam"]==$value) {
                if (isset($platoonCardMod[$platoonIndex]["numbers"])&&
                        ((is_string($platoonCardMod[$platoonIndex]["numbers"])&&$platoonCardMod[$platoonIndex]["numbers"][0] =="P")||
                         (is_int($platoonCardMod[$platoonIndex]["numbers"])&&$platoonCardMod[$platoonIndex]["numbers"]>= $explodeNumberTeams[$key]))) {
                    
                            $configRow["teams"] = str_replace($platoonCardMod[$platoonIndex]["originalTeam"], $platoonCardMod[$platoonIndex]["ReplaceTeam"], $configRow["teams"]);
                } else {
                    $configRow["teams"] .= "|" . $platoonCardMod[$platoonIndex]["ReplaceTeam"];
                    $configRow["sections"] .="|" . $platoonCardMod[$platoonIndex]["numbers"];
                    $platoonCardMod[$platoonIndex]["attachment"] = $platoonCardMod[$platoonIndex]["ReplaceUnit"];
                }
            }
        }
    }
    if (isset($platoonCardMod[$platoonIndex]["team"])) {       
        $configRow["teams"] .= str_repeat("|".$platoonCardMod[$platoonIndex]["team"],$platoonCardMod[$platoonIndex]["numbers"]);

    }
    
    if (isset($platoonCardMod[$platoonIndex]["attachment"])&&isset($platoonCardMod[$platoonIndex]["numbers"])||isset($platoonCardMod[$platoonIndex]["cardPlatoonCode"])) {
        if ((is_string($platoonCardMod[$platoonIndex]["numbers"])&&$platoonCardMod[$platoonIndex]["numbers"][0] =="P")&&(isset($platoonCardMod[$platoonIndex]["image"])&&$platoonCardMod[$platoonIndex]["image"] !=="")) {

            $platoonCardMod[$platoonIndex]["image"] = str_repeat($platoonCardMod[$platoonIndex]["image"],ceil($configRow["nrOfTeams"]/(max(1,intval($platoonCardMod[$platoonIndex]["numbers"][1])))));
        } elseif (isset($platoonCardMod[$platoonIndex]["image"])) {
            //$platoonCardMod[$platoonIndex]["image"] = str_repeat($platoonCardMod[$platoonIndex]["image"],$platoonCardMod[$platoonIndex]["numbers"]);
        }
        if ((is_string($platoonCardMod[$platoonIndex]["numbers"])&&$platoonCardMod[$platoonIndex]["numbers"][0] =="P")&&(isset($platoonCardMod[$platoonIndex]["ReplaceImg"])||isset($platoonCardMod[$platoonIndex]["imageToAddPerTeam"]))) {
            
            if (isset($platoonCardMod[$platoonIndex]["transport"])) {
                $sections = explode("|",$configRow["sections"],15);
                $sectionImages = explode("|",$configRow["image"],15);
                $configRowTeams = explode("|",$configRow["teams"],15);
                $tempConfigRow ="";
                $tempSectionsRow = "";
                $tempImageRow = "";
                
                foreach ($configRowTeams as $key => $boxTeams) {
                    if ($platoonCardMod[$platoonIndex]["originalTeam"]??""==$boxTeams){
                        $configRow["nrOfTeams"] -=$sections[$key];
                    } else {
                        $tempConfigRow .= ($key==0?"":"|") . $boxTeams;
                        $tempSectionsRow .= ($key==0?"":"|") . $sections[$key];
                        $tempImageRow .= ($key==0?"":"|") . $sectionImages[$key];
                    }
                }
            }

            if (isset($platoonCardMod[$platoonIndex]["attachment"])) {
                $platoonCardMod[$platoonIndex]["numbers"] = ceil(($configRow["nrOfTeams"])/(max(1,intval($platoonCardMod[$platoonIndex]["numbers"][1]. ($platoonCardMod[$platoonIndex]["numbers"][2]??"")))));
            } else {
                $platoonCardMod[$platoonIndex]["perTeam"] =true;
            }
            if (isset($platoonCardMod[$platoonIndex]["transport"])&&!empty($platoonCardMod[$platoonIndex]["ReplaceTeam"])||isset($platoonCardMod[$platoonIndex]["imageToAddPerTeam"])) {
                $configRow["teams"] =$tempConfigRow . "|". ($platoonCardMod[$platoonIndex]["ReplaceTeam"]??"");
                $configRow["sections"] = $tempSectionsRow . "|". ($platoonCardMod[$platoonIndex]["numbers"]??"");
                $configRow["image"] = $tempImageRow. "|". ($platoonCardMod[$platoonIndex]["replaceImgWith"]??($platoonCardMod[$platoonIndex]["imageToAddPerTeam"]??""));
            }
        }

        if (isset($platoonCardMod[$platoonIndex]["attachment"])) {
            $attachmentsInForce[] = array("code" => $platoonCardMod[$platoonIndex]["attachment"], "platoonIndex" => $platoonIndex, "title" => $platoonCardMod[$platoonIndex]["card"] . ($platoonCardMod[$platoonIndex]["title"]??"") );
        }
        if (isset($platoonCardMod[$platoonIndex]["cardPlatoonCode"])) {
            $attachmentsInForce[] = array("code" => $platoonCardMod[$platoonIndex]["cardPlatoonCode"], "platoonIndex" => $platoonIndex, "title" => $platoonCardMod[$platoonIndex]["card"]);
        }
        if (isset($platoonCardMod[$platoonIndex]["attachmentTeam"])) {
            $weaponsTeamsInForce[] = $platoonCardMod[$platoonIndex]["attachmentTeam"];
        }
    }
    if ($configRow["teams"] !="") {
        foreach (explode("|",$configRow["teams"],15) as $boxTeams){
            $weaponsTeamsInForce[] =  $boxTeams;
        }
    }
    if (isset($configRow["attachment"])&&$configRow["attachment"] != "") {
        $explodedAttachment = explode("|",$configRow["attachment"],15);
        foreach ($explodedAttachment as $eachAttachment) {
            $attachmentsInForce[] = array("code" => $eachAttachment, "platoonIndex" => $platoonIndex);
        }
    }
    $configHTML = str_replace("\n","<br>", $configRow["configuration"])."<br>\n";
    return [$configCost, $configHTML];
}

function printPlatoonUnitCardHTML($unitCards, $boxRow, $query, &$platoonCardMod, &$attachmentsInForce, &$CardsInList, $currentBoxInFormation, $platoonIndex) {
    $unitCardImage ="";
    $cardsHTML ="";
    if (isset($unitCards)&&query_exists($unitCards)) {
        
        foreach ($unitCards as $unitCardRow) {
            
            if  (isset($boxRow["unitType"])&&($unitCardRow["unit"] == $boxRow["unitType"])&&(isset($unitCardRow["code"]))) {

                for ($cardIndex = 0; $cardIndex <=6; $cardIndex++){ 
                    if (isset($query[$currentBoxInFormation."uCd".$cardIndex])&&$unitCardRow["code"] == $query[$currentBoxInFormation."uCd".$cardIndex]) { 
                        if (!empty($unitCardRow["image"])) {
                            $unitCardImage .= "<img src='img/" . $unitCardRow["image"] . ".svg'>\n";
                            unset($unitCardRow["image"]);
                        }
                        $cardsHTML .= "<img src='img/cardSmall.svg'>{$unitCardRow["card"]} ({$unitCardRow["cost"]}p)<br>";
                        updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $unitCardRow);


                        if (isset($unitCardRow["addWeapon"])&&$unitCardRow["addWeapon"] !== "") {
                            
                            $exploded = explode("|",$unitCardRow["addWeapon"]);

                            if ($exploded[0]=="Team") {
                                
                                $platoonCardMod[$platoonIndex]["numbers"] = $exploded[1];
                                $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                if (array_key_exists("image",$platoonCardMod[$platoonIndex])) {
                                    $platoonCardMod[$platoonIndex]["image"] .= "<img src='img/{$exploded[3]}.svg'>";
                                } else {
                                    $platoonCardMod[$platoonIndex]["image"] = "<img src='img/{$exploded[3]}.svg'>";
                                }
                                $attachmentsInForce[] = array("code" => $exploded[2], "platoonIndex" => $platoonIndex, "title" => $unitCardRow["title"]);
                                if (isset($exploded[4])) {
                                    $platoonCardMod[$platoonIndex]["attachmentTeam"] = $exploded[4];
                                }
                            }

                        }
                        if ($unitCardRow["addUnit"]<>"") {
                            $attachmentsInForce[] = array("code" => $unitCardRow["addUnit"], "platoonIndex" => $platoonIndex, "title" => $unitCardRow["card"] . ((isset( $platoonCardMod[$platoonIndex]["card"])) ? $platoonCardMod[$platoonIndex]["card"]: ""));
                        }
                        $unitCardRow["thisCost"] = $unitCardRow["cost"];
                        $CardsInList[] = $unitCardRow;

                    }
                }
            }
        }
    }
    if_mysqli_reset($unitCards);
    return [$unitCardImage, $cardsHTML];
}

function printPlatoonCardHTML($platoonCards, $boxRow, $query, $currentBoxInFormation, $platoonIndex, &$platoonCardChange, &$platoonCardMod, &$CardsInList, &$platoonsInForce, $formationCardTitle, &$attachmentsInForce) {

    if (isset($platoonCards)&&query_exists($platoonCards)) {
        foreach ($platoonCards as $cardRowPlatoon) {
            if  (($cardRowPlatoon["platoon"] == $boxRow["platoon"])&&(isset($cardRowPlatoon["code"]))&&($cardRowPlatoon["platoonTypes"]==$boxRow["box_type"]||$cardRowPlatoon["platoonTypes"]==""||$cardRowPlatoon["platoonTypes"]=="Platoon")) {

                for ($cardIndex = 0; $cardIndex <=7; $cardIndex++){   
                    $queryKey = $currentBoxInFormation . "Card" . $cardIndex;
                    if (isset($query[$queryKey])&&$cardRowPlatoon["code"] == substr($query[$queryKey],0,6) ) {
                        updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $cardRowPlatoon);

                        if ($cardRowPlatoon["prerequisite"]=="Warrior") {
                            $platoonCardMod[$platoonIndex]["Warrior"] = "Warrior";
                        }
                        if ($cardRowPlatoon["prerequisite"]!=="Warrior"){
                            updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $cardRowPlatoon, true);
                            $platoonCardMod[$platoonIndex]["attachment"]["replaceUnitStats"] = "";
                        }
                        
                        updatePlatoonAttribute($platoonCardMod, $platoonIndex, $cardRowPlatoon, "motivation");
                        updatePlatoonAttribute($platoonCardMod, $platoonIndex, $cardRowPlatoon, "skill");
                        updatePlatoonAttribute($platoonCardMod, $platoonIndex, $cardRowPlatoon, "isHitOn");
                        if (!empty($platoonCardMod[$platoonIndex]["attachment"])&&is_array($platoonCardMod[$platoonIndex]["attachment"])&&$cardRowPlatoon["prerequisite"]!=="Warrior") {
                            $platoonCardMod[$platoonIndex]["attachment"]["skill"] = !empty($cardRowPlatoon["skill"])?$cardRowPlatoon["skill"]:null;
                            $platoonCardMod[$platoonIndex]["attachment"]["motivation"]=!empty($cardRowPlatoon["motivation"])?$cardRowPlatoon["motivation"]:null;
                            $platoonCardMod[$platoonIndex]["attachment"]["isHitOn"]=!empty($cardRowPlatoon["isHitOn"])?$cardRowPlatoon["isHitOn"]:null;
                        }
                        if ($cardRowPlatoon["replaceUnitStats"] !== "") {
                            $exploded = explode("|",$cardRowPlatoon["replaceUnitStats"]);    // Handle multiple replacements
                            if ($exploded[0] == "multiple") {
                                foreach ($exploded as $eachInExploded) {
                                    updateReplaceUnitStats($platoonCardMod[$platoonIndex], $eachInExploded, "!");
                                    }
                            } else {
                                // Handle single replacement
                                updateReplaceUnitStats($platoonCardMod[$platoonIndex], $cardRowPlatoon["replaceUnitStats"]);
                            }
                        }
                        if (!empty($cardRowPlatoon["addWeapon"])) {
                            $exploded = explode("|",$cardRowPlatoon["addWeapon"]);
                            $type = $exploded[0];
                                if (strlen($query[$currentBoxInFormation . "Card" . $cardIndex])>6) {
                                $numbers = (int)substr($query[$currentBoxInFormation . "Card" . $cardIndex],6,1);
                                } else {
                                $numbers = $exploded[1];
                                }
                            $imageNumbers = (is_string($numbers)&&$numbers[0]=="P") ? (int)substr($numbers, 1, 2) : (int)$numbers;

                            switch ($type) {
                                case "Team":
                                    $platoonCardMod[$platoonIndex]["numbers"] = $numbers;
                                $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                    if (is_string($numbers)&&$numbers[0]!="P") {
                                    $platoonCardMod[$platoonIndex]["image"] = appendImage($platoonCardMod, $platoonIndex, $exploded[3], $imageNumbers);
                                    } else {
                                        $platoonCardMod[$platoonIndex]["imageToAddPerTeam"] = $exploded[3];
                                        $platoonCardMod[$platoonIndex]["transport"] = true;
                                    }

                                if (isset($exploded[4])) {
                                    $platoonCardMod[$platoonIndex]["attachmentTeam"] = $exploded[4];
                                }
                                    $attachmentsInForce[] = createAttachment($exploded, $platoonIndex, $cardRowPlatoon);
                                    break;
                                case "ReplaceTransport":
                                    $platoonCardMod[$platoonIndex] = array_merge($platoonCardMod[$platoonIndex],[
                                        "numbers" => $exploded[1],
                                        "attachment" => $exploded[2],
                                        "originalTeam" => $exploded[3],
                                        "ReplaceTeam" => $exploded[4],
                                        "ReplaceImg" => $exploded[5],
                                        "replaceImgWith" => $exploded[6],
                                        "transport" => true,
                                        "ReplaceUnit" => ""
                                    ]);
                                    $attachmentsInForce[] = createAttachment($exploded, $platoonIndex, $cardRowPlatoon);
                                    break;
                                case "WeaponsTeam":
                                    $platoonCardMod[$platoonIndex]["numbers"] = $numbers;
                                $platoonCardMod[$platoonIndex]["team"] = $exploded[2];
                                    $platoonCardMod[$platoonIndex]["image"] = appendImage($platoonCardMod, $platoonIndex, $exploded[3], $imageNumbers);
                                    break;
                                case "Replace":
                                    $platoonCardMod[$platoonIndex]["numbers"] = $numbers;
                                    if ($exploded[3] != "N/A") {
                                        $platoonCardMod[$platoonIndex] = array_merge($platoonCardMod[$platoonIndex], [
                                            "cardPlatoonCode" => $exploded[2],
                                            "originalTeam" => $exploded[3],
                                            "ReplaceTeam" => $exploded[4],
                                            "ReplaceUnit" => $exploded[2],
                                            "replaceImgWith" => $exploded[6] ?? null,
                                            "ReplaceImg" => $exploded[5] ?? null
                                        ]);
                                } else {
                                        $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                        $attachmentsInForce[] = createAttachment($exploded, $platoonIndex, $cardRowPlatoon);
                                        
                                    }
                                    break;
                            }
                        }

                        if ($cardRowPlatoon["replacedText"] == "After") {
                            $platoonsInForce[$platoonIndex]["title"] = $platoonsInForce[$platoonIndex]["title"] . $cardRowPlatoon["replaceWith"];
                        } elseif (($cardRowPlatoon["replacedText"] == "") && ($cardRowPlatoon["replaceWith"] != "")) {
                            $platoonsInForce[$platoonIndex]["title"] = trim($cardRowPlatoon["replaceWith"] . " " . ($platoonsInForce[$platoonIndex]["title"]??""));
                            //$platoonCardMod[$platoonIndex]["title"] = $platoonCardMod[$platoonIndex]["title"]??"" . $cardRowPlatoon["replaceWith"];
                        } elseif ($cardRowPlatoon["replacedText"] != "") {
                            $platoonsInForce[$platoonIndex]["title"] = str_replace($cardRowPlatoon["replacedText"] , $cardRowPlatoon["replaceWith"], $platoonsInForce[$platoonIndex]["title"]);
                        }
                        if (($cardRowPlatoon["title"] != "")) {
                            $platoonCardMod[$platoonIndex]["title"] .= trim(($platoonCardMod[$platoonIndex]["title"]==""?"":" ").(($platoonCardMod[$platoonIndex]["title"]==$cardRowPlatoon["title"])||(is_numeric(strpos($platoonCardMod[$platoonIndex]["title"],$cardRowPlatoon["title"])))?"":$cardRowPlatoon["title"]));
                        }
                        if (($formationCardTitle == "")&&($cardRowPlatoon["title"] != "")&&$platoonCardMod[$platoonIndex]["title"]=="") {
                            $platoonCardMod[$platoonIndex]["title"] = $cardRowPlatoon["title"];
                        }
                        if ((isset($boxRow["addCard"])&& $boxRow["addCard"]!="")&&($boxRow["addCard"] == $cardRowPlatoon["code"])) {
                            $platoonCardMod[$platoonIndex]=$cardRowPlatoon;
                            $CardsInList[] = $cardRowPlatoon;
                        }
                        if (!isset($platoonCardMod[$platoonIndex])){
                            $platoonCardMod[$platoonIndex] = $cardRowPlatoon;
                        }
                        if (!isset($platoonCardChange[$platoonIndex]["html"])) {
                            $platoonCardChange[$platoonIndex]["html"] ="";
                        }
                        if (strlen($query[$currentBoxInFormation . "Card" . $cardIndex])>6) {
                            $thisCost = $cardRowPlatoon["cost"] * substr($query[$currentBoxInFormation . "Card" . $cardIndex],6,1);
                        } else {
                            $thisCost = calculatePlatoonCost($cardRowPlatoon, $boxRow);
                            }
                        $cardNameTargetSplit = explode(":", $cardRowPlatoon["card"]);
                        $platoonCardChange[$platoonIndex]["html"] .= ((!is_numeric(strpos($platoonCardChange[$platoonIndex]["html"],$cardNameTargetSplit[0])))?"<img src='img/cardSmall.svg'>{$cardNameTargetSplit[0]} ({$thisCost}p)<br>":"");
                        $cardRowPlatoon["thisCost"] = $thisCost;
                        $CardsInList[] = $cardRowPlatoon;
                    }
                }
            }
        }
    }
    if_mysqli_reset($platoonCards);
}

function printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, &$platoonCardMod, $boxRow, $configRow, $query, &$CardsInList, &$platoonsInForce, &$attachmentsInForce) {
    $formationCard = [];
    $formationCardHTML ="";
    $queryKey = "{$formationNr}-{$currentBoxNr}-Card";
    if (isset($formationCards)&&query_exists($formationCards)&&
     (isset($boxRow["addCard"])||!empty($formationCardTitle[$formationNr]))||
     (!empty($query[$queryKey]))) {
        foreach($formationCards as $formCardRow) {
            
            $evalForSupport = ($formCardRow["code"]!=="")&&isset($query[$queryKey])&&($query[$queryKey]==$formCardRow["code"])&&(is_numeric(strpos($boxRow["formation"],$formCardRow["formation"])));
            $evalForFormation = isset($formCardRow["title"])&&(($formationCardTitle[$formationNr]??"nn") == (isset($formCardRow["title"])?trim($formCardRow["title"]):"na"));
//command card platoon
            $evalForCcdPlatoon = ($formCardRow["code"]!=="")&&(isset($boxRow["addCard"])&&$boxRow["addCard"] == $formCardRow["code"]);
            $thisCost = calculateCost($formCardRow, $boxRow);

            if (isset($boxRow["addCard"])&&($boxRow["addCard"] == $formCardRow["code"])) {
                $formationCard[] = "<img src='img/cardSmall.svg'>{$formCardRow["card"]} ({$thisCost}p)<br>";
                updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $formCardRow);
                $CardsInList[] = $formCardRow;
                $platoonsInForce[$platoonIndex]["title"] = empty($platoonsInForce[$platoonIndex]["title"])?$formCardRow["card"]:$platoonsInForce[$platoonIndex]["title"];
            }
            if (($evalForFormation)&&($formCardRow["platoonTypes"]=="Attachment")) {
                $platoonCardMod[$platoonIndex]["attachment"]=$formCardRow;
                foreach ($attachmentsInForce as $value) {
                    if ($value["platoonIndex"] == $platoonIndex) {
                        $cardNameTargetSplit = explode(":", $formCardRow["card"]);
                        $formationCard[] = "<img src='img/cardSmall.svg'>{$cardNameTargetSplit[0]} ({$thisCost}p)".(isset($cardNameTargetSplit[1])? ": {$cardNameTargetSplit[1]}":"")."<br>";
                        $CardsInList[] = $formCardRow;
                    }
                }
            }
            if ($evalForFormation&&$formCardRow["platoonTypes"]=="Formation"&&$platoonIndex==2) {
                $formCardRow["thisCost"] = $formCardRow["cost"]; 
            }
            if  (($evalForCcdPlatoon||$evalForSupport||$evalForFormation)&&($formCardRow["platoonTypes"]==$boxRow["box_type"])) {
                $cardNameTargetSplit = explode(":", $formCardRow["card"]);
                $formationCard[] = "<img src='img/cardSmall.svg'>{$cardNameTargetSplit[0]} ({$thisCost}p)".(isset($cardNameTargetSplit[1])? ": {$cardNameTargetSplit[1]}":": {$formCardRow["platoonTypes"]}")."<br>";
                //$platoonCardMod[$platoonIndex]["title"] .= ((is_numeric(strpos($platoonCardMod[$platoonIndex]["title"] . $platoonsInForce[$platoonIndex]["title"],$formCardRow["title"])))?"":$formCardRow["title"]);
                updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $formCardRow);
                $CardsInList[] = $formCardRow;
                }

            elseif (($evalForSupport||$evalForCcdPlatoon||$evalForFormation)&&($formCardRow["platoonTypes"]=="Formation"||($formCardRow["platoonTypes"]=="All"))) {
                $cardNameTargetSplit = explode(":", $formCardRow["card"]);
                if (!($formCardRow["platoonTypes"]=="Formation")||$platoonIndex==0) {
                    $formationCard[] = "<img src='img/cardSmall.svg'>{$cardNameTargetSplit[0]} ({$thisCost}p) for {$formCardRow["platoonTypes"]}".(isset($cardNameTargetSplit[1])? " for {$cardNameTargetSplit[1]}":"")."<br>";
                }

                $platoonCardMod[$platoonIndex]["motivationReplaceAll"] = !empty($platoonCardMod[$platoonIndex]["motivation"]) ? $platoonCardMod[$platoonIndex]["motivation"] : null;
                $platoonCardMod[$platoonIndex]["skillReplaceAll"] = !empty($platoonCardMod[$platoonIndex]["skill"])? $platoonCardMod[$platoonIndex]["skill"] : null;
    
                updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $formCardRow);
                $CardsInList[] = $formCardRow;
            }
        }
                }
if ($formationCards instanceof mysqli_result) {
    mysqli_data_seek($formationCards ,0);
}

    $formationCard = array_unique($formationCard);
    foreach ($formationCard as $key => $value) {
        $formationCardHTML .= $value ;
    }
    return $formationCardHTML;
}


// Helper function to apply replacements to an attribute based on card modifications
function applyAttributeReplacement($attribute, $cardMod, &$attributeValue) {
    $cardSmall = false;
    $properAttribute = ucwords($attribute);
    // Replace "All" attribute if present and no warrior modification
    if (!empty($cardMod["{$attribute}ReplaceAll"]) && empty($cardMod["Warrior"])) {
        $cardSmall = true;
        $attributeValue = str_replace("!", "|", $cardMod["{$attribute}ReplaceAll"]);
    }
    // Specific replacement if available and no warrior modification
    elseif (!empty($cardMod["replace{$properAttribute}"]) && empty($cardMod["Warrior"])) {
        $oldParts = explode("/", $cardMod["replace{$properAttribute}"], 7);
        $newParts = explode("/", $cardMod[$attribute], 7);

        foreach ($oldParts as $key => $oldPart) {
            $attributeValue = str_replace("!", "|", str_replace($oldPart, $newParts[$key] ?? '', $attributeValue));
        }
        $cardSmall = true;
    }
    // Direct replacement if specific attribute is present
    elseif (!empty($cardMod[$attribute])) {
        $cardSmall = true;
        $attributeValue = str_replace("!", "|", $cardMod[$attribute]);
    }

    return $cardSmall;
}
function processPlatoonStats($row, $platoonIndex, $platoonSoftStats, $platoonCardMod) {
    $row['platoonIndex'] = $platoonIndex;
    $motivationValue = $skillValue = $ishitonValue = '';

    // Find matching platoon stats and apply replacements
    foreach ($platoonSoftStats as $platoonSoftStatRow) {
        if ($row['platoon'] === $platoonSoftStatRow["code"] && $platoonSoftStatRow["TACTICAL"] != "") {
            $motivationValue = $platoonSoftStatRow["MOTIVATION"]??"";
            $skillValue = $platoonSoftStatRow["SKILL"]??"";
            $ishitonValue = $platoonSoftStatRow["IS_HIT_ON"]??"";
            $cardMod = $platoonCardMod[$platoonIndex] ?? null;

            // Apply replacements for each attribute
            applyAttributeReplacement("motivation", $cardMod, $motivationValue);
            applyAttributeReplacement("skill", $cardMod, $skillValue);
            applyAttributeReplacement("isHitOn", $cardMod, $ishitonValue);
            break;
        }
    }

    // Generate MSI display content
    $msi = "
    <div class='MSI'>
        <div>
            {$motivationValue[0]}-{$skillValue[0]}-{$ishitonValue[0]}
        </div>
    </div>";

    return $msi;
                    } 
function processPlatoonAttribute($attribute, $platoonRow, $platoonSoftStatRow, $platoonCardMod) {
    $attributeValue = $platoonSoftStatRow[strtoupper($attribute)]; 
    $cardSmall = false;
    $platoonIndex = $platoonRow['platoonIndex'];
    $cardMod = $platoonCardMod[$platoonIndex]??null;
    if (!isset($platoonRow["attachment"])) {

        $cardSmall = applyAttributeReplacement($attribute, $cardMod, $attributeValue);

    }
    // Attachment-specific replacements
    elseif (isset($platoonRow["attachment"])) {

        $cardSmall = applyAttributeReplacement($attribute, $cardMod["attachment"]??null, $attributeValue);

    }

    // Wrap with small card icon if cardSmall is true
    if ($cardSmall) {
        $attributeValue = "<div class='floatingImg'><img src='img/cardSmall.svg'></div>" . $attributeValue;
    }

    return $attributeValue;
}

function printPointsAndIsHitOnHTML($platoonSoftStats, $boxRow, $platoonCardMod, $platoonIndex) {

// -------- ishiton ----------
    foreach ($platoonSoftStats as $row2) {
        if ( $row2["code"] === $boxRow["platoon"]) {
            if ($platoonCardMod[$platoonIndex]['isHitOn']<>"") {
                $boxAllHTML .=  " <img src='img/cardSmall.svg'>"; 
                $platoonIsHitOn = $platoonCardMod[$platoonIndex]['isHitOn'];
            } else {
                $platoonIsHitOn = $row2["IS_HIT_ON"];
            }
            $boxAllHTML .= "
            <div class='IsHitOn'>
              <div>
                " . $platoonIsHitOn . "
              </div>
            </div>";
        }
    }
    if_mysqli_reset($platoonSoftStats); 
    return $boxAllHTML;
}