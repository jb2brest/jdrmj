<?php
require_once 'classes/init.php';

echo "=== Analyse de l'organisation des choix ===\n";

// Récupérer tous les choix pour la classe 1
$choix = StartingEquipmentChoix::findBySource('class', 1);

// Grouper par no_choix
$groupedChoix = [];
foreach ($choix as $choixItem) {
    $noChoix = $choixItem->getNoChoix();
    if (!isset($groupedChoix[$noChoix])) {
        $groupedChoix[$noChoix] = [];
    }
    $groupedChoix[$noChoix][] = $choixItem;
}

foreach ($groupedChoix as $noChoix => $choixGroup) {
    echo "Groupe de choix $noChoix:\n";
    foreach ($choixGroup as $choixItem) {
        echo "  - option_letter: " . $choixItem->getOptionLetter() . ", options: " . count($choixItem->getOptions()) . "\n";
        foreach ($choixItem->getOptions() as $option) {
            echo "    * " . $option->getTypeLabel() . " (x" . $option->getNb() . ")\n";
        }
    }
    echo "\n";
}

echo "=== Structure pour l'interface ===\n";
echo "Pour l'interface, il faut:\n";
echo "1. no_choix = 0: Équipement par défaut (pas de choix)\n";
echo "2. no_choix > 0: Grouper par no_choix et afficher les options\n";
?>



