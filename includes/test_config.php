<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Before including config.php\n";
var_dump(__DIR__);

require_once __DIR__ . '/../config.php';

echo "Step 2: After including config.php\n";
var_dump([
    'DB_HOST' => defined('DB_HOST'),
    'DB_NAME' => defined('DB_NAME'),
    'DB_USER' => defined('DB_USER'),
    'DB_PASS' => defined('DB_PASS')
]);