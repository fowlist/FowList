<?php
// cleanup_expired_tokens.php

// Include database connection
include_once 'sqlServerinfo.php';

try {
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at <= NOW()");
    $stmt->execute();
    echo "Expired tokens cleaned up successfully at " . date('Y-m-d H:i:s') . PHP_EOL;
} catch (PDOException $e) {
    echo "Error cleaning up expired tokens: " . $e->getMessage() . PHP_EOL;
}
?>
