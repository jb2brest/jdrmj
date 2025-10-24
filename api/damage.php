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
    $damage = (int)($_POST['damage'] ?? 0);
    $type_cible = $_POST['type_cible'] ?? 'PNJ';
    
    if (!$npc_id) {
        throw new Exception('ID du NPC manquant');
    }
    
    // Valider le type de cible
    if (!in_array($type_cible, ['PNJ', 'monstre', 'PJ'])) {
        throw new Exception('Type de cible invalide');
    }
    
    if ($damage <= 0) {
        throw new Exception('Les dégâts doivent être positifs');
    }
    
    // Récupérer l'instance NPC
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('NPC non trouvé');
    }
    
    // Calculer les nouveaux points de vie
    $new_hp = max(0, $npc->hit_points_current - $damage);
    $npc->updateMyHitPoints($new_hp);
    
    echo json_encode([
        'success' => true,
        'message' => "Dégâts infligés ({$type_cible}) : {$damage} PV. Points de vie restants : {$new_hp}",
        'current_hp' => $new_hp,
        'damage_dealt' => $damage,
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
