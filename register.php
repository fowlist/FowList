<?php
session_start();

$username = "";
$email   = "";
$errors = []; 

include 'sqlServerinfo.php';

if (isset($_POST['reg_user'])) {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password_1 = $_POST['password_1'];
  $password_2 = $_POST['password_2'];

    // Validate input
    if (empty($username)) { 
      $errors[] = "Username is required"; 
  }
  if (empty($password_1)) { 
      $errors[] = "Password is required"; 
  }
  if ($password_1 !== $password_2) { 
      $errors[] = "The two passwords do not match"; 
  }
  if (!empty($email)&&!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
      $errors[] = "Invalid email format"; 
  }

  // Check if the username already exists
  $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
  $stmt->execute([$username]);
  if ($stmt->fetch()) {
      $errors[] = "Username already exists";
  }

  // If no errors, proceed with registration
  if (empty($errors)) {
      try {
          // Hash the password securely
          $password = password_hash($password_1, PASSWORD_DEFAULT);

          // Insert the new user into the database
          $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
          $stmt->execute([$username, $email, $password]);

          // Fetch the new user ID
          $userId = $pdo->lastInsertId();

          // Generate a unique token for remember-me functionality
          $token = bin2hex(random_bytes(50));
          $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

          // Insert the token into the `remember_tokens` table
          $stmtToken = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
          $stmtToken->execute([$userId, $token, $expiresAt]);

          // Set the token as a secure cookie
          setcookie('remember_token', $token, [
              'expires' => strtotime($expiresAt),
              'path' => '/',
              'secure' => isset($_SERVER['HTTPS']), // Secure only if HTTPS
              'httponly' => true,
              'samesite' => 'Strict',
          ]);

          // Set session variables
          $_SESSION['user_id'] = $userId;
          $_SESSION['username'] = $username;
          $_SESSION['success'] = "You are now registered and logged in.";

          // Redirect to the homepage
          header('location: index.php');
          exit;

      } catch (Exception $e) {
          $errors[] = "An error occurred during registration: " . $e->getMessage();
      }
  }

  // Display any errors
  foreach ($errors as $error) {
      echo $error . "<br>";
  }
}
$conn->close();
$pdo = null;