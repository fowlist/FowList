<?php
// Define a function to log connection events
function logConnectionEvent($event) {
    $logFile = 'connection.log'; // Specify the path to your log file
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $event\n";

    // Append the log entry to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$servernameArray = [];
$phpUsernameArray = [];
$phpPasswordArray = [];
$dbnameArray = [];

$cookieSslOnly = true;
$cookieDomain = "fowlist.com";
$servername = "localhost";
//$servername = "";
//$servername = "";
$phpUsername = "root";
$phpPassword = "8ods398O";
$dbname = "ki12570048_Fowlist";
$dbnameFS = "ki12570048_Fowlist_firestorm";
$dbnameGW = "ki12570048_Fowlist_GW";
$userDB  = "ki12570048_users";
$dbnameEW = "dbs15153684";

$servernameArray["GW"] = $servername;
$phpUsernameArray["GW"] = $phpUsername;
$phpPasswordArray["GW"] = $phpPassword;
$dbnameArray["GW"] = $dbnameGW;

$servernameArray["MW"] = $servername;
$phpUsernameArray["MW"] = $phpUsername;
$phpPasswordArray["MW"] = $phpPassword;
$dbnameArray["MW"] = $dbname;

$servernameArray["LW"] = $servername;
$phpUsernameArray["LW"] = $phpUsername;
$phpPasswordArray["LW"] = $phpPassword;
$dbnameArray["LW"] = $dbname;

$servernameArray["EW"] = $servername;
$phpUsernameArray["EW"] = $phpUsername;
$phpPasswordArray["EW"] = $phpPassword;
$dbnameArray["EW"] = $dbnameEW;

$servernameArray["LL"] = $servername;
$phpUsernameArray["LL"] = $phpUsername;
$phpPasswordArray["LL"] = $phpPassword;
$dbnameArray["LL"] = $dbname;

$parts1 = parse_url($_SERVER['REQUEST_URI']);
$query1 = [];
if (isset($parts1['query'])) {
    parse_str($parts1['query'], $query1);
}

unset($query1);
unset($parts1);

$Periods  = [
            [ "period" => "MW",  "periodLong" => "Mid War"],
            [ "period" => "LW",  "periodLong" => "Late War"],
            [ "period" => "EW",  "periodLong" => "Early War"],
            [ "period" => "GW",  "periodLong" => "Great War"],
            [ "period" => "LL",  "periodLong" => "Late War Leviathans"]
            
];

// Create connection
if (!isset($pdo)) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $pdo = new PDO("mysql:host=$servername;dbname=$userDB;charset=utf8mb4", $phpUsername, $phpPassword, $options);
    } catch(PDOException $e) {
        
        echo "<!--". "User DB Connection failed: " . $e->getMessage() . "-->";
        $pdo = null;
    }
}


if (!isset($conn)) {
    $conn = new mysqli($servername, $phpUsername, $phpPassword, $dbname);
    $conn->set_charset("utf8mb4");
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);
}

// Check connection
if ($conn->connect_error) {
    $conn->close();
    $pdo = null;
    die("List Data Connection failed: " . $conn->connect_error);
}
