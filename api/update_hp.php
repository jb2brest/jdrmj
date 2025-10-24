<?php
header('Content-Type: application/json');
require_once '../classes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $npc_id = (int)($_POST['npc_id'] ?? 0);
    $new_hp = (int)($_POST['current_hp'] ?? 0);
    $max_hp = (int)($_POST['max_hp'] ?? 0);
    $type_cible = $_POST['type_cible'] ?? 'PNJ';
    
    if (!$npc_id) {
        throw new Exception('ID du NPC manquant');
    }
    
    // Valider le type de cible
    if (!in_array($type_cible, ['PNJ', 'monstre', 'PJ'])) {
        throw new Exception('Type de cible invalide');
    }
    
    // Récupérer l'instance NPC
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('NPC non trouvé');
    }
    
    // Valider les points de vie
    if ($new_hp < 0) {
        $new_hp = 0;
    }
    if ($new_hp > $max_hp) {
        $new_hp = $max_hp;
    }
    
    // Mettre à jour les points de vie actuels
    $npc->updateMyHitPoints($new_hp);
    
    echo json_encode([
        'success' => true,
        'message' => "Points de vie mis à jour ({$type_cible}) : {$new_hp}/{$max_hp}",
        'current_hp' => $new_hp,
        'max_hp' => $max_hp,
        'type_cible' => $type_cible
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>