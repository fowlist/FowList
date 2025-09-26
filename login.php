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
                        'expires' => strtotime('+30 days'),
                        'path' => '/',
                        'domain' => '.fowlist.com', // Allows sharing across all subdomains
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict',
                    ]);

                    // Redirect to the homepage
                    // header('location: index.php');
                    // exit;
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


    if ($result) {
        // Valid token, log the user in
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        $userID = $_SESSION['user_id'];
        $username = $_SESSION['username'];
    } 
}

$userID = $_SESSION['user_id']?? "";
$username = $_SESSION['username'] ?? "";
$saved_url = $_POST['updated_url'] ?? "";
$listName =  $_POST['listName']??"";

if (isset($userID)) {
 

    if (isset($_POST['save_url'])) {
        echo "saving";
        $saved_url = "index.php?{$linkQuery}&loadedListName=" . ($listName??"") .$costArrayStrig;

        $selectedEvent = $_POST['listEventList'] ?? null;
        $customEvent   = trim($_POST['customEvent'] ?? "");
        if ($customEvent !== "") {
            $associatedEvent = $customEvent;   // free text overrides
        } else {
            $associatedEvent = $selectedEvent; // fallback to chosen
        }

        $saved_url_to_list = "listPrintGet.php?{$linkQuery}&loadedListName=" . ($listName??"") .$costArrayStrig;
        $saveCost = array_sum($formationCost)+$listCardCost;

        $query1 = $pdo->prepare("INSERT INTO saved_lists (user_id, url, urlToList, name, cost, saveDate, tournament) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $query1->execute([$userID, $saved_url, $saved_url_to_list, $listName, $saveCost, date("Y-m-d",time()),$associatedEvent]);
        //$query1 = "INSERT INTO saved_lists (user_id, url, name, cost) VALUES ('$userID', '$saved_url', '$listName', '$saveCost')";
        //mysqli_query($conn, $query1);
        $newId = $pdo->lastInsertId();
        echo "URL saved.";
        $query['loadedListName'] = $listName;
        $_POST["loadedListName"] = $listName;
        $_SESSION['loadedListNumber'] =$newId;
      }

      if (isset($_POST['updateSelected'])&&isset($_POST["listNameList"])) {
        $saved_url = "index.php?{$linkQuery}&loadedListName=" . ($_POST["listNameList"]??"") .$costArrayStrig;
        $saved_url_to_list = "listPrintGet.php?{$linkQuery}&loadedListName=" . ($_POST["listNameList"]??"") .$costArrayStrig;
        $associatedEvent = "";
        $usersEventList = $usersEventList??$_SESSION["usersEEventList"];
        if (!empty($usersEventList)) {
          foreach ($usersEventList as $eachEvent) {
            if ($eachEvent["value"]==$_POST["listEventList"]) {
              $associatedEvent  = $eachEvent["description"];
            }
          }
        }
        $saveCost = array_sum($formationCost)+$listCardCost;
        $query1 = $pdo->prepare("UPDATE saved_lists SET url = ?, saveDate = ?, urlToList =?, cost=?, tournament=? WHERE id =?");
        $query1->execute([$saved_url, date("Y-m-d",time()),$saved_url_to_list, $saveCost, $associatedEvent, $_POST["listNameList"]]);
        //$query1 = "UPDATE saved_lists SET url = '{$saved_url}' WHERE id ='{$_POST["listNameList"]}' ";
        //mysqli_query($conn, $query1);
        $query['loadedListName'] = $_POST["listNameList"];
        $_POST["loadedListName"] = $_POST["listNameList"];

      }
        // -- all general tables 

            include_once "sqlServerinfo.php";
            $usersListsQuery = $pdo->prepare("SELECT * FROM saved_lists WHERE user_id=? AND url NOT LIKE '%pd=TY%' ORDER BY name");
            $usersListsQuery->execute([$userID]);
            $usersEventList=[];
            $usersListsList=[];
            foreach ($usersListsQuery as $key => $value) {
    
                $usersListsList[$key]["value"] = $value["id"];
                $usersListsList[$key]["description"] = $value["name"];
                $usersListsList[$key]["event"] = ($value["tournament"]=="0"||$value["tournament"]==""||$value["tournament"]=="none"?"":$value["tournament"]);
                $parts = parse_url($value["url"]);       // splits into path + query
                parse_str($parts['query'], $queryArray); // makes assoc array

                $usersListsList[$key]["period"] = $queryArray['pd'] ?? "";
                $usersListsList[$key]["nation"] = $queryArray['ntn'] ?? "";
                $usersListsList[$key]["FormationCode"] = $queryArray['F1'] ?? "";

                if (isset($_SESSION['loadedListNumber'])&&$_SESSION['loadedListNumber'] == $value["id"]) {
                    $usersListsList[$key]["selected"] =1;
                } else {
                    $usersListsList[$key]["selected"] ="";
                }
                $usersListsList[$key]["url"] = $value["url"];
                $eventValue = htmlspecialchars(trim(strtolower( $value["tournament"]??"")));
    
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
    if (isset($_POST['loadSelected'])) {
        
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
