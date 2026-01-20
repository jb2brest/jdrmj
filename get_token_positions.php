<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['place_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit();
}

$place_id = (int)$input['place_id'];
$last_update = $input['last_update'] ?? null;

try {
    // Vérifier que l'utilisateur a accès à cette pièce
    $stmt = $pdo->prepare("
        SELECT p.id, pc.campaign_id 
        FROM places p 
        JOIN place_campaigns pc ON p.id = pc.place_id
        WHERE p.id = ?
    ");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();
    
    if (!$place) {
        http_response_code(404);
        echo json_encode(['error' => 'Pièce non trouvé']);
        exit();
    }
    
    // Vérifier que l'utilisateur est membre de la campagne ou est le DM
    $stmt = $pdo->prepare("
        SELECT role FROM campaign_members 
        WHERE campaign_id = ? AND user_id = ?
    ");
    $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
    $membership = $stmt->fetch();
    
    // Si pas membre, vérifier si c'est le DM de la campagne
    if (!$membership) {
        $stmt = $pdo->prepare("
            SELECT dm_id FROM campaigns 
            WHERE id = ? AND dm_id = ?
        ");
        $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
        $is_dm = $stmt->fetch();
        
        if (!$is_dm) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé - Vous devez être membre de la campagne ou le DM']);
            exit();
        }
    }
    
    // Récupérer les positions des pions
    $stmt = $pdo->prepare("
        SELECT token_type, entity_id, position_x, position_y, is_on_map, updated_at
        FROM place_tokens 
        WHERE place_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$place_id]);
    
    $positions = [];
    $latest_timestamp = null;
    
    while ($row = $stmt->fetch()) {
        $tokenKey = $row['token_type'] . '_' . $row['entity_id'];
        $positions[$tokenKey] = [
            'x' => (int)$row['position_x'],
            'y' => (int)$row['position_y'],
            'is_on_map' => (bool)$row['is_on_map']
        ];
        
        // Garder la timestamp la plus récente
        if ($latest_timestamp === null || $row['updated_at'] > $latest_timestamp) {
            $latest_timestamp = $row['updated_at'];
        }
    }
    
    // Récupérer les informations de visibilité et identification des PNJ
    $stmt = $pdo->prepare("
        SELECT sn.id, sn.name, sn.is_visible, sn.is_identified,
               c.profile_photo AS character_profile_photo, 
               sn.profile_photo,
               n.profile_photo AS npc_profile_photo
        FROM place_npcs sn 
        LEFT JOIN characters c ON sn.npc_character_id = c.id
        LEFT JOIN npcs n ON sn.npc_character_id = n.id
        WHERE sn.place_id = ? AND sn.monster_id IS NULL
        ORDER BY sn.id ASC
    ");
    $stmt->execute([$place_id]);
    
    $npcs = [];
    while ($row = $stmt->fetch()) {
        // Priorité : photo du NPC global (npcs) > photo perso joueur (characters) > photo locale (place_npcs)
        $photo = $row['npc_profile_photo'] ?: ($row['character_profile_photo'] ?: $row['profile_photo']);
        
        $npcs['npc_' . $row['id']] = [
            'name' => $row['name'],
            'is_visible' => (bool)$row['is_visible'],
            'is_identified' => (bool)$row['is_identified'],
            'character_profile_photo' => $photo,
            'profile_photo' => $photo
        ];
    }
    
    // Récupérer les informations de visibilité et identification des monstres
    $stmt = $pdo->prepare("
        SELECT sn.id, sn.name, sn.is_visible, sn.is_identified, sn.monster_id
        FROM place_npcs sn 
        WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL
        ORDER BY sn.id ASC
    ");
    $stmt->execute([$place_id]);
    
    $monsters = [];
    while ($row = $stmt->fetch()) {
        $monsters['monster_' . $row['id']] = [
            'name' => $row['name'],
            'is_visible' => (bool)$row['is_visible'],
            'is_identified' => (bool)$row['is_identified'],
            'monster_id' => $row['monster_id']
        ];
    }
    
    // Récupérer les positions des objets depuis place_tokens (système unifié)
    $stmt = $pdo->prepare("
        SELECT pt.entity_id, pt.position_x, pt.position_y, pt.is_on_map, pt.updated_at,
               i.id, i.display_name, i.object_type, i.is_visible, i.is_identified
        FROM place_tokens pt
        INNER JOIN items i ON pt.entity_id = i.id
        WHERE pt.place_id = ? AND pt.token_type = 'object'
          AND (i.owner_type = 'place' OR i.owner_type IS NULL)
        ORDER BY pt.updated_at DESC
    ");
    $stmt->execute([$place_id]);
    
    $objects_from_tokens = [];
    while ($row = $stmt->fetch()) {
        $tokenKey = 'object_' . $row['entity_id'];
        
        // Les positions sont déjà dans $positions depuis la requête principale place_tokens
        // Mais on les met à jour ici pour s'assurer qu'elles sont bien présentes
        if (!isset($positions[$tokenKey])) {
            $positions[$tokenKey] = [
                'x' => (int)$row['position_x'],
                'y' => (int)$row['position_y'],
                'is_on_map' => (bool)$row['is_on_map']
            ];
        }
        
        // Ajouter les informations de l'objet
        $objects_from_tokens[$tokenKey] = [
            'name' => $row['display_name'],
            'object_type' => $row['object_type'],
            'is_visible' => (bool)$row['is_visible'],
            'is_identified' => (bool)$row['is_identified']
        ];
        
        // Garder la timestamp la plus récente
        if ($latest_timestamp === null || $row['updated_at'] > $latest_timestamp) {
            $latest_timestamp = $row['updated_at'];
        }
    }
    
    // Récupérer aussi les objets visibles qui n'ont pas encore d'entrée dans place_tokens
    $stmt = $pdo->prepare("
        SELECT id, display_name, object_type, is_visible, is_identified, position_x, position_y, is_on_map, updated_at
        FROM items 
        WHERE place_id = ? AND (owner_type = 'place' OR owner_type IS NULL) AND is_visible = 1
          AND id NOT IN (
              SELECT entity_id FROM place_tokens 
              WHERE place_id = ? AND token_type = 'object'
          )
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$place_id, $place_id]);
    
    $objects = $objects_from_tokens;
    while ($row = $stmt->fetch()) {
        $tokenKey = 'object_' . $row['id'];
        
        // Ne pas écraser si l'objet a déjà une position dans place_tokens
        if (!isset($positions[$tokenKey])) {
            $positions[$tokenKey] = [
                'x' => (int)$row['position_x'],
                'y' => (int)$row['position_y'],
                'is_on_map' => (bool)$row['is_on_map']
            ];
        }
        
        // Ajouter les informations de l'objet si pas déjà présent
        if (!isset($objects[$tokenKey])) {
            $objects[$tokenKey] = [
                'name' => $row['display_name'],
                'object_type' => $row['object_type'],
                'is_visible' => (bool)$row['is_visible'],
                'is_identified' => (bool)$row['is_identified']
            ];
        }
        
        // Garder la timestamp la plus récente
        if ($latest_timestamp === null || $row['updated_at'] > $latest_timestamp) {
            $latest_timestamp = $row['updated_at'];
        }
    }
    
    // Récupérer les couleurs personnalisées des pions
    $stmt = $pdo->prepare("
        SELECT token_type, entity_id, border_color
        FROM token_colors
        WHERE place_id = ?
    ");
    $stmt->execute([$place_id]);
    
    $colors = [];
    while ($row = $stmt->fetch()) {
        $tokenKey = $row['token_type'] . '_' . $row['entity_id'];
        $colors[$tokenKey] = $row['border_color'];
    }
    
    // Pour les PNJ et monstres, on retourne toujours les données car ils n'ont pas de updated_at
    // Le JavaScript se chargera de détecter les changements en comparant les données
    
    echo json_encode([
        'success' => true,
        'positions' => $positions,
        'colors' => $colors,
        'npcs' => $npcs,
        'monsters' => $monsters,
        'objects' => $objects,
        'timestamp' => $latest_timestamp
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_token_positions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>