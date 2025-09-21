<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['character_id']) || !isset($input['item_name']) || !isset($input['item_type']) || !isset($input['slot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$characterId = (int)$input['character_id'];
$itemName = $input['item_name'];
$itemType = $input['item_type'];
$slot = $input['slot'];

try {
    // Vérifier que le personnage appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT user_id FROM characters WHERE id = ?");
    $stmt->execute([$characterId]);
    $character = $stmt->fetch();
    
    if (!$character || $character['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
    
    // Vérifier les règles d'équipement
    if ($itemType === 'weapon') {
        // Récupérer les informations de l'arme avec recherche flexible
        $weapon = null;
        
        // D'abord essayer une correspondance exacte
        $stmt = $pdo->prepare("SELECT hands FROM weapons WHERE name = ?");
        $stmt->execute([$itemName]);
        $weapon = $stmt->fetch();
        
        // Si pas trouvé, essayer de chercher sans les articles et avec majuscule
        if (!$weapon) {
            $itemNameWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $itemName);
            $itemNameCapitalized = ucfirst($itemNameWithoutArticle);
            $stmt = $pdo->prepare("SELECT hands FROM weapons WHERE name = ?");
            $stmt->execute([$itemNameCapitalized]);
            $weapon = $stmt->fetch();
        }
        
        // Si toujours pas trouvé, chercher par correspondance partielle
        if (!$weapon) {
            $itemNameWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $itemName);
            $stmt = $pdo->prepare("SELECT hands FROM weapons WHERE name LIKE ?");
            $stmt->execute(['%' . $itemNameWithoutArticle . '%']);
            $weapon = $stmt->fetch();
        }
        
        // Si toujours pas trouvé, chercher avec le mot clé principal (gestion du pluriel)
        if (!$weapon) {
            $itemNameWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $itemName);
            // Extraire le dernier mot et enlever le 's' final (ex: "deux hachettes" -> "hachette")
            $words = explode(' ', $itemNameWithoutArticle);
            $lastWord = end($words);
            $keyword = rtrim($lastWord, 's');
            $stmt = $pdo->prepare("SELECT hands FROM weapons WHERE name LIKE ?");
            $stmt->execute(['%' . $keyword . '%']);
            $weapon = $stmt->fetch();
        }
        
        if (!$weapon) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Arme non trouvée: ' . $itemName]);
            exit;
        }
        
        // Vérifier les règles d'équipement
        // TOUJOURS libérer tous les slots de main avant d'équiper une nouvelle arme
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND item_type = 'weapon' AND equipped = 1
        ");
        $stmt->execute([$characterId]);
    } elseif ($itemType === 'shield') {
        // Bouclier : libérer le slot off_hand
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND equipped_slot = 'off_hand'
        ");
        $stmt->execute([$characterId]);
    } elseif ($itemType === 'armor') {
        // Armure : libérer le slot armor
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND equipped_slot = 'armor'
        ");
        $stmt->execute([$characterId]);
    }
    
    // Équiper l'objet dans la nouvelle table
    // Cas spécial : "deux hachettes" = équiper dans les deux mains
    if (strtolower($itemName) === 'deux hachettes') {
        // Équiper dans les deux slots (stocké comme "main_hand,off_hand")
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 1, equipped_slot = 'main_hand,off_hand' 
            WHERE character_id = ? AND item_name = ? AND item_type = ?
        ");
        $success = $stmt->execute([$characterId, $itemName, $itemType]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Deux hachettes équipées avec succès (une dans chaque main)']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'équipement des deux hachettes']);
        }
    } else {
        // Équipement normal
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 1, equipped_slot = ? 
            WHERE character_id = ? AND item_name = ? AND item_type = ?
        ");
        $success = $stmt->execute([$slot, $characterId, $itemName, $itemType]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Objet équipé avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'équipement']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
