<?php
require_once 'classes/init.php';

echo "=== Test des noms réels d'équipement ===\n";

// Récupérer les options pour la classe 1
$choix = StartingEquipmentChoix::findBySource('class', 1);
foreach ($choix as $choixItem) {
    if ($choixItem->getNoChoix() == 0) {
        echo "Équipement par défaut:\n";
        foreach ($choixItem->getOptions() as $option) {
            echo "  - Type: " . $option->getType() . ", ID: " . $option->getTypeId() . ", Nb: " . $option->getNb() . "\n";
            echo "    Nom générique: " . $option->getTypeLabel() . "\n";
            echo "    Nom réel: " . $option->getRealItemName() . "\n";
            echo "    Avec quantité: " . $option->getNameWithQuantity() . "\n";
            echo "\n";
        }
        break;
    }
}

echo "=== Test avec des choix d'options ===\n";
foreach ($choix as $choixItem) {
    if ($choixItem->getNoChoix() > 0) {
        echo "Choix " . $choixItem->getNoChoix() . " - Option " . $choixItem->getOptionLetter() . ":\n";
        foreach ($choixItem->getOptions() as $option) {
            echo "  - " . $option->getNameWithQuantity() . "\n";
        }
        echo "\n";
    }
}
?>

