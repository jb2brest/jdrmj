<?php
// Configuration des limites d'upload
// Ce fichier doit être inclus au début de chaque page qui gère l'upload

// Augmenter les limites d'upload à 10M (seulement si nécessaire)
$currentUploadMax = ini_get('upload_max_filesize');
$targetUploadMax = '10M';

// Convertir en octets pour comparaison
function parseSize($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

$currentBytes = parseSize($currentUploadMax);
$targetBytes = parseSize($targetUploadMax);

// Seulement définir si la valeur actuelle est inférieure à la cible
if ($currentBytes < $targetBytes) {
    ini_set('upload_max_filesize', $targetUploadMax);
    ini_set('post_max_size', '12M');
    ini_set('max_execution_time', '300');
    ini_set('memory_limit', '256M');
    ini_set('max_input_time', '300');
    
    // Vérifier que les paramètres ont été appliqués
    if (ini_get('upload_max_filesize') !== $targetUploadMax) {
        error_log("Warning: upload_max_filesize could not be set to $targetUploadMax. Current value: " . ini_get('upload_max_filesize'));
    }
} else {
    // La valeur actuelle est déjà suffisante ou supérieure
    ini_set('post_max_size', '12M');
    ini_set('max_execution_time', '300');
    ini_set('memory_limit', '256M');
    ini_set('max_input_time', '300');
}
?>
