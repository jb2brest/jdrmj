<?php
/**
 * Script de configuration du système homogène de capacités
 * Ce script initialise les tables et migre les données existantes
 */

require_once 'config/database.php';

echo "<h1>Configuration du système de capacités</h1>\n";

try {
    // 1. Créer les tables
    echo "<h2>1. Création des tables...</h2>\n";
    
    $sql = file_get_contents('database/create_capabilities_system.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Tables créées avec succès<br>\n";
    
    // 2. Migrer les capacités de base
    echo "<h2>2. Migration des capacités de base...</h2>\n";
    
    $migrationSql = file_get_contents('database/migrate_all_capabilities.sql');
    $migrationStatements = explode(';', $migrationSql);
    
    foreach ($migrationStatements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Capacités de base migrées avec succès<br>\n";
    
    // 3. Mettre à jour les capacités de tous les personnages existants
    echo "<h2>3. Mise à jour des personnages existants...</h2>\n";
    
    require_once 'includes/capabilities_functions.php';
    
    $stmt = $pdo->prepare("SELECT id FROM characters");
    $stmt->execute();
    $characters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updated = 0;
    foreach ($characters as $characterId) {
        if (updateCharacterCapabilities($characterId)) {
            $updated++;
        }
    }
    
    echo "✓ $updated personnages mis à jour avec succès<br>\n";
    
    // 4. Vérification
    echo "<h2>4. Vérification du système...</h2>\n";
    
    // Compter les capacités
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM capabilities");
    $stmt->execute();
    $capabilityCount = $stmt->fetchColumn();
    echo "✓ $capabilityCount capacités dans la base de données<br>\n";
    
    // Compter les types de capacités
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM capability_types");
    $stmt->execute();
    $typeCount = $stmt->fetchColumn();
    echo "✓ $typeCount types de capacités définis<br>\n";
    
    // Compter les capacités de personnages
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM character_capabilities WHERE is_active = 1");
    $stmt->execute();
    $characterCapabilityCount = $stmt->fetchColumn();
    echo "✓ $characterCapabilityCount capacités attribuées aux personnages<br>\n";
    
    echo "<h2>✅ Configuration terminée avec succès !</h2>\n";
    echo "<p>Le système homogène de capacités est maintenant opérationnel.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors de la configuration</h2>\n";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Vérifiez les logs pour plus de détails.</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
h1 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; }
</style>
