<?php
$sessionStatus = session_start();
header('Content-Type: text/html; charset=utf-8');
include "process.php";

//-----------------------------------------------------------------------------
//------------------- HTML print-----------------------------------------------
//-----------------------------------------------------------------------------
echo "<!DOCTYPE html>";
include "cssVersion.php";
?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=0.7">

    <meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
    <script src="jquery-3.7.0.min.js"></script>
          <script>
    $('#submit').click(function(e){ 
        e.preventDefault();
    });
    </script>
        <link rel='stylesheet' href="css/menu.css?v=<?=$cssVersion?>">
    <link rel='stylesheet' href='css/nations.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/index.css?v=<?=$cssVersion?>'>
    <link rel='stylesheet' href='css/MSI.css?v=<?=$cssVersion?>'>
    <title>FOW List<?=((isset($bookTitle))?" - ". $bookTitle:"") . ((isset($formationTitle[1]))?" - " . $formationTitle[1]:"") . ((isset($formationTitle[2]))?" - " . $formationTitle[2]:"")?></title>
    <link rel="icon" type="image/x-icon" href="/img/<?=((isset($query["ntn"]))?$query["ntn"]:"Card")?>.svg">
</head>
<body>

<?php include "menu.php"; 
$pdo = null;?>
    <form name="form" id="form" method="get" action="<?=$_SERVER['PHP_SELF']. $targetLocation ?>">    
    <div  id="page-container" class="page-container">
    <a href="index.php?<?=$linkQuery?>&loadedListName=<?=$query["loadedListName"]??""?>">FOWList.com</a><label for="dPs"><input type="checkbox" name="dPs" id="dPs" value="true" 
    <?=((isset($query["dPs"])&&$query["dPs"]=="true")?"checked":"")?>
    onchange='this.form.submit();'> Enable Dynamic Points</label>
    <div id="backToTopButton">
        <a href="#top">Back to Top</a>
    </div>
    <div id="pointsOnTop">
        <div class='Points'>
            <div>
                <?=array_sum($formationCost)+$listCardCost?> points 
            </div>
        </div>
    </div>
    <input type="hidden" name="lsID" id="lsID" value="">
    <a href="listPrintGet.php?<?=$linkQuery?>&loadedListName=<?=$query["loadedListName"]??""?><?=$costArrayStrig?>">
            <div id="viewlistOnTop">
                    View List
                    </div>
        </a>
        <br>
    <div class="disclaimer">Disclamer:<br>
This is a free unofficial alternative tool to generate lists for flames of war as a complement to the books and cards, that you need to have bought either physical or from here: <a href="https://forces.flamesofwar.com">the official tool</a> should always be used. This is especially true for validating lists for tournament play.</div>
    <?php
// -----------------------------------------------------
// ----------- Formation print -------------------------
// -----------------------------------------------------
if (isset($query['Book'])&&isset($query['ntn'])) {
    ?>
    <div class="header">
        <h2 class="<?=$query["ntn"]?>">
            <?php
                echo " <button type='submit' value='' onClick='pd.value =0; Book.value =0; ntn.value =0; this.form.submit();'>New List</button>";
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true, "Select Nation");
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML("Book","Book", $nationBooks,true, "Select Book");
                echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
            ?>
            <br>
                 <input name="loadedListName" id="loadedListName" value="<?php echo $query['loadedListName']??""; ?>">
                 <button type="submit" value="" onClick="setDropdownAndSubmit();">
                    Update
                </button>
                <button type="submit" value="" onClick="resetDropdownAndSubmit();">
                    Clear
                </button>
            <?=$bookTitle?>         
        </h2><br>

Select number of formation from this book: <select name='nOF' id='nOF' onchange='this.form.submit();'>
                <?php
        for ($i = 0; $i <=3; $i++) {
            echo "\n<option " . ((isset($query['nOF'])&&($i == $query['nOF'])||((!isset($query['nOF']))&&($i==1))) ? " selected " : "") . "value={$i}>{$i}</option>";
            }  
                ?>
            </select>
    </div>
    <?php 
}    
for ($formationNr = 1; $formationNr <= $nrOfFormationsInForce; $formationNr++) {
    $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.
    if (isset($query[$currentFormation])){

// -----------Formation Title print --------------------
    ?>
    <button type="button" class="collapsible <?=$query["ntn"]?>">
        <h3> <?php 
            if ((isset($cmdCardTitleOfEntireFormation[$formationNr])&&($cmdCardTitleOfEntireFormation[$formationNr]!="")||(is_numeric(strpos($query[$currentFormation],"C"))))) {
                echo "<div class='left'>
                <img class='card' src='img/Card.svg'>" . generateTitleImanges($insignia, $cmdCardTitleOfEntireFormation[$formationNr] . $formationTitle[$formationNr], $query["ntn"]) . "
            </div>";
            }
        
            if (isset($cmdCardTitleOfEntireFormation[$formationNr])) {
                echo  (((isset($cmdCardTitleOfEntireFormation[$formationNr])&&$cmdCardTitleOfEntireFormation[$formationNr]!="")&&($cmdCardTitleOfEntireFormation[$formationNr]!=$formationTitle[$formationNr])) ? "
            {$cmdCardTitleOfEntireFormation[$formationNr]}: ": "");
            }
            echo isset($formationTitle[$formationNr])?$formationTitle[$formationNr]:""?>

        </h3>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:""?> points 
            </div>
        </div> 
    </button>
    <?php 
// -----------------------------------------------------
    ?>    
    <div class="Formation">
        <br> 
        <?=dropdown($Formations,"","code","title",$currentFormation,true,"Book",$bookTitle,false,"",'',$query)?>
        <button type='submit' value='' onClick='<?=$currentFormation?>.value =0; this.form.submit();'>Clear</button>
        <?=isset($cmdCardsOfEntireFormation[$formationNr])?$cmdCardsOfEntireFormation[$formationNr]:""?>
        <?=((!empty($formationNote[$formationNr]))? "<br>" .$formationNote[$formationNr] : "" )?>
        <div class='grid'>
        <?php
// ------------ Boxes print -------------- 
        foreach ($formationHTML as $formationKey => $htmlOutputRow){
            
            $position = strpos($formationKey ,"-" ); 
            if ($position !== false) {
                $boxKey = substr($formationKey, $position + strlen("-"));
                $formationKey = strstr($formationKey, "-",true);
                if ($formationKey == $currentFormation) {
                    echo $htmlOutputRow;
                }
                
     // -------- points ----------
                
                if (($formationKey == $currentFormation)&&(isset($boxCost[$formationNr][$boxKey]))) {
                     echo "
                    <div class='Points'>
                      <div>
                        {$boxCost[$formationNr][$boxKey]} points
                      </div>
                    </div>
                    ";
                }
    // -------- To Here ----------             
                if ($formationKey == $currentFormation) {
                    echo "</div>";
                }
            }
        }
        echo "
            </div>
        </div>";
    } else {
        if ($bookSelected&&isset($formationSelectButtonsHTML[$currentFormation])){
            echo $formationSelectButtonsHTML[$currentFormation];
        }
    }
}     
    
// -----------------------------------------------------
// -------from other book Formation title print --------
// -----------------------------------------------------
if (isset($query['Book'])) {
?>
<div class="header"><button type="button" class="addFormButton" onclick="incrementNOF()">Add <?=$nrOfFormationsInForce>0?"one aditional ":"" ?>formation from <?=$bookTitle?></button></div>

   <div class="header">
   <h2>        Formation from other book         

            <select name='nOFoB' id='nOFoB' onchange='this.form.submit();'>
                
                <?php
        for ($i = 0; $i <=3; $i++) {
            echo "
                <option " . ((($i == $query['nOFoB'])) ? " selected " : "") . "value={$i}>{$i} formation" . (($i!=1)?"s":"") . "</option>";
            }  
                ?>
            </select>
</h2>
        
    </div>

    <?php 
}

for ($formationNr = $nrOfFormationsInForce+1; $formationNr <= $nrOfFormationsInForce+$query['nOFoB']; $formationNr++) {
    $currentFormation = "F" . $formationNr;          // F1 F2 ,(prev. Form01, Form02)  etc.  the session variable with this name should be set to ie. LG217, LG193 etc.

    if (isset($query[$currentFormation])){
    ?>
    <button type="button" class="collapsible <?=$formationNation[$currentFormation . "Book"]?>">
        <h3> <?=$formationTitle[$formationNr]?>
        </h3>
            <div class='Points'>
                <div>
                    <?=$formationCost[$formationNr]?> points 
                </div>
            </div> 
    </button>
    <?php 
// -----------------------------------------------------
    ?>    
    <div class="Formation">
        <br> 
        <?php
        echo generateDroppdownHTML($currentFormation . "Book", $currentFormation . "Book", $aliedBooks[$currentFormation],true);
        //echo dropdown($Books,       "","code","Book",   $currentFormation . "Book", true,"Nation",  $query['ntn']                       ,true,"period",$query['pd'],$query);
        echo dropdown($otherBookFormations,  "","code","title",  $currentFormation,          true,"Book",    $currentBookTitle[$currentFormation]   ,false,"",'',$query); ?>
        
        <button type='submit' value='' onClick='<?=$currentFormation?>.value =0; this.form.submit();'>Clear</button>

        <?php
        echo  (($formationNote[$formationNr]<>"")? "
        <br>" .$formationNote[$formationNr] : "" );
        echo "
        <div class='grid'>";

// ------------ Boxes print -------------- 
        foreach ($formationHTML as $formationKey => $htmlOutputRow){
            
            $position = strpos($formationKey ,"-" ); 
            if ($position !== false) {
                $boxKey = substr($formationKey, $position + strlen("-"));
                $formationKey = strstr($formationKey, "-",true);
                if ($formationKey == $currentFormation) {
                    echo $htmlOutputRow;
                }
     // -------- points ----------
                
                if (($formationKey == $currentFormation)&&(isset($boxCost[$formationNr][$boxKey]))) {
                     echo "
                    <div class='Points'>
                      <div>
                        {$boxCost[$formationNr][$boxKey]} points
                      </div>
                    </div>
                    ";
                }
    // -------- To Here ----------             
                if ($formationKey == $currentFormation) {
                    echo "</div>";
                }
            }
        }
        echo "
            </div>
        </div>";
    } else {
        if ($bookSelected) {
            if (isset($query[$currentFormation . "Book"])){ 

                echo $formationSelectButtonsHTML[$currentFormation];
    
            } else {
                echo generateDroppdownHTML($currentFormation . "Book", $currentFormation . "Book", $aliedBooks[$currentFormation]);
                //echo dropdown($Books,       "","code","Book",   $currentFormation . "Book", true,"Nation",  $query['ntn']                       ,true,"period",$query['pd'],$query);
            }
        }
    }
}
if (isset($forceCardHTML)) {
echo $forceCardHTML;
}


//-------------------------------------------------------------------
//--------------------- Support print -------------------------------
//-------------------------------------------------------------------
if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)){    
    
$currentFormation = "Sup";
$formationNr+=1;

?>
<button type="button" class="collapsible"> <h3><?=$bookTitle?> Support</h3>
    <div class='Points'>
        <div>
            <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
        </div>
    </div>  
</button>

<div class='Formation'>
    <br>
    <div class="grid">
        <?=$supportHTML[1]?>
    </div> 
</div>

<?php
$currentFormation = "BlackBox";
$formationNr+=1;

}

if (isset($BBSupport_DB)&&query_exists($BBSupport_DB)) {

    ?>
    <div class="header">
        <h2>Formation Support</h2>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
            </div>
        </div> 
    </div>
    <?php
    echo $blackBoxHTML[1];
}

if (isset($Formation_DB)&&(count($Formation_DB) > 0)&&(!($BBSupport_DB instanceof mysqli_result)&&query_exists($Formations))){    
    
} else {

    // ------- Selection buttons for book / nation / period ----------


    if ($bookSelected){ 
        
    } else{
        if  (isset($query['ntn'])) {
            ?>
            
            <div class="header">
            <h2 class="<?=$query["ntn"]?>">
                    <?php
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true, "Select Nation");
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML("Book","Book", $nationBooks,true, "Select Book");
                echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
            ?>
                    </h2>
                    </div>
                    <button type="button" class="collapsible"><h3>No Book selected:</h3></button>
                    <div class='Formation'>
                        <div class="grid">
        <?php
            if (count($Books)> 0) {
                foreach ($Books as $row) { 
                    if  (($row["Nation"] == $query['ntn'])&&($row["period"] == $query['pd'])) {
                        echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:100px;'>
                                    <button type='submit' name='Book' class='{$row["Nation"]}' value='{$row["code"]}' style='height:80px; width:100%;'><span class='nation'><img src='img/" . (is_numeric(strpos($row["Book"],"Waffen-SS"))? "shuts" : $query['ntn']) . ".svg'></span><br>{$row["Book"]}</button> <br>
                                </div>
                            </div>
                        </div>";
                    }
                }
                echo "
        </div>
        </div>
        ";
            }
        } else {       
            if  (isset($query['pd'])) {
                            ?>
                    <div class="header">
                    <h2>
        <?php
                echo dropdown($Periods,"","period","periodLong",'pd',false,"","",false,"","",$query);   
                echo " <button type='submit' value='' onClick='" . 'pd' . ".value =0; this.form.submit();'>Clear</button>";
                echo generateDroppdownHTML('ntn','ntn', $Nations,true, "Select Nation");
                echo " <button type='submit' value='' onClick='" . 'ntn' . ".value =0; this.form.submit();'>Clear</button>";
if (isset($query['ntn'])) {
                    echo generateDroppdownHTML("Book","Book", $nationBooks,true, "Select Book");
                echo " <button type='submit' value='' onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button>";
}
                
            ?>
            </h2>
            </div>
            
        <button type="button" class="collapsible"><h3>No nation selected <?php echo $query['pd']?> </h3></button>
        <div class='Formation'>
        <div class="grid">
        <?php
                    if (count($Nations) > 0) {
                    foreach ($Nations as $row) { 
                        if  ($row["period"] == $query['pd']) {
                            echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:100px;'>
                                    <button type='submit' name='ntn' class='{$row["Nation"]}' value='{$row["Nation"]}' style='height:80px; width:100%;'><span class='nation'><img src='img/{$row["Nation"]}.svg'></span><br>{$row["Nation"]}</button> <br>
                                </div>
                            </div>
                        </div>";
                        }
                    }
                    echo "
        </div>
        </div>
        ";
                }
            } else {
                echo "<br><br><br><br><button type=\"button\" class=\"collapsible\">
                <h3>No period selected</h3></button>
                <div class=\"Formation\">
                <div class=\"grid\">";
                    foreach ($Periods as $row) { 
                        echo  "
                        <div class='box'>
                            <div class='platoon'>
                                <div  class='title' style='height:120px;'>
                                    <button type='submit' name='pd' value='{$row["period"]}' style='height:100px; width:100%;'><span class='nation'><img src='img/{$row["period"]}.svg'></span><br>{$row["periodLong"]}</button> <br>
                                </div>
                            </div>
                        </div>";
                            }
                    echo "
        </div></div>
        ";
            }
        }
    }
}


$currentFormation = "CdPl";
$formationNr+=1;

if (isset($CardBoxHTML)&&count($CardBoxHTML) > 0) {

    ?>
    <div class="header">
        <h2>Card support platoons</h2>
        <div class='Points'>
            <div>
                <?=isset($formationCost[$formationNr])?$formationCost[$formationNr]:0?> points 
            </div>
        </div> 
    </div>
    <?php
    echo $CardBoxHTML[1];
}

if (isset($boxCost)){

$_SESSION["lastPage"] = $_SERVER['PHP_SELF'];}

?>
</div>
</form>


<script>
        function resetDropdownAndSubmit() {
            // Get the dropdown element from the other form
            const dropdown = document.getElementById('listNameList');
            
            // Reset the dropdown value to the default (first) option
            if (dropdown) {
                dropdown.selectedIndex = 0;
            }

            // Set the hidden input value to 0 to clear the session
            document.getElementById('loadedListName').value = 0;
            
            // Submit the current form (form1)
            document.getElementById('form1').submit();
        }

function setDropdownAndSubmit() {
    // Get the dropdown element from the other form
    const dropdown = document.getElementById('listNameList');
    
    // Reset the dropdown value to the default (first) option
    if (dropdown) {
        dropdown.selectedIndex = 0;
    } 
    
    // Submit the current form (form1)
    document.getElementById('form1').submit();
}
    
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.display === "none") {
      content.style.display = "inline-block";
    } else {
      content.style.display = "none";
    }
  });
}

// Select all the checkboxes, select elements, and buttons within the form
const selectElements = document.querySelectorAll('form input[type="checkbox"], form select, form button');

// Initialize the lsID with the first element's ID
let lsID = selectElements[0].id;

// Add change event listeners to all select elements and click event listeners to buttons
selectElements.forEach((element) => {
    // Check if the element is a checkbox or select element
    if (element.tagName === 'SELECT' || element.type === 'checkbox') {
        element.addEventListener('change', function () {
            handleElementChange(element);
        });
    }
    // Add click event listener for buttons
    else if (element.tagName === 'BUTTON') {
        element.addEventListener('click', function () {
            handleElementChange(element);
        });
    }
});

// Function to handle the change or click event
function handleElementChange(element) {
    // Update the lsID with the ID of the changed or clicked element
    lsID = element.id;
var parts = lsID.split("box");
lsID = parts[0];

        // Update the hidden input field's value with the lsID
        document.getElementById('lsID').value = lsID;

        // Store the lsID in Session Storage
        sessionStorage.setItem('lsID', lsID);
 
    // Update the hash based on the name of the last changed or clicked element
    window.location.hash = lsID + 'box';
}

// On page load, retrieve the value from Session Storage
lsID = sessionStorage.getItem('lsID');

// Update the hidden input field's value
if (lsID) {
document.getElementById('lsID').value = lsID;
window.location.hash = lsID + 'box';
}
</script>
<script>

var grids = document.querySelectorAll(".grid");
grids.forEach(function(grid) {
    var boxes = grid.querySelectorAll(".box");

    // Delay the measurement
    var gridHeight = 37;
    for (var i = 0; i < boxes.length; i++) {
        var box = boxes[i];
        box.style.gridRowEnd = "span 1";
        var height = box.scrollHeight;
        // Set the grid-row property based on the height
        for (var index = 1; index <  Math.floor(height/gridHeight)+3; index++) {
            
            if ((height+12) > ((gridHeight*index))) {
                box.style.gridRowEnd = "span " + (index+1);
                
            }
        }
    }
    });

</script>
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

<script>
    // Show/hide the button based on scroll position
    window.onscroll = function () {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("backToTopButton").style.display = "block";
        } else {
            document.getElementById("backToTopButton").style.display = "none";
        }
    };

    // Scroll to the top when the button is clicked
    document.getElementById("backToTopButton").onclick = function () {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    };

    function incrementNOF() {
    const nOFSelect = document.getElementById('nOF');
    const currentVal = parseInt(nOFSelect.value);

    // Increment by 1, ensuring it doesn't exceed the maximum value (3)
    if (currentVal < 3) {
        nOFSelect.value = currentVal + 1;
        nOFSelect.form.submit();  // Submit the form after incrementing
    }
}
</script>

</body>
</html>
