<?php 
$pageIsIndex = ((is_numeric(strpos($_SERVER['PHP_SELF'],"index")))? "index": false);
$pageISShowLists = ((is_numeric(strpos($_SERVER['PHP_SELF'],"showLists")))? "showLists": false);
$pageIsDisplayList = ((is_numeric(strpos($_SERVER['PHP_SELF'],"listPrintGet")))? "listPrintGet": false);
$currentUrl = $_SERVER['REQUEST_URI'];
?>

<nav class="sidebar" id="sidebar">
  <span id="navClosebtn" class="closebtn" onclick="closeNav()">&times;</span>

  <form id="saveListForm" method="post" action="<?=$currentUrl?>">
  
    <?php if (!$pageIsIndex) :
        if ($pageISShowLists) : ?>
        <div class="input-group">
          <a href="index.php?<?=$linkQuery?>">Edit latest List</a>
        </div>
          
        <?php else : ?>
          <div class="input-group">
            <a href="index.php?<?=$linkQuery?>">Edit List</a>
          </div>
          
          <?php if ($pageIsDisplayList) :?>
            <div class="input-group">
              <a listPrint href="listPrintGet.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>">View List</a>
            </div>
            <div class="input-group">
              <a listPrintBf href="listPrintGetBFStyle.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>">View List BF style</a>
            </div>
            
            
          <?php endif ?>
        <?php endif ?>
      <?php endif ?>
    <?php
    if (($userID??"na")=="") {
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
        <a listPrint href="listPrintGet.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>">View List</a>
      </div>
      <div class="input-group">
        <a listPrintBf href="listPrintGetBFStyle.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>">View List BF style</a>
      </div>
      <div class="input-group">
        <input alt="custom name" placeholder="Name displayed at list print" name="loadedListName" id="loadedListName" value="<?php echo $query['loadedListName']??""; ?>">
        <button value="" onClick="setDropdownAndSubmit();">
            <img src="img/update.svg" alt="update">
        </button>
        <button value="" onClick="resetDropdownAndSubmit();">
            <img src="img/clear.svg" alt="clear">
        </button>
      </div>

      Event:
      <div class="input-group">
        <?php if (isset($usersEventList)) echo generateDroppdownHTML("listEventList", "listEventList", $usersEventList,false,"Associate list with event" ); ?>
      </div>
      Update or load list:
      <div class="input-group">
      <?php if (isset($usersListsList)) echo generateDroppdownHTML("listNameList", "listNameList", $usersListsList,false, "Update or load list"); ?><button name="refreshSelected"  alt="refresh dropdown" title="refresh dropdown"><img src="img/update.svg" alt="update"></button>
      </div>
      
        <div class="input-group">
        <input type="hidden" id="updated_url" name="updated_url" value="<?=$currentUrl?>">
        <button type="submit" name="updateSelected">Update above list with current list</button>
        <button type="submit" name="loadSelected">Load above list</button>
          
        </div>

        <div class="input-group">
          <input type="text" name="listName" value="<?=$query["loadedListName"]??""?>" placeholder="New List name"> 
          
        </div>
        <button type="submit" name="save_url">Save as new list</button>

      <?php } ?>
      <a href="mailto:fowlistfeedback@gmail.com">Feedback</a>
      <div class="input-group">
      <a class="bmc-btn" target="_blank" href="https://buymeacoffee.com/Fowlist">
        <img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="support us" title="support us" width="150"></a>
      </div>
      <div class="input-group">
        <button type="submit" class="btn" name="logout_user">Logout</button>
      </div>

      <?php
      if (isset($_SESSION['success'])) {
        echo "<div class='input-group'>" . $_SESSION['success'] . "</div> ";
      }
    }
    ?>
  </form>
  <a href="https://breakthroughassault.co.uk/category/podcast/shoot-and-scoot/">    <div class="sponsor">
        <div>
        <img src="https://breakthroughassault.co.uk/wp-content/uploads/2022/07/newlogo_big-150x150.png" alt="shoot and scoot" style="width: 100px;">
        </div>
        <div>
        Thanks to Shoot and scoot podcast for sponsoring the site.
        </div>

        </div></a>
        <div class="disclaimer">Disclamer:<br>
        This is a free unofficial alternative tool to generate lists for flames of war. It is a complement to the books and cards, bought either physical or from here: <a href="https://forces.flamesofwar.com">the official tool</a>. For validating lists for tournament play use official tool.</div>
          <div class="theme-selector">
            <label for="theme">Theme: </label>
            <select id="theme" onchange="changeTheme(this.value)">
              <option value="default">Default</option>
              <option value="modern">Modern</option>
              <option value="earthy">Earthy</option>
              <option value="dark">Dark</option>
              <option value="german">German</option>
              <option value="american">American</option>
              <option value="british">British</option>
              <option value="soviet">Soviet</option>
              <option value="finnish">Finnish</option>
              <option value="hungarian">Hungarian</option>
              <option value="romanian">Romanian</option>
              <option value="italian">Italian</option>
            </select>
          </div>
        </nav>
<header id="main-header">

        <div class="logo">
          <span id="openMenuButtonSpan" class="hamburger" onclick="openNav()">☰</span>
          <a href="index.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?>"> <img src="img/Card.svg" alt=""> <span id="fowListLogo">FOWList</span></a>
        </div>
        <nav>
        <?php if (!$pageISShowLists) : ?>
          <a href="showLists.php">View all lists</a>
        <?php endif ?>
        <?php if (!$pageIsIndex) :
          if ($pageISShowLists) : ?>
            <a href="index.php?<?=$linkQuery?>">Edit latest List</a>
          <?php else : ?>
            <a href="index.php?<?=$linkQuery?>">Edit List</a>
            <?php if ($pageIsDisplayList) :?>
              <a listPrint href="listPrintGet.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>"><img class='buttonImage' src='img/viewList.svg' alt='View List' title='View List'> View List</a>
              <a listPrintBf href="listPrintGetBFStyle.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>"><img class='buttonImage' src='img/viewListBF.svg' alt='View List Bf style' title='View List Bf style'> View List BF style</a>
            <?php endif ?>
          <?php endif ?>
        <?php else :?>
          <a listPrint href="listPrintGet.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>"><img class='buttonImage' src='img/viewList.svg' alt='View List' title='View List'> View List</a>
          <a listPrintBf href="listPrintGetBFStyle.php?<?=$linkQuery?><?=($query["loadedListName"]??false)?"&loadedListName={$query["loadedListName"]}":""?><?=$costArrayStrig?>"><img class='buttonImage' src='img/viewListBF.svg' alt='View List Bf style' title='View List Bf style'> View List BF style</a>
        <?php endif ?>
          <a id="manualLink" href="https://github.com/fowlist/FowList/wiki/User-Manual">User manual</a>
          <a id="tyLink" href="https://ty.fowlist.com/index.php?lsID=&pd=TY">WWIII team yankee</a>
          <?php if ($pageIsDisplayList) : ?>
            <span class="printButton" value="" onClick="window.print();">Print the page</span>

        <?php endif ?>
        
        </nav>
          <?php  if (!$pageISShowLists&&!$pageIsIndex) : ?>
            <div class="rearrangeSwitch">
            <!--<button id="generatePdfButton">save pdf</button>-->
            <label for="dragToggleSwitch" class="switch">
              Rearrange:
            </label>
            <input id="dragToggleSwitch" type="checkbox">
          </div>
          <?php else : ?>
            <div class="pointsSpacer"> </div>
          <?php endif ?>

    </header>
    <?php if ($pageIsIndex) : ?>
          <div id="pointsOnTop" costArray="<?=htmlspecialchars(json_encode($dataToTransfer))?>">
            <div class='Points'>
              <div>
                  <?=array_sum($formationCost)+$listCardCost?> points 
              </div>
            </div>
          </div>
    <?php endif ?>
<script>
  function openNav() {
    document.getElementById("sidebar").classList.add("open");
    document.getElementById("navClosebtn").classList.add("open");
  }
  function closeNav() {
    document.getElementById("sidebar").classList.remove("open");
    document.getElementById("navClosebtn").classList.remove("open");
  }

  // Set a cookie
  function setCookie(name, value, days) {
      const expires = new Date(Date.now() + days * 86400000).toUTCString();
      document.cookie = `${name}=${value}; expires=${expires}; path=/`;
  }

  // Get a cookie
  function getCookie(name) {
      const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      console.log(match);

      return match ? match[2] : null;
  }

  // Change theme and save to cookie
  function changeTheme(theme) {
      document.body.setAttribute('data-theme', theme);
      setCookie('theme', theme, 30); // Save theme for 30 days
      updateNationSpecificTheme();
  }
  function updateNationSpecificTheme() {
    const theme = document.body.getAttribute('data-theme');
    const nationDivs = document.querySelectorAll('.platoon, .blackbox, h2, h3, th, .header, .collapsible, .forceCard label');

    nationDivs.forEach(div => {
      if (theme === 'default'||theme === 'modern') {
          // Remove the override class when the default theme is active
          div.classList.remove('theme-overridden');
      } else {
          // Add the override class for other themes
          div.classList.add('theme-overridden');
      }
    });
  }
  // Load saved theme from cookie on page load
  document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = getCookie('theme') || 'default';
      document.body.setAttribute('data-theme', savedTheme);
      document.getElementById('theme').value = savedTheme;
      updateNationSpecificTheme();
      
  });
</script>