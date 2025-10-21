<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
require_once 'includes/starting_equipment_functions.php';

$page_title = "Création de PNJ - Étape 9";
$current_page = "create_npc";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

if (!$session_id) {
    header('Location: npc_create_step1.php');
    exit();
}

// Récupérer les données de la session
$sessionData = getNPCCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 9) {
    header('Location: npc_create_step1.php');
    exit();
}

$data = $sessionData['data'];

// Récupérer les données sélectionnées
$selectedClassId = $sessionData['data']['class_id'] ?? null;
$selectedRaceId = $sessionData['data']['race_id'] ?? null;
$selectedBackgroundId = $sessionData['data']['background_id'] ?? null;

// Récupérer les informations des choix précédents
$selectedClass = null;
$selectedRace = null;
$selectedBackground = null;

if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $selectedClass = $stmt->fetch();
}

if ($selectedRaceId) {
    $stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->execute([$selectedRaceId]);
    $selectedRace = $stmt->fetch();
}

if ($selectedBackgroundId) {
    $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
    $stmt->execute([$selectedBackgroundId]);
    $selectedBackground = $stmt->fetch();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_equipment = $_POST['class_equipment'] ?? [];
    $class_weapon_choices = $_POST['class_weapon_choices'] ?? [];
    $class_weapon_choice = $_POST['class_weapon_choice'] ?? []; // Nouveaux menus déroulants
    
    $background_equipment = $_POST['background_equipment'] ?? [];
    $background_weapon_choices = $_POST['background_weapon_choices'] ?? [];
    $background_weapon_choice = $_POST['background_weapon_choice'] ?? []; // Nouveaux menus déroulants
    
    $race_equipment = $_POST['race_equipment'] ?? [];
    
    // Traiter les sélections d'armes et d'instruments
    $selectedWeapons = [];
    
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
    
    // Générer l'équipement final avec la nouvelle table starting_equipment
    $equipmentData = generateFinalEquipmentNew($data['class_id'], $data['background_id'], $data['race_id'], [
        'class' => $class_equipment,
        'background' => $background_equipment,
        'race' => $race_equipment,
        'selected_weapons' => $selectedWeapons,
    ]);
    
    $finalEquipment = implode("\n", $equipmentData['equipment']);
    $totalGold = $equipmentData['gold'];
    
    // Sauvegarder les données
    $stepData = [
        'starting_equipment' => $finalEquipment,
        'gold' => $totalGold,
        'class_equipment_choices' => $class_equipment,
        'class_weapon_choices' => $class_weapon_choices,
        'background_equipment_choices' => $background_equipment,
        'background_weapon_choices' => $background_weapon_choices,
        'race_equipment_choices' => $race_equipment,
    ];
    
    if (saveNPCCreationStep($user_id, $session_id, 9, $stepData)) {
        header("Location: npc_create_step11.php?session_id=$session_id");
        exit();
    } else {
        $message = displayMessage("Erreur lors de la sauvegarde de l'équipement.", "error");
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'go_back') {
    header("Location: npc_create_step8.php?session_id=$session_id");
    exit();
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

// Récupérer les armes de guerre et courantes pour les menus déroulants
$warWeapons = Item::getWarWeapons();
$commonWeapons = Item::getCommonWeapons();
$allWeapons = array_merge($warWeapons, $commonWeapons);
$weaponsByType = [];
foreach ($allWeapons as $weapon) {
    $weaponsByType[$weapon['type']][] = $weapon;
}

// Fonction helper pour afficher une option d'équipement avec menu déroulant si nécessaire
function displayEquipmentOption($item, $groupId, $optionLetter, $inputName, $inputId) {
    // Vérifier si c'est un équipement fusionné
    if (isset($item['merged_items']) && is_array($item['merged_items'])) {
        // Équipement fusionné - afficher tous les items
        $items = [];
        foreach ($item['merged_items'] as $mergedItem) {
            $items[] = getEquipmentDisplayName($mergedItem);
        }
        echo implode(' + ', $items);
    } else {
        // Équipement simple
        if (isset($item['type_filter']) && 
            (strpos($item['type_filter'], 'Armes de guerre') !== false ||
             strpos($item['type_filter'], 'Armes courantes') !== false)) {
            // C'est une arme - afficher un menu déroulant
            global $weaponsByType;
            $weaponsType = $item['type_filter'];
            $availableWeapons = $weaponsByType[$weaponsType] ?? [];
            
            if (!empty($availableWeapons)) {
                echo '<select class="form-select form-select-sm d-inline-block" name="' . str_replace('equipment', 'weapon_choice', $inputName) . '[' . $groupId . '][' . $optionLetter . ']" style="width: auto; min-width: 200px;">';
                echo '<option value="">-- Choisir une arme --</option>';
                foreach ($availableWeapons as $weapon) {
                    echo '<option value="' . htmlspecialchars($weapon['name']) . '">' . htmlspecialchars($weapon['name']) . '</option>';
                }
                echo '</select>';
            } else {
                // Équipement normal - affichage standard
                echo htmlspecialchars(getEquipmentDisplayName($item));
            }
        } else {
            // Équipement normal - affichage standard
            echo htmlspecialchars(getEquipmentDisplayName($item));
        }
    }
}

// Fonction helper pour afficher le nom d'un équipement (simple ou fusionné)
function getEquipmentDisplayName($item) {
    if (isset($item['merged_items']) && is_array($item['merged_items'])) {
        // Équipement fusionné - afficher tous les items
        $mergedItems = [];
        foreach ($item['merged_items'] as $mergedItem) {
            $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
            $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type_filter'] ?? $mergedItem['type'];
            $mergedItems[] = $itemName;
        }
        return implode(' + ', $mergedItems);
    } else {
        // Équipement simple
        $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
        $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $item['type_filter'] ?? $item['type'];
        return $itemName;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <style>
        .step-progress-bar {
            width: 100%; /* 9/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-tie me-3"></i>Création de PNJ</h1>
                    <p class="mb-0">Étape 9 sur 11 - Équipement</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 9/11</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-3">
                <?php if ($selectedClass): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                <strong>Classe :</strong> <?php echo htmlspecialchars($selectedClass['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <?php if ($selectedRace): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i>
                                <strong>Race :</strong> <?php echo htmlspecialchars($selectedRace['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <?php if ($selectedBackground): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-book me-1"></i>
                                <strong>Historique :</strong> <?php echo htmlspecialchars($selectedBackground['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-scroll me-1"></i>
                            <strong>Histoire :</strong> Définie
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-sword me-2"></i>Équipement de départ</h3>
                        <p class="mb-0 text-muted">Choisissez l'équipement de départ selon la classe, l'historique et la race du PNJ.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="equipmentForm">
                            
                            <!-- Équipement de classe -->
                            <?php if (!empty($classChoices)): ?>
                                <div class="equipment-section mb-4">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-shield-alt me-2"></i>Équipement de classe (<?php echo htmlspecialchars($selectedClass['name']); ?>)
                                    </h5>
                                    
                                    <?php 
                                    // Afficher d'abord l'équipement obligatoire (choiceId = 0)
                                    if (isset($classChoices[0]) && isset($classChoices[0]['options'])) {
                                        echo '<div class="equipment-choice mb-3">';
                                        echo '<h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Équipement obligatoire</h6>';
                                        echo '<div class="equipment-options">';
                                        foreach ($classChoices[0]['options'] as $item) {
                                            $itemDetails = getEquipmentDetails($item['type'], $item['type_id']);
                                            if ($itemDetails) {
                                                $itemName = $itemDetails['nom'] ?? $itemDetails['name'] ?? $item['type_filter'] ?? $item['type'];
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
                                    
                                    // Afficher les choix d'équipement
                                    foreach ($classChoices as $choiceIndex => $choice): 
                                        // Ignorer l'équipement obligatoire (choiceId = 0) car déjà affiché
                                        if ($choiceIndex == 0) continue;
                                        
                                        if (isset($choice['type']) && $choice['type'] === 'choice'): ?>
                                            <!-- Choix multiple -->
                                            <div class="equipment-choice mb-3">
                                                <h6 class="text-primary"><?php echo htmlspecialchars($choice['description']); ?></h6>
                                                <div class="equipment-options">
                                                    <?php foreach ($choice['options'] as $optionLetter => $option): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" name="class_equipment[<?php echo $choiceIndex; ?>]" value="<?php echo $optionLetter; ?>" id="class_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                            <label class="form-check-label" for="class_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                                <strong><?php echo $optionLetter; ?>.</strong> 
                                                                <?php displayEquipmentOption($option, $choiceIndex, $optionLetter, 'class_equipment', 'class'); ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Équipement d'historique -->
                            <?php if (!empty($backgroundChoices)): ?>
                                <div class="equipment-section mb-4">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-book me-2"></i>Équipement d'historique (<?php echo htmlspecialchars($selectedBackground['name']); ?>)
                                    </h5>
                                    
                                    <?php 
                                    // Afficher d'abord l'équipement obligatoire (choiceId = 0)
                                    if (isset($backgroundChoices[0]) && isset($backgroundChoices[0]['options'])) {
                                        echo '<div class="equipment-choice mb-3">';
                                        echo '<h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Équipement obligatoire</h6>';
                                        echo '<div class="equipment-options">';
                                        foreach ($backgroundChoices[0]['options'] as $item) {
                                            $itemDetails = getEquipmentDetails($item['type'], $item['type_id']);
                                            if ($itemDetails) {
                                                $itemName = $itemDetails['nom'] ?? $itemDetails['name'] ?? $item['type_filter'] ?? $item['type'];
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
                                    
                                    // Afficher les choix d'équipement
                                    foreach ($backgroundChoices as $choiceIndex => $choice): 
                                        // Ignorer l'équipement obligatoire (choiceId = 0) car déjà affiché
                                        if ($choiceIndex == 0) continue;
                                        
                                        if (isset($choice['type']) && $choice['type'] === 'choice'): ?>
                                            <!-- Choix multiple -->
                                            <div class="equipment-choice mb-3">
                                                <h6 class="text-primary"><?php echo htmlspecialchars($choice['description']); ?></h6>
                                                <div class="equipment-options">
                                                    <?php foreach ($choice['options'] as $optionLetter => $option): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" name="background_equipment[<?php echo $choiceIndex; ?>]" value="<?php echo $optionLetter; ?>" id="background_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                            <label class="form-check-label" for="background_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                                <strong><?php echo $optionLetter; ?>.</strong> 
                                                                <?php displayEquipmentOption($option, $choiceIndex, $optionLetter, 'background_equipment', 'background'); ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Équipement de race -->
                            <?php if (!empty($raceChoices)): ?>
                                <div class="equipment-section mb-4">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-users me-2"></i>Équipement de race (<?php echo htmlspecialchars($selectedRace['name']); ?>)
                                    </h5>
                                    
                                    <?php foreach ($raceChoices as $choiceIndex => $choice): ?>
                                        <?php if (isset($choice['type']) && $choice['type'] === 'choice'): ?>
                                            <!-- Choix multiple -->
                                            <div class="equipment-choice mb-3">
                                                <h6 class="text-primary"><?php echo htmlspecialchars($choice['description']); ?></h6>
                                                <div class="equipment-options">
                                                    <?php foreach ($choice['options'] as $optionLetter => $option): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" name="race_equipment[<?php echo $choiceIndex; ?>]" value="<?php echo $optionLetter; ?>" id="race_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                            <label class="form-check-label" for="race_<?php echo $choiceIndex; ?>_<?php echo $optionLetter; ?>">
                                                                <strong><?php echo $optionLetter; ?>.</strong> 
                                                                <?php displayEquipmentOption($option, $choiceIndex, $optionLetter, 'race_equipment', 'race'); ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Équipement fixe -->
                                            <div class="equipment-choice mb-3">
                                                <h6 class="text-primary"><?php echo htmlspecialchars($choice['description']); ?></h6>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($choice['description']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Finaliser la création
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Boutons de navigation -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="go_back">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 8
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>