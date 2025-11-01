<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'includes/starting_equipment_functions.php';

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
        try {
            if (!$ptCharacter) {
                $message = displayMessage("Brouillon introuvable.", 'error');
            } else if ($character_type === 'player') {
                // Création d'un PJ
                $skillsArr = $selectedSkills;
                $languagesArr = $selectedLanguages;
                $char = Character::create([
                    'user_id' => $_SESSION['user_id'],
                    'name' => $ptCharacter->name ?: 'Nouveau Personnage',
                    'race_id' => $ptCharacter->race_id,
                    'class_id' => $ptCharacter->class_id,
                    'background_id' => $ptCharacter->background_id,
                    'level' => $ptCharacter->level ?: 1,
                    'experience_points' => $ptCharacter->experience ?: 0,
                    'strength' => $ptCharacter->strength ?: 10,
                    'dexterity' => $ptCharacter->dexterity ?: 10,
                    'constitution' => $ptCharacter->constitution ?: 10,
                    'intelligence' => $ptCharacter->intelligence ?: 10,
                    'wisdom' => $ptCharacter->wisdom ?: 10,
                    'charisma' => $ptCharacter->charisma ?: 10,
                    'armor_class' => $ptCharacter->armor_class ?: 10,
                    'initiative' => 0,
                    'speed' => $ptCharacter->speed ?: 30,
                    'hit_points_max' => $ptCharacter->hit_points_max ?: 8,
                    'hit_points_current' => $ptCharacter->hit_points_current ?: ($ptCharacter->hit_points_max ?: 8),
                    'proficiency_bonus' => $ptCharacter->proficiency_bonus ?: 2,
                    'alignment' => $ptCharacter->alignment ?: 'Neutre',
                    'personality_traits' => $ptCharacter->personality_traits ?: null,
                    'ideals' => $ptCharacter->ideals ?: null,
                    'bonds' => $ptCharacter->bonds ?: null,
                    'flaws' => $ptCharacter->flaws ?: null,
                    'profile_photo' => $ptCharacter->profile_photo ?: null,
                    'selected_skills' => $skillsArr,
                    'selected_languages' => $languagesArr,
                ], $pdo);

                if ($char && isset($char->id)) {
                    // Récupérer les pièces d'or du background
                    $backgroundGold = 0;
                    if ($ptCharacter->background_id) {
                        try {
                            $stmtGold = $pdo->prepare("SELECT money_gold FROM backgrounds WHERE id = ?");
                            $stmtGold->execute([$ptCharacter->background_id]);
                            $resultGold = $stmtGold->fetch(PDO::FETCH_ASSOC);
                            if ($resultGold && isset($resultGold['money_gold'])) {
                                $backgroundGold = (int)$resultGold['money_gold'];
                            }
                        } catch (PDOException $e) {
                            error_log('Erreur récupération pièces d\'or background: ' . $e->getMessage());
                        }
                    }
                    // Ajouter les pièces d'or au personnage
                    if ($backgroundGold > 0) {
                        try {
                            $stmtUpdateGold = $pdo->prepare("UPDATE characters SET gold = gold + ? WHERE id = ?");
                            $stmtUpdateGold->execute([$backgroundGold, $char->id]);
                        } catch (PDOException $e) {
                            error_log('Erreur ajout pièces d\'or: ' . $e->getMessage());
                        }
                    }

                    // Persister l'équipement de départ depuis PT_equipment_choices
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM PT_equipment_choices WHERE pt_character_id = ? ORDER BY choice_type, choice_index");
                        $stmt->execute([$pt_id]);
                        $ptChoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $itemsAgg = [];
                        foreach ($ptChoices as $chc) {
                            $rowId = (int)$chc['selected_option'];
                            if ($rowId <= 0) continue;
                            $stmt2 = $pdo->prepare("SELECT * FROM starting_equipment_options WHERE starting_equipment_choix_id = ? ORDER BY id");
                            $stmt2->execute([$rowId]);
                            $opts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($opts as $o) {
                                $type = strtolower($o['type'] ?? '');
                                $qty = (int)($o['nb'] ?? 1);
                                $name = $o['label'] ?? null;
                                // Cas armes avec filtre: utiliser selected_weapons
                                $filterVal = $o['filter'] ?? ($o['type_filter'] ?? null);
                                $weaponId = null;
                                if (!empty($filterVal) && ($type === 'weapons' || $type === 'weapon')) {
                                    if (!empty($chc['selected_weapons'])) {
                                        $w = json_decode($chc['selected_weapons'], true);
                                        if (json_last_error() === JSON_ERROR_NONE && !empty($w['weapon_id'])) { $weaponId = (int)$w['weapon_id']; }
                                    }
                                    if ($weaponId) {
                                        $weaponName = resolveEquipmentNameForReview($pdo, 'weapons', $weaponId);
                                        $name = $weaponName ?: ('Arme (' . $filterVal . ')');
                                    } else {
                                        $name = 'Arme (' . $filterVal . ')';
                                    }
                                }
                                // Résoudre par type_id sinon
                                if (!$name && !empty($o['type_id'])) {
                                    $name = resolveEquipmentNameForReview($pdo, $type, (int)$o['type_id']);
                                }
                                if (!$name && $type) { $name = strtoupper($type); }
                                if (!$name) { continue; }

                                // Clé d'agrégation pour éviter les doublons
                                if ($weaponId) {
                                    $key = 'weapon_id#' . $weaponId;
                                } elseif (!empty($o['type_id'])) {
                                    $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
                                } elseif (!empty($o['label'])) {
                                    $key = 'label#' . mb_strtolower(trim($o['label']));
                                } else {
                                    $key = 'type#' . mb_strtolower(trim($type));
                                }

                                // Si obligatoire (choice_index == 0), prendre la quantité max, sinon additionner
                                if ((int)($chc['choice_index'] ?? 0) === 0) {
                                    if (!isset($itemsAgg[$key])) {
                                        $itemsAgg[$key] = ['name' => $name, 'quantity' => max(1, $qty)];
                                    } else {
                                        $itemsAgg[$key]['quantity'] = max($itemsAgg[$key]['quantity'], max(1, $qty));
                                    }
                                } else {
                                    if (!isset($itemsAgg[$key])) {
                                        $itemsAgg[$key] = ['name' => $name, 'quantity' => 0];
                                    }
                                    $itemsAgg[$key]['quantity'] += max(1, $qty);
                                }
                            }
                        }
                        $items = array_values($itemsAgg);
                        if (!empty($items)) {
                            addStartingEquipmentToCharacterNew($char->id, ['equipment' => ['items' => $items]]);
                        }
                    } catch (Exception $e) {
                        error_log('Erreur ajout équipement de départ: ' . $e->getMessage());
                    }
                    header('Location: view_character.php?id=' . $char->id);
                    exit();
                } else {
                    $message = displayMessage("Erreur lors de la création du personnage.", 'error');
                }
            } else {
                // Création d'un PNJ
                // Récupérer les pièces d'or du background
                $backgroundGoldNpc = 0;
                if ($ptCharacter->background_id) {
                    try {
                        $stmtGoldNpc = $pdo->prepare("SELECT money_gold FROM backgrounds WHERE id = ?");
                        $stmtGoldNpc->execute([$ptCharacter->background_id]);
                        $resultGoldNpc = $stmtGoldNpc->fetch(PDO::FETCH_ASSOC);
                        if ($resultGoldNpc && isset($resultGoldNpc['money_gold'])) {
                            $backgroundGoldNpc = (int)$resultGoldNpc['money_gold'];
                        }
                    } catch (PDOException $e) {
                        error_log('Erreur récupération pièces d\'or background PNJ: ' . $e->getMessage());
                    }
                }

                // Récupérer un world_id par défaut (premier monde de l'utilisateur ou monde par défaut)
                $world_id = null;
                try {
                    $stmtWorld = $pdo->prepare("SELECT id FROM worlds WHERE created_by = ? ORDER BY id ASC LIMIT 1");
                    $stmtWorld->execute([$_SESSION['user_id']]);
                    $worldResult = $stmtWorld->fetch(PDO::FETCH_ASSOC);
                    if ($worldResult && isset($worldResult['id'])) {
                        $world_id = (int)$worldResult['id'];
                    } else {
                        // Si aucun monde créé par l'utilisateur, utiliser le monde par défaut (Aeridon = 307) ou le premier monde disponible
                        $stmtDefaultWorld = $pdo->prepare("SELECT id FROM worlds ORDER BY id ASC LIMIT 1");
                        $stmtDefaultWorld->execute();
                        $defaultWorldResult = $stmtDefaultWorld->fetch(PDO::FETCH_ASSOC);
                        $world_id = $defaultWorldResult ? (int)$defaultWorldResult['id'] : 307;
                    }
                } catch (PDOException $e) {
                    error_log('Erreur récupération world_id: ' . $e->getMessage());
                    $world_id = 307; // Monde par défaut en cas d'erreur
                }

                $npc = new NPC([
                    'name' => $ptCharacter->name ?: 'Nouveau PNJ',
                    'class_id' => $ptCharacter->class_id,
                    'race_id' => $ptCharacter->race_id,
                    'background_id' => $ptCharacter->background_id,
                    'level' => $ptCharacter->level ?: 1,
                    'experience' => $ptCharacter->experience ?: 0,
                    'strength' => $ptCharacter->strength ?: 10,
                    'dexterity' => $ptCharacter->dexterity ?: 10,
                    'constitution' => $ptCharacter->constitution ?: 10,
                    'intelligence' => $ptCharacter->intelligence ?: 10,
                    'wisdom' => $ptCharacter->wisdom ?: 10,
                    'charisma' => $ptCharacter->charisma ?: 10,
                    'hit_points_max' => $ptCharacter->hit_points_max ?: 8,
                    'hit_points_current' => $ptCharacter->hit_points_current ?: ($ptCharacter->hit_points_max ?: 8),
                    'armor_class' => $ptCharacter->armor_class ?: 10,
                    'speed' => $ptCharacter->speed ?: 30,
                    'alignment' => $ptCharacter->alignment ?: 'Neutre',
                    'age' => $ptCharacter->age ?: null,
                    'height' => $ptCharacter->height ?: null,
                    'weight' => $ptCharacter->weight ?: null,
                    'eyes' => $ptCharacter->eyes ?: null,
                    'skin' => $ptCharacter->skin ?: null,
                    'hair' => $ptCharacter->hair ?: null,
                    'backstory' => $ptCharacter->backstory ?: null,
                    'personality_traits' => $ptCharacter->personality_traits ?: null,
                    'ideals' => $ptCharacter->ideals ?: null,
                    'bonds' => $ptCharacter->bonds ?: null,
                    'flaws' => $ptCharacter->flaws ?: null,
                    'gold' => $backgroundGoldNpc + ($ptCharacter->gold ?: 0),
                    'silver' => $ptCharacter->silver ?: 0,
                    'copper' => $ptCharacter->copper ?: 0,
                    'skills' => json_encode($selectedSkills),
                    'languages' => json_encode($selectedLanguages),
                    'profile_photo' => $ptCharacter->profile_photo ?: null,
                    'created_by' => $_SESSION['user_id'],
                    'world_id' => $world_id,
                    'location_id' => null,
                    'is_active' => 1
                ]);
                if ($npc->create()) {
                    // Persister l'équipement de départ depuis PT_equipment_choices (même logique que pour les PJ)
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM PT_equipment_choices WHERE pt_character_id = ? ORDER BY choice_type, choice_index");
                        $stmt->execute([$pt_id]);
                        $ptChoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $itemsAgg = [];
                        foreach ($ptChoices as $chc) {
                            $rowId = (int)$chc['selected_option'];
                            if ($rowId <= 0) continue;
                            $stmt2 = $pdo->prepare("SELECT * FROM starting_equipment_options WHERE starting_equipment_choix_id = ? ORDER BY id");
                            $stmt2->execute([$rowId]);
                            $opts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($opts as $o) {
                                $type = strtolower($o['type'] ?? '');
                                $qty = (int)($o['nb'] ?? 1);
                                $name = $o['label'] ?? null;
                                // Cas armes avec filtre: utiliser selected_weapons
                                $filterVal = $o['filter'] ?? ($o['type_filter'] ?? null);
                                $weaponId = null;
                                if (!empty($filterVal) && ($type === 'weapons' || $type === 'weapon')) {
                                    if (!empty($chc['selected_weapons'])) {
                                        $w = json_decode($chc['selected_weapons'], true);
                                        if (json_last_error() === JSON_ERROR_NONE && !empty($w['weapon_id'])) { $weaponId = (int)$w['weapon_id']; }
                                    }
                                    if ($weaponId) {
                                        $weaponName = resolveEquipmentNameForReview($pdo, 'weapons', $weaponId);
                                        $name = $weaponName ?: ('Arme (' . $filterVal . ')');
                                    } else {
                                        $name = 'Arme (' . $filterVal . ')';
                                    }
                                }
                                // Résoudre par type_id sinon
                                if (!$name && !empty($o['type_id'])) {
                                    $name = resolveEquipmentNameForReview($pdo, $type, (int)$o['type_id']);
                                }
                                if (!$name && $type) { $name = strtoupper($type); }
                                if (!$name) { continue; }

                                // Clé d'agrégation pour éviter les doublons
                                if ($weaponId) {
                                    $key = 'weapon_id#' . $weaponId;
                                } elseif (!empty($o['type_id'])) {
                                    $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
                                } elseif (!empty($o['label'])) {
                                    $key = 'label#' . mb_strtolower(trim($o['label']));
                                } else {
                                    $key = 'type#' . mb_strtolower(trim($type));
                                }

                                // Si obligatoire (choice_index == 0), prendre la quantité max, sinon additionner
                                if ((int)($chc['choice_index'] ?? 0) === 0) {
                                    if (!isset($itemsAgg[$key])) {
                                        $itemsAgg[$key] = ['name' => $name, 'quantity' => max(1, $qty)];
                                    } else {
                                        $itemsAgg[$key]['quantity'] = max($itemsAgg[$key]['quantity'], max(1, $qty));
                                    }
                                } else {
                                    if (!isset($itemsAgg[$key])) {
                                        $itemsAgg[$key] = ['name' => $name, 'quantity' => 0];
                                    }
                                    $itemsAgg[$key]['quantity'] += max(1, $qty);
                                }
                            }
                        }
                        $items = array_values($itemsAgg);
                        if (!empty($items)) {
                            // Ajouter l'équipement au NPC (même logique que pour les PJ mais avec owner_type = 'npc')
                            addStartingEquipmentToNpcNew($npc->id, ['equipment' => ['items' => $items]]);
                        }
                    } catch (Exception $e) {
                        error_log('Erreur ajout équipement de départ PNJ: ' . $e->getMessage());
                    }
                    
                    header('Location: view_npc.php?id=' . $npc->id);
                    exit();
                } else {
                    $message = displayMessage("Erreur lors de la création du PNJ.", 'error');
                }
            }
        } catch (Exception $e) {
            error_log('Erreur finalisation: ' . $e->getMessage());
            $message = displayMessage("Erreur lors de la finalisation.", 'error');
        }
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
    <?php include_once 'includes/navbar.php'; ?>

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


