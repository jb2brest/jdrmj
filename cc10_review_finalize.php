<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Revue et Finalisation";
$current_page = "character_creation";

$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) { $character_type = 'player'; }

// Charger le PTCharacter
$ptCharacter = $pt_id ? PTCharacter::findById($pt_id) : null;
$pdo = getPDO();

// Résolution nom équipements (même logique que étape 9)
function resolveEquipmentNameForReview(PDO $pdo, string $type, $typeId) {
    $map = [
        'armor' => 'armor',
        'bouclier' => 'shields',
        'instrument' => 'Object',
        'nourriture' => 'Object',
        'outils' => 'Object',
        'sac' => 'Object',
        'weapon' => 'weapons',
        'weapons' => 'weapons'
    ];
    $type = strtolower($type);
    $table = $map[$type] ?? null;
    if (!$table) return null;
    $column = ($table === 'Object') ? 'nom' : 'name';
    try {
        $stmt = $pdo->prepare("SELECT {$column} AS name FROM {$table} WHERE id = ?");
        $stmt->execute([(int)$typeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['name'] : null;
    } catch (PDOException $e) { return null; }
}

// Charger noms classe / race / historique
$classeName = $ptCharacter && $ptCharacter->class_id ? (Classe::findById($ptCharacter->class_id)?->name ?? '') : '';
$raceName = $ptCharacter && $ptCharacter->race_id ? (Race::findById($ptCharacter->race_id)?->name ?? '') : '';
$backgroundName = '';
if ($ptCharacter && $ptCharacter->background_id) {
    $bg = Background::findById($ptCharacter->background_id);
    $backgroundName = $bg ? $bg->name : '';
}

// Charger compétences / langues
$selectedSkills = [];
if ($ptCharacter && !empty($ptCharacter->selected_skills)) {
    $tmp = json_decode($ptCharacter->selected_skills, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $selectedSkills = $tmp; }
}
$selectedLanguages = [];
if ($ptCharacter && !empty($ptCharacter->selected_languages)) {
    $tmp = json_decode($ptCharacter->selected_languages, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $selectedLanguages = $tmp; }
}

// Charger choix d'équipement enregistrés
$equipmentChoices = [];
if ($ptCharacter) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM PT_equipment_choices WHERE pt_character_id = ? ORDER BY choice_type, choice_index");
        $stmt->execute([$pt_id]);
        $equipmentChoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $equipmentChoices = []; }
}

// Construire une liste d'items lisibles agrégée (éviter doublons)
$equipmentAgg = [];
foreach ($equipmentChoices as $ch) {
    $choixId = (int)$ch['selected_option'];
    if ($choixId <= 0) continue;
    try {
        $stmt = $pdo->prepare("SELECT * FROM starting_equipment_options WHERE starting_equipment_choix_id = ? ORDER BY id");
        $stmt->execute([$choixId]);
        $opts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($opts as $o) {
            $type = strtolower($o['type'] ?? '');
            $qty = (int)($o['nb'] ?? 1);
            $label = $o['label'] ?? null;

            // Gestion des armes filtrées
            $weaponId = null;
            $filterVal = $o['filter'] ?? $o['type_filter'] ?? null;
            if (!empty($filterVal) && ($type === 'weapons' || $type === 'weapon')) {
                if (!empty($ch['selected_weapons'])) {
                    $w = json_decode($ch['selected_weapons'], true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($w['weapon_id'])) { $weaponId = (int)$w['weapon_id']; }
                }
            }

            // Résoudre le nom
            $name = $label;
            if ($weaponId) {
                $weaponName = resolveEquipmentNameForReview($pdo, 'weapons', $weaponId);
                $name = $weaponName ?: 'Arme (' . $filterVal . ')';
            } elseif (!$name && !empty($o['type_id'])) {
                $name = resolveEquipmentNameForReview($pdo, $type, (int)$o['type_id']);
            }
            if (!$name && $type) { $name = strtoupper($type); }
            if (!$name) { continue; }

            // Clé d'agrégation robuste
            if ($weaponId) {
                $key = 'weapon_id#' . $weaponId;
            } elseif (!empty($o['type_id'])) {
                $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
            } elseif (!empty($label)) {
                $key = 'label#' . mb_strtolower(trim($label));
            } else {
                $key = 'type#' . mb_strtolower(trim($type));
            }

            if (!isset($equipmentAgg[$key])) {
                $equipmentAgg[$key] = ['name' => $name, 'qty' => 0];
            }
            // Si c'est un élément obligatoire (choice_index == 0), ne pas multiplier la quantité
            if ((int)($ch['choice_index'] ?? 0) === 0) {
                // Conserver la quantité maximale rencontrée pour cet item
                $equipmentAgg[$key]['qty'] = max($equipmentAgg[$key]['qty'], max(1, $qty));
            } else {
                // Pour les autres choix, on additionne
                $equipmentAgg[$key]['qty'] += max(1, $qty);
            }
        }
    } catch (PDOException $e) { continue; }
}
$equipmentItems = array_map(function($e){ return $e['name'] . ' x' . (int)$e['qty']; }, array_values($equipmentAgg));

// Traitement formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'go_back') {
        header('Location: cc09_starting_equipment.php?pt_id=' . $pt_id . '&type=' . $character_type);
        exit();
    } elseif ($action === 'confirm_create') {
        // À implémenter: création finale à partir des tables PT_
        $message = displayMessage("Validation reçue. La création finale sera implémentée ensuite.", 'success');
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

    <div class="container mt-4">
        <!-- Zone de titre alignée sur view_character -->
        <div class="zone-de-titre">
            <div class="zone-titre-container">
                <h1 class="titre-zone">
                    <i class="fas fa-user-ninja me-2"></i><?php echo htmlspecialchars($ptCharacter->name ?? 'Sans nom'); ?>
                </h1>
                <div>
                    <a href="cc09_starting_equipment.php?pt_id=<?php echo (int)$pt_id; ?>&type=<?php echo htmlspecialchars($character_type); ?>" class="btn-txt">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
        <?php if (!empty($message)) echo $message; ?>
        <div class="zone-d-entete mb-3">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="width:100px;">
                            <?php if (!empty($ptCharacter->profile_photo)): ?>
                                <img src="<?php echo htmlspecialchars($ptCharacter->profile_photo); ?>" alt="Photo" class="img-fluid rounded">
                            <?php else: ?>
                                <img src="images/default_profile.png" alt="Photo" class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($classeName ?: 'Classe'); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($raceName ?: 'Race'); ?></span>
                                <span class="badge bg-info text-dark">Niv. <?php echo (int)($ptCharacter->level ?? 1); ?></span>
                                <?php if (!empty($ptCharacter->alignment)): ?>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($ptCharacter->alignment); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-check me-2"></i>Revue et finalisation</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Compétences</strong>
                        <ul class="mb-0">
                            <?php foreach ($selectedSkills as $sk): ?>
                                <li><?php echo htmlspecialchars($sk); ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($selectedSkills)): ?><li class="text-muted">Aucune</li><?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Langues</strong>
                        <ul class="mb-0">
                            <?php foreach ($selectedLanguages as $lg): ?>
                                <li><?php echo htmlspecialchars(ucfirst($lg)); ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($selectedLanguages)): ?><li class="text-muted">Aucune</li><?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Équipement</strong>
                        <ul class="mb-0">
                            <?php foreach ($equipmentItems as $it): ?>
                                <li><?php echo htmlspecialchars($it); ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($equipmentItems)): ?><li class="text-muted">Aucun</li><?php endif; ?>
                        </ul>
                    </div>
                </div>

                <form method="POST" class="mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="cc09_starting_equipment.php?pt_id=<?php echo (int)$pt_id; ?>&type=<?php echo htmlspecialchars($character_type); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 9
                        </a>
                        <div>
                            <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-2">
                                Modifier
                            </button>
                            <button type="submit" name="action" value="confirm_create" class="btn btn-continue btn-lg">
                                <i class="fas fa-check me-2"></i>Créer le <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


