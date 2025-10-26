<?php
require_once '../config/database.php';
require_once '../classes/NPC.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_POST['npc_id']) || !isset($_FILES['profile_photo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$npcId = (int)$_POST['npc_id'];
$file = $_FILES['profile_photo'];

// Vérifier les erreurs d'upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
    exit;
}

// Vérifier la taille du fichier (10MB max)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux. Taille maximale : 10MB']);
    exit;
}

// Vérifier le type de fichier
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format de fichier non supporté. Utilisez JPG, PNG ou GIF']);
    exit;
}

try {
    // Vérifier que le NPC existe
    $npc = NPC::findById($npcId);
    if (!$npc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'NPC non trouvé']);
        exit;
    }

    // Créer le répertoire d'upload s'il n'existe pas
    $uploadDir = dirname(__DIR__) . '/uploads/npcs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'npc_' . $npcId . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Mettre à jour la base de données
        if (NPC::updateProfilePhoto($npcId, 'uploads/npcs/' . $filename)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Photo de profil mise à jour avec succès',
                'image_url' => 'uploads/npcs/' . $filename
            ]);
        } else {
            // Supprimer le fichier si la mise à jour en base échoue
            unlink($uploadPath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour en base de données']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
