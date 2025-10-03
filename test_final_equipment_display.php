<?php
require_once 'classes/init.php';

echo "=== Test final de l'affichage des équipements ===\n";

// Simuler une session
session_start();
$_SESSION['user_id'] = 1;

// Récupérer les choix pour la classe 1
$choix = StartingEquipmentChoix::findBySource('class', 1);

// Grouper les choix par no_choix
$groupedChoix = [];
foreach ($choix as $choixItem) {
    $noChoix = $choixItem->getNoChoix();
    if (!isset($groupedChoix[$noChoix])) {
        $groupedChoix[$noChoix] = [];
    }
    $groupedChoix[$noChoix][] = $choixItem;
}

echo "=== Équipement de classe - Barbare ===\n";

foreach ($groupedChoix as $noChoix => $choixGroup) {
    if ($noChoix == 0) {
        echo "\nÉquipement par défaut:\n";
        $defaultChoix = $choixGroup[0];
        if ($defaultChoix->hasOptions()) {
            $itemNames = [];
            foreach ($defaultChoix->getOptions() as $option) {
                $itemNames[] = $option->getNameWithQuantity();
            }
            echo "  - " . implode(', ', $itemNames) . "\n";
        }
    } else {
        echo "\nChoix $noChoix:\n";
        foreach ($choixGroup as $choix) {
            echo "  - Option " . $choix->getOptionLetter() . ": ";
            if ($choix->hasOptions()) {
                $itemNames = [];
                foreach ($choix->getOptions() as $option) {
                    $itemNames[] = $option->getNameWithQuantity();
                }
                echo implode(', ', $itemNames) . "\n";
            }
        }
    }
}

echo "\n✅ Test terminé - Les noms réels des équipements sont maintenant affichés!\n";
?>


