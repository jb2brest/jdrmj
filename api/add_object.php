<?php
/**
 * API Endpoint: Ajouter un objet à une pièce
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/Room.php';
require_once dirname(__DIR__) . '/classes/Item.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $placeId = (int)($_POST['place_id'] ?? 0);
    $objectType = sanitizeInput($_POST['object_type'] ?? '');
    $displayName = sanitizeInput($_POST['display_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $specificItemId = (int)($_POST['specific_item_id'] ?? 0);
    
    // Champs spécifiques pour les lettres
    $letterContent = sanitizeInput($_POST['letter_content'] ?? '');
    $letterRecipient = sanitizeInput($_POST['letter_recipient'] ?? '');
    $letterSealed = isset($_POST['letter_sealed']) ? 1 : 0;
    
    // Champs spécifiques pour l'or
    $goldCoins = (int)($_POST['gold_coins'] ?? 0);
    $silverCoins = (int)($_POST['silver_coins'] ?? 0);
    $copperCoins = (int)($_POST['copper_coins'] ?? 0);
    
    if (!$placeId || !$objectType || !$displayName) {
        throw new Exception('Données manquantes');
    }
    
    // Vérifier que la pièce existe
    $lieu = Room::findById($placeId);
    if (!$lieu) {
        throw new Exception('Pièce non trouvé');
    }
    
    // Vérifier les permissions (seuls les DM et admins peuvent ajouter des objets)
    if (!User::isDMOrAdmin()) {
        throw new Exception('Permissions insuffisantes');
    }
    
    // Préparer les données de l'objet
    $objectData = [
        'place_id' => $placeId,
        'display_name' => $displayName,
        'object_type' => $objectType,
        'type_precis' => $objectType, // Utiliser le type comme type précis
        'description' => $description,
        'is_identified' => 0, // Par défaut non identifié
        'is_visible' => 1, // Par défaut visible
        'is_equipped' => 0, // Par défaut non équipé
        'position_x' => 0,
        'position_y' => 0,
        'is_on_map' => 0, // Par défaut pas sur la carte
        'owner_type' => 'place', // Appartient à la pièce
        'owner_id' => null,
        'poison_id' => null,
        'weapon_id' => null,
        'armor_id' => null,
        'gold_coins' => 0,
        'silver_coins' => 0,
        'copper_coins' => 0,
        'letter_content' => null,
        'is_sealed' => 0
    ];
    
    // Gérer les types spéciaux
    switch ($objectType) {
        case 'bourse':
            $objectData['gold_coins'] = $goldCoins;
            $objectData['silver_coins'] = $silverCoins;
            $objectData['copper_coins'] = $copperCoins;
            break;
        case 'letter':
            $objectData['letter_content'] = $letterContent;
            $objectData['is_sealed'] = $letterSealed;
            // Ajouter le destinataire dans la description si fourni
            if ($letterRecipient) {
                $objectData['description'] = $description . "\n\nDestinataire: " . $letterRecipient;
            }
            break;
        case 'poison':
            if ($specificItemId) {
                $objectData['poison_id'] = $specificItemId;
            }
            break;
        case 'weapon':
            if ($specificItemId) {
                $objectData['weapon_id'] = $specificItemId;
            }
            break;
        case 'armor':
            if ($specificItemId) {
                $objectData['armor_id'] = $specificItemId;
            }
            break;
        case 'magical_item':
            if ($specificItemId) {
                // Pour les objets magiques, on peut stocker l'ID dans une colonne spécifique
                // ou utiliser le champ description pour stocker l'ID
                $objectData['description'] = $description . "\n\nID Objet Magique: " . $specificItemId;
            }
            break;
    }
    
    // Créer l'objet
    $item = Item::create($objectData);
    
    if ($item) {
        echo json_encode([
            'success' => true,
            'message' => 'Objet ajouté avec succès',
            'object_id' => $item->getId()
        ]);
    } else {
        throw new Exception('Erreur lors de la création de l\'objet');
    }
    
} catch (Exception $e) {
    error_log("Erreur add_object.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
