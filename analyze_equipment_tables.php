<?php
require_once 'classes/init.php';
$pdo = getPDO();

echo "=== Recherche de tables avec des équipements ===\n";

// Chercher toutes les tables qui pourraient contenir des équipements
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$equipmentTables = array_filter($tables, function($table) {
    return strpos($table, 'equipment') !== false || 
           strpos($table, 'tool') !== false || 
           strpos($table, 'kit') !== false ||
           strpos($table, 'gear') !== false ||
           strpos($table, 'item') !== false;
});

foreach ($equipmentTables as $table) {
    echo "Table: $table\n";
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "  - Nombre d'enregistrements: $count\n";
    
    // Vérifier s'il y a des IDs qui correspondent
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id FROM $table WHERE id IN (2, 5, 6, 7, 8) LIMIT 5");
        $matches = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($matches)) {
            echo "  - IDs correspondants trouvés: " . implode(', ', $matches) . "\n";
        }
    }
    echo "\n";
}

echo "=== Vérification des données dans starting_equipment avec type_filter ===\n";
$stmt = $pdo->query("
    SELECT type, type_id, type_filter, nb 
    FROM starting_equipment 
    WHERE type = 'outils' AND type_id IN (2, 5, 6, 7, 8)
    LIMIT 10
");
while ($row = $stmt->fetch()) {
    echo "- Type: " . $row['type'] . ", ID: " . $row['type_id'] . ", Filter: '" . $row['type_filter'] . "', Nb: " . $row['nb'] . "\n";
}

echo "\n=== Vérification des données dans starting_equipment avec type_filter non vide ===\n";
$stmt = $pdo->query("
    SELECT type, type_id, type_filter, nb 
    FROM starting_equipment 
    WHERE type_filter IS NOT NULL AND type_filter != ''
    LIMIT 10
");
while ($row = $stmt->fetch()) {
    echo "- Type: " . $row['type'] . ", ID: " . $row['type_id'] . ", Filter: '" . $row['type_filter'] . "', Nb: " . $row['nb'] . "\n";
}
?>




