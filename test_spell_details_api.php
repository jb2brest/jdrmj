<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== TEST API DÉTAILS DES SORTS ===\n\n";

try {
    // 1. Vérifier qu'il y a des sorts dans la base
    echo "1. Vérification des sorts disponibles:\n";
    
    $stmt = $pdo->query("SELECT id, name, level FROM spells LIMIT 5");
    $spells = $stmt->fetchAll();
    
    if (empty($spells)) {
        echo "   ERREUR: Aucun sort trouvé dans la base de données\n";
        exit;
    }
    
    echo "   - Sorts trouvés: " . count($spells) . "\n";
    foreach ($spells as $spell) {
        echo "     * ID: {$spell['id']} - {$spell['name']} (niveau {$spell['level']})\n";
    }
    
    // 2. Tester l'API get_spell_details.php
    echo "\n2. Test de l'API get_spell_details.php:\n";
    
    $testSpellId = $spells[0]['id'];
    echo "   - Test avec le sort ID: $testSpellId\n";
    
    // Simuler un appel à l'API
    $_GET['id'] = $testSpellId;
    
    // Capturer la sortie
    ob_start();
    include 'get_spell_details.php';
    $apiResponse = ob_get_clean();
    
    echo "   - Réponse de l'API: " . substr($apiResponse, 0, 200) . "...\n";
    
    // Vérifier si la réponse est valide JSON
    $decodedResponse = json_decode($apiResponse, true);
    if ($decodedResponse === null) {
        echo "   - ERREUR: Réponse JSON invalide\n";
        echo "   - Réponse brute: $apiResponse\n";
    } else {
        echo "   - JSON valide: " . ($decodedResponse['success'] ? "OUI" : "NON") . "\n";
        if ($decodedResponse['success']) {
            echo "   - Sort récupéré: " . $decodedResponse['spell']['name'] . "\n";
        } else {
            echo "   - Message d'erreur: " . $decodedResponse['message'] . "\n";
        }
    }
    
    // 3. Tester avec un personnage qui peut lancer des sorts
    echo "\n3. Test avec un personnage:\n";
    
    $character_id = 1;
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ?");
    $stmt->execute([$character_id]);
    $character = $stmt->fetch();
    
    if ($character) {
        echo "   - Personnage: " . $character['name'] . "\n";
        echo "   - Classe ID: " . $character['class_id'] . "\n";
        
        $canCast = canCastSpells($character['class_id']);
        echo "   - Peut lancer des sorts: " . ($canCast ? "OUI" : "NON") . "\n";
        
        if ($canCast) {
            $available_spells = getSpellsForClass($character['class_id']);
            echo "   - Sorts disponibles pour la classe: " . count($available_spells) . "\n";
            
            if (!empty($available_spells)) {
                $firstSpell = $available_spells[0];
                echo "   - Premier sort disponible: " . $firstSpell['name'] . " (ID: " . $firstSpell['id'] . ")\n";
                
                // Tester l'API avec ce sort
                $_GET['id'] = $firstSpell['id'];
                ob_start();
                include 'get_spell_details.php';
                $spellResponse = ob_get_clean();
                
                $spellData = json_decode($spellResponse, true);
                if ($spellData && $spellData['success']) {
                    echo "   - ✅ API fonctionne correctement pour ce sort\n";
                } else {
                    echo "   - ❌ Problème avec l'API pour ce sort\n";
                }
            }
        }
    }
    
    echo "\n✅ Test de l'API terminé !\n";
    
} catch (PDOException $e) {
    echo "ERREUR PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
?>
