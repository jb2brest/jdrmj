<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isLoggedIn() || !isDMOrAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier uploadé ou erreur d\'upload']);
    exit;
}

$file = $_FILES['image'];

// Vérifier la taille du fichier (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier trop volumineux (max 5MB)']);
    exit;
}

// Vérifier le type de fichier
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP']);
    exit;
}

// Créer le dossier d'upload s'il n'existe pas
$uploadDir = 'uploads/worlds/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'world_' . time() . '_' . uniqid() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Déplacer le fichier uploadé
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de l\'enregistrement du fichier']);
    exit;
}

// Retourner le chemin du fichier
echo json_encode([
    'success' => true,
    'file_path' => $filePath,
    'file_name' => $fileName
]);
?>
