<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include 'sqlServerinfo.php';
$userID = $_SESSION['user_id']??"";
$username = $_SESSION['username']??"";
include "functions.php";
include "login.php";
include "showListsFunctions.php";
include "cssVersion.php";

$linkQuery = $_SESSION['linkQuery']??"";


?>
<!DOCTYPE html>
<html>
<head>
    <title><?=$_SESSION['username']??""?>'s Lists</title>
    <link rel="icon" type="image/x-icon" href="/img/cardSmall.svg">
    <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/list.css?v=<?=$cssVersion?>'>
    <script src="showListsScripts.js"></script>
</head>
<body>
<?php 
$beta ="";
include "menu.php"; 
?>
    <div class="page-container">
        <div class="leftside"></div>
        <div class="centerColummn">
<?php 

if (isset($_SESSION['username'])) {


    $dataColumns = [

            [   "showMobile"=> true,
            "data-label" => "Period/Nation"], 
            [   "showMobile"=> true,
            "data-label" => "Book/Front"], 
            [   "showMobile"=> true,
            "data-label" => "Formation"], 
            [   "showMobile"=> true,
            "data-label" => "List Name"], 
            [   "showMobile"=> true,
            "data-label" => "Points"], 
            [   "showMobile"=> true,
            "data-label" => "Save date"], 
            [   "showMobile"=> true,
            "data-label" => "Event"]
    ];

    
    $results = $pdo->prepare(
        "SELECT * 
        FROM saved_lists 
        WHERE user_id=?
        ORDER by url, name");
    $results->execute([$userID]);

    $listArray = generateListArray($results, $conn);
    ?>

            <div class="header">
                <h2><?=$_SESSION['username']??""?>'s Lists</h2>
            </div>
            <div class="editRow">
                <span onclick="openNav()"><div class="menuButton wide"> ☰ </div></span>
                <form id="deleteForm" method="post" action="delete_selected.php">
                    <button type="submit" id="deleteSelectedButton">Delete Selected</button>
                </form>
                <button type="button" id="duplicateSelectedButton" class="delete-confirm">Duplicate Selected</button>
                <button type="button" id="editSelectedButton" class="delete-confirm">Edit Selected</button>
                <div class="searchBox"><label for="filterInput">Search:</label><input type="text" id="filterInput"></div>
                <button id="saveAllBtn" class="delete-confirm" style="display: none;">Save All</button>
            </div>

            <table id="listTable">
                <thead>
                    <tr id="filterRow">
                        <th style="display:none;" data-skip-filter></th>
            <th id="chenckboxHeaderCell" data-skip-filter ><input type="checkbox" id="selectAll"></th>
            
<?php
foreach ($dataColumns as $value) {
    if (!empty($value)) {
        ?>
            <th>
                <span class="sort"><?=$value["data-label"]?></span>
                <select class="filter-input">
                    <option value="all">All</option>
                </select>
                </th>
            </th>
        <?php
    }
}
?>
        </tr>
    </thead>
    <tbody>
<?php

echo generateShowListRows($listArray);
?>
    </tbody>
</table>
<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h4>Edit/inspect Entry</h4>

        <form id="editForm" method="post">
            <input type="hidden" name="id" id="entryId">
            
            <label for="editName">Rename:</label>
            <input type="text" name="name" id="editName">
            <button type="button" id="renameButton" data-action="rename" class="delete-confirm">Rename</button>
            <div id="platoonStats"></div>
            <br>
            <label for="editEvent">Add/Modify event:</label>
            <input list="eventList" name="event" id="editEvent" />
            <datalist id="eventList">
                <!-- Options will be populated dynamically -->
            </datalist>
            <button type="button" id="updateEventButton" data-action="updateEvent" class="delete-confirm">Change</button>
            <br>
            <button type="button" id="deleteButton" class="delete-confirm">Delete List</button>
            <button type="button" id="duplicateButton" class="delete-confirm">Duplicate List</button>
        </form>
    </div>
</div>


<!-- Edit Selected Modal -->
<div id="editSelectedModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h4>Edit Selected Entries</h4>
        <form id="editSelectedForm" method="post">

            <label for="editSelectedEventInput">Event:</label>
            <input list="eventList"  type="text" id="editSelectedEventInput" name="event">
            <datalist id="eventList">
                <!-- Options will be populated dynamically -->
            </datalist>
            <br>
            <label for="editNamePrefix">Name Prefix:</label>
            <input type="text" id="editNamePrefix" name="namePrefix">
            <br>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<?php
} else {
    
    ?>
    <span onclick="openNav()"><div id="openMenuButton">☰</div></span>
You are not logged in.
    
    <?php
}
$conn->close();
$pdo = null;
?>

<div class="footer"> </div>
</div>
<div class="rightside"></div>
</div>
</body>
</html>