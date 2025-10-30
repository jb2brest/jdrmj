<?php
/**
 * Étape 6 - Choix des compétences et des langues
 * Stockage: PT_characters.selected_skills / selected_languages (JSON)
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Compétences et Langues";
$current_page = "character_creation";

// Paramètres
$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) {
    $character_type = 'player';
}

if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Charger PTCharacter
$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

// Charger classe, race, historique pour le bandeau
$classeManager = new Classe();
$selectedClass = $ptCharacter->class_id ? $classeManager->findById($ptCharacter->class_id) : null;
$raceManager = new Race();
$selectedRace = $ptCharacter->race_id ? $raceManager->findById($ptCharacter->race_id) : null;
$backgroundManager = new Background();
$selectedBackground = $ptCharacter->background_id ? $backgroundManager->findById($ptCharacter->background_id) : null;

// Compétences: utiliser la table classes (skill_count, skill_choices)
$pdo = getPDO();
$classSkillCount = 0;
$skillChoices = [];
if ($ptCharacter->class_id) {
    $stmtCls = $pdo->prepare("SELECT skill_count, skill_choices FROM classes WHERE id = ?");
    $stmtCls->execute([$ptCharacter->class_id]);
    if ($row = $stmtCls->fetch(PDO::FETCH_ASSOC)) {
        $classSkillCount = (int)($row['skill_count'] ?? 0);
        if (!empty($row['skill_choices'])) {
            $skillChoices = array_values(array_filter(array_map('trim', explode(',', $row['skill_choices']))));
        }
    }
}

// Compétences fixes issues de l'historique/race (si applicable)
$fixedSkills = [];
// Liste spécifique des compétences offertes par l'historique (pour affichage/flag)
$backgroundSkills = [];
if ($selectedBackground && !empty($selectedBackground->skill_proficiencies)) {
    $bg = json_decode($selectedBackground->skill_proficiencies, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($bg)) {
        $backgroundSkills = $bg;
        $fixedSkills = array_values(array_unique(array_merge($fixedSkills, $bg)));
    }
}
// Historique: compétences offertes (JSON stocké dans backgrounds.skill_proficiencies)
if ($selectedBackground && !empty($selectedBackground->skill_proficiencies)) {
    $bg = json_decode($selectedBackground->skill_proficiencies, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($bg)) {
        $fixedSkills = array_values(array_unique(array_merge($fixedSkills, $bg)));
    }
}

// LANGUES: récupérer toutes les langues (sauf "Commun" déjà incluse par défaut)
$allLanguages = $pdo->query("SELECT name, type FROM languages WHERE name != 'Commun' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Langues fixes et au choix (via race + historique)
$fixedLanguages = [];
$choiceLanguageCount = 0;

// Race: parser le champ languages textuel pour extraire fixes et mentions "langue de votre choix"
if ($selectedRace && !empty($selectedRace->languages)) {
    $raceLangArray = array_map('trim', explode(',', $selectedRace->languages));
    foreach ($raceLangArray as $lang) {
        if (stripos($lang, 'choix') !== false) {
            if (stripos($lang, 'deux') !== false) { $choiceLanguageCount += 2; }
            elseif (stripos($lang, 'une') !== false) { $choiceLanguageCount += 1; }
        } else {
            $fixedLanguages[] = $lang;
        }
    }
}

// Historique: languages JSON pouvant contenir des "langues de votre choix"
if ($selectedBackground && !empty($selectedBackground->languages)) {
    $bgLangs = json_decode($selectedBackground->languages, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($bgLangs)) {
        foreach ($bgLangs as $lang) {
            if (stripos($lang, 'choix') !== false) {
                if (stripos($lang, 'deux') !== false) { $choiceLanguageCount += 2; }
                elseif (stripos($lang, 'une') !== false) { $choiceLanguageCount += 1; }
            } else {
                $fixedLanguages[] = $lang;
            }
        }
    }
}

// Nettoyer doublons fixes
$fixedLanguages = array_values(array_unique($fixedLanguages));

// Déterminer les langues disponibles (exclure celles déjà connues)
$availableLanguages = [];
$knownLower = array_map('mb_strtolower', $fixedLanguages);
foreach ($allLanguages as $language) {
    if (!in_array(mb_strtolower($language['name']), $knownLower, true)) {
        $availableLanguages[] = $language;
    }
}

// Quota de langues à choisir
$maxLanguageChoices = $choiceLanguageCount;

// Récupérer sélection actuelle
$selected_skills = [];
if (!empty($ptCharacter->selected_skills)) {
    $tmp = json_decode($ptCharacter->selected_skills, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $selected_skills = $tmp;
    }
}

$selected_languages = [];
if (!empty($ptCharacter->selected_languages)) {
    $tmp = json_decode($ptCharacter->selected_languages, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $selected_languages = $tmp;
    }
}

// Déterminer les quotas de choix
$maxSkillChoices = $classSkillCount;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_selections') {
        $skills = isset($_POST['skills']) && is_array($_POST['skills']) ? array_values(array_unique($_POST['skills'])) : [];
        $languages = isset($_POST['languages']) && is_array($_POST['languages']) ? array_values(array_unique($_POST['languages'])) : [];

        // Valider: skills doivent appartenir à skillChoices; languages parmi les disponibles
        $validSkills = array_values(array_intersect($skills, $skillChoices));
        $availableLanguageNames = array_map(function($l){ return $l['name']; }, $availableLanguages);
        $validLanguages = array_values(array_intersect($languages, $availableLanguageNames));

        // Vérifier que le nombre exact est respecté
        if (count($validSkills) !== (int)$maxSkillChoices) {
            $message = displayMessage("Vous devez choisir exactement {$maxSkillChoices} compétence(s) de classe.", 'error');
        }
        if (empty($message) && count($validLanguages) !== (int)$maxLanguageChoices) {
            $message = displayMessage("Vous devez choisir exactement {$maxLanguageChoices} langue(s) supplémentaire(s).", 'error');
        }

        if (empty($message)) {
            // Enrichir: inclure automatiquement les fixes (union)
            $finalSkills = array_values(array_unique(array_merge($fixedSkills, $validSkills)));
            $finalLanguages = array_values(array_unique(array_merge($fixedLanguages, $validLanguages)));

            // Sauvegarder dans PT_characters
            $ptCharacter->selected_skills = json_encode($finalSkills, JSON_UNESCAPED_UNICODE);
            $ptCharacter->selected_languages = json_encode($finalLanguages, JSON_UNESCAPED_UNICODE);
            if ((int)$ptCharacter->step < 7) { $ptCharacter->step = 7; }

            if ($ptCharacter->update()) {
                header('Location: cc07_alignment_profile.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } else {
                $message = displayMessage("Erreur lors de l'enregistrement des sélections.", 'error');
            }
        }
    } elseif ($action === 'go_back') {
        header('Location: cc05_class_specialization.php?pt_id=' . $pt_id . '&type=' . $character_type);
        exit();
    }
}

// Aide visuelle: éléments cochés
$checkedSkills = array_fill_keys($selected_skills, true);
$checkedLanguages = array_fill_keys($selected_languages, true);

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
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 6 sur 9 - Choisissez vos compétences et vos langues</p>
                </div>
                <div class="col-md-4">
                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                        <div class="progress-bar bg-light" style="width: 66.67%"></div>
                    </div>
                    <small>Étape 6/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)) echo $message; ?>

        <!-- Bandeau info-card (reprend C04) -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <?php if ($selectedClass): ?>
                        <h4><i class="fas fa-shield-alt me-2"></i>Classe : <?php echo htmlspecialchars($selectedClass->name); ?></h4>
                        <small>Dé de vie : d<?php echo $selectedClass->hit_dice; ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedRace): ?>
                        <h4><i class="fas fa-users me-2"></i>Race : <?php echo htmlspecialchars($selectedRace->name); ?></h4>
                        <small>Vitesse : <?php echo $selectedRace->speed; ?> pieds | Taille : <?php echo htmlspecialchars($selectedRace->size); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedBackground): ?>
                        <h4><i class="fas fa-scroll me-2"></i>Historique : <?php echo htmlspecialchars($selectedBackground->name); ?></h4>
                        <small>Compétences : <?php 
                            $bgSkills = '';
                            if (!empty($selectedBackground->skill_proficiencies)) {
                                $decoded = json_decode($selectedBackground->skill_proficiencies, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $bgSkills = implode(', ', $decoded);
                                } else {
                                    $bgSkills = $selectedBackground->skill_proficiencies;
                                }
                            }
                            echo htmlspecialchars($bgSkills);
                        ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class="fas fa-graduation-cap me-2"></i>Compétences</h3>
                    <p class="mb-2 text-muted">Choisissez jusqu'à <?php echo (int)$maxSkillChoices; ?> compétence(s)</p>
                    <form method="POST" id="skillsLanguagesForm">
                        <input type="hidden" name="action" value="save_selections">
                        <div class="row">
                            <?php foreach ($skillChoices as $sk): ?>
                                <?php 
                                    $isFromBackground = in_array($sk, $backgroundSkills, true);
                                    $inputId = 'skill_' . md5($sk);
                                ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input skill-checkbox" type="checkbox" name="skills[]" value="<?php echo htmlspecialchars($sk); ?>" id="<?php echo $inputId; ?>" <?php echo $isFromBackground ? 'checked disabled data-fixed="1"' : (isset($checkedSkills[$sk]) ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="<?php echo $inputId; ?>">
                                            <?php echo htmlspecialchars($sk); ?>
                                            <?php if ($isFromBackground): ?>
                                                <span class="ms-1 text-muted">(historique)</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                </div>

                <div class="info-card mt-4">
                    <h3><i class="fas fa-language me-2"></i>Langues</h3>
                    <p class="mb-2 text-muted">Langues connues</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($fixedLanguages)): foreach ($fixedLanguages as $lg): ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($lg); ?></span>
                        <?php endforeach; else: ?>
                            <span class="text-muted">Aucune langue fixe</span>
                        <?php endif; ?>
                    </div>
                    <p class="mb-2 text-muted">Choisissez jusqu'à <?php echo (int)$maxLanguageChoices; ?> langue(s)</p>
                    <div class="row">
                        <?php foreach ($availableLanguages as $language): $lg = $language['name']; ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input language-checkbox" type="checkbox" name="languages[]" value="<?php echo htmlspecialchars($lg); ?>" id="lang_<?php echo md5($lg); ?>" <?php echo isset($checkedLanguages[$lg]) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="lang_<?php echo md5($lg); ?>">
                                        <?php echo htmlspecialchars($lg); ?>
                                        <?php if (!empty($language['type']) && $language['type'] === 'exotique'): ?>
                                            <span class="badge bg-warning ms-2">Exotique</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn">
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 7
                        </button>
                        <a href="cc05_class_specialization.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" class="btn btn-outline-secondary ms-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Contrainte côté client sur le nombre de cases cochées
    document.addEventListener('DOMContentLoaded', function() {
        const maxSkills = <?php echo (int)$maxSkillChoices; ?>;
        const maxLangs = <?php echo (int)$maxLanguageChoices; ?>;
        const skillBoxes = document.querySelectorAll('.skill-checkbox');
        const langBoxes = document.querySelectorAll('.language-checkbox');
        const continueBtn = document.getElementById('continueBtn');

        function enforceLimit(boxes, limit) {
            const checked = Array.from(boxes).filter(b => b.checked && !b.hasAttribute('data-fixed'));
            if (checked.length > limit) {
                // décocher le dernier coché
                const last = checked.pop();
                last.checked = false;
            }
        }

        function updateContinueState() {
            const skillsSelected = Array.from(skillBoxes).filter(b => b.checked && !b.hasAttribute('data-fixed')).length;
            const langsSelected = Array.from(langBoxes).filter(b => b.checked).length;
            if (continueBtn) {
                continueBtn.disabled = !(skillsSelected === maxSkills && langsSelected === maxLangs);
            }
        }

        skillBoxes.forEach(b => b.addEventListener('change', () => { enforceLimit(skillBoxes, maxSkills); updateContinueState(); }));
        langBoxes.forEach(b => b.addEventListener('change', () => { enforceLimit(langBoxes, maxLangs); updateContinueState(); }));

        // init
        updateContinueState();
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


