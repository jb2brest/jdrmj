<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'includes/upload_config.php';
$page_title = "Détails de la Thématique";
$current_page = "view_thematique";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: thematiques.php');
    exit();
}

$thematique_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer la thématique
$thematique = Thematique::findById($thematique_id);

if (!$thematique || $thematique->created_by != $user_id) {
    header('Location: thematiques.php?error=thematique_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_information':
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $niveau_confidentialite = $_POST['niveau_confidentialite'] ?? 'connu';
            $statut = $_POST['statut'] ?? 'a_verifier';
            
            if (empty($titre)) {
                $error_message = "Le titre de l'information est obligatoire.";
            } else {
                $information = new Information([
                    'titre' => $titre,
                    'description' => $description,
                    'niveau_confidentialite' => $niveau_confidentialite,
                    'statut' => $statut,
                    'created_by' => $user_id
                ]);
                
                if ($information->save()) {
                    // Gérer l'upload de l'image si fourni
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = $information->uploadImage($_FILES['image']);
                        if ($uploadResult['success']) {
                            $information->save(); // Sauvegarder le chemin de l'image
                        }
                    }
                    
                    // Associer automatiquement la nouvelle information à la thématique
                    $thematique->addInformation($information->id);
                    
                    // Gérer les accès
                    $information->clearAccesses();
                    
                    // Accès joueurs
                    if (isset($_POST['player_access']) && is_array($_POST['player_access'])) {
                        foreach ($_POST['player_access'] as $player_id) {
                            $information->addPlayerAccess($player_id);
                        }
                    }
                    
                    // Accès PNJ
                    if (isset($_POST['npc_access']) && is_array($_POST['npc_access'])) {
                        foreach ($_POST['npc_access'] as $npc_id) {
                            $information->addNpcAccess($npc_id);
                        }
                    }
                    
                    // Accès Monstres
                    if (isset($_POST['monster_access']) && is_array($_POST['monster_access'])) {
                        foreach ($_POST['monster_access'] as $monster_id) {
                            $information->addMonsterAccess($monster_id);
                        }
                    }
                    
                    // Accès groupes (niveaux individuels)
                    if (isset($_POST['group_access']) && is_array($_POST['group_access'])) {
                        // Organiser par groupe
                        $group_accesses = [];
                        foreach ($_POST['group_access'] as $group_level_data) {
                            $parts = explode('_', $group_level_data);
                            $groupe_id = $parts[0];
                            $niveau = isset($parts[1]) ? (int)$parts[1] : null;
                            if ($niveau !== null) {
                                if (!isset($group_accesses[$groupe_id])) {
                                    $group_accesses[$groupe_id] = [];
                                }
                                $group_accesses[$groupe_id][] = $niveau;
                            }
                        }
                        // Ajouter les accès pour chaque groupe
                        foreach ($group_accesses as $groupe_id => $niveaux) {
                            $information->removeGroupAccess($groupe_id); // Supprimer les anciens accès
                            $information->addGroupAccessLevels($groupe_id, $niveaux);
                        }
                    }
                    
                    $success_message = "Information créée et ajoutée à la thématique avec succès !";
                    header('Location: view_thematique.php?id=' . $thematique_id);
                    exit();
                } else {
                    $error_message = "Erreur lors de la création de l'information.";
                }
            }
            break;
            
        case 'update_information':
            $information_id = (int)($_POST['information_id'] ?? 0);
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $niveau_confidentialite = $_POST['niveau_confidentialite'] ?? 'connu';
            $statut = $_POST['statut'] ?? 'a_verifier';
            
            if ($information_id <= 0 || empty($titre)) {
                $error_message = "Données invalides.";
            } else {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    $information->titre = $titre;
                    $information->description = $description;
                    $information->niveau_confidentialite = $niveau_confidentialite;
                    $information->statut = $statut;
                    
                    // Gérer l'upload de l'image si fourni
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = $information->uploadImage($_FILES['image']);
                        if ($uploadResult['success']) {
                            // Le chemin est déjà mis à jour dans uploadImage
                        }
                    }
                    
                    if ($information->save()) {
                        // Gérer les accès
                        $information->clearAccesses();
                        
                        // Accès joueurs
                        if (isset($_POST['player_access']) && is_array($_POST['player_access'])) {
                            foreach ($_POST['player_access'] as $player_id) {
                                $information->addPlayerAccess($player_id);
                            }
                        }
                        
                        // Accès PNJ
                        if (isset($_POST['npc_access']) && is_array($_POST['npc_access'])) {
                            foreach ($_POST['npc_access'] as $npc_id) {
                                $information->addNpcAccess($npc_id);
                            }
                        }
                        
                        // Accès Monstres
                        if (isset($_POST['monster_access']) && is_array($_POST['monster_access'])) {
                            foreach ($_POST['monster_access'] as $monster_id) {
                                $information->addMonsterAccess($monster_id);
                            }
                        }
                        
                        // Accès groupes (niveaux individuels)
                        if (isset($_POST['group_access']) && is_array($_POST['group_access'])) {
                            // Organiser par groupe
                            $group_accesses = [];
                            foreach ($_POST['group_access'] as $group_level_data) {
                                $parts = explode('_', $group_level_data);
                                $groupe_id = $parts[0];
                                $niveau = isset($parts[1]) ? (int)$parts[1] : null;
                                if ($niveau !== null) {
                                    if (!isset($group_accesses[$groupe_id])) {
                                        $group_accesses[$groupe_id] = [];
                                    }
                                    $group_accesses[$groupe_id][] = $niveau;
                                }
                            }
                            // Ajouter les accès pour chaque groupe
                            foreach ($group_accesses as $groupe_id => $niveaux) {
                                $information->removeGroupAccess($groupe_id); // Supprimer les anciens accès
                                $information->addGroupAccessLevels($groupe_id, $niveaux);
                            }
                        }
                        
                        $success_message = "Information modifiée avec succès !";
                        header('Location: view_thematique.php?id=' . $thematique_id);
                        exit();
                    } else {
                        $error_message = "Erreur lors de la modification de l'information.";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
            
        case 'add_information_to_thematique':
            $information_id = (int)($_POST['information_id'] ?? 0);
            if ($information_id > 0) {
                if ($thematique->addInformation($information_id)) {
                    $success_message = "Information ajoutée à la thématique avec succès !";
                } else {
                    $error_message = "Erreur lors de l'ajout de l'information.";
                }
            }
            break;
            
        case 'remove_information_from_thematique':
            $information_id = (int)($_POST['information_id'] ?? 0);
            if ($information_id > 0) {
                if ($thematique->removeInformation($information_id)) {
                    $success_message = "Information retirée de la thématique avec succès !";
                } else {
                    $error_message = "Erreur lors de la suppression de l'information.";
                }
            }
            break;
            
        case 'move_information_up':
            $information_id = (int)($_POST['information_id'] ?? 0);
            if ($information_id > 0) {
                if ($thematique->moveInformationUp($information_id)) {
                    $success_message = "Ordre mis à jour avec succès !";
                } else {
                    $error_message = "Impossible de déplacer cette information.";
                }
            }
            break;
            
        case 'move_information_down':
            $information_id = (int)($_POST['information_id'] ?? 0);
            if ($information_id > 0) {
                if ($thematique->moveInformationDown($information_id)) {
                    $success_message = "Ordre mis à jour avec succès !";
                } else {
                    $error_message = "Impossible de déplacer cette information.";
                }
            }
            break;
            
        case 'delete_information':
            $information_id = (int)($_POST['information_id'] ?? 0);
            if ($information_id > 0) {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    if ($information->delete()) {
                        $success_message = "Information supprimée avec succès !";
                        header('Location: view_thematique.php?id=' . $thematique_id);
                        exit();
                    } else {
                        $error_message = "Erreur lors de la suppression de l'information.";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
            
        case 'add_sub_information':
            $information_id = (int)($_POST['information_id'] ?? 0);
            $child_information_id = (int)($_POST['child_information_id'] ?? 0);
            
            // Si un child_information_id est fourni, on ajoute une information existante
            if ($information_id > 0 && $child_information_id > 0) {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    if ($information->canBeSubInformationOf($child_information_id)) {
                        if ($information->addSubInformation($child_information_id)) {
                            $success_message = "Sous-information ajoutée avec succès !";
                        } else {
                            $error_message = "Erreur lors de l'ajout de la sous-information.";
                        }
                    } else {
                        $error_message = "Impossible d'ajouter cette sous-information (risque de boucle).";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            // Sinon, on crée une nouvelle information et on l'ajoute comme sous-information
            elseif ($information_id > 0 && isset($_POST['create_new_sub_information']) && $_POST['create_new_sub_information'] === '1') {
                $titre = trim($_POST['titre'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $niveau_confidentialite = $_POST['niveau_confidentialite'] ?? 'connu';
                $statut = $_POST['statut'] ?? 'a_verifier';
                
                if (empty($titre)) {
                    $error_message = "Le titre de l'information est obligatoire.";
                } else {
                    $parent_information = Information::findById($information_id);
                    if ($parent_information && $parent_information->created_by == $user_id) {
                        $new_information = new Information([
                            'titre' => $titre,
                            'description' => $description,
                            'niveau_confidentialite' => $niveau_confidentialite,
                            'statut' => $statut,
                            'created_by' => $user_id
                        ]);
                        
                        if ($new_information->save()) {
                            // Gérer l'upload de l'image si fourni
                            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                                $uploadResult = $new_information->uploadImage($_FILES['image']);
                                if ($uploadResult['success']) {
                                    $new_information->save(); // Sauvegarder le chemin de l'image
                                }
                            }
                            
                            // Vérifier qu'on peut l'ajouter comme sous-information
                            if ($parent_information->canBeSubInformationOf($new_information->id)) {
                                // Ajouter comme sous-information
                                $parent_information->addSubInformation($new_information->id);
                                
                                // Gérer les accès si fournis
                                $new_information->clearAccesses();
                                
                                // Accès joueurs
                                if (isset($_POST['player_access']) && is_array($_POST['player_access'])) {
                                    foreach ($_POST['player_access'] as $player_id) {
                                        $new_information->addPlayerAccess($player_id);
                                    }
                                }
                                
                                // Accès PNJ
                                if (isset($_POST['npc_access']) && is_array($_POST['npc_access'])) {
                                    foreach ($_POST['npc_access'] as $npc_id) {
                                        $new_information->addNpcAccess($npc_id);
                                    }
                                }
                                
                                // Accès Monstres
                                if (isset($_POST['monster_access']) && is_array($_POST['monster_access'])) {
                                    foreach ($_POST['monster_access'] as $monster_id) {
                                        $new_information->addMonsterAccess($monster_id);
                                    }
                                }
                                
                                // Accès groupes (niveaux individuels)
                                if (isset($_POST['group_access']) && is_array($_POST['group_access'])) {
                                    // Organiser par groupe
                                    $group_accesses = [];
                                    foreach ($_POST['group_access'] as $group_level_data) {
                                        $parts = explode('_', $group_level_data);
                                        $groupe_id = $parts[0];
                                        $niveau = isset($parts[1]) ? (int)$parts[1] : null;
                                        if ($niveau !== null) {
                                            if (!isset($group_accesses[$groupe_id])) {
                                                $group_accesses[$groupe_id] = [];
                                            }
                                            $group_accesses[$groupe_id][] = $niveau;
                                        }
                                    }
                                    // Ajouter les accès pour chaque groupe
                                    foreach ($group_accesses as $groupe_id => $niveaux) {
                                        $new_information->addGroupAccessLevels($groupe_id, $niveaux);
                                    }
                                }
                                
                                $success_message = "Sous-information créée et ajoutée avec succès !";
                                header('Location: view_thematique.php?id=' . $thematique_id);
                                exit();
                            } else {
                                $error_message = "Impossible d'ajouter cette sous-information (risque de boucle).";
                            }
                        } else {
                            $error_message = "Erreur lors de la création de l'information.";
                        }
                    } else {
                        $error_message = "Information parente non trouvée ou vous n'avez pas les permissions.";
                    }
                }
            }
            break;
            
        case 'remove_sub_information':
            $information_id = (int)($_POST['information_id'] ?? 0);
            $child_information_id = (int)($_POST['child_information_id'] ?? 0);
            if ($information_id > 0 && $child_information_id > 0) {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    if ($information->removeSubInformation($child_information_id)) {
                        $success_message = "Sous-information retirée avec succès !";
                    } else {
                        $error_message = "Erreur lors de la suppression de la sous-information.";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
            
        case 'move_sub_information_up':
            $information_id = (int)($_POST['information_id'] ?? 0);
            $child_information_id = (int)($_POST['child_information_id'] ?? 0);
            if ($information_id > 0 && $child_information_id > 0) {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    if ($information->moveSubInformationUp($child_information_id)) {
                        $success_message = "Ordre mis à jour avec succès !";
                    } else {
                        $error_message = "Impossible de déplacer cette sous-information.";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
            
        case 'move_sub_information_down':
            $information_id = (int)($_POST['information_id'] ?? 0);
            $child_information_id = (int)($_POST['child_information_id'] ?? 0);
            if ($information_id > 0 && $child_information_id > 0) {
                $information = Information::findById($information_id);
                if ($information && $information->created_by == $user_id) {
                    if ($information->moveSubInformationDown($child_information_id)) {
                        $success_message = "Ordre mis à jour avec succès !";
                    } else {
                        $error_message = "Impossible de déplacer cette sous-information.";
                    }
                } else {
                    $error_message = "Information non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
    }
}

// Récupérer toutes les informations pour le dropdown
$all_informations = Information::getByUser($user_id);

// Récupérer tous les personnages joueurs, groupes, PNJ et monstres pour les modals
$characters = Character::getCharactersByUser($user_id);

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("
        SELECT g.*, p.title as headquarters_name
        FROM groupes g
        LEFT JOIN places p ON g.headquarters_place_id = p.id
        WHERE g.created_by = ?
        ORDER BY g.name ASC
    ");
    $stmt->execute([$user_id]);
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $groupes = [];
    error_log("Erreur lors de la récupération des groupes: " . $e->getMessage());
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, p.title as place_name, 'npc' as type
        FROM place_npcs pn
        JOIN places p ON pn.place_id = p.id
        JOIN countries c ON p.country_id = c.id
        JOIN worlds w ON c.world_id = w.id
        WHERE w.created_by = ? AND pn.monster_id IS NULL
        ORDER BY pn.name ASC
    ");
    $stmt->execute([$user_id]);
    $npcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $npcs = [];
    error_log("Erreur lors de la récupération des PNJ: " . $e->getMessage());
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("
        SELECT pn.id, COALESCE(dm.name, pn.name) as name, p.title as place_name, 'monster' as type
        FROM place_npcs pn
        JOIN places p ON pn.place_id = p.id
        JOIN countries c ON p.country_id = c.id
        JOIN worlds w ON c.world_id = w.id
        LEFT JOIN dnd_monsters dm ON pn.monster_id = dm.id
        WHERE w.created_by = ? AND pn.monster_id IS NOT NULL
        ORDER BY COALESCE(dm.name, pn.name) ASC
    ");
    $stmt->execute([$user_id]);
    $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $monsters = [];
    error_log("Erreur lors de la récupération des monstres: " . $e->getMessage());
}

// Information en cours d'édition
$editing_information = null;
if (isset($_GET['edit_information'])) {
    $editing_information = Information::findById((int)$_GET['edit_information']);
    if (!$editing_information || $editing_information->created_by != $user_id) {
        $editing_information = null;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thematique->nom); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .information-item {
            border-left: 4px solid #8B4513;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .information-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .badge-confidentialite {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
        }
        .badge-confidentialite.archi-connu { background-color: #28a745; }
        .badge-confidentialite.connu { background-color: #17a2b8; }
        .badge-confidentialite.connu-du-milieu { background-color: #ffc107; color: #212529; }
        .badge-confidentialite.confidentiel { background-color: #fd7e14; }
        .badge-confidentialite.secret { background-color: #dc3545; }
        .badge-statut {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
        }
        .badge-statut.vraie { background-color: #28a745; }
        .badge-statut.fausse { background-color: #dc3545; }
        .badge-statut.a_verifier { background-color: #ffc107; color: #212529; }
        .info-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
            margin-top: 10px;
        }
        /* Style pour les onglets du modal "Ajouter une Sous-information" */
        [id^="addSubInformationModal_"] .nav-tabs .nav-link {
            color: var(--dnd-primary-darker) !important;
        }
        [id^="addSubInformationModal_"] .nav-tabs .nav-link.active {
            color: var(--dnd-primary-darker) !important;
            font-weight: 600;
        }
        [id^="addSubInformationModal_"] .nav-tabs .nav-link:hover {
            color: var(--dnd-primary-darker) !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="zone-de-titre">
            <div class="zone-titre-container">
                <h1 class="titre-zone">
                    <i class="fas fa-palette text-primary me-2"></i>
                    <?php echo htmlspecialchars($thematique->nom); ?>
                </h1>
                <div>
                    <a href="thematiques.php" class="btn-txt">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <div class="mb-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInformationToThematiqueModal">
                        <i class="fas fa-plus"></i> Ajouter une Information
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createInformationModal">
                        <i class="fas fa-plus-circle"></i> Créer une Nouvelle Information
                    </button>
                    <a href="thematiques.php?edit=<?php echo $thematique->id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier la Thématique
                    </a>
                </div>
                
                <div class="mb-4">
                    <h5>Description</h5>
                    <?php if ($thematique->description): ?>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($thematique->description)); ?></p>
                    <?php else: ?>
                        <p class="text-muted"><em>Aucune description</em></p>
                    <?php endif; ?>
                </div>
                
                <h5 class="mb-3">Informations associées</h5>
                <?php 
                $thematique_informations = $thematique->getInformations();
                if (empty($thematique_informations)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Aucune information associée à cette thématique.
                    </div>
                <?php else: ?>
                    <?php 
                    $total_informations = count($thematique_informations);
                    foreach ($thematique_informations as $index => $info): 
                        $is_first = ($index === 0);
                        $is_last = ($index === $total_informations - 1);
                        $niveau_label = Information::NIVEAUX[$info['niveau_confidentialite']] ?? $info['niveau_confidentialite'];
                        $niveau_class = str_replace('_', '-', $info['niveau_confidentialite']);
                        $statut_label = Information::STATUTS[$info['statut']] ?? $info['statut'];
                        $statut_class = str_replace('_', '-', $info['statut']);
                    ?>
                        <div class="information-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                        <form method="POST" style="display: inline;" 
                                              <?php if ($is_first): ?>onsubmit="return false;"<?php else: ?>onsubmit="return true;"<?php endif; ?>>
                                            <input type="hidden" name="action" value="move_information_up">
                                            <input type="hidden" name="information_id" value="<?php echo $info['id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary" 
                                                    title="Monter" 
                                                    <?php if ($is_first): ?>disabled<?php endif; ?>>
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" 
                                              <?php if ($is_last): ?>onsubmit="return false;"<?php else: ?>onsubmit="return true;"<?php endif; ?>>
                                            <input type="hidden" name="action" value="move_information_down">
                                            <input type="hidden" name="information_id" value="<?php echo $info['id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary" 
                                                    title="Descendre" 
                                                    <?php if ($is_last): ?>disabled<?php endif; ?>>
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($info['titre']); ?>
                                            <span class="badge badge-confidentialite <?php echo $niveau_class; ?> ms-2">
                                                <?php echo htmlspecialchars($niveau_label); ?>
                                            </span>
                                            <span class="badge badge-statut <?php echo $statut_class; ?> ms-2">
                                                <?php echo htmlspecialchars($statut_label); ?>
                                            </span>
                                        </h6>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_thematique.php?id=<?php echo $thematique->id; ?>&edit_information=<?php echo $info['id']; ?>" 
                                       class="btn btn-outline-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Retirer cette information de la thématique ?');">
                                        <input type="hidden" name="action" value="remove_information_from_thematique">
                                        <input type="hidden" name="information_id" value="<?php echo $info['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Retirer de la thématique">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette information ?');">
                                        <input type="hidden" name="action" value="delete_information">
                                        <input type="hidden" name="information_id" value="<?php echo $info['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php if ($info['image_path']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($info['image_path']); ?>" 
                                         alt="Image" class="info-image">
                                </div>
                            <?php endif; ?>
                            <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($info['description'] ?: 'Aucune description')); ?></p>
                            
                            <!-- Sous-informations -->
                            <?php 
                            $info_obj = Information::findById($info['id']);
                            $sub_informations = $info_obj ? $info_obj->getSubInformations() : [];
                            ?>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-secondary" 
                                        type="button"
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#subInformations_<?php echo $info['id']; ?>"
                                        aria-expanded="false" 
                                        aria-controls="subInformations_<?php echo $info['id']; ?>">
                                    <i class="fas fa-list-ul"></i> 
                                    <span class="sub-info-toggle-text">Afficher les Sous-informations</span>
                                    <?php if (!empty($sub_informations)): ?>
                                        <span class="badge bg-secondary ms-2"><?php echo count($sub_informations); ?></span>
                                    <?php endif; ?>
                                </button>
                                <button class="btn btn-sm btn-outline-primary ms-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addSubInformationModal_<?php echo $info['id']; ?>">
                                    <i class="fas fa-plus"></i> Ajouter une Sous-information
                                </button>
                            </div>
                            
                            <?php if (!empty($sub_informations)): ?>
                                <div class="collapse mt-3 ms-4 border-start border-3 border-secondary ps-3" id="subInformations_<?php echo $info['id']; ?>">
                                    <h6 class="small mb-2">
                                        <i class="fas fa-list-ul"></i> Sous-informations
                                    </h6>
                                    <?php 
                                    // Fonction récursive pour afficher les sous-informations
                                    $renderSubInfo = function($sub_info, $parent_info_id, $sub_index, $total_sub, $thematique_id) use (&$renderSubInfo) {
                                        $is_first_sub = ($sub_index === 0);
                                        $is_last_sub = ($sub_index === $total_sub - 1);
                                        $sub_niveau_label = Information::NIVEAUX[$sub_info['niveau_confidentialite']] ?? $sub_info['niveau_confidentialite'];
                                        $sub_niveau_class = str_replace('_', '-', $sub_info['niveau_confidentialite']);
                                        $sub_statut_label = Information::STATUTS[$sub_info['statut']] ?? $sub_info['statut'];
                                        $sub_statut_class = str_replace('_', '-', $sub_info['statut']);
                                        
                                        // Récupérer les sous-informations de cette sous-information
                                        $sub_info_obj = Information::findById($sub_info['id']);
                                        $sub_sub_informations = $sub_info_obj ? $sub_info_obj->getSubInformations() : [];
                                    ?>
                                        <div class="information-item mb-2" style="margin-left: 0;">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <form method="POST" style="display: inline;" 
                                                              <?php if ($is_first_sub): ?>onsubmit="return false;"<?php else: ?>onsubmit="return true;"<?php endif; ?>>
                                                            <input type="hidden" name="action" value="move_sub_information_up">
                                                            <input type="hidden" name="information_id" value="<?php echo $parent_info_id; ?>">
                                                            <input type="hidden" name="child_information_id" value="<?php echo $sub_info['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm" 
                                                                    title="Monter" 
                                                                    <?php if ($is_first_sub): ?>disabled<?php endif; ?>>
                                                                <i class="fas fa-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;" 
                                                              <?php if ($is_last_sub): ?>onsubmit="return false;"<?php else: ?>onsubmit="return true;"<?php endif; ?>>
                                                            <input type="hidden" name="action" value="move_sub_information_down">
                                                            <input type="hidden" name="information_id" value="<?php echo $parent_info_id; ?>">
                                                            <input type="hidden" name="child_information_id" value="<?php echo $sub_info['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm" 
                                                                    title="Descendre" 
                                                                    <?php if ($is_last_sub): ?>disabled<?php endif; ?>>
                                                                <i class="fas fa-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <small class="fw-bold">
                                                            <?php echo htmlspecialchars($sub_info['titre']); ?>
                                                            <span class="badge badge-confidentialite <?php echo $sub_niveau_class; ?> ms-2">
                                                                <?php echo htmlspecialchars($sub_niveau_label); ?>
                                                            </span>
                                                            <span class="badge badge-statut <?php echo $sub_statut_class; ?> ms-2">
                                                                <?php echo htmlspecialchars($sub_statut_label); ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_thematique.php?id=<?php echo $thematique_id; ?>&edit_information=<?php echo $sub_info['id']; ?>" 
                                                       class="btn btn-outline-warning btn-sm" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Retirer cette sous-information ?');">
                                                        <input type="hidden" name="action" value="remove_sub_information">
                                                        <input type="hidden" name="information_id" value="<?php echo $parent_info_id; ?>">
                                                        <input type="hidden" name="child_information_id" value="<?php echo $sub_info['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Retirer">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <?php if ($sub_info['image_path']): ?>
                                                <div class="mb-2">
                                                    <img src="<?php echo htmlspecialchars($sub_info['image_path']); ?>" 
                                                         alt="Image" class="info-image" style="max-height: 200px;">
                                                </div>
                                            <?php endif; ?>
                                            <p class="mb-0 small text-muted"><?php echo nl2br(htmlspecialchars($sub_info['description'] ?: 'Aucune description')); ?></p>
                                            
                                            <!-- Boutons pour les sous-informations de cette sous-information -->
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        type="button"
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#subInformations_<?php echo $sub_info['id']; ?>"
                                                        aria-expanded="false" 
                                                        aria-controls="subInformations_<?php echo $sub_info['id']; ?>">
                                                    <i class="fas fa-list-ul"></i> 
                                                    <span class="sub-info-toggle-text">Afficher les Sous-informations</span>
                                                    <?php if (!empty($sub_sub_informations)): ?>
                                                        <span class="badge bg-secondary ms-2"><?php echo count($sub_sub_informations); ?></span>
                                                    <?php endif; ?>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary ms-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#addSubInformationModal_<?php echo $sub_info['id']; ?>">
                                                    <i class="fas fa-plus"></i> Ajouter une Sous-information
                                                </button>
                                            </div>
                                            
                                            <!-- Affichage récursif des sous-informations -->
                                            <?php if (!empty($sub_sub_informations)): ?>
                                                <div class="collapse mt-3 ms-4 border-start border-3 border-secondary ps-3" id="subInformations_<?php echo $sub_info['id']; ?>">
                                                    <h6 class="small mb-2">
                                                        <i class="fas fa-list-ul"></i> Sous-informations
                                                    </h6>
                                                    <?php 
                                                    $total_sub_sub = count($sub_sub_informations);
                                                    foreach ($sub_sub_informations as $sub_sub_index => $sub_sub_info): 
                                                        $renderSubInfo($sub_sub_info, $sub_info['id'], $sub_sub_index, $total_sub_sub, $thematique_id);
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php
                                    };
                                    
                                    $total_sub = count($sub_informations);
                                    foreach ($sub_informations as $sub_index => $sub_info): 
                                        $renderSubInfo($sub_info, $info['id'], $sub_index, $total_sub, $thematique->id);
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="mb-2 mt-4">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Créé le <?php echo date('d/m/Y à H:i', strtotime($thematique->created_at)); ?>
                        <?php if ($thematique->updated_at != $thematique->created_at): ?>
                            <br>
                            <i class="fas fa-edit me-1"></i>
                            Modifié le <?php echo date('d/m/Y à H:i', strtotime($thematique->updated_at)); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Ajouter Information à la Thématique -->
    <div class="modal fade" id="addInformationToThematiqueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter une Information à la Thématique</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_information_to_thematique">
                        
                        <div class="mb-3">
                            <label class="form-label">Sélectionner une Information</label>
                            <?php 
                            $thematique_info_ids = array_column($thematique->getInformations(), 'id');
                            $available_informations = array_filter($all_informations, function($info) use ($thematique_info_ids) {
                                return !in_array($info->id, $thematique_info_ids);
                            });
                            ?>
                            <?php if (empty($available_informations)): ?>
                                <p class="text-muted">Toutes les informations sont déjà associées à cette thématique.</p>
                            <?php else: ?>
                                <select class="form-select" name="information_id" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($available_informations as $info): ?>
                                        <option value="<?php echo $info->id; ?>">
                                            <?php echo htmlspecialchars($info->titre); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <?php if (!empty($available_informations)): ?>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Création/Modification Information -->
    <?php include 'includes/modals/information_modal.php'; ?>
    
    <!-- Modals pour ajouter des sous-informations -->
    <?php 
    // Fonction récursive pour générer les modals
    $renderSubInfoModal = function($info, $all_informations, $characters, $npcs, $monsters, $groupes) use (&$renderSubInfoModal) {
    ?>
        <div class="modal fade" id="addSubInformationModal_<?php echo $info['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data" id="addSubInformationForm_<?php echo $info['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Ajouter une Sous-information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_sub_information">
                            <input type="hidden" name="information_id" value="<?php echo $info['id']; ?>">
                            
                            <!-- Onglets pour choisir entre ajouter une existante ou créer une nouvelle -->
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="add-existing-tab-<?php echo $info['id']; ?>" 
                                            data-bs-toggle="tab" data-bs-target="#add-existing-<?php echo $info['id']; ?>" 
                                            type="button" role="tab">
                                        Ajouter une Information existante
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="create-new-tab-<?php echo $info['id']; ?>" 
                                            data-bs-toggle="tab" data-bs-target="#create-new-<?php echo $info['id']; ?>" 
                                            type="button" role="tab">
                                        Créer une Nouvelle Information
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Onglet Ajouter existante -->
                                <div class="tab-pane fade show active" id="add-existing-<?php echo $info['id']; ?>" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label">Sélectionner une Information existante</label>
                                        <?php 
                                        $info_obj = Information::findById($info['id']);
                                        $existing_sub_ids = $info_obj ? array_column($info_obj->getSubInformations(), 'id') : [];
                                        $existing_sub_ids[] = $info['id']; // Exclure l'information elle-même
                                        
                                        // Exclure aussi toutes les informations qui créeraient une boucle
                                        $available_informations = array_filter($all_informations, function($inf) use ($existing_sub_ids, $info_obj) {
                                            if (in_array($inf->id, $existing_sub_ids)) {
                                                return false;
                                            }
                                            // Vérifier si cela créerait une boucle
                                            if ($info_obj) {
                                                return $info_obj->canBeSubInformationOf($inf->id);
                                            }
                                            return true;
                                        });
                                        ?>
                                        <?php if (empty($available_informations)): ?>
                                            <p class="text-muted">Aucune information disponible à ajouter.</p>
                                        <?php else: ?>
                                            <select class="form-select" name="child_information_id" id="child_information_select_<?php echo $info['id']; ?>">
                                                <option value="">-- Sélectionner --</option>
                                                <?php foreach ($available_informations as $available_info): ?>
                                                    <option value="<?php echo $available_info->id; ?>">
                                                        <?php echo htmlspecialchars($available_info->titre); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Onglet Créer nouvelle -->
                                <div class="tab-pane fade" id="create-new-<?php echo $info['id']; ?>" role="tabpanel">
                                    <input type="hidden" name="create_new_sub_information" value="1" id="create_new_flag_<?php echo $info['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="sub_info_titre_<?php echo $info['id']; ?>" class="form-label">Titre *</label>
                                        <input type="text" class="form-control" id="sub_info_titre_<?php echo $info['id']; ?>" name="titre">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sub_info_description_<?php echo $info['id']; ?>" class="form-label">Description</label>
                                        <textarea class="form-control" id="sub_info_description_<?php echo $info['id']; ?>" name="description" rows="4"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="sub_info_niveau_<?php echo $info['id']; ?>" class="form-label">Niveau de confidentialité *</label>
                                            <select class="form-select" id="sub_info_niveau_<?php echo $info['id']; ?>" name="niveau_confidentialite">
                                                <?php foreach (Information::NIVEAUX as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $key === 'connu' ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="sub_info_statut_<?php echo $info['id']; ?>" class="form-label">Statut *</label>
                                            <select class="form-select" id="sub_info_statut_<?php echo $info['id']; ?>" name="statut">
                                                <?php foreach (Information::STATUTS as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $key === 'a_verifier' ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sub_info_image_<?php echo $info['id']; ?>" class="form-label">Image</label>
                                        <input type="file" class="form-control" id="sub_info_image_<?php echo $info['id']; ?>" name="image" accept="image/*">
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="mb-3">Accès (optionnel)</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Accès Joueurs</label>
                                        <?php if (empty($characters)): ?>
                                            <p class="text-muted small">Aucun personnage joueur disponible</p>
                                        <?php else: ?>
                                            <div class="border p-2" style="max-height: 100px; overflow-y: auto;">
                                                <?php foreach ($characters as $character): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="sub_info_player_<?php echo $info['id']; ?>_<?php echo $character->id; ?>" 
                                                               name="player_access[]" 
                                                               value="<?php echo $character->id; ?>">
                                                        <label class="form-check-label small" for="sub_info_player_<?php echo $info['id']; ?>_<?php echo $character->id; ?>">
                                                            <?php echo htmlspecialchars($character->name); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Accès PNJ</label>
                                        <?php if (empty($npcs)): ?>
                                            <p class="text-muted small">Aucun PNJ disponible</p>
                                        <?php else: ?>
                                            <div class="border p-2" style="max-height: 100px; overflow-y: auto;">
                                                <?php foreach ($npcs as $npc): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="sub_info_npc_<?php echo $info['id']; ?>_<?php echo $npc['id']; ?>" 
                                                               name="npc_access[]" 
                                                               value="<?php echo $npc['id']; ?>">
                                                        <label class="form-check-label small" for="sub_info_npc_<?php echo $info['id']; ?>_<?php echo $npc['id']; ?>">
                                                            <?php echo htmlspecialchars($npc['name']); ?> 
                                                            <small class="text-muted">(<?php echo htmlspecialchars($npc['place_name']); ?>)</small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Accès Monstres</label>
                                        <?php if (empty($monsters)): ?>
                                            <p class="text-muted small">Aucun monstre disponible</p>
                                        <?php else: ?>
                                            <div class="border p-2" style="max-height: 100px; overflow-y: auto;">
                                                <?php foreach ($monsters as $monster): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="sub_info_monster_<?php echo $info['id']; ?>_<?php echo $monster['id']; ?>" 
                                                               name="monster_access[]" 
                                                               value="<?php echo $monster['id']; ?>">
                                                        <label class="form-check-label small" for="sub_info_monster_<?php echo $info['id']; ?>_<?php echo $monster['id']; ?>">
                                                            <?php echo htmlspecialchars($monster['name']); ?> 
                                                            <small class="text-muted">(<?php echo htmlspecialchars($monster['place_name']); ?>)</small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Accès Groupes</label>
                                        <?php if (empty($groupes)): ?>
                                            <p class="text-muted small">Aucun groupe disponible</p>
                                        <?php else: ?>
                                            <div class="border p-2" style="max-height: 300px; overflow-y: auto;">
                                                <?php foreach ($groupes as $groupe): ?>
                                                    <div class="mb-3 border-bottom pb-2">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input group-checkbox-sub" type="checkbox" 
                                                                   id="sub_info_group_<?php echo $info['id']; ?>_<?php echo $groupe['id']; ?>" 
                                                                   data-group-id="<?php echo $groupe['id']; ?>">
                                                            <label class="form-check-label small fw-bold" for="sub_info_group_<?php echo $info['id']; ?>_<?php echo $groupe['id']; ?>">
                                                                <?php echo htmlspecialchars($groupe['name']); ?>
                                                            </label>
                                                        </div>
                                                        <div class="ms-4" id="niveau_sub_group_<?php echo $info['id']; ?>_<?php echo $groupe['id']; ?>" style="display: none;">
                                                            <label class="form-label small mb-1">Niveaux hiérarchiques (1 = dirigeant)</label>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <?php for ($niveau = 1; $niveau <= 5; $niveau++): ?>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input group-level-checkbox-sub" 
                                                                               type="checkbox" 
                                                                               id="sub_info_group_<?php echo $info['id']; ?>_<?php echo $groupe['id']; ?>_niveau_<?php echo $niveau; ?>" 
                                                                               name="group_access[]" 
                                                                               value="<?php echo $groupe['id']; ?>_<?php echo $niveau; ?>"
                                                                               data-group-id="<?php echo $groupe['id']; ?>">
                                                                        <label class="form-check-label small" for="sub_info_group_<?php echo $info['id']; ?>_<?php echo $groupe['id']; ?>_niveau_<?php echo $niveau; ?>">
                                                                            Niveau <?php echo $niveau; ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" id="submit_sub_info_<?php echo $info['id']; ?>">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        (function() {
            var modalId = '<?php echo $info['id']; ?>';
            var form = document.getElementById('addSubInformationForm_' + modalId);
            var childSelect = document.getElementById('child_information_select_' + modalId);
            var createNewFlag = document.getElementById('create_new_flag_' + modalId);
            
            // Gérer le texte du bouton d'affichage des sous-informations
            var subInfoToggle = document.querySelector('[data-bs-target="#subInformations_' + modalId + '"]');
            var subInfoCollapse = document.getElementById('subInformations_' + modalId);
            if (subInfoToggle && subInfoCollapse) {
                subInfoCollapse.addEventListener('show.bs.collapse', function() {
                    var toggleText = subInfoToggle.querySelector('.sub-info-toggle-text');
                    if (toggleText) {
                        toggleText.textContent = 'Masquer les Sous-informations';
                    }
                    var icon = subInfoToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-list-ul');
                        icon.classList.add('fa-chevron-up');
                    }
                });
                subInfoCollapse.addEventListener('hide.bs.collapse', function() {
                    var toggleText = subInfoToggle.querySelector('.sub-info-toggle-text');
                    if (toggleText) {
                        toggleText.textContent = 'Afficher les Sous-informations';
                    }
                    var icon = subInfoToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-list-ul');
                    }
                });
            }
            
            // Gérer l'affichage des cases à cocher de niveaux pour les groupes
            document.querySelectorAll('#addSubInformationModal_' + modalId + ' .group-checkbox-sub').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    var niveauDiv = document.getElementById('niveau_sub_group_' + modalId + '_' + this.dataset.groupId);
                    if (niveauDiv) {
                        niveauDiv.style.display = this.checked ? 'block' : 'none';
                        // Si on décoche le groupe, décocher aussi tous les niveaux
                        if (!this.checked) {
                            var levelCheckboxes = niveauDiv.querySelectorAll('.group-level-checkbox-sub');
                            levelCheckboxes.forEach(function(levelCb) {
                                levelCb.checked = false;
                            });
                        }
                    }
                });
            });
            
            // Gérer la case principale groupe : se cocher si au moins un niveau est coché
            document.querySelectorAll('#addSubInformationModal_' + modalId + ' .group-level-checkbox-sub').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    var groupId = this.dataset.groupId;
                    var groupCheckbox = document.getElementById('sub_info_group_' + modalId + '_' + groupId);
                    var levelCheckboxes = document.querySelectorAll('#addSubInformationModal_' + modalId + ' .group-level-checkbox-sub[data-group-id="' + groupId + '"]');
                    var hasChecked = Array.from(levelCheckboxes).some(function(cb) { return cb.checked; });
                    
                    if (groupCheckbox) {
                        groupCheckbox.checked = hasChecked;
                        var niveauDiv = document.getElementById('niveau_sub_group_' + modalId + '_' + groupId);
                        if (niveauDiv) {
                            niveauDiv.style.display = hasChecked ? 'block' : 'none';
                        }
                    }
                });
            });
            
            // Gérer le changement d'onglet
            var addExistingTab = document.getElementById('add-existing-tab-' + modalId);
            var createNewTab = document.getElementById('create-new-tab-' + modalId);
            
            if (addExistingTab) {
                addExistingTab.addEventListener('shown.bs.tab', function() {
                    if (createNewFlag) createNewFlag.value = '0';
                    if (childSelect) childSelect.required = true;
                });
            }
            
            if (createNewTab) {
                createNewTab.addEventListener('shown.bs.tab', function() {
                    if (createNewFlag) createNewFlag.value = '1';
                    if (childSelect) {
                        childSelect.required = false;
                        childSelect.value = '';
                    }
                });
            }
            
            // Les valeurs des groupes sont déjà gérées par les checkboxes individuelles
            // Pas besoin de traitement supplémentaire
        })();
        </script>
    <?php 
        // Récursion : générer les modals pour les sous-informations
        $info_obj = Information::findById($info['id']);
        $sub_informations = $info_obj ? $info_obj->getSubInformations() : [];
        foreach ($sub_informations as $sub_info) {
            $renderSubInfoModal($sub_info, $all_informations, $characters, $npcs, $monsters, $groupes);
        }
    };
    
    $thematique_informations = $thematique->getInformations();
    foreach ($thematique_informations as $info): 
        $renderSubInfoModal($info, $all_informations, $characters, $npcs, $monsters, $groupes);
    endforeach; 
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gérer tous les boutons d'affichage/masquage des sous-informations (récursif)
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour gérer un bouton de toggle
            function setupSubInfoToggle(toggleButton) {
                var targetId = toggleButton.getAttribute('data-bs-target');
                if (!targetId) return;
                
                var collapse = document.querySelector(targetId);
                if (!collapse) return;
                
                collapse.addEventListener('show.bs.collapse', function() {
                    var toggleText = toggleButton.querySelector('.sub-info-toggle-text');
                    if (toggleText) {
                        toggleText.textContent = 'Masquer les Sous-informations';
                    }
                    var icon = toggleButton.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-list-ul');
                        icon.classList.add('fa-chevron-up');
                    }
                });
                
                collapse.addEventListener('hide.bs.collapse', function() {
                    var toggleText = toggleButton.querySelector('.sub-info-toggle-text');
                    if (toggleText) {
                        toggleText.textContent = 'Afficher les Sous-informations';
                    }
                    var icon = toggleButton.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-list-ul');
                    }
                });
            }
            
            // Appliquer à tous les boutons de toggle
            document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target^="#subInformations_"]').forEach(function(button) {
                setupSubInfoToggle(button);
            });
        });
        
        // Ouvrir automatiquement le modal d'édition si présent
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($editing_information) && $editing_information): ?>
                var editInfoModal = new bootstrap.Modal(document.getElementById('editInformationModal'));
                editInfoModal.show();
            <?php endif; ?>
        });
        
        // Ajouter from_thematique au formulaire de création d'information
        var createInfoModal = document.getElementById('createInformationModal');
        if (createInfoModal) {
            createInfoModal.addEventListener('show.bs.modal', function() {
                var container = document.getElementById('fromThematiqueContainer_createInformationModal');
                if (container && !document.getElementById('fromThematiqueInput_createInformationModal')) {
                    container.innerHTML = '<input type="hidden" name="from_thematique" value="<?php echo $thematique->id; ?>" id="fromThematiqueInput_createInformationModal">';
                }
            });
        }
    </script>
</body>
</html>

