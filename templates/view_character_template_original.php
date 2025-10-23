<?php
/**
 * Template pour l'affichage d'une feuille de personnage
 * Reprend le style d'affichage de l'ancienne page
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
    <style>
        .character-sheet {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .hp-display, .ac-display, .xp-display {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .info-section h3 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .clickable-xp {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .clickable-xp:hover {
            color: #0d6efd !important;
        }
        
        .capability-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .capability-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .capability-item.active {
            background: #e3f2fd;
            border-left-color: #2196f3;
        }
        
        .capability-header h6 {
            margin: 0;
            color: #495057;
        }
        
        .capability-description {
            line-height: 1.5;
        }
        
        .capability-item .text-primary {
            color: #007bff !important;
        }
        
        .capability-item .text-success {
            color: #28a745 !important;
        }
        
        .capability-item .text-warning {
            color: #ffc107 !important;
        }
        
        .capabilities-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        #capability-detail h6 {
            color: #0d6efd;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($character['name']); ?>
            </h1>
            <div>
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

        <div class="character-sheet">
            <!-- En-tête du personnage -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="me-3 position-relative">
                            <?php if (!empty($character['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($character['name']); ?>" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user text-white" style="font-size: 2.5rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($canModifyHP): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="bottom: -5px; right: -5px;" data-bs-toggle="modal" data-bs-target="#photoModal" title="Changer la photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($character['name']); ?></h2>
                            <p class="text-muted">
                                <?php echo htmlspecialchars($characterDetails['race_name']); ?> 
                                <?php echo htmlspecialchars($characterDetails['class_name']); ?> 
                                niveau <?php echo $character['level']; ?>
                            </p>
                            <?php if ($characterDetails['background_name']): ?>
                                <p><strong>Historique:</strong> <?php echo htmlspecialchars($characterDetails['background_name']); ?></p>
                            <?php endif; ?>
                            <?php if ($character['alignment']): ?>
                                <p><strong>Alignement:</strong> <?php echo htmlspecialchars($character['alignment']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="hp-display current-hp"><?php echo $character['hit_points_current']; ?></div>
                                <div class="stat-label">Points de Vie</div>
                                <small class="text-muted max-hp">/ <?php echo $character['hit_points_max']; ?></small>
                                <?php if ($canModifyHP): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#hpModal" title="Gérer les points de vie">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="ac-display"><?php echo $armorClass; ?></div>
                                <div class="stat-label">Classe d'Armure</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <?php if ($canModifyHP): ?>
                                    <div class="xp-display clickable-xp current-xp" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience" style="cursor: pointer;"><?php echo number_format($character['experience_points']); ?></div>
                                <?php else: ?>
                                    <div class="xp-display current-xp"><?php echo number_format($character['experience_points']); ?></div>
                                <?php endif; ?>
                                <div class="stat-label">Exp.</div>
                                <small class="text-muted">Niveau <?php echo $character['level']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caractéristiques -->
            <div class="info-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                
                <!-- Tableau des caractéristiques -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 20%;">Type</th>
                                <th style="width: 13.33%;">Force</th>
                                <th style="width: 13.33%;">Dextérité</th>
                                <th style="width: 13.33%;">Constitution</th>
                                <th style="width: 13.33%;">Intelligence</th>
                                <th style="width: 13.33%;">Sagesse</th>
                                <th style="width: 13.33%;">Charisme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Caractéristiques de base -->
                            <tr>
                                <td><strong>Caractéristiques de base</strong></td>
                                <td><strong><?php echo $character['strength']; ?></strong></td>
                                <td><strong><?php echo $character['dexterity']; ?></strong></td>
                                <td><strong><?php echo $character['constitution']; ?></strong></td>
                                <td><strong><?php echo $character['intelligence']; ?></strong></td>
                                <td><strong><?php echo $character['wisdom']; ?></strong></td>
                                <td><strong><?php echo $character['charisma']; ?></strong></td>
                            </tr>
                            <!-- Bonus raciaux -->
                            <tr>
                                <td><strong>Bonus raciaux</strong></td>
                                <td><span class="text-success"><?php echo ($characterDetails['strength_bonus'] > 0 ? '+' : '') . $characterDetails['strength_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['dexterity_bonus'] > 0 ? '+' : '') . $characterDetails['dexterity_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['constitution_bonus'] > 0 ? '+' : '') . $characterDetails['constitution_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['intelligence_bonus'] > 0 ? '+' : '') . $characterDetails['intelligence_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['wisdom_bonus'] > 0 ? '+' : '') . $characterDetails['wisdom_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['charisma_bonus'] > 0 ? '+' : '') . $characterDetails['charisma_bonus']; ?></span></td>
                            </tr>
                            <!-- Modificateurs -->
                            <tr class="table-primary">
                                <td><strong>Modificateurs</strong></td>
                                <td><strong><?php echo ($character['strength_modifier'] >= 0 ? '+' : '') . $character['strength_modifier']; ?></strong></td>
                                <td><strong><?php echo ($character['dexterity_modifier'] >= 0 ? '+' : '') . $character['dexterity_modifier']; ?></strong></td>
                                <td><strong><?php echo ($character['constitution_modifier'] >= 0 ? '+' : '') . $character['constitution_modifier']; ?></strong></td>
                                <td><strong><?php echo ($character['intelligence_modifier'] >= 0 ? '+' : '') . $character['intelligence_modifier']; ?></strong></td>
                                <td><strong><?php echo ($character['wisdom_modifier'] >= 0 ? '+' : '') . $character['wisdom_modifier']; ?></strong></td>
                                <td><strong><?php echo ($character['charisma_modifier'] >= 0 ? '+' : '') . $character['charisma_modifier']; ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions rapides -->
            <?php if ($canModifyHP): ?>
            <div class="info-section">
                <h3><i class="fas fa-bolt me-2"></i>Actions Rapides</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-sword text-danger me-2"></i>Infliger des Dégâts</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(1)">-1</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(5)">-5</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(10)">-10</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(20)">-20</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-heart text-success me-2"></i>Appliquer des Soins</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(1)">+1</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(5)">+5</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(10)">+10</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(20)">+20</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-minus text-danger me-2"></i>Retirer des Points d'Expérience</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-100)">-100</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-500)">-500</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-1000)">-1000</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-plus text-success me-2"></i>Ajouter des Points d'Expérience</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(100)">+100</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(500)">+500</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(1000)">+1000</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Équipement -->
            <div class="info-section">
                <h3><i class="fas fa-shield-alt me-2"></i>Équipement</h3>
                
                <!-- Filtres d'équipement -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filter-weapons" checked>
                            <label class="form-check-label" for="filter-weapons">Armes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filter-armor" checked>
                            <label class="form-check-label" for="filter-armor">Armures</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filter-tools" checked>
                            <label class="form-check-label" for="filter-tools">Outils</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filter-misc" checked>
                            <label class="form-check-label" for="filter-misc">Divers</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                            <i class="fas fa-undo me-1"></i>Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Tableau d'équipement -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th onclick="sortTable(0)" style="cursor: pointer;">
                                    Nom <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th onclick="sortTable(1)" style="cursor: pointer;">
                                    Type <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th onclick="sortTable(2)" style="cursor: pointer;">
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

            <!-- Capacités -->
            <?php if (!empty($capabilities)): ?>
            <div class="info-section">
                <h3><i class="fas fa-magic me-2"></i>Capacités</h3>
                <div class="row">
                    <div class="col-md-6">
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
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
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
