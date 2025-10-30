<?php
/**
 * Étape 5 - Spécialisation de classe (archétype)
 * Source de données: table class_archetypes
 * Flux PT_ (personnages temporaires) avec pt_id et type (player|npc)
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Spécialisation de classe";
$current_page = "character_creation";

// Paramètres requis
$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) {
    $character_type = 'player';
}

// Permissions PNJ
if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Récupérer le personnage temporaire
$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

// S'assurer qu'une classe est sélectionnée (étape 1 OK) et récupérer la classe
$classeManager = new Classe();
$selectedClass = $ptCharacter->class_id ? $classeManager->findById($ptCharacter->class_id) : null;
// Récupérer race et historique pour reprendre le bandeau info-card de C04
$raceManager = new Race();
$selectedRace = $ptCharacter->race_id ? $raceManager->findById($ptCharacter->race_id) : null;
$backgroundManager = new Background();
$selectedBackground = $ptCharacter->background_id ? $backgroundManager->findById($ptCharacter->background_id) : null;
if (!$selectedClass) {
    // Revenir à l'étape 1 si pas de classe
    header('Location: cc01_class_selection.php?type=' . $character_type);
    exit();
}

// Déterminer le libellé selon la classe
$optionTypes = [
    'Barbare' => 'Voie',
    'Barde' => 'Collège',
    'Clerc' => 'Domaine',
    'Druide' => 'Cercle',
    'Guerrier' => 'Archétype',
    'Magicien' => 'Tradition',
    'Moine' => 'Tradition',
    'Paladin' => 'Serment',
    'Rôdeur' => 'Archétype',
    'Roublard' => 'Archétype',
    'Ensorceleur' => 'Origine',
    'Occultiste' => 'Pacte'
];
$optionType = $optionTypes[$selectedClass->name] ?? 'spécialisation';

// Charger les archétypes pour la classe
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, class_id, name, description, level_1_feature, level_2_feature, level_3_feature, level_6_feature FROM class_archetypes WHERE class_id = ? ORDER BY name");
$stmt->execute([$ptCharacter->class_id]);
$classOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lire la sélection existante depuis PT_capabilities (type = class_archetype)
$selectedOptionId = null;
$stmtSel = $pdo->prepare("SELECT capability_description FROM PT_capabilities WHERE pt_character_id = ? AND capability_type = 'class_archetype' LIMIT 1");
$stmtSel->execute([$pt_id]);
$existingCap = $stmtSel->fetch(PDO::FETCH_ASSOC);
if ($existingCap && !empty($existingCap['capability_description'])) {
    $decoded = json_decode($existingCap['capability_description'], true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['id'])) {
        $selectedOptionId = (int)$decoded['id'];
    }
}

// Traitement du formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'select_option') {
        $option_id = (int)($_POST['option_id'] ?? 0);

        // Valider l'option
        $isValid = false;
        if (empty($classOptions)) {
            $isValid = true; // pas d'options pour cette classe
        } else {
            foreach ($classOptions as $opt) {
                if ((int)$opt['id'] === $option_id) { $isValid = true; break; }
            }
        }

        if ($isValid) {
            try {
                $pdo->beginTransaction();

                // Nettoyer l'ancienne sélection
                $del = $pdo->prepare("DELETE FROM PT_capabilities WHERE pt_character_id = ? AND capability_type = 'class_archetype'");
                $del->execute([$pt_id]);

                if (!empty($classOptions)) {
                    // Enregistrer la nouvelle sélection comme JSON
                    $chosen = array_values(array_filter($classOptions, function($o) use ($option_id){ return (int)$o['id'] === $option_id; }));
                    $chosen = $chosen ? $chosen[0] : null;
                    $payload = json_encode([
                        'id' => $option_id,
                        'name' => $chosen ? $chosen['name'] : null,
                    ], JSON_UNESCAPED_UNICODE);

                    $ins = $pdo->prepare("INSERT INTO PT_capabilities (pt_character_id, capability_name, capability_description, capability_type, level_acquired) VALUES (?, 'class_archetype', ?, 'class_archetype', 3)");
                    $ins->execute([$pt_id, $payload]);
                }

                // Avancer l'étape si besoin (à 6)
                if ((int)$ptCharacter->step < 6) {
                    $ptCharacter->step = 6;
                    $ptCharacter->update();
                }

                $pdo->commit();

                header('Location: cc06_starting_equipment.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Erreur sauvegarde archétype: ' . $e->getMessage());
                $message = displayMessage("Erreur lors de la sauvegarde du choix.", 'error');
            }
        } else {
            $message = displayMessage("Veuillez sélectionner une option.", 'error');
        }
    } elseif ($action === 'go_back') {
        header('Location: cc04_characteristics.php?pt_id=' . $pt_id . '&type=' . $character_type);
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
    <link href="css/style.css" rel="stylesheet">
    <style>
        .option-card { cursor: pointer; transition: all 0.3s ease; border: 2px solid transparent; }
        .option-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .option-card.selected { border-color: #0d6efd; background-color: #e7f3ff; }
        .step-progress-bar { height: 8px; width: 55.56%; }
        .summary-card { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; }
        .feature-list { font-size: 0.9em; }
        .feature-active { color: #28a745; }
        .feature-future { color: #6c757d; }
    </style>
    <?php /* petite barre de progression visuelle alignée sur cc0x */ ?>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 5 sur 9 - Choisissez votre <?php echo htmlspecialchars($optionType); ?></p>
                </div>
                <div class="col-md-4">
                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                        <div class="progress-bar bg-light step-progress-bar"></div>
                    </div>
                    <small>Étape 5/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)) echo $message; ?>

        <!-- Bandeau info-card identique à C04 -->
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

        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-star me-2"></i>Choisissez votre <?php echo htmlspecialchars($optionType); ?></h3>
                <small class="text-muted">Votre choix détermine des capacités spéciales, parfois actives à des niveaux supérieurs.</small>
            </div>
            <div class="card-body">
                <form method="POST" id="optionForm">
                    <input type="hidden" name="action" value="select_option">
                    <input type="hidden" name="option_id" id="selected_option_id" value="<?php echo (int)$selectedOptionId; ?>">

                    <?php if (!empty($classOptions)): ?>
                        <div class="row">
                            <?php foreach ($classOptions as $option): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card option-card h-100 <?php echo ((int)$selectedOptionId === (int)$option['id']) ? 'selected' : ''; ?>" data-option-id="<?php echo (int)$option['id']; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-star me-2"></i><?php echo htmlspecialchars($option['name']); ?></h5>
                                            <?php if (!empty($option['description'])): ?>
                                                <p class="card-text"><?php echo htmlspecialchars(mb_substr($option['description'], 0, 180)); ?><?php if (mb_strlen($option['description']) > 180): ?>...<?php endif; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($option['level_1_feature'])): ?>
                                                <div class="feature-list feature-active mt-2">
                                                    <strong><i class="fas fa-check-circle text-success me-1"></i>Niveau 1</strong><br>
                                                    <small><?php echo htmlspecialchars(mb_substr($option['level_1_feature'], 0, 120)); ?><?php if (mb_strlen($option['level_1_feature']) > 120): ?>...<?php endif; ?></small>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($option['level_2_feature'])): ?>
                                                <div class="feature-list feature-future mt-2">
                                                    <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 2</strong><br>
                                                    <small><?php echo htmlspecialchars(mb_substr($option['level_2_feature'], 0, 120)); ?><?php if (mb_strlen($option['level_2_feature']) > 120): ?>...<?php endif; ?></small>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($option['level_3_feature'])): ?>
                                                <div class="feature-list feature-future mt-2">
                                                    <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 3</strong><br>
                                                    <small><?php echo htmlspecialchars(mb_substr($option['level_3_feature'], 0, 120)); ?><?php if (mb_strlen($option['level_3_feature']) > 120): ?>...<?php endif; ?></small>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($option['level_6_feature'])): ?>
                                                <div class="feature-list feature-future mt-2">
                                                    <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 6</strong><br>
                                                    <small><?php echo htmlspecialchars(mb_substr($option['level_6_feature'], 0, 120)); ?><?php if (mb_strlen($option['level_6_feature']) > 120): ?>...<?php endif; ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>Cette classe n'a pas d'options spéciales à choisir. Vous pouvez continuer.</div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="cc04_characteristics.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" class="btn btn-outline-secondary me-3"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn" <?php echo (!empty($classOptions) && !$selectedOptionId) ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 6
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.option-card');
        const input = document.getElementById('selected_option_id');
        const btn = document.getElementById('continueBtn');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                cards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                input.value = this.dataset.optionId;
                if (btn) btn.disabled = false;
            });
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


