<?php
// Connect to the database
$beta = ((is_numeric(strpos($_SERVER['PHP_SELF'],"Beta")))? "Beta": "");
include "sqlServerinfo{$beta}.php";

// Check if the token is provided in the URL
if (!isset($_GET['token'])) {
    // Token not provided, redirect user to the reset password form
    header("initiate_reset_password.php");
    exit;
}

$token = $_GET['token'];

// Verify the token and fetch the associated user
$stmt = $pdo->prepare("SELECT * FROM password_reset WHERE token = :token");
$stmt->bindParam(':token', $token);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Invalid or expired token, handle accordingly
    echo "Invalid or expired token.";
    exit;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $password_2 = $_POST['password_2'];
// Validate password (add more validation as needed)
    if ($password != $password_2) { $error = "The two passwords do not match"; } 
    elseif (empty($password)) {
        $error = "Password is required.";
    } else {
        // Hash the password
        //$hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = md5($password);
        // Update user's password in the database
        $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        $update_stmt->bindParam(':password', $hashed_password);
        $update_stmt->bindParam(':email', $user['email']);
        $update_stmt->execute();

        // Delete the token from the database to prevent reuse
        $delete_stmt = $pdo->prepare("DELETE FROM password_reset WHERE token = :token");
        $delete_stmt->bindParam(':token', $token);
        $delete_stmt->execute();

        // Redirect user to login page or any other page
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token) ?>">
        <div>
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label>Confirm password</label>
            <input type="password" id="password_2" name="password_2" required>
       </div>

        <button type="submit">Reset Password</button>
        <?php if (isset($error)) echo "<p>$error</p>"; ?>
    </form>
</body>
</html>