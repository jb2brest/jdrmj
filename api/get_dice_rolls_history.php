<?php
/**
 * API pour récupérer l'historique des jets de dés
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/DiceRoll.php';
require_once __DIR__ . '/../classes/Campaign.php';
require_once __DIR__ . '/../classes/User.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

// Vérifier que la requête est en GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les paramètres
    $campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
    $show_hidden = isset($_GET['show_hidden']) ? filter_var($_GET['show_hidden'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (!$campaign_id) {
        echo json_encode(['success' => false, 'error' => 'ID de campagne requis']);
        exit;
    }
    
    // Vérifier que l'utilisateur a accès à cette campagne
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
        exit;
    }
    
    try {
        if (!User::isAdmin()) {
            $campaign = Campaign::findById($campaign_id);
            if (!$campaign || !$campaign->isMember($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Accès non autorisé à cette campagne']);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur de vérification des permissions: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur de vérification des permissions']);
        exit;
    }
    
    // Récupérer l'historique des jets de dés
    $rolls = DiceRoll::getByCampaignId($campaign_id, $show_hidden);
    
    echo json_encode([
        'success' => true,
        'rolls' => $rolls
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_dice_rolls_history.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne du serveur'
    ]);
}
?>
