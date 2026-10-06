<?php
if ($_SERVER['HTTP_HOST'] = "localhost") {
    $cookieAdress = "localhost";
} else {
    $cookieAdress = "fowlist.com";
}
include_once 'sqlServerinfo.php';

if (isset($_POST['login_user'])) {
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
                    'domain' => '{$cookieAdress}', // Change 'example.com' to your domain
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
        setcookie('remember_token', '', time() - 3600, '/'); // 3600 seconds = 1 hour ago
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
                $pdo = null;
                $conn->close();
                header("Location: " . $value["url"]);
                
                exit;
            }
        }
    }
}
