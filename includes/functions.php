<?php
session_start();

// Fonction pour calculer le modificateur d'une caractéristique
function getAbilityModifier($score) {
    return floor(($score - 10) / 2);
}

// Fonction pour calculer le bonus de maîtrise selon le niveau
function getProficiencyBonus($level) {
    return floor(($level - 1) / 4) + 2;
}

// Fonction pour calculer les points de vie maximum selon la classe
function calculateMaxHP($level, $hitDie, $constitutionModifier) {
    $hp = $hitDie + $constitutionModifier; // Premier niveau
    for ($i = 2; $i <= $level; $i++) {
        $hp += rand(1, $hitDie) + $constitutionModifier;
    }
    return max(1, $hp); // Minimum 1 PV
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour vérifier le rôle de l'utilisateur
function getUserRole() {
    return $_SESSION['role'] ?? 'player';
}

// Fonction pour vérifier si l'utilisateur est MJ
function isDM() {
    return getUserRole() === 'dm';
}

// Fonction pour vérifier si l'utilisateur est joueur
function isPlayer() {
    return getUserRole() === 'player';
}

// Fonction pour rediriger si l'utilisateur n'est pas MJ
function requireDM() {
    requireLogin();
    if (!isDM()) {
        header('Location: profile.php?error=dm_required');
        exit();
    }
}

// Fonction pour obtenir les informations complètes de l'utilisateur
function getUserInfo($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir le niveau d'expérience en français
function getExperienceLevelLabel($level) {
    switch ($level) {
        case 'debutant':
            return 'Débutant';
        case 'intermediaire':
            return 'Intermédiaire';
        case 'expert':
            return 'Expert';
        default:
            return 'Débutant';
    }
}

// Fonction pour obtenir le rôle en français
function getRoleLabel($role) {
    switch ($role) {
        case 'player':
            return 'Joueur';
        case 'dm':
            return 'Maître du Jeu';
        default:
            return 'Joueur';
    }
}

// Fonction pour nettoyer les données d'entrée
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
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

// Fonction pour obtenir les compétences D&D
function getSkills() {
    return [
        'Acrobaties' => 'Dextérité',
        'Arcanes' => 'Intelligence',
        'Athlétisme' => 'Force',
        'Discrétion' => 'Dextérité',
        'Dressage' => 'Sagesse',
        'Escamotage' => 'Dextérité',
        'Histoire' => 'Intelligence',
        'Intimidation' => 'Charisme',
        'Investigation' => 'Intelligence',
        'Médecine' => 'Sagesse',
        'Nature' => 'Intelligence',
        'Perception' => 'Sagesse',
        'Perspicacité' => 'Sagesse',
        'Persuasion' => 'Charisme',
        'Religion' => 'Intelligence',
        'Représentation' => 'Charisme',
        'Survie' => 'Sagesse',
        'Tromperie' => 'Charisme'
    ];
}

// Fonction pour obtenir les jets de sauvegarde
function getSavingThrows() {
    return [
        'Force' => 'strength',
        'Dextérité' => 'dexterity',
        'Constitution' => 'constitution',
        'Intelligence' => 'intelligence',
        'Sagesse' => 'wisdom',
        'Charisme' => 'charisma'
    ];
}

// Fonction pour calculer la classe d'armure
function calculateArmorClass($dexterityModifier, $armor = null) {
    $baseAC = 10 + $dexterityModifier;
    
    if ($armor) {
        // Logique pour différents types d'armure
        switch ($armor) {
            case 'armure de cuir':
                $baseAC = 11 + $dexterityModifier;
                break;
            case 'armure de cuir clouté':
                $baseAC = 12 + $dexterityModifier;
                break;
            case 'cotte de mailles':
                $baseAC = 16;
                $baseAC = min($baseAC, 16); // Max 16 avec Dextérité
                break;
            case 'armure de plates':
                $baseAC = 18;
                break;
        }
    }
    
    return $baseAC;
}
?>

