<?php



if (isset($_POST['login_user'])) {
    $usernameLogin = "";
    $password = "";
    $errors = array(); 
    $_SESSION['success'] ="";
    $usernameLogin = $_POST['username'];
    $password = $_POST['password'];
    $_SESSION['success'] .= "<!--{$usernameLogin}-->";

    if (empty($usernameLogin)) { array_push($errors, "Username is required"); }
    if (empty($password)) { array_push($errors, "Password is required"); }

    if (count($errors) == 0) {
        $password = md5($password);
        include_once "InitiatePDO.php";
        try {
            $UserQuery = $pdo->prepare("SELECT * FROM users WHERE username= ? AND password= ?");
            $UserQuery->execute(array($usernameLogin, $password));
            $results = $UserQuery->fetch();
            $curerntToken = "";
            if ($results) {
                
                foreach ($results as $thisKey => $thisValue) {
                    if ($thisKey == 'id') {
                        $_SESSION['user_id'] = $thisValue;
                        $_SESSION['success'] .= "<!--{$thisValue}-->";
                    }
                    
                    if ($thisKey == 'remember_token') {
                        $_SESSION['success'] .= "<!--{$thisValue}-->";
                        $curerntToken = $thisValue;
                    }
                }
                $_SESSION['username'] = $usernameLogin;
                $_SESSION['success'] .= "You are now logged in ". $results[0]["username"] . " sad";
                // Generate a unique token
                if (strlen($curerntToken)>2) {   
                    $token = $curerntToken;
                } else {
                    $token = bin2hex(random_bytes(50));
                    // Save the token in the database
                    $stmtTokenretrieve = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmtTokenretrieve->execute([$token, $_SESSION['user_id']]);
                }
                // Send the token to the client as a cookie
                setcookie('remember_token', $token, time() + (86400 * 30)); // 86400 = 1 day
            }else {
                array_push($errors, "Wrong username/password combination");
            }
        } catch (PDOException $e) {
            // Handle PDO exceptions (e.g., database connection errors, query errors)
            $_SESSION['success'] .= "Error: " . $e->getMessage();
        }
    }
}
foreach ($errors as $key => $value) {
    $_SESSION['success'] .= $value;
}

if (isset($_POST['logout_user'])) {
    $_SESSION = array();
    unset($_SESSION);
    // Destroy the session.
    session_destroy();
    $sessionStatus = session_start();
    // Unset the remember_token cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/'); // 3600 seconds = 1 hour ago
    }
}

if (($_SESSION['user_id']!="")) {

    $userID = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    include_once 'sqlServerinfo.php';
    echo "<!-- here:123{$userID}133-->";
} elseif (isset($_COOKIE['remember_token'])&&!isset($_POST['logout_user'])) {
    // Retrieve the token from the cookie
    $cookieToken = $_COOKIE['remember_token'];
    echo "<!--cookie: {$cookieToken}325-->";
    // Look up the user associated with the token
    include_once "InitiatePDO.php";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$cookieToken]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Start a session and log the user in

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $userID = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        include_once 'sqlServerinfo.php';
        echo "<!--231{$username}325-->";
    }
}

$userID = $_SESSION['user_id'];
$username = $_SESSION['username'];
$saved_url = $_SERVER['REQUEST_URI'];
$listName =  $_POST['listName'];

if (isset($userID)) {
    $usersListsList=[];
    $usersListsQuery = $pdo->prepare("SELECT * FROM saved_lists WHERE user_id=?");
    $usersListsQuery->execute([$userID]);
    $usersLists = $usersListsQuery;
    foreach ($usersLists as $key => $value) {
        
        $usersListsList[$key]["value"] = $value["id"];
        $usersListsList[$key]["description"] = $value["name"];
        $usersListsList[$key]["selected"] ="";
        $usersListsList[$key]["url"] = $value["url"];
    }
    if (isset($_POST['loadSelected'])&&isset($_POST["listNameList"])) {
        foreach ($usersListsList as $key => $value) {
            if ($_POST["listNameList"]==$value["value"]) {
                $conn->close();
                $pdo = null;
                $conn1 = Null;
                header("Location: " . $value["url"]);
                
                exit;
            }
        }
    }
    if (isset($_POST['save_url'])) {
        include_once "InitiatePDO.php";
        $saveCost = array_sum($formationCost)+$listCardCost;
        $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, name, cost) VALUES (?, ?, ?, ?)");
        $query1->execute([$userID, $saved_url, $listName, $saveCost]);
        //$query1 = "INSERT INTO saved_lists (user_id, url, name, cost) VALUES ('$userID', '$saved_url', '$listName', '$saveCost')";
        //mysqli_query($conn, $query1);
        echo "URL saved.";
    }
    if (isset($_POST['updateSelected'])&&isset($_POST["listNameList"])) {
        $saveCost = array_sum($formationCost)+$listCardCost;
        include_once "InitiatePDO.php";
        $query1 = $pdo->prepare("UPDATE saved_lists SET url = ? WHERE id =?");
        $query1->execute([$saved_url, $_POST["listNameList"]]);
        //$query1 = "UPDATE saved_lists SET url = '{$saved_url}' WHERE id ='{$_POST["listNameList"]}' ";
        //mysqli_query($conn, $query1);
        echo "URL Updated.";
    }
}
$pdo = null;
// Log connection closure event
logConnectionEvent("PBO user Connection closed");