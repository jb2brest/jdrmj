<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Fonction pour extraire le nombre du dé des points de vie
function extractDiceNumber($hitDice) {
    if (empty($hitDice)) {
        return 8; // Valeur par défaut
    }
    
    // Extraire le nombre après 'd' (ex: '1d12' -> 12)
    if (preg_match('/d(\d+)/', $hitDice, $matches)) {
        return (int)$matches[1];
    }
    
    // Si pas de format 'dX', essayer de convertir directement
    if (is_numeric($hitDice)) {
        return (int)$hitDice;
    }
    
    return 8; // Valeur par défaut
}

$page_title = "Création de Personnage - Étape 4";
$current_page = "create_character";

requireLogin();

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

if (!$session_id) {
    header('Location: character_create_step1.php');
    exit();
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 4) {
    header('Location: character_create_step1.php');
    exit();
}

$message = '';

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

// Valeurs par défaut des caractéristiques (méthode des 27 points)
$defaultStats = [
    'strength' => 8,
    'dexterity' => 8,
    'constitution' => 8,
    'intelligence' => 8,
    'wisdom' => 8,
    'charisma' => 8
];

// Récupérer les caractéristiques déjà définies
$currentStats = array_merge($defaultStats, [
    'strength' => $sessionData['data']['strength'] ?? 8,
    'dexterity' => $sessionData['data']['dexterity'] ?? 8,
    'constitution' => $sessionData['data']['constitution'] ?? 8,
    'intelligence' => $sessionData['data']['intelligence'] ?? 8,
    'wisdom' => $sessionData['data']['wisdom'] ?? 8,
    'charisma' => $sessionData['data']['charisma'] ?? 8
]);

// Calculer les points utilisés selon le tableau officiel
function calculatePointsUsed($stats) {
    $pointCosts = [
        8 => 0,
        9 => 1,
        10 => 2,
        11 => 3,
        12 => 4,
        13 => 5,
        14 => 7,
        15 => 9
    ];
    
    $pointsUsed = 0;
    foreach ($stats as $stat => $value) {
        $pointsUsed += $pointCosts[$value] ?? 0;
    }
    return $pointsUsed;
}

$pointsUsed = calculatePointsUsed($currentStats);
$pointsRemaining = 27 - $pointsUsed;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_characteristics') {
        $stats = [
            'strength' => (int)$_POST['strength'],
            'dexterity' => (int)$_POST['dexterity'],
            'constitution' => (int)$_POST['constitution'],
            'intelligence' => (int)$_POST['intelligence'],
            'wisdom' => (int)$_POST['wisdom'],
            'charisma' => (int)$_POST['charisma']
        ];
        
        // Validation des valeurs (entre 8 et 15 avec méthode des 27 points)
        $valid = true;
        $totalPointsUsed = calculatePointsUsed($stats);
        
        foreach ($stats as $stat => $value) {
            if ($value < 8 || $value > 15) {
                $valid = false;
                break;
            }
        }
        
        if ($valid && $totalPointsUsed <= 27) {
            // Calculer les bonus raciaux
            $racialBonuses = [
                'strength' => $selectedRace['strength_bonus'] ?? 0,
                'dexterity' => $selectedRace['dexterity_bonus'] ?? 0,
                'constitution' => $selectedRace['constitution_bonus'] ?? 0,
                'intelligence' => $selectedRace['intelligence_bonus'] ?? 0,
                'wisdom' => $selectedRace['wisdom_bonus'] ?? 0,
                'charisma' => $selectedRace['charisma_bonus'] ?? 0
            ];
            
            // Calculer les caractéristiques finales (base + bonus raciaux)
            $finalStats = [];
            foreach ($stats as $stat => $value) {
                $finalStats[$stat] = $value + $racialBonuses[$stat];
            }
            
            // Calculer les modificateurs
            $modifiers = [];
            foreach ($finalStats as $stat => $value) {
                $modifiers[$stat] = floor(($value - 10) / 2);
            }
            
            // Calculer les valeurs dérivées
            $armorClass = 10 + $modifiers['dexterity'];
            $speed = $selectedRace['speed'] ?? 30;
            $hitPoints = extractDiceNumber($selectedClass['hit_dice'] ?? '1d8') + $modifiers['constitution'];
            $proficiencyBonus = 2; // Niveau 1
            
            // Sauvegarder les caractéristiques
            $dataToSave = array_merge($stats, [
                'armor_class' => $armorClass,
                'speed' => $speed,
                'hit_points_max' => $hitPoints,
                'hit_points_current' => $hitPoints,
                'proficiency_bonus' => $proficiencyBonus,
                'level' => 1,
                'experience_points' => 0
            ]);
            
            if (saveCharacterCreationStep($user_id, $session_id, 5, $dataToSave)) {
                header("Location: character_create_step5.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde des caractéristiques.", "error");
            }
        } else {
            if ($totalPointsUsed > 27) {
                $message = displayMessage("Vous avez utilisé trop de points (" . $totalPointsUsed . "/27).", "error");
            } else {
                $message = displayMessage("Les caractéristiques doivent être entre 8 et 15.", "error");
            }
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: character_create_step3.php?session_id=$session_id");
        exit();
    }
}

// Calculer les bonus raciaux pour l'affichage
$racialBonuses = [
    'strength' => isset($selectedRace['strength_bonus']) ? (int)$selectedRace['strength_bonus'] : 0,
    'dexterity' => isset($selectedRace['dexterity_bonus']) ? (int)$selectedRace['dexterity_bonus'] : 0,
    'constitution' => isset($selectedRace['constitution_bonus']) ? (int)$selectedRace['constitution_bonus'] : 0,
    'intelligence' => isset($selectedRace['intelligence_bonus']) ? (int)$selectedRace['intelligence_bonus'] : 0,
    'wisdom' => isset($selectedRace['wisdom_bonus']) ? (int)$selectedRace['wisdom_bonus'] : 0,
    'charisma' => isset($selectedRace['charisma_bonus']) ? (int)$selectedRace['charisma_bonus'] : 0
];
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
        .step-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .step-progress {
            height: 4px;
            background-color: rgba(255,255,255,0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        .step-progress-bar {
            height: 100%;
            background-color: white;
            width: 44.44%; /* 4/9 * 100 */
            transition: width 0.3s ease;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .stat-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-input {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            width: 80px;
        }
        .stat-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .modifier {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .racial-bonus {
            font-size: 0.9rem;
            color: #28a745;
            font-weight: bold;
        }
        .final-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #dc3545;
        }
        .derived-stats {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 10px;
            padding: 20px;
        }
        .points-counter {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .points-counter.warning {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
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
                    <h1><i class="fas fa-user-plus me-3"></i>Création de Personnage</h1>
                    <p class="mb-0">Étape 4 sur 9 - Définissez vos caractéristiques</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 4/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-4">
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
            <div class="col-md-4">
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
            <div class="col-md-4">
                <?php if ($selectedBackground): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-scroll me-1"></i>
                                <strong>Historique :</strong> <?php echo htmlspecialchars($selectedBackground['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-dice-d20 me-2"></i>Définissez vos caractéristiques</h3>
                        <p class="mb-0 text-muted">Utilisez la méthode des 27 points pour attribuer des valeurs entre 8 et 15 à vos caractéristiques de base. Les bonus raciaux seront ajoutés automatiquement.</p>
                        <div class="mt-2">
                            <span class="badge bg-primary me-2">Points utilisés : <span id="points_used"><?php echo $pointsUsed; ?></span>/27</span>
                            <span class="badge bg-success">Points restants : <span id="points_remaining"><?php echo $pointsRemaining; ?></span></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="characteristicsForm">
                            <input type="hidden" name="action" value="set_characteristics">
                            
                            <!-- Compteur de points -->
                            <div class="points-counter" id="points_counter">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5><i class="fas fa-coins me-2"></i>Méthode des 27 points</h5>
                                        <p class="mb-0">Coût par valeur : 8=0, 9=1, 10=2, 11=3, 12=4, 13=5, 14=7, 15=9 points.</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="h4 mb-0">
                                            <span id="points_used_display"><?php echo $pointsUsed; ?></span>/27
                                        </div>
                                        <small class="text-muted">Points utilisés</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Caractéristiques -->
                            <div class="row mb-4">
                                <?php 
                                $stats = [
                                    'strength' => ['name' => 'Force', 'icon' => 'fist-raised', 'color' => 'danger'],
                                    'dexterity' => ['name' => 'Dextérité', 'icon' => 'running', 'color' => 'success'],
                                    'constitution' => ['name' => 'Constitution', 'icon' => 'heart', 'color' => 'warning'],
                                    'intelligence' => ['name' => 'Intelligence', 'icon' => 'brain', 'color' => 'info'],
                                    'wisdom' => ['name' => 'Sagesse', 'icon' => 'eye', 'color' => 'primary'],
                                    'charisma' => ['name' => 'Charisme', 'icon' => 'star', 'color' => 'secondary']
                                ];
                                
                                foreach ($stats as $stat => $info): 
                                    $currentValue = $currentStats[$stat];
                                    $racialBonus = $racialBonuses[$stat];
                                    $finalValue = $currentValue + $racialBonus;
                                    $modifier = floor(($finalValue - 10) / 2);
                                ?>
                                    <div class="col-md-4 col-lg-2 mb-3">
                                        <div class="stat-card">
                                            <h6 class="text-<?php echo $info['color']; ?>">
                                                <i class="fas fa-<?php echo $info['icon']; ?> me-1"></i>
                                                <?php echo $info['name']; ?>
                                            </h6>
                                            
                                            <input type="number" 
                                                   class="stat-input form-control mb-2" 
                                                   name="<?php echo $stat; ?>" 
                                                   value="<?php echo $currentValue; ?>" 
                                                   min="8" 
                                                   max="15" 
                                                   required>
                                            
                                            <?php if ($racialBonus > 0): ?>
                                                <div class="racial-bonus">
                                                    +<?php echo $racialBonus; ?> racial
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="final-value" id="final_<?php echo $stat; ?>">
                                                <?php echo $finalValue; ?>
                                            </div>
                                            
                                            <div class="modifier" id="modifier_<?php echo $stat; ?>">
                                                <?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Valeurs dérivées -->
                            <div class="derived-stats mb-4">
                                <h5><i class="fas fa-calculator me-2"></i>Valeurs dérivées</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Classe d'armure :</strong><br>
                                        <span class="text-primary">10 + Dex = <span id="armor_class"><?php 
                                            $dexModifier = floor((($currentStats['dexterity'] + ($racialBonuses['dexterity'] ?? 0)) - 10) / 2);
                                            echo (10 + $dexModifier); 
                                        ?></span></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Vitesse :</strong><br>
                                        <span class="text-success"><?php echo $selectedRace['speed'] ?? 30; ?> pieds</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Points de vie :</strong><br>
                                        <span class="text-danger">d<?php echo extractDiceNumber($selectedClass['hit_dice'] ?? '1d8'); ?> + Con = <span id="hit_points"><?php 
                                            $conModifier = floor((($currentStats['constitution'] + ($racialBonuses['constitution'] ?? 0)) - 10) / 2);
                                            $hitDiceNumber = extractDiceNumber($selectedClass['hit_dice'] ?? '1d8');
                                            echo ($hitDiceNumber + $conModifier); 
                                        ?></span></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Bonus de maîtrise :</strong><br>
                                        <span class="text-warning">+2 (niveau 1)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 5
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statInputs = document.querySelectorAll('.stat-input');
            const armorClassSpan = document.getElementById('armor_class');
            const hitPointsSpan = document.getElementById('hit_points');
            const pointsUsedSpan = document.getElementById('points_used');
            const pointsRemainingSpan = document.getElementById('points_remaining');
            const pointsUsedDisplaySpan = document.getElementById('points_used_display');
            const pointsCounter = document.getElementById('points_counter');
            
            // Valeurs par défaut
            const racialBonuses = <?php echo json_encode($racialBonuses); ?>;
            const hitDice = <?php echo extractDiceNumber($selectedClass['hit_dice'] ?? '1d8'); ?>;
            
            function calculatePointsUsed(stats) {
                const pointCosts = {
                    8: 0,
                    9: 1,
                    10: 2,
                    11: 3,
                    12: 4,
                    13: 5,
                    14: 7,
                    15: 9
                };
                
                let pointsUsed = 0;
                for (const stat in stats) {
                    const value = stats[stat];
                    pointsUsed += pointCosts[value] || 0;
                }
                return pointsUsed;
            }
            
            function updateDerivedStats() {
                // Vérifier que tous les éléments nécessaires existent
                const requiredElements = ['armor_class', 'hit_points', 'points_used', 'points_remaining', 'points_used_display', 'points_counter'];
                for (let elementId of requiredElements) {
                    if (!document.getElementById(elementId)) {
                        console.error('Element manquant:', elementId);
                        return;
                    }
                }
                
                // Calculer les statistiques actuelles
                const currentStats = {};
                statInputs.forEach(input => {
                    let value = parseInt(input.value);
                    // S'assurer que la valeur est dans les limites
                    if (value < 8) value = 8;
                    if (value > 15) value = 15;
                    input.value = value;
                    currentStats[input.name] = value;
                });
                
                // Calculer les points utilisés
                const pointsUsed = calculatePointsUsed(currentStats);
                const pointsRemaining = 27 - pointsUsed;
                
                // Mettre à jour l'affichage des points
                pointsUsedSpan.textContent = pointsUsed;
                pointsRemainingSpan.textContent = pointsRemaining;
                pointsUsedDisplaySpan.textContent = pointsUsed;
                
                // Changer la couleur du compteur si nécessaire
                if (pointsUsed > 27) {
                    pointsCounter.classList.add('warning');
                } else {
                    pointsCounter.classList.remove('warning');
                }
                
                // Calculer la dextérité finale
                const dexterity = currentStats.dexterity + (racialBonuses.dexterity || 0);
                const constitution = currentStats.constitution + (racialBonuses.constitution || 0);
                
                // Mettre à jour la classe d'armure
                const armorClass = 10 + Math.floor((dexterity - 10) / 2);
                armorClassSpan.textContent = armorClass;
                
                // Mettre à jour les points de vie
                const hitPoints = hitDice + Math.floor((constitution - 10) / 2);
                hitPointsSpan.textContent = hitPoints;
                
                // Mettre à jour les modificateurs et valeurs finales
                statInputs.forEach(input => {
                    const stat = input.name;
                    const baseValue = parseInt(input.value);
                    const racialBonus = racialBonuses[stat] || 0;
                    const finalValue = baseValue + racialBonus;
                    const modifier = Math.floor((finalValue - 10) / 2);
                    
                    // Mettre à jour les éléments avec les IDs spécifiques
                    const finalValueElement = document.getElementById('final_' + stat);
                    const modifierElement = document.getElementById('modifier_' + stat);
                    
                    if (finalValueElement) {
                        finalValueElement.textContent = finalValue;
                    }
                    if (modifierElement) {
                        modifierElement.textContent = (modifier >= 0 ? '+' : '') + modifier;
                    }
                });
            }
            
            statInputs.forEach(input => {
                input.addEventListener('input', updateDerivedStats);
                input.addEventListener('change', updateDerivedStats);
                input.addEventListener('blur', updateDerivedStats);
            });
            
            // Initialiser les valeurs dérivées après un petit délai pour s'assurer que le DOM est prêt
            setTimeout(function() {
                updateDerivedStats();
            }, 100);
        });
    </script>
</body>
</html>
