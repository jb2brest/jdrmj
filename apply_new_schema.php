<?php
/**
 * Script pour appliquer le nouveau schéma des tables starting_equipment
 * à la base de données de test
 */

require_once 'classes/init.php';

try {
    $pdo = getPDO();
    echo "Connexion à la base de données réussie.\n";
    
    // Lire le script de refactorisation
    $refactorScript = file_get_contents('database/refactor_starting_equipment_tables.sql');
    
    // Diviser le script en requêtes individuelles
    $queries = array_filter(array_map('trim', explode(';', $refactorScript)));
    
    echo "Début de l'application du nouveau schéma...\n";
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $pdo->exec($query);
                echo "✓ Requête exécutée avec succès\n";
            } catch (PDOException $e) {
                echo "⚠ Erreur lors de l'exécution de la requête: " . $e->getMessage() . "\n";
                echo "Requête: " . substr($query, 0, 100) . "...\n";
            }
        }
    }
    
    echo "Schéma de refactorisation appliqué.\n";
    
    // Vérifier que les tables ont été créées
    $tables = $pdo->query("SHOW TABLES LIKE 'starting_equipment%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables créées: " . implode(', ', $tables) . "\n";
    
    // Lire le script de migration
    $migrationScript = file_get_contents('database/migrate_starting_equipment_data.sql');
    
    // Diviser le script en requêtes individuelles
    $migrationQueries = array_filter(array_map('trim', explode(';', $migrationScript)));
    
    echo "Début de la migration des données...\n";
    
    foreach ($migrationQueries as $query) {
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                if (stripos($query, 'SELECT') === 0) {
                    // Pour les requêtes SELECT, on affiche juste le résultat
                    $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($result)) {
                        echo "Résultat de la requête:\n";
                        foreach ($result as $row) {
                            echo "  " . implode(' | ', $row) . "\n";
                        }
                    }
                } else {
                    $pdo->exec($query);
                    echo "✓ Migration exécutée avec succès\n";
                }
            } catch (PDOException $e) {
                echo "⚠ Erreur lors de la migration: " . $e->getMessage() . "\n";
                echo "Requête: " . substr($query, 0, 100) . "...\n";
            }
        }
    }
    
    echo "Migration des données terminée.\n";
    
    // Vérification finale
    $choixCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_choix")->fetchColumn();
    $optionsCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_options")->fetchColumn();
    
    echo "Vérification finale:\n";
    echo "- Nombre de choix créés: $choixCount\n";
    echo "- Nombre d'options créées: $optionsCount\n";
    
    echo "Application du nouveau schéma terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>



