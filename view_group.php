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
            
            if ($member_id > 0 && in_array($member_type, ['pnj', 'pj', 'monster'])) {
                if ($groupe->addMember($member_id, $member_type, $hierarchy_level, $is_secret)) {
                    $success_message = "Membre ajouté au groupe avec succès !";
                } else {
                    $error_message = "Erreur lors de l'ajout du membre.";
                }
            } else {
                $error_message = "Données invalides pour l'ajout du membre.";
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
    }
}

// Récupérer les informations du groupe
$members = $groupe->getMembers();
$leader = $groupe->getLeader();
$headquarters = $groupe->getHeadquarters();

// Récupérer les PNJ, PJ et Monstres disponibles pour l'ajout
try {
    $pdo = getPdo();
    
    // PNJ disponibles
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, pl.title as place_name
        FROM place_npcs pn
        JOIN places pl ON pn.place_id = pl.id
        LEFT JOIN countries co ON pl.country_id = co.id
        WHERE co.world_id IN (
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
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, 'Personnage Joueur' as place_name
        FROM characters c
        WHERE c.user_id = ?
        AND c.id NOT IN (
            SELECT member_id FROM groupe_membres 
            WHERE groupe_id = ? AND member_type = 'pj'
        )
        ORDER BY c.name
    ");
    $stmt->execute([$user_id, $groupe_id]);
    $available_pjs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monstres disponibles
    $stmt = $pdo->prepare("
        SELECT pm.id, dm.name, pl.title as place_name
        FROM place_monsters pm
        JOIN dnd_monsters dm ON pm.monster_id = dm.id
        JOIN places pl ON pm.place_id = pl.id
        LEFT JOIN countries co ON pl.country_id = co.id
        WHERE co.world_id IN (
            SELECT id FROM worlds WHERE created_by = ?
        )
        AND pm.id NOT IN (
            SELECT member_id FROM groupe_membres 
            WHERE groupe_id = ? AND member_type = 'monster'
        )
        ORDER BY dm.name
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
    <title><?php echo htmlspecialchars($groupe->name); ?> - JDR MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($groupe->crest_image)): ?>
                            <img src="<?php echo htmlspecialchars($groupe->crest_image); ?>" 
                                 alt="Blason" class="rounded me-3" style="width: 64px; height: 64px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded bg-secondary d-flex align-items-center justify-content-center me-3" 
                                 style="width: 64px; height: 64px;">
                                <i class="fas fa-users fa-2x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h1>
                                <?php echo htmlspecialchars($groupe->name); ?>
                                <?php if ($groupe->is_secret): ?>
                                    <i class="fas fa-eye-slash text-warning ms-2" title="Groupe secret"></i>
                                <?php endif; ?>
                            </h1>
                            <?php if ($groupe->description): ?>
                                <p class="text-muted"><?php echo htmlspecialchars($groupe->description); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="manage_groups.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux Groupes
                        </a>
                        <a href="edit_group.php?id=<?php echo $groupe->id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </a>
                    </div>
                </div>
                
                <?php if ($success_message): ?>
                    <?php echo displayMessage($success_message, 'success'); ?>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <?php echo displayMessage($error_message, 'error'); ?>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Informations du groupe -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Informations
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($headquarters): ?>
                                    <div class="mb-3">
                                        <strong>Quartier Général :</strong><br>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($headquarters['title']); ?>
                                        <?php if ($headquarters['country_name']): ?>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars($headquarters['country_name']); ?>
                                                <?php if ($headquarters['region_name']): ?>
                                                    - <?php echo htmlspecialchars($headquarters['region_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($leader): ?>
                                    <div class="mb-3">
                                        <strong>Dirigeant :</strong><br>
                                        <i class="fas fa-crown me-1"></i>
                                        <?php echo htmlspecialchars($leader['member_name']); ?>
                                        <span class="badge bg-warning ms-1">Niveau 1</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <strong>Membres :</strong><br>
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo count($members); ?> membre(s)
                                </div>
                                
                                <div>
                                    <strong>Créé le :</strong><br>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y à H:i', strtotime($groupe->created_at)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Membres du groupe -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Membres du Groupe
                                </h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                    <i class="fas fa-plus me-1"></i>Ajouter un Membre
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($members)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-user-plus fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun membre dans ce groupe</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
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
                                                                <button class="btn btn-outline-secondary" 
                                                                        onclick="updateHierarchy(<?php echo $member['member_id']; ?>, '<?php echo $member['member_type']; ?>', <?php echo $member['hierarchy_level']; ?>)"
                                                                        title="Modifier le niveau">
                                                                    <i class="fas fa-sort-numeric-up"></i>
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
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'ajout de membre -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un Membre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_member">
                        
                        <div class="mb-3">
                            <label for="member_type" class="form-label">Type de membre</label>
                            <select class="form-select" id="member_type" name="member_type" onchange="updateMemberOptions()" required>
                                <option value="">Sélectionner un type</option>
                                <option value="pnj">PNJ</option>
                                <option value="pj">PJ</option>
                                <option value="monster">Monstre</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="member_id" class="form-label">Membre</label>
                            <select class="form-select" id="member_id" name="member_id" required>
                                <option value="">Sélectionner un membre</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hierarchy_level" class="form-label">Niveau hiérarchique</label>
                            <select class="form-select" id="hierarchy_level" name="hierarchy_level" required>
                                <option value="2">Niveau 2 (Membre)</option>
                                <option value="3">Niveau 3 (Subalterne)</option>
                                <option value="4">Niveau 4 (Recrue)</option>
                                <option value="5">Niveau 5 (Novice)</option>
                            </select>
                            <small class="form-text text-muted">Le niveau 1 est réservé au dirigeant du groupe.</small>
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .clickable-badge {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
            const newLevel = prompt('Nouveau niveau hiérarchique (1-5):', currentLevel);
            if (newLevel && newLevel >= 1 && newLevel <= 5 && newLevel != currentLevel) {
                document.getElementById('updateMemberId').value = memberId;
                document.getElementById('updateMemberType').value = memberType;
                document.getElementById('updateHierarchyLevel').value = newLevel;
                document.getElementById('updateHierarchyForm').submit();
            }
        }
    </script>
</body>
</html>
