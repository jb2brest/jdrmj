<?php
header('Content-Type: application/json');

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès non autorisé']));
}

// Récupérer le paramètre de recherche
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

// Lire le fichier CSV des poisons
$poisonsFile = 'aidednddata/poisons.csv';
if (!file_exists($poisonsFile)) {
    echo json_encode([]);
    exit();
}

$poisons = [];
$handle = fopen($poisonsFile, 'r');
if ($handle !== false) {
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 6) {
            $id = $data[0];
            $nom = $data[1];
            $cle = $data[2];
            $description = $data[3];
            $type = $data[4];
            $source = $data[5];
            
            // Recherche dans le nom, la clé et la description
            if (stripos($nom, $query) !== false || 
                stripos($cle, $query) !== false || 
                stripos($description, $query) !== false ||
                stripos($type, $query) !== false) {
                
                $poisons[] = [
                    'id' => $id,
                    'nom' => $nom,
                    'cle' => $cle,
                    'description' => $description,
                    'type' => $type,
                    'source' => $source
                ];
            }
        }
    }
    fclose($handle);
}

// Limiter les résultats à 50 pour éviter de surcharger l'interface
$poisons = array_slice($poisons, 0, 50);

echo json_encode($poisons);
?>
