<?php
require_once "api/session_init.php";


// Töm alla sessionsvariabler
$_SESSION = [];

// Förstör själva sessions-cookien i webbläsaren
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Förstör sessionen på servern
session_destroy();

// Skicka tillbaka användaren till inloggningssidan
header("Location: login.php");
exit;