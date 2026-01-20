<?php
require_once 'classes/init.php';

try {
    $pdo = getPDO();
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM locations LIKE 'map_url'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE locations ADD COLUMN map_url VARCHAR(255) DEFAULT NULL AFTER description");
        echo "Column 'map_url' added to 'locations' table successfully.\n";
    } else {
        echo "Column 'map_url' already exists in 'locations' table.\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
