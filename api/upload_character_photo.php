<?php
/**
 * API endpoint pour uploader une photo de profil de personnage
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucun fichier uploadé']);
    exit();
}

$characterId = (int)($_POST['character_id'] ?? 0);

// Validation
if ($characterId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de personnage invalide']);
    exit();
}

// Vérifier les permissions
$character = Character::findById($characterId);
if (!$character) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé']);
    exit();
}

$isOwner = $character->belongsToUser($_SESSION['user_id']);
$isDM = isDM();
$isAdmin = User::isAdmin();

if (!$isOwner && !$isDM && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit();
}

// Validation du fichier
$file = $_FILES['photo'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
    exit();
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)']);
    exit();
}

// Créer le dossier de destination s'il n'existe pas
$uploadDir = dirname(__DIR__) . '/uploads/character_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'character_' . $characterId . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

// Déplacer le fichier
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Chemin relatif pour la base de données
    $relativePath = 'uploads/character_photos/' . $filename;
    
    // Mettre à jour la base de données
    $success = Character::updateProfilePhoto($characterId, $relativePath);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Photo de profil mise à jour avec succès',
            'image_url' => $relativePath
        ]);
    } else {
        // Supprimer le fichier si la mise à jour en base a échoué
        unlink($uploadPath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour en base de données']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
}
?>
