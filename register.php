<?php
session_start();

$username = "";
$email   = "";
$errors = array(); 

include 'sqlServerinfo.php';

if (isset($_POST['reg_user'])) {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password_1 = $_POST['password_1'];
  $password_2 = $_POST['password_2'];

 if (empty($email)) { $email="na"; }
 if (empty($username)) { array_push($errors, "Username is required"); }
 $token = bin2hex(random_bytes(50));
// if (empty($securityQuestion)) { array_push($errors, "Answer the question to verify you are a human"); }
 //if (($securityQuestion != "Duncan Gosling")) { array_push($errors, "Answer the question to verify you are a human"); }

 if (empty($password_1)) { array_push($errors, "Password is required"); }
 if ($password_1 != $password_2) { array_push($errors, "The two passwords do not match"); }

 $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
 $stmt->execute([$username]);
 $user = $stmt->fetch();

// $user_check_query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
// $result = mysqli_query($conn, $user_check_query);
// $user = mysqli_fetch_assoc($result);
 
 if ($user) {
   if ($user['username'] === $username) {
     array_push($errors, "Username already exists");
   }
 }

 if (count($errors) == 0) {
       $password = md5($password_1);

       $stmt = $pdo->prepare("INSERT INTO users (username, email, password, remember_token) 
       VALUES(?, ?, ?, ?)");
       $stmt->execute([$username, $email, $password, $token]);

       $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
       $stmt->execute([$username]);
       $user = $stmt->fetch();

//       $query = "INSERT INTO users (username, email, password, remember_token) 
//                        VALUES('$username', '$email', '$password', '$token')";
//       mysqli_query($conn, $query);
//       $user_check_query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
//       $result = mysqli_query($conn, $user_check_query);
//       $user = mysqli_fetch_assoc($result);


       $_SESSION['user_id'] = $user["id"];
       $_SESSION['username'] = $username;

       $_SESSION['success'] = "You are now logged in";
       header('location: index.php');
 } else {
    foreach ($errors as $key => $value) {
      echo $value. "<br>";
    }
 }
}
$conn->close();
$pdo = null;