<?php
/**
 * Étape 9 - Choix de l'équipement de départ (classe + historique)
 * Tables: starting_equipment_choix, starting_equipment_options, weapons
 * Stockage temporaire: PT_equipment_choices
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Équipement de départ";
$current_page = "character_creation";

$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) { $character_type = 'player'; }
if ($character_type === 'npc' && !User::isDMOrAdmin()) { header('Location: index.php?error=access_denied'); exit(); }

$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) { header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php')); exit(); }

$pdo = getPDO();

// Helper: identifier les types d'armes
function isWeaponType($type)
{
    $t = strtolower((string)$type);
    return $t === 'weapons' || $t === 'weapon';
}

// Résolution de nom d'item par type/type_id et exposition de la requête exécutée
function resolveEquipment(PDO $pdo, string $type, $typeId)
{
    if (empty($type) || empty($typeId)) return ['name' => null, 'query' => null];
    $type = strtolower($type);
    $map = [
        'armor' => 'armor',
        'bouclier' => 'shields',
        'instrument' => 'Object',
        'nourriture' => 'Object',
        'outils' => 'Object',
        'sac' => 'Object',
        'weapon' => 'weapons'
    ];
    $table = $map[$type] ?? null;
    if ($table === null) {
        if (preg_match('/^[a-z_]+$/', $type)) {
            $table = $type;
        } else {
            return ['name' => null, 'query' => null];
        }
    }

    $column = ($table === 'Object') ? 'nom' : 'name';
    $sql = "SELECT {$column} AS name FROM {$table} WHERE id = ?";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$typeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'name' => $row ? $row['name'] : null,
            'query' => $sql . ' [id=' . (int)$typeId . ']'
        ];
    } catch (PDOException $e) {
        error_log('Erreur résolution nom équipement: ' . $e->getMessage());
        return ['name' => null, 'query' => $sql . ' [id=' . (int)$typeId . ']'];
    }
}

// Charger entêtes
$classeManager = new Classe();
$selectedClass = $ptCharacter->class_id ? $classeManager->findById($ptCharacter->class_id) : null;
$raceManager = new Race();
$selectedRace = $ptCharacter->race_id ? $raceManager->findById($ptCharacter->race_id) : null;
$backgroundManager = new Background();
$selectedBackground = $ptCharacter->background_id ? $backgroundManager->findById($ptCharacter->background_id) : null;

// Récupérer les choix d'équipement de la classe et de l'historique
// Charger séparément pour classe et historique
$classChoices = [];
$backgroundChoices = [];
try {
    if ($ptCharacter->class_id) {
        $stmt = $pdo->prepare("SELECT *, 'class' AS src FROM starting_equipment_choix WHERE src = 'class' AND src_id = ? ORDER BY no_choix ASC, id ASC");
        $stmt->execute([$ptCharacter->class_id]);
        $classChoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($ptCharacter->background_id) {
        $stmt = $pdo->prepare("SELECT *, 'background' AS src FROM starting_equipment_choix WHERE src = 'background' AND src_id = ? ORDER BY no_choix ASC, id ASC");
        $stmt->execute([$ptCharacter->background_id]);
        $backgroundChoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Erreur lecture starting_equipment_choix: ' . $e->getMessage());
    $classChoices = [];
    $backgroundChoices = [];
}

// Charger options pour chaque choix
$choiceIdToOptions = [];
$allChoices = array_merge($classChoices, $backgroundChoices);
if (!empty($allChoices)) {
    $ids = array_column($allChoices, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT * FROM starting_equipment_options WHERE starting_equipment_choix_id IN ($in) ORDER BY id ASC");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $choiceIdToOptions[$row['starting_equipment_choix_id']][] = $row;
        }
    } catch (PDOException $e) {
        error_log('Erreur lecture starting_equipment_options: ' . $e->getMessage());
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_equipment') {
        // Parcourir les choix postés
        $postedChoices = $_POST['choice'] ?? [];
        $postedWeaponSelects = $_POST['weapon_select'] ?? [];

        // Validation: s'assurer que chaque no_choix > 0 a une valeur
        $missing = [];
        foreach ([['set'=>$classChoices,'prefix'=>'class_'], ['set'=>$backgroundChoices,'prefix'=>'bg_']] as $group) {
            $byNo = [];
            foreach ($group['set'] as $c) { $byNo[(int)($c['no_choix'] ?? 0)][] = $c; }
            foreach ($byNo as $no => $rows) {
                if ($no === 0) { continue; }
                $idxKey = $group['prefix'] . (string)$no;
                if (!isset($postedChoices[$idxKey]) || $postedChoices[$idxKey] === '') {
                    $missing[] = $idxKey;
                }
            }
        }
        if (!empty($missing)) {
            $message = displayMessage("Veuillez compléter tous les choix d'équipement.", 'error');
        }

        if (empty($message)) {
            try {
                // Effacer les anciens choix pour ce PT
                $del = $pdo->prepare("DELETE FROM PT_equipment_choices WHERE pt_character_id = ?");
                $del->execute([$pt_id]);

                // Insérer nouveaux choix
                $ins = $pdo->prepare("INSERT INTO PT_equipment_choices (pt_character_id, choice_type, choice_index, selected_option, selected_weapons) VALUES (?, ?, ?, ?, ?)");

                foreach ([['set'=>$classChoices,'prefix'=>'class_'], ['set'=>$backgroundChoices,'prefix'=>'bg_']] as $group) {
                    // Regrouper par no_choix
                    $byNo = [];
                    foreach ($group['set'] as $c) { $byNo[(int)($c['no_choix'] ?? 0)][] = $c; }
                    foreach ($byNo as $no => $rows) {
                        if ($no === 0) {
                            // Obligatoires: enregistrer chaque ligne comme fixe
                            foreach ($rows as $c) {
                                $ins->execute([
                                    $pt_id,
                                    $c['src'] ?? 'class',
                                    0,
                                    (string)((int)$c['id']),
                                    null
                                ]);
                            }
                            continue;
                        }
                        $idxKey = $group['prefix'] . (string)$no;
                        $selectedRowId = (int)$postedChoices[$idxKey];
                        $selectedWeapons = null;
                        // Si la ligne sélectionnée contient un filtre armes
                        $optionsOfSelectedRow = $choiceIdToOptions[$selectedRowId] ?? [];
                        foreach ($optionsOfSelectedRow as $o) {
                            $typeFilter = $o['filter'] ?? ($o['type_filter'] ?? null);
                            if (!empty($typeFilter) && isWeaponType($o['type'] ?? '')) {
                                $weaponSel = $postedWeaponSelects[$idxKey] ?? '';
                                if ($weaponSel !== '') {
                                    $selectedWeapons = json_encode([ 'weapon_id' => (int)$weaponSel ], JSON_UNESCAPED_UNICODE);
                                }
                                break;
                            }
                        }
                        $ins->execute([
                            $pt_id,
                            $rows[0]['src'] ?? 'class',
                            (int)$no,
                            (string)$selectedRowId,
                            $selectedWeapons
                        ]);
                    }
                }

                // Avancer l'étape
                if ((int)$ptCharacter->step < 10) { $ptCharacter->step = 10; $ptCharacter->update(); }

                header('Location: cc10_review_finalize.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } catch (PDOException $e) {
                error_log('Erreur sauvegarde PT_equipment_choices: ' . $e->getMessage());
                $message = displayMessage("Erreur lors de l'enregistrement des choix d'équipement.", 'error');
            }
        }
    } elseif ($action === 'go_back') {
        header('Location: cc08_identity_story.php?pt_id=' . $pt_id . '&type=' . $character_type);
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
                    <p class="mb-0">Étape 9 sur 9 - Choix de l'équipement de départ</p>
                </div>
                <div class="col-md-4">
                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                        <div class="progress-bar bg-light" style="width: 100%"></div>
                    </div>
                    <small>Étape 9/9</small>
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
                <h3><i class="fas fa-box-open me-2"></i>Équipement de départ</h3>
                <small class="text-muted">Choisissez une option pour chaque ligne. Certaines options d'armes nécessitent une sélection détaillée.</small>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_equipment">

                    <?php 
                        $renderChoiceSet = function($title, $set, $prefix) use ($choiceIdToOptions, $pdo) {
                            if (empty($set)) return;
                            echo '<h4 class="mb-3">' . htmlspecialchars($title) . '</h4>';
                            // Regrouper par no_choix
                            $byNo = [];
                            foreach ($set as $c) { $byNo[(int)($c['no_choix'] ?? 0)][] = $c; }

                            // Afficher les obligatoires (no_choix=0) agrégés (éviter doublons)
                            if (!empty($byNo[0])) {
                                echo '<div class="mb-4 p-3 border rounded bg-light">';
                                echo '<h5 class="mb-3">Éléments obligatoires</h5>';
                                $agg = [];
                                foreach ($byNo[0] as $c) {
                                    $options = $choiceIdToOptions[$c['id']] ?? [];
                                    foreach ($options as $o) {
                                        $type = strtolower($o['type'] ?? '');
                                        $qty = (int)($o['nb'] ?? 1);
                                        $resolvedName = null;
                                        if (!empty($o['type_id'])) {
                                            $info = resolveEquipment($pdo, $type, (int)$o['type_id']);
                                            $resolvedName = $info['name'];
                                        }
                                        $label = $o['label'] ?? null;
                                        $display = $resolvedName ?: ($label ?? (strtoupper($type)));
                                        // Clé d'agrégation robuste: privilégier type_id quand présent
                                        if (!empty($o['type_id'])) {
                                            $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
                                        } elseif (!empty($label)) {
                                            $key = 'label#' . mb_strtolower(trim($label));
                                        } else {
                                            $key = 'type#' . mb_strtolower(trim($type));
                                        }
                                        if (!isset($agg[$key])) {
                                            $agg[$key] = ['name' => $display, 'qty' => 0];
                                        }
                                        $agg[$key]['qty'] += max(1, $qty);
                                    }
                                }
                                if (!empty($agg)) {
                                    echo '<ul class="mb-0">';
                                    foreach ($agg as $entry) {
                                        $pretty = $entry['name'];
                                        echo '<li>' . htmlspecialchars($pretty) . ' x' . (int)$entry['qty'] . '</li>';
                                    }
                                    echo '</ul>';
                                }
                                echo '</div>';
                            }

                            // Afficher chaque groupe de choix (no_choix>0)
                            foreach ($byNo as $no => $rows) {
                                if ($no === 0) continue;
                                $idxKey = $prefix . (string)$no;
                                echo '<div class="mb-4 p-3 border rounded">';
                                echo '<h5 class="mb-3">Choix ' . (int)$no . '</h5>';
                                foreach ($rows as $c) {
                                    $rowId = (int)$c['id'];
                                    $options = $choiceIdToOptions[$rowId] ?? [];
                                    // Construire un label cumulant les items de cette ligne
                                    $items = [];
                                    $needsWeaponSelect = false;
                                    $weaponFilter = '';
                                    foreach ($options as $o) {
                                        $type = strtolower($o['type'] ?? '');
                                        $qty = (int)($o['nb'] ?? 1);
                                        $typeFilter = $o['filter'] ?? ($o['type_filter'] ?? null);
                                        if (!empty($typeFilter) && isWeaponType($type)) {
                                            $needsWeaponSelect = true;
                                            $weaponFilter = $typeFilter;
                                            $items[] = 'Arme (' . htmlspecialchars($typeFilter) . ') x' . max(1,$qty);
                                        } else {
                                        $resolvedName = null;
                                            if (!empty($o['type_id'])) {
                                                $info = resolveEquipment($pdo, $type, (int)$o['type_id']);
                                            $resolvedName = $info['name'];
                                            }
                                            $display = $resolvedName ?: ($o['label'] ?? strtoupper($type));
                                        $items[] = $display . ' x' . max(1,$qty);
                                        }
                                    }
                                    $radioId = 'choice_' . $idxKey . '_' . $rowId;
                                    echo '<div class="form-check mb-2">';
                                    echo '<input class="form-check-input" type="radio" name="choice[' . htmlspecialchars($idxKey) . ']" id="' . htmlspecialchars($radioId) . '" value="' . $rowId . '" required>';
                                    echo '<label class="form-check-label" for="' . htmlspecialchars($radioId) . '">';
                                    echo htmlspecialchars(implode(' + ', $items));
                                    echo '</label>';
                                    echo '</div>';

                                    if ($needsWeaponSelect) {
                                        $weapons = [];
                                        try {
                                            $stmtW = $pdo->prepare("SELECT id, name, type FROM weapons WHERE type LIKE ? ORDER BY name");
                                            $stmtW->execute([$weaponFilter]);
                                            $weapons = $stmtW->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (PDOException $e) { error_log('Erreur lecture weapons: ' . $e->getMessage()); }
                                        echo '<div class="ms-4 mb-3">';
                                        echo '<label class="form-label">Sélectionner l\'arme (filtre: ' . htmlspecialchars($weaponFilter) . ')</label>';
                                        echo '<select class="form-select" name="weapon_select[' . htmlspecialchars($idxKey) . ']">';
                                        foreach ($weapons as $w) {
                                            echo '<option value="' . (int)$w['id'] . '">' . htmlspecialchars($w['name']) . ' (' . htmlspecialchars($w['type']) . ')</option>';
                                        }
                                        echo '</select>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';
                            }
                        };

                        // Rendre la section classe puis historique (addition des deux)
                        $renderChoiceSet('Équipement de la classe', $classChoices, 'class_');
                        $renderChoiceSet('Équipement de l\'historique', $backgroundChoices, 'bg_');
                    ?>

                    <div class="text-center mt-4">
                        <a href="cc08_identity_story.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg" <?php echo (empty($classChoices) && empty($backgroundChoices)) ? 'disabled' : ''; ?>>
                            <i class="fas fa-check me-2"></i>Enregistrer et finaliser
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


