<?php
$_SESSION['success'] ="";
if (isset($_POST['login_user'])) {
    include_once 'sqlServerinfo.php';
    // Collect and sanitize user input
    $usernameLogin = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    // Validate input
    if (empty($usernameLogin)) { 
        $errors[] = "Username is required"; 
    }
    if (empty($password)) { 
        $errors[] = "Password is required"; 
    }

    if (empty($errors)) {
        try {
            // Fetch the user from the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$usernameLogin]);
            $user = $stmt->fetch();

            if ($user) {
                $storedPassword = $user['password'];
                $isMD5 = (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword));

                // Check if the password matches
                if (
                    ($isMD5 && md5($password) === $storedPassword) || // Check MD5
                    password_verify($password, $storedPassword) // Check password_hash
                ) {
                    // If MD5, upgrade the password hash
                    if ($isMD5) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->execute([$newHash, $user['id']]);
                    }

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['success'] = "You are now logged in.";

                    // Generate a unique token for remember-me functionality
                    $token = bin2hex(random_bytes(50));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                    // Insert the token into the `remember_tokens` table
                    $stmtToken = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmtToken->execute([$user['id'], $token, $expiresAt]);

                    // Set the token as a secure cookie
                    setcookie('remember_token', $token, [
                        'expires' => strtotime($expiresAt),
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']), // Secure only if HTTPS
                        'httponly' => true,
                        'samesite' => 'Strict',
                    ]);

                    // Redirect to the homepage
                    header('location: index.php');
                    exit;
                } else {
                    $errors[] = "Invalid username or password.";
                }
            } else {
                $errors[] = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred during login: " . $e->getMessage();
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
    // Unset the remember_token cookie
    if (isset($_COOKIE['remember_token'])) {
        // Invalidate the token in the database for the logged-in user.
        $token = $_COOKIE['remember_token'];
        
        // Update the user_tokens table to mark the token as invalid
        $stmt = $pdo->prepare("UPDATE remember_tokens SET expires_at = ? WHERE token = ?");
        $stmt->execute([date('Y-m-d H:i:s', strtotime('-1 days')),$token]);
        
        // Clear the cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600, // Expiry time, adjust as needed
            'path' => '/', // Path for which the cookie is available
            'secure' => isset($_SERVER['HTTPS']), // Secure only if HTTPS
            'httponly' => true,
            'samesite' => 'Strict', 
        ]);
    }
}

if (isset($_SESSION['user_id'])) {
    // User is logged in via session
    $userID = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    include_once 'sqlServerinfo.php';
} elseif (isset($_COOKIE['remember_token']) && !isset($_POST['logout_user'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $pdo->prepare("SELECT u.id, u.username FROM users u 
                           JOIN remember_tokens rt ON u.id = rt.user_id 
                           WHERE rt.token = ? AND rt.expires_at > NOW()");
    $stmt->execute([$token]);
    $result = $stmt->fetch();
//--- temporary
    if (!$result && $token) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
    
        if ($user) {
            // Migrate the old token
            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, date('Y-m-d H:i:s', strtotime('+30 days'))]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $userID = $_SESSION['user_id'];
            $username = $_SESSION['username'];
        }
    }
// --to here

    if ($result) {
        // Valid token, log the user in
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        $userID = $_SESSION['user_id'];
        $username = $_SESSION['username'];
    } elseif (empty($_SESSION['user_id'])) {
        // Invalid or expired token
        setcookie('remember_token', '', time() - 3600, '/'); // Clear the cookie

    }
}

$userID = $_SESSION['user_id']?? "";
$username = $_SESSION['username'] ?? "";
$saved_url = $_SERVER['REQUEST_URI'];
$listName =  $_POST['listName']??"";

if (isset($userID)) {
    if ($userID == 1) {
        echo "<!--{$username}-->\n";
        include_once "sqlServerinfo.php";
        $usersEval = $pdo->prepare("SELECT * FROM users");
        $usersEval->execute();
        $usersEval1 =[];
        foreach ($usersEval as $key => $value) {
            echo "<!--{$value["username"]}-->\n";
            $usersEval1[]=$value;
        }
        $numberOfUsers = $usersEval ->rowCount() ;
        echo "<!--{$numberOfUsers}-->\n";
        $allUsersListsQuery = $pdo->prepare("SELECT * FROM saved_lists WHERE saveDate=? ORDER BY name");
        $allUsersListsQuery->execute([date("Y-m-d",time())]);
        foreach ($allUsersListsQuery as $key => $list) {
            
            echo "<!--{$list["user_id"]}-->\n";
            foreach ($usersEval1 as $user) {
                if ($user["id"] == $list["user_id"]) {
                    echo "<!--{$user["username"]}----{$list["name"]}-->\n";
                }
            }
        }
        $numberOflists = $allUsersListsQuery ->rowCount() ;
        echo "<!--{$numberOflists}-->\n";
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
