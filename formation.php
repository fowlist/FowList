<?php

$boxCost[]=0;
    foreach ($boxNrs as $row){ 
        foreach ($Formation_DB as $row2) {
            if($row["box_nr"] == $row2["box_nr"]){
        echo  "
                <li>
                    <div class='box'>" . $row["box_type"] . "<br>";
                echo "
                        <input";
                if ($row2["platoon"] == $_POST[$_POST['formation'] .  $row2["box_nr"]]) { 
                    echo " checked";
                };
                echo " type='checkbox' name='" . $_POST['formation'] .  $row2["box_nr"] . "' class='" . $_POST['formation'] . $row2["box_nr"] . "' value='". $row2["platoon"] ; ?>' onchange="$('.<?php echo $_POST['formation'] . $row2["box_nr"];?>').not(this).prop('checked', false);this.form.submit();">    
                        <div>
                            <?php;
                foreach ($images as $row3) if ($row3["code"] == $row2["platoon"]) echo "<img src='img/" . $row3["image"] . ".svg'><br>
                            ";
                mysqli_data_seek($images ,0);  
                echo "<b>" . $row2["title"] . "</b><br>
                            " . $row2["platoon"] . "<br>";
             
// ------ Config of platoon -------------                       
                             
                if (($platoonConfig->num_rows > 0)&&($row2["platoon"] == $_POST[$_POST['formation'] .  $row2["box_nr"]])) {
                    echo "
                            <select name='" . "Form01-" . $row["box_nr"] . $row2["platoon"] . "' class='" . $_POST['formation'] . $row["box_nr"] . $row2["platoon"] . "' onchange=' { this.form.submit(); }'>
                                <option value=''  >Choose here</option>";
// output data of each row
                    while($row4 = $platoonConfig->fetch_assoc()) {
                        if  ($row4["platoon"] == $row2["platoon"]) { echo "
                                    <option ";
                            if (str_replace("\n", " ", $row4["configuration"]) === $_POST["Form01-" . $row["box_nr"] . $row4["platoon"]] ) { 
                                echo "selected ";
                            };
                            echo "value='" . str_replace("\n", " ", $row4["configuration"]). "'>" . $row4["configuration"] . "</option>";
                        }
                        
                    }
                    mysqli_data_seek($platoonConfig ,0);
                    echo "
                            </select>
                            <br>
                            <br>";
                }    
// -------- To Here ----------   
                        echo "
                        </div><br>";

        echo "
                    </div><br>";

// ------ Options of platoon -------------                       

$platoonHaveOptions = FALSE;
$platoonCost = 0;

                if (($platoonOptionHeaders->num_rows > 0)&&($row2["platoon"] == $_POST[$_POST['formation'] .  $row2["box_nr"]])) {
                    foreach($platoonOptionHeaders as $row4) {
                        if  ($row4["code"] == $row2["platoon"]) $platoonHaveOptions =TRUE;
                    }
                    mysqli_data_seek($platoonOptionHeaders ,0);
                    if  ($platoonHaveOptions){
                        foreach ($platoonOptionHeaders as $key5 => $row5) {
                            if  ($row5["code"] == $row2["platoon"]){
                                echo "<br>" . $row5["description"] . "<br>
                            <select name='" . "Form01-" . $row["box_nr"] . $row2["platoon"] . "Option" .$key5. "' class='" . $_POST['formation'] . $row["box_nr"] . $row2["platoon"] . "Option" . "' onchange=' this.form.submit(); '>
                                <option value=''  >No option selected</option>";

                                foreach($platoonOptionOptions as $row4) {
                                    if  (($row4["code"] == $row2["platoon"])&&($row5["description"] == $row4["description"])) { echo "
                                    <option ";
                                        if (str_replace("\n", " ", $row4["optionSelection"]) === $_POST["Form01-" . $row["box_nr"] . $row4["code"] . "Option" .$key5  ] ) { 
                                            $platoonCost += $row4["price"];
                                            echo "selected ";
                                        };
                                    echo "value='" . str_replace("\n", " ", $row4["optionSelection"]). "'>" . $row4["optionSelection"] . "</option>";
                                    }

                                }
                                                                echo "
                            </select>
                            <br>";
                                mysqli_data_seek($platoonOptionOptions ,0);
                            }
                        }
                    }
                    mysqli_data_seek($platoonOptionHeaders ,0);
                }
                        echo "

                            <br>";     
                
                
                
// -------- To Here ----------
            }
        }                
                


        while($row4 = $platoonConfig->fetch_assoc()) {
            if (str_replace("\n", " ", $row4["configuration"]) === $_POST["Form01-" . $row["box_nr"] . $row4["platoon"]] ) 
            { 
                $boxCost[$row["box_nr"]] = $platoonCost +=$row4["cost"];
                echo "
                <div class='Points'>
                      <div>
                        " . $platoonCost . " points
                      </div>
                    </div>";
            };
        }
        mysqli_data_seek($platoonConfig ,0);                    
                    echo "
                </li>
                ";
    }
echo "</ul>
            </li>
        " . array_sum($boxCost);
?>