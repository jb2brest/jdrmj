<?php
/**
 * Script pour enregistrer l'équipement de départ du Héros du Peuple
 * avec la nouvelle structure de table starting_equipment
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
    
    // Inclure les fonctions d'auto-insertion
    require_once 'includes/object_auto_insert.php';
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU HÉROS DU PEUPLE ===\n\n";
    
    // Vérifier que la table est vide pour le héros du peuple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 8");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Héros du Peuple: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Outils d'artisan
    echo "Insertion du Choix 1 (Outils d'artisan)...\n";
    
    // (a) Matériel d'alchimiste
    $materielAlchimisteId = autoInsertObject($pdo, 'outils', 'Matériel d\'alchimiste');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $materielAlchimisteId, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Matériel d'alchimiste (Object ID: $materielAlchimisteId)\n";
    
    // (b) Matériel de brasseur
    $materielBrasseurId = autoInsertObject($pdo, 'outils', 'Matériel de brasseur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $materielBrasseurId, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Matériel de brasseur (Object ID: $materielBrasseurId)\n";
    
    // (c) Matériel de calligraphe
    $materielCalligrapheId = autoInsertObject($pdo, 'outils', 'Matériel de calligraphe');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $materielCalligrapheId, 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) Matériel de calligraphe (Object ID: $materielCalligrapheId)\n";
    
    // (d) Matériel de peintre
    $materielPeintreId = autoInsertObject($pdo, 'outils', 'Matériel de peintre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $materielPeintreId, 1, 'd', 1, 'à_choisir', 1]);
    echo "  - (d) Matériel de peintre (Object ID: $materielPeintreId)\n";
    
    // (e) Outils de bijoutier
    $outilsBijoutierId = autoInsertObject($pdo, 'outils', 'Outils de bijoutier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsBijoutierId, 1, 'e', 1, 'à_choisir', 1]);
    echo "  - (e) Outils de bijoutier (Object ID: $outilsBijoutierId)\n";
    
    // (f) Outils de bricoleur
    $outilsBricoleurId = autoInsertObject($pdo, 'outils', 'Outils de bricoleur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsBricoleurId, 1, 'f', 1, 'à_choisir', 1]);
    echo "  - (f) Outils de bricoleur (Object ID: $outilsBricoleurId)\n";
    
    // (g) Outils de cartographe
    $outilsCartographeId = autoInsertObject($pdo, 'outils', 'Outils de cartographe');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsCartographeId, 1, 'g', 1, 'à_choisir', 1]);
    echo "  - (g) Outils de cartographe (Object ID: $outilsCartographeId)\n";
    
    // (h) Outils de charpentier
    $outilsCharpentierId = autoInsertObject($pdo, 'outils', 'Outils de charpentier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsCharpentierId, 1, 'h', 1, 'à_choisir', 1]);
    echo "  - (h) Outils de charpentier (Object ID: $outilsCharpentierId)\n";
    
    // (i) Outils de cordonnier
    $outilsCordonnierId = autoInsertObject($pdo, 'outils', 'Outils de cordonnier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsCordonnierId, 1, 'i', 1, 'à_choisir', 1]);
    echo "  - (i) Outils de cordonnier (Object ID: $outilsCordonnierId)\n";
    
    // (j) Outils de forgeron
    $outilsForgeronId = autoInsertObject($pdo, 'outils', 'Outils de forgeron');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsForgeronId, 1, 'j', 1, 'à_choisir', 1]);
    echo "  - (j) Outils de forgeron (Object ID: $outilsForgeronId)\n";
    
    // (k) Outils de maçon
    $outilsMaconId = autoInsertObject($pdo, 'outils', 'Outils de maçon');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsMaconId, 1, 'k', 1, 'à_choisir', 1]);
    echo "  - (k) Outils de maçon (Object ID: $outilsMaconId)\n";
    
    // (l) Outils de menuisier
    $outilsMenuisierId = autoInsertObject($pdo, 'outils', 'Outils de menuisier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsMenuisierId, 1, 'l', 1, 'à_choisir', 1]);
    echo "  - (l) Outils de menuisier (Object ID: $outilsMenuisierId)\n";
    
    // (m) Outils de potier
    $outilsPotierId = autoInsertObject($pdo, 'outils', 'Outils de potier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsPotierId, 1, 'm', 1, 'à_choisir', 1]);
    echo "  - (m) Outils de potier (Object ID: $outilsPotierId)\n";
    
    // (n) Outils de souffleur de verre
    $outilsSouffleurVerreId = autoInsertObject($pdo, 'outils', 'Outils de souffleur de verre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsSouffleurVerreId, 1, 'n', 1, 'à_choisir', 1]);
    echo "  - (n) Outils de souffleur de verre (Object ID: $outilsSouffleurVerreId)\n";
    
    // (o) Outils de tanneur
    $outilsTanneurId = autoInsertObject($pdo, 'outils', 'Outils de tanneur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsTanneurId, 1, 'o', 1, 'à_choisir', 1]);
    echo "  - (o) Outils de tanneur (Object ID: $outilsTanneurId)\n";
    
    // (p) Outils de tisserand
    $outilsTisserandId = autoInsertObject($pdo, 'outils', 'Outils de tisserand');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $outilsTisserandId, 1, 'p', 1, 'à_choisir', 1]);
    echo "  - (p) Outils de tisserand (Object ID: $outilsTisserandId)\n";
    
    // (q) Ustensiles de cuisinier
    $ustensilesCuisinierId = autoInsertObject($pdo, 'outils', 'Ustensiles de cuisinier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $ustensilesCuisinierId, 1, 'q', 1, 'à_choisir', 1]);
    echo "  - (q) Ustensiles de cuisinier (Object ID: $ustensilesCuisinierId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Une pelle
    $pelleId = autoInsertObject($pdo, 'outils', 'Pelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $pelleId, 2, 'obligatoire', 1]);
    echo "  - Une pelle (Object ID: $pelleId)\n";
    
    // Un pot en fer
    $potFerId = autoInsertObject($pdo, 'outils', 'Pot en fer');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $potFerId, 2, 'obligatoire', 1]);
    echo "  - Un pot en fer (Object ID: $potFerId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 8, 'outils', $vetementsCommunsId, 2, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 8");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 17 options d'outils d'artisan (a-q)\n";
    echo "  - (a) Matériel d'alchimiste\n";
    echo "  - (b) Matériel de brasseur\n";
    echo "  - (c) Matériel de calligraphe\n";
    echo "  - (d) Matériel de peintre\n";
    echo "  - (e) Outils de bijoutier\n";
    echo "  - (f) Outils de bricoleur\n";
    echo "  - (g) Outils de cartographe\n";
    echo "  - (h) Outils de charpentier\n";
    echo "  - (i) Outils de cordonnier\n";
    echo "  - (j) Outils de forgeron\n";
    echo "  - (k) Outils de maçon\n";
    echo "  - (l) Outils de menuisier\n";
    echo "  - (m) Outils de potier\n";
    echo "  - (n) Outils de souffleur de verre\n";
    echo "  - (o) Outils de tanneur\n";
    echo "  - (p) Outils de tisserand\n";
    echo "  - (q) Ustensiles de cuisinier\n";
    echo "Obligatoire: 3 items (pelle + pot en fer + vêtements communs)\n";
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
