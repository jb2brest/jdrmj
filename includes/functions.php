<?php
// Vérifier si une session est déjà active avant de la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers de compatibilité
require_once __DIR__ . '/user_compatibility.php';
require_once __DIR__ . '/campaign_compatibility.php';





// Fonction pour nettoyer les données d'entrée
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    // Ne pas encoder en HTML ici, cela sera fait lors de l'affichage
    return $data;
}

// Fonction pour afficher les messages d'erreur/succès
function displayMessage($message, $type = 'info') {
    $alertClass = '';
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        default:
            $alertClass = 'alert-info';
    }
    
    return "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}



// Fonction pour obtenir les options d'outils selon le type de choix


// Fonction pour parser l'équipement de départ d'une classe
// Fonction pour obtenir le contenu d'un sac d'équipement














// Fonction pour parser l'équipement d'historique et extraire les pièces d'or









// Fonction pour obtenir l'équipement équipé d'un personnage


























// Fonctions pour la hiérarchie géographique




// Fonction pour obtenir tous les pays (pour compatibilité)
function getCountries() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM countries ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getCountries: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les régions d'un pays
function getRegionsByCountry($countryId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM regions WHERE country_id = ? ORDER BY name");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getRegionsByCountry: " . $e->getMessage());
        return [];
    }
}















/**
 * Tronque un texte s'il dépasse une certaine longueur
 */
function truncateText($text, $length = 100) {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}
