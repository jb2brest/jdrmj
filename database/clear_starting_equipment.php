<?php
/**
 * Script pour effacer tout le contenu de la table starting_equipment
 * ATTENTION: Cette opération est irréversible !
 */

require_once 'config/database.php';

echo "=== SCRIPT DE SUPPRESSION DE LA TABLE STARTING_EQUIPMENT ===\n\n";

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user_id'])) {
    echo "ERREUR: Vous devez être connecté pour exécuter ce script.\n";
    exit(1);
}

// Vérifier le rôle admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    echo "ERREUR: Seuls les administrateurs peuvent exécuter ce script.\n";
    exit(1);
}

try {
    // 1. Compter les enregistrements avant suppression
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_before = $stmt->fetch()['count'];
    
    echo "Nombre d'enregistrements avant suppression: $count_before\n\n";
    
    if ($count_before == 0) {
        echo "La table starting_equipment est déjà vide.\n";
        exit(0);
    }
    
    // 2. Demander confirmation
    echo "⚠️  ATTENTION: Cette opération va supprimer TOUS les enregistrements de la table starting_equipment.\n";
    echo "Cette action est IRRÉVERSIBLE !\n\n";
    
    // 3. Créer une sauvegarde avant suppression
    echo "Création d'une sauvegarde...\n";
    
    $backup_file = __DIR__ . '/backup_starting_equipment_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_content = "-- Sauvegarde de la table starting_equipment avant suppression\n";
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
    
    // 4. Supprimer tous les enregistrements
    echo "Suppression des enregistrements...\n";
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM starting_equipment");
    $stmt->execute();
    
    $deleted_count = $stmt->rowCount();
    
    $pdo->commit();
    
    echo "✅ Suppression terminée avec succès!\n";
    echo "Nombre d'enregistrements supprimés: $deleted_count\n\n";
    
    // 5. Vérifier que la table est vide
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
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERREUR lors de la suppression: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SCRIPT TERMINÉ ===\n";
?>
