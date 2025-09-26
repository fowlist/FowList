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

// 2. Pagination setup
$limit = 100; // rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 3. Count total rows
$totalResult = $conn->query("SELECT COUNT(DISTINCT team) as total FROM team_platoon_formation_stats")->fetch_assoc();
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

$platoonsInForce = [];
$result = $conn->query("SELECT DISTINCT team, team_image FROM team_platoon_formation_stats WHERE formation_info IS NOT NULL AND formation_info <> '' ORDER BY team
        LIMIT $limit OFFSET $offset");
foreach ($result as $key => $value) {
    $platoonsInForce[$value["team"]] = $value;
}

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

    <title>FOW List all teams </title>
    <link rel="icon" type="image/x-icon" href="/img/<?=$query["ntn"]?>.svg">
</head>
<body>


<?php include "menu.php"; ?>

    <div id="page-container" class="page-container">
<?php        
//------------------------------------
// ------- all teams ----------
//------------------------------------
?>
    <div class='break-inside-avoid'>
    <div class="collapsible <?=$query['ntn']?>">
        <div class="formHeader"> 
        Arsenal (page <?=$page?>)
        </div>
    </div>

    <?php

if (isset($platoonsInForce)&&count($platoonsInForce) > 0) {// ------- soft stats ----------
    ?>
        <table class='arsenal break-inside-avoid'>
        <THEAD>
            <tr><th class='German' rowspan='2'>Image</th><th class='German'>Team</th></tr>
        </THEAD>
        <TBODY>

    <?php
    foreach ($platoonsInForce as $platoonSoftStatRow) { 
        if (($platoonSoftStatRow["team"]!="")) {

            ?>

            <tr class='break-inside-avoid'>
                <td class='imagerow break-inside-avoid' style='max-width: 60px;'>
                                <a href="list_all_teams.php?team=<?= urlencode($platoonSoftStatRow["team"]) ?>" style="color: inherit; text-decoration: none;">
                <?php
                if (isset($platoonSoftStatRow["team_image"])) {
                    foreach (explode("|",$platoonSoftStatRow["team_image"] ,7) as $key1 => $boxImage) 
                    echo  "<img src='img/{$boxImage}.svg'>";
                }
                ?>
</a>
                </td>
                <td>
                                <a href="list_all_teams.php?team=<?= urlencode($platoonSoftStatRow["team"]) ?>" style="color: inherit; text-decoration: none;">
            <?= htmlspecialchars($platoonSoftStatRow["team"]) ?></a></td>
                </tr>


                <?php
                
        }
    } ?>
    </TBODY>
</table>
<?php }
// Previous button
if ($page > 1) {
    echo "<a href='?page=" . ($page - 1) . "'>Previous</a> ";
}
// Show up to 10 page numbers around current
$start = max(1, $page - 5);
$end = min($totalPages, $page + 5);
for ($i = $start; $i <= $end; $i++) {
    $active = ($i == $page) ? "style='font-weight:bold;'" : "";
    echo "<a $active href='?page=$i'>$i</a> ";
}

// Next button
if ($page < $totalPages) {
    echo "<a href='?page=" . ($page + 1) . "'>Next</a> ";
}

echo "</div>";

// 7. Dropdown to jump directly
echo "<form method='get' style='margin-top:10px;'>
        <label for='page'>Jump to page: </label>
        <select name='page' id='page' onchange='this.form.submit()'>";
for ($i = 1; $i <= $totalPages; $i++) {
    $selected = ($i == $page) ? "selected" : "";
    echo "<option value='$i' $selected>$i</option>";
}
echo "</select>
      </form>";

?></div>