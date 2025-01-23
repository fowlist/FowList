<?php



function msi($platoonInBox) {
    ob_start();
    if (isset($platoonInBox["motivSkillHitOn"])): ?>
        <span class='topright'>
            <div class='MSI'>
                <div>
                    <?=$platoonInBox["motivSkillHitOn"]?>
                </div>
            </div>
        </span>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}

function platoonTitle($platoonInBox,$formation) {
    ob_start();
     ?>
        <div class="title">
            <span class="left"><?=$platoonInBox["insignia"]??""?></span>
            <span class="titleGrid">
            <b>
                <?=isset($formation["formationCard"])&&!empty($formation["formationCard"]["title"])?trim($formation["formationCard"]["title"]).": ":""?><?=str_replace(" Force","",$platoonInBox["title"]??"")?>
            </b>
            </span>
            <span class="cardCode"><?=$platoonInBox["platoon"]??""?></span>
        </div>
    <?php
    $html = ob_get_clean();

    return $html;
}

function platoonConfigHTML($platoonInBox,$boxPositionID) {
    ob_start();
    ?>
            <div class="configBox">
                <select id="<?=$boxPositionID?>" name="<?=$boxPositionID?>c" class="select-element" currentCost="<?=$platoonInBox["configCost"]?>">
                <?php foreach ($platoonInBox["config"] as $shortID => $config) {?>
                    <option <?=($config["selected"]??false)?"selected":""?> value="<?=$shortID?>" cost="<?=$config["cost"]?>" nrOfTeams="<?=$config["nrOfTeams"]?>"><?=$config["configuration"]?> <?=$config["cost"]?> points</option>
                <?php } ?>
                </select>
            </div>
    <?php 
    $html = ob_get_clean();

    return $html;
}

function boxOptionPrintHTML($platoonInBox,$boxPositionID) {
    ob_start();
    if (!empty($platoonInBox["Options"])): ?>
        <div class="optionBox">
        <?php foreach ($platoonInBox["Options"] as $optionNr => $options):
            if (count($options["dDAlternatives"])>1) { ?>
                <?=$options["description"]?>
                <br>
                <select 
                    id="<?=$boxPositionID?>box-Op<?=$optionNr?>" 
                    name="<?=$boxPositionID?>Op<?=$optionNr?>" 
                    currentCost="<?=$options["thisCost"]??"0"?>"
                    class="<?=$boxPositionID?>Option">
                    <option 
                        value="">
                        No <?=$options["dDAlternatives"][0]["teams"]?>
                    </option>
                <?php foreach ($options["dDAlternatives"] as $optionRow):?>
                    <option 
                        <?=($optionRow["selected"]??false)?"selected":""?> 
                        value="<?=$optionRow["optionSelection"]?>" cost="<?=$optionRow["cost"]??"0"?>">
                        <?=$optionRow["nrOfOptions"]?>x <?=$optionRow["teams"]?> (<?=$optionRow["cost"]?> points)
                    </option>
                <?php endforeach ?>
                </select>
            <?php } else { ?>
                <label>
                    <input 
                        <?=($options["dDAlternatives"][0]["selected"]??false)?"checked":""?> 
                        type="checkbox" 
                        id="<?=$boxPositionID?>box-Op<?=$optionNr?>" 
                        name="<?=$boxPositionID?>Op<?=$optionNr?>" 
                        class="<?=$boxPositionID?>Option" 
                        value="<?=$options["dDAlternatives"][0]["optionSelection"]?>"
                        cost="<?=$options["dDAlternatives"][0]["cost"]??"0"?>">
                    <?=$options["description"]?>
                </label>
            <?php } ?>
        <?php endforeach?>
        </select>
        </div>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}

function boxformCardPrintHTML($platoonInBox) {
    ob_start();
    if (!empty($platoonInBox["formCards"])): ?>
        <div class="optionBox">
        <button type="button" class="smallCard-btn smallCard" data-cards="<?=htmlspecialchars(json_encode(array_column($platoonInBox["formCards"],"code")??[]))?>"><img src="img/cardSmall.svg"></button>
        <?php foreach ($platoonInBox["formCards"] as $cardNr => $card):
            if (isset($card["card"])): ?>
            <card value="<?=$card["code"]?>" currentCost="<?=$card["cost"]??"0"?>" cost="<?=$card["cost"]??"0"?>" priceFactor="<?=$card["priceFactor"]??"0"?>">
                <?=$card["card"]?> <?=!empty($card["cost"])?"{$card["cost"]} Points: ":""?>
            </card>
            <?php endif ?>
        <?php endforeach?>
        </div>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}

function boxPlatoonCardPrintHTML($platoonInBox,$boxPositionID) {
    ob_start();
    if (!empty($platoonInBox["platoonCards"])):?>
        <div class="optionBox">
        <button type="button" class="smallCard-btn smallCard" data-cards="<?=htmlspecialchars(json_encode(array_column($platoonInBox["platoonCards"],"code")??[]))?>"><img src="img/cardSmall.svg"></button>
        <?php foreach ($platoonInBox["platoonCards"] as $cardNr => $card):
            if (isset($card["options"])): ?>
            <span class="<?=($card["disabled"]&&$card["formcard"]??false)?"hidden":""?>">
            <?=$card["card"]?>
                <select 
                    name="<?=$boxPositionID?>Card<?=$cardNr?>" 
                    id="<?=$boxPositionID?>box-Card<?=$cardNr?>" 
                    class="<?=$boxPositionID?>Option"
                    currentCost="<?=$card["currentCost"]??"0"?>"
                    prerequisite="<?=$card["prerequisite"]?>"
                    <?=$card["disabled"]?"disabled":""?>>
                    <option value="">
                        No <?=$card["card"]?>
                    </option>
                <?php foreach ($card["options"] as $optionNr => $optionRow): ?>
                    <option
                        <?=($optionRow["selected"]??false)?"selected":""?> 
                        value="<?=$card["code"]?><?=$optionNr?>" cost="<?=$optionRow["cost"]?>">
                            <?=$optionRow["value"]?>x <?=$optionRow["description"]?> (<?=$optionRow["cost"]?> points)
                    </option>
                <?php endforeach ?>
                </select>
            </span>

           <?php elseif (isset($card["card"])): ?>
            <label class="<?=(($card["disabled"]&&$card["formcard"])??false)?"hidden":""?>">
                <input 
                    type="checkbox"
                    <?=($card["formcard"]??false)?"formcard":""?>
                    <?=($card["selected"]??false)?"checked":""?> 
                    name="<?=$boxPositionID?>Card<?=$cardNr?>" 
                    id="<?=$boxPositionID?>box-Card<?=$cardNr?>"
                    currentCost="<?=$card["currentCost"]??"0"?>"
                    cost="<?=$card["cost"]?>"
                    pricePerTeam="<?=$card["pricePerTeam"]?>"
                    prerequisite="<?=$card["prerequisite"]?>"
                    <?=$card["disabled"]?"disabled":""?>
                    value="<?=$card["code"]?>"><span><span name="cost"><?=$card["thisCost"]?></span> points: <?=$card["card"]?></span> 
            </label>
            <?php endif  ?>
        <?php endforeach?>
        </select>
        </div>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}

function boxUnitCardPrintHTML($platoonInBox,$boxPositionID) {
    ob_start();
    if (!empty($platoonInBox["uCard"])):?>
        <div class="optionBox">
        <button type="button" class="smallCard-btn smallCard" data-cards="<?=htmlspecialchars(json_encode(array_column($platoonInBox["uCard"],"code")??[]))?>"><img src="img/cardSmall.svg"></button>
        <?php foreach ($platoonInBox["uCard"] as $cardNr => $card):
            if (isset($card["card"])): ?>
            <label>
                <input 
                    type="checkbox" 
                    <?=($card["selected"]??false)?"checked":""?> 
                    name="<?=$boxPositionID?>uCd<?=$cardNr?>" 
                    id="<?=$boxPositionID?>box-CarduCd<?=$cardNr?>" 
                    cost="<?=$card["cost"]?>"
                    value="<?=$card["code"]?>"><?=$card["cost"]?> points: <?=$card["card"]?>
            </label>
            <?php endif  ?>
        <?php endforeach?>
        </select>
        </div>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}

function boxPrintHTML($platoonInBox,$boxPositionID) {
    ob_start();
    if (!empty($platoonInBox["uCard"])):?>
        <div class="optionBox">
        <div class="smallCard"><img src="img/cardSmall.svg"></div>
        <?php foreach ($platoonInBox["uCard"] as $cardNr => $card):
            if (isset($card["card"])): ?>
            <label>
                <input 
                    type="checkbox" 
                    <?=($card["selected"]??false)?"checked":""?> 
                    name="<?=$boxPositionID?>uCd<?=$cardNr?>" 
                    id="<?=$boxPositionID?>box-CarduCd<?=$cardNr?>" 
                    value="<?=$card["code"]?>"><?=$card["cost"]?> points: <?=$card["card"]?>
            </label>
            <?php endif  ?>
        <?php endforeach?>
        </select>
        </div>
    <?php endif;
    $html = ob_get_clean();

    return $html;
}


function generateFormationButtonsHTML($Formations, $bookTitle, $thisNation, $currentFormation, $currentPlatoon, $currentUnit, $insignia) {
    ob_start();

    $ntn = $thisNation['ntn']??$thisNation??"";
    if (isset($Formations)&&query_exists($Formations)) {
        foreach ($Formations as $row) {
            if ($row["Book"] == $bookTitle) {
                ?>
            <button type='submit' id="<?=$currentFormation?>box<?=$row["code"]?>" name='<?=$currentFormation?>' value='<?=$row["code"]?>' class='platoon <?=$ntn?>'>
            <span class='platoonImageSpan'>
                <span class='topright'>
                    <div class='MSI'>
                        <div>
                            <?=$row["motivSkillHitOn"]?>
                        </div>
                    </div>
                </span>
                <?php

                $allImages = explode("|", $row["icon"], 10);
                $firstImage = $allImages[0];
                $lastImage = $allImages[count($allImages)-1];
                ?>
                <img src='img/<?=$firstImage?>.svg'>
                <?php if ($lastImage!=$firstImage) :?>
                    <img src='img/<?=$lastImage?>.svg'>
                <?php endif ?>
                </span>
                <div class='title'>
                    <span class='left'>
                        <?= generateTitleImanges($insignia, $row["title"], $ntn)?>
                    <?php if (is_numeric(strpos($row["code"],"CC"))) {
                        ?>
                        <div class="floatingImg"><img src='img/Card.svg'></div>
                        
                        <?php
                    }
                    ?>
                    </span>
                    <span>
                        <?=$row["title"]?><br><?=$row["code"]?>
                    </span>
                </div>
            </button>
        
        <?php
            }

        }

        if ($Formations instanceof mysqli_result) {
            mysqli_data_seek($Formations ,0);
        }
    }
    $html = ob_get_clean();
    return $html;
}