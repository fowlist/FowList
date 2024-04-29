<?php
// Include database connection
$beta = ((is_numeric(strpos($_SERVER['PHP_SELF'],"Beta")))? "Beta": "");
include "sqlServerinfo{$beta}.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email submitted via the form
    $email = $_POST['email'];

    // Check if the email exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate a random token for password reset
        $token = bin2hex(random_bytes(32));

        // Store the token in the database along with the user's email
        $insert_stmt = $pdo->prepare("INSERT INTO password_reset (email, token) VALUES (:email, :token)");
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':token', $token);
        $insert_stmt->execute();

        // Construct the password reset link with the token
        $reset_link = "http://fowlist.com/reset_password.php?token=$token";

        // Send an email with the password reset link to the user
        $to = $email;
        $subject = "Password Reset Link";
        $message = "Click the link below to reset your password:\n$reset_link";
        $headers = "From: feedback@fowlist.com";
        mail($to, $subject, $message, $headers);

        // Redirect the user to a confirmation page or display a success message
        header("Location: reset_link_sent.php");
        exit;
    } else {
        // If the email doesn't exist in the database, inform the user
        $error = "Email not found. Please enter a valid email address.";
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
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit">Reset Password</button>
        <?php if (isset($error)) echo "<p>$error</p>"; ?>
    </form>
</body>
</html>