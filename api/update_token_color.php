<?php
/**
 * API pour mettre à jour la couleur d'un pion
 * Permet au MJ de personnaliser la couleur de bordure des pions dans view_place
 */

require_once '../classes/init.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Vérifier l'authentification
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }

    // Vérifier que l'utilisateur est MJ ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé - Réservé aux MJ');
    }

    // Récupérer les données POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }

    // Validation des données requises
    $required_fields = ['place_id', 'token_type', 'entity_id', 'border_color'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Champ manquant: $field");
        }
    }

    $place_id = (int)$data['place_id'];
    $token_type = $data['token_type'];
    $entity_id = (int)$data['entity_id'];
    $border_color = $data['border_color'];

    // Validation du type de pion
    $valid_types = ['player', 'npc', 'monster', 'object'];
    if (!in_array($token_type, $valid_types)) {
        throw new Exception("Type de pion invalide: $token_type");
    }

    // Validation de la couleur (format hexadécimal)
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $border_color)) {
        throw new Exception("Format de couleur invalide. Utilisez le format #RRGGBB");
    }

    $pdo = getPdo();

    // Vérifier que la pièce existe
    $stmt = $pdo->prepare("SELECT id FROM places WHERE id = ?");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$place) {
        throw new Exception("Pièce non trouvé");
    }

    // Les admins peuvent toujours modifier
    // Pour les MJ, la vérification isDMOrAdmin() ci-dessus suffit

    // Insérer ou mettre à jour la couleur
    $stmt = $pdo->prepare("
        INSERT INTO token_colors (place_id, token_type, entity_id, border_color)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE border_color = ?, updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([
        $place_id,
        $token_type,
        $entity_id,
        $border_color,
        $border_color
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Couleur du pion mise à jour avec succès',
        'data' => [
            'place_id' => $place_id,
            'token_type' => $token_type,
            'entity_id' => $entity_id,
            'border_color' => $border_color
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
