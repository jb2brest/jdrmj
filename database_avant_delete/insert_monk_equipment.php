<?php
/**
 * Script pour enregistrer l'équipement de départ du Moine
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU MOINE ===\n\n";
    
    // Vérifier que la table est vide pour le moine
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 8");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Moine: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Arme
    echo "Insertion du Choix 1 (Arme)...\n";
    
    // (a) une épée courte
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'weapon', 16, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Une épée courte (ID: 16)\n";
    
    // (b) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'weapon', 'Armes courantes à distance', 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (distance)\n";
    
    // (c) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'weapon', 'Armes courantes de corps à corps', 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme courante (corps à corps)\n";
    
    // CHOIX 2: Sac d'équipement
    echo "\nInsertion du Choix 2 (Sac d'équipement)...\n";
    
    // (a) groupe sac d'explorateur
    echo "  - (a) Sac d'explorateur:\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'sac', $sacId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $sacCouchageId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // Une gamelle
    $gamelleId = autoInsertObject($pdo, 'outils', 'Gamelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $gamelleId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Une gamelle (Object ID: $gamelleId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $allumeFeuId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $torcheId, 2, 'a', 2, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'nourriture', $rationsId, 2, 'a', 2, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'nourriture', $gourdeId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $cordeId, 2, 'a', 2, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // (b) groupe sac d'exploration souterraine
    echo "  - (b) Sac d'exploration souterraine:\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'sac', $sacId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un pied de biche
    $piedBicheId = autoInsertObject($pdo, 'outils', 'Pied de biche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $piedBicheId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Un pied de biche (Object ID: $piedBicheId)\n";
    
    // Un marteau
    $marteauId = autoInsertObject($pdo, 'outils', 'Marteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $marteauId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Un marteau (Object ID: $marteauId)\n";
    
    // 10 pitons
    $pitonId = autoInsertObject($pdo, 'outils', 'Piton');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $pitonId, 2, 'b', 3, 'à_choisir', 10]);
    echo "    - 10 pitons (Object ID: $pitonId)\n";
    
    // 10 torches
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $torcheId, 2, 'b', 3, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // Une boite d'allume-feu
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $allumeFeuId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 jours de rations
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'nourriture', $rationsId, 2, 'b', 3, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'nourriture', $gourdeId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'outils', $cordeId, 2, 'b', 3, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // 10 fléchettes
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 8, 'weapon', 13, 4, 'obligatoire', 10]);
    echo "  - 10 fléchettes (ID: 13)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 8");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 3 options d'armes (a) épée courte, (b) arme courante distance, (c) arme courante corps à corps\n";
    echo "Choix 2a: 8 items du sac d'explorateur\n";
    echo "Choix 2b: 9 items du sac d'exploration souterraine\n";
    echo "Obligatoire: 1 item (10 fléchettes)\n";
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
