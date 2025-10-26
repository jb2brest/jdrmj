<?php
/**
 * API unifiée pour la gestion des longs repos
 * Supporte les PNJ et personnages joueurs
 * Restaure : emplacements de sorts, rages, points de vie, etc.
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../classes/NPC.php';
require_once '../classes/Character.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $target_id = (int)($input['target_id'] ?? 0);
    $target_type = $input['target_type'] ?? '';

    if (!$target_id) {
        throw new Exception('ID de la cible manquant');
    }

    if (!in_array($target_type, ['PNJ', 'PJ'])) {
        throw new Exception('Type de cible invalide. Types supportés: PNJ, PJ');
    }

    // Traiter selon le type de cible
    switch ($target_type) {
        case 'PNJ':
            $result = handleNpcLongRest($target_id);
            break;

        case 'PJ':
            $result = handleCharacterLongRest($target_id);
            break;

        default:
            throw new Exception('Type de cible non supporté');
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Gérer le long repos d'un PNJ
 */
function handleNpcLongRest($npc_id) {
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('PNJ non trouvé');
    }

    $actions_performed = [];
    $success = true;

    // 1. Restaurer les points de vie au maximum
    if ($npc->hit_points_max > 0) {
        $npc->updateMyHitPoints($npc->hit_points_max);
        $actions_performed[] = 'Points de vie restaurés au maximum';
    }

    // 2. Restaurer les emplacements de sorts
    if (NPC::restoreSpellSlots($npc_id)) {
        $actions_performed[] = 'Emplacements de sorts restaurés';
    }

    // 3. Restaurer les rages (si applicable)
    $pdo = Database::getInstance()->getPdo();
    try {
        // Vérifier si le PNJ a des rages
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM npc_rage_usage WHERE npc_id = ?");
        $stmt->execute([$npc_id]);
        $has_rages = $stmt->fetchColumn() > 0;
        
        if ($has_rages) {
            $stmt = $pdo->prepare("UPDATE npc_rage_usage SET used = 0 WHERE npc_id = ?");
            $stmt->execute([$npc_id]);
            $actions_performed[] = 'Rages restaurées';
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la restauration des rages PNJ: " . $e->getMessage());
    }

    // 4. Restaurer d'autres capacités spéciales si nécessaire
    // (À étendre selon les besoins futurs)

    $message = 'Long repos effectué pour le PNJ';
    if (!empty($actions_performed)) {
        $message .= ': ' . implode(', ', $actions_performed);
    }

    return [
        'success' => $success,
        'message' => $message,
        'target_type' => 'PNJ',
        'target_id' => $npc_id,
        'actions_performed' => $actions_performed
    ];
}

/**
 * Gérer le long repos d'un personnage joueur
 */
function handleCharacterLongRest($character_id) {
    requireLogin(); // Assurez-vous que l'utilisateur est connecté

    $character = Character::findById($character_id);
    if (!$character) {
        throw new Exception('Personnage non trouvé');
    }

    // Vérifier les permissions
    $isOwner = $character->belongsToUser($_SESSION['user_id']);
    $isDM = isDM();
    $isAdmin = User::isAdmin();

    if (!$isOwner && !$isDM && !$isAdmin) {
        throw new Exception('Permissions insuffisantes pour effectuer un long repos sur ce personnage');
    }

    $actions_performed = [];
    $success = true;

    // 1. Restaurer les points de vie au maximum
    if ($character->hit_points_max > 0) {
        $success = Character::updateHitPoints($character_id, $character->hit_points_max);
        if ($success) {
            $actions_performed[] = 'Points de vie restaurés au maximum';
        }
    }

    // 2. Restaurer les emplacements de sorts
    if (Character::canCastSpells($character->class_id)) {
        if (Character::resetSpellSlotsUsageStatic($character_id)) {
            $actions_performed[] = 'Emplacements de sorts restaurés';
        }
    }

    // 3. Restaurer les rages (si applicable)
    $pdo = Database::getInstance()->getPdo();
    try {
        // Vérifier si le personnage a des rages
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM character_rage_usage WHERE character_id = ?");
        $stmt->execute([$character_id]);
        $has_rages = $stmt->fetchColumn() > 0;
        
        if ($has_rages) {
            $stmt = $pdo->prepare("UPDATE character_rage_usage SET used = 0 WHERE character_id = ?");
            $stmt->execute([$character_id]);
            $actions_performed[] = 'Rages restaurées';
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la restauration des rages PJ: " . $e->getMessage());
    }

    // 4. Restaurer d'autres capacités spéciales si nécessaire
    // (À étendre selon les besoins futurs)

    $message = 'Long repos effectué pour le personnage';
    if (!empty($actions_performed)) {
        $message .= ': ' . implode(', ', $actions_performed);
    }

    return [
        'success' => $success,
        'message' => $message,
        'target_type' => 'PJ',
        'target_id' => $character_id,
        'actions_performed' => $actions_performed
    ];
}
?>
