<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Security.php';

$password = 'phini@1234';
$hash = Security::hashPassword($password);
echo "Password Hash for '$password': " . $hash . "\n";
