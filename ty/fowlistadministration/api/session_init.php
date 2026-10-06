<?php
// session_init.php
session_name('FOW_ADMIN_SESS'); 
ini_set('session.save_path', __DIR__ . '/../../sessions_tmp');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/ty/fowlistadministration/', // Håll kvar denna om alla filer ligger här
    'domain' => '',                         // Tom = hanterar fowlist.com automatiskt
    'secure' => true,                       // Kräver HTTPS (vilket du har!)
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

