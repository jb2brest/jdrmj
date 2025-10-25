<?php
/**
 * API endpoint pour gérer les rages individuelles (utiliser/libérer)
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

// Debug: afficher les données reçues
error_log("Manage rage - Input: " . $input);
error_log("Manage rage - Data: " . print_r($data, true));

$characterId = $data['character_id'] ?? null;
$npcId = $data['npc_id'] ?? null;
$action = $data['action'] ?? null;
$rageIndex = $data['rage_index'] ?? null;

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

if (!$action) {
    $response['message'] = 'Action is missing.';
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

// Gérer l'action selon le type d'entité
$pdo = \Database::getInstance()->getPdo();
try {
    if ($entityType === 'character') {
        if ($action === 'use') {
            $result = Character::useRageStatic($entityId);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Rage utilisée avec succès.';
            } else {
                $response['message'] = 'Impossible d\'utiliser une rage.';
            }
        } elseif ($action === 'reset') {
            $result = Character::resetRagesStatic($entityId);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Rages réinitialisées avec succès.';
            } else {
                $response['message'] = 'Impossible de réinitialiser les rages.';
            }
        }
    } elseif ($entityType === 'npc') {
        if ($action === 'toggle') {
            // Toggle une rage spécifique
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
            // Si l'index de la rage est <= au nombre de rages utilisées, elle est utilisée
            if ($rageIndex <= $used) {
                // Cette rage est utilisée, on la libère (décrémenter le compteur)
                $newUsed = max(0, $used - 1);
            } else {
                // Cette rage n'est pas utilisée, on l'utilise (incrémenter le compteur)
                $newUsed = min($maxUses, $used + 1);
            }
            
            $stmt = $pdo->prepare("UPDATE npc_rage_usage SET used = ? WHERE npc_id = ?");
            $stmt->execute([$newUsed, $entityId]);
            
            $response['success'] = true;
            $response['message'] = 'Rage mise à jour avec succès.';
            $response['used_rages'] = $newUsed;
            $response['total_rages'] = $maxUses;
        } elseif ($action === 'reset') {
            // Réinitialiser toutes les rages
            $stmt = $pdo->prepare("UPDATE npc_rage_usage SET used = 0 WHERE npc_id = ?");
            $stmt->execute([$entityId]);
            
            $response['success'] = true;
            $response['message'] = 'Rages réinitialisées avec succès.';
            $response['used_rages'] = 0;
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la gestion de la rage: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
}

echo json_encode($response);
?>
