<?php
/**
 * API Endpoint: Sauvegarder un lancer de dés
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Aucune donnée JSON reçue');
    }
    
    if (!isset($input['campaign_id']) || !isset($input['dice_sides'])) {
        throw new Exception('Données manquantes');
    }
    
    $campaignId = (int)$input['campaign_id'];
    $diceSides = (int)$input['dice_sides'];
    $quantity = (int)($input['quantity'] ?? 1);
    $results = $input['results'] ?? [];
    $total = (int)($input['total'] ?? 0);
    $maxResult = (int)($input['max_result'] ?? 0);
    $minResult = (int)($input['min_result'] ?? 0);
    $isHidden = (bool)($input['is_hidden'] ?? false);
    $userId = $_SESSION['user_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Déterminer le type de dé
    $diceType = "D{$diceSides}";
    
    // Récupérer le nom d'utilisateur
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user ? $user['username'] : 'Unknown';
    
    // Sauvegarder en base de données
    $stmt = $pdo->prepare("
        INSERT INTO dice_rolls (campaign_id, user_id, username, dice_type, dice_sides, quantity, results, total, max_result, min_result, is_hidden, rolled_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $success = $stmt->execute([
        $campaignId,
        $userId,
        $username,
        $diceType,
        $diceSides,
        $quantity,
        json_encode($results),
        $total,
        $maxResult,
        $minResult,
        $isHidden ? 1 : 0
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Jet de dés sauvegardé avec succès',
            'roll_id' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Erreur lors de la sauvegarde');
    }
    
} catch (Exception $e) {
    error_log("Erreur save_dice_roll.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
