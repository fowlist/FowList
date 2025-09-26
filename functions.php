<?php
function printPlatoonStats($platoonSoftStatRow,$configItems,$weapons,$options) {
    $smaller = ($platoonSoftStatRow["TACTICAL"]=="UNLIMITED")?" style='font-size: x-small;' ":"";
    ob_start();
    if (!empty($platoonSoftStatRow)): 


        foreach ($configItems as $config) {
            if ($config["platoon"]==$platoonSoftStatRow["code"]) {
                $platoonSoftStatRow["config"][]=$config;
                $explodedTeams = explode("|", $config["teams"]);
                foreach ($explodedTeams as $team) {
                    $platoonSoftStatRow["weapons"]=array_merge($platoonSoftStatRow["weapons"]??[],$weapons[$team]??[]);
                }
            }
        }

        foreach ($options as $platoon => $optionsRow) {
            if ($platoon==$platoonSoftStatRow["code"]) {
                foreach ($optionsRow as $platoonOpt => $value) {
                    $platoonSoftStatRow["option"][]=$value;
                    $platoonSoftStatRow["weapons"]=array_merge($platoonSoftStatRow["weapons"]??[],$weapons[$value["teams"]]??[]);
                }
            }
        }

        if (empty($platoonSoftStatRow["config"])) {
            $explodedTeams = explode("|", $platoonSoftStatRow["teams"]);
            foreach ($explodedTeams as $team) {
                $platoonSoftStatRow["weapons"]=array_merge($platoonSoftStatRow["weapons"]??[],$weapons[$team]??[]);
            }
        }

    ?>
    <tr>
        <td class='statsrow'>
            <span class='left'><b><?=$platoonSoftStatRow["title"]?></b> <i><?=$platoonSoftStatRow["code"]?></i></span>
            <br>
            <span class='left'><?=$platoonSoftStatRow["Keywords"]?></span>
            <br>
            <?php foreach (explode("|",$platoonSoftStatRow["image"]) as $image): ?>
                <img src="img/<?=$image?>.svg">
            <?php endforeach ?>
            <table class="movementTable">
                <tr>        
                    <th>TACT.</th>
                    <th>TERR. DASH</th>
                    <th>CROSS C. DASH</th>
                    <th>ROAD DASH</th>
                    <th>CROSS</th>
                </tr>
                <tr>
                    <td>
                        <b <?=$smaller?>><?=$platoonSoftStatRow["TACTICAL"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$platoonSoftStatRow["TERRAIN_DASH"]?></b>
                    </td>                    
                    <td>
                        <b <?=$smaller?>><?=$platoonSoftStatRow["CROSS_COUNTRY_DASH"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$platoonSoftStatRow["ROAD_DASH"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$platoonSoftStatRow["CROSScheck"]?></b>
                    </td>
                </tr>
            </table>

            <ul>
            <?php foreach ($platoonSoftStatRow["config"]??[] as $config): ?>
                <li>
                    <span class="config">
                        <?=str_replace("\n","<br>",$config["configuration"])?>
                    </span>
                    <i>(<?=$config["cost"]?><?=$config["dynamicPoints"]?"/dynamic: " . $config["dynamicPoints"]:""?> points) </i>
                </li>
            <?php endforeach ?>
            </ul>
            <ul>
            <?php foreach ($platoonSoftStatRow["option"]??[] as $option): ?>
                <li>
                    <span class="config">
                        <?=str_replace("\n","<br>",$option["description"])?>
                    </span>

                </li>
            <?php endforeach ?>
            </ul>
            <table class="movementTable">
                <tr>        
                    <th>weapon.</th>
                    <th>Range</th>
                    <th>h. ROF</th>
                    <th>m. ROF</th>
                    <th>AT</th>
                    <th>FP</th>
                    <th>Note</th>
                </tr>
            <?php foreach ($platoonSoftStatRow["weapons"] as $weapon): ?>

                <tr>
                    <td>
                        <b <?=$smaller?>><?=$weapon["weapon"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$weapon["ranges"]?></b>
                    </td>                    
                    <td>
                        <b <?=$smaller?>><?=$weapon["haltedROF"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$weapon["movingROF"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$weapon["antiTank"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$weapon["firePower"]?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=$weapon["notes"]?></b>
                    </td>
            <?php endforeach ?>
            </tr>
            </table>
        </td>
        <td class='statsrow'>
            <?=motivationBox(processPlatoonAttribute("motivation", [], $platoonSoftStatRow, []))?>
            <?=motivationBox(processPlatoonAttribute("skill", [], $platoonSoftStatRow, []))?>
            <?=motivationBox($platoonSoftStatRow["IS_HIT_ON"])?>
            <?=saveBox($platoonSoftStatRow["ARMOUR_SAVE"],"")?>
        </td>
    </tr>
    <?php endif;
    $html = ob_get_clean();
    return $html;
}
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


function createBoxArray($inputArray) {
    $boxNrs = [];
    $boxTypes =[];
    foreach ($inputArray as $value) {
        $boxNrs[$value["box_nr"]][] =$value;
        $boxTypes[$value["box_nr"]]["box_type"] = isset($boxTypes[$value["box_nr"]]["box_type"])?$boxTypes[$value["box_nr"]]["box_type"]:$value["box_type"];
        if ($value["box_nr"] == 1 || $value["box_nr"] =="1") {
            $boxTypes[$value["box_nr"]]["box_type"] = "Headquarters";
        } 
    }
    return [$boxNrs,$boxTypes];
}

function generateBookBoxes($Books,$query,$name = "Book") {
    ob_start();

    if (count($Books)> 0) { ?>

        <div class="grid">
        <?php
        foreach ($Books as $row) { 
            if  (($row["Nation"] == $query['ntn'])&&($row["period"] == $query['pd'])||($row["alliedBook"]??false)) { ?>
                <div class='box'>
                    <div class='platoon'>
                        <button 
                            type='submit' 
                            name='<?=$name?>' 
                            class='<?=$row["Nation"]?> initial'
                            value='<?=$row["code"]?>'>
                            <span class='nation'>
                                <img src='img/<?=is_numeric(strpos($row["Book"],"Waffen-SS"))? "shuts" : $row["Nation"] ?>.svg'>
                            </span>
                            <br>
                            <?=$row["Book"]?>
                        </button> 
                    </div>
                </div>
            <?php
                }
            }
            ?>
        </div>

    <?php
    $html = ob_get_clean();

    return $html;
    } else {
        return "";
    }

}

function sectionHeader($formation,$insignia) {
    ob_start();
    ?>

    <button type="button" class="collapsible <?=$formation["thisNation"]??""?>">
        <h3>
            <div class='left'>
             <?php if ((!empty($formation["formCard"]["title"])||(is_numeric(strpos($formation["formationCode"]??"","C"))))) :?>
                <img class='card' src='img/Card.svg'>
                <?php endif ?>
            <?=generateTitleImanges($insignia, ($formation["formCard"]["title"]??"") . ($formation["formationTitle"]??"No Formation Selected"), $formation["thisNation"]) ?> 
             </div>
             <b><?=isset($formation["formCard"])&&!empty($formation["formCard"]["title"])&&$formation["formationTitle"] !=$formation["formCard"]["title"]?trim($formation["formCard"]["title"]).": ":""?><?=$formation["formationTitle"]??""?></b>
        </h3>
        <div class="Points">
            <div>
            <?=$formation["formCost"]?> Point<?=$formation["formCost"]>1?"s":""?>
            </div>
        </div>
    </button>
    <?php
        $html = ob_get_clean();

        return $html;
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

function generateListFrameHTML($inputName, $optionsArray, $type = "list", $onChange = true, $displayText = "Choose here") {
    $output = "";

    if (count($optionsArray) > 0) {
        $output .= "<div id='{$inputName}Frame' class='list-frame' role='radiogroup' aria-label='{$displayText}'>\n";
        foreach ($optionsArray as $option) {
            $id = $inputName . "_" . htmlspecialchars($option["value"]);
            $checked = (isset($option["selected"]) && $option["selected"] == 1) ? "checked" : "";
            $images = "";
            if ("list" == $type) {
                $images = "
                    <img class='period insignia' src='img/{$option["period"]}.svg' alt=''>
                <img class='insignia' src='img/{$option["nation"]}.svg' alt=''>
                ";
            }

            $output .= "
            <label for='{$id}' class='{$type}-item' data-nation='{$option["nation"]}' data-period='{$option["period"]}' data-event='{$option["event"]}'>

                <input type='radio' name='{$inputName}' id='{$id}' value='{$option["value"]}' {$checked}>
                            {$images}
                <span class='list-text'>{$option["description"]}</span>
                <span class='event-text'>{$option["event"]}</span>
            </label>\n";
        }
        $output .= "</div>\n";
    } else {
        $output .= "<div class='list-frame empty'>0 results</div>\n";
    }

    return $output;
}

function generateDroppdownHTML($selectName, $selectID, $optionsArray, $onChange = true, $displayText = "Choose here", $firstOptionHidden = true) {
    $output =""; 


    if (count($optionsArray) > 0) {

        $output .= "<select name='" . $selectName . "' id='" . $selectID . (($onChange)?"' onchange=' this.form.submit();":"'")."'>
            <option value='' selected" . ($firstOptionHidden? "disabled hidden":"") . ">{$displayText}</option>\n";
        foreach ($optionsArray as $option) {

            $output .=  "<option " . ((isset($option["selected"])&&$option["selected"]==1) ? "selected='selected' ": "") . "value='{$option["value"]}'>{$option["description"]}</option>\n";
        }
        $output .=  "</select>\n";
    } else {
        $output .=  "0 results\n";
    }
    return $output;
}

function addCardPlatoonToSection($cardPlatoon,&$Formation_DB,&$formationSpecificPlatoonConfig,$formationNr,$query,$conn,$location) {
         
    if (isset($cardPlatoon)&&query_exists($cardPlatoon)) {
        $limitedUsed = false;
        $limitedUsedInBox = 0;
        foreach ($cardPlatoon as $value) {
            $replacePlatoonInBox = false;
            $foundit = false;
            $cardPrerequisiteEval = false;
            switch ($location) {
                case 'formation':
                    $usedEval = isset($query["F{$formationNr}-{$value["box_nr"]}"])&&$query["F{$formationNr}-{$value["box_nr"]}"]==$value["platoon"];
                    break;
                case 'support':
                    $usedEval = isset($query["Sup-{$value["box_nr"]}"])&&$query["Sup-{$value["box_nr"]}"]==$value["platoon"];
                    break;

                default:
                    
                    break;
            }

            
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
                if ($usedEval&&$limitedUsedInBox==0) {
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
                $thisImage = $conn->query(
                    "SELECT  image
                    FROM    platoonImages
                    WHERE code = '" . $value["platoon"] . "'
                    ");
                $tempImgArray = $thisImage->fetch_assoc();

                $attributeList["image"] = is_array($tempImgArray)?implode("|",$tempImgArray):$tempImgArray;

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
                    $formationSpecificPlatoonConfig[$value1["shortID"]] = $value1;
                    $formationLookup1 = $value1["formation"];
                }
            }
            if_mysqli_reset($cardPlatoonConfig);
        }
        if_mysqli_reset($cardPlatoon );
        array_multisort(array_column($Formation_DB,"box_nr"), SORT_ASC, SORT_NUMERIC,$Formation_DB);
    }
}





function cardNoteParse($card,$title = true) {

    $cardStats = [];
    $cardStats["title"]= $card["card"];
    $useStatRowInput = !empty($card["statsModifier"])?$card["statsModifier"]:(!empty($card["unitModifier"])?$card["unitModifier"]:"");

    $replace = [
        [": ",  "MOTIVATION",   "SKILL",    "IS HIT ON",    "SAVE", "ARMOUR",   "TACTICAL", "CROSS|\n", "\n|",  "|:EOS:",   "COUNTRY",  "Weapon|",   "WEAPON|"],
        [":",   ":MOTIVATION",  ":SKILL",   ":IS HIT ON",   ":SAVE",":ARMOUR",  ":TACTICAL","CROSS:",   "\n",   ":EOS:",    "C.",       "\n:Weapon|","\n:WEAPON|"]
    ];
    $useStatRow = str_replace($replace[0],$replace[1],(empty($useStatRowInput)?"":$useStatRowInput. ":EOS:"));
    $statsExplode = !empty($useStatRow)?explode(":",$useStatRow. ":EOS:"):[];
    foreach ($statsExplode as $key => $value) {
        if (str_replace(["MOTIVATION"],"",$value)!=$value) {
            $explodedMotivation = explode("/", $statsExplode[$key+1]);
            foreach ($explodedMotivation as $rowNr =>  $explodedValue) {
                if (!empty($explodedValue)) {
                    $cardStats["motivation{$rowNr}"] = motivationBox(trim(str_replace("!","|",str_replace("|","",$explodedValue))??"","\n\r\t"));
                }
                
            }

        }
        if (str_replace(["SKILL"],"",$value)!=$value) {
            $explodedSkill = explode("/", $statsExplode[$key+1]);
            $explodedSkill = array_unique($explodedSkill);
            foreach ($explodedSkill as $rowNr =>  $explodedValue) {
                $cardStats["skill{$rowNr}"] = motivationBox(trim(str_replace("|","",$explodedValue)??"","\n\r\t"));
            }
        }
        if (str_replace("IS HIT ON","",$value)!=$value) {
            $cardStats["isHitOn"] ??=  motivationBox(trim(str_replace("|","",$statsExplode[$key+1])??"","\n\r\t"));
        }
        if (str_replace(["SAVE","ARMOUR"],"",$value)!=$value) {
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
                                                                ["|","|","\n","\nMOTIVATION","\nSKILL","\nIS HIT ON","for:\n\n","follows:\n","below:\n","teams:\n"  ],str_replace($useStatRowInput,"",$card["notes"])));
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

    foreach ($card as $key => $value) {
        if (is_numeric(strpos($key,"points"))) {
            $cardStats[$key] = $value;
        }
    }

    generateDynamicGrid($cardStats,$title);

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

function processFormationCards($formationNr, $formationCards, &$query, $currentFormation, &$formationCost, &$boxesPlatoonsData) {
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
            $row5["value"] =$row5["code"];
            $useTitle = empty($row5["title"])?$row5['card']:$row5["title"];
            $row5["description"] =$useTitle;
            if ((!empty($query[$formationNr . "-Card"])&&($row5["code"] === $query[$formationNr . "-Card"]))|| 
                (empty($query[$formationNr . "-Card"])&&!empty($query[$currentFormation])&&$query[$currentFormation] == $row5["formation"]&&is_numeric(strpos($query[$currentFormation],"C")))) {
                $selected = "selected";
                $row5["selected"]= true;
                $cmdCardTitleOfEntireFormation = $row5['title']; // Set the card title
                $query[$formationNr . "-Card"] =$row5["code"];
                $boxesPlatoonsData["formCard"] =$row5;


            }
            
            if ($row5["platoonTypes"] == "Headquarters" && $row5["title"] !== "" && (!is_numeric(strpos($usedCards,$row5["title"])))) {

                $row5["description"] ="Platoon specific cost, {$useTitle}";
                $boxesPlatoonsData["formationCard"][$useTitle] = $row5;
                
            } elseif ($row5["platoonTypes"] == "" || $row5["platoonTypes"] == "All") {

                $row5["description"] =(($row5['cost']==0)?"Platoon specific cost, ":"{$row5['cost']} points per platoon: " ) . $useTitle;
                $boxesPlatoonsData["formationCard"][$useTitle] = $row5;
            } elseif ($row5["platoonTypes"] == "Formation") {

                $row5["description"] =(($row5['cost']==0)?"Platoon specific cost, ":"{$row5['cost']} points for formation: ") . $useTitle;

                $boxesPlatoonsData["formationCard"][$useTitle] = $row5;
                $boxesPlatoonsData["formCost"] += ($selected !== "") ? $row5["cost"] : 0;
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



function addConfigToBoxPlatoon($platoonConfig, &$boxesPlatoonsData,&$query,$currentBoxInFormation) {
    $thisPlatoonConfig = [];
    $thisPlatoonConfigShortCodes ="";
    if (isset($platoonConfig)&&query_exists($platoonConfig)) {
        foreach ($platoonConfig as $ConfigRowValue) {
            if ($ConfigRowValue["platoon"] === $boxesPlatoonsData["platoon"]) {
                $thisPlatoonConfig[] = $ConfigRowValue;
                $thisPlatoonConfigShortCodes .="|".$ConfigRowValue["shortID"];

            }
        }
    }

    if (!isset($query[$currentBoxInFormation . "c"])||(!is_numeric(strpos($query[$currentBoxInFormation . "c"],$boxesPlatoonsData["platoon"]))||!is_numeric(strpos($thisPlatoonConfigShortCodes, $query[$currentBoxInFormation . "c"])))) {
        unset($query[$currentBoxInFormation . "c"]);
        $boxesPlatoonsData["config"]["autoset"]=true;
    } else {
        $boxesPlatoonsData["config"][$query[$currentBoxInFormation . "c"]]["selected"] = true;
    }

    if (isset($platoonConfig)&&query_exists($platoonConfig)) {
        foreach ($thisPlatoonConfig as $ConfigRowValue) {
            if ($ConfigRowValue["platoon"] === $boxesPlatoonsData["platoon"]) {
                if (isset($query["dPs"])&&($query["dPs"]=="true")&&(!empty($ConfigRowValue["dynamicPoints"]))) {
                    $ConfigRowValue["cost"] = $ConfigRowValue["dynamicPoints"];
                } 
                if ($boxesPlatoonsData["config"]["autoset"]??false) {
                    $boxesPlatoonsData["config"][$ConfigRowValue["shortID"]]["selected"] = true;
                    $query[$currentBoxInFormation . "c"] =$ConfigRowValue["shortID"];
                    unset($boxesPlatoonsData["config"]["autoset"]);
                }
                if ($boxesPlatoonsData["config"][$ConfigRowValue["shortID"]]["selected"]??false) {

                    $boxesPlatoonsData["platoonCost"] = $boxesPlatoonsData["platoonCost"]??0 + $ConfigRowValue["cost"];
                    actualSectionsEval($ConfigRowValue, $boxesPlatoonsData["boxSections"]);
                    $query[$currentBoxInFormation . "c"] =$ConfigRowValue["shortID"];
                    $boxesPlatoonsData["configCost"] = $boxesPlatoonsData["configCost"]??0 + $ConfigRowValue["cost"];
                }
                $boxesPlatoonsData["box_type"] .= (!empty($ConfigRowValue["attachment"])?"|Attachment":"");

                $boxesPlatoonsData["config"][$ConfigRowValue["shortID"]] = array_merge($boxesPlatoonsData["config"][$ConfigRowValue["shortID"]]??[],$ConfigRowValue);

            } 
        }
    }
}


function addOptionsToBoxPlatoon($platoonOptionHeaders, $platoonOptionOptions,&$boxesPlatoonsData,&$query, $currentBoxInFormation) {
    foreach ($platoonOptionOptions as $optionRowValue) {
        if ($optionRowValue["code"] === $boxesPlatoonsData["platoon"]){
            foreach ($platoonOptionHeaders[$boxesPlatoonsData["platoon"]] as $optionID => $headerRowValue) {
                if ($headerRowValue["code"] === $boxesPlatoonsData["platoon"]&&$optionRowValue["description"]===$headerRowValue["description"]) {
                    if (isset($query["dPs"])&&($query["dPs"]=="true")&&!empty($headerRowValue["dynamicPoints"])) {
                        $optionRowValue["price"] = $headerRowValue["dynamicPoints"]*$optionRowValue["optionSelection"];
                    }
                    $optionRowValue["cost"] = $optionRowValue["price"];
                    if ($optionRowValue["optionSelection"] === ($query[$currentBoxInFormation . "Option" . ($headerRowValue["oldNr"]??"")]??"")||$optionRowValue["optionSelection"] === ($query[$currentBoxInFormation . "Op" . $optionID]??"")) {
                        unset($query[$currentBoxInFormation . "Option" . $headerRowValue["oldNr"]]);
                        $query[$currentBoxInFormation . "Op" . $optionID] = $optionRowValue["optionSelection"];
                        $optionRowValue["selected"] =true;

                        $boxesPlatoonsData["platoonCost"] = ($boxesPlatoonsData["platoonCost"]??0) + $optionRowValue["cost"];
                        $boxesPlatoonsData["Options"][$optionID]["thisCost"] = $optionRowValue["cost"];
                        
                    }
                    $boxesPlatoonsData["Options"][$optionID]["dDAlternatives"][]=$optionRowValue;
                    $boxesPlatoonsData["Options"][$optionID]["description"] = $headerRowValue["description"];

                }
            }
        } 
    }
}

function addFormationCardToBoxPlatoon($formationCards, &$boxesPlatoonsDataFormation, $currentBoxNr, $platoonInBox, $query, $formationNr) {
    $formationCardToggle =TRUE;
    if ($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["selected"]??false) {

        foreach ($formationCards as $foramtionCardRow) {
            $foramtionCardRow["priceFactor"] = $foramtionCardRow["cost"] * $foramtionCardRow["pricePerTeam"]??1;
            $thisCost = round($foramtionCardRow["cost"] * $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["boxSections"]["total"] * $foramtionCardRow["pricePerTeam"]);
            if  ($thisCost >= 0 ) {
                $thisCost = max($thisCost,1.0);
            } else {
                $thisCost = min($thisCost,-1.0);
            }
            
            if ((($boxesPlatoonsDataFormation["formCard"]["title"]??"N/A") === $foramtionCardRow["title"] && ((is_numeric(strpos($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["box_type"],$foramtionCardRow["platoonTypes"]))) || 
                 ($foramtionCardRow["platoonTypes"] == "All"))) ||
                (($foramtionCardRow["platoonTypes"] == "Formation") && (($boxesPlatoonsDataFormation["formCard"]["title"]??"N/A")  == $foramtionCardRow["title"]) || 
                 ($foramtionCardRow["code"] === ($query[$formationNr . "-Card"]??"")&&empty($foramtionCardRow["platoonTypes"]) ))) {
                // -- box command card summary cost --
                if ($foramtionCardRow["pricePerTeam"] != 0) {
                    $foramtionCardRow["cost"] = $thisCost;
                }

                // -- save title to print in each box --
                if (($foramtionCardRow["pricePerTeam"] != 0) && ($foramtionCardRow["platoonTypes"] != "Formation")) {
                    $foramtionCardRow["cost"] = $thisCost;
                } 
                if ($foramtionCardRow["platoonTypes"] == "Formation") {
                    $foramtionCardRow["cost"] =0;
                }
                $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["formCards"][] = $foramtionCardRow;
                $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"] =($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"]??0) + $foramtionCardRow["cost"];
            }
            
        }
    }
}



function generateDynamicGrid($data,$title=true) {
    echo '<div class="row-container">';
    if ($title) {
        echo "<div class='cell cell-title'><b>{$data["title"]}</b></div>";
    }
        

        // Optional content for the third column
        $optionalContent = '';
        $stringLength = strlen($data["notes"])<200?" style='flex-wrap: nowrap'":"";
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
    $motivationTitles = [
        "CONFIDENT",
        "FEARLESS",
        "RELUCTANT",
        "SS TIGER",
        "TRAINED",
        "VETERAN",
        "CONSCRIPT",
        "GREEN",
        "CAREFUL",
        "AGGRESSIVE",
        "RECKLESS",
        "-"
    ];

    foreach ($motivationArray as $rowKey => $motivationRow) {
        foreach ($motivationRow as $columnKey => $motivationColumn) {
            if (($rowKey == 0)) {
                foreach ($motivationTitles as $title) {
                    if (is_numeric(strrpos(strtoupper($motivationColumn),$title))) {
                        $textSeparatedArray[$rowKey][$columnKey]['class']="grid-heading";
                        break;
                    } else {
                        $textSeparatedArray[$rowKey][$columnKey]['class']= "mot-first-grid-item";
                    }
                }
                foreach ($diceRolls as $needle) {
                    if (is_numeric(strrpos($motivationColumn,$needle))) {
                        $textSeparatedArray[$rowKey][$columnKey]['class']="grid-heading";
                        break;
                    }
                }             
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

function addPlatoonCardToBoxPlatoon($platoonCards, &$boxesPlatoonsDataFormation, $currentBoxNr, $platoonInBox, &$query, $formationNr,$currentBoxInFormation=false) {
    if (!$currentBoxInFormation) {
        $currentBoxInFormation = "{$boxesPlatoonsDataFormation["currentFormation"]}-{$currentBoxNr}";
    }
    
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
    $cardArray = [];

    foreach ($query as $key => $value) {
        if (strpos($key, $currentBoxInFormation . "Card") === 0) {
            $cardArray[$key] =$value;
        }
    }
    if ($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["selected"]??false) {
        if (isset($platoonCards)&&query_exists($platoonCards))

        foreach ($platoonCards as $PlatoonCardRow) {
            $typePlatoonEval = ($PlatoonCardRow["platoonTypes"]== "")||
                                ($PlatoonCardRow["platoonTypes"]== "Platoon")||
                                is_numeric(strpos($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["box_type"],$PlatoonCardRow["platoonTypes"]));
            $cardPlatoonEval = (!$PlatoonCardRow["onlyAddCard"]||$PlatoonCardRow["card"]==$boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"]);
            $thisCardIsLimitedaAndUsedSomewhereElse = (!empty($query[$PlatoonCardRow["code"]]) && $query[$PlatoonCardRow["code"]]!=$currentBoxInFormation);
            $generalEval = !$thisCardIsLimitedaAndUsedSomewhereElse&&$PlatoonCardRow["platoon"] == $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoon"] && isset($PlatoonCardRow["code"])&&$cardPlatoonEval&&$typePlatoonEval;
            
            if ($generalEval) {

                if (isset($query["dPs"])&&($query["dPs"]=="true")&&(!empty($PlatoonCardRow["dynamicPoints"]))) {
                    $PlatoonCardRow["cost"] = $PlatoonCardRow["dynamicPoints"];
                }
                $useCost = $PlatoonCardRow["cost"];
                $cardIndex++;
                
                $cardsPrerequisitEval = true;
                if ((!empty($PlatoonCardRow["prerequisite"])&&!is_numeric(strpos($currentBoxInFormation,"BlackBox")))&&$PlatoonCardRow["prerequisite"]!="Warrior"&&$PlatoonCardRow["prerequisite"]!="AddOn"&&$PlatoonCardRow["prerequisite"]!="Limited") {
                    $cardsPrerequisitEval =false;
                    $PlatoonCardRow["formcard"]  = true;
                    foreach (explode("|",$PlatoonCardRow["prerequisite"]) as $value) {
                        $cardsPrerequisitEval = is_numeric(strpos($cardsPrerequisitEvalSource,$value)) || $cardsPrerequisitEval;
                    }
                }
                if (!empty($PlatoonCardRow["prerequisite"])&&substr($PlatoonCardRow["prerequisite"],0,5)=="AddOn") {
                    $PlatoonCardRow["formcard"]  = false;
                    foreach (explode("|",$PlatoonCardRow["prerequisite"]) as $value) {
                        $cardsPrerequisitEval = is_numeric(strpos($cardsPrerequisitAddOnEvalSource,$value)) || $cardsPrerequisitEval;
                    }
                }

                if ($generalEval) {
                    $cardInQuery =false;
                    
                    foreach ($cardArray as $key => $value) {
                        if ($PlatoonCardRow["code"]===$value) {
                            $cardInQuery =true;
                            break;
                        }
                    }
                    $PlatoonCardRow["disabled"] = !$cardsPrerequisitEval;

                    $thisCardEval = $cardInQuery || (($PlatoonCardRow["title"] != "")&&(($boxesPlatoonsDataFormation["formCard"]["title"]??"N/A") == $PlatoonCardRow["title"]));
                    if ((($boxesPlatoonsDataFormation["title"]??"") != $PlatoonCardRow["title"])&&(!empty($boxesPlatoonsDataFormation["formCard"]["title"]))&&($PlatoonCardRow["title"] != "")) {
                        unset($query[$currentBoxInFormation . "Card" . $cardIndex]);
                    }
                    $thisCost = round($useCost * 
                    $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["boxSections"]["total"] * 
                    $PlatoonCardRow["pricePerTeam"]);

                    $thisCost = ($thisCost==0)?1:$thisCost;
                    if ($PlatoonCardRow["pricePerTeam"]!=0) {
                        $PlatoonCardRow["thisCost"] = $thisCost;
                    } else {
                        $PlatoonCardRow["thisCost"] = $useCost;
                    }

                    if (!empty($PlatoonCardRow["multiSelect"])) {
                        $optionsInCard = explode("|",$PlatoonCardRow["multiSelect"]);
                        $PlatoonCardRow["thisCost"] = $useCost;
                        for ($optionNumber=1; $optionNumber < $optionsInCard[0]+1; $optionNumber++) {

                            $PlatoonCardRow["options"][$optionNumber]["value"] = $optionNumber;
                            $PlatoonCardRow["options"][$optionNumber]["description"] = $optionsInCard[1];
                            $PlatoonCardRow["options"][$optionNumber]["cost"] = $PlatoonCardRow["thisCost"] * $optionNumber;
                            $cardInQuery = false;
                            foreach ($cardArray as $key => $value) {
                                if ($PlatoonCardRow["code"].$optionNumber===$value) {
                                    $cardInQuery =true;
                                    break;
                                }
                            }
                            
                            if ((empty($query["Warrior"])||(isset($query["Warrior"])&&$PlatoonCardRow["code"]==$query["Warrior"]||($PlatoonCardRow["prerequisite"]!="Warrior")))&&$cardInQuery&&$cardsPrerequisitEval) {
                                $PlatoonCardRow["options"][$optionNumber]["selected"] = true;
                                $PlatoonCardRow["thisCost"] = $PlatoonCardRow["thisCost"]*$optionNumber;
                                $PlatoonCardRow["currentCost"] = $PlatoonCardRow["thisCost"];
                                $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"] =($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"]??0) + $PlatoonCardRow["options"][$optionNumber]["cost"];

                                if ($PlatoonCardRow["prerequisite"]=="Warrior") {
                                    $query["Warrior"] = $PlatoonCardRow["code"];
                                }
                                if (!empty($PlatoonCardRow["limited"])) {
                                    $query[$PlatoonCardRow["code"]] = $currentBoxInFormation;
                                }
                            }
                            if (!empty($query["Warrior"])&&$PlatoonCardRow["code"]!=$query["Warrior"]&&$PlatoonCardRow["prerequisite"]=="Warrior"&&$cardsPrerequisitEval) {
                                unset($query[$currentBoxInFormation . "Card" . $cardIndex]);
                            }
                        }
                    } else {
                        if ((empty($query["Warrior"])||(isset($query["Warrior"])&&$PlatoonCardRow["code"]==$query["Warrior"]||($PlatoonCardRow["prerequisite"]!="Warrior")))&&$thisCardEval&&$cardsPrerequisitEval) {

                            $PlatoonCardRow["selected"] = true;
                            $PlatoonCardRow["currentCost"] = $PlatoonCardRow["thisCost"];
                            $query[$currentBoxInFormation . "Card" . $cardIndex] = $PlatoonCardRow["code"];
                            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"] =($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCost"]??0) + $PlatoonCardRow["thisCost"];
                            if ($PlatoonCardRow["prerequisite"]=="Warrior") {
                                $query["Warrior"] = $PlatoonCardRow["code"];
                            }
                            if (!empty($PlatoonCardRow["limited"])) {
                                $query[$PlatoonCardRow["code"]] = $currentBoxInFormation;
                            }

                        } 
                    }
                    $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCards"][$cardIndex] = $PlatoonCardRow;

                    if ($thisCardEval) {

                        if ($PlatoonCardRow["replacedText"] == "After") {
                            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"] = $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"] . $PlatoonCardRow["replaceWith"];
                        } elseif (($PlatoonCardRow["replacedText"] == "") && ($PlatoonCardRow["replaceWith"] != "")) {
                            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"] = $PlatoonCardRow["replaceWith"] . $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"];
                        } elseif ($PlatoonCardRow["replaceWith"] != "") {
                            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"] = str_replace($PlatoonCardRow["replacedText"] , $PlatoonCardRow["replaceWith"], $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"]);
                        }
                        if ((($boxesPlatoonsDataFormation["formCard"]["title"]??"N/A") == "")&&($PlatoonCardRow["title"] != "")) {
                            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"] = "{$PlatoonCardRow["title"]}: {$boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["title"]}";
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
        if (isset($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCards"])) {
            $tempArr1 = [];
            $boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCards"] = array_filter($boxesPlatoonsDataFormation["boxes"][$currentBoxNr][$platoonInBox]["platoonCards"], function ($card) use (&$tempArr1) {
                if (!in_array($card["code"], $tempArr1)) {
                    $tempArr1[] = $card["code"];
                    return true;
                }
                return false;
            });
        }
    }
}

function addUnitCardsToBoxPlatoon($unitCards,&$boxesPlatoonsData,$query,$currentBoxInFormation) {
    $cardIndex = 0;
    if (isset($unitCards)&&query_exists($unitCards)) {
        foreach ($unitCards as $unitCardKey => $unitCardRow) {
            if ($unitCardRow["unit"] == $boxesPlatoonsData["unitType"] && !empty($unitCardRow["code"])) {
                $cardIndex++;
                if ($unitCardRow["pricePerTeam"] != "0") {
                    $thisCost = round($unitCardRow["cost"] * $boxesPlatoonsData["boxSections"]["total"] * $unitCardRow["pricePerTeam"]);
                } else {
                    $thisCost = $unitCardRow["cost"];
                }
                $thisCost = max($thisCost,1.0);
                $unitCardRow["thisCost"] = $thisCost;
                if (isset($query[$currentBoxInFormation . "uCd" . $cardIndex])&&$unitCardRow["code"] === $query[$currentBoxInFormation . "uCd" . $cardIndex]) {
                    $unitCardRow["selected"] = true;
                    $boxesPlatoonsData["platoonCost"] += $unitCardRow["thisCost"];
                }
                $boxesPlatoonsData["uCard"][$cardIndex] = $unitCardRow;
            }
        }
    }
}

function generateTitleImanges($insignia, $title, $nation) {
    $HTML = "";

    foreach ($insignia as $row) {
        if ((strpos($title,$row["term"]) !== false)&&(($row["ntn"] == "")||(($nation == $row["ntn"])))) {
            $HTML  = "<img src='img/{$row['img']}.svg'>";
            break;
        }
    }

    if ($insignia instanceof mysqli_result) {
    mysqli_data_seek($insignia ,0);
}
    if (($HTML == "")&&(isset($nation))) {
        $HTML  .= "<img src='img/{$nation}.svg'>";
    }
    return $HTML;
}


function platoonOptionChangedAnalysis($row, $platoonOptionHeaders, $platoonOptionOptions) {
    $platoonOptionChanged = [];
    $platoonOptionHeadersChanged = [];
    if (!empty($row["optionChange"])&&$row["optionChange"] != "Remove") {
        $optionChangeRow = explode("\n",$row["optionChange"]);
        $optionNr=0;
        $code = $row["platoon"];
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
            $description = $temp[0];
            $needle = ["code" => $code,"description" => $description, "oldNr" => 0, "dynamicPoints" => ($row["optionChangeDp"]??"")];
            // Check if the combination of Nation and period exists in the unique array
            if (!in_array($needle, $platoonOptionHeadersChanged[$code]??[])) {
                $platoonOptionHeadersChanged[$code][]  = $needle;
            }
        }
    } elseif ((!empty($row["optionChange"])&&$row["optionChange"] == "Remove")) {
        $code = $row["platoon"];
        $platoonOptionHeaders[$code] =[];

        $platoonOptionHeadersChanged = $platoonOptionHeaders;
        $platoonOptionChanged = $platoonOptionOptions;
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
                "image" => str_replace("!","|",$temp[5]??""),
                "teams" => str_replace("!","|",$temp[6]??""),
                "nrOfTeams" => array_sum(explode("!",$temp[2],15)),
                "attachment" => isset($temp[7])?str_replace("!","|",$temp[7]):"",
                "dynamicPoints" =>(!empty($temp[8])?str_replace("!","|",$temp[8]):null)
            );
        }
    }
    else {
        foreach ($platoonConfig as $key => $value) {
            $platoonConfigChanged[$key] = $value;
        }
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
                    if (isset($platoonCardMod[$platoonIndex]["attachment"])&&!is_string($platoonCardMod[$platoonIndex]["attachment"])) {
                        $platoonCardMod[$platoonIndex]["attachment"][$subKey] = $subRow;
                    }
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
    $thisCost = (isset($cardRowPlatoon["dynamicPoints"]) && $cardRowPlatoon["dynamicPoints"]!=""?$cardRowPlatoon["dynamicPoints"]:$cardRowPlatoon["cost"]);
    
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

function generatePlatoonOptionsPrintHTML($platoonOptionHeaders, $platoonOptionOptions,$boxRow, &$configRow, $query, &$weaponsTeamsInForce, &$attachmentsInForce, $platoonIndex, $currentFormation, &$platoonsInForce =[],$currentBoxInFormation=null) {

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
    if (!isset($currentBoxInFormation)) {
        $currentBoxInFormation = $currentFormation ."-" . ($boxRow["box_nr"]??"");
    }
    $optionsHTML ="";

    $optionImageReplaceNumber = 0;
    if (isset($platoonOptionHeaders[$currentPlatoon])) {
        foreach ($platoonOptionHeaders[$currentPlatoon] as $key5 => $row5) {
        if  ($row5["code"] == $currentPlatoon){

            foreach($platoonOptionOptions as $row4) {

                if  (($row4["code"] == $currentPlatoon)&&($row5["description"] == $row4["description"])) {

                        if ((isset($query[$currentBoxInFormation . "Option" .$row5["oldNr"]])&&$row4["optionSelection"] == $query[$currentBoxInFormation . "Option" .$row5["oldNr"]])||$row4["optionSelection"] === ($query[$currentBoxInFormation . "Op" . $key5]??"")) {
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
    }
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
           ($platoonCardMod[$platoonIndex]["ReplaceImg"] == $tempConfigImage[$key1])&&
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
        $break = ($infantryPC)?"\n<br>\n":"";
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

                for ($cardIndex = 0; $cardIndex <=12; $cardIndex++){   
                    $queryKey = $currentBoxInFormation . "Card" . $cardIndex;
                    if (isset($query[$queryKey])&&$cardRowPlatoon["code"] == substr($query[$queryKey],0,6) ) {
                        updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $cardRowPlatoon);

                        if ($cardRowPlatoon["prerequisite"]=="Warrior") {
                            $platoonCardMod[$platoonIndex]["Warrior"] = "Warrior";
                        }
                        if ($cardRowPlatoon["prerequisite"]!=="Warrior"){
                            updateNonEmptyPlatoonCardMod($platoonCardMod, $platoonIndex, $cardRowPlatoon, true);
                            if (isset($platoonCardMod[$platoonIndex]["attachment"])&&!is_string($platoonCardMod[$platoonIndex]["attachment"])) {
                                $platoonCardMod[$platoonIndex]["attachment"]["replaceUnitStats"] = "";
                            }
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
                            $thisCost = ($cardRowPlatoon["dynamicPoints"]!=""?$cardRowPlatoon["dynamicPoints"]:$cardRowPlatoon["cost"]) * substr($query[$currentBoxInFormation . "Card" . $cardIndex],6,1);
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
            $evalForFormation = isset($formCardRow["title"])&&(($formationCardTitle[$formationNr]??"nn") == (isset($formCardRow["title"])?trim($formCardRow["title"]):"na"))&&(is_numeric(strpos($boxRow["formation"],$formCardRow["formation"])));
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
    $platoonIndex = $platoonRow['platoonIndex']??"";
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