<?php
require_once 'classes/init.php';

echo "=== Test de la nouvelle structure StartingEquipmentChoix ===\n";

// Récupérer un choix d'équipement pour voir sa structure
$choix = StartingEquipmentChoix::findBySource('class', 1);
if (!empty($choix)) {
    $firstChoix = $choix[0];
    echo "Structure du premier choix:\n";
    echo "- ID: " . $firstChoix->getId() . "\n";
    echo "- src: " . $firstChoix->getSrc() . "\n";
    echo "- src_id: " . $firstChoix->getSrcId() . "\n";
    echo "- no_choix: " . $firstChoix->getNoChoix() . "\n";
    echo "- option_letter: " . $firstChoix->getOptionLetter() . "\n";
    echo "- hasOptions: " . ($firstChoix->hasOptions() ? 'OUI' : 'NON') . "\n";
    echo "- options count: " . count($firstChoix->getOptions()) . "\n";
    
    if ($firstChoix->hasOptions()) {
        echo "Première option:\n";
        $firstOption = $firstChoix->getOptions()[0];
        echo "  - type: " . $firstOption->getType() . "\n";
        echo "  - nb: " . $firstOption->getNb() . "\n";
        echo "  - type_filter: " . $firstOption->getTypeFilter() . "\n";
    }
    
    // Tester la méthode getFullDescription
    echo "\nDescription complète: " . $firstChoix->getFullDescription() . "\n";
}

echo "\n=== Test de tous les choix pour la classe 1 ===\n";
foreach ($choix as $index => $choixItem) {
    echo "Choix $index:\n";
    echo "  - no_choix: " . $choixItem->getNoChoix() . "\n";
    echo "  - option_letter: " . $choixItem->getOptionLetter() . "\n";
    echo "  - options: " . count($choixItem->getOptions()) . "\n";
    echo "  - description: " . $choixItem->getFullDescription() . "\n";
}
?>




