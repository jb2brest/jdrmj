<?php
/**
 * Script pour enregistrer l'équipement de départ du Guerrier
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU GUERRIER ===\n\n";
    
    // Vérifier que la table est vide pour le guerrier
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 6");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Guerrier: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Armure
    echo "Insertion du Choix 1 (Armure)...\n";
    
    // (a) cotte de mailles
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'armor', 10, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Cotte de mailles (ID: 10)\n";
    
    // (b) groupe - armure de cuir
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'armor', 2, 1, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) Armure de cuir (ID: 2)\n";
    
    // (b) groupe - arc long
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 12, 1, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) Arc long (ID: 12)\n";
    
    // (b) groupe - 20 flèches
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 13, 1, 'b', 2, 'à_choisir', 20]);
    echo "  - (b) 20 flèches (ID: 13)\n";
    
    // CHOIX 2: Armes principales
    echo "\nInsertion du Choix 2 (Armes principales)...\n";
    
    // (a) groupe - arme de guerre à distance
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre à distance', 2, 'a', 3, 'à_choisir', 1]);
    echo "  - (a) N'importe quelle arme de guerre à distance\n";
    
    // (a) groupe - un bouclier
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'bouclier', 13, 2, 'a', 3, 'à_choisir', 1]);
    echo "  - (a) Un bouclier (ID: 13)\n";
    
    // (b) groupe - 2 armes de guerre de corps à corps
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre de corps à corps', 2, 'b', 4, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme de guerre de corps à corps (1ère)\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre de corps à corps', 2, 'b', 4, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme de guerre de corps à corps (2ème)\n";
    
    // (c) groupe - 2 armes de guerre à distance
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre à distance', 2, 'c', 5, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme de guerre à distance (1ère)\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre à distance', 2, 'c', 5, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme de guerre à distance (2ème)\n";
    
    // (d) groupe - arme de guerre de corps à corps + arme de guerre à distance
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre de corps à corps', 2, 'd', 6, 'à_choisir', 1]);
    echo "  - (d) N'importe quelle arme de guerre de corps à corps\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre à distance', 2, 'd', 6, 'à_choisir', 1]);
    echo "  - (d) N'importe quelle arme de guerre à distance\n";
    
    // (e) groupe - arme de guerre de corps à corps + bouclier
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 'Armes de guerre de corps à corps', 2, 'e', 7, 'à_choisir', 1]);
    echo "  - (e) N'importe quelle arme de guerre de corps à corps\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'bouclier', 13, 2, 'e', 7, 'à_choisir', 1]);
    echo "  - (e) Un bouclier (ID: 13)\n";
    
    // CHOIX 3: Armes secondaires
    echo "\nInsertion du Choix 3 (Armes secondaires)...\n";
    
    // (a) groupe - arbalète légère
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 11, 3, 'a', 8, 'à_choisir', 1]);
    echo "  - (a) Arbalète légère (ID: 11)\n";
    
    // (a) groupe - 20 carreaux
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', 46, 3, 'a', 8, 'à_choisir', 20]);
    echo "  - (a) 20 carreaux (Object ID: 46)\n";
    
    // (b) 2 hachettes
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'weapon', 14, 3, 'b', 9, 'à_choisir', 2]);
    echo "  - (b) 2 hachettes (ID: 14)\n";
    
    // CHOIX 4: Sac d'équipement
    echo "\nInsertion du Choix 4 (Sac d'équipement)...\n";
    
    // (a) groupe sac d'explorateur
    echo "  - (a) Sac d'explorateur:\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'sac', $sacId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $sacCouchageId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // Une gamelle
    $gamelleId = autoInsertObject($pdo, 'outils', 'Gamelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $gamelleId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Une gamelle (Object ID: $gamelleId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $allumeFeuId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $torcheId, 4, 'a', 10, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'nourriture', $rationsId, 4, 'a', 10, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'nourriture', $gourdeId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $cordeId, 4, 'a', 10, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // (b) groupe sac d'exploration souterraine
    echo "  - (b) Sac d'exploration souterraine:\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'sac', $sacId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un pied de biche
    $piedBicheId = autoInsertObject($pdo, 'outils', 'Pied de biche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $piedBicheId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Un pied de biche (Object ID: $piedBicheId)\n";
    
    // Un marteau
    $marteauId = autoInsertObject($pdo, 'outils', 'Marteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $marteauId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Un marteau (Object ID: $marteauId)\n";
    
    // 10 pitons
    $pitonId = autoInsertObject($pdo, 'outils', 'Piton');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $pitonId, 4, 'b', 11, 'à_choisir', 10]);
    echo "    - 10 pitons (Object ID: $pitonId)\n";
    
    // 10 torches
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $torcheId, 4, 'b', 11, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // Une boite d'allume-feu
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $allumeFeuId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 jours de rations
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'nourriture', $rationsId, 4, 'b', 11, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'nourriture', $gourdeId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 6, 'outils', $cordeId, 4, 'b', 11, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 6");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 2 options d'armures (a) cotte de mailles, (b) armure de cuir + arc long + flèches\n";
    echo "Choix 2: 5 options d'armes principales (a-e) avec différentes combinaisons\n";
    echo "Choix 3: 2 options d'armes secondaires (a) arbalète + carreaux, (b) 2 hachettes\n";
    echo "Choix 4a: 8 items du sac d'explorateur\n";
    echo "Choix 4b: 9 items du sac d'exploration souterraine\n";
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
