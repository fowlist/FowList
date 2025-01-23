<<?php
include 'sqlServerinfo.php';
    $stmt = $pdo->prepare("SELECT id FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $token = bin2hex(random_bytes(50));
        $expiryDate = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
        $insert = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$user['id'], $token, $expiryDate]);
    }
?>
