<?php
foreach ($boxNrs as $BoxInSection){ 
// ------- set reused variables
    $currentBoxNr = $BoxInSection;
    foreach($boxTypes as $row4) {
        if (($row4["box_nr"] == $currentBoxNr)) {
            $thisBoxType = $row4["box_type"];
            break;
        }
    }
    $currentBoxInFormation =    $currentFormation."-".$currentBoxNr;
    $thisBoxSelectedPlatoon =   $query[$currentBoxInFormation];
    $currentPlatoonFormation = $query[$currentFormation];
    $formationHTML[$currentBoxInFormation] .=
    "<div id='{$currentBoxInFormation}box' class='box'><b>{$thisBoxType}</b><br>";
    foreach ($Formation_DB as $platoonInBox) {
        // ---- reset $thisBoxType for each separate platoon -------------
        $thisBoxType = $platoonInBox["box_type"];
        if($currentBoxNr == $platoonInBox["box_nr"]){
            $currentPlatoon =   $platoonInBox["platoon"];
            $currentUnit =      $platoonInBox["unitType"];
            $platoonTitle =     $platoonInBox["title"];
            $platoonHaveCards = FALSE;
//------- Black box
            $formationHTML[$currentBoxInFormation] .= 
            "<div" .((($thisBoxType == "Headquarters")||($platoonInBox["BlackBox"]== 1)) ? " class='blackbox" : " class='platoon") .(($currentPlatoon == $thisBoxSelectedPlatoon) ? " checkedBox" : ""). "'>";
// ----- checked and set status from session variablse for the selected platoon in the box 
            $formationHTML[$currentBoxInFormation] .= "
            <input" . (($currentPlatoon == $thisBoxSelectedPlatoon) ? " checked" : "") . " id='{$currentBoxInFormation}box{$currentPlatoon}' 
            type='checkbox' 
            name='{$currentBoxInFormation}' 
            class='{$currentBoxInFormation}' 
            value='{$currentPlatoon}' " . <<<HTML
            onchange="\$('.{$currentBoxInFormation}').not(this).prop('checked', false); this.form.submit();">
HTML;
            if ($platoonInBox["configChange"]!="") {
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
            if (($currentPlatoon == $query[$currentBoxInFormation])) {
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
            $formationHTML[$currentBoxInFormation] .= generateTitleImanges($insignia, $cmdCardTitleOfEntireFormation[$formationNr] . $platoonInBox["title"], ($platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$thisNation["ntn"]) . "</span>\n <span>";
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