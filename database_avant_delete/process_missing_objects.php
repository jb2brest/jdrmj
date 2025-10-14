<?php
/**
 * Script pour traiter automatiquement les objets manquants
 * dans la table starting_equipment
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
    
    echo "=== TRAITEMENT DES OBJETS MANQUANTS ===\n\n";
    
    // Analyser les objets manquants
    echo "Analyse des objets manquants...\n";
    $suggestions = analyzeMissingObjects($pdo);
    
    if (empty($suggestions)) {
        echo "✅ Aucun objet manquant trouvé.\n";
        exit(0);
    }
    
    echo "Objets manquants identifiés:\n";
    foreach ($suggestions as $suggestion) {
        echo "  - ID {$suggestion['equipment_id']}: {$suggestion['type']} - {$suggestion['nom']} (x{$suggestion['nb']})\n";
    }
    
    echo "\nTraitement automatique...\n";
    
    // Traiter tous les objets manquants
    $results = processAllMissingObjects($pdo);
    
    echo "✅ Traitement terminé!\n\n";
    
    echo "Résultats:\n";
    foreach ($results as $result) {
        echo "  - Équipement ID {$result['equipment_id']}: {$result['type']} '{$result['nom']}' -> Object ID {$result['object_id']}\n";
    }
    
    // Vérifier les résultats
    echo "\nVérification des résultats...\n";
    $stmt = $pdo->query("
        SELECT se.id, se.type, se.type_id, o.nom, se.nb
        FROM starting_equipment se
        LEFT JOIN Object o ON se.type_id = o.id
        WHERE se.type IN ('sac', 'outils', 'nourriture', 'accessoire')
        ORDER BY se.type, se.id
    ");
    
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "État final des équipements:\n";
    foreach ($equipment as $item) {
        if ($item['type_id']) {
            echo "  ✅ ID {$item['id']}: {$item['type']} - {$item['nom']} (x{$item['nb']}) [Object ID: {$item['type_id']}]\n";
        } else {
            echo "  ❌ ID {$item['id']}: {$item['type']} - Non lié (x{$item['nb']})\n";
        }
    }
    
    echo "\n=== SCRIPT TERMINÉ ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
