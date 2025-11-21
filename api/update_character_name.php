<?php
/**
 * API Endpoint: Mettre à jour le nom d'un personnage (PJ ou PNJ)
 */

require_once '../includes/functions.php';
require_once '../classes/init.php';

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $character_id = (int)($_POST['character_id'] ?? 0);
    $character_type = $_POST['character_type'] ?? ''; // 'pj' ou 'pnj'
    $new_name = trim($_POST['new_name'] ?? '');
    
    if (!$character_id || !in_array($character_type, ['pj', 'pnj']) || empty($new_name)) {
        throw new Exception('Données manquantes ou invalides');
    }
    
    // Vérifier les permissions selon le type
    if ($character_type === 'pj') {
        $character = Character::findById($character_id);
        if (!$character) {
            throw new Exception('Personnage non trouvé');
        }
        
        // Seul le propriétaire ou un MJ/Admin peut renommer
        if ($character->user_id != $_SESSION['user_id'] && !User::isDMOrAdmin()) {
            throw new Exception('Vous n\'avez pas la permission de renommer ce personnage');
        }
        
        // Mettre à jour le nom
        if ($character->update(['name' => $new_name])) {
            echo json_encode([
                'success' => true,
                'message' => 'Nom du personnage mis à jour avec succès'
            ]);
        } else {
            throw new Exception('Erreur lors de la mise à jour du nom');
        }
        
    } elseif ($character_type === 'pnj') {
        $npc = NPC::findById($character_id);
        if (!$npc) {
            throw new Exception('PNJ non trouvé');
        }
        
        // Seul le créateur ou un MJ/Admin peut renommer
        if ($npc->created_by != $_SESSION['user_id'] && !User::isDMOrAdmin()) {
            throw new Exception('Vous n\'avez pas la permission de renommer ce PNJ');
        }
        
        // Mettre à jour le nom
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE npcs SET name = ? WHERE id = ?");
        if ($stmt->execute([$new_name, $character_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Nom du PNJ mis à jour avec succès'
            ]);
        } else {
            throw new Exception('Erreur lors de la mise à jour du nom');
        }
    }
    
} catch (Exception $e) {
    error_log("Erreur update_character_name.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

