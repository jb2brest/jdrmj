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
    $xp_action = $_POST['xp_action'] ?? '';
    $xp_amount = (int)($_POST['xp_amount'] ?? 0);
    $type_cible = $_POST['type_cible'] ?? 'PNJ';
    
    if (!$npc_id) {
        throw new Exception('ID du NPC manquant');
    }
    
    // Valider le type de cible
    if (!in_array($type_cible, ['PNJ', 'monstre', 'PJ'])) {
        throw new Exception('Type de cible invalide');
    }
    
    if (!$xp_action) {
        throw new Exception('Action XP manquante');
    }
    
    // Récupérer l'instance NPC
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('NPC non trouvé');
    }
    
    $current_xp = $npc->experience ?? 0;
    $new_xp = $current_xp;
    $message = '';
    
    switch ($xp_action) {
        case 'add':
            if ($xp_amount > 0) {
                $new_xp = $current_xp + $xp_amount;
                $npc->updateMyExperiencePoints($new_xp);
                $message = "Points d'expérience ajoutés ({$type_cible}) : +{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            } else {
                throw new Exception('Le montant d\'XP doit être positif');
            }
            break;
            
        case 'remove':
            if ($xp_amount > 0) {
                $new_xp = max(0, $current_xp - $xp_amount);
                $npc->updateMyExperiencePoints($new_xp);
                $message = "Points d'expérience retirés ({$type_cible}) : -{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            } else {
                throw new Exception('Le montant d\'XP doit être positif');
            }
            break;
            
        case 'set':
            if ($xp_amount >= 0) {
                $new_xp = $xp_amount;
                $npc->updateMyExperiencePoints($new_xp);
                $message = "Points d'expérience définis ({$type_cible}) : " . number_format($new_xp) . " XP";
            } else {
                throw new Exception('Le montant d\'XP ne peut pas être négatif');
            }
            break;
            
        default:
            throw new Exception('Action XP invalide');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'current_xp' => $new_xp,
        'xp_change' => $new_xp - $current_xp,
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
