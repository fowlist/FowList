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
<link rel="stylesheet" href=https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css>
<!-- Top Navigation Menu -->
<div class="topnav">
    <h3>FOW List</h3>
<form  name='form' id='form' method="get" action="<?php echo $_SERVER['PHP_SELF'];?>">
<button type="submit" />Save/update</button>
  
  <!-- Navigation links (hidden by default) -->
  <div id="myLinks">
<a href="listPrint.php">View List</a>
<?php

echo " <label for='" . 'period' . "'>Choose the " . 'period' . "</label><br>";
dropdown($Periods,"","period","period",'period',false,"","",false,"","");   echo "<button type='submit' value=''onClick='" . 'period' . ".value =0; this.form.submit();'>Clear</button><br>";
echo " <label for='" . 'Nation' . "'>Choose the " . 'Nation' . "</label><br>";
dropdown($Nations,"","Nation","Nation",'Nation',true,"period",$_POST['period'],false,"",""); echo "<button type='submit' value=''onClick='" . 'Nation' . ".value =0; this.form.submit();'>Clear</button><br>";
echo " <label for='" . 'Book' . "'>Choose the " . 'Book' . "</label><br>";
dropdown($Books,"","Book","Book",'Book',true,"Nation",$_POST['Nation'],true,"period",$_POST['period']);echo "<button type='submit' value=''onClick='" . 'Book' . ".value =0; this.form.submit();'>Clear</button><br>";


?>
  </div>
  <!-- "Hamburger menu" / "Bar icon" to toggle the navigation links -->
  <a href="javascript:void(0);" class="icon" onclick="myFunction()">
    <i class="fa fa-bars"></i>
  </a>
</div>