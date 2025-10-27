<?php
/**
 * API endpoint pour basculer l'état d'une rage spécifique (personnage ou NPC)
 */

require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isLoggedIn()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

// Récupérer les données POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si les données JSON ne sont pas disponibles, essayer $_POST
if (!$data && isset($_POST['target_id'])) {
    $data = $_POST;
}

$targetId = $data['target_id'] ?? null;
$targetType = $data['target_type'] ?? null;
$rageIndex = $data['rage_index'] ?? null;

// Déterminer le type d'entité basé sur target_type
$entityType = null;
$entityId = $targetId;

if ($targetType === 'PJ') {
    $entityType = 'character';
} elseif ($targetType === 'PNJ') {
    $entityType = 'npc';
} else {
    $response['message'] = 'Invalid target type. Must be "PJ" or "PNJ".';
    echo json_encode($response);
    exit();
}

if (!$entityId) {
    $response['message'] = 'Target ID is required.';
    echo json_encode($response);
    exit();
}

if (!$rageIndex) {
    $response['message'] = 'Rage index is missing.';
    echo json_encode($response);
    exit();
}

// Vérifier les permissions selon le type d'entité
if ($entityType === 'character') {
    $character = Character::findById($entityId);
    if (!$character || ($character->getUserId() != $_SESSION['user_id'] && !User::isDMOrAdmin())) {
        $response['message'] = 'Access denied.';
        echo json_encode($response);
        exit();
    }
} elseif ($entityType === 'npc') {
    $npc = NPC::findById($entityId);
    if (!$npc || ($npc->created_by != $_SESSION['user_id'] && !User::isDMOrAdmin())) {
        $response['message'] = 'Access denied.';
        echo json_encode($response);
        exit();
    }
}

// Basculer l'état de la rage
$pdo = \Database::getInstance()->getPdo();
try {
    if ($entityType === 'character') {
        // Récupérer l'état actuel des rages du personnage
        $stmt = $pdo->prepare("SELECT used, max_uses FROM character_rage_usage WHERE character_id = ?");
        $stmt->execute([$entityId]);
        $rageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rageData) {
            // Créer une entrée par défaut
            $stmt = $pdo->prepare("INSERT INTO character_rage_usage (character_id, used, max_uses) VALUES (?, 0, 2)");
            $stmt->execute([$entityId]);
            $used = 0;
            $maxUses = 2;
        } else {
            $used = $rageData['used'];
            $maxUses = $rageData['max_uses'];
        }
        
        // Logique de toggle basée sur l'index de la rage
        if ($rageIndex <= $used) {
            // Cette rage est utilisée, on la libère
            $newUsed = max(0, $used - 1);
        } else {
            // Cette rage n'est pas utilisée, on l'utilise
            $newUsed = min($maxUses, $used + 1);
        }
        
        $stmt = $pdo->prepare("UPDATE character_rage_usage SET used = ? WHERE character_id = ?");
        $stmt->execute([$newUsed, $entityId]);
        
        $response['success'] = true;
        $response['message'] = 'Rage mise à jour avec succès.';
        $response['used_rages'] = $newUsed;
        $response['total_rages'] = $maxUses;
        
    } elseif ($entityType === 'npc') {
        // Récupérer l'état actuel des rages
        $stmt = $pdo->prepare("SELECT used, max_uses FROM npc_rage_usage WHERE npc_id = ?");
        $stmt->execute([$entityId]);
        $rageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rageData) {
            // Créer une entrée par défaut
            $stmt = $pdo->prepare("INSERT INTO npc_rage_usage (npc_id, used, max_uses) VALUES (?, 0, 2)");
            $stmt->execute([$entityId]);
            $used = 0;
            $maxUses = 2;
        } else {
            $used = $rageData['used'];
            $maxUses = $rageData['max_uses'];
        }
        
        // Logique de toggle basée sur l'index de la rage
        if ($rageIndex <= $used) {
            // Cette rage est utilisée, on la libère
            $newUsed = max(0, $used - 1);
        } else {
            // Cette rage n'est pas utilisée, on l'utilise
            $newUsed = min($maxUses, $used + 1);
        }
        
        $stmt = $pdo->prepare("UPDATE npc_rage_usage SET used = ? WHERE npc_id = ?");
        $stmt->execute([$newUsed, $entityId]);
        
        $response['success'] = true;
        $response['message'] = 'Rage mise à jour avec succès.';
        $response['used_rages'] = $newUsed;
        $response['total_rages'] = $maxUses;
    }
} catch (Exception $e) {
    $response['message'] = 'Database error occurred.';
}

echo json_encode($response);
?>
