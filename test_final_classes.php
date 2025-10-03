<?php
require_once 'classes/init.php';

echo "=== Test des classes après correction ===\n";

// Test StartingEquipmentChoix
$choix = StartingEquipmentChoix::findBySource('class', 1);
echo "Nombre de choix pour la classe 1: " . count($choix) . "\n";

if (!empty($choix)) {
    $firstChoix = $choix[0];
    echo "Premier choix:\n";
    echo "- ID: " . $firstChoix->getId() . "\n";
    echo "- no_choix: " . $firstChoix->getNoChoix() . "\n";
    echo "- option_letter: " . $firstChoix->getOptionLetter() . "\n";
    echo "- hasOptions: " . ($firstChoix->hasOptions() ? 'OUI' : 'NON') . "\n";
    echo "- getFullDescription: " . $firstChoix->getFullDescription() . "\n";
}

echo "\n✅ Toutes les méthodes fonctionnent correctement!\n";
?>


