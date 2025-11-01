<?php
/**
 * Script pour purger la table starting_equipment
 * Supprime tous les enregistrements de la table
 */

// Configuration de la base de données
$config = include_once 'config/database.test.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "=== PURGE DE LA TABLE STARTING_EQUIPMENT ===\n\n";
    
    // Compter les enregistrements avant suppression
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_before = $stmt->fetch()['count'];
    
    echo "Nombre d'enregistrements avant purge: $count_before\n\n";
    
    if ($count_before == 0) {
        echo "La table starting_equipment est déjà vide.\n";
        exit(0);
    }
    
    // Afficher les enregistrements qui vont être supprimés
    echo "Enregistrements qui vont être supprimés:\n";
    $stmt = $pdo->query("SELECT id, src, src_id, type, type_id, type_filter, no_choix, option_letter, type_choix, nb, groupe_id FROM starting_equipment ORDER BY id");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        echo "  - ID {$record['id']}: {$record['src']} {$record['src_id']} - {$record['type']}";
        if ($record['type_id']) {
            echo " (ID: {$record['type_id']})";
        }
        if ($record['type_filter']) {
            echo " [{$record['type_filter']}]";
        }
        if ($record['nb'] > 1) {
            echo " x{$record['nb']}";
        }
        echo "\n";
    }
    
    echo "\n⚠️  ATTENTION: Cette opération va supprimer TOUS les enregistrements de la table starting_equipment.\n";
    echo "Cette action est IRRÉVERSIBLE !\n\n";
    
    // Supprimer tous les enregistrements
    echo "Suppression des enregistrements...\n";
    
    $stmt = $pdo->prepare("DELETE FROM starting_equipment");
    $stmt->execute();
    
    $deleted_count = $stmt->rowCount();
    
    echo "✅ Purge terminée avec succès!\n";
    echo "Nombre d'enregistrements supprimés: $deleted_count\n\n";
    
    // Vérifier que la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_after = $stmt->fetch()['count'];
    
    echo "Nombre d'enregistrements après purge: $count_after\n";
    
    if ($count_after == 0) {
        echo "✅ La table starting_equipment est maintenant vide.\n";
    } else {
        echo "⚠️  ATTENTION: La table n'est pas complètement vide!\n";
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Enregistrements avant: $count_before\n";
    echo "Enregistrements supprimés: $deleted_count\n";
    echo "Enregistrements après: $count_after\n";
    echo "Sauvegarde: database/backup_starting_equipment_before_purge.sql\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR lors de la purge: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SCRIPT TERMINÉ ===\n";
?>
