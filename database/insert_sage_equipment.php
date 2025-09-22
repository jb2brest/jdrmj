<?php
/**
 * Script pour enregistrer l'équipement de départ du Sage
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU SAGE ===\n\n";
    
    // Vérifier que la table est vide pour le sage
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 11");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Sage: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Une bouteille d'encre noire
    $bouteilleEncreId = autoInsertObject($pdo, 'outils', 'Bouteille d\'encre noire');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 11, 'outils', $bouteilleEncreId, 1, 'obligatoire', 1]);
    echo "  - Une bouteille d'encre noire (Object ID: $bouteilleEncreId)\n";
    
    // Une plume
    $plumeId = autoInsertObject($pdo, 'outils', 'Plume');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 11, 'outils', $plumeId, 1, 'obligatoire', 1]);
    echo "  - Une plume (Object ID: $plumeId)\n";
    
    // Un petit couteau
    $petitCouteauId = autoInsertObject($pdo, 'outils', 'Petit couteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 11, 'outils', $petitCouteauId, 1, 'obligatoire', 1]);
    echo "  - Un petit couteau (Object ID: $petitCouteauId)\n";
    
    // Une lettre d'un collègue mort posant une question à laquelle vous n'avez pas encore été en mesure de répondre
    $lettreCollegueId = autoInsertObject($pdo, 'outils', 'Lettre d\'un collègue mort');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 11, 'outils', $lettreCollegueId, 1, 'obligatoire', 1]);
    echo "  - Une lettre d'un collègue mort (Object ID: $lettreCollegueId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 11, 'outils', $vetementsCommunsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 11");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 5 items\n";
    echo "  - Une bouteille d'encre noire\n";
    echo "  - Une plume\n";
    echo "  - Un petit couteau\n";
    echo "  - Une lettre d'un collègue mort\n";
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
