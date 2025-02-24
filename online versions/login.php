<?php



if (isset($_POST['login_user'])) {
    include_once 'sqlServerinfo.php';
    $usernameLogin = "";
    $password = "";
    $errors = array(); 
    $_SESSION['success'] ="";
    $usernameLogin = $_POST['username'];
    $password = $_POST['password'];

    if (empty($usernameLogin)) { array_push($errors, "Username is required"); }
    if (empty($password)) { array_push($errors, "Password is required"); }

    if (count($errors) == 0) {
        $password = md5($password);
        try {
            $UserQuery = $pdo->prepare("SELECT * FROM users WHERE username= ? AND password= ?");
            $UserQuery->execute(array($usernameLogin, $password));
            $results = $UserQuery->fetch();
            $curerntToken = "";
            if ($results) {
                
                foreach ($results as $thisKey => $thisValue) {
                    if ($thisKey == 'id') {
                        $_SESSION['user_id'] = $thisValue;
                    }
                    
                    if ($thisKey == 'remember_token') {
                        $curerntToken = $thisValue;
                    }
                }
                $_SESSION['username'] = $usernameLogin;
                $_SESSION['success'] .= "You are now logged in ";
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
                setcookie('remember_token', $token, [
                    'expires' => time() + (86400 * 30), // Expiry time, adjust as needed
                    'path' => '/', // Path for which the cookie is available
                    'domain' => 'fowlist.com', // Change 'example.com' to your domain
                    'samesite' => 'Strict', // SameSite option: 'Strict', 'Lax', or 'None'
                ]);
            }else {
                array_push($errors, "Wrong username/password combination");
            }
        } catch (PDOException $e) {
            // Handle PDO exceptions (e.g., database connection errors, query errors)
            $_SESSION['success'] .= "Error: " . $e->getMessage();
        }
    }
}
if (isset($errors)) {
foreach ($errors as $key => $value) {
    $_SESSION['success'] .= $value;
}
    
}

if (isset($_POST['logout_user'])) {
    $_SESSION = array();
    unset($_SESSION);
    // Destroy the session.
    session_destroy();
    $sessionStatus = session_start();
    // Unset the remember_token cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', 
        [
            'expires' => time() - (3600), // Expiry time, adjust as needed
            'path' => '/', // Path for which the cookie is available
            'domain' => 'fowlist.com', // Change 'example.com' to your domain
            'samesite' => 'Strict', // SameSite option: 'Strict', 'Lax', or 'None'
        ]); // 3600 seconds = 1 hour ago
    }
}

if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    include_once 'sqlServerinfo.php';
} elseif (isset($_COOKIE['remember_token'])&&!isset($_POST['logout_user'])) {
    // Retrieve the token from the cookie
    $cookieToken = $_COOKIE['remember_token'];
    // Look up the user associated with the token
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
    }
}

$userID = isset($_SESSION['user_id'])?$_SESSION['user_id']:"";
$username = isset($_SESSION['username'])?$_SESSION['username']:"";
$saved_url = $_SERVER['REQUEST_URI'];
$listName =  isset($_POST['listName'])?$_POST['listName']:"";

if (isset($userID)) {
    if ($userID == 1) {
        echo "<!--{$username}-->\n";
    }

        // -- all general tables 
    if (empty($_SESSION["usersListsList"])||isset($_POST['refreshSelected'])) {
        unset($_POST['refreshSelected']);
        include_once "sqlServerinfo.php";
        $usersListsQuery = $pdo->prepare("SELECT * FROM saved_lists WHERE user_id=? ORDER BY name");
        $usersListsQuery->execute([$userID]);
        $usersEventList=[];
        $usersListsList=[];
        foreach ($usersListsQuery as $key => $value) {

            $usersListsList[$key]["value"] = $value["id"];
            $usersListsList[$key]["description"] = $value["name"] .  ($value["tournament"]=="0"||$value["tournament"]=="none"?"":": " .$value["tournament"]);
            if (isset($_SESSION['loadedListNumber'])&&$_SESSION['loadedListNumber'] == $value["id"]) {
                $usersListsList[$key]["selected"] =1;
            } else {
                $usersListsList[$key]["selected"] ="";
            }
            $usersListsList[$key]["url"] = $value["url"];
            $eventValue = htmlspecialchars(trim(strtolower( $value["tournament"])));

            if (!in_array($eventValue,array_column($usersEventList,"value"))) {
                $usersEventList[$key]["value"] = htmlspecialchars(strtolower( $value["tournament"]));
                $usersEventList[$key]["description"] = trim($value["tournament"]);
                if (isset($_SESSION['listEventList'])&&$_SESSION['listEventList'] == $eventValue) {
                    $usersEventList[$key]["selected"] =1;
                } else {
                    $usersEventList[$key]["selected"] ="";
                }
                
            }
        }

        $usersListsQuery = null;
        $_SESSION["usersListsList"] = $usersListsList;
        $_SESSION["usersEEventList"] = $usersEventList;
        
    } else {
        $usersListsList = $_SESSION["usersListsList"];
        $usersEventList = $_SESSION["usersEEventList"];

    }

    foreach ($usersListsList as $key => $value) {
        if (isset($_SESSION['loadedListNumber'])&&$_SESSION['loadedListNumber'] == $value["value"]) {
            $usersListsList[$key]["selected"] =1;
        } else {
            $usersListsList[$key]["selected"] ="";
        }
    }

    foreach ($usersEventList as $key => $value) {
        if (isset($_SESSION['listEventList'])&&$_SESSION['listEventList'] == $value["value"]) {
            $usersEventList[$key]["selected"] =1;
        } else {
            $usersEventList[$key]["selected"] ="";
        }
    }

/*
    $usersListsList=[];
    $usersEventList=[];
    $usersListsQuery = $pdo->prepare("SELECT * FROM saved_lists WHERE user_id=? ORDER BY name");
    $usersListsQuery->execute([$userID]);
    $usersLists = $usersListsQuery;
    foreach ($usersLists as $key => $value) {
        
        $usersListsList[$key]["value"] = $value["id"];
        $usersListsList[$key]["description"] = $value["name"] .  ($value["tournament"]=="0"||$value["tournament"]=="none"?"":": " .$value["tournament"]);
        if (isset($_SESSION['loadedListNumber'])&&$_SESSION['loadedListNumber'] == $value["id"]) {
            $usersListsList[$key]["selected"] =1;
        } else {
            $usersListsList[$key]["selected"] ="";
        }
        
        $usersListsList[$key]["url"] = $value["url"];
    }
    */
    if (isset($_POST['loadSelected'])&&isset($_POST["listNameList"])) {
        
        foreach ($usersListsList as $key => $value) {
            if ($_POST["listNameList"]==$value["value"]) {
                $_SESSION['loadedListNumber'] = $value["value"];
                $pdo = null;
                $conn->close();
                header("Location: " . $value["url"]."&loadedListName={$value["description"]}");
                
                exit;
            }
        }
    }
}
