<?php
foreach ($SupporboxNrs as $BoxInSection){ 

    $currentBoxNr =             $BoxInSection["box_nr"];
    $thisBoxType =              $BoxInSection["box_type"];
    $currentBoxInFormation =    $currentFormation."-".$currentBoxNr;
    $thisBoxSelectedPlatoon =   $query[$currentBoxInFormation];
    $supportHTML[1] .=
    "<div id='{$currentBoxInFormation}box' class='box'><b>{$thisBoxType}</b><br>";
    foreach ($combinedSupportDB as $platoonInBox) {
        if($currentBoxNr == $platoonInBox["box_nr"]){
            $platoonConfigChanged = []; 
            $formationCardHTML =  "";
            $currentPlatoon =   $platoonInBox["platoon"];
            $currentUnit =      $platoonInBox["unitType"];
            $platoonTitle =     $platoonInBox["title"];
            $supportHTML[1] .=  
            "<div class='platoon'>
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
                $platoonConfigChanged = $supportConfig;
            }
            $cardIndex =0;
            if (($currentPlatoon !=="")&&($currentPlatoon == $thisBoxSelectedPlatoon)&&(isset($platoonInBox["cardNr"]))) {
                foreach ($platoonCards as $key => $value) {
                    if ($value["platoon"] == $currentPlatoon && isset($value["code"]) || ($value["code"] == $platoonInBox["cardNr"]) ) { // isset code is to not show incomplete cards (price and text)
                        $cardIndex++;
                        if ($value["code"] == $platoonInBox["cardNr"]) {
                            $query[$currentBoxInFormation . "Card" . $cardIndex] =  $platoonInBox["cardNr"];
                        }
                    }
                }
            }
            // ------ Config of platoon -------------       
            $boxConfigHTML = processPlatoonConfig($currentPlatoon, $platoonConfigChanged, $currentBoxInFormation, $formationNr, $currentBoxNr, $query, $boxCost, $formationCost, $boxSections);
// ------ cmdCards of platoon -------------              
            if (($currentPlatoon == $query[$currentBoxInFormation])) {
//------- check if the platoon have cards available                    
                $formationCardHTML .= generateCardArrays($formationCards, $thisBoxType, $formationCard, $unitCards, $currentUnit, $unitCard, $platoonCards, $currentPlatoon, $platoonCard);
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
            <label for='{$currentBoxInFormation}box{$currentPlatoon}'> <span class='left'>"
            . generateTitleImanges($insignia,$platoonInBox["title"], ($platoonInBox["Nation"]<>"")?$platoonInBox["Nation"]:$query['ntn']) 
            . "</span><span><b>{$platoonTitle}</b><br>
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
    if (isset($boxCost[$formationNr][$BoxInSection["box_nr"]])) {
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