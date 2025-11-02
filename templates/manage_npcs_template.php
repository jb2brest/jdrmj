    <?php
/**
 * Template pour manage_npcs.php
 */

// Extraire les variables du template
extract($template_vars ?? []);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des PNJ et Monstres - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <style>
        .entity-name {
            color: #3E2723 !important; /* Marron presque noir */
            font-weight: 600;
        }
        .entity-name:hover {
            color: #5D4037 !important; /* Marron très foncé au survol */
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-users-cog me-2"></i>
                    Gestion des PNJ et Monstres
                </h1>
                <div>
                    <div class="btn-group me-2" role="group">
                        <a href="cc01_class_selection.php?type=npc" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nouveau Personnage
                        </a>
                        <a href="npc_create_automatic.php" class="btn btn-outline-primary">
                            <i class="fas fa-bolt me-1"></i> Création Automatique
                        </a>
                    </div>
                    <a href="monster_create_automatic.php" class="btn btn-success">
                        <i class="fas fa-dragon me-1"></i> Créer un Monstre
                    </a>
                </div>
            </div>

            <!-- Messages d'erreur/succès -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filtres
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Tous</option>
                                    <option value="PNJ" <?php echo ($filter_type === 'PNJ') ? 'selected' : ''; ?>>PNJ</option>
                                    <option value="Monstre" <?php echo ($filter_type === 'Monstre') ? 'selected' : ''; ?>>Monstres</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="world" class="form-label">Monde</label>
                                <select class="form-select" id="world" name="world">
                                    <option value="">Tous</option>
                                    <?php foreach ($worlds as $world): ?>
                                        <option value="<?php echo $world['id']; ?>" <?php echo ($filter_world == $world['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($world['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="country" class="form-label">Pays</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Tous</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?php echo $country['id']; ?>" <?php echo ($filter_country == $country['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($country['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="region" class="form-label">Région</label>
                                <select class="form-select" id="region" name="region">
                                    <option value="">Toutes</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['id']; ?>" <?php echo ($filter_region == $region['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="place" class="form-label">Lieu</label>
                                <select class="form-select" id="place" name="place">
                                    <option value="">Tous</option>
                                    <?php foreach ($places as $place): ?>
                                        <option value="<?php echo $place['id']; ?>" <?php echo ($filter_place == $place['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($place['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> Filtrer
                                    </button>
                                    <a href="manage_npcs.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Effacer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Créations PNJ en cours -->
            <?php if (!empty($pt_npc_drafts)): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hourglass-half me-2"></i>Créations PNJ en cours
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach ($pt_npc_drafts as $draft): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="me-3">
                                                    <?php if (!empty($draft['profile_photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($draft['profile_photo']); ?>" alt="Photo" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                            <i class="fas fa-user-plus text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($draft['name']); ?></h6>
                                                        <span class="badge bg-warning text-dark">Étape <?php echo (int)$draft['step']; ?>/9</span>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php if (!empty($draft['race_name'])): ?>
                                                            <i class="fas fa-dragon me-1"></i><?php echo htmlspecialchars($draft['race_name']); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($draft['class_name'])): ?>
                                                            <i class="fas fa-shield-alt me-2 ms-2"></i><?php echo htmlspecialchars($draft['class_name']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><i class="fas fa-calendar me-1"></i>Modifié le <?php echo date('d/m/Y', strtotime($draft['updated_at'])); ?></small>
                                                <div class="btn-group" role="group">
                                                    <a href="<?php echo $draft['resume_url']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-play me-1"></i>Reprendre
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            title="Supprimer" 
                                                            data-draft-id="<?php echo (int)($draft['id'] ?? 0); ?>"
                                                            data-draft-name="<?php echo htmlspecialchars($draft['name'] ?? ''); ?>"
                                                            onclick="deleteCharacterInProgress(parseInt(this.dataset.draftId), this.dataset.draftName, event)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des entités -->
            <div id="entitiesContainer">
                <?php if (empty($entities)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucune entité trouvée</h4>
                        <p class="text-muted">
                            <?php if ($filter_type || $filter_world || $filter_country || $filter_region || $filter_place): ?>
                                Aucune entité ne correspond aux filtres sélectionnés.
                            <?php else: ?>
                                Vous n'avez pas encore créé de PNJ ou de monstres.
                            <?php endif; ?>
                        </p>
                        <div class="mt-4">
                            <div class="btn-group me-2" role="group">
                                <a href="npc_create_step1.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Créer un PNJ mode complet
                                </a>
                                <a href="npc_create_automatic.php" class="btn btn-outline-primary">
                                    <i class="fas fa-bolt me-1"></i> Créer un PNJ mode rapide
                                </a>
                            </div>
                            <a href="monster_create_automatic.php" class="btn btn-success">
                                <i class="fas fa-dragon me-1"></i> Créer un Monstre
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row" id="entitiesList">
                        <?php foreach ($entities as $entity): ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-entity-id="<?php 
                                // Utiliser le même ID que celui du bouton de suppression
                                if (isset($entity['id']) && $entity['id'] > 0) {
                                    echo (int)$entity['id'];
                                } elseif (isset($entity['npc_character_id']) && $entity['npc_character_id'] > 0) {
                                    echo (int)$entity['npc_character_id'];
                                } else {
                                    echo '';
                                }
                            ?>">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($entity['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($entity['profile_photo']); ?>" 
                                                     alt="Photo" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="card-title mb-0">
                                                    <a href="<?php echo $entity['entity_type'] === 'PNJ' ? 'view_npc.php' : 'view_monster.php'; ?>?id=<?php echo $entity['view_id']; ?>" class="text-decoration-none entity-name">
                                                        <?php echo htmlspecialchars($entity['name']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <span class="badge bg-<?php echo $entity['entity_type'] === 'PNJ' ? 'primary' : 'danger'; ?>">
                                                        <?php echo $entity['entity_type']; ?>
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo $entity['entity_type'] === 'PNJ' ? 'view_npc.php' : 'view_monster.php'; ?>?id=<?php echo $entity['view_id']; ?>" 
                                               class="btn btn-outline-primary" title="Voir la fiche">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    data-entity-id="<?php 
                                                        // Pour les PNJ dans place_npcs, utiliser pn.id
                                                        // Pour les PNJ sans lieu (id NULL), utiliser npc_character_id
                                                        // Pour les monstres, utiliser m.id
                                                        if (isset($entity['id']) && $entity['id'] > 0) {
                                                            echo (int)$entity['id'];
                                                        } elseif (isset($entity['npc_character_id']) && $entity['npc_character_id'] > 0) {
                                                            echo (int)$entity['npc_character_id'];
                                                        } else {
                                                            echo '';
                                                        }
                                                    ?>"
                                                    data-entity-name="<?php echo htmlspecialchars($entity['name'] ?? ''); ?>"
                                                    data-entity-type="<?php echo htmlspecialchars($entity['entity_type'] ?? ''); ?>"
                                                    data-is-place-npc="<?php echo isset($entity['id']) && $entity['id'] > 0 ? '1' : '0'; ?>"
                                                    onclick="deleteEntity(this)"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($entity['description'])): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($entity['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- Informations spécifiques aux monstres -->
                                        <?php if ($entity['entity_type'] === 'Monstre' && !empty($entity['monster_type'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <strong>Type:</strong> <?php echo htmlspecialchars($entity['monster_type']); ?>
                                                    <?php if (!empty($entity['challenge_rating'])): ?>
                                                        | <strong>CR:</strong> <?php echo $entity['challenge_rating']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-globe me-1"></i>
                                                <?php echo htmlspecialchars($entity['world_name']); ?>
                                                <?php if (!empty($entity['country_name'])): ?>
                                                    - <?php echo htmlspecialchars($entity['country_name']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($entity['region_name'])): ?>
                                                    - <?php echo htmlspecialchars($entity['region_name']); ?>
                                                <?php endif; ?>
                                                - <?php echo htmlspecialchars($entity['place_name']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex gap-1">
                                            <?php if ($entity['is_visible']): ?>
                                                <span class="badge bg-success">Visible</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Caché</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($entity['is_identified']): ?>
                                                <span class="badge bg-info">Identifié</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Non identifié</span>
                                            <?php endif; ?>
                                        </div>
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

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer <strong id="entityName"></strong> ?</p>
                <p class="text-muted">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Données pour les filtres JavaScript
window.filterData = {
    countries: <?php echo json_encode(array_values($filtered_countries ?? $countries ?? [])); ?>,
    regions: <?php echo json_encode(array_values($filtered_regions ?? $regions ?? [])); ?>,
    places: <?php echo json_encode(array_values($filtered_places ?? $places ?? [])); ?>
};
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jdrmj.js"></script>
    <script>
        /**
         * Supprimer un personnage en cours de création (PTCharacter)
         */
        function deleteCharacterInProgress(ptCharacterId, characterName, event) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer le personnage en cours de création "' + characterName + '" ?\n\nCette action est irréversible et supprimera toutes les données associées.')) {
                return;
            }
            
            // Désactiver le bouton pendant le traitement
            const btn = (event || window.event).target.closest('button');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('api/delete_character_in_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pt_character_id: ptCharacterId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message + 
                                         '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    
                    const container = document.querySelector('.container-fluid');
                    container.insertBefore(alertDiv, container.firstChild);
                    
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur lors de la suppression'));
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la communication avec le serveur');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }
    </script>
</body>
</html>
