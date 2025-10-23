<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

$page_title = "Création de PNJ - Étape 4";
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
if (!$sessionData || $sessionData['step'] < 4) {
    header('Location: npc_create_step1.php');
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

// Valeurs par défaut des caractéristiques pour les PNJ
$defaultStats = [
    'strength' => 10,
    'dexterity' => 10,
    'constitution' => 10,
    'intelligence' => 10,
    'wisdom' => 10,
    'charisma' => 10
];

// Récupérer les caractéristiques déjà définies
$currentStats = array_merge($defaultStats, [
    'strength' => $sessionData['data']['strength'] ?? 10,
    'dexterity' => $sessionData['data']['dexterity'] ?? 10,
    'constitution' => $sessionData['data']['constitution'] ?? 10,
    'intelligence' => $sessionData['data']['intelligence'] ?? 10,
    'wisdom' => $sessionData['data']['wisdom'] ?? 10,
    'charisma' => $sessionData['data']['charisma'] ?? 10
]);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_abilities') {
        $stats = [
            'strength' => (int)$_POST['strength'],
            'dexterity' => (int)$_POST['dexterity'],
            'constitution' => (int)$_POST['constitution'],
            'intelligence' => (int)$_POST['intelligence'],
            'wisdom' => (int)$_POST['wisdom'],
            'charisma' => (int)$_POST['charisma']
        ];
        
        // Validation basique
        $valid = true;
        foreach ($stats as $stat => $value) {
            if ($value < 1 || $value > 20) {
                $valid = false;
                break;
            }
        }
        
        if ($valid) {
            // Sauvegarder les caractéristiques
            if (saveNPCCreationStep($user_id, $session_id, 5, $stats)) {
                header("Location: npc_create_step5.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde des caractéristiques.", "error");
            }
        } else {
            $message = displayMessage("Les caractéristiques doivent être entre 1 et 20.", "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: npc_create_step3.php?session_id=$session_id");
        exit();
    }
}

// Calculer les bonus raciaux
$racialBonuses = [
    'strength' => $selectedRace['strength_bonus'] ?? 0,
    'dexterity' => $selectedRace['dexterity_bonus'] ?? 0,
    'constitution' => $selectedRace['constitution_bonus'] ?? 0,
    'intelligence' => $selectedRace['intelligence_bonus'] ?? 0,
    'wisdom' => $selectedRace['wisdom_bonus'] ?? 0,
    'charisma' => $selectedRace['charisma_bonus'] ?? 0
];

// Calculer les valeurs finales avec bonus raciaux
$finalStats = [];
foreach ($currentStats as $stat => $value) {
    $finalStats[$stat] = $value + $racialBonuses[$stat];
}

// Obtenir les préconisations pour la classe sélectionnée
$dndRecommendations = $selectedClass ? getDnDRecommendations($selectedClass['name']) : null;
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
            width: 44.44%; /* 4/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .ability-score {
            font-size: 1.2em;
            font-weight: bold;
        }
        .ability-modifier {
            font-size: 0.9em;
            color: #6c757d;
        }
        .stat-input {
            width: 80px;
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
                    <p class="mb-0">Étape 4 sur 12 - Définissez les caractéristiques</p>
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
                                <i class="fas fa-book me-1"></i>
                                <strong>Historique :</strong> <?php echo htmlspecialchars($selectedBackground['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Préconisations D&D -->
        <?php if ($dndRecommendations): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb me-2"></i>Préconisations D&D pour <?php echo htmlspecialchars($selectedClass['name']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-2"><?php echo $dndRecommendations['description']; ?></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary">Caractéristiques principales :</h6>
                                            <ul class="list-unstyled">
                                                <?php foreach ($dndRecommendations['primary'] as $stat): ?>
                                                    <li><i class="fas fa-star text-warning me-2"></i><?php echo $stat; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-secondary">Caractéristiques secondaires :</h6>
                                            <ul class="list-unstyled">
                                                <?php foreach ($dndRecommendations['secondary'] as $stat): ?>
                                                    <li><i class="fas fa-circle text-muted me-2"></i><?php echo $stat; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <button type="button" class="btn btn-outline-primary" onclick="applyDnDRecommendations()">
                                        <i class="fas fa-magic me-2"></i>Appliquer les préconisations
                                    </button>
                                    <small class="text-muted d-block mt-2">Ce bouton appliquera automatiquement les valeurs recommandées pour cette classe</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-dice-d20 me-2"></i>Caractéristiques du PNJ</h3>
                        <p class="mb-0 text-muted">Définissez les valeurs de base des caractéristiques (les bonus raciaux seront ajoutés automatiquement).</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="abilitiesForm">
                            <input type="hidden" name="action" value="set_abilities">
                            
                            <div class="row">
                                <?php 
                                $abilities = [
                                    'strength' => ['name' => 'Force', 'icon' => 'fist-raised', 'color' => 'danger'],
                                    'dexterity' => ['name' => 'Dextérité', 'icon' => 'running', 'color' => 'success'],
                                    'constitution' => ['name' => 'Constitution', 'icon' => 'heart', 'color' => 'warning'],
                                    'intelligence' => ['name' => 'Intelligence', 'icon' => 'brain', 'color' => 'info'],
                                    'wisdom' => ['name' => 'Sagesse', 'icon' => 'eye', 'color' => 'primary'],
                                    'charisma' => ['name' => 'Charisme', 'icon' => 'star', 'color' => 'secondary']
                                ];
                                
                                foreach ($abilities as $ability => $info): 
                                    $baseValue = $currentStats[$ability];
                                    $racialBonus = $racialBonuses[$ability];
                                    $finalValue = $finalStats[$ability];
                                    $modifier = floor(($finalValue - 10) / 2);
                                ?>
                                    <div class="col-md-4 col-lg-2 mb-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h6 class="card-title text-<?php echo $info['color']; ?>">
                                                    <i class="fas fa-<?php echo $info['icon']; ?> me-1"></i>
                                                    <?php echo $info['name']; ?>
                                                </h6>
                                                
                                                <div class="mb-3">
                                                    <label for="<?php echo $ability; ?>" class="form-label small">Valeur de base</label>
                                                    <input type="number" 
                                                           class="form-control stat-input mx-auto" 
                                                           id="<?php echo $ability; ?>" 
                                                           name="<?php echo $ability; ?>" 
                                                           value="<?php echo $baseValue; ?>" 
                                                           min="1" max="20" required>
                                                </div>
                                                
                                                <?php if ($racialBonus > 0): ?>
                                                    <div class="mb-2">
                                                        <small class="text-success">
                                                            <i class="fas fa-plus me-1"></i>
                                                            Bonus racial : +<?php echo $racialBonus; ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="ability-score text-<?php echo $info['color']; ?>">
                                                    <?php echo $finalValue; ?>
                                                </div>
                                                <div class="ability-modifier">
                                                    Modificateur : <?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 5
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
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 3
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mise à jour automatique des valeurs finales quand on change les valeurs de base
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    updateFinalValue(this);
                });
            });
            
            function updateFinalValue(input) {
                const ability = input.name;
                const baseValue = parseInt(input.value) || 0;
                const card = input.closest('.card');
                const finalValueElement = card.querySelector('.ability-score');
                const modifierElement = card.querySelector('.ability-modifier');
                
                // Calculer la valeur finale (base + bonus racial)
                const racialBonus = <?php echo json_encode($racialBonuses); ?>[ability] || 0;
                const finalValue = baseValue + racialBonus;
                const modifier = Math.floor((finalValue - 10) / 2);
                
                // Mettre à jour l'affichage
                finalValueElement.textContent = finalValue;
                modifierElement.textContent = `Modificateur : ${modifier >= 0 ? '+' : ''}${modifier}`;
            }
        });
        
        // Fonction pour appliquer les préconisations D&D
        function applyDnDRecommendations() {
            const className = '<?php echo $selectedClass ? addslashes($selectedClass['name']) : ''; ?>';
            
            // Valeurs recommandées selon la classe (point buy system)
            const recommendations = {
                'Barbare': { strength: 15, dexterity: 13, constitution: 14, wisdom: 12, intelligence: 8, charisma: 10 },
                'Barde': { strength: 8, dexterity: 14, constitution: 13, wisdom: 10, intelligence: 12, charisma: 15 },
                'Clerc': { strength: 13, dexterity: 12, constitution: 14, wisdom: 15, intelligence: 8, charisma: 10 },
                'Druide': { strength: 8, dexterity: 13, constitution: 14, wisdom: 15, intelligence: 12, charisma: 10 },
                'Guerrier': { strength: 15, dexterity: 13, constitution: 14, wisdom: 10, intelligence: 12, charisma: 8 },
                'Moine': { strength: 12, dexterity: 15, constitution: 13, wisdom: 14, intelligence: 10, charisma: 8 },
                'Paladin': { strength: 15, dexterity: 12, constitution: 13, wisdom: 10, intelligence: 8, charisma: 14 },
                'Magicien': { strength: 8, dexterity: 13, constitution: 14, wisdom: 12, intelligence: 15, charisma: 10 },
                'Ensorceleur': { strength: 8, dexterity: 13, constitution: 14, wisdom: 10, intelligence: 12, charisma: 15 },
                'Occultiste': { strength: 8, dexterity: 13, constitution: 14, wisdom: 10, intelligence: 12, charisma: 15 },
                'Roublard': { strength: 8, dexterity: 15, constitution: 13, wisdom: 10, intelligence: 14, charisma: 12 },
                'Rôdeur': { strength: 8, dexterity: 15, constitution: 13, wisdom: 14, intelligence: 12, charisma: 10 }
            };
            
            const classRecommendations = recommendations[className];
            if (!classRecommendations) {
                alert('Aucune préconisation trouvée pour cette classe.');
                return;
            }
            
            // Appliquer les valeurs recommandées
            Object.keys(classRecommendations).forEach(stat => {
                const input = document.querySelector(`input[name="${stat}"]`);
                if (input) {
                    input.value = classRecommendations[stat];
                }
            });
            
            // Mettre à jour les statistiques dérivées
            setTimeout(function() {
                const event = new Event('input');
                document.querySelectorAll('.stat-input').forEach(input => {
                    input.dispatchEvent(event);
                });
            }, 100);
            
            // Afficher un message de confirmation
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Préconisations D&D appliquées pour ${className} !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insérer l'alerte après le formulaire
            const form = document.getElementById('abilitiesForm');
            form.parentNode.insertBefore(alertDiv, form.nextSibling);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>