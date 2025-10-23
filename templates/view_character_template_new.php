<?php
/**
 * Template pour l'affichage d'une feuille de personnage
 * Utilise les nouvelles structures refactorisées
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($character['name']); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Messages d'alerte -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- En-tête du personnage -->
        <div class="character-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($character['profile_photo']): ?>
                        <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" 
                             alt="Photo de <?php echo htmlspecialchars($character['name']); ?>" 
                             class="character-photo">
                    <?php else: ?>
                        <div class="character-photo bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="character-info">
                        <h1 class="mb-2">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($character['name']); ?>
                        </h1>
                        <p class="mb-1">
                            <strong>Niveau <?php echo $character['level']; ?></strong> 
                            <?php echo htmlspecialchars($characterDetails['class_name']); ?> 
                            <?php echo htmlspecialchars($characterDetails['race_name']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-heart text-danger me-1"></i>
                            <span class="current-hp"><?php echo $character['hit_points_current']; ?></span> / 
                            <span class="max-hp"><?php echo $character['hit_points_max']; ?></span> PV
                            <span class="ms-3">
                                <i class="fas fa-star text-warning me-1"></i>
                                <span class="current-xp"><?php echo $character['experience_points']; ?></span> XP
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <?php if ($dm_campaign_id): ?>
                        <a href="view_campaign.php?id=<?php echo $dm_campaign_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                        </a>
                    <?php else: ?>
                        <a href="characters.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="character-stats">
            <div class="row">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['strength']; ?></div>
                        <div class="stat-label">Force</div>
                        <div class="stat-modifier"><?php echo $character['strength_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['strength_modifier']; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['dexterity']; ?></div>
                        <div class="stat-label">Dextérité</div>
                        <div class="stat-modifier"><?php echo $character['dexterity_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['dexterity_modifier']; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['constitution']; ?></div>
                        <div class="stat-label">Constitution</div>
                        <div class="stat-modifier"><?php echo $character['constitution_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['constitution_modifier']; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['intelligence']; ?></div>
                        <div class="stat-label">Intelligence</div>
                        <div class="stat-modifier"><?php echo $character['intelligence_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['intelligence_modifier']; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['wisdom']; ?></div>
                        <div class="stat-label">Sagesse</div>
                        <div class="stat-modifier"><?php echo $character['wisdom_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['wisdom_modifier']; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $character['charisma']; ?></div>
                        <div class="stat-label">Charisme</div>
                        <div class="stat-modifier"><?php echo $character['charisma_modifier'] >= 0 ? '+' : ''; ?><?php echo $character['charisma_modifier']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <?php if ($canModifyHP): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-sword text-danger me-2"></i>Infliger des Dégâts</h6>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(1)">-1</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(5)">-5</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(10)">-10</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(20)">-20</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-heart text-success me-2"></i>Appliquer des Soins</h6>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(1)">+1</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(5)">+5</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(10)">+10</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(20)">+20</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gestion de l'expérience -->
        <?php if ($canModifyHP): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-minus text-danger me-2"></i>Retirer des Points d'Expérience</h6>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-100)">-100</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-500)">-500</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-1000)">-1000</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-plus text-success me-2"></i>Ajouter des Points d'Expérience</h6>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(100)">+100</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(500)">+500</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(1000)">+1000</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Équipement -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt me-2"></i>Équipement</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filtres d'équipement -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="filter-checkbox">
                                        <input type="checkbox" id="filter-weapons" checked>
                                        <label for="filter-weapons">Armes</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="checkbox" id="filter-armor" checked>
                                        <label for="filter-armor">Armures</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="checkbox" id="filter-tools" checked>
                                        <label for="filter-tools">Outils</label>
                                    </div>
                                    <div class="filter-checkbox">
                                        <input type="checkbox" id="filter-misc" checked>
                                        <label for="filter-misc">Divers</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                        <i class="fas fa-undo me-1"></i>Réinitialiser
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau d'équipement -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="sortable-header" onclick="sortTable(0)">
                                            Nom <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable-header" onclick="sortTable(1)">
                                            Type <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable-header" onclick="sortTable(2)">
                                            Type précis <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th>État</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($equipment as $item): ?>
                                    <tr data-item-type="<?php echo $item['item_type']; ?>">
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_subtype'] ?? ''); ?></td>
                                        <td>
                                            <?php if ($item['equipped']): ?>
                                                <span class="badge bg-success">Équipé</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non équipé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['equipped']): ?>
                                                <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $character_id; ?>, '<?php echo addslashes($item['item_name']); ?>')">
                                                    <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $character_id; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo $item['item_type']; ?>', '<?php echo $item['equipped_slot'] ?? 'main_hand'; ?>')">
                                                    <i class="fas fa-hand-rock me-1"></i>Équiper
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-warning btn-sm ms-1" onclick="dropItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>')">
                                                <i class="fas fa-trash me-1"></i>Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capacités -->
        <?php if (!empty($capabilities)): ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-magic me-2"></i>Capacités</h5>
                    </div>
                    <div class="card-body">
                        <div class="capabilities-list">
                            <?php foreach ($capabilities as $capability): ?>
                            <div class="capability-item" data-capability='<?php echo json_encode($capability); ?>'>
                                <div class="capability-header">
                                    <h6><?php echo htmlspecialchars($capability['name']); ?></h6>
                                </div>
                                <div class="capability-description">
                                    <?php echo htmlspecialchars($capability['description']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Détails de la Capacité</h5>
                    </div>
                    <div class="card-body">
                        <div id="capability-detail">
                            <p class="text-muted">Sélectionnez une capacité pour voir ses détails.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <!-- Modal de transfert d'objets -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transférer un Objet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="transferForm">
                        <input type="hidden" id="transferItemId" name="item_id">
                        <input type="hidden" id="transferCurrentOwnerType" name="current_owner_type">
                        <input type="hidden" id="transferSource" name="source">
                        
                        <div class="mb-3">
                            <label class="form-label">Objet à transférer</label>
                            <p class="form-control-plaintext" id="transferItemName"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Propriétaire actuel</label>
                            <p class="form-control-plaintext" id="transferCurrentOwner"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transferTarget" class="form-label">Nouveau propriétaire</label>
                            <select class="form-select" id="transferTarget" name="target" required>
                                <option value="">Sélectionner une cible...</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="confirmTransfer()">
                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'upload de photo -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer la Photo de Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="photoFile" class="form-label">Sélectionner une image</label>
                        <input type="file" class="form-control" id="photoFile" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF. Taille maximale: 5MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="uploadPhoto()">
                        <i class="fas fa-upload me-1"></i>Uploader
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jdrmj.js"></script>
</body>
</html>
