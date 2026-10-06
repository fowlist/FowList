<?php
// Define a function to log connection events
function logConnectionEvent($event) {
    $logFile = 'connection.log'; // Specify the path to your log file
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $event\n";

    // Append the log entry to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$servername["TY"] = "database-5019179349.webspace-host.com";
$phpUsername["TY"] = "dbu5616992";
$phpPassword["TY"] = "Shank-Overstay-Audition8";
$dbname["TY"] = "dbs15060252";

$servername["AT"] = "database-5019179355.webspace-host.com";
$phpUsername["AT"] = "dbu819053";
$phpPassword["AT"] = "Disloyal-Unmoving9-Giving";
$dbname["AT"] = "dbs15060257";

$servername["CpC"] = "database-5019179358.webspace-host.com";
$dbname["CpC"] = "dbs15060259";
$phpUsername["CpC"] = "dbu3766937";
$phpPassword["CpC"] = "Stifling-Estate-Uninsured3";

$servernameUserDB = "database-5019179363.webspace-host.com";
$phpUsernameUserDB = "dbu460697";
$phpPasswordUserDB = "Uptown-Collector-Swampland7";
$userDB  = "dbs15060263";

$parts1 = parse_url($_SERVER['REQUEST_URI']);
$query1 = [];
if (isset($parts1['query'])) {
    parse_str($parts1['query'], $query1);
}




$Periods  = [
            [ "period" => "TY",  "periodLong" => "Team Yankee"],
            [ "period" => "AT",  "periodLong" => "Asian Theatre (Custom Lists)"],
            [ "period" => "CpC",  "periodLong" => "Checkpoint Charlie"]
            
        ];

// Create connection
if (!isset($pdo)) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $pdo = new PDO("mysql:host=$servernameUserDB;dbname=$userDB;charset=utf8mb4", $phpUsernameUserDB, $phpPasswordUserDB, $options);
    } catch(PDOException $e) {
        $pdo = null;
        echo "<!--". "User DB Connection failed: " . $e->getMessage() . "-->";
    }
}



    $dbname1 = $dbname["TY"];
    $servername1 = $servername["TY"];
    $phpUsername1 = $phpUsername["TY"];
    $phpPassword1 = $phpPassword["TY"];




if (($query1['pd']??"") == "AT"||($query['pd']??"") == "AT") {
    
    $dbname1 = $dbname["AT"];
    $servername1 = $servername["AT"];
    $phpUsername1 = $phpUsername["AT"];
    $phpPassword1 = $phpPassword["AT"];

}

if (($query1['pd']??"") == "CpC"||($query['pd']??"") == "CpC") {
    $dbname1 = $dbname["CpC"];
    $servername1 = $servername["CpC"];
    $phpUsername1 = $phpUsername["CpC"];
    $phpPassword1 = $phpPassword["CpC"];
}
unset($query1);
unset($parts1);

if (!isset($conn)) {
    $conn = new mysqli($servername1, $phpUsername1, $phpPassword1, $dbname1);
    $conn->set_charset("utf8mb4");
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);
}

// Check connection
if ($conn->connect_error) {
    $conn->close();
    $pdo = null;
    die("List Data Connection failed: " . $conn->connect_error);
}

