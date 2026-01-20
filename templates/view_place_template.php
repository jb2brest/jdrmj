<?php
/**
 * Template pour la vue d'une pièce
 * Version restaurée
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
    <link rel="icon" type="image/png" href="images/logo.png">
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
    .object-token .fa-crosshairs {
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
<body data-place-id="<?php echo $place['id']; ?>">

<?php if (!$isModal): ?>
    <?php include_once 'includes/navbar.php'; ?>
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

    <!-- Fil d'ariane -->
    <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <?php if (!empty($crumb['active'])): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($crumb['name']); ?></li>
                    <?php elseif (!empty($crumb['url'])): ?>
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($crumb['name']); ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item text-muted"><?php echo htmlspecialchars($crumb['name']); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>

    <!-- En-tête de la pièce -->
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
                    <i class="fas fa-edit me-1"></i>Modifier la pièce
                </button>
            <?php endif; ?>
            <?php if (!empty($place['region_id'])): ?>
                <a href="view_region.php?id=<?php echo (int)$place['region_id']; ?>" class="btn btn-outline-info">
                    <i class="fas fa-map-marked-alt me-1"></i>Afficher la région
                </a>
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
                    <!-- Sélection de la campagne pour les lancers de dés -->
                    <?php if (!empty($accessibleCampaigns)): ?>
                        <div class="mb-3">
                            <label for="dice-campaign-select" class="form-label">
                                <i class="fas fa-dice-d20 me-1"></i>Campagne pour les lancers de dés :
                            </label>
                            <select class="form-select" id="dice-campaign-select">
                                <?php foreach ($accessibleCampaigns as $campaign): ?>
                                    <option value="<?php echo (int)$campaign['id']; ?>" 
                                            <?php echo ($defaultCampaignId && $campaign['id'] == $defaultCampaignId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campaign['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
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
        <!-- Colonne gauche - Plan de la pièce -->
        <div class="col-lg-8">

            <!-- Plan de la pièce -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Plan de la pièce</span>
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
                                <h6>Modifier le plan de la pièce</h6>
                                <form enctype="multipart/form-data" class="row g-3" id="uploadMapForm">
                                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                                    <div class="col-12">
                                        <label class="form-label">Téléverser un plan (image)</label>
                                        <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif" id="planFileInput">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes du MJ</label>
                                        <textarea class="form-control" name="notes" id="placeNotes" rows="3" placeholder="Notes internes sur cette pièce..."><?php echo htmlspecialchars($place['notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="uploadMapButton">
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
                                <img id="mapImage" src="<?php echo htmlspecialchars($place['map_url']); ?>" class="img-fluid rounded" alt="Plan de la pièce" style="max-height: 500px; cursor: crosshair;">
                                
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
                                        $hasCustomImage = !empty($player['profile_photo']) && $player['profile_photo'] !== 'images/default_character.png';
                                        $imageUrl = !empty($player['profile_photo']) ? $player['profile_photo'] : 'images/default_character.png';
                                        $borderColor = $tokenColors[$tokenKey] ?? '#007bff';  // Couleur par défaut: bleu
                                        ?>
                                        <?php if ($hasCustomImage): ?>
                                            <div class="token" 
                                                 data-token-type="player" 
                                                 data-entity-id="<?php echo $player['player_id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                                 title="<?php echo htmlspecialchars($displayName); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="token" 
                                                 data-token-type="player" 
                                                 data-entity-id="<?php echo $player['player_id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-flex; align-items: center; justify-content: center; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-color: <?php echo $borderColor; ?>;"
                                                 title="<?php echo htmlspecialchars($displayName); ?>">
                                                <i class="fas fa-user" style="font-size: 14px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des PNJ -->
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <?php 
                                        $tokenKey = 'npc_' . $npc['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        // Priorité : npcs.profile_photo, puis characters.profile_photo, puis place_npcs.profile_photo, avec vérification d'existence
                                        $imageUrl = 'images/default_npc.png';
                                        $hasCustomImage = false;
                                        if (!empty($npc['npc_profile_photo']) && file_exists($npc['npc_profile_photo'])) {
                                            $imageUrl = $npc['npc_profile_photo'];
                                            $hasCustomImage = true;
                                        } elseif (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                            $imageUrl = $npc['character_profile_photo'];
                                            $hasCustomImage = true;
                                        } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                            $imageUrl = $npc['profile_photo'];
                                            $hasCustomImage = true;
                                        }
                                        $borderColor = $tokenColors[$tokenKey] ?? '#28a745';  // Couleur par défaut: vert
                                        ?>
                                        <?php if ($hasCustomImage): ?>
                                            <div class="token" 
                                                 data-token-type="npc" 
                                                 data-entity-id="<?php echo $npc['id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                                 title="<?php echo htmlspecialchars($npc['name']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="token" 
                                                 data-token-type="npc" 
                                                 data-entity-id="<?php echo $npc['id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-flex; align-items: center; justify-content: center; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-color: <?php echo $borderColor; ?>;"
                                                 title="<?php echo htmlspecialchars($npc['name']); ?>">
                                                <i class="fas fa-user-tie" style="font-size: 14px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des monstres -->
                                    <?php foreach ($placeMonsters as $monster): ?>
                                        <?php 
                                        $tokenKey = 'monster_' . $monster['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        
                                        // Logique d'image pour les monstres (même que view_monster.php)
                                        $imageUrl = 'images/default_monster.png';
                                        $hasCustomImage = false;
                                        
                                        // Essayer d'abord l'image personnalisée si elle existe
                                        if (!empty($monster['image_url']) && file_exists($monster['image_url'])) {
                                            $imageUrl = $monster['image_url'];
                                            $hasCustomImage = true;
                                        } else {
                                            // Sinon, utiliser l'image du monstre par csv_id
                                            if (isset($monster['csv_id']) && $monster['csv_id'] !== null) {
                                                $monsterImagePath = 'images/monstres/' . $monster['csv_id'] . '.jpg';
                                                if (file_exists($monsterImagePath)) {
                                                    $imageUrl = $monsterImagePath;
                                                    $hasCustomImage = true;
                                                }
                                            }
                                        }
                                        $borderColor = $tokenColors[$tokenKey] ?? '#dc3545';  // Couleur par défaut: rouge
                                        ?>
                                        <?php if ($hasCustomImage): ?>
                                            <div class="token" 
                                                 data-token-type="monster" 
                                                 data-entity-id="<?php echo $monster['id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                                 title="<?php echo htmlspecialchars($monster['name']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="token" 
                                                 data-token-type="monster" 
                                                 data-entity-id="<?php echo $monster['id']; ?>"
                                                 data-position-x="<?php echo $position['x']; ?>"
                                                 data-position-y="<?php echo $position['y']; ?>"
                                                 data-border-color="<?php echo $borderColor; ?>"
                                                 style="width: 30px; height: 30px; margin: 2px; display: inline-flex; align-items: center; justify-content: center; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 50%; background-color: <?php echo $borderColor; ?>;"
                                                 title="<?php echo htmlspecialchars($monster['name']); ?>">
                                                <i class="fas fa-dragon" style="font-size: 14px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des objets (seulement les objets visibles) -->
                                    <?php 
                                    // Utiliser $visibleObjectsForMap si disponible, sinon filtrer $placeObjects par is_visible
                                    $objectsForMap = isset($visibleObjectsForMap) ? $visibleObjectsForMap : array_filter($placeObjects, function($obj) { return $obj['is_visible'] == 1; });
                                    foreach ($objectsForMap as $object): ?>
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
                                                case 'poison':
                                                    $icon_class = 'fa-flask';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'magical_item':
                                                    $icon_class = 'fa-magic';
                                                    $icon_color = '#0dcaf0';
                                                    break;
                                                case 'weapon':
                                                    $icon_class = 'fa-crosshairs';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'armor':
                                                    $icon_class = 'fa-shield-alt';
                                                    $icon_color = '#198754';
                                                    break;
                                                case 'letter':
                                                    $icon_class = 'fa-envelope';
                                                    $icon_color = '#0d6efd';
                                                    break;
                                                case 'coins':
                                                case 'bourse':
                                                    $icon_class = 'fa-coins';
                                                    $icon_color = '#ffc107';
                                                    break;
                                                default:
                                                    $icon_class = 'fa-box';
                                                    $icon_color = '#6c757d';
                                            }
                                        }
                                        $borderColor = $tokenColors[$tokenKey] ?? '#FF8C00';  // Couleur par défaut: orange
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
                                             data-border-color="<?php echo $borderColor; ?>"
                                             style="width: 24px; height: 24px; margin: 2px; display: flex; align-items: center; justify-content: center; cursor: move; border: 2px solid <?php echo $borderColor; ?>; border-radius: 4px; background: linear-gradient(45deg, #FFD700, #FFA500); box-shadow: 0 2px 4px rgba(0,0,0,0.3); font-size: 12px; color: <?php echo $icon_color; ?>; font-weight: bold;"
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
                            <p>Aucun plan disponible pour cette pièce</p>
                            <?php if ($isOwnerDM): ?>
                                <p class="small">Utilisez le bouton "Modifier le plan" pour ajouter une carte</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes de la pièce -->
            <?php if (!empty($place['notes'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes de la pièce</h5>
                    </div>
                    <div class="card-body">
                        <div class="notes-content"><?php echo nl2br(htmlspecialchars($place['notes'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Colonne droite - Entités de la pièce -->
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
                                <?php 
                                $tokenKey = 'player_' . $player['player_id'];
                                $borderColor = $tokenColors[$tokenKey] ?? '#007bff';
                                ?>
                                <div class="player-item d-flex align-items-center p-2 border-bottom">
                                    <div class="player-avatar me-3 position-relative">
                                        <?php if ($player['character_id'] && $player['profile_photo']): ?>
                                            <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <!-- Badge de couleur du pion -->
                                        <div class="position-absolute" style="bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: <?php echo $borderColor; ?>; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);" title="Couleur du pion"></div>
                                    </div>
                                    <div class="player-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($player['username']); ?></div>
                                        <?php if ($player['character_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($player['character_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="player-actions">
                                        <?php if ($player['character_id']): ?>
                                            <a href="view_character.php?id=<?php echo $player['character_id']; ?>" class="btn btn-sm btn-outline-primary me-2" title="Voir la fiche">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removePlayer(<?php echo $player['player_id']; ?>)">
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

            <!-- PNJ présents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>PNJ présents</h5>
                    <?php if ($isOwnerDM): ?>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNpcModal">
                                <i class="fas fa-plus me-1"></i>Ajouter
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createNpcAutoModal">
                                <i class="fas fa-magic me-1"></i>Créer (Auto)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeNpcs)): ?>
                        <p class="text-muted mb-0">Aucun PNJ présent</p>
                    <?php else: ?>
                        <div class="npcs-list">
                            <?php foreach ($placeNpcs as $npc): ?>
                                <?php 
                                $tokenKey = 'npc_' . $npc['id'];
                                $borderColor = $tokenColors[$tokenKey] ?? '#28a745';
                                ?>
                                <div class="npc-item d-flex align-items-center p-2 border-bottom">
                                    <div class="npc-avatar me-3 position-relative">
                                        <?php 
                                        // Priorité : npcs.profile_photo, puis characters.profile_photo, puis place_npcs.profile_photo, avec vérification d'existence
                                        $imageUrl = 'images/default_npc.png';
                                        if (!empty($npc['npc_profile_photo']) && file_exists($npc['npc_profile_photo'])) {
                                            $imageUrl = $npc['npc_profile_photo'];
                                        } elseif (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                            $imageUrl = $npc['character_profile_photo'];
                                        } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                            $imageUrl = $npc['profile_photo'];
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                                        <!-- Badge de couleur du pion -->
                                        <div class="position-absolute" style="bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: <?php echo $borderColor; ?>; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);" title="Couleur du pion"></div>
                                    </div>
                                    <div class="npc-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($npc['name']); ?></div>
                                        <?php if ($npc['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="view_npc.php?id=<?php echo $npc['npc_character_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                                <?php 
                                $tokenKey = 'monster_' . $monster['id'];
                                $borderColor = $tokenColors[$tokenKey] ?? '#dc3545';
                                ?>
                                <div class="monster-item d-flex align-items-center p-2 border-bottom">
                                    <div class="monster-avatar me-3 position-relative">
                                        <?php 
                                        // Logique d'image pour les monstres (même que view_monster.php)
                                        $imageUrl = 'images/default_monster.png';
                                        
                                        // Essayer d'abord l'image personnalisée si elle existe
                                        if (!empty($monster['image_url']) && file_exists($monster['image_url'])) {
                                            $imageUrl = $monster['image_url'];
                                        } else {
                                            // Sinon, utiliser l'image du monstre par csv_id
                                            if (isset($monster['csv_id']) && $monster['csv_id'] !== null) {
                                                $monsterImagePath = 'images/monstres/' . $monster['csv_id'] . '.jpg';
                                                if (file_exists($monsterImagePath)) {
                                                    $imageUrl = $monsterImagePath;
                                                } else {
                                                    // Fallback vers l'image par défaut
                                                    $imageUrl = 'images/default_monster.png';
                                                }
                                            } else {
                                                // Fallback vers l'image par défaut
                                                $imageUrl = 'images/default_monster.png';
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                                        <!-- Badge de couleur du pion -->
                                        <div class="position-absolute" style="bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; background: <?php echo $borderColor; ?>; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3);" title="Couleur du pion"></div>
                                    </div>
                                    <div class="monster-info flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($monster['type']); ?> - CR <?php echo $monster['challenge_rating']; ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="view_monster.php?id=<?php echo isset($monster['monster_instance_id']) && $monster['monster_instance_id'] ? $monster['monster_instance_id'] : $monster['monster_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                                <div class="object-item d-flex align-items-center p-2 border-bottom <?php echo !$object['is_visible'] ? 'opacity-50 bg-light' : ''; ?>">
                                    <div class="object-info flex-grow-1">
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($object['display_name']); ?>
                                            <?php if (!$object['is_visible']): ?>
                                                <span class="badge bg-secondary ms-2">Masqué</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($object['object_type']); ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-success" onclick="assignObject(<?php echo $object['id']; ?>, '<?php echo htmlspecialchars($object['display_name']); ?>')" title="Attribuer cet objet">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
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
                    <div class="d-flex gap-2">
                        <?php if ($canEdit && !empty($placeAccesses)): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#moveEntitiesModal">
                                <i class="fas fa-arrows-alt me-1"></i>Déplacer
                            </button>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#teleportEntitiesModal">
                                <i class="fas fa-magic me-1"></i>Téléporter
                            </button>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAccessModal">
                                <i class="fas fa-plus me-1"></i>Ajouter un Accès
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($placeAccesses)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-door-closed fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucun accès configuré pour cette pièce</p>
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
                                                <?php if ($access->from_place_id == $place_id): ?>
                                                    <strong>Vers:</strong> <?= htmlspecialchars($access->to_place_name) ?>
                                                <?php else: ?>
                                                    <strong>Depuis:</strong> <?= htmlspecialchars($access->from_place_name) ?>
                                                <?php endif; ?>
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
                                                    <?php if ($access->from_place_id == $place_id): ?>
                                                        <a href="view_place.php?id=<?= $access->to_place_id ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i>Aller vers cette pièce
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="view_place.php?id=<?= $access->from_place_id ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i>Aller vers cette pièce
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($access->from_place_id == $place_id): ?>
                                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#editAccessModal"
                                                                data-access-id="<?= $access->id ?>"
                                                                data-access-name="<?= htmlspecialchars($access->name) ?>"
                                                                data-access-description="<?= htmlspecialchars($access->description ?? '') ?>"
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
                                                    <?php endif; ?>
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


<!-- Menu contextuel pour changer la couleur des pions -->
<?php include_once 'templates/token_color_menu.php'; ?>

<!-- Modals -->
<?php include_once 'templates/view_place_modals.php'; ?>

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
        
        // Gestion des listes déroulantes dynamiques pour les objets
        document.addEventListener('DOMContentLoaded', function() {
            const objectTypeSelect = document.getElementById('objectType');
            const specificSelection = document.getElementById('specificSelection');
            const specificItemSelect = document.getElementById('specificItem');
            const specificItemLabel = document.getElementById('specificItemLabel');
            const displayNameInput = document.getElementById('objectDisplayName');
            const letterFields = document.getElementById('letterFields');
            const goldFields = document.getElementById('goldFields');
            
            if (objectTypeSelect) {
                objectTypeSelect.addEventListener('change', function() {
                    const selectedType = this.value;
                   
                    // Masquer toutes les sections spécifiques par défaut
                    specificSelection.style.display = 'none';
                    letterFields.style.display = 'none';
                    goldFields.style.display = 'none';
                    specificItemSelect.innerHTML = '<option value="">Chargement...</option>';
                    
                    // Afficher la sélection spécifique selon le type
                    if (['weapon', 'armor', 'magical_item', 'poison'].includes(selectedType)) {
                        specificSelection.style.display = 'block';
                        loadSpecificItems(selectedType);
                    } else if (selectedType === 'letter') {
                        letterFields.style.display = 'block';
                        // Mettre à jour le nom d'affichage pour les lettres
                        displayNameInput.value = 'Lettre';
                    } else if (selectedType === 'bourse') {
                        goldFields.style.display = 'block';
                        // Mettre à jour le nom d'affichage pour l'or
                        displayNameInput.value = 'Pièces de monnaie';
                    }
                });
            }
            
            // Charger les éléments spécifiques selon le type
            function loadSpecificItems(type) {
                const labels = {
                    'weapon': 'Sélectionner une arme',
                    'armor': 'Sélectionner une armure',
                    'magical_item': 'Sélectionner un objet magique',
                    'poison': 'Sélectionner un poison'
                };
                
                specificItemLabel.textContent = labels[type] || 'Sélectionner un élément';
                
                // Charger les données via AJAX
                fetch(`api/get_${type}s.php`)
                    .then(response => response.json())
                    .then(data => {
                        specificItemSelect.innerHTML = '<option value="">Sélectionner...</option>';
                        
                        if (data.success && data.items) {
                            data.items.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.id;
                                option.textContent = item.name;
                                specificItemSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement:', error);
                        specificItemSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    });
            }
            
            // Mettre à jour le nom d'affichage quand un élément spécifique est sélectionné
            if (specificItemSelect) {
                specificItemSelect.addEventListener('change', function() {
                    if (this.value && this.selectedOptions[0]) {
                        displayNameInput.value = this.selectedOptions[0].textContent;
                    }
                });
            }
            
            // Gestion des champs de lettre
            if (letterContent) {
                letterContent.addEventListener('input', function() {
                    updateLetterDisplayName();
                });
            }
            
            if (letterRecipient) {
                letterRecipient.addEventListener('input', function() {
                    updateLetterDisplayName();
                });
            }
            
            if (letterSealed) {
                letterSealed.addEventListener('change', function() {
                    updateLetterDisplayName();
                });
            }
            
            function updateLetterDisplayName() {
                if (objectTypeSelect && objectTypeSelect.value === 'letter') {
                    let letterName = 'Lettre';
                    
                    if (letterRecipient && letterRecipient.value) {
                        letterName += ' pour ' + letterRecipient.value;
                    }
                    
                    if (letterSealed && letterSealed.checked) {
                        letterName += ' (scellée)';
                    }
                    
                    displayNameInput.value = letterName;
                }
            }
            
            // Gestion des champs de l'or
            if (goldCoins) {
                goldCoins.addEventListener('input', function() {
                    updateGoldDisplayName();
                });
            }
            
            if (silverCoins) {
                silverCoins.addEventListener('input', function() {
                    updateGoldDisplayName();
                });
            }
            
            if (copperCoins) {
                copperCoins.addEventListener('input', function() {
                    updateGoldDisplayName();
                });
            }
            
            function updateGoldDisplayName() {
                if (objectTypeSelect && objectTypeSelect.value === 'bourse') {
                    let goldName = 'Pièces de monnaie';
                    let totalGold = 0;
                    
                    if (goldCoins && goldCoins.value) {
                        totalGold += parseInt(goldCoins.value) || 0;
                    }
                    if (silverCoins && silverCoins.value) {
                        totalGold += (parseInt(silverCoins.value) || 0) / 10;
                    }
                    if (copperCoins && copperCoins.value) {
                        totalGold += (parseInt(copperCoins.value) || 0) / 100;
                    }
                    
                    if (totalGold > 0) {
                        goldName += ` (${totalGold.toFixed(2)} PO)`;
                    }
                    
                    displayNameInput.value = goldName;
                }
            }
        });
        
        // Gestion du chargement des régions selon le pays sélectionné dans le modal de création d'accès
        const createAccessCountry = document.getElementById('createAccessCountry');
        const createAccessRegion = document.getElementById('createAccessRegion');
        const createAccessToPlace = document.getElementById('createAccessToPlace');
        
        if (createAccessCountry && createAccessRegion) {
            createAccessCountry.addEventListener('change', function() {
                const countryId = this.value;
                
                // Réinitialiser la liste des régions et des pièces
                createAccessRegion.innerHTML = '<option value="">Sélectionner une région</option>';
                if (createAccessToPlace) {
                    createAccessToPlace.innerHTML = '<option value="">Sélectionner une pièce</option>';
                }
                
                if (!countryId) {
                    return;
                }
                
                // Charger les régions via l'API
                fetch('api/get_regions_by_country.php?country_id=' + countryId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.regions) {
                            data.regions.forEach(function(region) {
                                const option = document.createElement('option');
                                option.value = region.id;
                                option.textContent = region.name;
                                createAccessRegion.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des régions:', error);
                    });
            });
        }
        
        // Gestion du chargement des pièces selon la région sélectionnée dans le modal de création d'accès
        if (createAccessRegion && createAccessToPlace) {
            createAccessRegion.addEventListener('change', function() {
                const regionId = this.value;
                
                // Réinitialiser la liste des pièces
                createAccessToPlace.innerHTML = '<option value="">Sélectionner une pièce</option>';
                
                if (!regionId) {
                    return;
                }
                
                // Charger les pièces via l'API (exclure la pièce actuelle)
                const placeId = window.placeId;
                fetch('api/get_places_by_region.php?region_id=' + regionId + '&exclude_place_id=' + placeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.places) {
                            data.places.forEach(function(place) {
                                const option = document.createElement('option');
                                option.value = place.id;
                                option.textContent = place.title;
                                createAccessToPlace.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des pièces:', error);
                    });
            });
        }
        
        // Gestion de la soumission du formulaire de création d'accès
        const createAccessForm = document.getElementById('createAccessForm');
        if (createAccessForm) {
            createAccessForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                
                // Désactiver le bouton pendant la soumission
                submitButton.disabled = true;
                submitButton.textContent = 'Création...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour afficher le nouvel accès
                        window.location.reload();
                    } else {
                        alert('Erreur : ' + (data.error || 'Erreur inconnue'));
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la création de l\'accès.');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
            });
        }
        
        // Fonction pour décoder les entités HTML
        function decodeHtmlEntities(text) {
            if (!text) return '';
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        }
        
        // Gestion de l'ouverture du modal d'édition d'accès
        const editAccessModal = document.getElementById('editAccessModal');
        if (editAccessModal) {
            editAccessModal.addEventListener('shown.bs.modal', function(event) {
                // Récupérer le bouton qui a déclenché le modal
                const button = event.relatedTarget;
                if (button) {
                    // Récupérer les données depuis les attributs data-*
                    const accessId = button.getAttribute('data-access-id') || '';
                    const accessName = button.getAttribute('data-access-name') || '';
                    // Récupérer la description (peut être null ou vide)
                    let accessDescription = button.getAttribute('data-access-description');
                    if (accessDescription === null || accessDescription === 'null' || accessDescription === '') {
                        accessDescription = '';
                    }
                    const toPlaceId = button.getAttribute('data-access-to-place-id') || '';
                    const isVisibleStr = button.getAttribute('data-access-is-visible') || '0';
                    const isOpenStr = button.getAttribute('data-access-is-open') || '0';
                    const isTrappedStr = button.getAttribute('data-access-is-trapped') || '0';
                    const trapDescription = button.getAttribute('data-access-trap-description') || '';
                    const trapDifficulty = button.getAttribute('data-access-trap-difficulty') || '';
                    const trapDamage = button.getAttribute('data-access-trap-damage') || '';
                    
                    // Convertir les valeurs booléennes
                    const isVisible = isVisibleStr === '1' || isVisibleStr === 1 || isVisibleStr === true;
                    const isOpen = isOpenStr === '1' || isOpenStr === 1 || isOpenStr === true;
                    const isTrapped = isTrappedStr === '1' || isTrappedStr === 1 || isTrappedStr === true;
                    
                    // Décoder les entités HTML
                    const decodedName = decodeHtmlEntities(accessName);
                    const decodedDescription = decodeHtmlEntities(accessDescription);
                    const decodedTrapDescription = decodeHtmlEntities(trapDescription);
                    const decodedTrapDamage = decodeHtmlEntities(trapDamage);
                    
                    // Remplir les champs de base
                    const editAccessId = document.getElementById('editAccessId');
                    const editAccessName = document.getElementById('editAccessName');
                    const editAccessDescription = document.getElementById('editAccessDescription');
                    const editAccessIsVisible = document.getElementById('editAccessIsVisible');
                    const editAccessIsOpen = document.getElementById('editAccessIsOpen');
                    const editAccessIsTrapped = document.getElementById('editAccessIsTrapped');
                    const editTrapDescription = document.getElementById('editTrapDescription');
                    const editTrapDifficulty = document.getElementById('editTrapDifficulty');
                    const editTrapDamage = document.getElementById('editTrapDamage');
                    
                    if (editAccessId) editAccessId.value = accessId;
                    if (editAccessName) editAccessName.value = decodedName;
                    if (editAccessDescription) editAccessDescription.value = decodedDescription;
                    if (editAccessIsVisible) editAccessIsVisible.checked = isVisible;
                    if (editAccessIsOpen) editAccessIsOpen.checked = isOpen;
                    if (editAccessIsTrapped) editAccessIsTrapped.checked = isTrapped;
                    if (editTrapDescription) editTrapDescription.value = decodedTrapDescription;
                    if (editTrapDifficulty) editTrapDifficulty.value = trapDifficulty;
                    if (editTrapDamage) editTrapDamage.value = decodedTrapDamage;
                    
                    // Afficher/masquer les détails du piège
                    const editTrapDetails = document.getElementById('editTrapDetails');
                    if (editTrapDetails) {
                        editTrapDetails.style.display = isTrapped ? 'block' : 'none';
                    }
                    
                    // Charger les informations de la pièce de destination pour pré-remplir pays/région/pièce
                    if (toPlaceId) {
                        fetch('api/get_place_info.php?place_id=' + toPlaceId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.place) {
                                    const place = data.place;
                                    const editAccessCountry = document.getElementById('editAccessCountry');
                                    const editAccessRegion = document.getElementById('editAccessRegion');
                                    const editAccessToPlace = document.getElementById('editAccessToPlace');
                                    
                                    // Charger les régions du pays
                                    if (place.country_id && editAccessCountry) {
                                        editAccessCountry.value = place.country_id;
                                        
                                        // Déclencher le changement pour charger les régions
                                        editAccessCountry.dispatchEvent(new Event('change'));
                                        
                                        // Attendre que les régions soient chargées, puis sélectionner la région
                                        setTimeout(function() {
                                            if (place.region_id && editAccessRegion) {
                                                editAccessRegion.value = place.region_id;
                                                
                                                // Déclencher le changement pour charger les pièces
                                                editAccessRegion.dispatchEvent(new Event('change'));
                                                
                                                // Attendre que les pièces soient chargés, puis sélectionner la pièce
                                                setTimeout(function() {
                                                    if (editAccessToPlace) {
                                                        editAccessToPlace.value = toPlaceId;
                                                    }
                                                }, 300);
                                            }
                                        }, 300);
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors du chargement des informations de la pièce:', error);
                            });
                    }
                }
            });
            
            // Gestion de l'affichage/masquage des détails du piège
            const editAccessIsTrapped = document.getElementById('editAccessIsTrapped');
            const editTrapDetails = document.getElementById('editTrapDetails');
            if (editAccessIsTrapped && editTrapDetails) {
                editAccessIsTrapped.addEventListener('change', function() {
                    editTrapDetails.style.display = this.checked ? 'block' : 'none';
                });
            }
        }
        
        // Gestion du chargement des régions pour le modal d'édition
        const editAccessCountry = document.getElementById('editAccessCountry');
        const editAccessRegion = document.getElementById('editAccessRegion');
        if (editAccessCountry && editAccessRegion) {
            editAccessCountry.addEventListener('change', function() {
                const countryId = this.value;
                editAccessRegion.innerHTML = '<option value="">Sélectionner une région</option>';
                if (!countryId) {
                    return;
                }
                fetch('api/get_regions_by_country.php?country_id=' + countryId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.regions) {
                            data.regions.forEach(function(region) {
                                const option = document.createElement('option');
                                option.value = region.id;
                                option.textContent = region.name;
                                editAccessRegion.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des régions:', error);
                    });
            });
        }
        
        // Gestion du chargement des pièces pour le modal d'édition
        const editAccessRegionSelect = document.getElementById('editAccessRegion');
        const editAccessToPlace = document.getElementById('editAccessToPlace');
        if (editAccessRegionSelect && editAccessToPlace) {
            editAccessRegionSelect.addEventListener('change', function() {
                const regionId = this.value;
                editAccessToPlace.innerHTML = '<option value="">Sélectionner une pièce</option>';
                if (!regionId) {
                    return;
                }
                const placeId = window.placeId;
                fetch('api/get_places_by_region.php?region_id=' + regionId + '&exclude_place_id=' + placeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.places) {
                            data.places.forEach(function(place) {
                                const option = document.createElement('option');
                                option.value = place.id;
                                option.textContent = place.title;
                                editAccessToPlace.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des pièces:', error);
                    });
            });
        }
        
        // Gestion de la soumission du formulaire d'édition d'accès
        const editAccessForm = document.getElementById('editAccessForm');
        if (editAccessForm) {
            editAccessForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                
                // Désactiver le bouton pendant la soumission
                submitButton.disabled = true;
                submitButton.textContent = 'Modification...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour afficher les modifications
                        window.location.reload();
                    } else {
                        alert('Erreur : ' + (data.error || 'Erreur inconnue'));
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la modification de l\'accès.');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
            });
        }
        
        // Gestion de l'ouverture du modal de suppression d'accès
        const deleteAccessModal = document.getElementById('deleteAccessModal');
        if (deleteAccessModal) {
            deleteAccessModal.addEventListener('shown.bs.modal', function(event) {
                // Récupérer le bouton qui a déclenché le modal
                const button = event.relatedTarget;
                if (button) {
                    // Récupérer les données depuis les attributs data-*
                    const accessId = button.getAttribute('data-access-id') || '';
                    const accessName = button.getAttribute('data-access-name') || '';
                    
                    // Décoder les entités HTML
                    const decodedName = decodeHtmlEntities(accessName);
                    
                    // Remplir le formulaire
                    const deleteAccessId = document.getElementById('deleteAccessId');
                    const deleteAccessName = document.getElementById('deleteAccessName');
                    
                    if (deleteAccessId) deleteAccessId.value = accessId;
                    if (deleteAccessName) deleteAccessName.textContent = decodedName;
                }
            });
        }
        
        // Gestion de la soumission du formulaire de suppression d'accès
        const deleteAccessForm = document.getElementById('deleteAccessForm');
        if (deleteAccessForm) {
            deleteAccessForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                
                // Désactiver le bouton pendant la soumission
                submitButton.disabled = true;
                submitButton.textContent = 'Suppression...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour afficher les modifications
                        window.location.reload();
                    } else {
                        alert('Erreur : ' + (data.error || 'Erreur inconnue'));
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression de l\'accès.');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
            });
        }
        
        // Gestion de l'upload de plan de pièce (comme pour les photos de profil NPC)
        function initUploadMapForm() {
            const uploadMapForm = document.getElementById('uploadMapForm');
            if (uploadMapForm) {
                // Vérifier si l'événement est déjà attaché
                if (uploadMapForm.dataset.listenerAttached === 'true') {
                    return;
                }
                uploadMapForm.dataset.listenerAttached = 'true';
                uploadMapForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const fileInput = document.getElementById('planFileInput');
                    const notesInput = document.getElementById('placeNotes');
                    const submitButton = document.getElementById('uploadMapButton');
                    
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        alert('Veuillez sélectionner un fichier.');
                        return;
                    }
                    
                    const file = fileInput.files[0];
                    
                    // Vérifier la taille du fichier (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('Le plan est trop volumineux (max 10MB).');
                        return;
                    }
                    
                    // Vérifier le type de fichier
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Format de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.');
                        return;
                    }
                    
                    const formData = new FormData();
                    const placeId = uploadMapForm.querySelector('input[name="place_id"]').value;
                    formData.append('place_id', placeId);
                    formData.append('plan_file', file);
                    formData.append('notes', notesInput ? notesInput.value : '');
                    
                    // Afficher un indicateur de chargement
                    const originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Upload en cours...';
                    
                    fetch('api/upload_place_map.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error('Erreur HTTP ' + response.status + ': ' + text);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert('Erreur : ' + (data.message || 'Erreur inconnue'));
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        alert('Erreur lors de l\'upload du plan: ' + error.message);
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    });
                });
            }
        }
        
        // Essayer plusieurs fois pour s'assurer que le formulaire est chargé (même dans un collapse)
        document.addEventListener('DOMContentLoaded', function() {
            initUploadMapForm();
        });
        
        // Également essayer après un court délai au cas où le collapse serait ouvert après
        setTimeout(function() {
            initUploadMapForm();
        }, 500);
        
        // Écouter l'ouverture du collapse Bootstrap
        const editMapFormCollapse = document.getElementById('editMapForm');
        if (editMapFormCollapse) {
            editMapFormCollapse.addEventListener('shown.bs.collapse', function() {
                setTimeout(initUploadMapForm, 100);
            });
        }
        
        // Aussi écouter le clic sur le bouton pour ouvrir le collapse
        const editMapButton = document.querySelector('[data-bs-target="#editMapForm"]');
        if (editMapButton) {
            editMapButton.addEventListener('click', function() {
                setTimeout(initUploadMapForm, 300);
            });
        }
    </script>
<?php endif; ?>

<!-- Script pour la modale de déplacement -->
<?php if ($canEdit && !empty($placeAccesses)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const moveEntitiesForm = document.getElementById('moveEntitiesForm');
        const moveToPlace = document.getElementById('moveToPlace');
        const moveEntitiesSubmitBtn = document.getElementById('moveEntitiesSubmitBtn');
        
        if (moveEntitiesForm && moveToPlace && moveEntitiesSubmitBtn) {
            // Fonction pour vérifier si au moins une entité est sélectionnée
            function checkFormValidity() {
                const selectedEntities = moveEntitiesForm.querySelectorAll('input[name="entities[]"]:checked');
                const hasPlace = moveToPlace.value !== '';
                const hasEntities = selectedEntities.length > 0;
                
                moveEntitiesSubmitBtn.disabled = !hasPlace || !hasEntities;
            }
            
            // Écouter les changements sur le sélecteur de pièce
            moveToPlace.addEventListener('change', checkFormValidity);
            
            // Écouter les changements sur les checkboxes d'entités
            const entityCheckboxes = moveEntitiesForm.querySelectorAll('input[name="entities[]"]');
            entityCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', checkFormValidity);
            });
            
            // Vérifier au chargement initial
            checkFormValidity();
            
            // Gestion de la soumission du formulaire
            moveEntitiesForm.addEventListener('submit', function(e) {
                const selectedEntities = moveEntitiesForm.querySelectorAll('input[name="entities[]"]:checked');
                
                if (selectedEntities.length === 0) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins une entité à déplacer.');
                    return false;
                }
                
                if (!moveToPlace.value) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une pièce de destination.');
                    return false;
                }
                
                // Désactiver le bouton pendant la soumission
                moveEntitiesSubmitBtn.disabled = true;
                moveEntitiesSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Déplacement en cours...';
            });
        }
    });
</script>
<?php endif; ?>

<!-- Script pour charger les personnages d'un joueur -->
<?php if ($canEdit && hasCampaignId($place)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const playerSelect = document.getElementById('playerSelect');
        const characterSelect = document.getElementById('characterSelect');
        
        if (playerSelect && characterSelect) {
            playerSelect.addEventListener('change', function() {
                const playerId = this.value;
                
                // Réinitialiser le select des personnages
                characterSelect.innerHTML = '<option value="">Chargement...</option>';
                characterSelect.disabled = true;
                
                if (!playerId) {
                    characterSelect.innerHTML = '<option value="">Sélectionner un personnage</option>';
                    characterSelect.disabled = false;
                    return;
                }
                
                // Charger les personnages du joueur sélectionné
                const campaignId = document.querySelector('input[name="campaign_id"]')?.value || null;
                fetch('api/get_player_characters.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        player_id: playerId,
                        campaign_id: campaignId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    characterSelect.innerHTML = '<option value="">Sélectionner un personnage</option>';
                    
                    if (data.success && data.characters && data.characters.length > 0) {
                        data.characters.forEach(function(character) {
                            const option = document.createElement('option');
                            option.value = character.id;
                            option.textContent = character.name;
                            characterSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Aucun personnage disponible';
                        characterSelect.appendChild(option);
                    }
                    
                    characterSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des personnages:', error);
                    characterSelect.innerHTML = '<option value="">Erreur lors du chargement</option>';
                    characterSelect.disabled = false;
                });
            });
        }
    });
</script>
<?php endif; ?>

<!-- Script pour la modale de téléportation -->
<?php if ($canEdit && !empty($worldPlaces)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const teleportEntitiesForm = document.getElementById('teleportEntitiesForm');
        const teleportToPlace = document.getElementById('teleportToPlace');
        const teleportEntitiesSubmitBtn = document.getElementById('teleportEntitiesSubmitBtn');
        
        if (teleportEntitiesForm && teleportToPlace && teleportEntitiesSubmitBtn) {
            // Fonction pour vérifier si au moins une entité est sélectionnée
            function checkTeleportFormValidity() {
                const selectedEntities = teleportEntitiesForm.querySelectorAll('input[name="entities[]"]:checked');
                const hasPlace = teleportToPlace.value !== '';
                const hasEntities = selectedEntities.length > 0;
                
                teleportEntitiesSubmitBtn.disabled = !hasPlace || !hasEntities;
            }
            
            // Écouter les changements sur le sélecteur de pièce
            teleportToPlace.addEventListener('change', checkTeleportFormValidity);
            
            // Écouter les changements sur les checkboxes d'entités
            const entityCheckboxes = teleportEntitiesForm.querySelectorAll('input[name="entities[]"]');
            entityCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', checkTeleportFormValidity);
            });
            
            // Vérifier au chargement initial
            checkTeleportFormValidity();
            
            // Gestion de la soumission du formulaire
            teleportEntitiesForm.addEventListener('submit', function(e) {
                const selectedEntities = teleportEntitiesForm.querySelectorAll('input[name="entities[]"]:checked');
                
                if (selectedEntities.length === 0) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins une entité à téléporter.');
                    return false;
                }
                
                if (!teleportToPlace.value) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une pièce de destination.');
                    return false;
                }
                
                // Désactiver le bouton pendant la soumission
                teleportEntitiesSubmitBtn.disabled = true;
                teleportEntitiesSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Téléportation en cours...';
            });
        }
    });
</script>
<?php endif; ?>

</body>
<!-- Modal Création Automatique PNJ -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="createNpcAutoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-magic me-2"></i>Création Automatique de PNJ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createNpcAutoForm">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="auto_npc_race" class="form-label">Race *</label>
                            <select class="form-select" id="auto_npc_race" name="race_id" required>
                                <option value="">Choisir une race</option>
                                <?php foreach ($races as $race): ?>
                                    <option value="<?php echo $race['id']; ?>"><?php echo htmlspecialchars($race['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="auto_npc_class" class="form-label">Classe</label>
                            <select class="form-select" id="auto_npc_class" name="class_id">
                                <option value="">Choisir une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="auto_npc_level" class="form-label">Niveau *</label>
                            <select class="form-select" id="auto_npc_level" name="level" required>
                                <?php for($i=1; $i<=20; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="auto_npc_name" class="form-label">Nom (Optionnel)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="auto_npc_name" name="name" placeholder="Laisser vide pour aléatoire">
                                <button class="btn btn-outline-secondary" type="button" id="generateAutoNpcName" title="Générer un nom">
                                    <i class="fas fa-dice"></i>
                                </button>
                            </div>
                            <div id="autoNpcNameSuggestions" class="mt-2 small"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                             <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_npc_visible" name="is_visible" value="1" checked>
                                <label class="form-check-label" for="auto_npc_visible">Visible par les joueurs</label>
                            </div>
                        </div>
                         <div class="col-md-6 mb-3">
                             <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_npc_identified" name="is_identified" value="1">
                                <label class="form-check-label" for="auto_npc_identified">Identifié par les joueurs</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="createNpcAutoError"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitCreateNpcAuto">
                    <i class="fas fa-wand-magic-sparkles me-1"></i>Créer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateBtn = document.getElementById('generateAutoNpcName');
    const nameInput = document.getElementById('auto_npc_name');
    const raceSelect = document.getElementById('auto_npc_race');
    const classSelect = document.getElementById('auto_npc_class');
    const suggestionsDiv = document.getElementById('autoNpcNameSuggestions');
    const submitBtn = document.getElementById('submitCreateNpcAuto');
    const form = document.getElementById('createNpcAutoForm');
    const errorAlert = document.getElementById('createNpcAutoError');

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const raceId = raceSelect.value;
            const classId = classSelect.value;
            
            const formData = new FormData();
            if (raceId) formData.append('race_id', raceId);
            if (classId) formData.append('class_id', classId);
            formData.append('count', 3);

            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('api/generate_npc_name.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-dice"></i>';
                
                if (data.success && data.suggestions) {
                    suggestionsDiv.innerHTML = '';
                    data.suggestions.forEach(name => {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-light text-dark border me-1 cursor-pointer';
                        badge.style.cursor = 'pointer';
                        badge.textContent = name;
                        badge.onclick = () => nameInput.value = name;
                        suggestionsDiv.appendChild(badge);
                    });
                }
            })
            .catch(err => {
                console.error(err);
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fas fa-dice"></i>';
            });
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Création...';
            errorAlert.classList.add('d-none');

            // Force values for checkboxes (if unchecked they are missing from FormData usually, but here we read from input so it might depend)
            // Actually Object.fromEntries only takes checked checkboxes.
            // We should ensure is_visible/identified are handled correctly.
            // In the API we check 'isset', so if missing it's 0. That's fine.

            fetch('api/create_npc_automatic.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload(); // Simple reload to show new NPC
                } else {
                    errorAlert.textContent = result.message || 'Erreur inconnue';
                    errorAlert.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>Créer';
                }
            })
            .catch(err => {
                errorAlert.textContent = 'Erreur réseau: ' + err.message;
                errorAlert.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>Créer';
            });
        });
    }
});
</script>
<?php endif; ?>
</body>
<script>
    // Fonction pour supprimer un PNJ
    function removeNpc(npcId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce PNJ de la pièce ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'view_place.php?id=<?php echo $place_id; ?>';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_npc';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'npc_id';
            idInput.value = npcId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<!-- Script pour la gestion des couleurs de pions (chargé en dernier pour s'assurer que le DOM est prêt) -->
<script src="js/token-color-menu.js"></script>

</html>
