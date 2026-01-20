<?php
/**
 * API Endpoint: Uploader un plan de pièce
 */

// Configurer error_log pour écrire dans un fichier accessible
$customLogFile = dirname(__DIR__) . '/logs/php_errors.log';
$logDir = dirname($customLogFile);

// S'assurer que le répertoire existe
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

// Configurer error_log si le répertoire est accessible en écriture
if (is_writable($logDir)) {
    ini_set('error_log', $customLogFile);
}

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/classes/Room.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $place_id = (int)($_POST['place_id'] ?? 0);
    
    if (!$place_id) {
        throw new Exception('ID de la pièce manquant');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Récupérer la pièce
    $lieu = Room::findById($place_id);
    if (!$lieu) {
        throw new Exception('Pièce non trouvé');
    }
    
    // Vérifier les permissions
    $place = $lieu->toArray();
    $campaigns = $lieu->getCampaigns();
    $dm_id = null;
    if (!empty($campaigns)) {
        $campaign = $campaigns[0];
        $dm_id = $campaign['dm_id'];
    }
    
    $user_id = $_SESSION['user_id'];
    $isOwnerDM = User::isDMOrAdmin() && ($dm_id === null || $user_id === (int)$dm_id);
    
    if (!User::isAdmin() && !$isOwnerDM) {
        throw new Exception('Vous n\'avez pas les droits pour modifier cette pièce');
    }
    
    if (!isset($_FILES['plan_file']) || $_FILES['plan_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Aucun fichier sélectionné ou erreur lors de l\'upload';
        if (isset($_FILES['plan_file'])) {
            $errorMsg .= ' (code erreur: ' . $_FILES['plan_file']['error'] . ')';
        }
        throw new Exception($errorMsg);
    }
    
    $upload_dir = dirname(__DIR__) . '/uploads/plans/' . date('Y/m') . '/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Impossible de créer le répertoire d\'upload');
        }
    }
    
    // Vérifier que le répertoire est accessible en écriture
    if (!is_writable($upload_dir)) {
        throw new Exception('Le répertoire d\'upload n\'est pas accessible en écriture');
    }
    
    $file_extension = strtolower(pathinfo($_FILES['plan_file']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Format de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.');
    }
    
    $file_size = $_FILES['plan_file']['size'];
    if ($file_size > 10 * 1024 * 1024) { // 10MB max
        throw new Exception('Le plan est trop volumineux (max 10MB).');
    }
    
    // Vérifier le type MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['plan_file']['tmp_name']);
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($mime, $allowed_mimes)) {
        throw new Exception('Format de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.');
    }
    
    $new_filename = 'plan_' . $place_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($_FILES['plan_file']['tmp_name'], $upload_path)) {
        $error = error_get_last();
        $errorMsg = 'Erreur lors de l\'upload du plan. ' . ($error ? $error['message'] : '');
        throw new Exception($errorMsg);
    }
    
    // Supprimer l'ancien plan si il existe
    if (!empty($lieu->map_url) && file_exists(dirname(__DIR__) . '/' . $lieu->map_url)) {
        @unlink(dirname(__DIR__) . '/' . $lieu->map_url);
    }
    
    // Construire le chemin web
    $web_path = 'uploads/plans/' . date('Y/m') . '/' . $new_filename;
    
    // Récupérer les notes
    $notes = trim($_POST['notes'] ?? '');
    
    // Mettre à jour la base de données
    $result = $lieu->updateMapUrl($web_path, $notes);
    if (!$result['success']) {
        // Supprimer le fichier uploadé en cas d'erreur
        @unlink($upload_path);
        throw new Exception($result['message']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Plan de la pièce mis à jour avec succès',
        'map_url' => $web_path
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

