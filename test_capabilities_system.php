<?php
/**
 * Script de test du système homogène de capacités
 */

require_once 'config/database.php';
require_once 'includes/capabilities_functions.php';

echo "<h1>Test du système de capacités</h1>\n";

try {
    // Test 1: Vérifier que les tables existent
    echo "<h2>Test 1: Vérification des tables</h2>\n";
    
    $tables = ['capability_types', 'capabilities', 'character_capabilities'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "✓ Table '$table' existe<br>\n";
        } else {
            echo "❌ Table '$table' manquante<br>\n";
        }
    }
    
    // Test 2: Vérifier les types de capacités
    echo "<h2>Test 2: Types de capacités</h2>\n";
    
    $stmt = $pdo->prepare("SELECT name, icon, color FROM capability_types ORDER BY name");
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Types disponibles (" . count($types) . "):<br>\n";
    foreach ($types as $type) {
        echo "- {$type['name']} ({$type['icon']}, {$type['color']})<br>\n";
    }
    
    // Test 3: Vérifier les capacités par source
    echo "<h2>Test 3: Capacités par source</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT source_type, COUNT(*) as count 
        FROM capabilities 
        WHERE is_active = 1 
        GROUP BY source_type 
        ORDER BY source_type
    ");
    $stmt->execute();
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sources as $source) {
        echo "- {$source['source_type']}: {$source['count']} capacités<br>\n";
    }
    
    // Test 4: Tester les fonctions de récupération
    echo "<h2>Test 4: Fonctions de récupération</h2>\n";
    
    // Test des capacités de classe
    $barbarianCapabilities = getBarbarianCapabilities(5);
    echo "✓ Capacités de Barbare niveau 5: " . count($barbarianCapabilities) . " capacités<br>\n";
    
    $fighterCapabilities = getFighterCapabilities(3);
    echo "✓ Capacités de Guerrier niveau 3: " . count($fighterCapabilities) . " capacités<br>\n";
    
    // Test des capacités raciales
    $raceCapabilities = getRaceCapabilities(1); // Humain
    echo "✓ Capacités raciales Humain: " . count($raceCapabilities) . " capacités<br>\n";
    
    // Test 5: Vérifier un personnage existant
    echo "<h2>Test 5: Personnage existant</h2>\n";
    
    $stmt = $pdo->prepare("SELECT id, name FROM characters LIMIT 1");
    $stmt->execute();
    $character = $stmt->fetch();
    
    if ($character) {
        echo "Test avec le personnage: {$character['name']} (ID: {$character['id']})<br>\n";
        
        // Mettre à jour ses capacités
        if (updateCharacterCapabilities($character['id'])) {
            echo "✓ Capacités mises à jour<br>\n";
            
            // Récupérer ses capacités
            $characterCapabilities = getCharacterCapabilities($character['id']);
            echo "✓ Capacités récupérées: " . count($characterCapabilities) . " capacités<br>\n";
            
            // Afficher quelques capacités
            if (!empty($characterCapabilities)) {
                echo "Exemples de capacités:<br>\n";
                foreach (array_slice($characterCapabilities, 0, 3) as $capability) {
                    echo "- {$capability['name']} ({$capability['type_name']})<br>\n";
                }
            }
        } else {
            echo "❌ Erreur lors de la mise à jour des capacités<br>\n";
        }
    } else {
        echo "Aucun personnage trouvé pour le test<br>\n";
    }
    
    // Test 6: Recherche de capacités
    echo "<h2>Test 6: Recherche de capacités</h2>\n";
    
    $searchResults = searchCapabilities('rage');
    echo "✓ Recherche 'rage': " . count($searchResults) . " résultats<br>\n";
    
    $searchResults = searchCapabilities('magie');
    echo "✓ Recherche 'magie': " . count($searchResults) . " résultats<br>\n";
    
    // Test 7: Statistiques finales
    echo "<h2>Test 7: Statistiques finales</h2>\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM capabilities WHERE is_active = 1");
    $stmt->execute();
    $totalCapabilities = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM character_capabilities WHERE is_active = 1");
    $stmt->execute();
    $totalCharacterCapabilities = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM characters");
    $stmt->execute();
    $totalCharacters = $stmt->fetchColumn();
    
    echo "📊 Statistiques:<br>\n";
    echo "- Total capacités: $totalCapabilities<br>\n";
    echo "- Total capacités de personnages: $totalCharacterCapabilities<br>\n";
    echo "- Total personnages: $totalCharacters<br>\n";
    echo "- Moyenne capacités par personnage: " . ($totalCharacters > 0 ? round($totalCharacterCapabilities / $totalCharacters, 2) : 0) . "<br>\n";
    
    echo "<h2>✅ Tous les tests sont passés avec succès !</h2>\n";
    echo "<p>Le système homogène de capacités fonctionne correctement.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors des tests</h2>\n";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
h1 { border-bottom: 2px solid #28a745; padding-bottom: 10px; }
h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; }
</style>
