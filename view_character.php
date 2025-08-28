<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: characters.php');
    exit();
}

$character_id = (int)$_GET['id'];
$dm_campaign_id = isset($_GET['dm_campaign_id']) ? (int)$_GET['dm_campaign_id'] : null;

// Récupération du personnage avec ses détails (sans filtrer par propriétaire)
$stmt = $pdo->prepare("
    SELECT c.*, r.name as race_name, r.description as race_description, r.ability_score_bonus, r.traits,
           cl.name as class_name, cl.description as class_description, cl.hit_die, cl.primary_ability
    FROM characters c 
    JOIN races r ON c.race_id = r.id 
    JOIN classes cl ON c.class_id = cl.id 
    WHERE c.id = ?
");
$stmt->execute([$character_id]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit();
}

// Contrôle d'accès: propriétaire OU MJ de la campagne liée
$canView = ($character['user_id'] == $_SESSION['user_id']);

if (!$canView && isDM() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
    $ownsCampaign = (bool)$stmt->fetch();

    if ($ownsCampaign) {
        // Vérifier que le joueur propriétaire du personnage est membre ou a candidaté à cette campagne
        $owner_user_id = (int)$character['user_id'];
        $isMember = false;
        $hasApplied = false;

        $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$dm_campaign_id, $owner_user_id]);
        $isMember = (bool)$stmt->fetch();

        if (!$isMember) {
            $stmt = $pdo->prepare("SELECT 1 FROM campaign_applications WHERE campaign_id = ? AND player_id = ? LIMIT 1");
            $stmt->execute([$dm_campaign_id, $owner_user_id]);
            $hasApplied = (bool)$stmt->fetch();
        }

        $canView = ($isMember || $hasApplied);
    }
}

if (!$canView) {
    header('Location: characters.php');
    exit();
}

// Calcul des modificateurs
$strengthMod = getAbilityModifier($character['strength']);
$dexterityMod = getAbilityModifier($character['dexterity']);
$constitutionMod = getAbilityModifier($character['constitution']);
$intelligenceMod = getAbilityModifier($character['intelligence']);
$wisdomMod = getAbilityModifier($character['wisdom']);
$charismaMod = getAbilityModifier($character['charisma']);

// Calcul de l'initiative
$initiative = $dexterityMod;

// Calcul de la classe d'armure
$armorClass = $character['armor_class'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($character['name']); ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .character-sheet {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-modifier {
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .hp-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e74c3c;
        }
        .ac-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="characters.php">Mes Personnages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_character.php">Créer un Personnage</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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
                <div class="col-md-8">
                    <h2><?php echo htmlspecialchars($character['name']); ?></h2>
                    <p class="text-muted">
                        <?php echo htmlspecialchars($character['race_name']); ?> 
                        <?php echo htmlspecialchars($character['class_name']); ?> 
                        niveau <?php echo $character['level']; ?>
                    </p>
                    <?php if ($character['background']): ?>
                        <p><strong>Historique:</strong> <?php echo htmlspecialchars($character['background']); ?></p>
                    <?php endif; ?>
                    <?php if ($character['alignment']): ?>
                        <p><strong>Alignement:</strong> <?php echo htmlspecialchars($character['alignment']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="hp-display">&nbsp;<?php echo $character['hit_points_current']; ?></div>
                                <div class="stat-label">Points de Vie</div>
                                <small class="text-muted">/ <?php echo $character['hit_points_max']; ?></small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="ac-display">&nbsp;<?php echo $armorClass; ?></div>
                                <div class="stat-label">Classe d'Armure</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caractéristiques -->
            <div class="info-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                <div class="row">
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['strength']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($strengthMod >= 0 ? '+' : '') . $strengthMod; ?></div>
                            <div class="stat-label">Force</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['dexterity']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?></div>
                            <div class="stat-label">Dextérité</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['constitution']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($constitutionMod >= 0 ? '+' : '') . $constitutionMod; ?></div>
                            <div class="stat-label">Constitution</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['intelligence']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($intelligenceMod >= 0 ? '+' : '') . $intelligenceMod; ?></div>
                            <div class="stat-label">Intelligence</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['wisdom']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($wisdomMod >= 0 ? '+' : '') . $wisdomMod; ?></div>
                            <div class="stat-label">Sagesse</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-box">
                            <div class="stat-value">&nbsp;<?php echo $character['charisma']; ?></div>
                            <div class="stat-modifier">&nbsp;<?php echo ($charismaMod >= 0 ? '+' : '') . $charismaMod; ?></div>
                            <div class="stat-label">Charisme</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Combat -->
            <div class="info-section">
                <h3><i class="fas fa-sword me-2"></i>Combat</h3>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Initiative:</strong> &nbsp;<?php echo ($initiative >= 0 ? '+' : '') . $initiative; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Vitesse:</strong> &nbsp;<?php echo $character['speed']; ?> pieds
                    </div>
                    <div class="col-md-3">
                        <strong>Bonus de maîtrise:</strong> &nbsp;+<?php echo $character['proficiency_bonus']; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Points d'expérience:</strong> &nbsp;<?php echo number_format($character['experience_points']); ?>
                    </div>
                </div>
            </div>

            <!-- Informations de race et classe -->
            <div class="row">
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-dragon me-2"></i>Race: <?php echo htmlspecialchars($character['race_name']); ?></h3>
                        <p><?php echo htmlspecialchars($character['race_description']); ?></p>
                        <p><strong>Bonus de caractéristiques:</strong> &nbsp;<?php echo htmlspecialchars($character['ability_score_bonus']); ?></p>
                        <p><strong>Traits:</strong> &nbsp;<?php echo htmlspecialchars($character['traits']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-shield-alt me-2"></i>Classe: <?php echo htmlspecialchars($character['class_name']); ?></h3>
                        <p><?php echo htmlspecialchars($character['class_description']); ?></p>
                        <p><strong>Dé de vie:</strong> &nbsp;d<?php echo $character['hit_die']; ?></p>
                        <p><strong>Caractéristique principale:</strong> &nbsp;<?php echo htmlspecialchars($character['primary_ability']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Équipement et trésor -->
            <div class="info-section">
                <h3><i class="fas fa-backpack me-2"></i>Équipement et Trésor</h3>
                <div class="row">
                    <div class="col-md-8">
                        <?php if ($character['equipment']): ?>
                            <p><strong>Équipement:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($character['equipment'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Aucun équipement enregistré</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Argent:</strong></p>
                        <ul class="list-unstyled">
                            <li><?php echo $character['money_gold']; ?> PO (pièces d'or)</li>
                            <li><?php echo $character['money_silver']; ?> PA (pièces d'argent)</li>
                            <li><?php echo $character['money_copper']; ?> PC (pièces de cuivre)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Informations personnelles -->
            <?php if ($character['personality_traits'] || $character['ideals'] || $character['bonds'] || $character['flaws']): ?>
                <div class="info-section">
                    <h3><i class="fas fa-user-edit me-2"></i>Informations Personnelles</h3>
                    <div class="row">
                        <?php if ($character['personality_traits']): ?>
                            <div class="col-md-6">
                                <p><strong>Traits de personnalité:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['personality_traits'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['ideals']): ?>
                            <div class="col-md-6">
                                <p><strong>Idéaux:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['ideals'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['bonds']): ?>
                            <div class="col-md-6">
                                <p><strong>Liens:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['bonds'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['flaws']): ?>
                            <div class="col-md-6">
                                <p><strong>Défauts:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['flaws'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


