<?php

function dropdown($source,$valueindex,$collumnvalue,$collumnText,$globalVar,$condition1enable,$condition1_1,$condition1_2,$condition2enable,$condition2_1,$condition2_2,$query){
    $output ="";
    if (!($source instanceof mysqli_result)&&count($source) > 0||($source instanceof mysqli_result)&&$source -> num_rows > 0) {
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
        if ($source instanceof mysqli_result) {
            mysqli_data_seek($source ,0);
        }
        $output .=  "
        </select>";
    } 
    return $output;
}

function generateDroppdownHTML($selectName, $selectID, $optionsArray, $onChange = true) {
    $output =""; 


    if (count($optionsArray) > 0) {

        $output .= "<select name='" . $selectName . "' id='" . $selectID . (($onChange)?"' onchange='if(this.value != 0) { this.form.submit(); }":"'")."'>
            <option value='' selected disabled hidden>Choose here</option>\n";
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
                echo "<div class='title'><button type='submit' name='" . $row["code"] . "' value='" . $row["code"] . "'>" . $row["title"] . "<br>" . $row["code"] . "</button></div></div></div>";
            }
        }
        echo "</div>";
        if ($Formations instanceof mysqli_result) {
            mysqli_data_seek($Formations ,0);
        }
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
        if ($Books instanceof mysqli_result) {
            mysqli_data_seek($Books ,0);
        }
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
        if ($Nations instanceof mysqli_result) {
            mysqli_data_seek($Nations ,0);
        }
    }
}

function displayPeriods($Periods) {
    echo "<div class='Formation'><h3>No period selected</h3><br>";
    foreach ($Periods as $row) {
        echo "<div class='box'><div class='platoon'><div class='title'><button type='submit' name='period' value='" . $row["period"] . "'>" . $row["period"] . "</button></div></div></div>";
    }
    if ($Periods instanceof mysqli_result) {
        mysqli_data_seek($Periods ,0);
    }
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
    if ($formationCards instanceof mysqli_result) {
        $formationCardsNR = $formationCards -> num_rows;
        mysqli_data_seek($formationCards ,0);
    } else {
        $formationCardsNR = count($formationCards);
    }
    if ($unitCards->num_rows > 0) {
        foreach ($unitCards as $key4 =>  $row4) {
            if ($row4["unit"] == $currentUnit) {
                $unitCard[$key4] = $row4;
            }
        }
    }
    if ($unitCards instanceof mysqli_result) {
        $unitCardsNR = $unitCards -> num_rows;
        mysqli_data_seek($unitCards ,0);
    }else {
        $unitCardsNR = count($unitCards);
    }
    if ($platoonCards->num_rows > 0) {
        foreach ($platoonCards as $key4 => $row4) {
            if ($row4["platoon"] == $currentPlatoon) {
                $platoonCard[$key4] = $row4;
            }
        }    
    }
    if ($platoonCards instanceof mysqli_result) {
        $platoonCardsNR = $platoonCards -> num_rows;
        mysqli_data_seek($platoonCards ,0);
    }else {
        $platoonCardsNR = count($platoonCards);
    }

// ----------- if it has cards, print the heading 
    return  ((($unitCardsNR>0)||($platoonCardsNR>0)||($formationCardsNR>0))? "Cards:<br>" : "");
}

function processFormationCards($formationNr, $formationCards, &$query, $currentFormation, &$formationCost) {
    $formationHaveCards = FALSE;
    $cardText = "";
    $HTML = "";
    $usedCards = "";
    $cmdCardTitleOfEntireFormation ="";
    if ($formationCards->num_rows > 0) {
        foreach ($formationCards as $key5 => $row5) {
            if ($row5['formation'] == $query[$currentFormation] && !$formationHaveCards) {
                $HTML .= "<div> <img src='img/cardSmall.svg'> Select a card for this formation:
                <select name='{$formationNr}-Card' class='{$formationNr}Card' onchange=' this.form.submit(); '>
                    <option value=''>No card selected</option>";
                $formationHaveCards = true;
            }
            $selected = "";
            $cardValue = $row5["code"];
            if (isset($query[$formationNr . "-Card"])&&($row5["code"] === $query[$formationNr . "-Card"] && $query[$formationNr . "-Card"] != "")) {
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
                $HTML .= "<option value='{$cardValue}' $selected>{$row5['cost']} points per platoon: {$row5['card']}</option>";
            } elseif ($row5["platoonTypes"] == "Formation") {
                $cardText = ($selected !== "") ? str_replace("/n", "<br>", $row5["notes"]) : $cardText;
                $formationCost += ($selected !== "") ? $row5["cost"] : 0;
                $HTML .= "<option value='{$cardValue}' $selected>{$row5['cost']} points for formation: {$row5['card']}</option>";
            }
        }
        if ($formationCards instanceof mysqli_result) {
            mysqli_data_seek($formationCards ,0);
        }

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
    if (!isset($formationCards)||(!($formationCards instanceof mysqli_result)&&!(count($formationCards) > 0)||($formationCards instanceof mysqli_result)&&!($formationCards -> num_rows > 0))) {
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
            $thisCost = round($row5["cost"] * $boxSections[$formationNr][$currentBoxNr] * $row5["pricePerTeam"]);
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

function processPlatoonConfig($currentPlatoon, $platoonConfig, $currentBoxInFormation, $formationNr, $currentBoxNr, &$query, &$boxCost, &$formationCost, &$boxSections) {
    $CR = "\n";
    $boxConfigHTML = "";
    $boxSections[$formationNr][$currentBoxNr] = 0;

    if ((($platoonConfig instanceof mysqli_result)&&($platoonConfig->num_rows > 0)||(isset($platoonConfig))) && isset($query[$currentBoxInFormation])&&($currentPlatoon == $query[$currentBoxInFormation]) && (isset($query[$currentBoxInFormation]))) {
        $boxConfigHTML .= "<select id='{$currentBoxInFormation}' name='{$currentBoxInFormation}c' class='select-element' onchange='{ this.form.submit(); }'>{$CR}";
        $configSelected = FALSE;
        if (!isset($query[$currentBoxInFormation . "c"])||(!is_numeric(strpos($query[$currentBoxInFormation . "c"],$query[$currentBoxInFormation])))) {
            $query[$currentBoxInFormation . "c"] =null;
        }
        foreach ($platoonConfig as $row4) {
            if ($row4["platoon"] == $currentPlatoon) {
                $boxConfigHTML .= $CR . "<option ";
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


                    // Modify the boxSections variable by reference
                    foreach (explode("|", $row4["sections"], 7) as $key1 => $boxSection) {
                        $boxSections[$formationNr][$currentBoxNr] += $boxSection;
                    }
                }
                
                $boxConfigHTML .= "value='" . str_replace("\n", " ", $row4["shortID"]) . "'>" . str_replace("\n", "<br>", $row4["configuration"]) . ": ".((isset($query["dPs"])&&($query["dPs"]=="true")&&($row4["dynamicPoints"]!=""))? $row4["dynamicPoints"] : $row4["cost"]) . " points</option>{$CR}";
            }
            
        }
        if (is_a($platoonConfig, 'mysqli_result')) {
            mysqli_data_seek($platoonConfig, 0);
        }
        $boxConfigHTML .= "</select><br>{$CR}";
    }

    return $boxConfigHTML;
}

function processFormationCardHTML($formationCards, $query, $currentFormation, $formationNr, $currentBoxNr, $thisBoxType, &$boxCost, &$formationCost, $boxSections, $cmdCardTitleOfEntireFormation, $thisBoxSelectedPlatoon, &$formationCardTitle, $currentPlatoon) {
    $CR = "\n"; // Define the carriage return character
    $formationCardHTML = "";

    if (($formationCards->num_rows > 0) && isset($query[$formationNr . "-Card"])&&($query[$formationNr . "-Card"] <> "") && ($thisBoxSelectedPlatoon == $currentPlatoon)) {
        $formationCardTitle[$currentFormation ."-" . $currentBoxNr] = $cmdCardTitleOfEntireFormation[$formationNr];

        foreach ($formationCards as $row4) {
            $thisCost = round($row4["cost"] * $boxSections[$formationNr][$currentBoxNr] * $row4["pricePerTeam"]);
            $thisCost = max($thisCost,1.0);
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
                if (($row4["pricePerTeam"] <> 0) && ($row4["platoonTypes"] <> "Formation")) {
                    $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                    $formationCost[$formationNr] += $thisCost;
                } else if (($row5["platoonTypes"] = "Formation") && ($formationCardToggle == TRUE)) {
                    $formationCost[$formationNr] += $row4["cost"];
                    $formationCardToggle = FALSE;
                } else if ($row5["platoonTypes"] <> "Formation") {
                    $formationCost[$formationNr] += $row4["cost"];
                    $boxCost[$formationNr][$currentBoxNr] += $row4["cost"];
                }
            }
            if ((($query[$formationNr . "-Card"] == str_replace("'", "", $row4["card"])) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == $thisBoxType) && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . " at: " . (($row4["pricePerTeam"] <> 0) ? $thisCost : $row4["cost"]) . " point" . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            } elseif ((($query[$formationNr . "-Card"] == str_replace("'", "", $row4["card"])) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == "Formation") && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . "<br>";
            } elseif ((($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"]) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == $thisBoxType) && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . " at: " . (($row4["pricePerTeam"] <> 0) ? $thisCost : $row4["cost"]) . " point" . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            } elseif ((($cmdCardTitleOfEntireFormation[$formationNr] == $row4["title"]) || ($row4["code"] === $query[$formationNr . "-Card"])) && ($row4["platoonTypes"] == "All") && ($row4["code"] != "")) {
                $formationCardHTML .= "<img src='img/cardSmall.svg'>" . $row4["card"] . " at: " . (($row4["pricePerTeam"] <> 0) ? $thisCost : $row4["cost"]) . " point" . (($row4["cost"] > 1) ? "s" : "") . "<br>";
            }
        }
        if ($formationCards instanceof mysqli_result) {
            mysqli_data_seek($formationCards ,0);
        }
    }
    return $formationCardHTML;
}

function generatePlatoonOptionsHTML($currentBoxInFormation, $currentPlatoon, $query, $platoonOptionHeaders, $platoonOptionOptions, $formationNr, $currentBoxNr, &$boxCost, &$formationCost) {
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
                    if ($platoonOptionOptions instanceof mysqli_result) {
                        mysqli_data_seek($platoonOptionOptions ,0);
                    }
                    if (count($OptionsCount) > 1) {
                        $formationHTML .= "{$CR}<br>{$row5["description"]}<br>
                        <select 
                        name='{$currentBoxInFormation}Option{$key5}' 
                        id='{$currentBoxInFormation}box-Option{$key5}' 
                        class='{$currentBoxInFormation}Option' 
                        onchange='this.form.submit(); '>
                        <option value=''>No option selected</option>";
                        foreach ($OptionsCount as $row4) {
                            $formationHTML .= "{$CR}<option ";
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
                            $formationHTML .= "value='" . str_replace("\n", " ", $row4["optionSelection"]) . "'>{$row4["optionSelection"]} ({$row4["price"]} points)</option>";
                        }
                        $formationHTML .= "{$CR}</select>{$CR}<br>";
                    } else {
                        foreach ($OptionsCount as $row4) {
                            $formationHTML .= "{$CR}<label><input ";
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



function motivationBox($platoonMotivation) {
    $motivationArray =[];
    $noCols =0;
    $textSeparatedArray=[];
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

    foreach ($motivationArray as $rowKey => $motivationRow) {
        foreach ($motivationRow as $columnKey => $motivationColumn) {
            if (($rowKey == 0)) {
                $textSeparatedArray[$rowKey][$columnKey]['class']="grid-heading";
            } elseif ($rowKey == $rows-1) {
                $textSeparatedArray[$rowKey][$columnKey]['class']= "grid-bottom";
            } else {
                $textSeparatedArray[$rowKey][$columnKey]['class']= "grid-item";
            }
            if (($rowKey > 0) && ($columnKey > 0)) {
                $textSeparatedArray[$rowKey][$columnKey]['class'] = "grid-addon";
                $textSeparatedArray[$rowKey][$columnKey]['text'] = $motivationColumn;
            } 
            if ((strlen($motivationColumn) - strrpos($motivationColumn, " ")<4)&&(!is_numeric(strrpos(substr($motivationColumn,-4),"MG")))){
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
    echo "<div class='grids'" . (($noCols > 1)?" style=' grid-template-columns: 130px auto; '":"") . ">";
    foreach ($textSeparatedArray as $rowKey => $textSeparatedRow) {
        echo "<div class='{$textSeparatedRow[0]["class"]}'>" . (($rowKey == 0)?"<div>":"") . "<span>{$textSeparatedRow[0]["text"]}</span></div>" . (($rowKey == 0)?"</div>":"");
        if ($noCols>1) {
            if (isset($textSeparatedRow[1])&&$textSeparatedRow[1]["text"] != "") {
                echo "<div class='{$textSeparatedRow[1]["class"]}'>" . (($rowKey == 0)?"<div>":"") . "<span>" . $textSeparatedRow[1]["text"] . (($noCols>2)?"|".$textSeparatedRow[2]["text"]:"") . "</span></div>" . (($rowKey == 0)?"</div>":"");
            } else {
                echo "<div></div>";
            }
        }
    }   
    echo "</div>";
}


function generatePlatoonCardsHTML($currentBoxInFormation, $currentPlatoon, &$query, $platoonCard, $boxSections, &$formationCost, &$boxCost, $formationNr, $currentBoxNr, &$replacePlatoonTitle, $formationCardTitle) {
    $CR = "\n";
    $formationHTML = "";
    $cardIndex = 0; // Initialize the card index.
//    echo " form {$formationNr} sections {$boxSections[$formationNr][$currentBoxNr]}";

    if (isset($platoonCard)&&(!($platoonCard instanceof mysqli_result)&&count($platoonCard) > 0||($platoonCard instanceof mysqli_result)&&$platoonCard -> num_rows > 0)) {
        foreach ($platoonCard as $row5) {

            if ($row5["platoon"] == $currentPlatoon && isset($row5["code"])&& (!$row5["onlyAddCard"]||$row5["card"]==$replacePlatoonTitle)) { // isset code is to not show incomplete cards (price and text)
                $cardIndex++;
                $formationHTML .= "
                <label><img src='img/cardSmall.svg'><input ";
                //reset if formation card is changed
                if (($formationCardTitle != $row5["title"])&&($formationCardTitle != "")&&($row5["title"] != "")) {
                    $query[$currentBoxInFormation . "Card" . $cardIndex] ="";
                }
                $thisCost = round($row5["cost"] * $boxSections[$formationNr][$currentBoxNr] * $row5["pricePerTeam"]);
                $thisCost = ($thisCost==0)?1:$thisCost;
                if (isset($query[$currentBoxInFormation . "Card" . $cardIndex])&&($row5["code"] === $query[$currentBoxInFormation . "Card" . $cardIndex] && isset($query[$currentBoxInFormation . "Card" . $cardIndex])) || (($row5["title"] != "")&&($formationCardTitle == $row5["title"]))) {
                    $formationHTML .= " checked ";
                    $query[$currentBoxInFormation . "Card" . $cardIndex] = $row5["code"];
                    if ($row5["replacedText"] == "After") {
                        $replacePlatoonTitle = $replacePlatoonTitle . $row5["replaceWith"];
                    } elseif (($row5["replacedText"] == "") && ($row5["replaceWith"] != "")) {
                        $replacePlatoonTitle = $row5["replaceWith"] . $replacePlatoonTitle;
                    } elseif ($row5["replaceWith"] != "") {
                        $replacePlatoonTitle = str_replace($row5["replacedText"] , $row5["replaceWith"], $replacePlatoonTitle);
                    }
                    if (($formationCardTitle == "")&&($row5["title"] != "")) {
                        $replacePlatoonTitle = "{$row5["title"]}: {$replacePlatoonTitle}";
                    }
                    if ($row5["pricePerTeam"] <> 0) {

                        $boxCost[$formationNr][$currentBoxNr] += $thisCost;
                        $formationCost[$formationNr] += $thisCost;
                    } else {
                        $boxCost[$formationNr][$currentBoxNr] += $row5["cost"];
                        $formationCost[$formationNr] += $row5["cost"];
                    }
                    if (($formationCardTitle == $row5["title"])) {
                        $formationCardTitle ="";
                    }
                }
                $formationHTML .= " type='checkbox' 
                    name='" . $currentBoxInFormation . "Card" . $cardIndex . "' 
                    id='" . $currentBoxInFormation . "box-Card" . $cardIndex . "' 
                    class='" . $currentBoxInFormation . $currentPlatoon . "Card" . "' 
                    value='" . $row5['code'] . "' 
                    onchange='this.form.submit();'>";
                if ($row5["pricePerTeam"] <> 0) {
                    $formationHTML .= $thisCost;
                } else {
                    $formationHTML .= $row5["cost"];
                }
                $formationHTML .= " points: " . $row5["card"] . "</label><br>";
            }
        }
    }
    return $formationHTML;
}

function generateUnitCardsHTML($currentBoxInFormation, $currentUnit, $query, $unitCard, $boxSections, &$formationCost, &$boxCost, $formationNr, $currentBoxNr) {
    $CR = "\n";
    $formationHTML = "";
    $cardIndex = 0; // Initialize the card index.
    if (isset($unitCard)&&(!($unitCard instanceof mysqli_result)&&count($unitCard) > 0||($unitCard instanceof mysqli_result)&&$unitCard -> num_rows > 0)) {
        foreach ($unitCard as $row5) {
            if ($row5["unit"] == $currentUnit && $row5["code"] != "") {
                $cardIndex++;
                $formationHTML .= "{$CR}<label><img src='img/cardSmall.svg'><input ";
                $thisCost = round($row5["cost"] * $boxSections[$formationNr][$currentBoxNr] * $row5["pricePerTeam"]);
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

    $HTML = "<label for='{$currentBoxInFormation}box{$currentPlatoon}'><span class='platoonImageSpan'>\n";
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

function generateFormationButtonsHTML($Formations, $bookTitle, $query, $currentFormation, $currentPlatoon, $currentUnit, $insignia) {
    $html = "<button type='button' class='collapsible'><h3>No formation selected:</h3></button>";
    $html .= "<div class='Formation'>
    <div class='grid'>";

    if ($Formations->num_rows > 0) {
        foreach ($Formations as $row) {
            if ($row["Book"] == $bookTitle) {
                //$html .= "\n\t<div class='box'>";
                $html .= "\n\t\t<button type='submit' name='{$currentFormation}' value='" . $row["code"] . "' class='platoon {$query['ntn']}'>";
                

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

                $html .= "\n\t\t\t<div class='title'>";
                $html .= "\n\t\t\t<span class='left'>" . generateTitleImanges($insignia, $row["title"], $query['ntn']) . "</span>";
                $html .= "\n\t\t\t<span>" . $row["title"] . "<br>" . $row["code"] . " </span>";
                $html .= "\n\t\t\t</div>";
                $html .= "\n\t\t</button>";
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

// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------- list print functions -------------------------------------------
// -------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------

function printBoxImageAndGeneratePlatoonOptionsHTML($platoonOptionHeaders, $platoonOptionOptions,$boxRow, $configRow, $query, &$weaponsTeamsInForce, &$attachmentsInForce, $platoonIndex, $currentFormation, $unitCardImage, &$platoonCardMod) {

    $sections = explode("|",$configRow["sections"],7);
    $configImage = explode("|",$configRow["image"],7);

    $tempConfigImage = [];
    foreach ($configImage as $key => $boxImage) {

        for ($i=0; $i < $sections[$key]; $i++) { 
            $tempConfigImage[] = $boxImage;
        } 
    }
    $currentPlatoon = $boxRow["platoon"];
    $currentBoxInFormation = $currentFormation ."-" . ($boxRow["box_nr"]??"");
    $optionsHTML = "";
    $optionImage =[];
    $boxImageHTML = "";

    $optionImageReplace = "";
    $optionImageReplaceWith = "";
    $optionImageSufix = "";
    $optionImageSufixTo = "";
    $optionImageReplaceNumber = 0;
    foreach ($platoonOptionHeaders as $key5 => $row5) {
        if  ($row5["code"] == $currentPlatoon){
            foreach($platoonOptionOptions as $row4) {
                
                if  (($row4["code"] == $currentPlatoon)&&($row5["description"] == $row4["description"])) { 

                    if (isset($query[$currentBoxInFormation . "Option" .$key5])&&$row4["optionSelection"] == $query[$currentBoxInFormation . "Option" .$key5] ) {
                        if ($row4["replaceText"] != "") {
                            $optionImageReplace = $row4["replaceText"];
                            $optionImageReplaceWith = $row4["ReplacementOrSufix"];
                            $optionImageReplaceNumber = $row4["nrOfOptions"];
                        }
                        else if ($row4["ReplacementOrSufix"] != "") {
                            $optionImageSufix = $row4["ReplacementOrSufix"];
                            $optionImageSufixTo = $row4["addSufixTo"];
                        }
                        else if ($row4["image"] != "") {
                            $optionImage[] = str_repeat("<img src='img/" . $row4["image"] . ".svg'>\n",$row4["optionSelection"]);
                        }
                        if ($row4["teams"] != "") {
                            $weaponsTeamsInForce[] = $row4["teams"];
                        }
                        $optionsHTML .= "{$row5["description"]} ({$row4["optionSelection"]} selected)<br>\n";
                        if ($row4["addUnit"] != "") {
                            $attachmentsInForce[] = array("code" => $row4["addUnit"], "platoonIndex" => $platoonIndex, "title" => $row4["teams"] . ((isset( $platoonCardMod[$platoonIndex]["card"])) ? $platoonCardMod[$platoonIndex]["card"]: ""));
                        }
                        foreach ($tempConfigImage as $key1 => $boxImage){
                            if (($boxImage == $optionImageReplace)&&($optionImageReplaceNumber>0)) {
                                $tempConfigImage[$key1]=$optionImageReplaceWith;
                                $optionImageReplaceNumber--;
                            } elseif (is_numeric(strpos($boxImage, $optionImageReplace))&&($optionImageReplaceNumber>0)) {
                                $tempConfigImage[$key1] = str_replace($optionImageReplace,$optionImageReplaceWith, $boxImage );
                                $optionImageReplaceNumber--;
                            } 
                        }
                    }
                }                                  
            }
            mysqli_data_seek($platoonOptionOptions ,0);
        }
    }
    foreach ($tempConfigImage as $key1 => $boxImage){
        if ( $optionImageSufixTo <>"") {
            if (is_numeric(strpos($boxImage, $optionImageSufixTo))) {
                $tempConfigImage[$key1] = $tempConfigImage[$key1] . $optionImageSufix;
            }
        }
        if (isset($platoonCardMod[$platoonIndex])&&(isset($platoonCardMod[$platoonIndex]["ReplaceImg"])&&($platoonCardMod[$platoonIndex]["ReplaceImg"] == $boxImage)&&(($platoonCardMod[$platoonIndex]["numbers"]>0)||(is_string($platoonCardMod[$platoonIndex]["numbers"]))&&($platoonCardMod[$platoonIndex]["numbers"][1]>0)))) {
            if (isset($platoonCardMod[$platoonIndex]["perTeam"])) {
                unset($tempConfigImage[$key1]);

                
                for ($i=0; $i < $platoonCardMod[$platoonIndex]["numbers"][1]; $i++) { 
                    $tempConfigImage[] = $platoonCardMod[$platoonIndex]["replaceImgWith"];
                }
            } else {
                $tempConfigImage[$key1] = $platoonCardMod[$platoonIndex]["replaceImgWith"];
                $platoonCardMod[$platoonIndex]["numbers"]--;
            }
            
        }
    }
    foreach ($tempConfigImage as $key1 => $boxImage){
        $break = (($key1 == 0)&&($sections[$key1] == 1))?"<br>":"";
        $boxImageHTML .= $unitCardImage . "<img src='img/{$boxImage}.svg'>". $break;
    }
    if (isset($optionImage)) {
        foreach ($optionImage as $eachOptionImage) {
            $boxImageHTML .= $eachOptionImage;
        }
    }      
    return  [$boxImageHTML,$optionsHTML];
}

function configPrintHTML(&$configRow, $platoonIndex, &$weaponsTeamsInForce, &$attachmentsInForce, &$platoonCardMod) {
    $configHTML = [];
    $configCost = $configRow['cost'];
    // addWeapon is alwauýs available from the database query
    $explodeBaseTeams = explode("|",$configRow["teams"],10);
    $explodeNumberTeams = explode("|",$configRow["sections"],10);
    if (isset($platoonCardMod[$platoonIndex]["addWeapon"])) {
        $explodedTeam = explode("|",$platoonCardMod[$platoonIndex]["addWeapon"],5);
        if (count($explodedTeam)==2) {          
            $configRow["teams"] = str_replace($explodedTeam[0], $explodedTeam[1], $configRow["teams"]);
        } elseif (count($explodedTeam)==1) {
            $configRow["teams"] .= "|".$explodedTeam[0];
        }
    }

    if (isset($platoonCardMod[$platoonIndex]["ReplaceTeam"])) {

        foreach ($explodeBaseTeams as $key => $value) {
            if ($platoonCardMod[$platoonIndex]["originalTeam"]==$value) {
                if (isset($platoonCardMod[$platoonIndex]["numbers"])&&
                        (($platoonCardMod[$platoonIndex]["numbers"][0] =="P"&&$platoonCardMod[$platoonIndex]["numbers"][1]>= $explodeNumberTeams[$key])||
                         ($platoonCardMod[$platoonIndex]["numbers"][0]>= $explodeNumberTeams[$key]))) {
                    $configRow["teams"] = str_replace($platoonCardMod[$platoonIndex]["originalTeam"], $platoonCardMod[$platoonIndex]["ReplaceTeam"], $configRow["teams"]);
                    if (!isset($platoonsInForce[$platoonIndex])) {
                        $platoonsInForce[$platoonIndex] =[];
                    } else {
                        $platoonsInForce[$platoonIndex]["originalPlatoonCode"] = $platoonsInForce[$platoonIndex]["code"];
                    }

                    $platoonsInForce[$platoonIndex]["code"] = $platoonCardMod[$platoonIndex]["ReplaceUnit"];

                } else {
                    $configRow["teams"] .= "|" .$platoonCardMod[$platoonIndex]["ReplaceTeam"];
                    $configRow["sections"] .="|" . $platoonCardMod[$platoonIndex]["numbers"][0];
                    if (!isset($platoonsInForce[$platoonIndex])) {
                        $platoonsInForce[$platoonIndex] =[];
                    } else {
                        $platoonsInForce[$platoonIndex]["originalPlatoonCode"] = $platoonsInForce[$platoonIndex]["code"];
                    }
                    
                    $platoonCardMod[$platoonIndex]["attachment"] = $platoonCardMod[$platoonIndex]["ReplaceUnit"];
                }
            }
        }
    }
    if (isset($platoonCardMod[$platoonIndex]["team"])) {       
        $configRow["teams"] .= str_repeat("|".$platoonCardMod[$platoonIndex]["team"],$platoonCardMod[$platoonIndex]["numbers"]);
    }
    if (isset($platoonCardMod[$platoonIndex]["attachment"])||isset($platoonCardMod[$platoonIndex]["cardPlatoonCode"])) {
        if (($platoonCardMod[$platoonIndex]["numbers"][0] =="P")&&(isset($platoonCardMod[$platoonIndex]["image"])&&$platoonCardMod[$platoonIndex]["image"] !=="")) {
            //$platoonCardMod[$platoonIndex]["image"] = str_repeat($platoonCardMod[$platoonIndex]["image"],ceil($configRow["nrOfTeams"]/(max(1,intval($platoonCardMod[$platoonIndex]["numbers"][1])))));
        } elseif (isset($platoonCardMod[$platoonIndex]["image"])) {
            //$platoonCardMod[$platoonIndex]["image"] = str_repeat($platoonCardMod[$platoonIndex]["image"],$platoonCardMod[$platoonIndex]["numbers"]);
        }
        if (($platoonCardMod[$platoonIndex]["numbers"][0] =="P")&&($platoonCardMod[$platoonIndex]["ReplaceImg"] !=="")) {
            
            if (isset($platoonCardMod[$platoonIndex]["transport"])) {
                $sections = explode("|",$configRow["sections"],7);
                foreach (explode("|",$configRow["teams"],7) as $key => $boxTeams) {
                    if ($platoonCardMod[$platoonIndex]["originalTeam"]=$boxTeams){
                        $configRow["nrOfTeams"] -=$sections[$key];
                    }
                }
            }

            //$platoonCardMod[$platoonIndex]["numbers"] = ceil(($configRow["nrOfTeams"])/(max(1,intval($platoonCardMod[$platoonIndex]["numbers"][1]))));
            $platoonCardMod[$platoonIndex]["perTeam"] =true;
        }
        if (isset($platoonCardMod[$platoonIndex]["attachment"])) {
            $attachmentsInForce[] = array("code" => $platoonCardMod[$platoonIndex]["attachment"], "platoonIndex" => $platoonIndex, "title" => $platoonCardMod[$platoonIndex]["card"]);
        }
        
    }
    if ($configRow["teams"] <>"") {
        foreach (explode("|",$configRow["teams"],7) as $boxTeams){
            $weaponsTeamsInForce[] =  $boxTeams;
        }
    }
    if (isset($configRow["attachment"])&&$configRow["attachment"] != "") {
        $attachmentsInForce[] = array("code" => $configRow["attachment"], "platoonIndex" => $platoonIndex);
    }
    $configHTML = str_replace("\n","<br>", $configRow["configuration"])."<br>\n";
    return [$configCost, $configHTML];
}

function printPlatoonUnitCardHTML($unitCards, $boxRow, $query, &$platoonCardMod, &$attachmentsInForce, &$CardsInList, $currentBoxInFormation, $platoonIndex) {
    $unitCardImage ="";
    $cardsHTML ="";
    if (($unitCards->num_rows > 0)) {
        foreach ($unitCards as $row5) {
            if  (isset($boxRow["unitType"])&&($row5["unit"] == $boxRow["unitType"])&&(isset($row5["code"]))) {
                for ($cardIndex = 0; $cardIndex <=6; $cardIndex++){ 
                    if (isset($query[$currentBoxInFormation."uCd".$cardIndex])&&$row5["code"] == $query[$currentBoxInFormation."uCd".$cardIndex]) { 
                        if ((isset($row5["image"]))&&($row5["image"] != "0")) {
                            $unitCardImage .= "<img src='img/" . $row5["image"] . ".svg'>\n";
                        }
                        $cardsHTML .= "<img src='img/cardSmall.svg'>{$row5["card"]} ({$row5["cost"]}p)<br>";
                        if (!isset($platoonCardMod[$platoonIndex])){
                            $platoonCardMod[$platoonIndex] = $row5;
                            $platoonCardMod[$platoonIndex]["image"]="";
                            $platoonCardMod[$platoonIndex+30] = $row5;
                        }
                        if ($row5["addUnit"]<>"") {
                            $attachmentsInForce[] = array("code" => $row5["addUnit"], "platoonIndex" => $platoonIndex, "tirle" => $row5["card"] . ((isset( $platoonCardMod[$platoonIndex]["card"])) ? $platoonCardMod[$platoonIndex]["card"]: ""));
                        }
                        $CardsInList[] = $row5;

                    }
                }
            }
        }
    }
    mysqli_data_seek($unitCards ,0);
    return [$unitCardImage, $cardsHTML];
}

function printPlatoonCardHTML($platoonCards, $boxRow, $query, $currentBoxInFormation, $platoonIndex, &$platoonCardChange, &$platoonCardMod, &$CardsInList, &$platoonsInForce, $formationCardTitle, &$attachmentsInForce) {

    if (($platoonCards->num_rows > 0)) {
        
        foreach ($platoonCards as $key5 => $row5) {
            if  (($row5["platoon"] == $boxRow["platoon"])&&(isset($row5["code"]))) {
                
                for ($cardIndex = 0; $cardIndex <=17; $cardIndex++){   
                    
                    if (isset($query[$currentBoxInFormation . "Card" . $cardIndex])&&$row5["code"] == $query[$currentBoxInFormation . "Card" . $cardIndex] ) {
                        if (!isset($platoonCardMod[$platoonIndex])){
                            $platoonCardMod[$platoonIndex] = $row5;
                            $platoonCardMod[$platoonIndex+30] = $row5;
}
                        if ($row5["skill"] !== "" && $platoonCardMod[$platoonIndex]["skill"]=="") {
                            $platoonCardMod[$platoonIndex]["skill"]=$row5["skill"];
                        }
                        if ($row5["motivation"] !== "" && $platoonCardMod[$platoonIndex]["motivation"]=="") {
                            $platoonCardMod[$platoonIndex]["motivation"]=$row5["motivation"];
                        }
                        if ($row5["isHitOn"] !== "" && $platoonCardMod[$platoonIndex]["isHitOn"]=="") {
                            $platoonCardMod[$platoonIndex]["isHitOn"]=$row5["isHitOn"];
                        }
                        if ($row5["replaceUnitStats"] !== "") {
                            $exploded = explode("|",$row5["replaceUnitStats"]);
                            if ($exploded[0]=="movement") {
                                $platoonCardMod[$platoonIndex]["replaceMovement"] = $exploded[0];
                                $platoonCardMod[$platoonIndex]["TACTICAL"] = $exploded[1];
                                $platoonCardMod[$platoonIndex]["TERRAIN_DASH"]  = $exploded[2];
                                $platoonCardMod[$platoonIndex]["CROSS_COUNTRY_DASH"]  = $exploded[3];
                                $platoonCardMod[$platoonIndex]["ROAD_DASH"]  = $exploded[4];
                                $platoonCardMod[$platoonIndex]["CROSScheck"]  = $exploded[5];
                            }
                            if ($exploded[0]=="save") {
                                $platoonCardMod[$platoonIndex]["replaceSave"] = $exploded[0];
                                $platoonCardMod[$platoonIndex]["ARMOUR_SAVE"] = str_replace("|","\n",substr($row5["replaceUnitStats"], strpos($row5["replaceUnitStats"], "|") + 1));
                            }
                        }
                        
                        if (isset($row5["addWeapon"])&&$row5["addWeapon"] !== "") {
                            
                            $exploded = explode("|",$row5["addWeapon"]);

                            if ($exploded[0]=="Team") {
                                $platoonCardMod[$platoonIndex]["numbers"] = $exploded[1];
                                $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                $platoonCardMod[$platoonIndex]["image"] = "<img src='img/{$exploded[3]}.svg'>";
                                $attachmentsInForce[] = array("code" => $exploded[2], "platoonIndex" => $platoonIndex, "title" => $row5["title"]);
                            }
                            if ($exploded[0]=="ReplaceTransport") {
                                $platoonCardMod[$platoonIndex]["numbers"] = $exploded[1];
                                $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                $platoonCardMod[$platoonIndex]["originalTeam"] = $exploded[3];
                                $platoonCardMod[$platoonIndex]["ReplaceTeam"] = $exploded[4];
                                $platoonCardMod[$platoonIndex]["ReplaceImg"] = $exploded[5];
                                $platoonCardMod[$platoonIndex]["replaceImgWith"] = $exploded[6];
                                $platoonCardMod[$platoonIndex]["transport"] = true;
                                $attachmentsInForce[] = array("code" => $exploded[2], "platoonIndex" => $platoonIndex, "title" => $row5["title"]);
                            }
                            if ($exploded[0]=="WeaponsTeam") {
                                $platoonCardMod[$platoonIndex]["numbers"] = $exploded[1];
                                $platoonCardMod[$platoonIndex]["team"] = $exploded[2];
                                $platoonCardMod[$platoonIndex]["image"] = "<img src='img/{$exploded[3]}.svg'>";
                            }
                            if ($exploded[0]=="Replace") {
                                $platoonCardMod[$platoonIndex]["numbers"] = $exploded[1];
                                
                                if ($exploded[3] !="N/A") {
                                    $platoonCardMod[$platoonIndex]["cardPlatoonCode"] = $exploded[2];
                                    $platoonCardMod[$platoonIndex]["originalTeam"] = $exploded[3];
                                    $platoonCardMod[$platoonIndex]["ReplaceTeam"] = $exploded[4];
                                    $platoonCardMod[$platoonIndex]["ReplaceUnit"] = $exploded[2];
                                    //var är det denna användes?
                                    //$platoonsInForce[$platoonIndex]["originalPlatoonCode"] = $platoonsInForce[$platoonIndex]["code"];
                                    //$platoonsInForce[$platoonIndex]["code"] = $exploded[2];
                                    $platoonsInForce[$platoonIndex]["replaceImgWith"] = $exploded[6];
                                } else {
                                    $platoonCardMod[$platoonIndex]["attachment"] = $exploded[2];
                                    $attachmentsInForce[] = array("code" => $exploded[2], "platoonIndex" => $platoonIndex, "title" => $row5["title"]);
                                }
                                $platoonCardMod[$platoonIndex]["ReplaceImg"] = $exploded[5];
                                $platoonCardMod[$platoonIndex]["replaceImgWith"] = $exploded[6];

                                
                            }
                        }

                        if ($row5["replacedText"] == "After") {
                            $platoonsInForce[$platoonIndex]["title"] = $platoonsInForce[$platoonIndex]["title"] . $row5["replaceWith"];
                        } elseif (($row5["replacedText"] == "") && ($row5["replaceWith"] != "")) {
                            $platoonsInForce[$platoonIndex]["title"] = $row5["replaceWith"] . $platoonsInForce[$platoonIndex]["title"];
                        } elseif ($row5["replacedText"] != "") {
                            $platoonsInForce[$platoonIndex]["title"] = str_replace($row5["replacedText"] , $row5["replaceWith"], $platoonsInForce[$platoonIndex]["title"]);
                        }
                        if (($formationCardTitle == "")&&($row5["title"] != "")) {
                            $platoonsInForce[$platoonIndex]["title"] = "{$row5["title"]}: {$platoonsInForce[$platoonIndex]["title"]}";
                        }
                        if ((isset($boxRow["addCard"])&& $boxRow["addCard"]!="")&&($boxRow["addCard"] == $row5["code"])) {
                            $platoonCardMod[$platoonIndex]=$row5;
                            $CardsInList[] = $row5;
                        }
                        if (!isset($platoonCardMod[$platoonIndex])){
                            $platoonCardMod[$platoonIndex] = $row5;
                            $platoonCardMod[$platoonIndex+30] = $row5;
                        }
                        if (!isset($platoonCardChange[$platoonIndex]["html"])) {
                            $platoonCardChange[$platoonIndex]["html"] ="";
                            
                        }
                        $platoonCardChange[$platoonIndex]["html"] .= ((!is_numeric(strpos($platoonCardChange[$platoonIndex]["html"],$row5["card"])))?"<img src='img/cardSmall.svg'>{$row5["card"]}<br>":"");

                        $CardsInList[] = $row5;
                    }
                }
            }
        }
    }
    mysqli_data_seek($platoonCards ,0);
}

function printFormationCardsHTML($formationCards, $formationCardTitle, $formationNr, $platoonIndex, $currentBoxNr, &$platoonCardMod, $boxRow, $configRow, $query, &$CardsInList, &$platoonsInForce, &$attachmentsInForce) {
    $formationCard = [];
    $formationCardHTML ="";
    if (isset($formationCards)&&(!($formationCards instanceof mysqli_result)&&count($formationCards) > 0||($formationCards instanceof mysqli_result)&&$formationCards -> num_rows > 0)&&
     (isset($boxRow["addCard"])||isset($formationCardTitle[$formationNr])&&($formationCardTitle[$formationNr] !== ""))||
     (isset($query[$formationNr . "-" . $currentBoxNr . "-Card"])&&$query[$formationNr . "-" . $currentBoxNr . "-Card"] != "")) {
        foreach($formationCards as $row4) {
            $evalForSupport = (($row4["code"]!=="")&&isset($query[$formationNr . "-" . $currentBoxNr . "-Card"])&&($query[$formationNr . "-" . $currentBoxNr . "-Card"]==$row4["code"])&&(is_numeric(strpos($boxRow["formation"],$row4["formation"]))));
            $evalForFormation = (isset($row4["title"]))&&(($formationCardTitle[$formationNr]??"nn") == (isset($row4["title"])?trim($row4["title"]):"na"));
//command card platoon
            $evalForCcdPlatoon = ($row4["code"]!=="")&&(isset($boxRow["addCard"])&&$boxRow["addCard"] == $row4["code"]);
            if (( isset($boxRow["addCard"]))&&($boxRow["addCard"] == $row4["code"])) {
                $formationCard[] = "<img src='img/cardSmall.svg'>" . $row4["card"] . "<br>"; 
                $platoonCardMod[$platoonIndex]=$row4;
                $CardsInList[] = $row4;
                $platoonsInForce[$platoonIndex]["title"] = $row4["card"];
            }
//--
            
            if  (($evalForCcdPlatoon||$evalForSupport||$evalForFormation)&&($row4["platoonTypes"]==$boxRow["box_type"])) {
                $formationCard[] = "<img src='img/cardSmall.svg'>" . $row4["card"] . "<br>"; 
                foreach ($row4 as $subKey => $subRow) {
                    $platoonCardMod[$platoonIndex][$subKey] =$subRow;
                }
                //$platoonCardMod[$platoonIndex]=$row4;
                $CardsInList[] = $row4;
            }

            elseif (($evalForSupport||$evalForCcdPlatoon||$evalForFormation)&&($row4["platoonTypes"]=="Formation")) {
                $formationCard[] = "<img src='img/cardSmall.svg'>" .$row4["card"] . "<br>"; 
                $platoonCardMod[$platoonIndex]=$row4;
                $CardsInList[] = $row4;

            }
            elseif  (($evalForCcdPlatoon||$evalForSupport||$evalForFormation)&&($row4["platoonTypes"]=="All")) {
                $formationCard[] = "<img src='img/cardSmall.svg'>" . $row4["card"]. "<br>"; 
                $platoonCardMod[$platoonIndex]=$row4;
                $CardsInList[] = $row4;
            }

            if (($evalForFormation)&&($row4["platoonTypes"]=="Attachment")) {

                $platoonCardMod[$platoonIndex]["attachment"]=$row4;
                echo "<!--this is it {$platoonCardMod[$platoonIndex]["attachment"]["skill"]}-->";
                foreach ($attachmentsInForce as $value) {
                    if ($value["platoonIndex"] == $platoonIndex) {
                        $formationCard[] = "<img src='img/cardSmall.svg'>" . $row4["card"] . "<br>"; 
                        $CardsInList[] = $row4;
                    }
                    
                }
                
            }

        }
    }
    if ($formationCards instanceof mysqli_result) {
        mysqli_data_seek($formationCards ,0);
    }

    $formationCard = array_unique($formationCard);
    foreach ($formationCard as $key => $value) {
        $formationCardHTML .= $value;
    }
    return $formationCardHTML;
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
    mysqli_data_seek($platoonSoftStats ,0); 
    return $boxAllHTML;
}