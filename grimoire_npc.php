<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/NPC.php';

requireLogin();

$npc_id = (int)($_GET['id'] ?? 0);

if ($npc_id === 0) {
    header('Location: manage_npcs.php');
    exit;
}

// Récupérer les informations du NPC
$npc = NPC::findById($npc_id, $pdo);
if (!$npc) {
    header('Location: manage_npcs.php');
    exit;
}

$character = $npc->toArray();

// Vérifier si la classe peut lancer des sorts
$spellcastingClasses = [2, 3, 4, 5, 7, 9, 10, 11]; // Barde, Clerc, Druide, Ensorceleur, Magicien, Occultiste, Paladin, Rôdeur
$canCastSpells = in_array($character['class_id'], $spellcastingClasses);

if (!$canCastSpells) {
    header('Location: view_npc.php?id=' . $npc_id);
    exit;
}

// Calculer les modificateurs de caractéristiques
$wisdomModifier = floor(($character['wisdom'] - 10) / 2);
$intelligenceModifier = floor(($character['intelligence'] - 10) / 2);

// Récupérer les capacités de sorts de la classe
$spell_capabilities = NPC::getClassSpellCapabilities($character['class_id'], $character['level'], $wisdomModifier, $intelligenceModifier);

// Récupérer les sorts du NPC
$character_spells = NPC::getNpcSpells($npc_id);

// Récupérer les utilisations d'emplacements de sorts
$spell_slots_usage = NPC::getSpellSlotsUsage($npc_id);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'prepare_spell':
            $spell_id = (int)($_POST['spell_id'] ?? 0);
            if ($spell_id > 0) {
                // Vérifier si le sort n'est pas déjà préparé
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
                $checkStmt->execute([$npc_id, $spell_id]);
                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists['count'] == 0) {
                    // Vérifier le niveau du sort et les limites
                    $spellStmt = $pdo->prepare("SELECT level FROM spells WHERE id = ?");
                    $spellStmt->execute([$spell_id]);
                    $spell = $spellStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($spell) {
                        $spellLevel = $spell['level'];
                        
                        // Récupérer les informations du NPC
                        $npcStmt = $pdo->prepare("
                            SELECT n.*, c.name as class_name
                            FROM npcs n
                            LEFT JOIN classes c ON n.class_id = c.id
                            WHERE n.id = ?
                        ");
                        $npcStmt->execute([$npc_id]);
                        $npc = $npcStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($npc) {
                            // Calculer les capacités de sorts
                            $capabilities = NPC::getClassSpellCapabilities($npc['class_id'], $npc['level'], 0, 0);
                            $maxCantrips = $capabilities['cantrips_known'] ?? 0;
                            $maxPrepared = $capabilities['spells_known'] ?? 0;
                            
                            // Compter les sorts actuels
                            $currentCantrips = 0;
                            $currentPreparedSpells = 0;
                            $stmt = $pdo->prepare("
                                SELECT s.level, COUNT(*) as count
                                FROM npc_spells ns
                                JOIN spells s ON ns.spell_id = s.id
                                WHERE ns.npc_id = ?
                                GROUP BY s.level
                            ");
                            $stmt->execute([$npc_id]);
                            $existingSpells = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($existingSpells as $existingSpell) {
                                if ($existingSpell['level'] == 0) {
                                    $currentCantrips = $existingSpell['count'];
                                } else {
                                    $currentPreparedSpells += $existingSpell['count'];
                                }
                            }
                            
                            // Vérifier les limites selon le niveau du sort
                            $canAdd = false;
                            if ($spellLevel == 0 && $currentCantrips < $maxCantrips) {
                                $canAdd = true;
                            } elseif ($spellLevel > 0 && $currentPreparedSpells < $maxPrepared) {
                                $canAdd = true;
                            }
                            
                            if ($canAdd) {
                                $insertStmt = $pdo->prepare("INSERT INTO npc_spells (npc_id, spell_id, prepared) VALUES (?, ?, 1)");
                                $insertStmt->execute([$npc_id, $spell_id]);
                                $success_message = "Sort préparé avec succès";
                            } else {
                                if ($spellLevel == 0) {
                                    $error_message = "Limite de sorts mineurs atteinte ($maxCantrips maximum)";
                                } else {
                                    $error_message = "Limite de sorts préparés atteinte ($maxPrepared maximum)";
                                }
                            }
                        }
                    }
                } else {
                    $error_message = "Ce sort est déjà préparé";
                }
            }
            break;
            
        case 'unprepare_spell':
            $spell_id = (int)($_POST['spell_id'] ?? 0);
            if ($spell_id > 0) {
                $deleteStmt = $pdo->prepare("DELETE FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
                $deleteStmt->execute([$npc_id, $spell_id]);
                $success_message = "Sort retiré du grimoire";
            }
            break;
            
        case 'use_spell_slot':
            $spell_level = (int)($_POST['spell_level'] ?? 0);
            if ($spell_level > 0) {
                $result = NPC::useSpellSlot($npc_id, $spell_level);
                if ($result) {
                    $success_message = "Emplacement de sort de niveau $spell_level utilisé";
                } else {
                    $error_message = "Erreur lors de l'utilisation de l'emplacement";
                }
            }
            break;
            
        case 'restore_spell_slots':
            $result = NPC::restoreSpellSlots($npc_id);
            if ($result) {
                $success_message = "Tous les emplacements de sorts ont été restaurés";
            } else {
                $error_message = "Erreur lors de la restauration des emplacements";
            }
            break;
            
        case 'toggle_spell_prepared':
            $spell_id = (int)($_POST['spell_id'] ?? 0);
            if ($spell_id > 0) {
                try {
                    // Vérifier l'état actuel
                    $stmt = $pdo->prepare("SELECT prepared FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
                    $stmt->execute([$npc_id, $spell_id]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($current) {
                        $new_prepared = $current['prepared'] ? 0 : 1;
                        
                        // Si on veut préparer un sort, vérifier les limites
                        if ($new_prepared) {
                            // Récupérer les informations du NPC
                            $npcStmt = $pdo->prepare("
                                SELECT n.*, c.name as class_name
                                FROM npcs n
                                LEFT JOIN classes c ON n.class_id = c.id
                                WHERE n.id = ?
                            ");
                            $npcStmt->execute([$npc_id]);
                            $npc = $npcStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($npc) {
                                // Calculer les capacités de sorts
                                $capabilities = NPC::getClassSpellCapabilities($npc['class_id'], $npc['level'], 0, 0);
                                
                                // Compter les sorts préparés actuels
                                $preparedCount = 0;
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(*) as count 
                                    FROM npc_spells ns 
                                    JOIN spells s ON ns.spell_id = s.id 
                                    WHERE ns.npc_id = ? AND ns.prepared = 1 AND s.level > 0
                                ");
                                $stmt->execute([$npc_id]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $preparedCount = $result['count'];
                                
                                // Vérifier la limite
                                $maxPrepared = $capabilities['spells_known'] ?? 0;
                                if ($preparedCount >= $maxPrepared) {
                                    $error_message = "Limite de sorts préparés atteinte ($maxPrepared maximum)";
                                    break;
                                }
                            }
                        }
                        
                        $stmt = $pdo->prepare("UPDATE npc_spells SET prepared = ? WHERE npc_id = ? AND spell_id = ?");
                        $stmt->execute([$new_prepared, $npc_id, $spell_id]);
                        
                        $status = $new_prepared ? 'préparé' : 'non préparé';
                        $success_message = "Sort {$status}";
                    } else {
                        $error_message = "Sort non trouvé";
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de la modification du sort";
                }
            }
            break;
    }
    
    // Recharger les données après modification
    $character_spells = NPC::getNpcSpells($npc_id);
    $spell_slots_usage = NPC::getSpellSlotsUsage($npc_id);
}

// Récupérer tous les sorts disponibles par niveau
$allSpells = [];
for ($level = 0; $level <= 9; $level++) {
    $stmt = $pdo->prepare("
        SELECT * FROM spells 
        WHERE level = ? 
        ORDER BY name
    ");
    $stmt->execute([$level]);
    $allSpells[$level] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Grouper les sorts par niveau
$spells_by_level = [];
foreach ($character_spells as $spell) {
    $spells_by_level[$spell['level']][] = $spell;
}

// Compter les sorts préparés
$prepared_spells_count = 0;
$cantrips_count = 0;
$learned_spells_count = 0;
foreach ($character_spells as $spell) {
    if ($spell['level'] == 0) {
        $cantrips_count++;
    } else {
        $learned_spells_count++;
        if ($spell['prepared']) {
            $prepared_spells_count++;
        }
    }
}

// Calculer les limites de sorts préparés
$spell_capabilities = NPC::getClassSpellCapabilities($character['class_id'], $character['level'], 0, 0);
$max_prepared_spells = $spell_capabilities['spells_known'] ?? 0;
$can_prepare_more = $prepared_spells_count < $max_prepared_spells;

$page_title = "Grimoire - " . $character['name'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <style>
.grimoire-container {
    background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.grimoire-book {
    background: #F5F5DC;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    margin: 0 auto;
    width: 90%;
    max-width: 1200px;
    min-width: 800px;
    min-height: 80vh;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.grimoire-pages-container {
    display: flex;
    height: calc(100% - 200px);
    width: 100%;
}

.grimoire-page {
    padding: 15px;
    min-height: 80vh;
    position: relative;
    overflow-x: hidden;
    overflow-y: auto;
    width: 50%;
    min-width: 350px;
    box-sizing: border-box;
}

.grimoire-page-left {
    border-right: 3px solid #8B4513;
    border-top: 2px solid #8B4513;
    border-bottom: 2px solid #8B4513;
    border-left: 2px solid #8B4513;
    background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    border-radius: 0 8px 8px 0;
}

.grimoire-page-right {
    border-left: 3px solid #8B4513;
    border-top: 2px solid #8B4513;
    border-bottom: 2px solid #8B4513;
    border-right: 2px solid #8B4513;
    background: linear-gradient(270deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    border-radius: 8px 0 0 8px;
}

.spell-level-section {
    margin-bottom: 12px;
    padding: 6px;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    border: 1px solid rgba(139, 69, 19, 0.2);
}

.spell-level-title {
    color: #8B4513;
    font-weight: bold;
    font-size: 1em;
    margin-bottom: 10px;
    text-align: center;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
}

.spell-tabs-container {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.spell-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 10px;
    padding: 0;
    list-style: none;
    border-bottom: 2px solid #8B4513;
    padding-bottom: 10px;
}

.spell-capabilities-info {
    background: rgba(139, 69, 19, 0.1);
    border: 1px solid #8B4513;
    border-radius: 5px;
    padding: 8px 12px;
    margin-bottom: 15px;
    font-size: 0.85em;
    color: #8B4513;
    text-align: center;
}

.spell-slots-container {
    background: rgba(139, 69, 19, 0.05);
    border: 1px solid rgba(139, 69, 19, 0.2);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
}

.spell-slots-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    color: #8B4513;
    font-size: 0.9em;
}

.spell-slots-count {
    background: rgba(139, 69, 19, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: bold;
}

.spell-slots-grid {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.spell-slot {
    width: 35px;
    height: 35px;
    border: 2px solid #8B4513;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #F5F5DC;
    color: #8B4513;
    font-weight: bold;
    font-size: 0.8em;
}

.spell-slot:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(139, 69, 19, 0.3);
}

.spell-slot.used {
    background: #8B4513;
    color: #F5F5DC;
    border-color: #654321;
}

.spell-item {
    background: rgba(255,255,255,0.8);
    border: 1px solid rgba(139, 69, 19, 0.3);
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.spell-item:hover {
    background: rgba(255,255,255,0.95);
    border-color: #8B4513;
    transform: translateX(5px);
}

.spell-item.prepared {
    background: rgba(139, 69, 19, 0.1);
    border-color: #8B4513;
    border-left: 4px solid #8B4513;
}

.spell-name {
    font-weight: bold;
    color: #8B4513;
    font-size: 0.9em;
    margin-bottom: 2px;
}

.spell-details {
    font-size: 0.75em;
    color: #654321;
    font-style: italic;
}

.spell-description {
    font-size: 0.7em;
    color: #654321;
    margin-top: 4px;
    line-height: 1.3;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.spell-actions {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 4px;
}

.spell-action-btn {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.7em;
}

.spell-action-btn.prepare {
    background: #28a745;
    color: white;
}

.spell-action-btn.unprepare {
    background: #dc3545;
    color: white;
}

.spell-action-btn:hover {
    transform: scale(1.2);
}

.grimoire-header {
    background: linear-gradient(90deg, #8B4513 0%, #A0522D 100%);
    color: #F5F5DC;
    padding: 15px 20px;
    text-align: center;
    border-radius: 15px 15px 0 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.grimoire-title {
    font-size: 1.5em;
    font-weight: bold;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.grimoire-subtitle {
    font-size: 0.9em;
    margin: 5px 0 0 0;
    opacity: 0.9;
}

.navigation-buttons {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 10px;
}

.nav-btn {
    background: rgba(245, 245, 220, 0.9);
    color: #8B4513;
    border: 2px solid #8B4513;
    border-radius: 8px;
    padding: 8px 12px;
    text-decoration: none;
    font-size: 0.8em;
    font-weight: bold;
    transition: all 0.3s ease;
}

.nav-btn:hover {
    background: #8B4513;
    color: #F5F5DC;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.restore-btn {
    background: linear-gradient(45deg, #ff6b35, #f7931e);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.restore-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.spell-level-badge {
    background: linear-gradient(45deg, #8B4513, #A0522D);
    color: #F5F5DC;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7em;
    font-weight: bold;
    margin-right: 8px;
}

.spell-school-badge {
    background: rgba(139, 69, 19, 0.2);
    color: #8B4513;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.6em;
    font-style: italic;
}
    </style>
</head>
<body>
    <div class="grimoire-container">
        <div class="grimoire-book">
            <!-- En-tête du grimoire -->
            <div class="grimoire-header">
                <div class="navigation-buttons">
                    <a href="view_npc.php?id=<?php echo $npc_id; ?>" class="nav-btn">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    <a href="manage_npcs.php" class="nav-btn">
                        <i class="fas fa-users me-1"></i>NPCs
                    </a>
                </div>
                <h1 class="grimoire-title">
                    <i class="fas fa-book-open me-2"></i>Grimoire de <?php echo htmlspecialchars($character['name']); ?>
                </h1>
                <p class="grimoire-subtitle">
                    <?php echo htmlspecialchars($spell_capabilities['class_name']); ?> de niveau <?php echo $character['level']; ?>
                </p>
            </div>

            <!-- Messages d'alerte -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Pages du grimoire -->
            <div class="grimoire-pages-container">
                <!-- Page de gauche : Emplacements de sorts et capacités -->
                <div class="grimoire-page grimoire-page-left">
                    <!-- Informations sur les capacités -->
                    <div class="spell-capabilities-info">
                        <strong>Modificateur d'incantation :</strong> <?php echo ($spell_capabilities['spellcasting_ability'] >= 0 ? '+' : '') . $spell_capabilities['spellcasting_ability']; ?><br>
                        <strong>Sorts mineurs :</strong> <?php echo $cantrips_count; ?>/<?php echo $spell_capabilities['cantrips_known']; ?> 
                        <span class="text-muted">(toujours préparés)</span><br>
                        <strong>Sorts de niveau 1+ :</strong> 
                        <span class="<?php echo $prepared_spells_count >= $max_prepared_spells ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $prepared_spells_count; ?>/<?php echo $max_prepared_spells; ?>
                        </span>
                        <span class="text-muted">(préparés)</span>
                        <?php if ($prepared_spells_count >= $max_prepared_spells): ?>
                            <span class="text-danger">(Limite atteinte)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Emplacements de sorts -->
                    <div class="spell-slots-container">
                        <div class="spell-slots-header">
                            <span><i class="fas fa-magic me-1"></i>Emplacements de sorts</span>
                        </div>
                        
                        <?php for ($level = 1; $level <= 9; $level++): ?>
                            <?php if ($spell_capabilities && $spell_capabilities['spell_slots'][$level] > 0): ?>
                                <div class="spell-level-section">
                                    <div class="spell-level-title">
                                        Niveau <?php echo $level; ?>
                                        <span class="spell-slots-count">
                                            <?php 
                                            $totalSlots = $spell_capabilities['spell_slots'][$level];
                                            $usedSlots = $spell_slots_usage[$level] ?? 0;
                                            echo $usedSlots . '/' . $totalSlots;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="spell-slots-grid">
                                        <?php for ($i = 1; $i <= $totalSlots; $i++): ?>
                                            <span class="spell-slot <?php echo $i <= $usedSlots ? 'used' : ''; ?>" 
                                                  onclick="useSpellSlot(<?php echo $level; ?>)"
                                                  title="<?php echo $i <= $usedSlots ? 'Utilisé' : 'Disponible'; ?>">
                                                <?php echo $i; ?>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <div class="text-center mt-3">
                            <button class="restore-btn" onclick="restoreAllSpellSlots()">
                                <i class="fas fa-moon me-1"></i>Repos long
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Page de droite : Sorts préparés -->
                <div class="grimoire-page grimoire-page-right">
                    <div class="spell-tabs-container">
                        <h4 class="spell-level-title">
                            <i class="fas fa-scroll me-2"></i>Sorts préparés
                        </h4>
                        
                        <?php if (empty($character_spells)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle me-2"></i>Aucun sort préparé pour le moment.
                            </div>
                        <?php else: ?>
                            <?php for ($level = 0; $level <= 9; $level++): ?>
                                <?php if (isset($spells_by_level[$level])): ?>
                                    <div class="spell-level-section">
                                        <div class="spell-level-title">
                                            <?php if ($level == 0): ?>
                                                <i class="fas fa-sparkles me-1"></i>Sorts mineurs
                                            <?php else: ?>
                                                <i class="fas fa-star me-1"></i>Niveau <?php echo $level; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php foreach ($spells_by_level[$level] as $spell): ?>
                                            <div class="spell-item <?php echo $spell['prepared'] ? 'prepared' : ''; ?>">
                                                <div class="spell-name">
                                                    <?php echo htmlspecialchars($spell['name']); ?>
                                                    <span class="spell-school-badge"><?php echo htmlspecialchars($spell['school']); ?></span>
                                                </div>
                                                <div class="spell-details">
                                                    <?php echo htmlspecialchars($spell['casting_time']); ?> • 
                                                    <?php echo htmlspecialchars($spell['range'] ?? 'N/A'); ?> • 
                                                    <?php echo htmlspecialchars($spell['duration']); ?>
                                                </div>
                                                <?php if (!empty($spell['description'])): ?>
                                                    <div class="spell-description">
                                                        <?php echo htmlspecialchars(substr($spell['description'], 0, 100)) . (strlen($spell['description']) > 100 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="spell-actions">
                                                    <?php if ($spell['level'] > 0): ?>
                                                        <?php 
                                                        $canToggle = $spell['prepared'] || $can_prepare_more;
                                                        $buttonClass = $spell['prepared'] ? 'btn-warning' : ($can_prepare_more ? 'btn-outline-warning' : 'btn-outline-secondary');
                                                        $buttonTitle = $spell['prepared'] ? 'Marquer comme non préparé' : ($can_prepare_more ? 'Marquer comme préparé' : 'Limite de sorts préparés atteinte');
                                                        ?>
                                                        <button class="btn btn-sm toggle-prepared-btn <?php echo $buttonClass; ?>" 
                                                                onclick="<?php echo $canToggle ? 'toggleSpellPrepared(' . $spell['id'] . ')' : 'alert(\'Limite de sorts préparés atteinte (' . $max_prepared_spells . ' maximum)\')'; ?>"
                                                                data-spell-id="<?php echo $spell['id']; ?>"
                                                                data-prepared="<?php echo $spell['prepared'] ? '1' : '0'; ?>"
                                                                title="<?php echo $buttonTitle; ?>"
                                                                <?php echo !$canToggle ? 'disabled' : ''; ?>>
                                                            <i class="fas <?php echo $spell['prepared'] ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                                            <?php echo $spell['prepared'] ? 'Préparé' : 'Non préparé'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="unprepare_spell">
                                                        <input type="hidden" name="spell_id" value="<?php echo $spell['id']; ?>">
                                                        <button type="submit" class="spell-action-btn unprepare" 
                                                                onclick="return confirm('Retirer ce sort du grimoire ?')"
                                                                title="Retirer du grimoire">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter des sorts -->
    <div class="modal fade" id="addSpellsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Ajouter des sorts
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php for ($level = 0; $level <= 9; $level++): ?>
                        <?php if (!empty($allSpells[$level])): ?>
                            <div class="accordion mb-3" id="spellsLevel<?php echo $level; ?>">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $level; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $level; ?>" aria-expanded="false">
                                            <?php if ($level == 0): ?>
                                                <i class="fas fa-sparkles me-2"></i>Sorts mineurs
                                            <?php else: ?>
                                                <i class="fas fa-star me-2"></i>Niveau <?php echo $level; ?>
                                            <?php endif; ?>
                                            (<?php echo count($allSpells[$level]); ?> sorts)
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $level; ?>" class="accordion-collapse collapse" 
                                         data-bs-parent="#spellsLevel<?php echo $level; ?>">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php foreach ($allSpells[$level] as $spell): ?>
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title"><?php echo htmlspecialchars($spell['name']); ?></h6>
                                                                <p class="card-text">
                                                                    <small class="text-muted">
                                                                        <?php echo htmlspecialchars($spell['school']); ?> • 
                                                                        <?php echo htmlspecialchars($spell['casting_time']); ?>
                                                                    </small>
                                                                </p>
                                                                <form method="POST">
                                                                    <input type="hidden" name="action" value="prepare_spell">
                                                                    <input type="hidden" name="spell_id" value="<?php echo $spell['id']; ?>">
                                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                                        <i class="fas fa-plus me-1"></i>Ajouter
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton flottant pour ajouter des sorts -->
    <div class="position-fixed" style="bottom: 20px; right: 20px; z-index: 1000;">
        <button class="btn btn-primary btn-lg rounded-circle" data-bs-toggle="modal" data-bs-target="#addSpellsModal" 
                style="width: 60px; height: 60px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function useSpellSlot(level) {
            if (confirm('Utiliser un emplacement de sort de niveau ' + level + ' ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="use_spell_slot">
                    <input type="hidden" name="spell_level" value="${level}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function restoreAllSpellSlots() {
            if (confirm('Restaurer tous les emplacements de sorts (repos long) ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="restore_spell_slots">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleSpellPrepared(spellId) {
            const button = document.querySelector(`[data-spell-id="${spellId}"]`);
            const isPrepared = button.getAttribute('data-prepared') === '1';
            const newStatus = isPrepared ? 'non préparé' : 'préparé';
            
            if (confirm(`Marquer ce sort comme ${newStatus} ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_spell_prepared">
                    <input type="hidden" name="spell_id" value="${spellId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>