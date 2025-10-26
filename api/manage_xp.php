<?php
/**
 * API unifiée pour la gestion des points d'expérience
 * Supporte les PNJ, monstres et personnages joueurs
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
    $action = $input['action'] ?? '';
    $amount = (int)($input['amount'] ?? 0);
    $new_xp = (int)($input['new_xp'] ?? 0);

    if (!$target_id) {
        throw new Exception('ID de la cible manquant');
    }

    if (!in_array($action, ['update', 'add', 'remove', 'set'])) {
        throw new Exception('Action invalide. Actions supportées: update, add, remove, set');
    }

    // Traiter selon le type de cible
    switch ($target_type) {
        case 'PNJ':
            $result = handleNpcXp($target_id, $action, $amount, $new_xp);
            break;

        case 'monstre':
            $result = handleMonsterXp($target_id, $action, $amount, $new_xp);
            break;

        case 'PJ':
            $result = handleCharacterXp($target_id, $action, $amount, $new_xp);
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
 * Gérer les XP d'un PNJ
 */
function handleNpcXp($npc_id, $action, $amount, $new_xp) {
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('PNJ non trouvé');
    }

    $current_xp = $npc->experience ?? 0;

    switch ($action) {
        case 'update':
            if ($new_xp < 0) $new_xp = 0;
            $npc->updateMyExperiencePoints($new_xp);
            $final_xp = $new_xp;
            break;

        case 'add':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = $current_xp + $amount;
            $npc->updateMyExperiencePoints($final_xp);
            break;

        case 'remove':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = max(0, $current_xp - $amount);
            $npc->updateMyExperiencePoints($final_xp);
            break;

        case 'set':
            if ($amount < 0) {
                throw new Exception('Le montant ne peut pas être négatif');
            }
            $final_xp = $amount;
            $npc->updateMyExperiencePoints($final_xp);
            break;

        default:
            throw new Exception('Action non supportée pour les PNJ');
    }

    return [
        'success' => true,
        'message' => "Points d'expérience du PNJ mis à jour : " . number_format($final_xp) . " XP",
        'current_xp' => $final_xp,
        'target_type' => 'PNJ',
        'target_id' => $npc_id
    ];
}

/**
 * Gérer les XP d'un monstre
 */
function handleMonsterXp($monster_id, $action, $amount, $new_xp) {
    // Pour les monstres, on utilise la table place_npcs
    $pdo = Database::getInstance()->getPdo();

    $stmt = $pdo->prepare("SELECT * FROM place_npcs WHERE id = ? AND monster_id IS NOT NULL");
    $stmt->execute([$monster_id]);
    $monster = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$monster) {
        throw new Exception('Monstre non trouvé');
    }

    $current_xp = $monster['experience'] ?? 0;

    switch ($action) {
        case 'update':
            if ($new_xp < 0) $new_xp = 0;
            $final_xp = $new_xp;
            break;

        case 'add':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = $current_xp + $amount;
            break;

        case 'remove':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = max(0, $current_xp - $amount);
            break;

        case 'set':
            if ($amount < 0) {
                throw new Exception('Le montant ne peut pas être négatif');
            }
            $final_xp = $amount;
            break;

        default:
            throw new Exception('Action non supportée pour les monstres');
    }

    // Mettre à jour dans la base de données
    $stmt = $pdo->prepare("UPDATE place_npcs SET experience = ? WHERE id = ?");
    $stmt->execute([$final_xp, $monster_id]);

    return [
        'success' => true,
        'message' => "Points d'expérience du monstre mis à jour : " . number_format($final_xp) . " XP",
        'current_xp' => $final_xp,
        'target_type' => 'monstre',
        'target_id' => $monster_id
    ];
}

/**
 * Gérer les XP d'un personnage joueur
 */
function handleCharacterXp($character_id, $action, $amount, $new_xp) {
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
        throw new Exception('Permissions insuffisantes pour modifier ce personnage');
    }

    $current_xp = $character->experience ?? 0;

    switch ($action) {
        case 'update':
            if ($new_xp < 0) $new_xp = 0;
            $final_xp = $new_xp;
            break;

        case 'add':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = $current_xp + $amount;
            break;

        case 'remove':
            if ($amount <= 0) {
                throw new Exception('Le montant doit être positif');
            }
            $final_xp = max(0, $current_xp - $amount);
            break;

        case 'set':
            if ($amount < 0) {
                throw new Exception('Le montant ne peut pas être négatif');
            }
            $final_xp = $amount;
            break;

        default:
            throw new Exception('Action non supportée pour les personnages');
    }

    // Mettre à jour dans la base de données
    $success = Character::updateExperience($character_id, $final_xp);

    if (!$success) {
        throw new Exception('Erreur lors de la mise à jour des points d\'expérience du personnage');
    }

    return [
        'success' => true,
        'message' => "Points d'expérience du personnage mis à jour : " . number_format($final_xp) . " XP",
        'current_xp' => $final_xp,
        'target_type' => 'PJ',
        'target_id' => $character_id
    ];
}
?>
