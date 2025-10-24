<?php
header('Content-Type: application/json');

error_log("API upload_photo.php - Début de l'exécution");

require_once '../classes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $npc_id = (int)($_POST['npc_id'] ?? 0);
    $type_cible = $_POST['type_cible'] ?? 'PNJ';
    
    if (!$npc_id) {
        throw new Exception('ID du NPC manquant');
    }
    
    // Valider le type de cible
    if (!in_array($type_cible, ['PNJ', 'monstre', 'PJ'])) {
        throw new Exception('Type de cible invalide');
    }
    
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier sélectionné ou erreur lors de l\'upload');
    }
    
    // Récupérer l'instance NPC
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('NPC non trouvé');
    }
    
    $upload_dir = '../uploads/profiles/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Impossible de créer le répertoire d\'upload');
        }
    }
    
    // Vérifier que le répertoire est accessible en écriture
    if (!is_writable($upload_dir)) {
        throw new Exception('Le répertoire d\'upload n\'est pas accessible en écriture');
    }
    
    // Debug: Vérifier le chemin absolu
    $absolute_path = realpath($upload_dir);
    if (!$absolute_path) {
        throw new Exception('Impossible de résoudre le chemin du répertoire d\'upload');
    }
    
    $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Format de fichier non supporté. Utilisez JPG, PNG ou GIF.');
    }
    
    $file_size = $_FILES['profile_photo']['size'];
    if ($file_size > 10 * 1024 * 1024) { // 10MB max
        throw new Exception('La photo est trop volumineuse (max 10MB).');
    }
    
    $new_filename = 'profile_' . $npc_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
        throw new Exception('Erreur lors de l\'upload de la photo.');
    }
    
    // Supprimer l'ancienne photo si elle existe
    if (!empty($npc->profile_photo) && file_exists($npc->profile_photo)) {
        unlink($npc->profile_photo);
    }
    
    // Construire le chemin web avant de mettre à jour la base de données
    $api_dir = __DIR__; // /path/to/jdrmj/api
    $project_root = dirname($api_dir); // /path/to/jdrmj
    $project_dir = basename($project_root); // jdrmj
    $web_path =  'uploads/profiles/' . $new_filename;
    
    // Mettre à jour la base de données avec le chemin web
    if (!$npc->updateMyProfilePhoto($web_path)) {
        // Supprimer le fichier uploadé en cas d'erreur
        unlink($upload_path);
        throw new Exception('Erreur lors de la mise à jour de la base de données.');
    }
    
    
    error_log("API upload_photo.php - Succès, retour de la réponse JSON");
    echo json_encode([
        'success' => true,
        'message' => "Photo de profil mise à jour avec succès ({$type_cible})",
        'photo_path' => $web_path,
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
