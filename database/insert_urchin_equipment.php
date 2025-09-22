<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Enfant des Rues
 * avec la nouvelle structure de table starting_equipment
 */

// Configuration de la base de données
$config = include 'config/database.test.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    // Inclure les fonctions d'auto-insertion
    require_once 'includes/object_auto_insert.php';
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'ENFANT DES RUES ===\n\n";
    
    // Vérifier que la table est vide pour l'enfant des rues
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 6");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Enfant des Rues: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Un petit couteau
    $petitCouteauId = autoInsertObject($pdo, 'outils', 'Petit couteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 6, 'outils', $petitCouteauId, 1, 'obligatoire', 1]);
    echo "  - Un petit couteau (Object ID: $petitCouteauId)\n";
    
    // Une carte de la ville dans laquelle vous avez grandi
    $carteVilleId = autoInsertObject($pdo, 'outils', 'Carte de la ville dans laquelle vous avez grandi');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 6, 'outils', $carteVilleId, 1, 'obligatoire', 1]);
    echo "  - Une carte de la ville dans laquelle vous avez grandi (Object ID: $carteVilleId)\n";
    
    // Une souris domestiquée
    $sourisDomestiqueeId = autoInsertObject($pdo, 'outils', 'Souris domestiquée');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 6, 'outils', $sourisDomestiqueeId, 1, 'obligatoire', 1]);
    echo "  - Une souris domestiquée (Object ID: $sourisDomestiqueeId)\n";
    
    // Un souvenir de vos parents
    $souvenirParentsId = autoInsertObject($pdo, 'outils', 'Souvenir de vos parents');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 6, 'outils', $souvenirParentsId, 1, 'obligatoire', 1]);
    echo "  - Un souvenir de vos parents (Object ID: $souvenirParentsId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 6, 'outils', $vetementsCommunsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 6");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 5 items\n";
    echo "  - Un petit couteau\n";
    echo "  - Une carte de la ville dans laquelle vous avez grandi\n";
    echo "  - Une souris domestiquée\n";
    echo "  - Un souvenir de vos parents\n";
    echo "  - Des vêtements communs\n";
    echo "Total: " . ($count_after - $count) . " enregistrements ajoutés\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERREUR lors de l'insertion: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SCRIPT TERMINÉ ===\n";
?>
