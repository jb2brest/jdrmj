<?php
/**
 * Étape 8 - Nom, apparence, traits de caractère et histoire
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Identité et Histoire";
$current_page = "character_creation";

$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) {
    $character_type = 'player';
}

if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

$classeManager = new Classe();
$selectedClass = $ptCharacter->class_id ? $classeManager->findById($ptCharacter->class_id) : null;
$raceManager = new Race();
$selectedRace = $ptCharacter->race_id ? $raceManager->findById($ptCharacter->race_id) : null;
$backgroundManager = new Background();
$selectedBackground = $ptCharacter->background_id ? $backgroundManager->findById($ptCharacter->background_id) : null;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_identity_story') {
        $name = trim($_POST['name'] ?? '');
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $height = trim($_POST['height'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $eyes = trim($_POST['eyes'] ?? '');
        $skin = trim($_POST['skin'] ?? '');
        $hair = trim($_POST['hair'] ?? '');
        $personality_traits = trim($_POST['personality_traits'] ?? '');
        $ideals = trim($_POST['ideals'] ?? '');
        $bonds = trim($_POST['bonds'] ?? '');
        $flaws = trim($_POST['flaws'] ?? '');
        $backstory = trim($_POST['backstory'] ?? '');

        if ($name === '') {
            $message = displayMessage("Le nom est obligatoire.", 'error');
        }

        if (empty($message)) {
            $ptCharacter->name = $name;
            $ptCharacter->age = $age ?: null;
            $ptCharacter->height = $height ?: null;
            $ptCharacter->weight = $weight ?: null;
            $ptCharacter->eyes = $eyes ?: null;
            $ptCharacter->skin = $skin ?: null;
            $ptCharacter->hair = $hair ?: null;
            $ptCharacter->personality_traits = $personality_traits ?: null;
            $ptCharacter->ideals = $ideals ?: null;
            $ptCharacter->bonds = $bonds ?: null;
            $ptCharacter->flaws = $flaws ?: null;
            $ptCharacter->backstory = $backstory ?: null;
            if ((int)$ptCharacter->step < 9) { $ptCharacter->step = 9; }

            if ($ptCharacter->update()) {
                header('Location: cc09_starting_equipment.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } else {
                $message = displayMessage("Erreur lors de l'enregistrement.", 'error');
            }
        }
    } elseif ($action === 'go_back') {
        header('Location: cc07_alignment_profile.php?pt_id=' . $pt_id . '&type=' . $character_type);
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
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 8 sur 9 - Identité et histoire</p>
                </div>
                <div class="col-md-4">
                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                        <div class="progress-bar bg-light" style="width: 88.89%"></div>
                    </div>
                    <small>Étape 8/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)) echo $message; ?>

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
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-id-card me-2"></i>Identité et Histoire</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_identity_story">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($ptCharacter->name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="age" class="form-label">Âge</label>
                            <input type="number" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($ptCharacter->age ?? ''); ?>" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="height" class="form-label">Taille</label>
                            <input type="text" class="form-control" id="height" name="height" placeholder="ex: 1m80" value="<?php echo htmlspecialchars($ptCharacter->height ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="weight" class="form-label">Poids</label>
                            <input type="text" class="form-control" id="weight" name="weight" placeholder="ex: 75 kg" value="<?php echo htmlspecialchars($ptCharacter->weight ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="eyes" class="form-label">Yeux</label>
                            <input type="text" class="form-control" id="eyes" name="eyes" value="<?php echo htmlspecialchars($ptCharacter->eyes ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="skin" class="form-label">Peau</label>
                            <input type="text" class="form-control" id="skin" name="skin" value="<?php echo htmlspecialchars($ptCharacter->skin ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="hair" class="form-label">Cheveux</label>
                            <input type="text" class="form-control" id="hair" name="hair" value="<?php echo htmlspecialchars($ptCharacter->hair ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6 mb-3">
                            <label for="personality_traits" class="form-label">Traits de personnalité</label>
                            <textarea class="form-control" id="personality_traits" name="personality_traits" rows="3" placeholder="Ex: Courageux, prudent, sarcastique..."><?php echo htmlspecialchars($ptCharacter->personality_traits ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ideals" class="form-label">Idéaux</label>
                            <textarea class="form-control" id="ideals" name="ideals" rows="3" placeholder="Ex: Justice, liberté, tradition..."><?php echo htmlspecialchars($ptCharacter->ideals ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6 mb-3">
                            <label for="bonds" class="form-label">Liens</label>
                            <textarea class="form-control" id="bonds" name="bonds" rows="3" placeholder="Ex: Dévoué à sa famille, protège son village..."><?php echo htmlspecialchars($ptCharacter->bonds ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="flaws" class="form-label">Défauts</label>
                            <textarea class="form-control" id="flaws" name="flaws" rows="3" placeholder="Ex: Orgueilleux, impulsif, jaloux..."><?php echo htmlspecialchars($ptCharacter->flaws ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="backstory" class="form-label">Histoire du personnage</label>
                        <textarea class="form-control" id="backstory" name="backstory" rows="6" placeholder="Racontez son passé, ses motivations, ses rencontres clés..."><?php echo htmlspecialchars($ptCharacter->backstory ?? ''); ?></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <a href="cc07_alignment_profile.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 9
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


