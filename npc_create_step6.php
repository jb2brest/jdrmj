<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

$page_title = "Création de PNJ - Étape 6";
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
if (!$sessionData || $sessionData['step'] < 6) {
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_skills_languages') {
        $selectedSkills = $_POST['skills'] ?? [];
        $selectedLanguages = $_POST['languages'] ?? [];
        
        // Validation
        $errors = [];
        
        // Vérifier le nombre de compétences
        if (count($selectedSkills) != $classSkillCount) {
            $errors[] = "Vous devez sélectionner exactement {$classSkillCount} compétence(s).";
        }
        
        // Vérifier le nombre de langues
        if (count($selectedLanguages) != $choiceLanguageCount) {
            $errors[] = "Vous devez sélectionner exactement {$choiceLanguageCount} langue(s).";
        }
        
        if (empty($errors)) {
            // Sauvegarder les choix
            $dataToSave = [
                'selected_skills' => $selectedSkills,
                'selected_languages' => $selectedLanguages,
                'fixed_skills' => $fixedSkills,
                'fixed_languages' => $fixedLanguages
            ];
            
            if (saveNPCCreationStep($user_id, $session_id, 7, $dataToSave)) {
                header("Location: npc_create_step7.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde des compétences et langues.", "error");
            }
        } else {
            $message = displayMessage(implode('<br>', $errors), "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: npc_create_step5.php?session_id=$session_id");
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
        .skill-group {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .skill-group h6 {
            color: #007bff;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .skill-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .skill-item:last-child {
            border-bottom: none;
        }
        .skill-name {
            flex: 1;
            font-weight: 500;
        }
        .skill-ability {
            color: #6c757d;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .language-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .language-item:last-child {
            border-bottom: none;
        }
        .language-name {
            flex: 1;
            font-weight: 500;
        }
        .fixed-item {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #495057;
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
                    <p class="mb-0">Étape 6 sur 12 - Compétences et Langues</p>
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
                            <i class="fas fa-star me-1"></i>
                            <strong>Spécialisation :</strong> Définie
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-brain me-2"></i>Compétences et Langues</h3>
                        <p class="mb-0 text-muted">Sélectionnez les compétences et langues du PNJ selon sa classe, race et historique.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="skillsLanguagesForm">
                            <input type="hidden" name="action" value="set_skills_languages">
                            
                            <div class="row">
                                <!-- Compétences -->
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-dice-d20 me-2"></i>Compétences
                                    </h5>
                                    
                                    <!-- Compétences fixes -->
                                    <?php if (!empty($fixedSkills)): ?>
                                        <div class="skill-group">
                                            <h6><i class="fas fa-lock me-2"></i>Compétences acquises</h6>
                                            <?php foreach ($fixedSkills as $skill): ?>
                                                <div class="skill-item">
                                                    <span class="fixed-item"><?php echo htmlspecialchars($skill); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Compétences au choix -->
                                    <?php if ($classSkillCount > 0): ?>
                                        <div class="skill-group">
                                            <h6>
                                                <i class="fas fa-check-circle me-2"></i>
                                                Choisissez <?php echo $classSkillCount; ?> compétence(s) parmi :
                                            </h6>
                                            <?php foreach ($classSkillChoices as $skill): ?>
                                                <div class="skill-item">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($skill); ?>" id="skill_<?php echo htmlspecialchars($skill); ?>">
                                                        <label class="form-check-label" for="skill_<?php echo htmlspecialchars($skill); ?>">
                                                            <span class="skill-name"><?php echo htmlspecialchars($skill); ?></span>
                                                            <span class="skill-ability">(<?php echo $allSkills[$skill] ?? 'Inconnue'; ?>)</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Cette classe n'a pas de compétences au choix.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Langues -->
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-language me-2"></i>Langues
                                    </h5>
                                    
                                    <!-- Langues fixes -->
                                    <?php if (!empty($fixedLanguages)): ?>
                                        <div class="skill-group">
                                            <h6><i class="fas fa-lock me-2"></i>Langues connues</h6>
                                            <?php foreach ($fixedLanguages as $language): ?>
                                                <div class="language-item">
                                                    <span class="fixed-item"><?php echo htmlspecialchars($language); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Langues au choix -->
                                    <?php if ($choiceLanguageCount > 0): ?>
                                        <div class="skill-group">
                                            <h6>
                                                <i class="fas fa-check-circle me-2"></i>
                                                Choisissez <?php echo $choiceLanguageCount; ?> langue(s) parmi :
                                            </h6>
                                            <?php foreach ($availableLanguages as $language): ?>
                                                <div class="language-item">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="languages[]" value="<?php echo htmlspecialchars($language['name']); ?>" id="lang_<?php echo htmlspecialchars($language['name']); ?>">
                                                        <label class="form-check-label" for="lang_<?php echo htmlspecialchars($language['name']); ?>">
                                                            <span class="language-name"><?php echo htmlspecialchars($language['name']); ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Aucune langue supplémentaire à choisir.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 7
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
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 5
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('skillsLanguagesForm');
            const skillCheckboxes = document.querySelectorAll('input[name="skills[]"]');
            const languageCheckboxes = document.querySelectorAll('input[name="languages[]"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Limiter le nombre de compétences sélectionnées
            skillCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedSkills = document.querySelectorAll('input[name="skills[]"]:checked');
                    if (checkedSkills.length > <?php echo $classSkillCount; ?>) {
                        this.checked = false;
                        alert('Vous ne pouvez sélectionner que <?php echo $classSkillCount; ?> compétence(s).');
                    }
                });
            });
            
            // Limiter le nombre de langues sélectionnées
            languageCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedLanguages = document.querySelectorAll('input[name="languages[]"]:checked');
                    if (checkedLanguages.length > <?php echo $choiceLanguageCount; ?>) {
                        this.checked = false;
                        alert('Vous ne pouvez sélectionner que <?php echo $choiceLanguageCount; ?> langue(s).');
                    }
                });
            });
        });
    </script>
</body>
</html>