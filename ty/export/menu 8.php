<div class="topnav">
    <a class="slowLink" href="<?="index" . $beta .".php?". $linkQuery ?>">FOW List: <?=isset($bookTitle)?$bookTitle:""?></a>

<div id="myLinks">
<?php
$pageIsIndex = ((is_numeric(strpos($_SERVER['PHP_SELF'],"index")))? "index": false);
if (!$pageIsIndex) {
  ?>
  <a class="slowLink" href="index<?=$beta?>.php?<?=$linkQuery?>">Back</a>
  <?php

} else {
  ?>
  <a class="slowLink" href="listPrintGet<?=$beta?>.php?<?=$linkQuery. $costArrayStrig?>">View List</a>
  <?php
}
?>

  <form id="saveListForm" method="post" action="<?="index" . $beta .".php?". $linkQuery ?>">
  
    <?php
    if ((isset($userID))&&($userID=="")) {
      ?>
      <div class="input-group">
        <label>Username</label>
        <input type="text" name="username">
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password">
      </div>
      <div class="input-group">
        <button type="submit" class="btn" name="login_user">Login</button>
      </div>
      <label>
              Not a member? <a href="createUser.htm">Sign Up</a>
      </label>
      <?php
      echo "<label>" . $_SESSION['success'] . "</label>";
    } else {
      echo "<label>" . (isset($_SESSION['success'])?$_SESSION['success']:"") . "</label>";
      ?>
      <input type="text" name="listName" placeholder="List Name">
      <button type="submit" name="save_url">Save URL</button>
      <?php 
      if (isset($usersListsList)) echo generateDroppdownHTML("listNameList", "listNameList", $usersListsList,false);
      ?>
      <button type="submit" name="updateSelected">Update Selected</button>
      <button type="submit" name="loadSelected">Load Selected</button>
      <a href="showLists.php">View lists of <?=(isset($username)?$username:"") ?></a>
      <button type="submit" class="btn" name="logout_user">Logout</button>
      
      <?php
          if (isset($_POST['save_url'])) {
              $saveCost = array_sum($formationCost)+$listCardCost;
              $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost) VALUES (?, ?, ?, ?)");
              $query1->execute([$userID, $saved_url, $listName, $saveCost]);
              //$query1 = "INSERT INTO saved_lists (user_id, url, name, cost) VALUES ('$userID', '$saved_url', '$listName', '$saveCost')";
              //mysqli_query($conn, $query1);
              echo "URL saved.";
          }
          if (isset($_POST['updateSelected'])&&isset($_POST["listNameList"])) {
              $saveCost = array_sum($formationCost)+$listCardCost;
              $query1 = $pdo->prepare("UPDATE saved_lists SET url = ? WHERE id =?");
              $query1->execute([$saved_url, $_POST["listNameList"]]);
              //$query1 = "UPDATE saved_lists SET url = '{$saved_url}' WHERE id ='{$_POST["listNameList"]}' ";
              //mysqli_query($conn, $query1);
              echo "URL Updated.";
          }

    }
    ?>
  </form>


</div>

    <a href="javascript:void(0);" class="icon" onclick="myFunction()">
    ☰
    </a>
</div>

<script>
        /* Toggle between showing and hiding the navigation menu links when the user clicks on the hamburger menu / bar icon */
        function myFunction() {
          var x = document.getElementById("myLinks");
          if (x.style.display === "block") {
            x.style.display = "none";
          } else {
            x.style.display = "block";
          }
        }
    </script>