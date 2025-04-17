<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration file which defines the DB constants.
require_once __DIR__ . '/db.config';

try {
    // Build the DSN string using the database constants from db.config.
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Create a PDO instance with the configuration values.
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
    
    echo "Connection successful!\n";
    
    // Test query: counts the number of products.
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Number of products: " . $result['count'];
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
