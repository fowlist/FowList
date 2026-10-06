<?php
require "../sqlServerinfo.php"; // Laddar ditt $pdo

$username = 'fowlistadminjatchley'; // <--- ÄNDRA TILL DITT ÖNSKADE ANVÄNDARNAMN
$password = 'Budget7-Reassign-Urologist-Outshoot'; // <--- ÄNDRA TILL DITT ÖNSKADE LÖSENORD

// Skapa den säkra krypterade strängen
$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:user, :hash)");
    $stmt->execute([':user' => $username, ':hash' => $hash]);
    echo "Användaren har skapats framgångsrikt i PDO-databasen!";
} catch (PDOException $e) {
    echo "Kunde inte skapa användare: ";
}
