<?php
/**
 * Étape 9 - Choix de l'équipement de départ (classe + historique)
 * Tables: starting_equipment_choix, starting_equipment_options, weapons
 * Stockage temporaire: PT_items
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

// Charger les choix précédemment enregistrés depuis PT_items pour pré-remplir le formulaire
$savedChoices = [];
$savedWeaponSelects = [];
if ($pt_id) {
    try {
        // Récupérer les choix distincts avec leur source, no_choix et option_letter
        // Pour chaque no_choix, prendre le starting_equipment_choix_id le plus fréquent
        // Utiliser directement pt.src à la pièce du JOIN
        $stmt = $pdo->prepare("
            SELECT 
                pt.no_choix, 
                pt.starting_equipment_choix_id,
                pt.option_letter,
                pt.weapon_id,
                pt.src,
                MAX(pt.created_at) as latest_created_at,
                COUNT(*) as item_count
            FROM PT_items pt
            WHERE pt.pt_character_id = ? 
            AND pt.starting_equipment_choix_id IS NOT NULL
            AND pt.no_choix > 0
            GROUP BY pt.no_choix, pt.src, pt.starting_equipment_choix_id, pt.option_letter
            ORDER BY pt.no_choix ASC, item_count DESC, latest_created_at DESC
        ");
        $stmt->execute([$pt_id]);
        $ptItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pour chaque no_choix, garder seulement le premier (le plus fréquent ou le plus récent)
        $choicesByNo = [];
        foreach ($ptItems as $item) {
            $noChoix = (int)$item['no_choix'];
            $src = $item['src'] ?? 'class';
            $key = $src . '_' . $noChoix;
            
            // Garder seulement le premier choix pour chaque no_choix
            if (!isset($choicesByNo[$key])) {
                $choicesByNo[$key] = $item;
            }
        }
        
        foreach ($choicesByNo as $key => $item) {
            $choixId = (int)$item['starting_equipment_choix_id'];
            $noChoix = (int)$item['no_choix'];
            $optionLetter = $item['option_letter'] ?? null;
            
            // Déterminer le préfixe selon la source (class ou background)
            $prefix = ($item['src'] === 'background') ? 'bg_' : 'class_';
            $idxKey = $prefix . (string)$noChoix;
            
            // Stocker le choix sélectionné avec option_letter si disponible
            $savedChoices[$idxKey] = [
                'starting_equipment_choix_id' => $choixId,
                'option_letter' => $optionLetter
            ];
            
            // Si c'est une arme sélectionnée, récupérer le weapon_id le plus fréquent pour ce choix
            if (!empty($item['weapon_id'])) {
                $savedWeaponSelects[$idxKey] = (int)$item['weapon_id'];
            } else {
                // Chercher le weapon_id le plus fréquent pour ce no_choix
                try {
                    $stmtW = $pdo->prepare("
                        SELECT weapon_id, COUNT(*) as cnt
                        FROM PT_items
                        WHERE pt_character_id = ? 
                        AND no_choix = ?
                        AND weapon_id IS NOT NULL
                        GROUP BY weapon_id
                        ORDER BY cnt DESC
                        LIMIT 1
                    ");
                    $stmtW->execute([$pt_id, $noChoix]);
                    $weaponRow = $stmtW->fetch(PDO::FETCH_ASSOC);
                    if ($weaponRow) {
                        $savedWeaponSelects[$idxKey] = (int)$weaponRow['weapon_id'];
                    }
                } catch (PDOException $e) {
                    // Ignorer
                }
            }
        }
        
        // Debug: logger les choix sauvegardés
        error_log('Choix sauvegardés chargés depuis PT_items: ' . json_encode([
            'choices' => $savedChoices,
            'weapon_selects' => $savedWeaponSelects,
            'total_items' => count($ptItems),
            'choices_by_no' => array_map(function($item) {
                return [
                    'no_choix' => $item['no_choix'],
                    'src' => $item['src'],
                    'starting_equipment_choix_id' => $item['starting_equipment_choix_id']
                ];
            }, array_values($choicesByNo))
        ]));
    } catch (PDOException $e) {
        error_log('Erreur chargement choix sauvegardés: ' . $e->getMessage());
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
                // Effacer les anciens items pour ce PT
                $del = $pdo->prepare("DELETE FROM PT_items WHERE pt_character_id = ?");
                $del->execute([$pt_id]);

                // Fonction helper pour créer un item dans PT_items
                $createPTItem = function($pdo, $ptId, $option, $weaponId = null, $noChoix = null, $optionLetter = null, $startingEquipmentChoixId = null, $src = null) {
                    $type = strtolower($option['type'] ?? '');
                    $qty = (int)($option['nb'] ?? 1);
                    
                    // Résoudre le nom de l'équipement
                    $displayName = $option['label'] ?? null;
                    $weapon_id = null;
                    $armor_id = null;
                    $shield_id = null;
                    $poison_id = null;
                    $magical_item_id = null;
                    
                    if ($weaponId) {
                        $weaponInfo = resolveEquipment($pdo, 'weapon', $weaponId);
                        $displayName = $weaponInfo['name'] ?: $displayName;
                        $weapon_id = $weaponId;
                    } elseif (!empty($option['type_id'])) {
                        $info = resolveEquipment($pdo, $type, (int)$option['type_id']);
                        $displayName = $info['name'] ?: $displayName;
                        
                        // Déterminer quel ID utiliser selon le type
                        if ($type === 'weapon') {
                            $weapon_id = (int)$option['type_id'];
                        } elseif ($type === 'armor') {
                            $armor_id = (int)$option['type_id'];
                        } elseif ($type === 'shield' || $type === 'bouclier') {
                            $shield_id = (int)$option['type_id'];
                        } elseif ($type === 'poison') {
                            $poison_id = (int)$option['type_id'];
                        }
                    }
                    
                    if (!$displayName) {
                        $displayName = strtoupper($type);
                    }
                    
                    // Mapper object_type depuis type
                    $objectTypeMap = [
                        'weapon' => 'weapon',
                        'weapons' => 'weapon',
                        'armor' => 'armor',
                        'shield' => 'shield',
                        'bouclier' => 'shield',
                        'poison' => 'poison',
                        'magical_item' => 'magical_item'
                    ];
                    $objectType = $objectTypeMap[$type] ?? 'misc';
                    
                    // Insérer dans PT_items
                    $stmt = $pdo->prepare("
                        INSERT INTO PT_items (
                            pt_character_id, display_name, object_type, type_precis, description,
                            is_identified, is_visible, is_equipped, position_x, position_y, is_on_map,
                            weapon_id, armor_id, shield_id, poison_id, magical_item_id,
                            gold_coins, silver_coins, copper_coins, letter_content, is_sealed,
                            quantity, equipped_slot, item_source, notes, obtained_at, obtained_from,
                            no_choix, option_letter, starting_equipment_choix_id, src
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $ptId,
                        $displayName,
                        $objectType,
                        $type,
                        $option['description'] ?? null,
                        1, // is_identified (converti en entier)
                        0, // is_visible (converti en entier)
                        0, // is_equipped (converti en entier)
                        0, // position_x
                        0, // position_y
                        0, // is_on_map (converti en entier)
                        $weapon_id,
                        $armor_id,
                        $shield_id,
                        $poison_id,
                        $magical_item_id,
                        0, // gold_coins
                        0, // silver_coins
                        0, // copper_coins
                        null, // letter_content
                        0, // is_sealed (converti en entier)
                        $qty,
                        null, // equipped_slot
                        'Équipement de départ',
                        null, // notes
                        date('Y-m-d H:i:s'),
                        'Équipement de départ',
                        $noChoix, // no_choix
                        $optionLetter, // option_letter
                        $startingEquipmentChoixId, // starting_equipment_choix_id
                        $src // src
                    ]);
                };
                
                // Agrégation pour éviter les doublons - utiliser le nom résolu comme clé principale
                $itemsAgg = [];
                
                foreach ([['set'=>$classChoices,'prefix'=>'class_','src'=>'class'], ['set'=>$backgroundChoices,'prefix'=>'bg_','src'=>'background']] as $group) {
                    // Regrouper par no_choix
                    $byNo = [];
                    foreach ($group['set'] as $c) { $byNo[(int)($c['no_choix'] ?? 0)][] = $c; }
                    
                    foreach ($byNo as $no => $rows) {
                        if ($no === 0) {
                            // Obligatoires: traiter chaque ligne et résoudre les noms
                            foreach ($rows as $c) {
                                $options = $choiceIdToOptions[$c['id']] ?? [];
                                foreach ($options as $o) {
                                    $type = strtolower($o['type'] ?? '');
                                    $qty = (int)($o['nb'] ?? 1);
                                    
                                    // Résoudre le nom pour l'agrégation
                                    $resolvedName = null;
                                    $weaponId = null;
                                    if (!empty($o['type_id'])) {
                                        $info = resolveEquipment($pdo, $type, (int)$o['type_id']);
                                        $resolvedName = $info['name'];
                                        
                                        if ($type === 'weapon') {
                                            $weaponId = (int)$o['type_id'];
                                        }
                                    }
                                    $label = $o['label'] ?? null;
                                    $displayName = $resolvedName ?: ($label ?? strtoupper($type));
                                    
                                    // Clé d'agrégation basée sur le nom résolu normalisé
                                    if (!empty($resolvedName)) {
                                        $normalizedName = mb_strtolower(trim($resolvedName));
                                        $key = 'name#' . $normalizedName . '#type#' . $type;
                                    } elseif ($weaponId) {
                                        $key = 'weapon_id#' . $weaponId;
                                    } elseif (!empty($o['type_id'])) {
                                        $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
                                    } elseif (!empty($label)) {
                                        $key = 'label#' . mb_strtolower(trim($label));
                                    } else {
                                        $key = 'type#' . mb_strtolower(trim($type));
                                    }
                                    
                                    if (!isset($itemsAgg[$key])) {
                                        $itemsAgg[$key] = [
                                            'option' => $o, 
                                            'qty' => 0, 
                                            'weapon_id' => $weaponId, 
                                            'resolved_name' => $resolvedName,
                                            'no_choix' => (int)($c['no_choix'] ?? 0),
                                            'option_letter' => $c['option_letter'] ?? null,
                                            'starting_equipment_choix_id' => (int)$c['id'],
                                            'src' => $group['src']
                                        ];
                                    }
                                    // Pour les obligatoires, prendre le max (éviter de compter plusieurs fois le même équipement)
                                    $itemsAgg[$key]['qty'] = max($itemsAgg[$key]['qty'], $qty);
                                }
                            }
                        } else {
                            // Choix: traiter la ligne sélectionnée
                            $idxKey = $group['prefix'] . (string)$no;
                            $selectedRowId = (int)$postedChoices[$idxKey];
                            $selectedWeaponId = null;
                            
                            // Si la ligne sélectionnée contient un filtre armes
                            $optionsOfSelectedRow = $choiceIdToOptions[$selectedRowId] ?? [];
                            // Récupérer les informations du choix sélectionné (une seule fois)
                            $selectedChoix = null;
                            foreach ($rows as $row) {
                                if ((int)$row['id'] === $selectedRowId) {
                                    $selectedChoix = $row;
                                    break;
                                }
                            }
                            
                            foreach ($optionsOfSelectedRow as $o) {
                                $type = strtolower($o['type'] ?? '');
                                $qty = (int)($o['nb'] ?? 1);
                                $typeFilter = $o['filter'] ?? ($o['type_filter'] ?? null);
                                
                                // Réinitialiser selectedWeaponId pour chaque option
                                $optionWeaponId = null;
                                $resolvedName = null;
                                
                                if (!empty($typeFilter) && isWeaponType($type)) {
                                    $weaponSel = $postedWeaponSelects[$idxKey] ?? '';
                                    if ($weaponSel !== '') {
                                        $optionWeaponId = (int)$weaponSel;
                                        // Résoudre le nom de l'arme sélectionnée
                                        $weaponInfo = resolveEquipment($pdo, 'weapon', $optionWeaponId);
                                        $resolvedName = $weaponInfo['name'];
                                    }
                                } elseif (!empty($o['type_id'])) {
                                    $info = resolveEquipment($pdo, $type, (int)$o['type_id']);
                                    $resolvedName = $info['name'];
                                } else {
                                    $resolvedName = null;
                                }
                                
                                $label = $o['label'] ?? null;
                                $displayName = $resolvedName ?: ($label ?? strtoupper($type));
                                
                                // Clé d'agrégation basée sur le nom résolu normalisé (inclure le type pour distinguer armes et boucliers)
                                if ($optionWeaponId) {
                                    $key = 'weapon_id#' . $optionWeaponId;
                                } elseif (!empty($resolvedName)) {
                                    $normalizedName = mb_strtolower(trim($resolvedName));
                                    $key = 'name#' . $normalizedName . '#type#' . $type;
                                } elseif (!empty($o['type_id'])) {
                                    $key = 'id#' . (int)$o['type_id'] . '#type#' . $type;
                                } elseif (!empty($label)) {
                                    $key = 'label#' . mb_strtolower(trim($label));
                                } else {
                                    $key = 'type#' . mb_strtolower(trim($type));
                                }
                                
                                if (!isset($itemsAgg[$key])) {
                                    $itemsAgg[$key] = [
                                        'option' => $o, 
                                        'qty' => 0, 
                                        'weapon_id' => $optionWeaponId, 
                                        'resolved_name' => $resolvedName ?? null,
                                        'no_choix' => $selectedChoix ? (int)($selectedChoix['no_choix'] ?? 0) : null,
                                        'option_letter' => $selectedChoix ? ($selectedChoix['option_letter'] ?? null) : null,
                                        'starting_equipment_choix_id' => $selectedRowId,
                                        'src' => $group['src']
                                    ];
                                }
                                $itemsAgg[$key]['qty'] += $qty;
                                if ($optionWeaponId) {
                                    $itemsAgg[$key]['weapon_id'] = $optionWeaponId;
                                }
                            }
                        }
                    }
                }
                
                // Créer les items dans PT_items en tenant compte des quantités agrégées
                foreach ($itemsAgg as $itemData) {
                    $option = $itemData['option'];
                    $quantity = $itemData['qty'];
                    $weaponId = $itemData['weapon_id'];
                    $noChoix = $itemData['no_choix'] ?? null;
                    $optionLetter = $itemData['option_letter'] ?? null;
                    $startingEquipmentChoixId = $itemData['starting_equipment_choix_id'] ?? null;
                    $src = $itemData['src'] ?? null;
                    
                    // Créer un seul item avec la quantité appropriée
                    $option['nb'] = $quantity;
                    $createPTItem($pdo, $pt_id, $option, $weaponId, $noChoix, $optionLetter, $startingEquipmentChoixId, $src);
                }

                // Avancer l'étape
                if ((int)$ptCharacter->step < 10) { $ptCharacter->step = 10; $ptCharacter->update(); }

                header('Location: cc10_review_finalize.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } catch (PDOException $e) {
                error_log('Erreur sauvegarde PT_items: ' . $e->getMessage());
                $message = displayMessage("Erreur lors de l'enregistrement de l'équipement.", 'error');
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
    <?php include_once 'includes/navbar.php'; ?>

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
                        $renderChoiceSet = function($title, $set, $prefix) use ($choiceIdToOptions, $pdo, $pt_id, $savedChoices, $savedWeaponSelects) {
                            if (empty($set)) return;
                            echo '<h4 class="mb-3">' . htmlspecialchars($title) . '</h4>';
                            // Regrouper par no_choix
                            $byNo = [];
                            foreach ($set as $c) { $byNo[(int)($c['no_choix'] ?? 0)][] = $c; }

                            // Déterminer la source à partir du préfixe
                            $src = ($prefix === 'bg_') ? 'background' : 'class';

                            // Afficher les obligatoires (no_choix=0) depuis PT_items si disponibles, sinon depuis les choix
                            if (!empty($byNo[0])) {
                                echo '<div class="mb-4 p-3 border rounded bg-light">';
                                echo '<h5 class="mb-3">Éléments obligatoires</h5>';
                                
                                // Essayer de charger depuis PT_items d'abord (seulement les obligatoires : no_choix = 0 ou NULL, filtrés par src)
                                $ptItems = [];
                                try {
                                    $stmtPT = $pdo->prepare("
                                        SELECT display_name, quantity, object_type 
                                        FROM PT_items 
                                        WHERE pt_character_id = ? 
                                        AND (no_choix = 0 OR no_choix IS NULL)
                                        AND src = ?
                                        ORDER BY display_name
                                    ");
                                    $stmtPT->execute([$pt_id, $src]);
                                    $ptItemsRaw = $stmtPT->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Agréger par nom
                                    $agg = [];
                                    foreach ($ptItemsRaw as $item) {
                                        $key = mb_strtolower(trim($item['display_name']));
                                        if (!isset($agg[$key])) {
                                            $agg[$key] = ['name' => $item['display_name'], 'qty' => 0];
                                        }
                                        $agg[$key]['qty'] += (int)$item['quantity'];
                                    }
                                    
                                    if (!empty($agg)) {
                                        echo '<ul class="mb-0">';
                                        foreach ($agg as $entry) {
                                            echo '<li>' . htmlspecialchars($entry['name']) . ' x' . (int)$entry['qty'] . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        // Fallback: afficher depuis les choix
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
                                                
                                                // Clé d'agrégation
                                                if (!empty($resolvedName)) {
                                                    $normalizedName = mb_strtolower(trim($resolvedName));
                                                    $key = 'name#' . $normalizedName . '#type#' . $type;
                                                } elseif (!empty($o['type_id'])) {
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
                                                echo '<li>' . htmlspecialchars($entry['name']) . ' x' . (int)$entry['qty'] . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                    }
                                } catch (PDOException $e) {
                                    // Si PT_items n'existe pas encore, afficher depuis les choix
                                    error_log('PT_items non disponible, utilisation des choix: ' . $e->getMessage());
                                }
                                echo '</div>';
                            }

                            // Afficher chaque groupe de choix (no_choix>0)
                            foreach ($byNo as $no => $rows) {
                                if ($no === 0) continue;
                                $idxKey = $prefix . (string)$no;
                                echo '<div class="mb-4 p-3 border rounded">';
                                echo '<h5 class="mb-3">Choix ' . (int)$no . '</h5>';
                                // Debug: log tous les IDs disponibles pour ce no_choix
                                $availableIds = array_map(function($r) { return (int)$r['id']; }, $rows);
                                error_log("Groupe choix: idxKey=$idxKey, no_choix=$no, IDs disponibles: " . implode(', ', $availableIds));
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
                                    // Vérifier si ce choix a été sélectionné : comparer starting_equipment_choix_id
                                    $isChecked = false;
                                    if (isset($savedChoices[$idxKey])) {
                                        $savedChoice = $savedChoices[$idxKey];
                                        if (is_array($savedChoice)) {
                                            // Comparer uniquement starting_equipment_choix_id (c'est l'identifiant unique)
                                            $savedId = (int)$savedChoice['starting_equipment_choix_id'];
                                            $isChecked = ($savedId === $rowId);
                                            // Debug pour tous les choix de ce groupe
                                            error_log("Comparaison choix: idxKey=$idxKey, savedId=$savedId, rowId=$rowId, isChecked=" . ($isChecked ? 'true' : 'false'));
                                        } else {
                                            // Ancien format (simple ID)
                                            $isChecked = ((int)$savedChoice === $rowId);
                                        }
                                    } else {
                                        error_log("Pas de choix sauvegardé pour idxKey=$idxKey, rowId=$rowId");
                                    }
                                    echo '<div class="form-check mb-2">';
                                    echo '<input class="form-check-input" type="radio" name="choice[' . htmlspecialchars($idxKey) . ']" id="' . htmlspecialchars($radioId) . '" value="' . $rowId . '"' . ($isChecked ? ' checked' : '') . ' required>';
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
                                        $savedWeaponId = $savedWeaponSelects[$idxKey] ?? null;
                                        echo '<div class="ms-4 mb-3">';
                                        echo '<label class="form-label">Sélectionner l\'arme (filtre: ' . htmlspecialchars($weaponFilter) . ')</label>';
                                        echo '<select class="form-select" name="weapon_select[' . htmlspecialchars($idxKey) . ']">';
                                        foreach ($weapons as $w) {
                                            $isSelected = $savedWeaponId && (int)$w['id'] === $savedWeaponId;
                                            echo '<option value="' . (int)$w['id'] . '"' . ($isSelected ? ' selected' : '') . '>' . htmlspecialchars($w['name']) . ' (' . htmlspecialchars($w['type']) . ')</option>';
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


