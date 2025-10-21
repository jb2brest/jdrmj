<?php
// Configuration des limites d'upload
// Ce fichier doit être inclus au début de chaque page qui gère l'upload

// Augmenter les limites d'upload à 10M
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');
ini_set('max_input_time', '300');

// Vérifier que les paramètres ont été appliqués
if (ini_get('upload_max_filesize') !== '10M') {
    error_log("Warning: upload_max_filesize could not be set to 10M. Current value: " . ini_get('upload_max_filesize'));
}
?>
