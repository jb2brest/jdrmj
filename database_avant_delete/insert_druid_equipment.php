<?php
/**
 * Script pour enregistrer l'équipement de départ du Druide
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU DRUIDE ===\n\n";
    
    // Vérifier que la table est vide pour le druide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 4");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Druide: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Bouclier ou Arme
    echo "Insertion du Choix 1 (Bouclier ou Arme)...\n";
    
    // (a) bouclier en bois
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'bouclier', 1, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Bouclier en bois (ID: 1)\n";
    
    // (b) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'weapon', 'Armes courantes à distance', 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (distance)\n";
    
    // (c) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'weapon', 'Armes courantes de corps à corps', 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme courante (corps à corps)\n";
    
    // CHOIX 2: Arme principale
    echo "\nInsertion du Choix 2 (Arme principale)...\n";
    
    // (a) cimeterre
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'weapon', 9, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - (a) Cimeterre (ID: 9)\n";
    
    // (b) n'importe quelle arme courante de corps à corps
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'weapon', 'Armes courantes de corps à corps', 2, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante de corps à corps\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Une armure de cuir
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'armor', 2, 3, 'obligatoire', 1]);
    echo "  - Une armure de cuir (ID: 2)\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'sac', $sacId, 3, 'obligatoire', 1]);
    echo "  - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $sacCouchageId, 3, 'obligatoire', 1]);
    echo "  - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // Une gamelle
    $gamelleId = autoInsertObject($pdo, 'outils', 'Gamelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $gamelleId, 3, 'obligatoire', 1]);
    echo "  - Une gamelle (Object ID: $gamelleId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $allumeFeuId, 3, 'obligatoire', 1]);
    echo "  - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $torcheId, 3, 'obligatoire', 10]);
    echo "  - 10 torches (Object ID: $torcheId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'nourriture', $rationsId, 3, 'obligatoire', 10]);
    echo "  - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'nourriture', $gourdeId, 3, 'obligatoire', 1]);
    echo "  - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $cordeId, 3, 'obligatoire', 1]);
    echo "  - Une corde de 15m (Object ID: $cordeId)\n";
    
    // Un focaliseur druidique
    $focaliseurId = autoInsertObject($pdo, 'outils', 'Focaliseur druidique');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 4, 'outils', $focaliseurId, 3, 'obligatoire', 1]);
    echo "  - Un focaliseur druidique (Object ID: $focaliseurId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 4");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 3 options (a) bouclier en bois, (b) arme courante distance, (c) arme courante corps à corps\n";
    echo "Choix 2: 2 options d'armes (a) cimeterre, (b) arme courante de corps à corps\n";
    echo "Obligatoire: 10 items (armure de cuir + 9 objets divers)\n";
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
