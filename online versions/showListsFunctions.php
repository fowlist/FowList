<?php

function generateListArray($results, $conn) {
    $Books = $conn->query("
    SELECT  * 
    FROM    nationBooks");
    $sqlTempStatement = "";
    $first = true;
    $listArray =[];
    foreach ($results as $listKey => $row) {
        $parts = parse_url($row['url']);
        parse_str($parts['query'], $query);
        $listArray[$listKey] = $row;
        $listArray[$listKey]["BookName"] = "";
        $listArray[$listKey]['query'] = $query;
        $platoons =[];
        $boxes =[];
        foreach ($Books as $bookKey => $value) {
            if ($value['code']==$query["Book"]) {
                $listArray[$listKey]["BookName"] = $value["Book"];
                $listArray[$listKey]["pd"] = $value["periodLong"];
                $temp = strtok($value["Book"],":");
                $listArray[$listKey]["ShortBookName"] = strpos($temp,"Force")?substr($temp,0,strpos($temp,"Force")):$temp;  
            }
        }
        $listArray[$listKey]["urlNew"] = $row['url']."&loadedListName=" . trim($row['name']) . ($row["tournament"]=="0"||$row["tournament"]=="none"?"":":" . trim($row["tournament"]));
        $listArray[$listKey]["urlToListNew"] = $row['urlToList']."&loadedListName=" . trim($row['name']) . ($row["tournament"]=="0"||$row["tournament"]=="none"?"":":" . trim($row["tournament"]));
        $listArray[$listKey]["urlToListNewBF"] = str_replace("listPrintGet.php","listPrintGetBFStyle.php",$listArray[$listKey]["urlToListNew"] );
        for ($formations=0; $formations < 6; $formations++) { 
            if (!empty($query["F{$formations}"])) {
                $sqlTempStatement .= (!$first?" OR ":"") . "code LIKE '". $query["F{$formations}"] . "'";
                $listArray[$listKey]["FormationCode"][] = $query["F{$formations}"];
                $first = False;
            }
        }
        foreach ($query as $key => $value) {

            if ((strlen($value)==5&&is_numeric(substr($value,2,3)))|| (is_numeric(strpos($value,"CC"))&& is_numeric(strpos(" {$key}","F")) && strlen($value)==7 && is_numeric(substr($value,4,3)))) {
                $boxes[$key] = $value;
                if (!in_array($value, $platoons)) {
                    $platoons[] = $value;
                }
                
            }
        }
        $listArray[$listKey]['platoons'] = $platoons;
        $listArray[$listKey]['boxes'] = $boxes;

    }
    $Formations = $conn->query(
        "SELECT  * 
         FROM    formations 
         WHERE   formations.title NOT LIKE '%Support%'
         AND {$sqlTempStatement}");
         
    foreach ($listArray as $key => $row) {
        foreach ($row["FormationCode"] as $formationCodes) {
            foreach ($Formations as $value) {
                if ($formationCodes == $value["code"]) {
                    $listArray[$key]["Formation"][] = $value;
                }
            }
        }
    }
    return $listArray;
}

function generateShowListRows($listArray) {
    ob_start();

    foreach ($listArray as $key => $row) {
        ?>
<tr id="<?=$row['id']?>" data-codes="<?=htmlspecialchars(json_encode($row['platoons']))?>" data-boxes="<?=htmlspecialchars(json_encode($row['boxes']??""))?>" data-formations="<?=htmlspecialchars(json_encode($row["Formation"]??""))?>">
    <td class="row-header" data-skip-filter>
        <div class="mobile-controls">
            <input type="checkbox" class="row-checkbox" name="selectedIds[]" value="<?=$row['id']?>">
            <div class="mobileRowControlls">
                <button class="save-btn" style="display: none;" data-id="<?=$row['id']?>">Save</button>
                <img class="period insignia" src="img/<?= $row['query']["pd"] ?>.svg" alt="">
                <img class="insignia" src="img/<?= $row['query']["ntn"] ?>.svg" alt="">
                <div class="bookName"><?= $row["ShortBookName"] ?></div>
                <button class="edit-button" data-id="<?=$row['id']?>">
                    <img class='buttonImage' src="img/inspectList.svg" alt="Edit Metadata/Inspect List" title="Edit Metadata/Inspect List">
                </button>
                <a class="set-list-number" href="<?=$row['urlNew']?>" data-id="<?=$row['id']?>"><img class='buttonImage' src="img/editList.svg" alt="Edit List" title="Edit List"></a>
                <?php if (!empty($row["urlToList"])) { ?> 
                    <a href="<?=$row['urlToListNew']?>" class='set-list-number' data-id="<?=$row['id']?>"><img class='buttonImage' src='img/viewList.svg' alt='View List' title='View List'></a>
                    <a href="<?=$row['urlToListNewBF']?>" class='set-list-number' data-id="<?=$row['id']?>"><img class='buttonImage' src='img/viewListBF.svg' alt='View List Bf style' title='View List Bf style'></a>
                <?php } ?>
            </div>
        </div>
    </td>
    <td data-skip-filter id="checkbox">
        <input type="checkbox" class="row-checkbox" name="selectedIds[]" value="<?=$row['id']?>">
        <button class="save-btn" style="display: none;" data-id="<?=$row['id']?>">Save</button>
    </td>
    <td data-label="Period/Nation" id="period">
        <img class="period insignia" src="img/<?= $row['query']["pd"] ?>.svg" alt="">
        <div style="display:none;"><?= $row["pd"] ?></div>
        <?= $row['query']["ntn"] ?>
    </td>
    <td data-label="Book/Front" id="Book">
        <img class="insignia" src="img/<?= $row['query']["ntn"] ?>.svg" alt="">
        <?= $row["ShortBookName"] ?>
    </td>
    <td data-label="Formation (points)" id="formation">
        <div class="MSI">
            <div>
                <?= $row["Formation"][0]["motivSkillHitOn"] ?>
            </div>
        </div>
        <a href="<?= $row['urlNew'] ?>" data-points="<?= $row['cost'] ?>"><?= $row["Formation"][0]["title"] ?></a>
    </td>
    <td data-label="List Name" id="name-<?=$row['id']?>">
        <span class="rowControlls">
            <button class="edit-button" data-id="<?=$row['id']?>">
                <img  class='buttonImage'  src="img/inspectList.svg" alt="Edit Metadata/Inspect List" title="Edit Metadata/Inspect List">
            </button>
            <a class="set-list-number" href="<?=$row['urlNew']?>" data-id="<?=$row['id']?>"><img class='buttonImage' src="img/editList.svg" alt="Edit List" title="Edit List"></a>
            <?php if (!empty($row["urlToList"])) { ?> 
                <a href="<?=$row['urlToListNew']?>" class='set-list-number' data-id="<?=$row['id']?>"><img class='buttonImage' src='img/viewList.svg' alt='View List' title='View List'></a>
                <a href="<?=$row['urlToListNewBF']?>" class='set-list-number' data-id="<?=$row['id']?>"><img class='buttonImage' src='img/viewListBF.svg' alt='View List Bf style' title='View List Bf style'></a>
            <?php } ?>
        </span>
        <span contenteditable="true" class="nameField"><?= trim($row['name']) ?></span>
    </td>
    <td data-label="Points" id="points">
        <?= $row['cost'] ?>
    </td>
    <td data-label="Save date" id="saveDate">
        <?= (!empty($row["saveDate"]) && $row["saveDate"] != "0000-00-00") ? $row["saveDate"] : " " ?>
    </td>
    <td data-label="Event" id="event-<?=$row['id']?>" contenteditable="true"><?= $row['tournament'] != "0" ? trim($row['tournament']) : " "?></td>
</tr>
    <?php
    }

    $html = ob_get_clean();

    return $html;
}
