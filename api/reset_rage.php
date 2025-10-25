<?php
/**
 * API endpoint pour restaurer les rages (personnage ou NPC)
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
if (!$data && isset($_POST['character_id'])) {
    $data = $_POST;
}

$characterId = $data['character_id'] ?? null;
$npcId = $data['npc_id'] ?? null;

// Déterminer le type d'entité
$entityType = null;
$entityId = null;

if ($characterId) {
    $entityType = 'character';
    $entityId = $characterId;
} elseif ($npcId) {
    $entityType = 'npc';
    $entityId = $npcId;
} else {
    $response['message'] = 'Character ID or NPC ID is missing.';
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

// Restaurer les rages
$pdo = \Database::getInstance()->getPdo();
try {
    if ($entityType === 'character') {
        // Réinitialiser toutes les rages du personnage
        $stmt = $pdo->prepare("UPDATE character_rage_usage SET used = 0 WHERE character_id = ?");
        $stmt->execute([$entityId]);
        
        // Récupérer les données mises à jour
        $stmt = $pdo->prepare("SELECT used, max_uses FROM character_rage_usage WHERE character_id = ?");
        $stmt->execute([$entityId]);
        $rageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rageData) {
            // Créer une entrée par défaut si elle n'existe pas
            $stmt = $pdo->prepare("INSERT INTO character_rage_usage (character_id, used, max_uses) VALUES (?, 0, 2)");
            $stmt->execute([$entityId]);
            $used = 0;
            $maxUses = 2;
        } else {
            $used = $rageData['used'];
            $maxUses = $rageData['max_uses'];
        }
        
        $response['success'] = true;
        $response['message'] = 'Rages réinitialisées avec succès.';
        $response['used_rages'] = $used;
        $response['total_rages'] = $maxUses;
    } elseif ($entityType === 'npc') {
        // Réinitialiser toutes les rages
        $stmt = $pdo->prepare("UPDATE npc_rage_usage SET used = 0 WHERE npc_id = ?");
        $stmt->execute([$entityId]);
        
        // Récupérer les données mises à jour
        $stmt = $pdo->prepare("SELECT used, max_uses FROM npc_rage_usage WHERE npc_id = ?");
        $stmt->execute([$entityId]);
        $rageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rageData) {
            // Créer une entrée par défaut si elle n'existe pas
            $stmt = $pdo->prepare("INSERT INTO npc_rage_usage (npc_id, used, max_uses) VALUES (?, 0, 2)");
            $stmt->execute([$entityId]);
            $used = 0;
            $maxUses = 2;
        } else {
            $used = $rageData['used'];
            $maxUses = $rageData['max_uses'];
        }
        
        $response['success'] = true;
        $response['message'] = 'Rages réinitialisées avec succès.';
        $response['used_rages'] = $used;
        $response['total_rages'] = $maxUses;
    }
} catch (Exception $e) {
    $response['message'] = 'Database error occurred.';
}

echo json_encode($response);
?>
