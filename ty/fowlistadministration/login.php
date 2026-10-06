<?php
require_once "api/session_init.php";

require "../sqlServerinfo.php";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {

                // --- NYTT: byt sessions-ID vid lyckad inloggning, skyddar mot session fixation ---
                session_regenerate_id(true);

                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;

                $dbStmt = $pdo->prepare("SELECT database_name FROM user_database_access WHERE user_id = :user_id");
                $dbStmt->execute([':user_id' => $user['id']]);
                $_SESSION['allowed_databases'] = $dbStmt->fetchAll(PDO::FETCH_COLUMN);

                header("Location: index.php");
                exit;
            } else {
                $error = "Fel användarnamn eller lösenord.";
            }
        } catch (PDOException $e) {
            // ÄNDRAT: logga det riktiga felet server-side, visa bara ett generiskt meddelande för användaren
            error_log("Login PDOException: " . $e->getMessage());
            $error = "Ett internt fel uppstod. Försök igen senare.";
        }
    } else {
        $error = "Vänligen fyll i alla fält.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Logga in</title></head>
<body>
    <form method="POST" style="margin: 100px auto; width: 300px; display: flex; flex-direction: column; gap: 10px;">
        <h2>Administration</h2>
        <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <input type="text" name="username" placeholder="Användarnamn" required>
        <input type="password" name="password" placeholder="Lösenord" required>
        <button type="submit">Logga in</button>
    </form>
</body>
</html>
