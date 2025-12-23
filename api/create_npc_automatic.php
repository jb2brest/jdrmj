<?php
// Démarrer la mise en mémoire tampon de sortie pour éviter toute pollution de la réponse JSON
ob_start();

require_once '../classes/init.php';
require_once '../includes/functions.php';
require_once '../npc_functions.php';

// Nettoyer toute sortie précédente (warnings, notices, etc.)
ob_clean();

header('Content-Type: application/json');

try {
    // Vérifier l'authentification
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }

    // Vérifier les droits (Admin ou MJ)
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé');
    }

    $user_id = $_SESSION['user_id'];

    // Récupérer les données POST
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    if (!$data) {
        $data = $_POST;
    }

    // Validation des données requises
    $required_fields = ['race_id', 'class_id', 'level', 'place_id'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Champ manquant: $field");
        }
    }

    $pdo = getPdo();
    
    // S'assurer que $pdo est global pour que npc_functions.php puisse l'utiliser
    global $pdo;

    // Récupérer le monde et le pays associés au lieu
    $place_id = (int)$data['place_id'];
    $stmt = $pdo->prepare("
        SELECT p.country_id, c.world_id 
        FROM places p 
        JOIN countries c ON p.country_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$place_id]);
    $placeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$placeInfo) {
        throw new Exception("Lieu non trouvé");
    }
    
    $country_id = $placeInfo['country_id'];
    $world_id = $placeInfo['world_id'];
    
    // Paramètres pour la création
    $race_id = (int)$data['race_id'];
    $class_id = (int)$data['class_id'];
    $level = (int)$data['level'];
    $custom_name = $data['name'] ?? '';
    $is_visible = isset($data['is_visible']) ? (int)$data['is_visible'] : 1;
    $is_identified = isset($data['is_identified']) ? (int)$data['is_identified'] : 0;
    
    // Utiliser la fonction existante
    $npc = createAutomaticNPC(
        $race_id, 
        $class_id, 
        $level, 
        $user_id, 
        $custom_name, 
        $place_id, 
        $is_visible, 
        $is_identified, 
        $world_id, 
        $country_id
    );
    
    if ($npc) {
        $response = [
            'success' => true, 
            'message' => 'PNJ créé avec succès',
            'npc' => $npc
        ];
    } else {
        throw new Exception("Erreur lors de la création du PNJ (fonction a retourné false)");
    }

} catch (Throwable $e) {
    // Catch Throwable catches both Exception (PHP 5+) and Error (PHP 7+)
    error_log("Erreur API create_npc_automatic: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    $response = ['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()];
}

// S'assurer qu'il n'y a rien dans le buffer
ob_clean();
echo json_encode($response);
exit;
?>
