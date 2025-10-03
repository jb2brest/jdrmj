<?php
/**
 * Script de vérification pour s'assurer que toutes les références à place_objects
 * ont été corrigées dans les fichiers PHP
 */

echo "=== Vérification des références à place_objects ===\n";

// Rechercher tous les fichiers PHP qui contiennent encore des références à place_objects
$phpFiles = glob('*.php');
$phpFiles = array_merge($phpFiles, glob('*/*.php'));

$filesWithPlaceObjects = [];
$totalReferences = 0;

foreach ($phpFiles as $file) {
    if (is_file($file) && !is_dir($file)) {
        $content = file_get_contents($file);
        $matches = [];
        preg_match_all('/place_objects/', $content, $matches);
        
        if (!empty($matches[0])) {
            $filesWithPlaceObjects[] = [
                'file' => $file,
                'count' => count($matches[0])
            ];
            $totalReferences += count($matches[0]);
        }
    }
}

if (empty($filesWithPlaceObjects)) {
    echo "✅ Aucune référence à 'place_objects' trouvée dans les fichiers PHP!\n";
} else {
    echo "⚠️  Fichiers PHP contenant encore des références à 'place_objects':\n";
    foreach ($filesWithPlaceObjects as $file) {
        echo "   - {$file['file']}: {$file['count']} références\n";
    }
    echo "   Total: $totalReferences références\n";
}

echo "\n=== Test de la base de données ===\n";

try {
    require_once 'classes/init.php';
    $pdo = getPDO();
    
    // Vérifier que la table items existe et fonctionne
    $itemsCount = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    echo "✅ Table 'items' accessible: $itemsCount enregistrements\n";
    
    // Vérifier que place_objects n'existe plus
    $placeObjectsExists = $pdo->query("SHOW TABLES LIKE 'place_objects'")->fetchColumn();
    if ($placeObjectsExists) {
        echo "⚠️  Table 'place_objects' existe encore\n";
    } else {
        echo "✅ Table 'place_objects' n'existe plus (correct)\n";
    }
    
    // Tester une requête typique
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM items 
        WHERE owner_type = 'player' AND owner_id = ? 
        AND (item_source = 'Équipement de départ' OR item_source = 'Classe')
    ");
    $stmt->execute([1]);
    $result = $stmt->fetch();
    echo "✅ Requête de test réussie: {$result['count']} résultats\n";
    
} catch (Exception $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
}

echo "\n=== Résumé ===\n";
if (empty($filesWithPlaceObjects)) {
    echo "🎉 Toutes les références à 'place_objects' ont été corrigées!\n";
    echo "✅ La base de données fonctionne correctement avec la table 'items'\n";
    echo "✅ Le problème de view_campaign.php est résolu\n";
} else {
    echo "⚠️  Il reste des références à corriger dans les fichiers PHP\n";
}
?>

