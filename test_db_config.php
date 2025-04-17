<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection setup from db.php.
// This file will incorporate the configuration and create a PDO instance.
require_once __DIR__ . '/db.php';

try {
    echo "Connection successful!\n";
    
    // Test query on the products table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Number of products: " . $result['count'];
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
