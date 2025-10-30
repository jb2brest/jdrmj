<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
$page_title = "Création de Personnage - Étape 6";
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
if (!$sessionData || $sessionData['step'] < 6) {
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

// Récupérer toutes les langues disponibles (sauf Commun qui est par défaut)
$allLanguages = $pdo->query("SELECT * FROM languages WHERE name != 'Commun' ORDER BY name")->fetchAll();

// Définir les compétences disponibles
$allSkills = [
    'Acrobaties' => 'Dextérité',
    'Arcanes' => 'Intelligence',
    'Athlétisme' => 'Force',
    'Discrétion' => 'Dextérité',
    'Dressage' => 'Sagesse',
    'Escamotage' => 'Dextérité',
    'Histoire' => 'Intelligence',
    'Intimidation' => 'Charisme',
    'Intuition' => 'Sagesse',
    'Investigation' => 'Intelligence',
    'Médecine' => 'Sagesse',
    'Nature' => 'Intelligence',
    'Perception' => 'Sagesse',
    'Persuasion' => 'Charisme',
    'Religion' => 'Intelligence',
    'Représentation' => 'Charisme',
    'Survie' => 'Sagesse',
    'Tromperie' => 'Charisme'
];

// Calculer les compétences disponibles selon la classe
$classSkillChoices = [];
$classSkillCount = 0;
if ($selectedClass) {
    $classSkillCount = (int)$selectedClass['skill_count'];
    if ($selectedClass['skill_choices']) {
        $classSkillChoices = array_map('trim', explode(',', $selectedClass['skill_choices']));
    }
}

// Calculer les compétences de l'historique (FIXES)
$backgroundSkills = [];
if ($selectedBackground && $selectedBackground['skill_proficiencies']) {
    $backgroundSkills = json_decode($selectedBackground['skill_proficiencies'], true) ?? [];
}

// Calculer les compétences raciales (FIXES) - pour l'instant aucune race n'a de compétences spécifiques
$raceSkills = [];

// Compétences déjà acquises (fixes)
$fixedSkills = array_merge($backgroundSkills, $raceSkills);

// Calculer les langues fixes et au choix
$fixedLanguages = [];
$choiceLanguageCount = 0;

// Langues fixes de la race
if ($selectedRace && $selectedRace['languages']) {
    $raceLanguages = $selectedRace['languages'];
    // Parser les langues de race (format: "commun, elfique, une langue de votre choix")
    $raceLangArray = array_map('trim', explode(',', $raceLanguages));
    
    foreach ($raceLangArray as $lang) {
        if (strpos($lang, 'choix') !== false) {
            // Compter les langues au choix de la race
            if (strpos($lang, 'deux') !== false) {
                $choiceLanguageCount += 2;
            } elseif (strpos($lang, 'une') !== false) {
                $choiceLanguageCount += 1;
            }
        } else {
            // Langue fixe
            $fixedLanguages[] = $lang;
        }
    }
}

// Langues au choix de l'historique
if ($selectedBackground && $selectedBackground['languages']) {
    $backgroundLanguages = json_decode($selectedBackground['languages'], true) ?? [];
    // Compter les langues "de votre choix" de l'historique
    foreach ($backgroundLanguages as $lang) {
        if (strpos($lang, 'choix') !== false) {
            if (strpos($lang, 'deux') !== false) {
                $choiceLanguageCount += 2;
            } elseif (strpos($lang, 'une') !== false) {
                $choiceLanguageCount += 1;
            }
        }
    }
}

// Filtrer les langues disponibles (exclure celles déjà connues)
$availableLanguages = [];
foreach ($allLanguages as $language) {
    $langName = strtolower($language['name']);
    $isAlreadyKnown = false;
    
    // Vérifier si la langue est déjà connue (fixe)
    foreach ($fixedLanguages as $fixedLang) {
        if (strtolower($fixedLang) === $langName) {
            $isAlreadyKnown = true;
            break;
        }
    }
    
    if (!$isAlreadyKnown) {
        $availableLanguages[] = $language;
    }
}

// Récupérer les choix déjà faits
$selectedSkills = $sessionData['data']['selected_skills'] ?? [];
$selectedLanguages = $sessionData['data']['selected_languages'] ?? [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'select_skills_languages') {
        $skills = $_POST['skills'] ?? [];
        $languages = $_POST['languages'] ?? [];
        
        // Validation des compétences
        $validSkills = true;
        if (count($skills) !== $classSkillCount) {
            $validSkills = false;
            $message = displayMessage("Vous devez choisir exactement $classSkillCount compétence(s) de classe.", "error");
        }
        
        // Validation des langues
        $validLanguages = true;
        if (count($languages) !== $choiceLanguageCount) {
            $validLanguages = false;
            $message = displayMessage("Vous devez choisir exactement $choiceLanguageCount langue(s) supplémentaire(s).", "error");
        }
        
        if ($validSkills && $validLanguages) {
            // Combiner les compétences fixes et les compétences choisies
            $allSelectedSkills = array_merge($fixedSkills, $skills);
            
            // Combiner les langues fixes et les langues choisies
            $allSelectedLanguages = array_merge($fixedLanguages, $languages);
            
            // Sauvegarder les choix
            $dataToSave = [
                'selected_skills' => $allSelectedSkills,
                'fixed_skills' => $fixedSkills,
                'chosen_skills' => $skills,
                'selected_languages' => $allSelectedLanguages,
                'fixed_languages' => $fixedLanguages,
                'chosen_languages' => $languages
            ];
            
            if (saveCharacterCreationStep($user_id, $session_id, 7, $dataToSave)) {
                header("Location: character_create_step7.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde des choix.", "error");
            }
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: character_create_step5.php?session_id=$session_id");
        exit();
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
            width: 66.67%; /* 6/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .skill-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .skill-card:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .skill-card.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .skill-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .language-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .language-card:hover {
            border-color: #28a745;
            background-color: #f8f9fa;
        }
        .language-card.selected {
            border-color: #28a745;
            background-color: #e7f3ff;
        }
        .language-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .skill-ability {
            font-size: 0.8em;
            color: #6c757d;
        }
        .selection-counter {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
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
                    <p class="mb-0">Étape 6 sur 9 - Choisissez vos compétences et langues</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 6/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-2">
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
            <div class="col-md-2">
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
            <div class="col-md-2">
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
            <div class="col-md-2">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-dice-d20 me-1"></i>
                            <strong>Caractéristiques :</strong> Définies
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-star me-1"></i>
                            <strong>Spécialisation :</strong> Choisie
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-graduation-cap me-2"></i>Compétences et langues</h3>
                        <p class="mb-0 text-muted">Choisissez vos compétences de classe et vos langues supplémentaires selon votre historique.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="skillsLanguagesForm">
                            <input type="hidden" name="action" value="select_skills_languages">
                            
                            <!-- Compteurs de sélection -->
                            <div class="selection-counter">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-tasks me-2"></i>Compétences</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-success me-1"><?php echo count($fixedSkills); ?></span>
                                            <small class="text-muted">acquises</small>
                                            <span class="badge bg-primary me-2 ms-2" id="skills-selected">0</span>/<span id="skills-total"><?php echo $classSkillCount; ?></span>
                                            <small class="text-muted ms-2">à choisir</small>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-language me-2"></i>Langues</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-success me-1"><?php echo count($fixedLanguages); ?></span>
                                            <small class="text-muted">acquises</small>
                                            <span class="badge bg-success me-2 ms-2" id="languages-selected">0</span>/<span id="languages-total"><?php echo $choiceLanguageCount; ?></span>
                                            <small class="text-muted ms-2">à choisir</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Compétences -->
                                <div class="col-md-6">
                                    <h5><i class="fas fa-tasks me-2"></i>Compétences</h5>
                                    
                                    <!-- Compétences de classe au choix -->
                                    <?php if ($classSkillCount > 0): ?>
                                        <div class="mb-4">
                                            <h6 class="text-primary"><i class="fas fa-hand-pointer me-2"></i>Compétences de classe au choix</h6>
                                            <p class="text-muted">Choisissez <?php echo $classSkillCount; ?> compétence(s) parmi :</p>
                                            
                                            <?php foreach ($classSkillChoices as $skill): ?>
                                                <?php 
                                                    $isBackgroundProvided = in_array($skill, $backgroundSkills, true);
                                                    $inputId = 'skill_' . strtolower(str_replace(' ', '_', $skill));
                                                ?>
                                                <div class="skill-card<?php echo $isBackgroundProvided ? ' selected' : ''; ?>" data-skill="<?php echo htmlspecialchars($skill); ?>">
                                                    <div class="form-check">
                    								<input class="form-check-input" type="checkbox" 
                                                               name="skills[]" 
                                                               value="<?php echo htmlspecialchars($skill); ?>" 
                                                               id="<?php echo $inputId; ?>"
                                                               <?php echo $isBackgroundProvided ? 'checked disabled data-fixed="1"' : ''; ?>>
                                                        <label class="form-check-label" for="<?php echo $inputId; ?>">
                                                            <strong><?php echo htmlspecialchars($skill); ?></strong>
                                                            <?php if ($isBackgroundProvided): ?>
                                                                <span class="ms-1 text-muted">(historique)</span>
                                                            <?php endif; ?>
                                                            <div class="skill-ability">(<?php echo $allSkills[$skill] ?? 'Inconnue'; ?>)</div>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Langues -->
                                <div class="col-md-6">
                                    <h5><i class="fas fa-language me-2"></i>Langues</h5>
                                    
                                    <!-- Langues fixes (déjà acquises) -->
                                    <?php if (!empty($fixedLanguages)): ?>
                                        <div class="mb-4">
                                            <h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Langues acquises automatiquement</h6>
                                            <div class="alert alert-success">
                                                <?php foreach ($fixedLanguages as $lang): ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        <strong><?php echo htmlspecialchars(ucfirst($lang)); ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Langues au choix -->
                                    <?php if ($choiceLanguageCount > 0): ?>
                                        <div class="mb-4">
                                            <h6 class="text-success"><i class="fas fa-hand-pointer me-2"></i>Langues au choix</h6>
                                            <p class="text-muted">Choisissez <?php echo $choiceLanguageCount; ?> langue(s) supplémentaire(s) :</p>
                                            <?php if (count($availableLanguages) < count($allLanguages)): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <small>Les langues déjà connues (<?php echo implode(', ', array_map('ucfirst', $fixedLanguages)); ?>) ne sont pas proposées dans cette liste.</small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php foreach ($availableLanguages as $language): ?>
                                                <div class="language-card" data-language="<?php echo htmlspecialchars($language['name']); ?>">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="languages[]" 
                                                               value="<?php echo htmlspecialchars($language['name']); ?>" 
                                                               id="lang_<?php echo strtolower(str_replace(' ', '_', $language['name'])); ?>">
                                                        <label class="form-check-label" for="lang_<?php echo strtolower(str_replace(' ', '_', $language['name'])); ?>">
                                                            <strong><?php echo htmlspecialchars($language['name']); ?></strong>
                                                            <?php if ($language['type'] === 'exotique'): ?>
                                                                <span class="badge bg-warning ms-2">Exotique</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Pas de langues supplémentaires :</strong> Votre race et votre historique ne vous accordent pas de langues au choix.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 7
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
            const skillCards = document.querySelectorAll('.skill-card');
            const languageCards = document.querySelectorAll('.language-card');
            const skillCheckboxes = document.querySelectorAll('input[name="skills[]"]');
            const languageCheckboxes = document.querySelectorAll('input[name="languages[]"]');
            const continueBtn = document.getElementById('continueBtn');
            
            const skillsTotal = <?php echo $classSkillCount; ?>;
            const languagesTotal = <?php echo $choiceLanguageCount; ?>;
            
            function updateCounters() {
                const skillsSelected = document.querySelectorAll('input[name="skills[]"]:checked:not([data-fixed="1"])').length;
                const languagesSelected = document.querySelectorAll('input[name="languages[]"]:checked').length;
                
                document.getElementById('skills-selected').textContent = skillsSelected;
                document.getElementById('languages-selected').textContent = languagesSelected;
                
                // Activer/désactiver le bouton continuer
                const canContinue = (skillsSelected === skillsTotal) && (languagesSelected === languagesTotal);
                continueBtn.disabled = !canContinue;
                
                // Mettre à jour l'apparence des cartes
                skillCards.forEach(card => {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    if (checkbox.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
                
                languageCards.forEach(card => {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    if (checkbox.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
            }
            
            // Gestion des clics sur les cartes de compétences
            skillCards.forEach(card => {
                card.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox.disabled) { return; }
                    const currentSelected = document.querySelectorAll('input[name="skills[]"]:checked').length;
                    
                    if (checkbox.checked) {
                        checkbox.checked = false;
                    } else if (currentSelected < skillsTotal) {
                        checkbox.checked = true;
                    }
                    
                    updateCounters();
                });
            });
            
            // Gestion des clics sur les cartes de langues
            languageCards.forEach(card => {
                card.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    const currentSelected = document.querySelectorAll('input[name="languages[]"]:checked').length;
                    
                    if (checkbox.checked) {
                        checkbox.checked = false;
                    } else if (currentSelected < languagesTotal) {
                        checkbox.checked = true;
                    }
                    
                    updateCounters();
                });
            });
            
            // Gestion des changements de checkbox
            skillCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCounters);
            });
            
            languageCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCounters);
            });
            
            // Initialiser les compteurs
            updateCounters();
        });
    </script>
</body>
</html>
