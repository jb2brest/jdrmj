<?php
/**
 * Template pour la vue d'un lieu
 * Version refactorisée avec séparation HTML/PHP
 */

// Extraire les variables du template
extract($template_vars ?? []);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($place['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/view_place.css" rel="stylesheet">
    
    <style>
    /* Styles pour les pions d'objets */
    .object-token {
        position: absolute;
        width: 24px;
        height: 24px;
        background: linear-gradient(45deg, #FFD700, #FFA500);
        border: 2px solid #FF8C00;
        border-radius: 4px;
        cursor: pointer;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    .object-token:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.4);
    }

    /* Styles pour les icônes des pions d'objets */
    .object-token .fa-question {
        color: #8B4513 !important;
        font-weight: bold;
    }
    .object-token .fa-flask {
        color: #dc3545 !important;
    }
    .object-token .fa-magic {
        color: #0dcaf0 !important;
    }
    .object-token .fa-sword {
        color: #dc3545 !important;
    }
    .object-token .fa-shield-alt {
        color: #198754 !important;
    }
    .object-token .fa-envelope {
        color: #0d6efd !important;
    }
    .object-token .fa-coins {
        color: #ffc107 !important;
    }
    .object-token .fa-box {
        color: #6c757d !important;
    }
    </style>
</head>
<body>

<?php if (!$isModal): ?>
    <?php include 'includes/navbar.php'; ?>
<?php endif; ?>

<div class="container mt-4">
    <!-- Messages de succès/erreur -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Informations du joueur actuel -->
    <?php if ($currentPlayer && isset($currentPlayer['username'])): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle me-2"></i>
                <span>Vous êtes <strong><?php echo htmlspecialchars($currentPlayer['username']); ?></strong>
                <?php if (isset($currentPlayer['character_name']) && $currentPlayer['character_name']): ?>
                    avec le personnage <strong><?php echo htmlspecialchars($currentPlayer['character_name']); ?></strong>
                <?php endif; ?>
                </span>
            </div>
            <div class="d-flex gap-2">
                <?php if (isset($currentPlayer['character_id']) && $currentPlayer['character_id']): ?>
                    <a href="view_character.php?id=<?php echo (int)$currentPlayer['character_id']; ?>&dm_campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-file-alt me-1"></i>Ma feuille de personnage
                    </a>
                    <?php if (isset($currentPlayer['class_id']) && $currentPlayer['class_id'] && Character::canCastSpells($currentPlayer['class_id'])): ?>
                        <a href="grimoire.php?id=<?php echo (int)$currentPlayer['character_id']; ?>" class="btn btn-info" target="_blank">
                            <i class="fas fa-book-open me-1"></i>Mon Grimoire
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- En-tête du lieu -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center">
                <h1 class="me-3"><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
                <?php if (hasCampaignId($place)): ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-dice-d20 me-1"></i>Campagne: <?php echo htmlspecialchars($place['campaign_title']); ?>
                        <span class="ms-3"><i class="fas fa-user-tie me-1"></i>MJ: <?php echo htmlspecialchars($place['dm_username']); ?></span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if ($canEdit): ?>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSceneModal">
                    <i class="fas fa-edit me-1"></i>Modifier le lieu
                </button>
            <?php endif; ?>
            <a href="view_campaign.php?id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour à la campagne
            </a>
        </div>
    </div>

    <!-- Jets de dés - Pleine largeur -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dice me-2"></i>Jets de dés</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Sélection des dés et résultats -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Choisir un dé :</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="4" title="Dé à 4 faces">
                                    <i class="fas fa-dice-d4"></i> D4
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="6" title="Dé à 6 faces">
                                    <i class="fas fa-dice"></i> D6
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="8" title="Dé à 8 faces">
                                    <i class="fas fa-dice-d8"></i> D8
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="10" title="Dé à 10 faces">
                                    <i class="fas fa-dice-d10"></i> D10
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="12" title="Dé à 12 faces">
                                    <i class="fas fa-dice-d12"></i> D12
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="20" title="Dé à 20 faces">
                                    <i class="fas fa-dice-d20"></i> D20
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="100" title="Dé à 100 faces">
                                    <i class="fas fa-dice-d20"></i> D100
                                </button>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="dice-quantity" class="form-label">Nombre de dés :</label>
                                    <input type="number" class="form-control" id="dice-quantity" min="1" max="10" value="1">
                                </div>
                                <div class="col-6">
                                    <label for="dice-modifier" class="form-label">Modificateur :</label>
                                    <input type="number" class="form-control" id="dice-modifier" value="0">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="roll-dice-btn" disabled>
                                    <i class="fas fa-play me-2"></i>Lancer les dés
                                </button>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="hide-dice-roll" <?php echo $isOwnerDM ? '' : 'disabled'; ?>>
                                    <label class="form-check-label" for="hide-dice-roll">
                                        <small>Masquer ce jet</small>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Zone de résultats sous le bouton -->
                            <div class="mt-3">
                                <h6 class="mb-3">Résultats :</h6>
                                <div id="dice-results" class="border rounded p-3 bg-light" style="min-height: 120px;">
                                    <div class="text-muted text-center">
                                        <i class="fas fa-dice fa-2x mb-2"></i>
                                        <p class="mb-0">Sélectionnez un dé et lancez !</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Historique des jets -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Historique des jets :</h6>
                            <div id="dice-history" class="border rounded" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-history fa-lg mb-2"></i>
                                    <p class="mb-0 small">Chargement de l'historique...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="row">
        <!-- Colonne gauche - Plan du lieu -->
        <div class="col-lg-8">

            <!-- Plan du lieu -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Plan du lieu</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editMapForm">
                            <i class="fas fa-edit me-1"></i>Modifier le plan
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($isOwnerDM): ?>
                        <div class="collapse mb-3" id="editMapForm">
                            <div class="card card-body">
                                <h6>Modifier le plan du lieu</h6>
                                <form method="POST" enctype="multipart/form-data" class="row g-3">
                                    <input type="hidden" name="action" value="update_map">
                                    <div class="col-12">
                                        <label class="form-label">Téléverser un plan (image)</label>
                                        <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes du MJ</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Notes internes sur cette lieu..."><?php echo htmlspecialchars($place['notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Enregistrer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($place['map_url']) && file_exists($place['map_url'])): ?>
                        <div class="position-relative" style="overflow: visible;">
                            <!-- Zone du plan avec pions -->
                            <div id="mapContainer" class="position-relative" style="display: inline-block; overflow: visible;">
                                <img id="mapImage" src="<?php echo htmlspecialchars($place['map_url']); ?>" class="img-fluid rounded" alt="Plan du lieu" style="max-height: 500px; cursor: crosshair;">
                                
                                <!-- Zone des pions sur le côté -->
                                <div id="tokenSidebar" class="position-absolute" style="right: -120px; top: 0; width: 100px; height: 500px; border: 2px dashed #ccc; border-radius: 8px; background: rgba(248, 249, 250, 0.8); padding: 10px; overflow-y: auto; z-index: 10;">
                                    <div class="text-center mb-2">
                                        <small class="text-muted">Pions</small>
                                    </div>
                                    
                                    <!-- Pions des joueurs -->
                                    <?php foreach ($placePlayers as $player): ?>
                                        <?php 
                                        $tokenKey = 'player_' . $player['player_id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $displayName = $player['character_name'] ?: $player['username'];
                                        $imageUrl = !empty($player['profile_photo']) ? $player['profile_photo'] : 'images/default_character.png';
                                        ?>
                                        <div class="token" 
                                             data-token-type="player" 
                                             data-entity-id="<?php echo $player['player_id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #007bff; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($displayName); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des PNJ -->
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <?php 
                                        $tokenKey = 'npc_' . $npc['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        // Priorité : characters.profile_photo, puis place_npcs.profile_photo, avec vérification d'existence
                                        $imageUrl = 'images/default_npc.png';
                                        if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                            $imageUrl = $npc['character_profile_photo'];
                                        } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                            $imageUrl = $npc['profile_photo'];
                                        }
                                        ?>
                                        <div class="token" 
                                             data-token-type="npc" 
                                             data-entity-id="<?php echo $npc['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #28a745; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($npc['name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des monstres -->
                                    <?php foreach ($placeMonsters as $monster): ?>
                                        <?php 
                                        $tokenKey = 'monster_' . $monster['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $imageUrl = !empty($monster['image_url']) ? $monster['image_url'] : 'images/default_monster.png';
                                        ?>
                                        <div class="token" 
                                             data-token-type="monster" 
                                             data-entity-id="<?php echo $monster['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #dc3545; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($monster['name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des objets -->
                                    <?php foreach ($placeObjects as $object): ?>
                                        <?php 
                                        $tokenKey = 'object_' . $object['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        
                                        // Icône selon le type et l'identification
                                        $icon_class = 'fa-box';
                                        $icon_color = '#6c757d';
                                        
                                        if (!$object['is_identified']) {
                                            $icon_class = 'fa-question';
                                            $icon_color = '#8B4513';
                                        } else {
                                            switch ($object['object_type']) {
                                                case 'potion':
                                                    $icon_class = 'fa-flask';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'magical_item':
                                                    $icon_class = 'fa-magic';
                                                    $icon_color = '#0dcaf0';
                                                    break;
                                                case 'weapon':
                                                    $icon_class = 'fa-sword';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'armor':
                                                    $icon_class = 'fa-shield-alt';
                                                    $icon_color = '#198754';
                                                    break;
                                                case 'scroll':
                                                    $icon_class = 'fa-envelope';
                                                    $icon_color = '#0d6efd';
                                                    break;
                                                case 'treasure':
                                                    $icon_class = 'fa-coins';
                                                    $icon_color = '#ffc107';
                                                    break;
                                                default:
                                                    $icon_class = 'fa-box';
                                                    $icon_color = '#6c757d';
                                            }
                                        }
                                        ?>
                                        <div class="token object-token"
                                             data-token-type="object"
                                             data-entity-id="<?php echo $object['id']; ?>"
                                             data-object-id="<?php echo $object['id']; ?>"
                                             data-object-name="<?php echo htmlspecialchars($object['display_name']); ?>"
                                             data-object-type="<?php echo $object['object_type']; ?>"
                                             data-is-identified="<?php echo $object['is_identified'] ? 'true' : 'false'; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 24px; height: 24px; margin: 2px; display: flex; align-items: center; justify-content: center; cursor: move; border: 2px solid #FF8C00; border-radius: 4px; background: linear-gradient(45deg, #FFD700, #FFA500); box-shadow: 0 2px 4px rgba(0,0,0,0.3); font-size: 12px; color: <?php echo $icon_color; ?>; font-weight: bold;"
                                             title="<?php echo htmlspecialchars($object['display_name']); ?>">
                                            <i class="fas <?php echo $icon_class; ?>"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-map fa-3x mb-3"></i>
                            <p>Aucun plan disponible pour ce lieu</p>
                            <?php if ($isOwnerDM): ?>
                                <p class="small">Utilisez le bouton "Modifier le plan" pour ajouter une carte</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Notes du lieu -->
            <?php if (!empty($place['notes'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes du lieu</h5>
                    </div>
                    <div class="card-body">
                        <div class="notes-content"><?php echo nl2br(htmlspecialchars($place['notes'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Colonne droite - Entités du lieu -->
        <div class="col-lg-4">

            <!-- Joueurs présents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Joueurs présents</h5>
                    <?php if ($canEdit && hasCampaignId($place)): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPlayerModal">
                            <i class="fas fa-plus me-1"></i>Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placePlayers)): ?>
                        <p class="text-muted mb-0">Aucun joueur présent</p>
                    <?php else: ?>
                        <div class="players-list">
                            <?php foreach ($placePlayers as $player): ?>
                                <div class="player-item d-flex align-items-center p-2 border-bottom">
                                    <div class="player-avatar me-3">
                                        <?php if ($player['character_id'] && $player['profile_photo']): ?>
                                            <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="player-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($player['username']); ?></div>
                                        <?php if ($player['character_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($player['character_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($canEdit): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removePlayer(<?php echo $player['player_id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PNJ présents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>PNJ présents</h5>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNpcModal">
                            <i class="fas fa-plus me-1"></i>Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeNpcs)): ?>
                        <p class="text-muted mb-0">Aucun PNJ présent</p>
                    <?php else: ?>
                        <div class="npcs-list">
                            <?php foreach ($placeNpcs as $npc): ?>
                                <div class="npc-item d-flex align-items-center p-2 border-bottom">
                                    <div class="npc-avatar me-3">
                                        <?php if ($npc['profile_photo']): ?>
                                            <img src="<?php echo htmlspecialchars($npc['profile_photo']); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user-tie text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="npc-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($npc['name']); ?></div>
                                        <?php if ($npc['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-info" onclick="toggleNpcVisibility(<?php echo $npc['id']; ?>)">
                                            <i class="fas fa-eye<?php echo $npc['is_visible'] ? '' : '-slash'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="toggleNpcIdentification(<?php echo $npc['id']; ?>)">
                                            <i class="fas fa-search<?php echo $npc['is_identified'] ? '' : '-plus'; ?>"></i>
                                        </button>
                                        <?php if ($isOwnerDM): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeNpc(<?php echo $npc['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Monstres présents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-dragon me-2"></i>Monstres présents</h5>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMonsterModal">
                            <i class="fas fa-plus me-1"></i>Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeMonsters)): ?>
                        <p class="text-muted mb-0">Aucun monstre présent</p>
                    <?php else: ?>
                        <div class="monsters-list">
                            <?php foreach ($placeMonsters as $monster): ?>
                                <div class="monster-item d-flex align-items-center p-2 border-bottom">
                                    <div class="monster-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($monster['type']); ?> - CR <?php echo $monster['challenge_rating']; ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-info" onclick="toggleMonsterVisibility(<?php echo $monster['id']; ?>)">
                                            <i class="fas fa-eye<?php echo $monster['is_visible'] ? '' : '-slash'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="toggleMonsterIdentification(<?php echo $monster['id']; ?>)">
                                            <i class="fas fa-search<?php echo $monster['is_identified'] ? '' : '-plus'; ?>"></i>
                                        </button>
                                        <?php if ($isOwnerDM): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeMonster(<?php echo $monster['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Objets présents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Objets présents</h5>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addObjectModal">
                            <i class="fas fa-plus me-1"></i>Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeObjects)): ?>
                        <p class="text-muted mb-0">Aucun objet présent</p>
                    <?php else: ?>
                        <div class="objects-list">
                            <?php foreach ($placeObjects as $object): ?>
                                <div class="object-item d-flex align-items-center p-2 border-bottom">
                                    <div class="object-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($object['display_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($object['object_type']); ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-info" onclick="toggleObjectVisibility(<?php echo $object['id']; ?>)">
                                            <i class="fas fa-eye<?php echo $object['is_visible'] ? '' : '-slash'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="toggleObjectIdentification(<?php echo $object['id']; ?>)">
                                            <i class="fas fa-search<?php echo $object['is_identified'] ? '' : '-plus'; ?>"></i>
                                        </button>
                                        <?php if ($isOwnerDM): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeObject(<?php echo $object['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accès disponibles -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-door-open me-2"></i>Accès disponibles</span>
                    <?php if ($canEdit): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAccessModal">
                            <i class="fas fa-plus me-1"></i>Ajouter un Accès
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeAccesses)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-door-closed fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucun accès configuré pour ce lieu</p>
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createAccessModal">
                                    <i class="fas fa-plus me-1"></i>Créer le premier accès
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($placeAccesses as $access): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <i class="<?= $access->getStatusIcon() ?> me-1 <?= $access->getStatusClass() ?>"></i>
                                                    <?= htmlspecialchars($access->name) ?>
                                                </h6>
                                                <span class="badge <?= $access->is_visible ? 'bg-success' : 'bg-secondary' ?>">
                                                    <i class="fas fa-eye me-1"></i><?= $access->is_visible ? 'Visible' : 'Caché' ?>
                                                </span>
                                            </div>
                                            
                                            <p class="card-text text-muted small mb-2">
                                                <strong>Vers:</strong> <?= htmlspecialchars($access->to_place_name) ?>
                                            </p>
                                            
                                            <?php if ($access->description): ?>
                                                <p class="card-text small"><?= htmlspecialchars($access->description) ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge <?= $access->is_open ? 'bg-success' : 'bg-warning' ?>">
                                                    <i class="fas fa-<?= $access->is_open ? 'unlock' : 'lock' ?> me-1"></i><?= $access->is_open ? 'Ouvert' : 'Fermé' ?>
                                                </span>
                                                <?php if ($access->is_trapped): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Piégé
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($access->is_trapped && $access->trap_description): ?>
                                                <div class="mt-2">
                                                    <small class="text-danger">
                                                        <i class="fas fa-bomb me-1"></i>
                                                        <strong>Piège:</strong> <?= htmlspecialchars($access->trap_description) ?>
                                                        <?php if ($access->trap_difficulty): ?>
                                                            (DD <?= $access->trap_difficulty ?>)
                                                        <?php endif; ?>
                                                        <?php if ($access->trap_damage): ?>
                                                            - <?= htmlspecialchars($access->trap_damage) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($canEdit): ?>
                                                <div class="mt-3 d-flex gap-2">
                                                    <a href="view_place.php?id=<?= $access->to_place_id ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt me-1"></i>Aller vers ce lieu
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#editAccessModal"
                                                            data-access-id="<?= $access->id ?>"
                                                            data-access-name="<?= htmlspecialchars($access->name) ?>"
                                                            data-access-description="<?= htmlspecialchars($access->description) ?>"
                                                            data-access-to-place-id="<?= $access->to_place_id ?>"
                                                            data-access-is-visible="<?= $access->is_visible ?>"
                                                            data-access-is-open="<?= $access->is_open ?>"
                                                            data-access-is-trapped="<?= $access->is_trapped ?>"
                                                            data-access-trap-description="<?= htmlspecialchars($access->trap_description) ?>"
                                                            data-access-trap-difficulty="<?= $access->trap_difficulty ?>"
                                                            data-access-trap-damage="<?= htmlspecialchars($access->trap_damage) ?>"
                                                            data-access-position-x="<?= $access->position_x ?>"
                                                            data-access-position-y="<?= $access->position_y ?>"
                                                            data-access-is-on-map="<?= $access->is_on_map ?>"
                                                            title="Modifier l'accès">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccessModal"
                                                            data-access-id="<?= $access->id ?>"
                                                            data-access-name="<?= htmlspecialchars($access->name) ?>"
                                                            title="Supprimer l'accès">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
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
</div>

<!-- Modals -->
<?php include 'templates/view_place_modals.php'; ?>

<!-- Scripts -->
<?php if (!$isModal): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/jdrmj.js"></script>
    
    <script>
        // Variables JavaScript globales
        window.placeId = <?php echo json_encode($js_vars['placeId']); ?>;
        window.canEdit = <?php echo json_encode($js_vars['canEdit']); ?>;
        window.isOwnerDM = <?php echo json_encode($js_vars['isOwnerDM']); ?>;
        window.tokenPositions = <?php echo json_encode($js_vars['tokenPositions']); ?>;
        window.campaignId = <?php echo json_encode($js_vars['campaignId']); ?>;
    </script>
<?php endif; ?>

</body>
</html>
