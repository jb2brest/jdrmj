<?php
/**
 * Script pour enregistrer l'équipement de départ du Barbare
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU BARBARE ===\n\n";
    
    // Vérifier que la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements avant insertion: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Arme principale
    echo "Insertion du Choix 1 (Arme principale)...\n";
    
    // (a) hache à deux mains
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 22, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Hache à deux mains (ID: 22)\n";
    
    // (b) n'importe quelle arme de guerre de corps à corps
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 'Armes de guerre de corps à corps', 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme de guerre de corps à corps\n";
    
    // CHOIX 2: Arme secondaire
    echo "\nInsertion du Choix 2 (Arme secondaire)...\n";
    
    // (a) deux hachettes
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 4, 2, 'a', 2, 'à_choisir', 2]);
    echo "  - (a) Deux hachettes (ID: 4)\n";
    
    // (b) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 'Armes courantes à distance', 2, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (distance)\n";
    
    // (b) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 'Armes courantes de corps à corps', 2, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (corps à corps)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // 4 javelines
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'weapon', 5, 3, 'obligatoire', 4]);
    echo "  - 4 javelines (ID: 5)\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'sac', 3, 'obligatoire', 1]);
    echo "  - Un sac à dos\n";
    
    // Un sac de couchage
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'outils', 3, 'obligatoire', 1]);
    echo "  - Un sac de couchage\n";
    
    // Une gamelle
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'outils', 3, 'obligatoire', 1]);
    echo "  - Une gamelle\n";
    
    // Une boite d'allume-feu
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'outils', 3, 'obligatoire', 1]);
    echo "  - Une boite d'allume-feu\n";
    
    // 10 torches
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'outils', 3, 'obligatoire', 10]);
    echo "  - 10 torches\n";
    
    // 10 jours de rations
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'nourriture', 3, 'obligatoire', 10]);
    echo "  - 10 jours de rations\n";
    
    // Une gourde d'eau
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'nourriture', 3, 'obligatoire', 1]);
    echo "  - Une gourde d'eau\n";
    
    // Une corde de 15m
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'outils', 3, 'obligatoire', 1]);
    echo "  - Une corde de 15m\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 2 options (a) hache à deux mains, (b) arme de guerre\n";
    echo "Choix 2: 3 options (a) deux hachettes, (b) arme courante distance, (b) arme courante corps à corps\n";
    echo "Obligatoire: 9 items (4 javelines + 8 objets divers)\n";
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
