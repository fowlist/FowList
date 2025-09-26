<?php
$sessionStatus = session_start();
header('Content-Type: text/html; charset=utf-8');
include "process.php";

//-----------------------------------------------------------------------------
//------------------- HTML print-----------------------------------------------
//-----------------------------------------------------------------------------
echo "<!DOCTYPE html>";
include "cssVersion.php";
include "htmlFunctions.php";

?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=0.7">

    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <script src="jquery-3.7.0.min.js"></script>
    <script src="masonry.pkgd.min.js"></script>
    <script src="index.js?v=<?=$cssVersion?>"></script>
    <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/index.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/cardNotes.css?v=<?=$cssVersion?>'>
    <title>FOW List<?=((isset($bookTitle))?" - ". $bookTitle:"") . ((isset($formationTitle[1]))?" - " . $formationTitle[1]:"") . ((isset($formationTitle[2]))?" - " . $formationTitle[2]:"")?>: army lists builder for Flames of War</title>
    <link rel="icon" type="image/x-icon" href="/img/<?=((isset($query["ntn"]))?$query["ntn"]:"Card")?>.svg">
    <script>
        const linkQuery = <?= json_encode($linkQuery); ?>;
    </script>
</head>
<body>

<?php include "menu.php";

$pdo = null;?>
<div id="toast" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background-color: #333; color: #fff; padding: 12px 24px; border-radius: 8px; opacity: 0; transition: opacity 0.5s ease, transform 0.5s ease; z-index: 9999; pointer-events: none;">
  <span id="toast-message"></span>
</div>
    <form name="form" id="form" method="get" action="index.php" autocomplete="off">
                <input hidden alt="custom name" placeholder="Name displayed at list print" name="loadedListName" id="loadedListName" value="<?php echo $query['loadedListName']??""; ?>">

    <div  id="page-container" class="page-container">
    <a href="#top">    
        <div id="backToTopButton">
            Back to Top
        </div>
    </a>
    <button type="button" id="process-link">
        <div id="viewlistOnTop">
                View List
        </div>
    </button>
    
    <?php
// -----------------------------------------------------
// ----------- Formation print -------------------------
// -----------------------------------------------------
    ?>
    <div class="header">
        <div  class="header collapsible <?=($query["ntn"]??"")?>">
        <?php if (!empty($query["Book"])) :?> 
            <div class="formHeader">
                <b><?=$bookTitle?></b>
            </div>
            
            <?php endif ?>
        </div>
        <div class="Formation">
   
            <button value='' onClick='pd.value =0; Book.value =0; ntn.value =0; this.form.submit();'><img src="img/new.svg" alt="new list" title="Clear all and start new list"></button>
            <span>
            <select name="pd" id="pd" onchange='this.form.submit()'>
                <option value='' selected disabled hidden>Select period</option>
                <?php foreach ($Periods as $period) :?>
                    <option <?=(isset($query['pd'])&&$query['pd']==$period["period"]) ? "selected='selected' ": ""?> value='<?=$period["period"]?>'><?=$period["periodLong"]?></option>
                <?php endforeach ?>
            </select>

        <?php if (!empty($query['pd'])) :?>
            <button value='' onClick="clearParameterAndSubmit('pd', this.form)"><img src="img/filter.svg" alt="clear" title="Clear period"></button></span>
            <span><?=generateDroppdownHTML('ntn','ntn', $Nations,true, "Select Nation")?>
        <?php endif ?>
        <?php if (!empty($query["ntn"])) :?>
            <button value='' onClick="clearParameterAndSubmit('ntn', this.form)"><img src="img/filter.svg" alt="clear" title="Clear Nation"></button></span>
            <span><?=generateDroppdownHTML("Book","Book", $nationBooks,true, "Select Book")?>
        <?php endif ?>
        <?php if (!empty($query["Book"])) :?>
            <button value='' onClick="clearParameterAndSubmit('Book', this.form)"><img src="img/filter.svg" alt="clear" title="Clear Book"></button></span>
        
        <br>

        <select name="dpVer" id="dpVer" onchange="this.form.submit()">
            <option value='Book' selected>Book Points</option>
            <?php foreach ($dpVersions as $dpVersion) :?>
                <option <?=(isset($query['dpVer'])&&$query['dpVer']==$dpVersion["year"]) ? "selected='selected' ": ""?> value='<?=$dpVersion["year"]?>'><?=$dpVersion["year"]?> Dynamic Points</option>
            <?php endforeach ?>
        </select>
        <select name='nOF' id='nOF' onchange='this.form.submit();'>
        <?php for ($i = 0; $i <=6; $i++): ?>

            <option <?=(isset($query['nOF'])&&($i == $query['nOF'])||((!isset($query['nOF']))&&($i==1))) ? " selected " : ""?> value=<?=$i?>>
            <?=$i?> formation<?=($i!=1)?"s":""?> from this book 
            </option>
        <?php endfor ?>
        </select>
        <select name='nOFoB' id='nOFoB' onchange='this.form.submit();'>
            <?php for ($i = 0; $i <=3; $i++): ?>
                <option <?=((($i == $query['nOFoB'])) ? " selected " : "")?>value=<?=$i?>>
                    <?=$i?> formation<?=(($i!=1)?"s":"")?> from other book
            </option>
        <?php endfor ?>
        </select>
        <?php else :?>
            <br>
            <select name="dpVer" id="dpVer" onchange="this.form.submit()">
            <option value='Book' selected>Book Points</option>
            <?php foreach ($dpVersions as $dpVersion) :?>
                <option <?=(isset($query['dpVer'])&&$query['dpVer']==$dpVersion["year"]) ? "selected='selected' ": ""?> value='<?=$dpVersion["year"]?>'><?=$dpVersion["year"]?> Dynamic Points</option>
            <?php endforeach ?>
        </select>
            </span>
        <?php endif ?>

    </div>
    </div>
    <?php 

// ------------------------------------------------------
//----------- Init from empty ---------------------------
//-------------------------------------------------------

if (!(isset($Formation_DB)&&(count($Formation_DB) > 0)&&(!($BBSupport_DB instanceof mysqli_result)&&query_exists($Formations)))){    
    
    // ------- Selection buttons for book / nation / period ----------
    if (!$bookSelected):
        if (!empty($query['ntn'])): ?>
        <div class="header collapsible">
            <div class="formHeader">No Book selected:</div>
        </div>
        <div class='Formation'>
            <?=(count($Books)> 0)?generateBookBoxes($Books,$query):"" ?>

        </div>
        <?php elseif (!empty($query['pd'])): ?>
    <div class="header collapsible">
        <div class="formHeader">
            No nation selected <?php echo $query['pd']?>
        </div>
    </div>
    <div class='Formation'>
        <div class="grid">
        <?php if (count($Nations) > 0):
            foreach ($Nations as $row):
                if ($row["period"] == $query['pd']):?>
                <div class='box'>
                    <div class='platoon'>
                        <button type='submit' name='ntn' class='<?=$row["Nation"]?> initial' value='<?=$row["Nation"]?>'>
                            <span class='nation'>
                                <img src='img/<?=$row["Nation"]?>.svg'>
                            </span>
                            <br>
                            <?=$row["Nation"]?>
                        </button> 
                    </div>
                </div>
            <?php endif;
            endforeach;
        endif;?>
        </div>
    </div>
    <?php else: ?>
        <div class="header collapsible">
            <div class="formHeader">No period selected</div>
        </div>
        <div class="Formation">
            <div class="grid">
            <?php foreach ($Periods as $row): ?>
                <div class='box'>
                    <div class='platoon'>
                        <button type='submit' name='pd' value='<?=$row["period"]?>' class='initial'>
                            <span class='nation'>
                                <img src='img/<?=$row["period"]?>.svg'>
                            </span>
                            <br>
                            <?=$row["periodLong"]?>
                        </button>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    <?php endif;
}

// ----------------------------------------------------------------
//  ------------------- Formation Print ---------------------------
// ----------------------------------------------------------------

foreach ($boxesPlatoonsData as $formationNr => $formation) {

    $alliedBooksEval = isset($formation["thisNation"])&&$formationNr>$nrOfFormationsInForce&&isset($bookTitle);
    $alliedFormationEval = $alliedBooksEval && ($query[$formation["currentFormation"] . "Book"]??false);


    if (!empty($formation["formationTitle"])||$alliedBooksEval) {
    // -----------This Formation Title print --------------------
    ?>

    <div id="F<?=$formationNr?>" class="header collapsible <?=$formation["thisNation"]??""?>">
        <div class="formHeader">
            <?php if ((!empty($formation["formCard"]["title"])||(is_numeric(strpos($formation["formationCode"]??"","C"))))) :?>
                <img class='card' src='img/Card.svg'>
                <?php endif ?>
            <?=generateTitleImanges($insignia, ($formation["formCard"]["title"]??"") . ($formation["formationTitle"]??""), $formation["thisNation"]) ?> 

        
            <b>
                <?=$alliedBooksEval?$formation["thisNation"] . " ally:":""?>
                <?=isset($formation["formCard"])&&!empty($formation["formCard"]["title"])&&$formation["formationTitle"] !=$formation["formCard"]["title"]?trim($formation["formCard"]["title"]).": ":""?>
                <?=$formation["formationTitle"]??(!$alliedFormationEval?"Select Book:":"Select Formation:")?>
            </b>

        </div>
        
        <div class="Points">
        <?php if ( $formationNr == $nrOfFormationsInForce +$query['nOFoB']): ?>
            <button type="button" onclick="decreaseNOF('<?=($query['nOFoB']>0)?'nOFoB':'nOF'?>')"><img src="img/clear.svg" alt="Remove" title="Remove this formation from force"></button>
        <?php endif ?>
            <div>
            <?=$formation["formCost"]?> Point<?=$formation["formCost"]>1?"s":""?>
            </div>
        </div>
    </div>
    <?php }

    // -----------------------------------------------------

if (isset($formation["boxes"])) {
    ?>
    <div class="Formation" id="<?=$formation["currentFormation"]?>box">
        <div class="formConfig">


<?php if ($formationNr>$nrOfFormationsInForce) :?>
        <div>
    <?=generateDroppdownHTML($formation["currentFormation"] . "Book", $formation["currentFormation"] . "Book",  $formation["books"],true)?>
        </div>
<?php endif ?>
    
<div>
    Switch formation: 
        <select formSelect name="<?=$formation["currentFormation"]?>" id="<?=$formation["currentFormation"]?>" onchange='this.form.submit()'>
            <option value='' selected>Select Formation</option>
            <?php foreach ($formation["thisFormationList"] as $option) :?>
                <option <?=(isset($option["selected"])&&$option["selected"]==1) ? "selected='selected' ": ""?> value='<?=$option["value"]?>'><?=$option["description"]?></option>
            <?php endforeach ?>
        </select>
    <button value='' name="F<?=$formationNr?>" onClick="clearParameterAndSubmit('<?=$formation["currentFormation"]?>', this.form)"><img src="img/filter.svg" alt="clear" title="Clear Formation"></button>
    
</div>

<?php if (isset($formation["formationCard"])) :?>
    <div>
    <img src='img/cardSmall.svg'> Formation card:
        <select fCardSelect name="<?=$formationNr?>-Card" id="<?=$formationNr?>-Card" onchange='this.form.submit()'>
            <option value='' selected>Select Card</option>
            <?php foreach ($formation["formationCard"] as $option) :?>
                <option <?=(isset($option["selected"])&&$option["selected"]==1) ? "selected='selected' ": ""?> value='<?=$option["value"]?>'><?=$option["description"]?></option>
            <?php endforeach ?>
        </select>
    </div>


<?php endif ?>
        </div>

<?php if (isset($formation["formCard"])): ?>

    <?=cardNoteParse($formation["formCard"],false)?>
<?php endif ?>
    <div class="grid">
    <?php foreach ($formation["boxes"]??[] as $boxNr => $boxRow) {
        $boxPositionID = "{$formation["currentFormation"]}-{$boxNr}";
        $first = true;
    ?>
        <div id='<?=$boxPositionID?>box' class='box'><b><?=$boxRow["thisBoxType"]?></b>
        <?php foreach ($boxRow??[] as $key => $platoonInBox) {
            if (isset($platoonInBox["title"])) { 
                ?>
            <div class="<?=(($platoonInBox["BlackBox"]??false)||trim($boxRow["thisBoxType"])=="Headquarters")?"blackbox":"platoon"?>">
                <?php if ($first&&$boxRow["codes"]??false) :
                    if (count($boxRow["codes"]) <= 1 &&!is_numeric(strpos($boxRow["codes"][0],"C")) ||count($boxRow["codes"]) > 1) :?>
                        <button type="button" class="info-btn" data-codes="<?=htmlspecialchars(json_encode($boxRow["codes"]??""))?>">i</button>
                        <?php $first = false; ?>
                    <?php endif ?>
                <?php endif ?>
                <input 
                    type="checkbox" 
                    <?=($platoonInBox["selected"]??false)?"checked":""?> 
                    id="<?=$boxPositionID?>box<?=$platoonInBox["platoon"]?>" 
                    name="<?=$boxPositionID?>"
                    platoonCheckbox
                    value="<?=$platoonInBox["platoon"]?>"
                    data-platoonInfo="<?=htmlspecialchars(json_encode(
                        ["platoon" => $platoonInBox["platoon"]??false,
                        "title" => $platoonInBox["title"]??false,
                        "teams" => $platoonInBox["teams"]??false,
                        "unitType" => $platoonInBox["unitType"]??false,
                        "box_type" => $platoonInBox["box_type"]??false,
                        "box_nr" => $platoonInBox["box_nr"]??false,
                        "formation" => $platoonInBox["formation"]??false,
                        "nation" => $formation["thisNation"]??$query["ntn"],
                        "forceNation" => $query["ntn"]??false,
                        "book" => $bookTitle??false,
                        "boxPositionID" => $boxPositionID??false,
                        "formCard" => $formation["formCard"]["code"]??false,
                        "formCardTitle" => $formation["formCard"]["code"]??false,
                        "cardNr" => $platoonInBox["cardNr"]??false,
                        "dynamic" => $query["dpVer"]??false,
                        "pd" => $query["pd"]??false,
                        "currentFormation" => "formation",
                            "prerequisite" => $platoonInBox["prerequisite"]??""
                        ]??[]))?>">
                <label for="<?=$boxPositionID?>box<?=$platoonInBox["platoon"]?>">
                    <span class='platoonImageSpan'>
                    
                    <?php foreach ($platoonInBox["images"] as $key => $image) :?><img src="img/<?=$image?>.svg"><?php endforeach ?>
                    </span>
                    <?=platoonTitle($platoonInBox,$formation)?>
                </label>

                <div class="selectedPlatoon <?=!empty($platoonInBox["selected"])?"selected":""?>" currentNrOfTeams="<?=$platoonInBox["boxSections"]["total"]??""?>" lastPrice="<?=$boxRow["boxCost"]?>" >
                <?php if (!empty($platoonInBox["selected"])): ?>
                    <?=platoonConfigHTML($platoonInBox,$boxPositionID)?>
                    <?=boxOptionPrintHTML($platoonInBox,$boxPositionID)?>
                    <?=boxformCardPrintHTML($platoonInBox)?>
                    <?=boxPlatoonCardPrintHTML($platoonInBox,$boxPositionID)?>
                    <?=boxUnitCardPrintHTML($platoonInBox,$boxPositionID)?>
                    <div class="Points">
                        <div>
                        <?=$boxRow["boxCost"]?> Point<?=$boxRow["boxCost"]>1?"s":""?>
                        </div>
                    </div>
                <?php endif ?>
                </div>
                <?php if (empty($platoonInBox["selected"])): ?>
                    <?=isset($platoonInBox["prerequisite"])&&$platoonInBox["prerequisite"]!=""?"<b>Prerequisites:</b> <br>":""?><?=str_replace(",",", ",$platoonInBox["prerequisite"]??"")?>
                <?php endif ?>
    </div><?php
        } 
    } 
        ?></div><?php
    }
    ?>
        </div>
    </div>
    <?php } elseif (!empty($formation["book"])) { ?>
        <div  id="F<?=$formationNr?>" class='header collapsible <?=$formation["thisNation"]??""?>'>
            <div class="formHeader">No formation selected: </div>
            <?php if ( $formationNr == $nrOfFormationsInForce +$query['nOFoB']): ?>
                <button type="button" onclick="decreaseNOF('<?=($query['nOFoB']>0)?'nOFoB':'nOF'?>')"><img src="img/clear.svg" alt="Remove" title="Remove this formation from force"></button>
            <?php endif ?>
        </div>
        <div class='Formation'>

            <div class='grid'>
                <?= generateFormationButtonsHTML($formation["thisFormationList"], $formation["book"], $formation["thisNation"], $formation["currentFormation"], isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia)?>
            </div>
        </div>
    <?php } elseif ($alliedFormationEval) { ?>
    <div class="Formation">
        <div class='Formation'>
            <?=generateDroppdownHTML($formation["currentFormation"] . "Book", $formation["currentFormation"] . "Book",  $formation["books"],false)?>
            <?=generateDroppdownHTML($formation["currentFormation"], $formation["currentFormation"],  $formation["thisFormationList"],false,"Formation")?>

            <div class='grid'>

                <?= generateFormationButtonsHTML($formation["thisFormationList"], $formation["BookTitle"], $formation["thisNation"], $formation["currentFormation"], isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia)?>
            </div>
        </div>
    </div>
    <?php } elseif ($alliedBooksEval) { ?>

    <div class="Formation">
        <?=generateDroppdownHTML($formation["currentFormation"] . "Book", $formation["currentFormation"] . "Book",  $formation["books"],false,"Select Allied Book")?>

        <?=generateBookBoxes($formation["books"],$query,$formation["currentFormation"] . "Book")?>

    </div>
    <?php
        
        //echo generateFormationButtonsHTML($formation["books"], $boxesPlatoonsData[$formationNr]["listBook"], $boxesPlatoonsData[$formationNr]["thisNation"], $boxesPlatoonsData[$formationNr]["currentFormation"], isset($currentPlatoon)?$currentPlatoon:"", isset($currentUnit)?$currentUnit:"", $insignia);
    }

    if ( $formationNr == $nrOfFormationsInForce ): ?>
        <div  id="F<?=$formationNr?>" class='header collapsible <?=$formation["thisNation"]??""?>'>
            <div class="formHeader"><b>Aditional formation</b></div>
        </div>
        <div class="Formation">
            <button type="button" name="F<?=($nrOfFormationsInForce+1)?>" class="addFormButton" onclick="incrementNOF('nOF')">Add <?=$nrOfFormationsInForce>0?"one aditional ":"" ?>formation from <?=$bookTitle?></button>
            <button type="button" name="F<?=($nrOfFormationsInForce+1)?>" class="addFormButton" onclick="incrementNOF('nOFoB')">Add <?=$query['nOFoB']>0?"one aditional ":"" ?>formation from other book</button>
        </div>
    <?php endif;
}

//-------------------------------------------------------------------
//--------------------- Force Cards print ---------------------------
//-------------------------------------------------------------------
if (!empty($listCards)) {
?>
<div class="header collapsible <?=$formation["thisNation"]??""?>">
    <div class="formHeader">
        <?=generateTitleImanges($insignia, "", $formation["thisNation"]??"") ?> 
        <b><?=$bookTitle?> Command Cards</b>
    </div>
    <div class="Points">
        <div>
        <?=$listCardCost?> Point<?=$listCardCost>1?"s":""?>
        </div>
    </div>
</div>
<div class='Formation'>

    <div class='grid'>

<?php foreach ($listCards as $cardKey => $eachCard): ?>
    <div id='fCd-<?=$cardKey?>box' class='box'>
        <div class="forceCard" >
        <input 
            <?=($eachCard["checked"])?" checked ":""?> 
            type='checkbox' 
            id='fCd-<?=$cardKey?>box1' 
            name='fCd-<?=$cardKey?>'
            fCardCheckbox
            class='fCd' 
            value='<?=$eachCard["code"]?>'                    
            data-cardInfo="<?=htmlspecialchars(json_encode(
                        ["code" => $eachCard["code"]??false,
                        "numbers" => $eachCard["numbers"]??1
                        ]??[]))?>">
        <label for='fCd-<?=$cardKey?>box1'>
            <img src='img/Card.svg'>
            
            <span class="cardTitle"><?=$eachCard["card"]?>
            
            <?php if (!$eachCard["checked"]): ?>

                <span class="cardPoints" >(<?=$eachCard["cost"]?> point<?=$eachCard["cost"]>1?"s":""?>)</span>

            <?php endif ?>
            </span>
            <button type="button" class="info-btn" data-cards="<?=htmlspecialchars(json_encode([$eachCard["code"]]??[]))?>">i</button>
        </label>
        <div class="selectedCard <?=!empty($eachCard["checked"])?"selected":""?>"  lastPrice="<?=!empty($eachCard["checked"])?$eachCard["totalPrice"]??$eachCard["cost"]:""?>" >
    <?php if ($eachCard["checked"]): ?>
        <?=cardConfigHTML($eachCard,$eachCard["numbers"]??1) ?>
    <?php endif ?>
    </div>
        </div>

    </div>
<?php endforeach ?>
    </div>
</div>
<?php
}


//-------------------------------------------------------------------
//--------------------- Support print -------------------------------
//-------------------------------------------------------------------
if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)){


foreach ($supportBoxesPlatoonsData as $formationNr => $formation) {
    if (isset($formation["boxes"])) {
    ?>
    <div class="header collapsible <?=$formation["thisNation"]??""?>">
        <div class="formHeader">
            <?=generateTitleImanges($insignia, "", $formation["thisNation"]) ?> 
            <b><?=$bookTitle?><?=$formation["currentFormation"]=="CdPl"?" Command Card":""?> Support</b>
    </div>
        <div class="Points">
            <div>
            <?=$formation["formCost"]?> Point<?=$formation["formCost"]>1?"s":""?>
            </div>
        </div>
    </div>

        <div class="Formation">
        <div class="grid">
        <?php foreach ($formation["boxes"]??[] as $boxNr => $boxRow) {
            $first = true;
            $boxPositionID = "{$formation["currentFormation"]}-{$boxNr}";
        ?>
            <div id='<?=$boxPositionID?>box' class='box'><b><?=implode(", ",array_unique(explode("|",$boxRow["thisBoxType"])))?></b>
            <?php foreach ($boxRow??[] as $key => $platoonInBox) {
                if (isset($platoonInBox["title"])) { ?>
                <div class="platoon">
                    <?php if ($first&&isset($boxRow["codes"])) :?>
                        <button type="button" class="info-btn" data-codes="<?=htmlspecialchars(json_encode($boxRow["codes"]??""))?>">i</button>
                        <?php $first = false; ?>
                    <?php endif ?>
                
                    <input 
                        type="checkbox" 
                        <?=($platoonInBox["selected"]??false)?"checked":""?> 
                        id="<?=$boxPositionID?>box<?=$platoonInBox["platoon"]?>" 
                        name="<?=$boxPositionID?>"
                        platoonCheckbox
                        value="<?=$platoonInBox["platoon"]?>"
                        data-platoonInfo="<?=htmlspecialchars(json_encode(
                            ["platoon" => $platoonInBox["platoon"]??false,
                            "title" => $platoonInBox["title"]??false,
                            "teams" => $platoonInBox["teams"]??false,
                            "unitType" => $platoonInBox["unitType"]??false,
                            "box_type" => $platoonInBox["box_type"]??false,
                            "box_nr" => $platoonInBox["box_nr"]??false,
                            "formation" => $platoonInBox["formation"]??false,
                            "nation" => $formation["thisNation"]??$query["ntn"],
                            "forceNation" => $query["ntn"]??false,
                            "book" => $bookTitle??false,
                            "boxPositionID" => $boxPositionID??false,
                            "formCard" => $formation["formCard"]["code"]??false,
                            "cardNr" => $platoonInBox["cardNr"]??false,
                            "dynamic" => $query["dpVer"]??false,
                            "pd" => $query["pd"]??false,
                            "currentFormation" => $formation["currentFormation"],
                            "prerequisite" => $platoonInBox["prerequisite"]??""
                            ]??[]))?>">
                    <label for="<?=$boxPositionID?>box<?=$platoonInBox["platoon"]?>">
                        <span class='platoonImageSpan'>
                        <?php foreach ($platoonInBox["images"] as $key => $image) :?><img src="img/<?=$image?>.svg"><?php endforeach ?>
                        </span>
                        <?=platoonTitle($platoonInBox,$formation)?>
                        
                    </label>
                    
                    <div class="selectedPlatoon <?=!empty($platoonInBox["selected"])?"selected":""?>" currentNrOfTeams="<?=$platoonInBox["boxSections"]["total"]??""?>" lastPrice="<?=$boxRow["boxCost"]?>" >
                        <?php if (!empty($platoonInBox["selected"])): ?>
                        <?=platoonConfigHTML($platoonInBox,$boxPositionID)?>
                        <?=boxOptionPrintHTML($platoonInBox,$boxPositionID)?>
                        <?=boxformCardPrintHTML($platoonInBox)?>
                        <?=boxPlatoonCardPrintHTML($platoonInBox,$boxPositionID)?>
                        <?=boxUnitCardPrintHTML($platoonInBox,$boxPositionID)?>
                        <div class="Points">
                            <div>
                            <?=$boxRow["boxCost"]?> Point<?=$boxRow["boxCost"]>1?"s":""?>
                            </div>
                        </div>
                        <?php endif ?>
                    </div>
                    <?php if (empty($platoonInBox["selected"])): ?>
                    <?=isset($platoonInBox["prerequisite"])&&$platoonInBox["prerequisite"]!=""?"<b>prerequisites:</b> <br>":""?><?=str_replace(",",", ",$platoonInBox["prerequisite"]??"")?>
                    <?php endif ?>
        </div><?php
            } 
        } 
            ?></div><?php
        }
    ?>  </div>
        </div>
        <?php
    }
}
}

//-------------------------------------------------------------------
//--------------------- Formation support print ---------------------
//-------------------------------------------------------------------
if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)) {
    $outerBoxnumber = 0;
foreach ($formSupBoxesPlatoonsData as $unique_type => $type_boxes) {
    ?>
    <div class="header collapsible <?=$formation["thisNation"]??""?>">

        <div class="formHeader">
            <b>Formation Support: <?=$unique_type?></b>
</div>
        <div class="Points">
            <div>
            <?=$type_boxes["formCost"]?> Point<?=$type_boxes["formCost"]>1?"s":""?>
            </div>
        </div>
    </div>
    <?php

    if (isset($type_boxes["boxes"])) {
        
        ?>
        <div class="Formation">
        <div class="grid">
        <?php foreach ($type_boxes["boxes"]??[] as $boxNr => $boxRow) {
            $outerBoxnumber++;
            $tempTitle = str_replace(
                 [" with"," Panzerfaust", " team"," half-track"," Half-track"," anti-tank","Flame-thrower"," (ROF2)", " mortar","inch"],
                [" w"," Pf.","",".H-t.",".H-t.","","f.t.","",".MB","in"], 
                explode("|",$boxNr));
        ?>                
            <div id='outer<?=$outerBoxnumber?>box' teams="<?=$boxNr?>" class='box'>
                <b><?=$tempTitle[0]??""?><?=($tempTitle[1]??false)?", {$tempTitle[1]}":""?><?=($tempTitle[2]??false)?", {$tempTitle[2]}":""?><?=($tempTitle[3]??false)?", {$tempTitle[3]}":""?></b>
            <?php 
            $first = true;

            foreach ($boxRow??[] as $key => $platoonInBox) {
                
                if (isset($platoonInBox["title"])) {
                    $boxPositionID = "{$platoonInBox["platoon"]}box";
                    if ($first) {
                    if (count($boxRow["codes"])==1) : ?>
                        <div class="platoon <?=$platoonInBox["Nation"]?>" id="<?=$boxPositionID?>">
                    <?php else : ?>
                        <div class="platoon">
                    <?php endif ?>
                        <label for="<?=$boxPositionID?>1">
                        <span class='platoonImageSpan'>
                    <?php if ($boxRow["codes"]??false) :?>
                        <button type="button" class="info-btn" data-codes="<?=htmlspecialchars(json_encode($boxRow["codes"]??""))?>">i</button>
                    <?php endif ?>
                    <?php foreach ($platoonInBox["images"] as $key => $image) :?>
                        <img src="img/<?=$image?>.svg">
                    <?php endforeach;
                        $first=false; ?>
                        </span>
                        </label>
                        <?php if (count($boxRow["codes"])>1) : ?>
                            </div>
                        <?php endif;
                    }
                    
                    if (isset($platoonInBox["title"])) { 
                        $platoonInBox["title"] = $platoonInBox["booksForPlatoon"] . ": ". $platoonInBox["title"];
                        if (count($boxRow["codes"])>1) : ?>
                            <div class="platoon <?=$platoonInBox["Nation"]?>" id="<?=$boxPositionID?>">
                        <?php endif ?>
                        <input 
                            type="checkbox" 
                            <?=($platoonInBox["selected"]??false)?"checked":""?> 
                            id="<?=$boxPositionID?>1" 
                            name="<?=$boxPositionID?>" 
                            platoonCheckbox
                            <?=($platoonInBox["Nation"] != $query["ntn"])?"ally":""?>
                            value="<?=$platoonInBox["platoon"]?>"
                            data-platoonInfo="<?=htmlspecialchars(json_encode(
                                ["platoon" => $platoonInBox["platoon"]??false,
                                "title" => $platoonInBox["title"]??false,
                                "teams" => $platoonInBox["teams"]??false,
                                "unitType" => $platoonInBox["unitType"]??false,
                                "box_type" => $platoonInBox["box_type"]??false,
                                "box_nr" => $platoonInBox["box_nr"]??false,
                                "formation" => $platoonInBox["formation"]??false,
                                "nation" => $platoonInBox["Nation"]??false,
                                "forceNation" => $query["ntn"]??false,
                                "book" => $platoonInBox["Book"]??false,
                                "boxPositionID" => $boxPositionID??false,
                                "formCard" => $formation["formCard"]["code"]??false,
                                "cardNr" => $platoonInBox["cardNr"]??false,
                                "dynamic" => $query["dpVer"]??false,
                                "pd" => $query["pd"]??false,
                                "currentFormation" => $type_boxes["currentFormation"]??false
                                ]??[]))?>">
                        <label for="<?=$boxPositionID?>1">
                            <?=platoonTitle($platoonInBox,$type_boxes)?>
                        </label>
                        <div class="selectedPlatoon <?=!empty($platoonInBox["selected"])?"selected":""?>" currentNrOfTeams="<?=$platoonInBox["boxSections"]["total"]??""?>" lastPrice="<?=$boxRow["boxCost"]?>" >
                            <?php if (!empty($platoonInBox["selected"])): ?>
                            <?=platoonConfigHTML($platoonInBox,$boxPositionID)?>
                            <?=boxOptionPrintHTML($platoonInBox,$boxPositionID)?>
                            <?=boxformCardPrintHTML($platoonInBox)?>
                            <?=boxPlatoonCardPrintHTML($platoonInBox,$boxPositionID)?>
                            <?=boxUnitCardPrintHTML($platoonInBox,$boxPositionID)?>
                            <div class="Points">
                                <div>
                                <?=$boxRow["boxCost"]?> Point<?=$boxRow["boxCost"]>1?"s":""?>
                                </div>
                            </div>
                            <?php endif ?>
                        </div>
                    </div><?php
                }
            }
        } 
            ?></div><?php
        }
    ?>  </div>
        </div>
        <?php
    }
}

}

if (isset($boxCost)){$_SESSION["lastPage"] = $_SERVER['PHP_SELF'];}

?>


</div>
<!-- Info Overlay -->
<div id="infoOverlay" class="hidden">
    <div id="infoContent">
        <button type="button"  id="closeOverlay">Close</button>
        <table id="platoonDetails"></table>
    </div>
</div>
</form>
</body>
</html>
