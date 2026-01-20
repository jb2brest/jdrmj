<?php
require_once 'classes/init.php';

try {
    $pdo = getPDO();
    echo "Starting migration...\n";

    // 1. Create locations table
    $sqlLocations = "CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        region_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
        UNIQUE KEY unique_location_per_region (name, region_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sqlLocations);
    echo "Table 'locations' created or already exists.\n";

    // 2. Add location_id to places table
    // Check if column exists first to avoid error
    $stmt = $pdo->query("SHOW COLUMNS FROM places LIKE 'location_id'");
    if ($stmt->rowCount() == 0) {
        $sqlAlter = "ALTER TABLE places 
                     ADD COLUMN location_id INT DEFAULT NULL AFTER region_id,
                     ADD CONSTRAINT fk_places_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
        $pdo->exec($sqlAlter);
        echo "Column 'location_id' added to 'places' table.\n";
    } else {
        echo "Column 'location_id' already exists in 'places' table.\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
