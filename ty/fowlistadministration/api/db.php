<?php

$host = 'database-5019179349.webspace-host.com';
$dbname = "dbs15060252";
$user = 'dbu5616992';
$pass = 'Shank-Overstay-Audition8';

$db = new mysqli($host, $user, $pass, $dbname);

if ($db->connect_error) {

    die($db->connect_error);
    
}

$db->set_charset('utf8mb4');

