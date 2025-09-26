<?php  header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start the session
session_start();

include "functions.php";
include "sqlServerinfo.php";
include "login.php";
include "cssVersion.php";

// -- all general tables 
    $rules = $conn->query(
       "SELECT  *
        FROM    rules ");   

    $insigniaResult = $conn->query(
        "SELECT  * 
    FROM    insignia
         ORDER BY autonr");
    $insignia = [];
    foreach ($insigniaResult as $value) {
        $insignia[]=$value;
    }
    $insigniaResult -> free();

    // 2. Pagination setup
$limit = 100; // rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 3. Count total rows
$totalResult = $conn->query("SELECT COUNT(*) as total FROM team_platoon_formation_stats")->fetch_assoc();
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

$result = $conn->query("SELECT DISTINCT team FROM team_platoon_formation_stats ORDER BY team");

// Read team from GET
$team = isset($_GET['team']) ? $_GET['team'] : '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=0.5">
    <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/listPrnt.css?v=<?=$cssVersion?>'>
    <script src="listPrint.js?v=<?=$cssVersion?>"></script>

    <?php
    // Menu
    if (isset($query['F1'])) {
        $firstFormationTitle = $query['F1'];
    } else {
        $firstFormationTitle = "No formation";
    }
    ?>


    <title>FOW List all <?=$team ?>teams </title>
    <link rel="icon" type="image/x-icon" href="/img/<?=$query["ntn"]?>.svg">
</head>
<body>


<?php include "menu.php"; ?>

    <div id="page-container" class="page-container">
<?php        
//------------------------------------
// ------- soft stats ----------
//------------------------------------
?>
    <div class='break-inside-avoid'>
        <form method="get" action="list_all_teams.php">
    <select name="team" required onchange="this.form.submit()">
        <option value="">-- Select Team --</option>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <option value="<?= htmlspecialchars($row['team']) ?>">
                <?= htmlspecialchars($row['team']) ?>
            </option>
        <?php } ?>
    </select>

</form>
<?php
    if (!$team) { die("No team selected."); }

// Fetch platoons for this team
$stmt = $conn->prepare("SELECT *
                        FROM team_platoon_formation_stats
                        WHERE team = ?
                        ORDER BY platoon_title");
$stmt->bind_param("s", $team);
$stmt->execute();
$platoonsInForceQuery = $stmt->get_result();
$formationsInForce = [];
$platoonsInForce = [];

    foreach ($platoonsInForceQuery as $key => $value) {

        $platoonsInForce[$value["code"]] = $value;
        if (isset($formationsInForce[$value["code"]])) {
            if (!is_numeric(strrpos(strtoupper($formationsInForce[$value["code"]]), strtoupper($value["formation_info"])))) {
                $formationsInForce[$value["code"]] = $formationsInForce[$value["code"]] . "|" . $value["formation_info"];
            }        
        } else {
            $formationsInForce[$value["code"]] = $value["formation_info"];
        }

        $platoonsInForceWeapon[$value["team"]][$value["weapon"]] = $value;
    }
$rulesInForce=[];
?>
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Platoons <?=$team?> (page <?=$page?>)
        </div>
    </div>

    <?php

if (isset($platoonsInForce)&&count($platoonsInForce) > 0) {// ------- soft stats ----------
    ?>
        <table class='arsenal break-inside-avoid'>
        <THEAD>
            <tr><th class='German' rowspan='2'>Image</th><th class='German'>TACT.</th><th class='German'>TERR. DASH</th><th class='German'>CROSS C. DASH</th><th class='German'>ROAD DASH</th><th class='German'>CROSS</th><th class='German' rowspan='2'>MOTIVATION </th><th class='German' rowspan='2'> SKILL</th><th class='German' rowspan='2'>ARMOUR/SAVE</th></tr>
        </THEAD>
        <TBODY>

    <?php
    foreach ($platoonsInForce as $platoonSoftStatRow) { 
        if (($platoonSoftStatRow["TACTICAL"]!="")) {
                    foreach ($rules as $row3) {
                        if (isset($platoonSoftStatRow["platoon_keywords"])&&$platoonSoftStatRow["platoon_keywords"] != ""&&isset($row3["name"])&&is_numeric(strrpos(strtoupper($platoonSoftStatRow["platoon_keywords"]), strtoupper($row3["name"]),-1))) {
                            foreach (explode(",",$platoonSoftStatRow["platoon_keywords"]) as $eachKeyword) {
                                if (trim($row3["name"]) == trim($eachKeyword)) {
                                    $rulesInForce[] =  $row3["name"];
                                }
                            }
                            
                        }
                    }         
            ?>
            
            <tr class='break-inside-avoid'>
                <td class='imagerow break-inside-avoid' rowspan='2'  style='max-width: 160px;'>
                <?php
                if (isset($platoonSoftStatRow["team_image"])) {
                    foreach (explode("|",$platoonSoftStatRow["team_image"] ,7) as $key1 => $boxImage) 
                    echo  "<img src='img/{$boxImage}.svg'>";
                }
                ?>
                <br>
                <?=$platoonSoftStatRow["code"]?>
                </td>
                <td class='statsrow' colspan='5' style='text-align: left;'>
            <b><span class='left nation'><?=generateTitleImanges($insignia,  (($platoonSoftStatRow["platoon_title"]=="")? $platoonSoftStatRow["platoon_title"] : $platoonSoftStatRow["platoon_title"] ) , (isset($platoonSoftStatRow["Nation"]))?$platoonSoftStatRow["Nation"]:"") ."</span><span>".(($platoonSoftStatRow["platoon_title"]=="")? $platoonSoftStatRow["platoon_title"] : $platoonSoftStatRow["platoon_title"] )?> </span></b><br>
                <?=$platoonSoftStatRow["platoon_keywords"]?>
                <br>
                <?=$platoonSoftStatRow["team"]?>

            </td>
            <td class='statsrow' rowspan='2'>
                <?php
//------------------ Motivation ------------------------
            $platoonMotivation = processPlatoonAttribute("motivation", $platoonSoftStatRow, $platoonSoftStatRow, []);
            echo motivationBox($platoonMotivation);
            echo "</td>";

//---------------------Skill ------------------------------
            echo "<td class='statsrow' rowspan='2'>";
            $platoonSkill = processPlatoonAttribute("skill", $platoonSoftStatRow, $platoonSoftStatRow, []);
            echo motivationBox($platoonSkill);
            echo "</td>";
//-------------------Save ------------------------------------

            echo "<td class='statsrow' rowspan='2'>";
            

                    $platoonIsHitOn = $platoonSoftStatRow["IS_HIT_ON"];
                
                echo motivationBox($platoonIsHitOn);


                    $platoonSave = $platoonSoftStatRow;
                    $platoonSave["statCardChangeIcon"] = "";
                

                echo  saveBox($platoonSave["ARMOUR_SAVE"],$platoonSave["statCardChangeIcon"]) . "    
            </td>
        </tr>";

//--------------------Speed --------------------------------------

          

                $smaller = ($platoonSoftStatRow["TACTICAL"]=="UNLIMITED")?" style='font-size: x-small;' ":"";
                ?>
                <tr>
                    <td>
                        <b <?=$smaller?>><?=str_replace("/", " / ", $platoonSoftStatRow["TACTICAL"])?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=str_replace("/", " / ", $platoonSoftStatRow["TERRAIN_DASH"])?></b>
                    </td>                    
                    <td>
                        <b <?=$smaller?>><?=str_replace("/", " / ", $platoonSoftStatRow["CROSS_COUNTRY_DASH"])?> </b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=str_replace("/", " / ", $platoonSoftStatRow["ROAD_DASH"])?></b>
                    </td>
                    <td>
                        <b <?=$smaller?>><?=str_replace("/", " / ", $platoonSoftStatRow["CROSScheck"])?></b>
                    </td>
                </tr>
                <tr></tr>
                <tr>
                    <td colspan='9'>
                        <table>
                    
                <?php
                    if (isset($formationsInForce[$platoonSoftStatRow["code"]] )) {
                        foreach (explode("|",$formationsInForce[$platoonSoftStatRow["code"]]  ,27) as $key1 => $formation) {
                            $address = explode(", ",str_replace(")","",$formation));

                            ?>
                                <tr>
                                    <td ><a href="index.php?pd=<?=$address[2][0]?>W&ntn=<?=$platoonSoftStatRow["Nation"]?>&Book=<?=$address[1]?>&F1=<?=$address[2]?>" style="color: black;"><?=$formation?></a></td>
                                </tr>
                            <?php

                        }
                    }
                    ?>
                    </table>
                </td>

                </tr>


                <?php
                
        }
    } ?>
    </TBODY>
</table>
<div class='break-inside-avoid'> 
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Weapons
        </div>
    </div>        
    <div><?php //------- weapons ----------?>
   <?php

if (isset($platoonsInForceWeapon)&&count($platoonsInForceWeapon)> 0) {
    ?>
    <table class='break-inside-avoid'>
    <THEAD>
        <tr><th class='German'>Image</th><th class='German'>Weapon</th><th class='German'>Range</th><th class='German'>Halted ROF</th><th class='German'>Moving ROF</th><th class='German'>Anti Tank</th><th class='German'>Firepower</th><th class='German'>Notes</th></tr>                    
    </THEAD>
    <TBODY>
<?php
foreach ($platoonsInForceWeapon as $key => $row1)  {
    $teamImage = "";
    $waponsRow=[];
    $weaponsPerTeam=2;
    foreach ($row1 as $row2) 
        if ( strtolower($key??"") === strtolower($row2["team"]??"")) {

            $weaponsRow[]=$row2;
            $weaponsPerTeam++;
            $teamImage = $row2["team_image"];
            $teamTeam = $row2["team"];
    }

    if ($teamImage != "") { ?>
    <tr class='break-inside-avoid'>
        <td class='imagerow'  style='page-break-inside:avoid;' rowspan='<?=$weaponsPerTeam?>'><img src='img/<?=$teamImage?>.svg'></td>
    </tr>
            <tr style=class='break-inside-avoid'>
            <td class='teamHeader' colspan='8'><?=$teamTeam?>
            </td>
        </tr>
            <?php
    }

    if (isset($weaponsRow)) {

        foreach ($weaponsRow as $row2) {
        if ( (strtolower($key) === strtolower($row2["team"]))||isset($row2["teams"])&&(strtolower($key) === strtolower($row2["teams"])) ) {
                    
                    foreach ($rules as $row3) {
                        if (isset($row2["weapon_notes"])&&$row2["weapon_notes"] != ""&&isset($row3["name"])&&is_numeric(strrpos(strtoupper($row2["weapon_notes"]), strtoupper($row3["name"]),-1))) {
                            foreach (explode(",",$row2["weapon_notes"]) as $eachKeyword) {
                                if (trim($row3["name"]) == trim($eachKeyword)) {
                                    $rulesInForce[] =  $row3["name"];
                                }
                            }
                            
                        }
                    }                
                    
                    ?>
                    <tr>
                        <td class='firstWeaponrow' style='text-align: left;'>
                            <b><?=$row2["weapon"]?> </b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b><?=$row2["ranges"]?> </b>
                        </td><?=((($row2["haltedROF"] == "ARTILLERY")||($row2["haltedROF"] == "SALVO")) ? "
                        <td  class='firstWeaponrow' colspan='2'>
                            <b>{$row2["haltedROF"]}</b>
                        </td>" :"
                        <td class='firstWeaponrow' >
                            <b>{$row2["haltedROF"]}</b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b>{$row2["movingROF"]}</b>
                        </td>") ?>
                        <td class='firstWeaponrow' >
                            <b><?=$row2["antiTank"]?> </b>
                        </td>
                        <td class='firstWeaponrow' >
                            <b><?=$row2["firePower"]?> </b>
                        </td>
                        <td class='firstWeaponrow'  style='text-align: left;'>
                            <b><?=$row2["weapon_notes"]?> </b>
                        </td>
                    </tr>
                    <?php
                }

            }
        }
    }
}
?>
</TBODY>
</table>
</div></div>
<?php }

// ------- rules ----------
echo "
<div class='break-inside-avoid'>  
";
?>

<div class="collapsible <?=$query['ntn']?>">
    <div class="formHeader"> 
    Rules
    </div>
</div>    


<?php
   
$rulesInForce = array_unique($rulesInForce);        

if (isset($rules)&&$rules->num_rows > 0) {

            echo  "                    
                <table>
                    <THEAD>                 
                    </THEAD>
                    <TBODY>";
    foreach ($rulesInForce as $row1) {
        if ( $row1 <> "")  {
            foreach ($rules as $row2) {
                if (($row2["text"] <> "")&&( $row1 <> "")&&( $row1 === $row2["name"] )) {
                    echo "
                <tr class='break-inside-avoid'>
                    <td style='text-align: left;'>
                    <b>{$row1}</b>
                    </td>
                    <td style='text-align: left;'>
                        ".str_replace("\n","<br>", $row2["text"]) ."
                    </td>
                </tr>";
            //}
        }
    }
    }
    mysqli_data_seek($rules ,0);
}
}
            echo "
            <TBODY>
            </table>
            </div>   ";   

?></div>