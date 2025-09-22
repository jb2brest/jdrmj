<?php
/**
 * Script simple pour effacer tout le contenu de la table starting_equipment
 * Version simplifiée sans vérification de session
 */

require_once 'config/database.php';

echo "=== SUPPRESSION DE LA TABLE STARTING_EQUIPMENT ===\n\n";

try {
    // Compter les enregistrements avant suppression
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_before = $stmt->fetch()['count'];
    
    echo "Nombre d'enregistrements avant suppression: $count_before\n\n";
    
    if ($count_before == 0) {
        echo "La table starting_equipment est déjà vide.\n";
        exit(0);
    }
    
    // Créer une sauvegarde
    echo "Création d'une sauvegarde...\n";
    
    $backup_file = __DIR__ . '/backup_starting_equipment_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_content = "-- Sauvegarde de la table starting_equipment\n";
    $backup_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Nombre d'enregistrements: $count_before\n\n";
    
    // Récupérer tous les enregistrements
    $stmt = $pdo->query("SELECT * FROM starting_equipment ORDER BY id");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        $backup_content .= "INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES (";
        $backup_content .= "'" . addslashes($record['src']) . "', ";
        $backup_content .= $record['src_id'] . ", ";
        $backup_content .= "'" . addslashes($record['type']) . "', ";
        $backup_content .= ($record['type_id'] ? $record['type_id'] : 'NULL') . ", ";
        $backup_content .= ($record['option_indice'] ? "'" . addslashes($record['option_indice']) . "'" : 'NULL') . ", ";
        $backup_content .= ($record['groupe_id'] ? $record['groupe_id'] : 'NULL') . ", ";
        $backup_content .= "'" . addslashes($record['type_choix']) . "');\n";
    }
    
    file_put_contents($backup_file, $backup_content);
    echo "Sauvegarde créée: $backup_file\n\n";
    
    // Supprimer tous les enregistrements
    echo "Suppression des enregistrements...\n";
    
    $stmt = $pdo->prepare("DELETE FROM starting_equipment");
    $stmt->execute();
    
    $deleted_count = $stmt->rowCount();
    
    echo "✅ Suppression terminée avec succès!\n";
    echo "Nombre d'enregistrements supprimés: $deleted_count\n\n";
    
    // Vérifier que la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_after = $stmt->fetch()['count'];
    
    echo "Nombre d'enregistrements après suppression: $count_after\n";
    
    if ($count_after == 0) {
        echo "✅ La table starting_equipment est maintenant vide.\n";
    } else {
        echo "⚠️  ATTENTION: La table n'est pas complètement vide!\n";
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Enregistrements avant: $count_before\n";
    echo "Enregistrements supprimés: $deleted_count\n";
    echo "Enregistrements après: $count_after\n";
    echo "Sauvegarde: $backup_file\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR lors de la suppression: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SCRIPT TERMINÉ ===\n";
?>
