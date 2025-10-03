<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/capabilities_functions.php';
require_once 'includes/starting_equipment_functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? null;

// Vérifier que la session existe
if (!$session_id) {
    header('Location: characters.php');
    exit;
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 8) {
    header('Location: characters.php');
    exit;
}

$data = $sessionData['data'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_equipment = $_POST['class_equipment'] ?? [];
    $class_weapon_choices = $_POST['class_weapon_choices'] ?? [];
    $class_weapon_choice = $_POST['class_weapon_choice'] ?? []; // Nouveaux menus déroulants
    $class_instrument_choice = $_POST['class_instrument_choice'] ?? []; // Choix d'instruments
    $background_equipment = $_POST['background_equipment'] ?? [];
    $background_weapon_choices = $_POST['background_weapon_choices'] ?? [];
    $background_weapon_choice = $_POST['background_weapon_choice'] ?? []; // Nouveaux menus déroulants
    $background_instrument_choice = $_POST['background_instrument_choice'] ?? []; // Choix d'instruments
    $tool_choices = $_POST['tool_choices'] ?? [];
    
    // Traiter les sélections d'armes et d'instruments
    $selectedWeapons = [];
    $selectedInstruments = [];
    
    // Traiter les armes de classe
    foreach ($class_weapon_choice as $groupId => $weaponChoices) {
        if (isset($class_equipment[$groupId])) {
            $selectedOption = $class_equipment[$groupId];
            if (isset($weaponChoices[$selectedOption])) {
                // Gérer les armes multiples pour une option
                if (is_array($weaponChoices[$selectedOption])) {
                    foreach ($weaponChoices[$selectedOption] as $weapon) {
                        if (!empty($weapon)) {
                            $selectedWeapons[] = $weapon;
                        }
                    }
                } else {
                    // Arme unique
                    if (!empty($weaponChoices[$selectedOption])) {
                        $selectedWeapons[] = $weaponChoices[$selectedOption];
                    }
                }
            }
        }
    }
    
    // Traiter les armes d'historique
    foreach ($background_weapon_choice as $groupId => $weaponChoices) {
        if (isset($background_equipment[$groupId])) {
            $selectedOption = $background_equipment[$groupId];
            if (isset($weaponChoices[$selectedOption])) {
                // Gérer les armes multiples pour une option
                if (is_array($weaponChoices[$selectedOption])) {
                    foreach ($weaponChoices[$selectedOption] as $weapon) {
                        if (!empty($weapon)) {
                            $selectedWeapons[] = $weapon;
                        }
                    }
                } else {
                    // Arme unique
                    if (!empty($weaponChoices[$selectedOption])) {
                        $selectedWeapons[] = $weaponChoices[$selectedOption];
                    }
                }
            }
        }
    }
    
    // Traiter les instruments de classe
    foreach ($class_instrument_choice as $choiceIndex => $instrument) {
        if (!empty($instrument)) {
            $selectedInstruments[] = $instrument;
        }
    }
    
    // Traiter les instruments d'historique
    foreach ($background_instrument_choice as $choiceIndex => $instrument) {
        if (!empty($instrument)) {
            $selectedInstruments[] = $instrument;
        }
    }
    
    // Générer l'équipement final avec la nouvelle table starting_equipment
    $equipmentData = generateFinalEquipmentNew($data['class_id'], $data['background_id'], $data['race_id'], [
        'class' => $class_equipment,
        'background' => $background_equipment,
        'selected_weapons' => $selectedWeapons,
        'selected_instruments' => $selectedInstruments
    ]);
    
    $finalEquipment = implode("\n", $equipmentData['equipment']);
    $totalGold = $equipmentData['gold'];
    
    // Sauvegarder les données
    $stepData = [
        'starting_equipment' => $finalEquipment,
        'money_gold' => $totalGold, // Utiliser 'money_gold' pour correspondre à finalizeCharacterCreation
        'class_equipment_choices' => $class_equipment,
        'class_weapon_choices' => $class_weapon_choices,
        'background_equipment_choices' => $background_equipment,
        'background_weapon_choices' => $background_weapon_choices,
        'tool_choices' => $tool_choices
    ];
    
    if (saveCharacterCreationStep($user_id, $session_id, 9, $stepData)) {
        // Finaliser la création du personnage
        $characterId = finalizeCharacterCreation($user_id, $session_id);
        
        if ($characterId) {
            // Ajouter l'équipement de départ au personnage
            if (!empty($finalEquipment)) {
                addStartingEquipmentToCharacterNew($characterId, ['equipment' => $finalEquipment, 'gold' => $totalGold]);
            }
            
            // Marquer le personnage comme équipé et verrouiller les modifications
            $stmt = $pdo->prepare("UPDATE characters SET is_equipped = 1, equipment_locked = 1, character_locked = 1 WHERE id = ?");
            $stmt->execute([$characterId]);
            
            // Rediriger vers la fiche du personnage avec un message de succès
            header("Location: view_character.php?id=$characterId&created=1");
            exit;
        } else {
            $error_message = "Erreur lors de la création du personnage. Vérifiez que toutes les données sont correctement sauvegardées.";
        }
    } else {
        $error_message = "Erreur lors de la sauvegarde des données.";
    }
}

// Récupérer les informations de la race, classe et historique
$selectedRaceId = $data['race_id'] ?? null;
$selectedClassId = $data['class_id'] ?? null;
$selectedBackgroundId = $data['background_id'] ?? null;

$raceInfo = null;
$classInfo = null;
$backgroundInfo = null;

if ($selectedRaceId) {
    $stmt = $pdo->prepare("SELECT name FROM races WHERE id = ?");
    $stmt->execute([$selectedRaceId]);
    $raceInfo = $stmt->fetch();
}

if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $classInfo = $stmt->fetch();
}

if ($selectedBackgroundId) {
    $stmt = $pdo->prepare("SELECT name FROM backgrounds WHERE id = ?");
    $stmt->execute([$selectedBackgroundId]);
    $backgroundInfo = $stmt->fetch();
}

// Récupérer l'équipement de départ avec la table starting_equipment
$classEquipmentDetailed = getClassStartingEquipmentNew($selectedClassId);
$backgroundEquipmentDetailed = getBackgroundStartingEquipment($selectedBackgroundId);
$raceEquipmentDetailed = getRaceStartingEquipment($selectedRaceId);

// Structurer l'équipement par choix
if (!empty($classEquipmentDetailed)) {
    $classChoices = structureStartingEquipmentByChoices($classEquipmentDetailed);
} else {
    // Fallback sur l'ancien système si pas de données dans starting_equipment
    $classEquipment = getClassStartingEquipment($selectedClassId);
    $classChoices = $classEquipment;
}

if (!empty($backgroundEquipmentDetailed)) {
    $backgroundChoices = structureStartingEquipmentByChoices($backgroundEquipmentDetailed);
} else {
    // Pas de données dans starting_equipment - aucun équipement de background
    // La colonne equipment a été supprimée de la table backgrounds
    $backgroundChoices = [];
}

if (!empty($raceEquipmentDetailed)) {
    $raceChoices = structureStartingEquipmentByChoices($raceEquipmentDetailed);
} else {
    $raceChoices = [];
}

// Récupérer les outils disponibles (simplifié pour la nouvelle table)
$availableTools = [];
$toolChoices = [];

// Les outils sont maintenant gérés directement dans l'équipement de départ
// via la table starting_equipment avec type = 'outils' ou 'instrument'

// Récupérer les armes de guerre et courantes pour les menus déroulants
$warWeapons = Item::getWarWeapons();
$commonWeapons = Item::getCommonWeapons();
$allWeapons = array_merge($warWeapons, $commonWeapons);
$weaponsByType = [];
foreach ($allWeapons as $weapon) {
    $weaponsByType[$weapon['type']][] = $weapon;
}

// Fonction helper pour détecter si un choix concerne les armes de guerre
function isWarWeaponsChoice($group) {
    foreach ($group['options'] as $option) {
        if (isset($option['type_filter']) && 
            (strpos($option['type_filter'], 'Armes de guerre') !== false ||
             strpos($option['type_filter'], 'armes de guerre') !== false)) {
            return true;
        }
    }
    return false;
}

// Fonction helper pour obtenir le type d'armes de guerre d'un choix
function getWarWeaponsType($group) {
    foreach ($group['options'] as $option) {
        if (isset($option['type_filter'])) {
            if (strpos($option['type_filter'], 'corps à corps') !== false) {
                return 'Armes de guerre de corps à corps';
            } elseif (strpos($option['type_filter'], 'distance') !== false) {
                return 'Armes de guerre à distance';
            }
        }
    }
    return null;
}

// Fonction helper pour récupérer les détails d'un item depuis la table Object
function getItemDetails($type, $typeId) {
    global $pdo;
    
    if (!$typeId) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM Object WHERE id = ?");
    $stmt->execute([$typeId]);
    return $stmt->fetch();
}

// Fonction helper pour afficher un choix d'équipement avec support des instruments
function displayEquipmentChoice($choice, $choiceIndex, $inputName, $inputId) {
    echo '<div class="form-check mb-2">';
    echo '<input class="form-check-input" type="radio" name="' . $inputName . '[' . $choiceIndex . ']" value="' . $choiceIndex . '" id="' . $inputId . '">';
    echo '<label class="form-check-label" for="' . $inputId . '">';
    
    if (is_array($choice) && isset($choice['type'])) {
        if ($choice['type'] === 'weapon_choice') {
            // Choix d'arme - afficher un menu déroulant
            echo '<span class="me-2">' . htmlspecialchars($choice['description']) . ' :</span>';
            echo '<select class="form-select form-select-sm d-inline-block" name="' . str_replace('equipment', 'weapon_choice', $inputName) . '[' . $choiceIndex . ']" style="width: auto; min-width: 200px;">';
            echo '<option value="">-- Choisir une arme --</option>';
            foreach ($choice['options'] as $weapon) {
                echo '<option value="' . htmlspecialchars($weapon['name']) . '">' . htmlspecialchars($weapon['name']) . '</option>';
            }
            echo '</select>';
        } elseif ($choice['type'] === 'instrument_choice') {
            // Choix d'instrument - afficher un menu déroulant
            echo '<span class="me-2">' . htmlspecialchars($choice['description']) . ' :</span>';
            echo '<select class="form-select form-select-sm d-inline-block" name="' . str_replace('equipment', 'instrument_choice', $inputName) . '[' . $choiceIndex . ']" style="width: auto; min-width: 200px;">';
            echo '<option value="">-- Choisir un instrument --</option>';
            foreach ($choice['options'] as $instrument) {
                echo '<option value="' . htmlspecialchars($instrument['name']) . '">' . htmlspecialchars($instrument['name']) . '</option>';
            }
            echo '</select>';
        } elseif ($choice['type'] === 'pack') {
            // Sac d'équipement - afficher la description
            echo htmlspecialchars($choice['description']);
        }
    } else {
        // Équipement simple
        echo htmlspecialchars($choice);
    }
    
    echo '</label>';
    echo '</div>';
}

// Fonction helper pour afficher un choix d'équipement détaillé (depuis starting_equipment)
function displayDetailedEquipmentChoice($choice, $choiceIndex, $inputName, $inputId) {
    echo '<div class="form-check mb-2">';
    echo '<input class="form-check-input" type="radio" name="' . $inputName . '[' . $choiceIndex . ']" value="' . $choiceIndex . '" id="' . $inputId . '">';
    echo '<label class="form-check-label" for="' . $inputId . '">';
    
    if (isset($choice['merged_items'])) {
        // Option avec plusieurs items fusionnés (comme un sac avec son contenu)
        echo '<div class="equipment-option-content">';
        $firstItem = true;
        
        foreach ($choice['merged_items'] as $item) {
            if (!$firstItem) {
                echo ' + ';
            }
            $firstItem = false;
            
            // Récupérer les détails de l'item
            $itemDetails = getItemDetails($item['type'], $item['type_id']);
            if ($itemDetails) {
                $itemName = $itemDetails['nom'];
                if ($item['nb'] > 1) {
                    $itemName = $item['nb'] . ' ' . $itemName;
                }
                echo htmlspecialchars($itemName);
            } else {
                // Fallback sur type_filter si pas de détails
                echo htmlspecialchars($item['type_filter'] ?? $item['type']);
            }
        }
        echo '</div>';
    } else {
        // Item simple
        $itemDetails = getItemDetails($choice['type'], $choice['type_id']);
        if ($itemDetails) {
            $itemName = $itemDetails['nom'];
            if ($choice['nb'] > 1) {
                $itemName = $choice['nb'] . ' ' . $itemName;
            }
            echo htmlspecialchars($itemName);
        } else {
            // Fallback sur type_filter si pas de détails
            echo htmlspecialchars($choice['type_filter'] ?? $choice['type']);
        }
    }
    
    echo '</label>';
    echo '</div>';
}

// Fonction helper pour afficher une option d'équipement avec menu déroulant si nécessaire
function displayEquipmentOption($item, $groupId, $optionLetter, $inputName, $inputId) {
    echo '<div class="form-check mb-2">';
    echo '<input class="form-check-input" type="radio" name="' . $inputName . '[' . $groupId . ']" value="' . $optionLetter . '" id="' . $inputId . '">';
    echo '<label class="form-check-label" for="' . $inputId . '">';
    
    if (isset($item['merged_items'])) {
        // Option avec plusieurs items fusionnés
        echo '<div class="equipment-option-content">';
        $weaponSelects = [];
        
        foreach ($item['merged_items'] as $index => $mergedItem) {
            if ($index > 0) {
                echo ' + ';
            }
            
            if (isset($mergedItem['type_filter']) && 
                (strpos($mergedItem['type_filter'], 'Armes de guerre') !== false ||
                 strpos($mergedItem['type_filter'], 'Armes courantes') !== false)) {
                // C'est une arme - afficher un menu déroulant
                global $weaponsByType;
                $weaponsType = $mergedItem['type_filter'];
                $availableWeapons = $weaponsByType[$weaponsType] ?? [];
                
                echo '<span class="me-2">' . htmlspecialchars($mergedItem['type_filter']) . ' :</span>';
                echo '<select class="form-select form-select-sm d-inline-block" name="' . str_replace('equipment', 'weapon_choice', $inputName) . '[' . $groupId . '][' . $optionLetter . '][' . $index . ']" style="width: auto; min-width: 200px;">';
                echo '<option value="">-- Choisir une arme --</option>';
                foreach ($availableWeapons as $weapon) {
                    echo '<option value="' . htmlspecialchars($weapon['name']) . '">' . htmlspecialchars($weapon['name']) . '</option>';
                }
                echo '</select>';
            } else {
                // Équipement normal
                echo htmlspecialchars(getEquipmentDisplayName($mergedItem));
            }
        }
        echo '</div>';
    } else {
        // Option avec un seul item
        if (isset($item['type_filter']) && 
            (strpos($item['type_filter'], 'Armes de guerre') !== false ||
             strpos($item['type_filter'], 'Armes courantes') !== false)) {
            // C'est une arme - afficher un menu déroulant
            global $weaponsByType;
            $weaponsType = $item['type_filter'];
            $availableWeapons = $weaponsByType[$weaponsType] ?? [];
            
            echo '<div class="d-flex align-items-center">';
            echo '<span class="me-2">' . htmlspecialchars($item['type_filter']) . ' :</span>';
            echo '<select class="form-select form-select-sm" name="' . str_replace('equipment', 'weapon_choice', $inputName) . '[' . $groupId . '][' . $optionLetter . '][0]" style="width: auto; min-width: 200px;">';
            echo '<option value="">-- Choisir une arme --</option>';
            foreach ($availableWeapons as $weapon) {
                echo '<option value="' . htmlspecialchars($weapon['name']) . '">' . htmlspecialchars($weapon['name']) . '</option>';
            }
            echo '</select>';
            echo '</div>';
        } else {
            // Équipement normal - affichage standard
            echo htmlspecialchars(getEquipmentDisplayName($item));
        }
    }
    
    echo '</label>';
    echo '</div>';
}

// Fonction helper pour afficher le nom d'un équipement (simple ou fusionné)
function getEquipmentDisplayName($item) {
    if (isset($item['merged_items'])) {
        // Équipement fusionné - afficher tous les items
        $names = [];
        foreach ($item['merged_items'] as $mergedItem) {
            $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
            $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type_filter'] ?? $mergedItem['type'];
            if ($mergedItem['nb'] > 1) {
                $itemName .= " (x{$mergedItem['nb']})";
            }
            $names[] = $itemName;
        }
        return implode(' + ', $names);
    } else {
        // Équipement simple
        $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
        $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $item['type_filter'] ?? $item['type'];
        if ($item['nb'] > 1) {
            $itemName .= " (x{$item['nb']})";
        }
        return $itemName;
    }
}

// Debug: Afficher les informations pour diagnostiquer
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Selected Class ID: " . ($selectedClassId ?? 'NULL') . "\n";
    echo "Selected Background ID: " . ($selectedBackgroundId ?? 'NULL') . "\n";
    echo "Class Info: " . print_r($classInfo, true) . "\n";
    echo "Background Info: " . print_r($backgroundInfo, true) . "\n";
    echo "Class Groups: " . print_r($classGroups, true) . "\n";
    echo "Background Groups: " . print_r($backgroundGroups, true) . "\n";
    echo "</pre>";
}

include 'includes/layout.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- En-tête de l'étape -->
            <div class="text-center mb-4">
                <h2><i class="fas fa-shopping-bag me-2"></i>Étape 9 : Équipement de Départ</h2>
                <p class="text-muted">Choisissez l'équipement de départ de votre personnage</p>
                
                <!-- Progression -->
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <small class="text-muted">Étape 9 sur 9 - Finalisation</small>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="step9Form">
                <div class="row">
                    <!-- Équipement de classe -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-shield-alt me-2"></i>Équipement de classe</h5>
                                <small class="text-muted"><?php echo htmlspecialchars($classInfo['name'] ?? 'Classe non sélectionnée'); ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($classChoices)): ?>
                                    <?php 
                                    // Afficher d'abord l'équipement obligatoire (choiceId = 0)
                                    if (isset($classChoices[0]) && isset($classChoices[0]['options'])) {
                                        echo '<div class="equipment-group mb-4">';
                                        echo '<h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Équipement obligatoire</h6>';
                                        echo '<div class="equipment-options">';
                                        foreach ($classChoices[0]['options'] as $item) {
                                            $itemDetails = getItemDetails($item['type'], $item['type_id']);
                                            if ($itemDetails) {
                                                $itemName = $itemDetails['nom'];
                                                if ($item['nb'] > 1) {
                                                    $itemName = $item['nb'] . ' ' . $itemName;
                                                }
                                                echo '<div class="form-check mb-2">';
                                                echo '<input class="form-check-input" type="checkbox" checked disabled>';
                                                echo '<label class="form-check-label text-muted">' . htmlspecialchars($itemName) . '</label>';
                                                echo '</div>';
                                            }
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    
                                    $displayIndex = 0;
                                    foreach ($classChoices as $choiceId => $choiceGroup): 
                                        // Ignorer l'équipement obligatoire (choiceId = 0) car déjà affiché
                                        if ($choiceId == 0) continue;
                                        
                                        $displayIndex++;
                                    ?>
                                        <div class="equipment-group mb-4">
                                            <h6 class="text-primary">
                                                <i class="fas fa-list me-2"></i>Choix d'équipement <?php echo $displayIndex; ?>
                                            </h6>
                                            
                                            <div class="equipment-options">
                                                <?php 
                                                // Gérer la nouvelle structure avec 'options'
                                                if (isset($choiceGroup['options'])) {
                                                    foreach ($choiceGroup['options'] as $optionIndex => $choice): 
                                                        $optionLetter = chr(97 + $optionIndex); // a, b, c, etc.
                                                ?>
                                                        <?php 
                                                        // Utiliser displayEquipmentOption pour gérer les dropdowns d'armes
                                                        displayEquipmentOption(
                                                            $choice, 
                                                            $displayIndex - 1, 
                                                            $optionLetter, 
                                                            'class_equipment', 
                                                            'class_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                        );
                                                        ?>
                                                <?php 
                                                    endforeach;
                                                } else {
                                                    // Gérer l'ancienne structure
                                                    foreach ($choiceGroup as $optionLetter => $choice): 
                                                ?>
                                                        <?php 
                                                        // Utiliser displayEquipmentOption pour gérer les dropdowns d'armes
                                                        displayEquipmentOption(
                                                            $choice, 
                                                            $displayIndex - 1, 
                                                            $optionLetter, 
                                                            'class_equipment', 
                                                            'class_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                        );
                                                        ?>
                                                <?php 
                                                    endforeach;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Aucun équipement de classe disponible.</strong>
                                        <?php if (!$classInfo): ?>
                                            <br>Classe non sélectionnée (ID: <?php echo $selectedClassId ?? 'NULL'; ?>)
                                        <?php else: ?>
                                            <br>Cette classe n'a pas d'équipement de départ défini dans la table starting_equipment.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Équipement d'historique -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-backpack me-2"></i>Équipement d'historique</h5>
                                <small class="text-muted"><?php echo htmlspecialchars($backgroundInfo['name'] ?? 'Historique non sélectionné'); ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($backgroundChoices)): ?>
                                    <?php 
                                    // Afficher d'abord l'équipement obligatoire (choiceId = 0)
                                    if (isset($backgroundChoices[0]) && isset($backgroundChoices[0]['options'])) {
                                        echo '<div class="equipment-group mb-4">';
                                        echo '<h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Équipement obligatoire</h6>';
                                        echo '<div class="equipment-options">';
                                        foreach ($backgroundChoices[0]['options'] as $item) {
                                            $itemDetails = getItemDetails($item['type'], $item['type_id']);
                                            if ($itemDetails) {
                                                $itemName = $itemDetails['nom'];
                                                if ($item['nb'] > 1) {
                                                    $itemName = $item['nb'] . ' ' . $itemName;
                                                }
                                                echo '<div class="form-check mb-2">';
                                                echo '<input class="form-check-input" type="checkbox" checked disabled>';
                                                echo '<label class="form-check-label text-muted">' . htmlspecialchars($itemName) . '</label>';
                                                echo '</div>';
                                            }
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    
                                    $displayIndex = 0;
                                    foreach ($backgroundChoices as $choiceId => $choiceGroup): 
                                        // Ignorer l'équipement obligatoire (choiceId = 0) car déjà affiché
                                        if ($choiceId == 0) continue;
                                        
                                        $displayIndex++;
                                    ?>
                                        <div class="equipment-group mb-4">
                                            <h6 class="text-primary">
                                                <i class="fas fa-list me-2"></i>Choix d'équipement <?php echo $displayIndex; ?>
                                            </h6>
                                            
                                            <div class="equipment-options">
                                                <?php 
                                                // Gérer la nouvelle structure avec 'options'
                                                if (isset($choiceGroup['options'])) {
                                                    foreach ($choiceGroup['options'] as $optionIndex => $choice): 
                                                        $optionLetter = chr(97 + $optionIndex); // a, b, c, etc.
                                                ?>
                                                        <?php 
                                                        // Utiliser la fonction détaillée si nous avons des données de starting_equipment
                                                        if (isset($choice['type_id']) || isset($choice['merged_items'])) {
                                                            displayDetailedEquipmentChoice(
                                                                $choice, 
                                                                $displayIndex - 1, 
                                                                'background_equipment', 
                                                                'background_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                            );
                                                        } else {
                                                            displayEquipmentChoice(
                                                                $choice, 
                                                                $displayIndex - 1, 
                                                                'background_equipment', 
                                                                'background_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                            );
                                                        }
                                                        ?>
                                                <?php 
                                                    endforeach;
                                                } else {
                                                    // Gérer l'ancienne structure
                                                    foreach ($choiceGroup as $optionLetter => $choice): 
                                                ?>
                                                        <?php 
                                                        // Utiliser la fonction détaillée si nous avons des données de starting_equipment
                                                        if (isset($choice['type_id']) || isset($choice['merged_items'])) {
                                                            displayDetailedEquipmentChoice(
                                                                $choice, 
                                                                $displayIndex - 1, 
                                                                'background_equipment', 
                                                                'background_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                            );
                                                        } else {
                                                            displayEquipmentChoice(
                                                                $choice, 
                                                                $displayIndex - 1, 
                                                                'background_equipment', 
                                                                'background_equipment_' . ($displayIndex - 1) . '_' . $optionLetter
                                                            );
                                                        }
                                                        ?>
                                                <?php 
                                                    endforeach;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Aucun équipement d'historique disponible.</strong>
                                        <?php if (!$backgroundInfo): ?>
                                            <br>Historique non sélectionné (ID: <?php echo $selectedBackgroundId ?? 'NULL'; ?>)
                                        <?php else: ?>
                                            <br>Cet historique n'a pas d'équipement défini dans la table starting_equipment.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Note sur les outils -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note :</strong> Les outils et instruments de musique sont maintenant inclus dans l'équipement de départ ci-dessus. 
                            Les choix d'outils spécifiques seront gérés automatiquement selon votre classe et historique.
                        </div>
                    </div>
                </div>

                <!-- Résumé du personnage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list me-2"></i>Résumé du personnage</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Informations de base</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nom :</strong> <?php echo htmlspecialchars($data['name'] ?? 'Non défini'); ?></li>
                                    <li><strong>Race :</strong> <?php echo htmlspecialchars($raceInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Classe :</strong> <?php echo htmlspecialchars($classInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Historique :</strong> <?php echo htmlspecialchars($backgroundInfo['name'] ?? 'Non sélectionné'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Caractéristiques</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Force :</strong> <?php echo ($data['strength'] ?? 10) + ($data['racial_strength_bonus'] ?? 0); ?></li>
                                    <li><strong>Dextérité :</strong> <?php echo ($data['dexterity'] ?? 10) + ($data['racial_dexterity_bonus'] ?? 0); ?></li>
                                    <li><strong>Constitution :</strong> <?php echo ($data['constitution'] ?? 10) + ($data['racial_constitution_bonus'] ?? 0); ?></li>
                                    <li><strong>Intelligence :</strong> <?php echo ($data['intelligence'] ?? 10) + ($data['racial_intelligence_bonus'] ?? 0); ?></li>
                                    <li><strong>Sagesse :</strong> <?php echo ($data['wisdom'] ?? 10) + ($data['racial_wisdom_bonus'] ?? 0); ?></li>
                                    <li><strong>Charisme :</strong> <?php echo ($data['charisma'] ?? 10) + ($data['racial_charisma_bonus'] ?? 0); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Autres informations</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Alignement :</strong> <?php echo htmlspecialchars($data['alignment'] ?? 'Non sélectionné'); ?></li>
                                    <li><strong>Âge :</strong> <?php echo htmlspecialchars($data['age'] ?? 'Non défini'); ?></li>
                                    <li><strong>Taille :</strong> <?php echo htmlspecialchars($data['height'] ?? 'Non définie'); ?></li>
                                    <li><strong>Poids :</strong> <?php echo htmlspecialchars($data['weight'] ?? 'Non défini'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé de l'argent de départ -->
                <?php
                // Calculer l'argent de départ pour l'affichage
                $previewEquipmentData = generateFinalEquipmentNew($selectedClassId, $selectedBackgroundId, $selectedRaceId, [
                    'class' => [],
                    'background' => [],
                    'selected_weapons' => []
                ]);
                $startingGold = $previewEquipmentData['gold'];
                ?>
                
                <?php if ($startingGold > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title text-warning">
                                    <i class="fas fa-coins me-2"></i>Argent de Départ
                                </h5>
                                <p class="card-text">
                                    <span class="display-6 text-warning"><?php echo $startingGold; ?></span>
                                    <span class="text-muted ms-2">pièces d'or</span>
                                </p>
                                <small class="text-muted">
                                    Cet argent sera ajouté à votre personnage lors de la création
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Boutons de navigation -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="character_create_step8.php?session_id=<?php echo htmlspecialchars($session_id); ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Étape précédente
                    </a>
                    
                    <button type="submit" class="btn btn-success btn-lg" id="createCharacterBtn">
                        <i class="fas fa-user-plus me-2"></i>Créer le personnage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion simplifiée pour la nouvelle structure
    // Les choix d'équipement sont maintenant gérés directement par les radio buttons
    
    // Validation du formulaire
    const form = document.getElementById('step9Form');
    const createBtn = document.getElementById('createCharacterBtn');
    
    form.addEventListener('submit', function(e) {
        // Vérifier que tous les choix requis sont faits
        const equipmentGroups = document.querySelectorAll('.equipment-group');
        let allChoicesValid = true;
        
        equipmentGroups.forEach(group => {
            const radioInputs = group.querySelectorAll('input[type="radio"]');
            const weaponSelects = group.querySelectorAll('select[name*="weapon_choice"]');
            
            if (radioInputs.length > 0) {
                // C'est un groupe avec des choix radio
                const hasSelection = Array.from(radioInputs).some(input => input.checked);
                
                if (!hasSelection) {
                    allChoicesValid = false;
                    group.style.border = '2px solid #dc3545';
                } else {
                    // Vérifier si l'option sélectionnée a des menus déroulants d'armes
                    const checkedInput = Array.from(radioInputs).find(input => input.checked);
                    if (checkedInput) {
                        const weaponSelects = checkedInput.closest('.form-check').querySelectorAll('select[name*="weapon_choice"]');
                        let hasEmptyWeaponSelect = false;
                        
                        weaponSelects.forEach(select => {
                            if (select.value === '') {
                                hasEmptyWeaponSelect = true;
                            }
                        });
                        
                        if (hasEmptyWeaponSelect) {
                            allChoicesValid = false;
                            group.style.border = '2px solid #dc3545';
                        } else {
                            group.style.border = 'none';
                        }
                    } else {
                        group.style.border = 'none';
                    }
                }
            }
        });
        
        if (!allChoicesValid) {
            e.preventDefault();
            alert('Veuillez faire tous les choix d\'équipement requis.');
            return;
        }
        
        // Confirmation avant création
        if (!confirm('Êtes-vous sûr de vouloir créer ce personnage ? Cette action est définitive.')) {
            e.preventDefault();
            return;
        }
        
        // Désactiver le bouton pour éviter les double-clics
        createBtn.disabled = true;
        createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
    });
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.6s ease;
}

.equipment-group {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: border-color 0.3s ease;
}

.equipment-group:hover {
    border-color: #0d6efd;
}

.equipment-item {
    margin-bottom: 10px;
}

.form-check-label {
    cursor: pointer;
}

.form-select {
    margin-top: 8px;
}

.list-unstyled li {
    margin-bottom: 0.25rem;
}

.btn-success {
    background-color: #198754;
    border-color: #198754;
}

.btn-success:hover {
    background-color: #157347;
    border-color: #146c43;
}
</style>

