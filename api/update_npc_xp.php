<?php
require_once '../config/database.php';
require_once '../classes/NPC.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['npc_id']) || !isset($input['action']) || !isset($input['amount'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$npcId = (int)$input['npc_id'];
$action = $input['action'];
$amount = (int)$input['amount'];

try {
    // Récupérer l'XP actuel
    $npc = NPC::findById($npcId);
    if (!$npc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'NPC non trouvé']);
        exit;
    }

    $currentXp = $npc->experience;
    $newXp = $currentXp;

    if ($action === 'add') {
        $newXp = $currentXp + $amount;
    } elseif ($action === 'remove') {
        $newXp = max(0, $currentXp - $amount);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
        exit;
    }

    // Mettre à jour l'XP
    if (NPC::updateExperiencePoints($npcId, $newXp)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Expérience mise à jour',
            'new_xp' => $newXp,
            'change' => $newXp - $currentXp
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

