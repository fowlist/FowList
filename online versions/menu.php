
<span id="openMenuButtonSpan" onclick="openNav()"><div id="openMenuButton">☰</div></span>
<?php 
$pageIsIndex = ((is_numeric(strpos($_SERVER['PHP_SELF'],"index")))? "index": false);
$pageISShowLists = ((is_numeric(strpos($_SERVER['PHP_SELF'],"showLists")))? "showLists": false);
if (!$pageIsIndex&&!$pageISShowLists) { ?>
  <a href="index.php?<?=$linkQuery?>"><div id="backButton">Edit List</div></a>
<?php } 
if (!$pageISShowLists) { ?>
  <a href="showLists.php"><div id="listsButton">View all lists</div></a>
<?php } ?>
<div id="mySidenav" class="topnav">
<a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
<?php

if (!$pageIsIndex) {
  if ($pageISShowLists) {
    ?>
    <a href="index.php?<?=$linkQuery?>">Edit latest List</a>
    <?php
  } else {
  ?>
  <a href="index.php?<?=$linkQuery?>">Edit List</a>
  <?php
  }


} else {
  ?>
  <a href="listPrintGet.php?<?=$linkQuery?>&loadedListName=<?=$query["loadedListName"]??""?><?=$costArrayStrig?>">View List</a>
  <a href="listPrintGetBFStyle.php?<?=$linkQuery?>&loadedListName=<?=$query["loadedListName"]??""?><?=$costArrayStrig?>">View List BF style</a>
  <?php
}
$currentUrl = $_SERVER['REQUEST_URI'];
?>

  <form id="saveListForm" method="post" action="<?=$currentUrl?>">
  
    <?php
    if ((isset($userID))&&($userID=="")) {
      ?>
      <div class="input-group">
        <label>Username</label><br>
        <input type="text" name="username">
      </div>
      <div class="input-group">
        <label>Password</label><br>
        <input type="password" name="password">
      </div>
      <div class="input-group">
        <button type="submit" class="btn" name="login_user">Login</button>
      </div>
      <div class="input-group">
        Not a member?
        <a href="createUser.htm">Sign Up</a>
      </div>   
      <div class="input-group">
        Forgot password?
        <a href="initiate_reset_password.php"> Reset password</a>
      </div>  
        
      <?php
      if (isset($_SESSION['success'])) {
        echo "<div class='input-group'>" . $_SESSION['success'] . "</div> ";
      }
      
    } else {

      if ($pageIsIndex) { ?>
      <div class="input-group">
        Associate with event:
        <?php if (isset($usersEventList)) echo generateDroppdownHTML("listEventList", "listEventList", $usersEventList,false); ?>
        <input type="text" name="listName" value="<?=$query["loadedListName"]??""?>" placeholder="List Name">
        <button type="submit" name="save_url">Save as new list</button>
      </div>
      <div class="input-group">
        Saved Lists:
      <?php if (isset($usersListsList)) echo generateDroppdownHTML("listNameList", "listNameList", $usersListsList,false); ?>
      </div>
      
      
        <div class="input-group">
          <button type="submit" name="updateSelected">Update Selected</button>
          <button type="submit" name="loadSelected">Load Selected</button>
          <button type="submit" name="refreshSelected">Refresh list</button>
        </div>
      <?php } 
      if (!$pageISShowLists) {
      ?>
      <a href="showLists.php">View lists of <?=(isset($username)?$username:"") ?></a>
      <?php } ?>
      <a href="mailto:fowlistfeedback@gmail.com">Something wrong? send feedback</a>
      <div class="input-group">
        <script type="text/javascript" src="https://cdnjs.buymeacoffee.com/1.0.0/button.prod.min.js" data-name="bmc-button" data-slug="Fowlist" data-color="#FFDD00" data-emoji=""  data-font="Cookie" data-text="support us" data-outline-color="#000000" data-font-color="#000000" data-coffee-color="#ffffff" ></script>
      </div>
      <div class="input-group">
        <button type="submit" class="btn" name="logout_user">Logout</button>
      </div>
      <a href="https://github.com/fowlist/FowList/wiki/User-Manual">User manual</a>
      <a href="https://ty.fowlist.com/index.php?lsID=&pd=TY">WWIII team yankee</a>
      <?php
      if (isset($_SESSION['success'])) {
        echo "<div class='input-group'>" . $_SESSION['success'] . "</div> ";
      }
          if (isset($_POST['save_url'])) {
        unset($_POST['save_url']);
          $associatedEvent = "";
          if (!empty($usersEventList)) {
            foreach ($usersEventList as $eachEvent) {
              if ($eachEvent["value"]==$_POST["listEventList"]) {
                $associatedEvent  = $eachEvent["description"];
              }
            }
          }

              $saved_url_to_list = "listPrintGet.php?{$linkQuery}&loadedListName=" . ($query["loadedListName"]??"") .$costArrayStrig;
              $saveCost = array_sum($formationCost)+$listCardCost;

            $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, urlToList, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $query1->execute([$userID, $saved_url, $saved_url_to_list, $listName, $saveCost, date("Y-m-d",time()),$associatedEvent]);
              //$query1 = "INSERT INTO saved_lists (user_id, url, name, cost) VALUES ('$userID', '$saved_url', '$listName', '$saveCost')";
              //mysqli_query($conn, $query1);
            $newId = $pdo->lastInsertId();
            echo "URL saved.";
            $query['loadedListName'] = $listName;
            $_POST["loadedListName"] = $listName;
            $_SESSION['loadedListNumber'] =$newId;
          }
          if (isset($_POST['updateSelected'])&&isset($_POST["listNameList"])) {
              $saved_url_to_list = "listPrintGet.php?{$linkQuery}&loadedListName=" . ($query["loadedListName"]??"") .$costArrayStrig;
              $saveCost = array_sum($formationCost)+$listCardCost;
              $query1 = $pdo->prepare("UPDATE saved_lists SET url = ?, saveDate = ?, urlToList =? WHERE id =?");
              $query1->execute([$saved_url, date("Y-m-d",time()),$saved_url_to_list, $_POST["listNameList"]]);
              //$query1 = "UPDATE saved_lists SET url = '{$saved_url}' WHERE id ='{$_POST["listNameList"]}' ";
              //mysqli_query($conn, $query1);
              echo "URL Updated.";
          }
    }
    ?>
  </form>
</div>
<script>

        /* Set the width of the side navigation to 250px and the left margin of the page content to 250px */
function openNav() {
  document.getElementById("mySidenav").classList.add("open");
}

/* Set the width of the side navigation to 0 and the left margin of the page content to 0 */
function closeNav() {
  document.getElementById("mySidenav").classList.remove("open");
}
    </script>