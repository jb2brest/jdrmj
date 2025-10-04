<?php
/**
 * Script de test des nouvelles classes PHP
 */

require_once 'classes/init.php';

echo "=== Test des classes PHP ===\n";

// Test StartingEquipmentChoix
echo "1. Test StartingEquipmentChoix:\n";
$choix = StartingEquipmentChoix::findBySource('class', 1);
echo "   - Nombre de choix trouvés pour la classe 1: " . count($choix) . "\n";

if (!empty($choix)) {
    $firstChoix = $choix[0];
    echo "   - Premier choix: ID=" . $firstChoix->getId() . ", src=" . $firstChoix->getSrc() . ", no_choix=" . $firstChoix->getNoChoix() . ", option_letter=" . $firstChoix->getOptionLetter() . "\n";
    echo "   - Nombre d'options: " . count($firstChoix->getOptions()) . "\n";
}

// Test StartingEquipmentOption
echo "\n2. Test StartingEquipmentOption:\n";
$options = StartingEquipmentOption::findBySource('class', 1);
echo "   - Nombre d'options trouvées pour la classe 1: " . count($options) . "\n";

if (!empty($options)) {
    $firstOption = $options[0];
    echo "   - Première option: ID=" . $firstOption->getId() . ", type=" . $firstOption->getType() . ", nb=" . $firstOption->getNb() . "\n";
    echo "   - Choix parent ID: " . $firstOption->getStartingEquipmentChoixId() . "\n";
}

// Test de création d'un nouveau choix
echo "\n3. Test de création d'un nouveau choix:\n";
try {
    $newChoix = StartingEquipmentChoix::create([
        'src' => 'class',
        'src_id' => 999,
        'no_choix' => 1,
        'option_letter' => 'A'
    ]);
    
    if ($newChoix) {
        echo "   ✓ Nouveau choix créé avec ID: " . $newChoix->getId() . "\n";
        
        // Test d'ajout d'une option
        $newOption = $newChoix->addOption([
            'src' => 'class',
            'src_id' => 999,
            'type' => 'weapon',
            'type_filter' => 'Armes de guerre de corps à corps',
            'nb' => 1
        ]);
        
        if ($newOption) {
            echo "   ✓ Nouvelle option créée avec ID: " . $newOption->getId() . "\n";
        }
        
        // Nettoyer les données de test
        $newChoix->delete();
        echo "   ✓ Données de test nettoyées\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur lors du test de création: " . $e->getMessage() . "\n";
}

echo "\n✅ Tests des classes PHP terminés!\n";
?>



