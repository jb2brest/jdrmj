<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Artisan de guilde
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'ARTISAN DE GUILDE ===\n\n";
    
    // Vérifier que la table est vide pour l'artisan de guilde
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 2");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Artisan de guilde: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Matériel d'artisan
    echo "Insertion du Choix 1 (Matériel d'artisan)...\n";
    
    // (a) Matériel d'alchimiste
    $alchimisteId = autoInsertObject($pdo, 'outils', 'Matériel d\'alchimiste');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $alchimisteId, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Matériel d'alchimiste (Object ID: $alchimisteId)\n";
    
    // (b) Matériel de brasseur
    $brasseurId = autoInsertObject($pdo, 'outils', 'Matériel de brasseur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $brasseurId, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Matériel de brasseur (Object ID: $brasseurId)\n";
    
    // (c) Matériel de calligraphe
    $calligrapheId = autoInsertObject($pdo, 'outils', 'Matériel de calligraphe');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $calligrapheId, 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) Matériel de calligraphe (Object ID: $calligrapheId)\n";
    
    // (d) Matériel de peintre
    $peintreId = autoInsertObject($pdo, 'outils', 'Matériel de peintre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $peintreId, 1, 'd', 1, 'à_choisir', 1]);
    echo "  - (d) Matériel de peintre (Object ID: $peintreId)\n";
    
    // (e) Outils de bijoutier
    $bijoutierId = autoInsertObject($pdo, 'outils', 'Outils de bijoutier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $bijoutierId, 1, 'e', 1, 'à_choisir', 1]);
    echo "  - (e) Outils de bijoutier (Object ID: $bijoutierId)\n";
    
    // (f) Outils de bricoleur
    $bricoleurId = autoInsertObject($pdo, 'outils', 'Outils de bricoleur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $bricoleurId, 1, 'f', 1, 'à_choisir', 1]);
    echo "  - (f) Outils de bricoleur (Object ID: $bricoleurId)\n";
    
    // (g) Outils de cartographe
    $cartographeId = autoInsertObject($pdo, 'outils', 'Outils de cartographe');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $cartographeId, 1, 'g', 1, 'à_choisir', 1]);
    echo "  - (g) Outils de cartographe (Object ID: $cartographeId)\n";
    
    // (h) Outils de charpentier
    $charpentierId = autoInsertObject($pdo, 'outils', 'Outils de charpentier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $charpentierId, 1, 'h', 1, 'à_choisir', 1]);
    echo "  - (h) Outils de charpentier (Object ID: $charpentierId)\n";
    
    // (i) Outils de cordonnier
    $cordonnierId = autoInsertObject($pdo, 'outils', 'Outils de cordonnier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $cordonnierId, 1, 'i', 1, 'à_choisir', 1]);
    echo "  - (i) Outils de cordonnier (Object ID: $cordonnierId)\n";
    
    // (j) Outils de forgeron
    $forgeronId = autoInsertObject($pdo, 'outils', 'Outils de forgeron');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $forgeronId, 1, 'j', 1, 'à_choisir', 1]);
    echo "  - (j) Outils de forgeron (Object ID: $forgeronId)\n";
    
    // (k) Outils de maçon
    $maconId = autoInsertObject($pdo, 'outils', 'Outils de maçon');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $maconId, 1, 'k', 1, 'à_choisir', 1]);
    echo "  - (k) Outils de maçon (Object ID: $maconId)\n";
    
    // (l) Outils de menuisier
    $menuisierId = autoInsertObject($pdo, 'outils', 'Outils de menuisier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $menuisierId, 1, 'l', 1, 'à_choisir', 1]);
    echo "  - (l) Outils de menuisier (Object ID: $menuisierId)\n";
    
    // (m) Outils de potier
    $potierId = autoInsertObject($pdo, 'outils', 'Outils de potier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $potierId, 1, 'm', 1, 'à_choisir', 1]);
    echo "  - (m) Outils de potier (Object ID: $potierId)\n";
    
    // (n) Outils de souffleur de verre
    $souffleurVerreId = autoInsertObject($pdo, 'outils', 'Outils de souffleur de verre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $souffleurVerreId, 1, 'n', 1, 'à_choisir', 1]);
    echo "  - (n) Outils de souffleur de verre (Object ID: $souffleurVerreId)\n";
    
    // (o) Outils de tanneur
    $tanneurId = autoInsertObject($pdo, 'outils', 'Outils de tanneur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $tanneurId, 1, 'o', 1, 'à_choisir', 1]);
    echo "  - (o) Outils de tanneur (Object ID: $tanneurId)\n";
    
    // (p) Outils de tisserand
    $tisserandId = autoInsertObject($pdo, 'outils', 'Outils de tisserand');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $tisserandId, 1, 'p', 1, 'à_choisir', 1]);
    echo "  - (p) Outils de tisserand (Object ID: $tisserandId)\n";
    
    // (q) Ustensiles de cuisinier
    $cuisinierId = autoInsertObject($pdo, 'outils', 'Ustensiles de cuisinier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $cuisinierId, 1, 'q', 1, 'à_choisir', 1]);
    echo "  - (q) Ustensiles de cuisinier (Object ID: $cuisinierId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Une lettre de recommandation de votre guilde
    $lettreRecommandationId = autoInsertObject($pdo, 'outils', 'Lettre de recommandation de votre guilde');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $lettreRecommandationId, 2, 'obligatoire', 1]);
    echo "  - Une lettre de recommandation de votre guilde (Object ID: $lettreRecommandationId)\n";
    
    // Des vêtements de voyage
    $vetementsVoyageId = autoInsertObject($pdo, 'outils', 'Vêtements de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 2, 'outils', $vetementsVoyageId, 2, 'obligatoire', 1]);
    echo "  - Des vêtements de voyage (Object ID: $vetementsVoyageId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 2");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 17 options de matériel d'artisan (a-q)\n";
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
    echo "Obligatoire: 2 items (lettre de recommandation + vêtements de voyage)\n";
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
