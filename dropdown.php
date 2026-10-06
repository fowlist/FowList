<?php 

function dropdown($source,$valueindex,$collumnvalue,$collumnText,$globalVar,$condition1enable,$condition1_1,$condition1_2,$condition2enable,$condition2_1,$condition2_2,$query){
$output ="";
if ($source->num_rows > 0) {
    $output .= "
    <select name='" . $globalVar . "' id='" . $globalVar . "' onchange='if(this.value != 0) { this.form.submit(); }'>
        <option value='' selected disabled hidden>Choose here</option>";
    // output data of each row
    foreach ($source as $row) {
        if  ((($row[$condition1_1] == $condition1_2)||!$condition1enable)&&(($row[$condition2_1] == $condition2_2)||!$condition1enable)) { 
            $output .=  "
        <option " . (($row[$collumnvalue] === $query[$globalVar]) ? "selected='selected' ": "") . "value='{$row[$collumnvalue]}'>{$row[$collumnText]}</option>";
        }
    }
    mysqli_data_seek($source ,0);
    $output .=  "
    </select>";
} else {
    $output .=  "
    0 results";
    }
return $output;
}
