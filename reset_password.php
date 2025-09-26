<?php
// Connect to the database
include "sqlServerinfo.php";

// Check if the token is provided in the URL
if (!isset($_GET['token'])) {
    // Token not provided, redirect user to the reset password form
    header("Location: reset_password_form.php");
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

    // Validate password (add more validation as needed)
    if (empty($password)) {
        $error = "Password is required.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

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
        header("Location: login.php");
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
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <div>
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Reset Password</button>
        <?php if (isset($error)) echo "<p>$error</p>"; ?>
    </form>
</body>
</html>