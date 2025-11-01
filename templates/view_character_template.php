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
        
        /* Styles pour les rages */
        .rage-container {
            text-align: center;
        }
        
        .rage-symbols {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .rage-symbol {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .rage-symbol.available {
            background-color: #28a745;
            color: white;
        }
        
        .rage-symbol.used {
            background-color: #dc3545;
            color: white;
        }
        
        .rage-info {
            text-align: center;
        }
        
        /* Styles pour les compétences */
        .skills-list .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .skills-list .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
            transform: translateX(5px);
        }
        
        .skills-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .skills-list .list-group-item.active h6 {
            color: white;
        }
        
        .skills-list .list-group-item.active small {
            color: rgba(255, 255, 255, 0.8);
        }
        
        #skill-detail {
            min-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        #skill-detail .card-body {
            padding: 20px;
        }
        
        #skill-detail li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>

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
                            <?php if ($archetypeDetails): ?>
                                <p><strong><?php echo htmlspecialchars($archetypeDetails['name']); ?>:</strong> <?php echo htmlspecialchars($archetypeDetails['description']); ?></p>
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
                                <?php if ($canModifyHP || $canModifyAsDM): ?>
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
                                <?php if ($canModifyXP): ?>
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
                            <!-- Bonus de niveau -->
                            <tr>
                                <td><strong>Bonus de niveau (<?php echo $remainingPoints; ?> pts restants)</strong></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['strength'] > 0 ? '+' : '') . $abilityImprovements['strength']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['dexterity'] > 0 ? '+' : '') . $abilityImprovements['dexterity']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['constitution'] > 0 ? '+' : '') . $abilityImprovements['constitution']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['intelligence'] > 0 ? '+' : '') . $abilityImprovements['intelligence']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['wisdom'] > 0 ? '+' : '') . $abilityImprovements['wisdom']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['charisma'] > 0 ? '+' : '') . $abilityImprovements['charisma']; ?></span></td>
                            </tr>
                            <!-- Bonus d'équipements -->
                            <tr>
                                <td><strong>Bonus d'équipements</strong></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['strength'] > 0 ? '+' : '') . $equipmentBonuses['strength']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['dexterity'] > 0 ? '+' : '') . $equipmentBonuses['dexterity']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['constitution'] > 0 ? '+' : '') . $equipmentBonuses['constitution']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['intelligence'] > 0 ? '+' : '') . $equipmentBonuses['intelligence']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['wisdom'] > 0 ? '+' : '') . $equipmentBonuses['wisdom']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['charisma'] > 0 ? '+' : '') . $equipmentBonuses['charisma']; ?></span></td>
                            </tr>
                            <!-- Bonus temporaires -->
                            <tr>
                                <td><strong>Bonus temporaires</strong></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['strength'] > 0 ? '+' : '') . $temporaryBonuses['strength']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['dexterity'] > 0 ? '+' : '') . $temporaryBonuses['dexterity']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['constitution'] > 0 ? '+' : '') . $temporaryBonuses['constitution']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['intelligence'] > 0 ? '+' : '') . $temporaryBonuses['intelligence']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['wisdom'] > 0 ? '+' : '') . $temporaryBonuses['wisdom']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['charisma'] > 0 ? '+' : '') . $temporaryBonuses['charisma']; ?></span></td>
                            </tr>
                            <!-- Total -->
                            <tr class="table-success">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $totalAbilities['strength']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['dexterity']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['constitution']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['intelligence']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['wisdom']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['charisma']; ?></strong></td>
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

            <!-- Rages (pour les barbares) -->
            <?php if ($isBarbarian && $rageData): ?>
            <div class="info-section">
                <h3><i class="fas fa-fire me-2"></i>Rages</h3>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="rage-container">
                            <div class="rage-symbols">
                                <?php for ($i = 1; $i <= $rageData['max']; $i++): ?>
                                    <div class="rage-symbol <?php echo $i <= $rageData['used'] ? 'used' : 'available'; ?>" 
                                         onclick="<?php echo $i <= $rageData['used'] ? 'alert(\'Les rages utilisées ne peuvent être récupérées qu\'avec un long repos.\')' : 'useRage(' . $character_id . ', ' . $i . ')' ?>"
                                         data-rage="<?php echo $i; ?>"
                                         title="<?php echo $i <= $rageData['used'] ? 'Rage utilisée (long repos requis)' : 'Rage disponible - cliquer pour utiliser'; ?>">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="rage-info">
                                <strong><?php echo $rageData['used']; ?>/<?php echo $rageData['max']; ?></strong> rages utilisées
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-warning" onclick="resetRages(<?php echo $character_id; ?>)">
                            <i class="fas fa-moon me-1"></i>Long repos
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Combat -->
            <div class="info-section">
                <h3><i class="fas fa-sword me-2"></i>Combat</h3>
                
                <!-- Statistiques de base -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Initiative:</strong> &nbsp;<?php echo ($character['initiative'] >= 0 ? '+' : '') . $character['initiative']; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Vitesse:</strong> <?php echo $character['speed']; ?> pieds
                    </div>
                    <div class="col-md-3">
                        <strong>Bonus de maîtrise:</strong> +<?php echo $character['proficiency_bonus']; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Dés de vie:</strong> d<?php echo $characterDetails['hit_dice']; ?>
                    </div>
                </div>
                
                <!-- Classe d'armure -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5><i class="fas fa-shield-alt me-2"></i>Classe d'armure</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h4 class="mb-0"><?php echo $armorClass; ?></h4>
                                        <small class="text-muted">Classe d'armure</small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            Base (10) + Modificateur de dextérité (<?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?>)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attaques -->
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-crosshairs me-2"></i>Attaques</h5>
                        <?php if (!empty($attacks)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Portée</th>
                                            <th>Bonus d'attaque</th>
                                            <th>Dégâts</th>
                                            <th>Maîtrise</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attacks as $attack): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($attack['attack_name']); ?></strong>
                                                <?php if ($attack['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($attack['description']); ?></small>
                                                <?php endif; ?>
                                                <?php if (isset($attack['source_type'])): ?>
                                                    <br><small class="<?php echo $attack['source_type'] == 'equipped' ? 'text-success' : 'text-info'; ?>">
                                                        <i class="fas <?php echo $attack['source_type'] == 'equipped' ? 'fa-hand-rock' : 'fa-user'; ?> me-1"></i>
                                                        <?php echo $attack['source_type'] == 'equipped' ? 'Objet équipé' : 'Attaque personnalisée'; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($attack['attack_type']); ?></td>
                                            <td>
                                                <?php 
                                                if ($attack['range_type'] == 'melee') {
                                                    echo 'Corps à corps';
                                                } elseif ($attack['range_type'] == 'ranged') {
                                                    echo 'À distance (' . $attack['range_value'] . ' pieds)';
                                                } else {
                                                    echo htmlspecialchars($attack['range_type']);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo ($attack['attack_bonus'] >= 0 ? '+' : '') . $attack['attack_bonus']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($attack['damage_dice']): ?>
                                                    <?php echo $attack['damage_dice']; ?>
                                                    <?php if ($attack['damage_bonus']): ?>
                                                        + <?php echo $attack['damage_bonus']; ?>
                                                    <?php endif; ?>
                                                    <?php if ($attack['damage_type']): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($attack['damage_type']); ?>)</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($attack['is_proficient']): ?>
                                                    <span class="badge bg-success">Maîtrisé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Non maîtrisé</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Aucune attaque enregistrée pour ce personnage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Compétences -->
            <div class="info-section">
                <h3><i class="fas fa-dice me-2"></i>Compétences</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-check-circle me-2"></i>Compétences maîtrisées</h5>
                        <?php if (!empty($allSkills)): ?>
                            <div class="list-group skills-list">
                                <?php foreach ($allSkills as $skill): ?>
                                    <?php 
                                    // Déterminer la source de la compétence
                                    $isBackgroundSkill = in_array($skill, $backgroundSkills);
                                    $sourceClass = $isBackgroundSkill ? 'text-success' : 'text-primary';
                                    $sourceIcon = $isBackgroundSkill ? 'fas fa-book' : 'fas fa-user';
                                    $sourceText = $isBackgroundSkill ? 'Historique' : 'Classe/Race';
                                    ?>
                                    <a href="#" class="list-group-item list-group-item-action skill-item" data-skill="<?php echo htmlspecialchars($skill); ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($skill); ?></h6>
                                            <small class="<?php echo $sourceClass; ?>">
                                                <i class="<?php echo $sourceIcon; ?> me-1"></i><?php echo $sourceText; ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune compétence maîtrisée</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle me-2"></i>Détail de la compétence</h5>
                        <div id="skill-detail" class="card">
                            <div class="card-body">
                                <p class="text-muted mb-0">Sélectionnez une compétence pour voir ses détails.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Langues -->
            <div class="info-section">
                <h3><i class="fas fa-language me-2"></i>Langues</h3>
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-comments me-2"></i>Langues parlées</h5>
                        <?php if (!empty($allLanguages)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($allLanguages as $language): ?>
                                    <span class="badge bg-info me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune langue parlée</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bourse -->
            <div class="info-section">
                <h3><i class="fas fa-coins me-2"></i>Bourse</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="card-title text-warning">
                                    <i class="fas fa-coins me-2"></i><?php echo $moneyData['gold']; ?>
                                </h4>
                                <p class="card-text">Pièces d'or</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="card-title text-secondary">
                                    <i class="fas fa-coins me-2"></i><?php echo $moneyData['silver']; ?>
                                </h4>
                                <p class="card-text">Pièces d'argent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="card-title text-warning">
                                    <i class="fas fa-coins me-2"></i><?php echo $moneyData['copper']; ?>
                                </h4>
                                <p class="card-text">Pièces de cuivre</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total en pièces d'or -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Total : <?php 
                                $totalGold = $moneyData['gold'] + ($moneyData['silver'] / 10) + ($moneyData['copper'] / 100);
                                echo number_format($totalGold, 2);
                                ?> pièces d'or
                            </h5>
                            <small class="text-muted">
                                (1 PO = 10 PA = 100 PC)
                            </small>
                        </div>
                    </div>
                </div>
            </div>

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
                            <input class="form-check-input" type="checkbox" id="filter-shield" checked>
                            <label class="form-check-label" for="filter-shield">Boucliers</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filter-other" checked>
                            <label class="form-check-label" for="filter-other">Autres objets</label>
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
                                    <?php 
                                    // Seules les armes, armures et boucliers peuvent être équipés
                                    $canBeEquipped = in_array($item['item_type'], ['weapon', 'armor', 'shield']);
                                    ?>
                                    <?php if ($canBeEquipped): ?>
                                        <?php if ($item['equipped']): ?>
                                            <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $character_id; ?>, '<?php echo addslashes($item['item_name']); ?>')">
                                                <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $character_id; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo $item['item_type']; ?>', '<?php echo $item['equipped_slot'] ?? 'main_hand'; ?>')">
                                                <i class="fas fa-hand-rock me-1"></i>Équiper
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-ban me-1"></i>Non équipable
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($canModifyHP && !str_starts_with($item['id'], 'base_')): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm ms-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#transferModal" 
                                                data-item-id="<?php echo $item['id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                data-item-type="<?php echo htmlspecialchars($item['item_type']); ?>"
                                                data-source="character_equipment"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-exchange-alt me-1"></i>Transférer
                                        </button>
                                        <?php if (!$item['equipped']): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm ms-1" 
                                                    onclick="dropItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>')"
                                                    title="Déposer l'objet dans le lieu actuel"
                                                    style="white-space: nowrap; min-width: 80px;">
                                                <i class="fas fa-hand-holding me-1"></i>Déposer
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

    <!-- Modale pour modifier les points de vie -->
    <div class="modal fade" id="hpModal" tabindex="-1" aria-labelledby="hpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hpModalLabel">Modifier les Points de Vie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="hpForm">
                        <div class="mb-3">
                            <label for="new_hp" class="form-label">Nouveaux Points de Vie</label>
                            <input type="number" class="form-control" id="new_hp" name="new_hp" min="0" max="<?php echo $character['hit_points_max']; ?>" value="<?php echo $character['hit_points_current']; ?>">
                            <div class="form-text">Maximum: <?php echo $character['hit_points_max']; ?> PV</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="updateHP()">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour modifier l'expérience -->
    <div class="modal fade" id="xpModal" tabindex="-1" aria-labelledby="xpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="xpModalLabel">Modifier l'Expérience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="xpForm">
                        <div class="mb-3">
                            <label for="new_xp" class="form-label">Nouveaux Points d'Expérience</label>
                            <input type="number" class="form-control" id="new_xp" name="new_xp" min="0" value="<?php echo $character['experience_points']; ?>">
                            <div class="form-text">Expérience actuelle: <?php echo number_format($character['experience_points']); ?> XP</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="updateXP()">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jdrmj.js"></script>
</body>
</html>
