<?php
/**
 * Script de configuration des tables temporaires PT_
 * Exécute le SQL pour créer les tables nécessaires
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

echo "<h1>Configuration des tables temporaires PT_</h1>";

try {
    $pdo = getPDO();
    
    // Lire le fichier SQL
    $sql = file_get_contents('database/create_pt_tables.sql');
    
    if ($sql === false) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Exécuter le SQL
    $pdo->exec($sql);
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Tables PT_ créées avec succès !";
    echo "</div>";
    
    // Vérifier que les tables existent
    $tables = ['PT_characters', 'PT_equipment_choices', 'PT_capabilities'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green; margin: 5px 0;'>✅ Table $table créée</div>";
        } else {
            echo "<div style='color: red; margin: 5px 0;'>❌ Table $table manquante</div>";
        }
    }
    
    echo "<h2>Configuration terminée !</h2>";
    echo "<p>Vous pouvez maintenant utiliser le nouveau système de création de personnages.</p>";
    echo "<p><a href='characters.php'>Aller aux personnages</a> | <a href='manage_npcs.php'>Aller aux PNJ</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Erreur : " . $e->getMessage();
    echo "</div>";
}
?>
