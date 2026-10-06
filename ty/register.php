<?php
session_start();

function is_valid_positive_user_id($value) {
    return is_numeric($value) && (int)$value > 0;
}

if (isset($_SESSION['user_id']) && !is_valid_positive_user_id($_SESSION['user_id'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

$username = "";
$email   = "";
$errors = []; 

include 'sqlServerinfo.php';

if (isset($_POST['reg_user'])) {
    // Validate Turnstile
$turnstile_response = $_POST['cf-turnstile-response'] ?? '';

$secret = "0x4AAAAAACBN9Yb7SljRhorAqrSwoRzntZs"; // från Cloudflare
// old key: 0x4AAAAAACBN9ShwM3NtVJ_4qFNScQVR1VI


$verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
$data = [
    'secret' => $secret,
    'response' => $turnstile_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

// Använd cURL för att skicka valideringen
$ch = curl_init($verify_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$ts_result = json_decode($response, true);

if (!$ts_result['success']) {
    $errors[] = "Failed human verification. Try again.";
}
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
          $userId = (int)$pdo->lastInsertId();
          if ($userId <= 0) {
              throw new Exception('Generated user id is invalid.');
          }

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