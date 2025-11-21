<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Détails du Groupe";
$current_page = "view_group";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_groups.php');
    exit();
}

$groupe_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer le groupe
$groupe = Groupe::findById($groupe_id);

if (!$groupe || $groupe->created_by != $user_id) {
    header('Location: manage_groups.php?error=group_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Traitement des actions sur les membres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_member':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            $hierarchy_level = (int)($_POST['hierarchy_level'] ?? 2);
            $is_secret = isset($_POST['member_is_secret']) && $_POST['member_is_secret'] === '1';
            $comment = trim($_POST['member_comment'] ?? '');
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->addMember($member_id, $member_type, $hierarchy_level, $is_secret, $comment)) {
                    $success_message = "Membre ajouté au groupe avec succès !";
                } else {
                    $error_message = "Erreur lors de l'ajout du membre.";
                }
            } else {
                $error_message = "Données invalides pour l'ajout du membre.";
            }
            break;
            
        case 'add_members_bulk':
            $members = $_POST['members'] ?? [];
            $hierarchy_level = (int)($_POST['hierarchy_level'] ?? 2);
            $is_secret = isset($_POST['member_is_secret']) && $_POST['member_is_secret'] === '1';
            $comment = trim($_POST['member_comment'] ?? '');
            
            if (empty($members) || !is_array($members)) {
                $error_message = "Aucun membre sélectionné.";
                break;
            }
            
            $added_count = 0;
            $failed_count = 0;
            $errors = [];
            
            foreach ($members as $member_data) {
                $parts = explode('|', $member_data);
                if (count($parts) !== 2) {
                    $failed_count++;
                    continue;
                }
                
                $member_id = (int)$parts[0];
                $member_type = $parts[1];
                
                if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                    if ($groupe->addMember($member_id, $member_type, $hierarchy_level, $is_secret, $comment)) {
                        $added_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $failed_count++;
                }
            }
            
            if ($added_count > 0) {
                if ($failed_count > 0) {
                    $success_message = "$added_count membre(s) ajouté(s) avec succès. $failed_count échec(s).";
                } else {
                    $success_message = "$added_count membre(s) ajouté(s) au groupe avec succès !";
                }
            } else {
                $error_message = "Aucun membre n'a pu être ajouté.";
            }
            break;
            
        case 'remove_member':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->removeMember($member_id, $member_type)) {
                    $success_message = "Membre supprimé du groupe avec succès !";
                } else {
                    $error_message = "Erreur lors de la suppression du membre.";
                }
            }
            break;
            
        case 'delete_member_completely':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            
            if ($member_id > 0 && $member_type === 'pj') {
                if ($groupe->deleteMemberCompletely($member_id, $user_id)) {
                    $success_message = "Personnage joueur supprimé complètement avec succès ! (Équipement, appartenances aux groupes et lieux supprimés)";
                } else {
                    $error_message = "Erreur lors de la suppression complète du personnage.";
                }
            } else {
                $error_message = "Seuls les personnages joueurs peuvent être supprimés complètement.";
            }
            break;
            
        case 'update_hierarchy':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            $hierarchy_level = (int)($_POST['hierarchy_level'] ?? 2);
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->updateMemberHierarchy($member_id, $member_type, $hierarchy_level)) {
                    $success_message = "Niveau hiérarchique mis à jour avec succès !";
                } else {
                    $error_message = "Erreur lors de la mise à jour du niveau hiérarchique.";
                }
            }
            break;
            
        case 'toggle_member_secret':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            $new_secret_status = isset($_POST['is_secret']) && $_POST['is_secret'] === '1';
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->updateMemberSecretStatus($member_id, $member_type, $new_secret_status)) {
                    $status_text = $new_secret_status ? 'secret' : 'public';
                    $success_message = "Statut du membre changé en {$status_text} avec succès !";
                } else {
                    $error_message = "Erreur lors de la mise à jour du statut du membre.";
                }
            } else {
                $error_message = "Données invalides pour la mise à jour du statut du membre.";
            }
            break;
            
        case 'update_member_comment':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            $comment = trim($_POST['comment'] ?? '');
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->updateMemberComment($member_id, $member_type, $comment)) {
                    $success_message = "Commentaire mis à jour avec succès !";
                } else {
                    $error_message = "Erreur lors de la mise à jour du commentaire.";
                }
            } else {
                $error_message = "Données invalides pour la mise à jour du commentaire.";
            }
            break;
            
        case 'update_member_participation':
            $member_id = (int)($_POST['member_id'] ?? 0);
            $member_type = $_POST['member_type'] ?? '';
            $hierarchy_level = (int)($_POST['hierarchy_level'] ?? 2);
            $is_secret = isset($_POST['is_secret']) && $_POST['is_secret'] === '1';
            $comment = trim($_POST['comment'] ?? '');
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->updateMemberParticipation($member_id, $member_type, $hierarchy_level, $is_secret, $comment)) {
                    $success_message = "Participation mise à jour avec succès !";
                } else {
                    $error_message = "Erreur lors de la mise à jour de la participation.";
                }
            } else {
                $error_message = "Données invalides pour la mise à jour de la participation.";
            }
            break;
            
        case 'update_hierarchy_levels_config':
            $levels_config = [];
            $max_levels = $groupe->max_hierarchy_levels ?? 5;
            
            for ($i = 1; $i <= $max_levels; $i++) {
                $title = trim($_POST["level_{$i}_title"] ?? '');
                $description = trim($_POST["level_{$i}_description"] ?? '');
                
                if (!empty($title) || !empty($description)) {
                    $levels_config[$i] = [
                        'title' => $title,
                        'description' => $description
                    ];
                }
            }
            
            if ($groupe->updateHierarchyLevelsConfig($levels_config)) {
                $success_message = "Configurations des niveaux hiérarchiques mises à jour avec succès !";
            } else {
                $error_message = "Erreur lors de la mise à jour des configurations des niveaux.";
            }
            break;
    }
}

// Récupérer les informations du groupe
$members = $groupe->getMembers();
$leader = $groupe->getLeader();
$headquarters = $groupe->getHeadquarters();
$hierarchy_levels_config = $groupe->getHierarchyLevelsConfig();

// Récupérer les PNJ, PJ et Monstres disponibles pour l'ajout
try {
    $pdo = getPdo();
    
    // PNJ disponibles (exclure les monstres : monster_id IS NULL)
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, pl.title as place_name
        FROM place_npcs pn
        JOIN places pl ON pn.place_id = pl.id
        LEFT JOIN countries co ON pl.country_id = co.id
        WHERE pn.monster_id IS NULL
        AND co.world_id IN (
            SELECT id FROM worlds WHERE created_by = ?
        )
        AND pn.id NOT IN (
            SELECT member_id FROM groupe_membres 
            WHERE groupe_id = ? AND member_type = 'pnj'
        )
        ORDER BY pn.name
    ");
    $stmt->execute([$user_id, $groupe_id]);
    $available_npcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // PJ disponibles
    // Les MJ/Admin peuvent voir tous les PJ disponibles
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, 'Personnage Joueur' as place_name
        FROM characters c
        WHERE c.id NOT IN (
            SELECT member_id FROM groupe_membres 
            WHERE groupe_id = ? AND member_type = 'pj'
        )
        ORDER BY c.name
    ");
    $stmt->execute([$groupe_id]);
    $available_pjs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monstres disponibles (depuis place_npcs où monster_id IS NOT NULL)
    $stmt = $pdo->prepare("
        SELECT pn.id, COALESCE(pn.name, dm.name) as name, pl.title as place_name
        FROM place_npcs pn
        JOIN dnd_monsters dm ON pn.monster_id = dm.id
        JOIN places pl ON pn.place_id = pl.id
        LEFT JOIN countries co ON pl.country_id = co.id
        WHERE pn.monster_id IS NOT NULL
        AND co.world_id IN (
            SELECT id FROM worlds WHERE created_by = ?
        )
        AND pn.id NOT IN (
            SELECT member_id FROM groupe_membres 
            WHERE groupe_id = ? AND member_type = 'monster'
        )
        ORDER BY COALESCE(pn.name, dm.name)
    ");
    $stmt->execute([$user_id, $groupe_id]);
    $available_monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $available_npcs = [];
    $available_pjs = [];
    $available_monsters = [];
    $error_message = "Erreur lors de la récupération des membres disponibles.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($groupe->name); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .member-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .info-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .clickable-badge {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .crest-display {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if ($success_message): ?>
            <?php echo displayMessage($success_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <?php echo displayMessage($error_message, 'error'); ?>
        <?php endif; ?>

        <!-- Zone de titre -->
        <div class="zone-de-titre">
            <div class="zone-titre-container">
                <div class="d-flex align-items-center">
                    <?php if (!empty($groupe->crest_image)): ?>
                        <img src="<?php echo htmlspecialchars($groupe->crest_image); ?>" 
                             alt="Blason" class="crest-display me-3">
                    <?php else: ?>
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-users text-white" style="font-size: 2rem;"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="titre-zone">
                            <i class="fas fa-users me-2"></i><?php echo htmlspecialchars($groupe->name); ?>
                            <?php if ($groupe->is_secret): ?>
                                <i class="fas fa-eye-slash text-warning ms-2" title="Groupe secret"></i>
                            <?php endif; ?>
                        </h1>
                        <?php if ($groupe->description): ?>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($groupe->description); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <a href="manage_groups.php" class="btn-txt">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                    <button type="button" class="btn-txt" data-bs-toggle="modal" data-bs-target="#configureLevelsModal">
                        <i class="fas fa-cog me-2"></i>Configurer les niveaux
                    </button>
                    <a href="edit_group.php?id=<?php echo $groupe->id; ?>" class="btn-txt">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                </div>
            </div>
        </div>
                
        <!-- Zone d'en-tête -->
        <div class="zone-d-entete mb-3">
            <div class="card border-0 shadow info-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informations
                            </h5>
                            <?php if ($headquarters): ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-map-marker-alt me-1"></i>Quartier Général :</strong><br>
                                    <?php echo htmlspecialchars($headquarters['title']); ?>
                                    <?php if ($headquarters['country_name']): ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars($headquarters['country_name']); ?>
                                            <?php if ($headquarters['region_name']): ?>
                                                - <?php echo htmlspecialchars($headquarters['region_name']); ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($leader): ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-crown me-1"></i>Dirigeant :</strong><br>
                                    <?php echo htmlspecialchars($leader['member_name']); ?>
                                    <span class="badge bg-warning ms-1">Niveau 1</span>
                                </p>
                            <?php endif; ?>
                            
                            <p class="mb-2">
                                <strong><i class="fas fa-users me-1"></i>Membres :</strong>
                                <?php echo count($members); ?> membre(s)
                            </p>
                            
                            <p class="mb-0">
                                <strong><i class="fas fa-calendar me-1"></i>Créé le :</strong>
                                <?php echo date('d/m/Y à H:i', strtotime($groupe->created_at)); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membres du groupe -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>Membres du Groupe
                </h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="fas fa-plus me-1"></i>Ajouter un Membre
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($members)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <h4>Aucun membre dans ce groupe</h4>
                        <p class="lead">Ajoutez des membres pour commencer à organiser votre groupe.</p>
                        <button class="btn btn-dnd btn-lg" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fas fa-plus me-2"></i>Ajouter le Premier Membre
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Membre</th>
                                                    <th>Type</th>
                                                    <th>Localisation</th>
                                                    <th>Niveau</th>
                                                    <th>Titre de niveau</th>
                                                    <th>Commentaire</th>
                                                    <th>Statut</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($members as $member): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if ($member['member_photo']): ?>
                                                                    <img src="<?php echo htmlspecialchars($member['member_photo']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($member['member_name']); ?>" 
                                                                         class="rounded-circle me-2" 
                                                                         style="width: 32px; height: 32px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                                         style="width: 32px; height: 32px;">
                                                                        <i class="fas <?php echo $member['member_type'] === 'monster' ? 'fa-dragon' : 'fa-user'; ?> text-white"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($member['member_name']); ?></strong>
                                                                    <?php if ($member['hierarchy_level'] == 1): ?>
                                                                        <i class="fas fa-crown text-warning ms-1" title="Dirigeant"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $member['member_type'] === 'pnj' ? 'bg-primary' : ($member['member_type'] === 'pj' ? 'bg-success' : 'bg-danger'); ?>">
                                                                <?php echo strtoupper($member['member_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($member['member_location'] ?? 'Non défini'); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">Niveau <?php echo $member['hierarchy_level']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($member['hierarchy_level_title'])): ?>
                                                                <span class="badge bg-primary"><?php echo htmlspecialchars($member['hierarchy_level_title']); ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($member['comment'])): ?>
                                                                <span class="text-muted" title="<?php echo htmlspecialchars($member['comment']); ?>">
                                                                    <i class="fas fa-comment me-1"></i><?php echo htmlspecialchars(mb_substr($member['comment'], 0, 50)) . (mb_strlen($member['comment']) > 50 ? '...' : ''); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($member['is_secret']): ?>
                                                                <span class="badge bg-warning clickable-badge" 
                                                                      onclick="toggleMemberSecret(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', <?php echo $member['is_secret'] ? 'true' : 'false'; ?>)"
                                                                      title="Cliquer pour changer en Public">
                                                                    <i class="fas fa-eye-slash me-1"></i>Secret
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info clickable-badge" 
                                                                      onclick="toggleMemberSecret(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', <?php echo $member['is_secret'] ? 'true' : 'false'; ?>)"
                                                                      title="Cliquer pour changer en Secret">
                                                                    <i class="fas fa-eye me-1"></i>Public
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-primary" 
                                                                        onclick="editParticipation(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', <?php echo $member['hierarchy_level']; ?>, <?php echo $member['is_secret'] ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars(addslashes($member['comment'] ?? '')); ?>')"
                                                                        title="Éditer la participation">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-outline-warning" 
                                                                        onclick="removeMember(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', '<?php echo htmlspecialchars($member['member_name']); ?>')"
                                                                        title="Retirer du groupe">
                                                                    <i class="fas fa-user-minus"></i>
                                                                </button>
                                                                <?php if ($member['member_type'] === 'pj'): ?>
                                                                    <button class="btn btn-outline-danger" 
                                                                            onclick="deleteMemberCompletely(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', '<?php echo htmlspecialchars($member['member_name']); ?>')"
                                                                            title="Supprimer complètement (équipement, groupes, lieux)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal d'ajout de membre -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="addMemberForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter des Membres</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_members_bulk">
                        
                        <!-- Filtres par type -->
                        <div class="mb-3">
                            <label class="form-label">Type de membre</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="member_type_filter" id="filter_all" value="all" checked onchange="filterMembersByType()">
                                <label class="btn btn-outline-primary" for="filter_all">Tous</label>
                                
                                <input type="radio" class="btn-check" name="member_type_filter" id="filter_pj" value="pj" onchange="filterMembersByType()">
                                <label class="btn btn-outline-success" for="filter_pj">PJ</label>
                                
                                <input type="radio" class="btn-check" name="member_type_filter" id="filter_pnj" value="pnj" onchange="filterMembersByType()">
                                <label class="btn btn-outline-info" for="filter_pnj">PNJ</label>
                                
                                <input type="radio" class="btn-check" name="member_type_filter" id="filter_monster" value="monster" onchange="filterMembersByType()">
                                <label class="btn btn-outline-danger" for="filter_monster">Monstres</label>
                            </div>
                        </div>
                        
                        <!-- Champ de recherche -->
                        <div class="mb-3">
                            <label for="member_search" class="form-label">Rechercher</label>
                            <input type="text" class="form-control" id="member_search" placeholder="Rechercher un membre par nom..." onkeyup="filterMembersByName()">
                        </div>
                        
                        <!-- Liste des membres avec checkboxes -->
                        <div class="mb-3">
                            <label class="form-label">Sélectionner les membres à ajouter</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;" id="members_list_container">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="select_all_members" onchange="toggleSelectAll()">
                                    <label class="form-check-label fw-bold" for="select_all_members">
                                        Sélectionner tout
                                    </label>
                                </div>
                                <hr class="my-2">
                                
                                <!-- PJ -->
                                <div class="member-type-group" data-type="pj">
                                    <?php if (!empty($available_pjs)): ?>
                                        <div class="text-muted small mb-2">
                                            <i class="fas fa-user me-1"></i>Personnages Joueurs (<?php echo count($available_pjs); ?>)
                                        </div>
                                        <?php foreach ($available_pjs as $pj): ?>
                                            <div class="form-check mb-2 member-item" data-type="pj" data-name="<?php echo htmlspecialchars(strtolower($pj['name'])); ?>">
                                                <input class="form-check-input member-checkbox" type="checkbox" 
                                                       name="members[]" 
                                                       id="member_pj_<?php echo $pj['id']; ?>"
                                                       value="<?php echo $pj['id']; ?>|pj">
                                                <label class="form-check-label" for="member_pj_<?php echo $pj['id']; ?>">
                                                    <?php echo htmlspecialchars($pj['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small mb-2">
                                            <i class="fas fa-user me-1"></i>Personnages Joueurs (0)
                                        </div>
                                        <div class="text-muted small">Aucun PJ disponible</div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- PNJ -->
                                <div class="member-type-group" data-type="pnj">
                                    <?php if (!empty($available_npcs)): ?>
                                        <div class="text-muted small mb-2 mt-3">
                                            <i class="fas fa-user-tie me-1"></i>Personnages Non-Joueurs (<?php echo count($available_npcs); ?>)
                                        </div>
                                        <?php foreach ($available_npcs as $npc): ?>
                                            <div class="form-check mb-2 member-item" data-type="pnj" data-name="<?php echo htmlspecialchars(strtolower($npc['name'] . ' ' . ($npc['place_name'] ?? ''))); ?>">
                                                <input class="form-check-input member-checkbox" type="checkbox" 
                                                       name="members[]" 
                                                       id="member_pnj_<?php echo $npc['id']; ?>"
                                                       value="<?php echo $npc['id']; ?>|pnj">
                                                <label class="form-check-label" for="member_pnj_<?php echo $npc['id']; ?>">
                                                    <?php echo htmlspecialchars($npc['name']); ?>
                                                    <?php if (!empty($npc['place_name'])): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($npc['place_name']); ?>)</small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small mb-2 mt-3">
                                            <i class="fas fa-user-tie me-1"></i>Personnages Non-Joueurs (0)
                                        </div>
                                        <div class="text-muted small">Aucun PNJ disponible</div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Monstres -->
                                <div class="member-type-group" data-type="monster">
                                    <?php if (!empty($available_monsters)): ?>
                                        <div class="text-muted small mb-2 mt-3">
                                            <i class="fas fa-dragon me-1"></i>Monstres (<?php echo count($available_monsters); ?>)
                                        </div>
                                        <?php foreach ($available_monsters as $monster): ?>
                                            <div class="form-check mb-2 member-item" data-type="monster" data-name="<?php echo htmlspecialchars(strtolower($monster['name'] . ' ' . ($monster['place_name'] ?? ''))); ?>">
                                                <input class="form-check-input member-checkbox" type="checkbox" 
                                                       name="members[]" 
                                                       id="member_monster_<?php echo $monster['id']; ?>"
                                                       value="<?php echo $monster['id']; ?>|monster">
                                                <label class="form-check-label" for="member_monster_<?php echo $monster['id']; ?>">
                                                    <?php echo htmlspecialchars($monster['name']); ?>
                                                    <?php if (!empty($monster['place_name'])): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($monster['place_name']); ?>)</small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small mb-2 mt-3">
                                            <i class="fas fa-dragon me-1"></i>Monstres (0)
                                        </div>
                                        <div class="text-muted small">Aucun monstre disponible</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <small class="form-text text-muted" id="selected_count">0 membre(s) sélectionné(s)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hierarchy_level" class="form-label">Niveau hiérarchique</label>
                            <select class="form-select" id="hierarchy_level" name="hierarchy_level" required>
                                <?php 
                                $max_levels = $groupe->max_hierarchy_levels ?? 5;
                                for ($niveau = 1; $niveau <= $max_levels; $niveau++): ?>
                                    <option value="<?php echo $niveau; ?>">Niveau <?php echo $niveau; ?><?php echo $niveau == 1 ? ' (Dirigeant)' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                            <small class="form-text text-muted">Le niveau 1 correspond aux dirigeants du groupe. Ce groupe a <?php echo $max_levels; ?> niveau<?php echo $max_levels > 1 ? 'x' : ''; ?> hiérarchique<?php echo $max_levels > 1 ? 's' : ''; ?>.</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="member_is_secret" name="member_is_secret" value="1" 
                                       <?php echo $groupe->is_secret ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="member_is_secret">
                                    <i class="fas fa-eye-slash me-1"></i>Membre secret
                                </label>
                            </div>
                            <div class="form-text">Un membre secret aura son appartenance cachée. Par défaut: <?php echo $groupe->is_secret ? 'secret' : 'public'; ?> (selon le groupe).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="member_comment" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="member_comment" name="member_comment" rows="3" 
                                      placeholder="Commentaire optionnel sur la participation de ces membres au groupe..."></textarea>
                            <small class="form-text text-muted">Note optionnelle qui s'appliquera à tous les membres sélectionnés</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitAddMembers" disabled>Ajouter les membres sélectionnés</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de configuration des niveaux hiérarchiques -->
    <div class="modal fade" id="configureLevelsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Configurer les niveaux hiérarchiques</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_hierarchy_levels_config">
                        <p class="text-muted mb-4">
                            Définissez un titre et une description pour chaque niveau hiérarchique de ce groupe.
                            Ces informations seront affichées lors de la consultation du groupe.
                        </p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 100px;">Niveau</th>
                                        <th>Titre</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $max_levels = $groupe->max_hierarchy_levels ?? 5;
                                    for ($i = 1; $i <= $max_levels; $i++): 
                                        $config = $hierarchy_levels_config[$i] ?? null;
                                        $title = $config['title'] ?? '';
                                        $description = $config['description'] ?? '';
                                    ?>
                                        <tr>
                                            <td class="align-middle text-center">
                                                <strong>Niveau <?php echo $i; ?></strong>
                                                <?php if ($i == 1): ?>
                                                    <br><span class="badge bg-warning mt-1">Dirigeant</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="level_<?php echo $i; ?>_title" 
                                                       name="level_<?php echo $i; ?>_title" 
                                                       value="<?php echo htmlspecialchars($title); ?>"
                                                       placeholder="Ex: Dirigeant, Lieutenant, Membre, etc.">
                                            </td>
                                            <td>
                                                <textarea class="form-control" 
                                                          id="level_<?php echo $i; ?>_description" 
                                                          name="level_<?php echo $i; ?>_description" 
                                                          rows="2"
                                                          placeholder="Description des responsabilités et du rôle de ce niveau..."><?php echo htmlspecialchars($description); ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les configurations</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulaires cachés pour les actions -->
    <form id="removeMemberForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="remove_member">
        <input type="hidden" name="member_id" id="removeMemberId">
        <input type="hidden" name="member_type" id="removeMemberType">
    </form>
    
    <form id="deleteMemberCompletelyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_member_completely">
        <input type="hidden" name="member_id" id="deleteMemberCompletelyId">
        <input type="hidden" name="member_type" id="deleteMemberCompletelyType">
    </form>
    
    <form id="toggleMemberSecretForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_member_secret">
        <input type="hidden" name="member_id" id="toggleMemberSecretId">
        <input type="hidden" name="member_type" id="toggleMemberSecretType">
        <input type="hidden" name="is_secret" id="toggleMemberSecretStatus">
    </form>
    
    <form id="updateHierarchyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_hierarchy">
        <input type="hidden" name="member_id" id="updateMemberId">
        <input type="hidden" name="member_type" id="updateMemberType">
        <input type="hidden" name="hierarchy_level" id="updateHierarchyLevel">
    </form>
    
    <!-- Modal de modification du commentaire -->
    <div class="modal fade" id="updateCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="updateCommentForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le commentaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_member_comment">
                        <input type="hidden" name="member_id" id="updateCommentMemberId">
                        <input type="hidden" name="member_type" id="updateCommentMemberType">
                        
                        <div class="mb-3">
                            <label for="updateCommentText" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="updateCommentText" name="comment" rows="4" 
                                      placeholder="Commentaire optionnel sur la participation de ce membre au groupe..."></textarea>
                            <small class="form-text text-muted">Note optionnelle sur ce membre dans ce groupe</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal d'édition de la participation -->
    <div class="modal fade" id="editParticipationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editParticipationForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Éditer la participation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_member_participation">
                        <input type="hidden" name="member_id" id="editParticipationMemberId">
                        <input type="hidden" name="member_type" id="editParticipationMemberType">
                        
                        <div class="mb-3">
                            <label for="editParticipationHierarchyLevel" class="form-label">Niveau hiérarchique</label>
                            <select class="form-select" id="editParticipationHierarchyLevel" name="hierarchy_level" required>
                                <?php 
                                $max_levels = $groupe->max_hierarchy_levels ?? 5;
                                for ($niveau = 1; $niveau <= $max_levels; $niveau++): ?>
                                    <option value="<?php echo $niveau; ?>">Niveau <?php echo $niveau; ?><?php echo $niveau == 1 ? ' (Dirigeant)' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                            <small class="form-text text-muted">Le niveau 1 correspond aux dirigeants du groupe.</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editParticipationIsSecret" name="is_secret" value="1">
                                <label class="form-check-label" for="editParticipationIsSecret">
                                    <i class="fas fa-eye-slash me-1"></i>Membre secret
                                </label>
                            </div>
                            <div class="form-text">Un membre secret aura son appartenance cachée.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editParticipationComment" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="editParticipationComment" name="comment" rows="4" 
                                      placeholder="Commentaire optionnel sur la participation de ce membre au groupe..."></textarea>
                            <small class="form-text text-muted">Note optionnelle sur ce membre dans ce groupe</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        .btn-dnd {
            background: linear-gradient(45deg, #8B4513, #D2691E);
            border: none;
            color: white;
        }
        .btn-dnd:hover {
            background: linear-gradient(45deg, #A0522D, #CD853F);
            color: white;
        }
    </style>
    <script>
        // Données des membres disponibles
        const availableMembers = {
            pnj: <?php echo json_encode($available_npcs); ?>,
            pj: <?php echo json_encode($available_pjs); ?>,
            monster: <?php echo json_encode($available_monsters); ?>
        };
        
        function updateMemberOptions() {
            const memberType = document.getElementById('member_type').value;
            const memberSelect = document.getElementById('member_id');
            
            memberSelect.innerHTML = '<option value="">Sélectionner un membre</option>';
            
            if (memberType && availableMembers[memberType]) {
                availableMembers[memberType].forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = member.name + (member.place_name ? ' (' + member.place_name + ')' : '');
                    memberSelect.appendChild(option);
                });
            }
        }
        
        function filterMembersByType() {
            const selectedType = document.querySelector('input[name="member_type_filter"]:checked').value;
            const memberItems = document.querySelectorAll('.member-item');
            const memberGroups = document.querySelectorAll('.member-type-group');
            
            memberItems.forEach(item => {
                const itemType = item.getAttribute('data-type');
                if (selectedType === 'all' || itemType === selectedType) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Afficher/masquer les groupes selon le filtre
            memberGroups.forEach(group => {
                const groupType = group.getAttribute('data-type');
                const visibleItems = group.querySelectorAll('.member-item[style=""]').length;
                const groupHeader = group.querySelector('.text-muted.small');
                
                if (selectedType === 'all' || groupType === selectedType) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
            
            // Appliquer aussi le filtre de recherche
            filterMembersByName();
        }
        
        function filterMembersByName() {
            const searchTerm = document.getElementById('member_search').value.toLowerCase();
            const selectedType = document.querySelector('input[name="member_type_filter"]:checked').value;
            const memberItems = document.querySelectorAll('.member-item');
            
            memberItems.forEach(item => {
                const itemType = item.getAttribute('data-type');
                const itemName = item.getAttribute('data-name') || '';
                const matchesType = selectedType === 'all' || itemType === selectedType;
                const matchesSearch = searchTerm === '' || itemName.includes(searchTerm);
                
                if (matchesType && matchesSearch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('select_all_members');
            const visibleCheckboxes = document.querySelectorAll('.member-item[style=""] .member-checkbox');
            
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.member-checkbox:checked');
            const count = checkedBoxes.length;
            document.getElementById('selected_count').textContent = count + ' membre(s) sélectionné(s)';
            
            const submitButton = document.getElementById('submitAddMembers');
            if (count > 0) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        }
        
        // Écouter les changements sur les checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.member-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
            
            // Réinitialiser la modale quand elle est fermée
            const modal = document.getElementById('addMemberModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('addMemberForm').reset();
                    document.getElementById('member_search').value = '';
                    document.getElementById('filter_all').checked = true;
                    document.querySelectorAll('.member-item').forEach(item => {
                        item.style.display = '';
                    });
                    document.querySelectorAll('.member-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                    document.getElementById('select_all_members').checked = false;
                    updateSelectedCount();
                });
            }
        });
        
        function removeMember(memberId, memberType, memberName) {
            if (confirm('Êtes-vous sûr de vouloir retirer "' + memberName + '" du groupe ?')) {
                document.getElementById('removeMemberId').value = memberId;
                document.getElementById('removeMemberType').value = memberType;
                document.getElementById('removeMemberForm').submit();
            }
        }
        
        function deleteMemberCompletely(memberId, memberType, memberName) {
            if (confirm('⚠️ ATTENTION ⚠️\n\nÊtes-vous sûr de vouloir supprimer COMPLÈTEMENT "' + memberName + '" ?\n\nCette action supprimera :\n• Le personnage et tout son équipement\n• Ses appartenances à tous les groupes\n• Sa présence dans tous les lieux\n• Toutes ses données associées\n\nCette action est IRRÉVERSIBLE !')) {
                document.getElementById('deleteMemberCompletelyId').value = memberId;
                document.getElementById('deleteMemberCompletelyType').value = memberType;
                document.getElementById('deleteMemberCompletelyForm').submit();
            }
        }
        
        function toggleMemberSecret(memberId, memberType, currentStatus) {
            const newStatus = !currentStatus;
            const statusText = newStatus ? 'secret' : 'public';
            const currentText = currentStatus ? 'secret' : 'public';
            
            if (confirm('Changer le statut de "' + memberType.toUpperCase() + '" de ' + currentText + ' à ' + statusText + ' ?')) {
                document.getElementById('toggleMemberSecretId').value = memberId;
                document.getElementById('toggleMemberSecretType').value = memberType;
                document.getElementById('toggleMemberSecretStatus').value = newStatus ? '1' : '0';
                document.getElementById('toggleMemberSecretForm').submit();
            }
        }
        
        function updateHierarchy(memberId, memberType, currentLevel) {
            const maxLevels = <?php echo $groupe->max_hierarchy_levels ?? 5; ?>;
            const newLevel = prompt('Nouveau niveau hiérarchique (1-' + maxLevels + '):', currentLevel);
            if (newLevel && newLevel >= 1 && newLevel <= maxLevels && newLevel != currentLevel) {
                document.getElementById('updateMemberId').value = memberId;
                document.getElementById('updateMemberType').value = memberType;
                document.getElementById('updateHierarchyLevel').value = newLevel;
                document.getElementById('updateHierarchyForm').submit();
            }
        }
        
        function updateComment(memberId, memberType, currentComment) {
            document.getElementById('updateCommentMemberId').value = memberId;
            document.getElementById('updateCommentMemberType').value = memberType;
            document.getElementById('updateCommentText').value = currentComment || '';
            
            const modal = new bootstrap.Modal(document.getElementById('updateCommentModal'));
            modal.show();
        }
        
        function editParticipation(memberId, memberType, hierarchyLevel, isSecret, comment) {
            document.getElementById('editParticipationMemberId').value = memberId;
            document.getElementById('editParticipationMemberType').value = memberType;
            document.getElementById('editParticipationHierarchyLevel').value = hierarchyLevel;
            document.getElementById('editParticipationIsSecret').checked = isSecret === true || isSecret === 'true' || isSecret === 1 || isSecret === '1';
            document.getElementById('editParticipationComment').value = comment || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editParticipationModal'));
            modal.show();
        }
    </script>
</body>
</html>
