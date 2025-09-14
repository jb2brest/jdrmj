<?php
require_once 'config/database.php';

echo "<h1>Configuration des tables d'équipement</h1>";

try {
    // Lire le fichier SQL
    $sqlFile = 'database/add_equipment_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL introuvable: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Diviser le SQL en requêtes individuelles
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>Exécution des requêtes SQL...</h2>";
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        echo "<p><strong>Exécution de:</strong> " . htmlspecialchars(substr($query, 0, 100)) . "...</p>";
        
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Avertissement: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h2>✅ Configuration terminée !</h2>";
    echo "<p>Les tables d'équipement ont été créées avec succès.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
}
?>









