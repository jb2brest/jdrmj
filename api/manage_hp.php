<?php
/**
 * API unifiée pour la gestion des points de vie
 * Supporte les PNJ, monstres et personnages joueurs
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

try {
    // Récupérer les données
    $input = json_decode(file_get_contents('php://input'), true);
    
    $target_id = (int)($input['target_id'] ?? 0);
    $target_type = $input['target_type'] ?? 'PNJ';
    $action = $input['action'] ?? 'update';
    $amount = (int)($input['amount'] ?? 0);
    $new_hp = (int)($input['new_hp'] ?? 0);
    
    // Validation des paramètres
    if (!$target_id) {
        throw new Exception('ID de la cible manquant');
    }
    
    if (!in_array($target_type, ['PNJ', 'monstre', 'PJ'])) {
        throw new Exception('Type de cible invalide. Types supportés: PNJ, monstre, PJ');
    }
    
    if (!in_array($action, ['update', 'damage', 'heal', 'reset'])) {
        throw new Exception('Action invalide. Actions supportées: update, damage, heal, reset');
    }
    
    // Traiter selon le type de cible
    switch ($target_type) {
        case 'PNJ':
            $result = handleNpcHp($target_id, $action, $amount, $new_hp);
            break;
            
        case 'monstre':
            $result = handleMonsterHp($target_id, $action, $amount, $new_hp);
            break;
            
        case 'PJ':
            $result = handleCharacterHp($target_id, $action, $amount, $new_hp);
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
 * Gérer les HP d'un PNJ
 */
function handleNpcHp($npc_id, $action, $amount, $new_hp) {
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('PNJ non trouvé');
    }
    
    $current_hp = $npc->hit_points_current;
    $max_hp = $npc->hit_points_max;
    
    switch ($action) {
        case 'update':
            if ($new_hp < 0) $new_hp = 0;
            if ($new_hp > $max_hp) $new_hp = $max_hp;
            $npc->updateMyHitPoints($new_hp);
            $final_hp = $new_hp;
            break;
            
        case 'damage':
            $final_hp = max(0, $current_hp - $amount);
            $npc->updateMyHitPoints($final_hp);
            break;
            
        case 'heal':
            $final_hp = min($max_hp, $current_hp + $amount);
            $npc->updateMyHitPoints($final_hp);
            break;
            
        case 'reset':
            $final_hp = $max_hp;
            $npc->updateMyHitPoints($final_hp);
            break;
            
        default:
            throw new Exception('Action non supportée pour les PNJ');
    }
    
    return [
        'success' => true,
        'message' => "Points de vie du PNJ mis à jour : {$final_hp}/{$max_hp}",
        'current_hp' => $final_hp,
        'max_hp' => $max_hp,
        'target_type' => 'PNJ',
        'target_id' => $npc_id
    ];
}

/**
 * Gérer les HP d'un monstre
 */
function handleMonsterHp($monster_id, $action, $amount, $new_hp) {
    // Pour les monstres, on utilise la table place_npcs jointe avec monsters
    $pdo = Database::getInstance()->getPdo();
    
    $stmt = $pdo->prepare("
        SELECT pn.*, m.hit_points as max_hit_points 
        FROM place_npcs pn 
        LEFT JOIN monsters m ON pn.monster_id = m.id 
        WHERE pn.id = ? AND pn.monster_id IS NOT NULL
    ");
    $stmt->execute([$monster_id]);
    $monster = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$monster) {
        throw new Exception('Monstre non trouvé');
    }
    
    $current_hp = $monster['current_hit_points'] ?? 0;
    $max_hp = $monster['max_hit_points'] ?? 0;
    
    // Si pas de HP maximum défini, utiliser les HP actuels comme maximum
    if ($max_hp <= 0) {
        $max_hp = max($current_hp, 1);
    }
    
    switch ($action) {
        case 'update':
            if ($new_hp < 0) $new_hp = 0;
            if ($new_hp > $max_hp) $new_hp = $max_hp;
            $final_hp = $new_hp;
            break;
            
        case 'damage':
            $final_hp = max(0, $current_hp - $amount);
            break;
            
        case 'heal':
            $final_hp = min($max_hp, $current_hp + $amount);
            break;
            
        case 'reset':
            $final_hp = $max_hp;
            break;
            
        default:
            throw new Exception('Action non supportée pour les monstres');
    }
    
    // Mettre à jour dans la base de données
    $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
    $stmt->execute([$final_hp, $monster_id]);
    
    return [
        'success' => true,
        'message' => "Points de vie du monstre mis à jour : {$final_hp}/{$max_hp}",
        'current_hp' => $final_hp,
        'max_hp' => $max_hp,
        'target_type' => 'monstre',
        'target_id' => $monster_id
    ];
}

/**
 * Gérer les HP d'un personnage joueur
 */
function handleCharacterHp($character_id, $action, $amount, $new_hp) {
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
    
    $current_hp = $character->hit_points_current;
    $max_hp = $character->hit_points_max;
    
    switch ($action) {
        case 'update':
            if ($new_hp < 0) $new_hp = 0;
            if ($new_hp > $max_hp) $new_hp = $max_hp;
            Character::updateHitPoints($character_id, $new_hp);
            $final_hp = $new_hp;
            break;
            
        case 'damage':
            $final_hp = max(0, $current_hp - $amount);
            Character::updateHitPoints($character_id, $final_hp);
            break;
            
        case 'heal':
            $final_hp = min($max_hp, $current_hp + $amount);
            Character::updateHitPoints($character_id, $final_hp);
            break;
            
        case 'reset':
            $final_hp = $max_hp;
            Character::updateHitPoints($character_id, $final_hp);
            break;
            
        default:
            throw new Exception('Action non supportée pour les personnages');
    }
    
    return [
        'success' => true,
        'message' => "Points de vie du personnage mis à jour : {$final_hp}/{$max_hp}",
        'current_hp' => $final_hp,
        'max_hp' => $max_hp,
        'target_type' => 'PJ',
        'target_id' => $character_id
    ];
}
?>
