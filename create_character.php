<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';

// Récupération des races, classes et historiques
$races = $pdo->query("SELECT * FROM races ORDER BY name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
$backgrounds = getAllBackgrounds();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $race_id = (int)$_POST['race_id'];
    $class_id = (int)$_POST['class_id'];
    $background_id = (int)($_POST['background_id'] ?? 0);
    $experience_points = (int)($_POST['experience_points'] ?? 0);
    
    // Statistiques
    $strength = (int)$_POST['strength'];
    $dexterity = (int)$_POST['dexterity'];
    $constitution = (int)$_POST['constitution'];
    $intelligence = (int)$_POST['intelligence'];
    $wisdom = (int)$_POST['wisdom'];
    $charisma = (int)$_POST['charisma'];
    
    // Informations de combat
    $armor_class = (int)$_POST['armor_class'];
    $speed = (int)$_POST['speed'];
    $alignment = sanitizeInput($_POST['alignment']);
    
    // Compétences
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    
    // Ajouter automatiquement les compétences de classe
    $classProficiencies = getClassProficiencies($class_id);
    $classSkills = array_merge(
        $classProficiencies['armor'],
        $classProficiencies['weapon'],
        $classProficiencies['tool']
    );
    
    // Ajouter automatiquement les compétences d'historique
    $backgroundSkills = [];
    $backgroundTools = [];
    if ($background_id > 0) {
        $backgroundProficiencies = getBackgroundProficiencies($background_id);
        $backgroundSkills = $backgroundProficiencies['skills'];
        $backgroundTools = $backgroundProficiencies['tools'];
    }
    
    // Fusionner toutes les compétences
    $allSkills = array_unique(array_merge($skills, $classSkills, $backgroundSkills, $backgroundTools));
    $skills_json = json_encode($allSkills);
    
    // Validation
    $errors = [];
    
    if (strlen($name) < 2) {
        $errors[] = "Le nom du personnage doit contenir au moins 2 caractères.";
    }
    
    if ($experience_points < 0) {
        $errors[] = "Les points d'expérience ne peuvent pas être négatifs.";
    }
    
    // Calculer le niveau basé sur l'expérience
    $level = calculateLevelFromExperience($experience_points);
    
    // Validation des caractéristiques
    $stats = [$strength, $dexterity, $constitution, $intelligence, $wisdom, $charisma];
    foreach ($stats as $stat) {
        if ($stat < 1 || $stat > 20) {
            $errors[] = "Les caractéristiques doivent être entre 1 et 20.";
            break;
        }
    }
    
    if (empty($errors)) {
        // Calcul des points de vie
        $stmt = $pdo->prepare("SELECT hit_dice FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $hitDie = $stmt->fetch()['hit_dice'];
        
        $constitutionModifier = getAbilityModifier($constitution);
        $maxHP = calculateMaxHP($level, $hitDie, $constitutionModifier);
        
        // Calcul du bonus de maîtrise basé sur l'expérience
        $proficiencyBonus = calculateProficiencyBonusFromExperience($experience_points);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, background_id, level, experience_points,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, speed, hit_points_max, hit_points_current, proficiency_bonus,
                    alignment, skills
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $name, $race_id, $class_id, $background_id, $level, $experience_points,
                $strength, $dexterity, $constitution, $intelligence, $wisdom, $charisma,
                $armor_class, $speed, $maxHP, $maxHP, $proficiencyBonus,
                $alignment, $skills_json
            ]);
            
            $character_id = $pdo->lastInsertId();
            $message = displayMessage("Personnage créé avec succès !", "success");
            
            // Redirection vers la vue du personnage après 2 secondes
            header("refresh:2;url=view_character.php?id=" . $character_id);
            
        } catch (PDOException $e) {
            $message = displayMessage("Erreur lors de la création du personnage : " . $e->getMessage(), "error");
        }
    } else {
        $message = displayMessage(implode("<br>", $errors), "error");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Personnage - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-dnd {
            background: linear-gradient(45deg, #8B4513, #D2691E);
            border: none;
            color: white;
        }
        .btn-dnd:hover {
            background: linear-gradient(45deg, #A0522D, #CD853F);
            color: white;
        }
        .stat-input {
            max-width: 80px;
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
                        <a class="nav-link active" href="create_character.php">Créer un Personnage</a>
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
        <h1><i class="fas fa-user-plus me-2"></i>Créer un Personnage</h1>
        
        <?php echo $message; ?>
        
        <form method="POST" action="">
            <!-- Informations de base -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle me-2"></i>Informations de base</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du personnage</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="race_id" class="form-label">Race</label>
                            <select class="form-select" id="race_id" name="race_id" required>
                                <option value="">Choisir une race</option>
                                <?php foreach ($races as $race): ?>
                                    <option value="<?php echo $race['id']; ?>" 
                                            <?php echo (isset($_POST['race_id']) && $_POST['race_id'] == $race['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($race['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Classe</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Choisir une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" 
                                            <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="experience_points" class="form-label">Points d'Expérience</label>
                            <input type="number" class="form-control" id="experience_points" name="experience_points" 
                                   value="<?php echo isset($_POST['experience_points']) ? $_POST['experience_points'] : '0'; ?>" 
                                   min="0" required>
                            <small class="form-text text-muted">Le niveau sera calculé automatiquement</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Niveau Calculé</label>
                            <div class="form-control-plaintext" id="calculated_level">
                                Niveau 1
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="background_id" class="form-label">Historique</label>
                            <select class="form-select" id="background_id" name="background_id">
                                <option value="">Choisir un historique</option>
                                <?php foreach ($backgrounds as $background): ?>
                                    <option value="<?php echo $background['id']; ?>" 
                                            <?php echo (isset($_POST['background_id']) && $_POST['background_id'] == $background['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($background['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="alignment" class="form-label">Alignement</label>
                            <select class="form-select" id="alignment" name="alignment">
                                <option value="">Choisir un alignement</option>
                                <option value="Loyal Bon" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Loyal Bon') ? 'selected' : ''; ?>>Loyal Bon</option>
                                <option value="Neutre Bon" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Neutre Bon') ? 'selected' : ''; ?>>Neutre Bon</option>
                                <option value="Chaotique Bon" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Chaotique Bon') ? 'selected' : ''; ?>>Chaotique Bon</option>
                                <option value="Loyal Neutre" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Loyal Neutre') ? 'selected' : ''; ?>>Loyal Neutre</option>
                                <option value="Neutre" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Neutre') ? 'selected' : ''; ?>>Neutre</option>
                                <option value="Chaotique Neutre" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Chaotique Neutre') ? 'selected' : ''; ?>>Chaotique Neutre</option>
                                <option value="Loyal Mauvais" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Loyal Mauvais') ? 'selected' : ''; ?>>Loyal Mauvais</option>
                                <option value="Neutre Mauvais" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Neutre Mauvais') ? 'selected' : ''; ?>>Neutre Mauvais</option>
                                <option value="Chaotique Mauvais" <?php echo (isset($_POST['alignment']) && $_POST['alignment'] == 'Chaotique Mauvais') ? 'selected' : ''; ?>>Chaotique Mauvais</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails de l'historique -->
            <div class="form-section" id="background-details-section" style="display: none;">
                <h3><i class="fas fa-book me-2"></i>Détails de l'historique</h3>
                <div class="row">
                    <div class="col-12">
                        <div id="background-details"></div>
                    </div>
                </div>
            </div>

            <!-- Caractéristiques -->
            <div class="form-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                
                <!-- Méthode de génération des caractéristiques -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label"><strong>Méthode de génération des caractéristiques :</strong></label>
                        <div class="btn-group" role="group" data-bs-toggle="buttons">
                            <input type="radio" class="btn-check" name="generation_method" id="point_buy" value="point_buy" checked>
                            <label class="btn btn-outline-primary" for="point_buy">
                                <i class="fas fa-calculator me-2"></i>Répartition de 27 points
                            </label>
                            
                            <input type="radio" class="btn-check" name="generation_method" id="dice_roll" value="dice_roll">
                            <label class="btn btn-outline-primary" for="dice_roll">
                                <i class="fas fa-dice me-2"></i>Tirage 4d6 (garder 3 meilleurs)
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Interface de répartition de points -->
                <div id="point-buy-interface" class="generation-interface">
                    <div class="alert alert-info">
                        <strong>Répartition de 27 points :</strong> Chaque caractéristique coûte des points selon le barème D&D 5e.
                        <br><strong>Barème :</strong> 8-13 = 1pt par niveau, 14 = 2pts, 15 = 3pts
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Points disponibles</h6>
                                </div>
                                <div class="card-body text-center">
                                    <h3 id="points-remaining" class="text-primary">27</h3>
                                    <small class="text-muted">points restants</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Répartition typique</h6>
                                </div>
                                <div class="card-body">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyTypicalDistribution()">
                                        Appliquer 15-14-13-12-10-8
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Interface de tirage de dés -->
                <div id="dice-roll-interface" class="generation-interface" style="display: none;">
                    <div class="alert alert-info">
                        <strong>Tirage 4d6 :</strong> Lancez 4 dés à 6 faces, gardez les 3 meilleurs. Répétez 6 fois.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary" onclick="rollAllDice()">
                                <i class="fas fa-dice me-2"></i>Lancer tous les dés
                            </button>
                        </div>
                        <div class="col-md-6">
                            <div id="dice-results" class="text-center">
                                <em class="text-muted">Cliquez sur "Lancer tous les dés" pour commencer</em>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations de la race sélectionnée -->
                <div id="race-info" class="alert alert-info" style="display: none;">
                    <div class="row">
                        <div class="col-md-3">
                            <div id="race-image-container" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <img id="race-image" src="" alt="Image de la race" class="img-fluid rounded" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations de la race</h5>
                            <div id="race-details"></div>
                        </div>
                    </div>
                </div>
                
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
                            <!-- Caractéristiques de base (éditables) -->
                            <tr>
                                <td><strong>Caractéristiques de base</strong></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="strength" name="strength" 
                                               value="<?php echo isset($_POST['strength']) ? $_POST['strength'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('strength', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('strength', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="strength-cost">Coût: 0 pts</small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="dexterity" name="dexterity" 
                                               value="<?php echo isset($_POST['dexterity']) ? $_POST['dexterity'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('dexterity', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('dexterity', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="dexterity-cost">Coût: 0 pts</small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="constitution" name="constitution" 
                                               value="<?php echo isset($_POST['constitution']) ? $_POST['constitution'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('constitution', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('constitution', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="constitution-cost">Coût: 0 pts</small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="intelligence" name="intelligence" 
                                               value="<?php echo isset($_POST['intelligence']) ? $_POST['intelligence'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('intelligence', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('intelligence', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="intelligence-cost">Coût: 0 pts</small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="wisdom" name="wisdom" 
                                               value="<?php echo isset($_POST['wisdom']) ? $_POST['wisdom'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('wisdom', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('wisdom', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="wisdom-cost">Coût: 0 pts</small>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control stat-input" id="charisma" name="charisma" 
                                               value="<?php echo isset($_POST['charisma']) ? $_POST['charisma'] : '8'; ?>" 
                                               min="8" max="15" required>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('charisma', -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustStat('charisma', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="charisma-cost">Coût: 0 pts</small>
                                </td>
                            </tr>
                            <!-- Bonus raciaux -->
                            <tr>
                                <td><strong>Bonus raciaux</strong></td>
                                <td><span id="racial-strength-bonus" class="text-success">+0</span></td>
                                <td><span id="racial-dexterity-bonus" class="text-success">+0</span></td>
                                <td><span id="racial-constitution-bonus" class="text-success">+0</span></td>
                                <td><span id="racial-intelligence-bonus" class="text-success">+0</span></td>
                                <td><span id="racial-wisdom-bonus" class="text-success">+0</span></td>
                                <td><span id="racial-charisma-bonus" class="text-success">+0</span></td>
                            </tr>
                            <!-- Bonus d'équipements -->
                            <tr>
                                <td><strong>Bonus d'équipements</strong></td>
                                <td><span id="equipment-strength-bonus" class="text-info">+0</span></td>
                                <td><span id="equipment-dexterity-bonus" class="text-info">+0</span></td>
                                <td><span id="equipment-constitution-bonus" class="text-info">+0</span></td>
                                <td><span id="equipment-intelligence-bonus" class="text-info">+0</span></td>
                                <td><span id="equipment-wisdom-bonus" class="text-info">+0</span></td>
                                <td><span id="equipment-charisma-bonus" class="text-info">+0</span></td>
                            </tr>
                            <!-- Bonus temporaires -->
                            <tr>
                                <td><strong>Bonus temporaires</strong></td>
                                <td><span id="temp-strength-bonus" class="text-warning">+0</span></td>
                                <td><span id="temp-dexterity-bonus" class="text-warning">+0</span></td>
                                <td><span id="temp-constitution-bonus" class="text-warning">+0</span></td>
                                <td><span id="temp-intelligence-bonus" class="text-warning">+0</span></td>
                                <td><span id="temp-wisdom-bonus" class="text-warning">+0</span></td>
                                <td><span id="temp-charisma-bonus" class="text-warning">+0</span></td>
                            </tr>
                            <!-- Total -->
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td><strong id="total-strength">10 (+0)</strong></td>
                                <td><strong id="total-dexterity">10 (+0)</strong></td>
                                <td><strong id="total-constitution">10 (+0)</strong></td>
                                <td><strong id="total-intelligence">10 (+0)</strong></td>
                                <td><strong id="total-wisdom">10 (+0)</strong></td>
                                <td><strong id="total-charisma">10 (+0)</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Langues -->
            <div class="form-section">
                <h3><i class="fas fa-language me-2"></i>Langues</h3>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="race_languages" class="form-label">Langues parlées</label>
                            <div id="languages-list" class="border rounded p-3" style="min-height: 50px; background-color: #f8f9fa;">
                                <em class="text-muted">Sélectionnez une race pour voir ses langues</em>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3" id="additional-language-section" style="display: none;">
                            <label for="additional_language" class="form-label">Langue supplémentaire</label>
                            <div class="input-group">
                                <select class="form-select" id="additional_language" name="additional_language">
                                    <option value="">Choisir une langue</option>
                                </select>
                                <button class="btn btn-outline-danger" type="button" id="remove-additional-language" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Si votre race permet "une langue de votre choix"</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Combat -->
            <div class="form-section">
                <h3><i class="fas fa-sword me-2"></i>Combat</h3>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="armor_class" class="form-label">Classe d'Armure</label>
                            <input type="number" class="form-control" id="armor_class" name="armor_class" 
                                   value="<?php echo isset($_POST['armor_class']) ? $_POST['armor_class'] : '10'; ?>" 
                                   min="1" max="30" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="race_size" class="form-label">Taille</label>
                            <input type="text" class="form-control" id="race_size" name="race_size" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="speed" class="form-label">Vitesse (pieds)</label>
                            <input type="text" class="form-control" id="speed" name="speed" readonly>
                            <small class="form-text text-muted" id="speed-info">Vitesse de base</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compétences -->
            <div class="form-section">
                <h3><i class="fas fa-star me-2"></i>Compétences</h3>
                <p class="text-muted">Cochez les compétences dans lesquelles votre personnage est compétent. Les compétences d'armure sont automatiquement attribuées selon la classe.</p>
                <div class="row">
                    <?php
                    $classId = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;
                    $backgroundId = isset($_POST['background_id']) ? (int)$_POST['background_id'] : null;
                    $skillData = getSkillsByCategoryWithClass($classId);
                    $skillCategories = $skillData['categories'];
                    $classProficiencies = $skillData['classProficiencies'];
                    
                    // Récupérer les compétences d'historique
                    $backgroundProficiencies = ['skills' => [], 'tools' => []];
                    if ($backgroundId) {
                        $backgroundProficiencies = getBackgroundProficiencies($backgroundId);
                    }
                    
                    // Debug: Afficher les compétences de classe pour debug
                    if ($classId) {
                        echo "<!-- Debug: Class ID = $classId -->\n";
                        echo "<!-- Debug: Armor proficiencies = " . json_encode($classProficiencies['armor']) . " -->\n";
                    }
                    
                    foreach ($skillCategories as $category => $skills): ?>
                        <div class="col-md-6 col-lg-3 mb-4">
                            <h6 class="text-primary border-bottom pb-2"><?php echo $category; ?></h6>
                            
                            <?php if ($category === 'Compétences'): ?>
                                <!-- Compétences classiques organisées par caractéristique -->
                                <?php
                                $abilityGroups = [
                                    'Force' => [],
                                    'Dextérité' => [],
                                    'Intelligence' => [],
                                    'Sagesse' => [],
                                    'Charisme' => []
                                ];
                                
                                foreach ($skills as $skill => $ability) {
                                    $abilityGroups[$ability][] = $skill;
                                }
                                
                                foreach ($abilityGroups as $ability => $abilitySkills): ?>
                                    <div class="mb-2">
                                        <small class="text-muted fw-bold"><?php echo $ability; ?></small>
                                        <?php foreach ($abilitySkills as $skill): ?>
                                            <?php 
                                            // Vérifier si la compétence est maîtrisée par la classe ou l'historique
                                            $isClassProficient = in_array($skill, array_merge($classProficiencies['armor'], $classProficiencies['weapon'], $classProficiencies['tool']));
                                            $isBackgroundProficient = in_array($skill, array_merge($backgroundProficiencies['skills'], $backgroundProficiencies['tools']));
                                            $isProficient = $isClassProficient || $isBackgroundProficient;
                                            $isSelected = (isset($_POST['skills']) && in_array($skill, $_POST['skills'])) || $isProficient;
                                            $labelClass = $isProficient ? 'text-success' : 'text-muted';
                                            ?>
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>" 
                                                       name="skills[]" 
                                                       value="<?php echo $skill; ?>"
                                                       <?php echo $isSelected ? 'checked' : ''; ?>
                                                       <?php echo $isProficient ? 'disabled' : ''; ?>
                                                       <?php echo $isBackgroundProficient ? 'data-background-skill="true"' : ''; ?>>
                                                <label class="form-check-label small <?php echo $labelClass; ?>" for="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>">
                                                    <?php echo $skill; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($category === 'Armure'): ?>
                                <!-- Compétences d'armure automatiques -->
                                <?php foreach ($skills as $skill): ?>
                                    <?php 
                                    $isProficient = in_array($skill, $classProficiencies['armor']);
                                    $labelClass = $isProficient ? 'text-success' : 'text-muted';
                                    ?>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" 
                                               id="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>" 
                                               name="skills[]" 
                                               value="<?php echo $skill; ?>"
                                               <?php echo $isProficient ? 'checked' : ''; ?>
                                               disabled>
                                        <label class="form-check-label small <?php echo $labelClass; ?>" for="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>">
                                            <?php echo $skill; ?>
                                            <?php if ($isProficient): ?>
                                                <small class="text-success">(Automatique)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($category === 'Arme'): ?>
                                <!-- Compétences d'armes automatiques -->
                                <?php foreach ($skills as $skill): ?>
                                    <?php 
                                    $isProficient = in_array($skill, $classProficiencies['weapon']);
                                    $labelClass = $isProficient ? 'text-success' : 'text-muted';
                                    ?>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" 
                                               id="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>" 
                                               name="skills[]" 
                                               value="<?php echo $skill; ?>"
                                               <?php echo $isProficient ? 'checked' : ''; ?>
                                               disabled>
                                        <label class="form-check-label small <?php echo $labelClass; ?>" for="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>">
                                            <?php echo $skill; ?>
                                            <?php if ($isProficient): ?>
                                                <small class="text-success">(Automatique)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($category === 'Outil'): ?>
                                <!-- Compétences d'outils automatiques -->
                                <?php foreach ($skills as $skill): ?>
                                    <?php 
                                    $isProficient = in_array($skill, $classProficiencies['tool']);
                                    $labelClass = $isProficient ? 'text-success' : 'text-muted';
                                    ?>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" 
                                               id="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>" 
                                               name="skills[]" 
                                               value="<?php echo $skill; ?>"
                                               <?php echo $isProficient ? 'checked' : ''; ?>
                                               disabled>
                                        <label class="form-check-label small <?php echo $labelClass; ?>" for="skill_<?php echo strtolower(str_replace([' ', '\''], ['_', ''], $skill)); ?>">
                                            <?php echo $skill; ?>
                                            <?php if ($isProficient): ?>
                                                <small class="text-success">(Automatique)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="characters.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
                <button type="submit" class="btn btn-dnd">
                    <i class="fas fa-save me-2"></i>Créer le Personnage
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour récupérer les informations de race
        function loadRaceInfo(raceId) {
            if (!raceId) {
                document.getElementById('race-info').style.display = 'none';
                clearRaceBonuses();
                return;
            }
            
            fetch(`get_race_info.php?id=${raceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRaceInfo(data.race);
                    } else {
                        console.error('Erreur lors du chargement des informations de race');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }
        
        // Fonction pour afficher les informations de race
        function displayRaceInfo(race) {
            const raceInfo = document.getElementById('race-info');
            const raceDetails = document.getElementById('race-details');
            const raceImage = document.getElementById('race-image');
            
            let details = `<div class="row">`;
            
            // Description de la race
            if (race.description) {
                details += `<div class="col-md-12 mb-3"><strong>Description :</strong><br>${race.description}</div>`;
            }
            
            // Bonus de caractéristiques
            const bonuses = [];
            if (race.strength_bonus > 0) bonuses.push(`<span class="badge bg-primary me-1">Force +${race.strength_bonus}</span>`);
            if (race.dexterity_bonus > 0) bonuses.push(`<span class="badge bg-success me-1">Dextérité +${race.dexterity_bonus}</span>`);
            if (race.constitution_bonus > 0) bonuses.push(`<span class="badge bg-warning me-1">Constitution +${race.constitution_bonus}</span>`);
            if (race.intelligence_bonus > 0) bonuses.push(`<span class="badge bg-info me-1">Intelligence +${race.intelligence_bonus}</span>`);
            if (race.wisdom_bonus > 0) bonuses.push(`<span class="badge bg-secondary me-1">Sagesse +${race.wisdom_bonus}</span>`);
            if (race.charisma_bonus > 0) bonuses.push(`<span class="badge bg-dark me-1">Charisme +${race.charisma_bonus}</span>`);
            
            if (bonuses.length > 0) {
                details += `<div class="col-md-6"><strong>Bonus de caractéristiques :</strong><br>${bonuses.join(' ')}</div>`;
            }
            
            // Vision
            if (race.vision) {
                details += `<div class="col-md-6"><strong>Vision :</strong><br>${race.vision}</div>`;
            }
            
            details += `</div>`;
            
            // Traits
            if (race.traits) {
                details += `<div class="mt-2"><strong>Traits raciaux :</strong><br><small>${race.traits}</small></div>`;
            }
            
            raceDetails.innerHTML = details;
            
            // Afficher l'image de la race
            if (race.image) {
                raceImage.src = `images/races/${race.image}`;
                raceImage.style.display = 'block';
            } else {
                raceImage.style.display = 'none';
            }
            
            raceInfo.style.display = 'block';
            
            // Afficher les bonus sous chaque caractéristique
            displayRaceBonuses(race);
            
            // Mettre à jour les champs de taille et vitesse
            updateRaceFields(race);
        }
        
        // Fonction pour afficher les bonus raciaux dans le tableau
        function displayRaceBonuses(race) {
            const bonuses = {
                'strength': race.strength_bonus,
                'dexterity': race.dexterity_bonus,
                'constitution': race.constitution_bonus,
                'intelligence': race.intelligence_bonus,
                'wisdom': race.wisdom_bonus,
                'charisma': race.charisma_bonus
            };
            
            Object.keys(bonuses).forEach(stat => {
                const bonusElement = document.getElementById(`racial-${stat}-bonus`);
                if (bonuses[stat] > 0) {
                    bonusElement.textContent = `+${bonuses[stat]}`;
                    bonusElement.className = 'text-success';
                } else {
                    bonusElement.textContent = '+0';
                    bonusElement.className = 'text-muted';
                }
            });
            
            // Recalculer les totaux
            calculateTotals();
        }
        
        // Fonction pour effacer les bonus raciaux
        function clearRaceBonuses() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            stats.forEach(stat => {
                const bonusElement = document.getElementById(`racial-${stat}-bonus`);
                bonusElement.textContent = '+0';
                bonusElement.className = 'text-muted';
            });
            
            // Effacer les champs de race
            document.getElementById('race_size').value = '';
            document.getElementById('race_languages').value = '';
            
            // Effacer la vitesse
            document.getElementById('speed').value = '';
            document.getElementById('speed-info').textContent = 'Vitesse de base';
            document.getElementById('speed-info').style.color = '#6c757d';
            
            // Recalculer les totaux
            calculateTotals();
        }
        
        // Fonction pour calculer les totaux des caractéristiques
        function calculateTotals() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            
            stats.forEach(stat => {
                // Valeur de base
                const baseValue = parseInt(document.getElementById(stat).value) || 0;
                
                // Bonus racial
                const racialBonus = parseInt(document.getElementById(`racial-${stat}-bonus`).textContent.replace('+', '')) || 0;
                
                // Bonus d'équipement (pour l'instant toujours 0)
                const equipmentBonus = 0;
                
                // Bonus temporaire (pour l'instant toujours 0)
                const tempBonus = 0;
                
                // Total
                const total = baseValue + racialBonus + equipmentBonus + tempBonus;
                
                // Modificateur = (caractéristique - 10) / 2, arrondi vers le bas
                const modifier = Math.floor((total - 10) / 2);
                const modifierText = modifier >= 0 ? `+${modifier}` : `${modifier}`;
                
                // Afficher le total
                document.getElementById(`total-${stat}`).textContent = `${total} (${modifierText})`;
            });
        }
        
        // Fonction pour calculer le coût d'une caractéristique selon le barème D&D 5e
        function calculateStatCost(value) {
            if (value <= 8) return 0;
            if (value <= 13) return value - 8;
            if (value === 14) return 7; // 5 + 2
            if (value === 15) return 9; // 5 + 2 + 2
            return 0;
        }
        
        // Fonction pour calculer le coût total des caractéristiques
        function calculateTotalCost() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            let totalCost = 0;
            
            stats.forEach(stat => {
                const value = parseInt(document.getElementById(stat).value) || 8;
                const cost = calculateStatCost(value);
                totalCost += cost;
                
                // Afficher le coût individuel
                document.getElementById(`${stat}-cost`).textContent = `Coût: ${cost} pts`;
            });
            
            return totalCost;
        }
        
        // Fonction pour mettre à jour l'affichage des points restants
        function updatePointsRemaining() {
            const totalCost = calculateTotalCost();
            const remaining = 27 - totalCost;
            const pointsElement = document.getElementById('points-remaining');
            
            pointsElement.textContent = remaining;
            
            if (remaining < 0) {
                pointsElement.className = 'text-danger';
            } else if (remaining === 0) {
                pointsElement.className = 'text-success';
            } else {
                pointsElement.className = 'text-primary';
            }
        }
        
        // Fonction pour ajuster une caractéristique
        function adjustStat(stat, change) {
            const input = document.getElementById(stat);
            const currentValue = parseInt(input.value) || 8;
            const newValue = currentValue + change;
            
            // Vérifier les limites
            if (newValue >= 8 && newValue <= 15) {
                input.value = newValue;
                updatePointsRemaining();
                calculateTotals();
            }
        }
        
        // Fonction pour appliquer la répartition typique
        function applyTypicalDistribution() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            const values = [15, 14, 13, 12, 10, 8];
            
            stats.forEach((stat, index) => {
                document.getElementById(stat).value = values[index];
            });
            
            updatePointsRemaining();
            calculateTotals();
        }
        
        // Fonction pour lancer un dé à 6 faces
        function rollD6() {
            return Math.floor(Math.random() * 6) + 1;
        }
        
        // Fonction pour lancer 4d6 et garder les 3 meilleurs
        function roll4d6Keep3() {
            const rolls = [rollD6(), rollD6(), rollD6(), rollD6()];
            rolls.sort((a, b) => b - a); // Tri décroissant
            return rolls[0] + rolls[1] + rolls[2]; // Somme des 3 meilleurs
        }
        
        // Fonction pour lancer tous les dés
        function rollAllDice() {
            const results = [];
            for (let i = 0; i < 6; i++) {
                results.push(roll4d6Keep3());
            }
            
            // Afficher les résultats avec sélection de caractéristique
            const resultsDiv = document.getElementById('dice-results');
            resultsDiv.innerHTML = `
                <h6>Résultats des dés :</h6>
                <div class="row">
                    ${results.map((result, index) => `
                        <div class="col-md-4 col-6 mb-2">
                            <div class="card">
                                <div class="card-body p-2">
                                    <h5 class="mb-2 text-center">${result}</h5>
                                    <small class="text-muted d-block text-center mb-2">Dé ${index + 1}</small>
                                    <select class="form-select form-select-sm" id="dice-${index}-assignment" onchange="updateDiceAssignment()">
                                        <option value="">Choisir une caractéristique</option>
                                        <option value="strength">Force</option>
                                        <option value="dexterity">Dextérité</option>
                                        <option value="constitution">Constitution</option>
                                        <option value="intelligence">Intelligence</option>
                                        <option value="wisdom">Sagesse</option>
                                        <option value="charisma">Charisme</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="mt-3 text-center">
                    <button type="button" class="btn btn-primary" onclick="assignSelectedDiceResults()" id="assign-button" disabled>
                        <i class="fas fa-check me-2"></i>Assigner les valeurs sélectionnées
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="rollAllDice()">
                        <i class="fas fa-redo me-2"></i>Relancer les dés
                    </button>
                </div>
            `;
        }
        
        // Fonction pour mettre à jour l'état du bouton d'assignation
        function updateDiceAssignment() {
            const assignButton = document.getElementById('assign-button');
            const selects = document.querySelectorAll('[id^="dice-"][id$="-assignment"]');
            
            // Vérifier si toutes les caractéristiques sont assignées
            let allAssigned = true;
            const assignedStats = new Set();
            
            selects.forEach(select => {
                if (select.value === '') {
                    allAssigned = false;
                } else {
                    // Vérifier les doublons
                    if (assignedStats.has(select.value)) {
                        allAssigned = false;
                    } else {
                        assignedStats.add(select.value);
                    }
                }
            });
            
            // Activer/désactiver le bouton
            assignButton.disabled = !allAssigned;
            
            // Mettre à jour le style des sélecteurs en cas de doublon
            selects.forEach(select => {
                if (select.value !== '' && assignedStats.has(select.value)) {
                    const count = Array.from(selects).filter(s => s.value === select.value).length;
                    if (count > 1) {
                        select.classList.add('is-invalid');
                    } else {
                        select.classList.remove('is-invalid');
                    }
                } else {
                    select.classList.remove('is-invalid');
                }
            });
        }
        
        // Fonction pour assigner les résultats de dés sélectionnés aux caractéristiques
        function assignSelectedDiceResults() {
            const selects = document.querySelectorAll('[id^="dice-"][id$="-assignment"]');
            const results = [];
            
            // Récupérer les résultats des dés
            for (let i = 0; i < 6; i++) {
                const diceElement = document.querySelector(`[id="dice-${i}-assignment"]`);
                if (diceElement) {
                    const stat = diceElement.value;
                    const result = parseInt(diceElement.parentElement.parentElement.querySelector('h5').textContent);
                    results.push({ stat, result });
                }
            }
            
            // Assigner les valeurs
            results.forEach(({ stat, result }) => {
                if (stat && result) {
                    document.getElementById(stat).value = result;
                }
            });
            
            updatePointsRemaining();
            calculateTotals();
            
            // Masquer l'interface de dés
            document.getElementById('dice-results').innerHTML = '<em class="text-muted">Valeurs assignées</em>';
        }
        
        // Fonction pour assigner les résultats de dés aux caractéristiques (ancienne version)
        function assignDiceResults(results) {
            // Pour l'instant, assigner dans l'ordre
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            
            stats.forEach((stat, index) => {
                if (results[index]) {
                    document.getElementById(stat).value = results[index];
                }
            });
            
            updatePointsRemaining();
            calculateTotals();
            
            // Masquer l'interface de dés
            document.getElementById('dice-results').innerHTML = '<em class="text-muted">Valeurs assignées</em>';
        }
        
        // Fonction pour basculer entre les méthodes de génération
        function switchGenerationMethod() {
            const pointBuyRadio = document.getElementById('point_buy');
            const diceRollRadio = document.getElementById('dice_roll');
            const pointBuyInterface = document.getElementById('point-buy-interface');
            const diceRollInterface = document.getElementById('dice-roll-interface');
            
            if (pointBuyRadio.checked) {
                pointBuyInterface.style.display = 'block';
                diceRollInterface.style.display = 'none';
                // Réinitialiser les valeurs pour le point buy
                const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
                stats.forEach(stat => {
                    document.getElementById(stat).value = 8;
                    document.getElementById(stat).min = 8;
                    document.getElementById(stat).max = 15;
                });
            } else if (diceRollRadio.checked) {
                pointBuyInterface.style.display = 'none';
                diceRollInterface.style.display = 'block';
                // Réinitialiser les valeurs pour le dice roll
                const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
                stats.forEach(stat => {
                    document.getElementById(stat).min = 3;
                    document.getElementById(stat).max = 18;
                });
            }
            
            updatePointsRemaining();
            calculateTotals();
        }
        
        // Fonction pour mettre à jour les champs de race
        function updateRaceFields(race) {
            // Mettre à jour la taille
            const sizeInput = document.getElementById('race_size');
            const sizeText = race.size === 'P' ? 'Petite' : race.size === 'M' ? 'Moyenne' : 'Grande';
            sizeInput.value = sizeText;
            
            // Mettre à jour la vitesse
            const speedInput = document.getElementById('speed');
            speedInput.value = race.speed ? `${race.speed} pieds` : '';
            
            // Mettre à jour les langues
            updateLanguagesDisplay(race.languages || '');
            
            // Mettre à jour la vitesse de combat
            const combatSpeedInput = document.getElementById('speed');
            const speedInfo = document.getElementById('speed-info');
            
            if (race.speed && race.speed > 0) {
                combatSpeedInput.value = race.speed;
                speedInfo.textContent = `Vitesse raciale : ${race.speed} pieds`;
                speedInfo.style.color = '#28a745';
            } else {
                combatSpeedInput.value = 30;
                speedInfo.textContent = 'Vitesse de base';
                speedInfo.style.color = '#6c757d';
            }
        }
        
        // Fonction pour mettre à jour l'affichage des langues
        function updateLanguagesDisplay(languagesText) {
            const languagesList = document.getElementById('languages-list');
            const additionalLanguageSection = document.getElementById('additional-language-section');
            const additionalLanguageSelect = document.getElementById('additional_language');
            const removeButton = document.getElementById('remove-additional-language');
            
            if (!languagesText) {
                languagesList.innerHTML = '<em class="text-muted">Sélectionnez une race pour voir ses langues</em>';
                additionalLanguageSection.style.display = 'none';
                return;
            }
            
            // Analyser les langues
            const languages = languagesText.split(',').map(lang => lang.trim());
            let hasChoice = false;
            let fixedLanguages = [];
            
            languages.forEach(lang => {
                if (lang.includes('une langue de votre choix') || lang.includes('une langue de choix')) {
                    hasChoice = true;
                } else {
                    fixedLanguages.push(lang);
                }
            });
            
            // Afficher les langues fixes
            let html = '';
            if (fixedLanguages.length > 0) {
                html += '<div class="mb-2"><strong>Langues raciales :</strong></div>';
                fixedLanguages.forEach(lang => {
                    html += `<span class="badge bg-primary me-1 mb-1">${lang}</span>`;
                });
            }
            
            // Gérer la langue de choix
            if (hasChoice) {
                additionalLanguageSection.style.display = 'block';
                populateAdditionalLanguageSelect(fixedLanguages);
                
                // Réinitialiser la sélection
                additionalLanguageSelect.value = '';
                removeButton.style.display = 'none';
            } else {
                additionalLanguageSection.style.display = 'none';
            }
            
            languagesList.innerHTML = html;
        }
        
        // Fonction pour peupler la liste des langues supplémentaires
        function populateAdditionalLanguageSelect(excludeLanguages) {
            const select = document.getElementById('additional_language');
            
            // Charger les langues depuis l'API
            fetch('get_languages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Vider les options existantes (sauf la première)
                        select.innerHTML = '<option value="">Choisir une langue</option>';
                        
                        // Ajouter les langues disponibles
                        data.languages.forEach(language => {
                            if (!excludeLanguages.includes(language.name)) {
                                const option = document.createElement('option');
                                option.value = language.name;
                                option.textContent = `${language.name} (${language.type})`;
                                select.appendChild(option);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des langues:', error);
                });
        }
        
        // Événement de changement de race
        document.getElementById('race_id').addEventListener('change', function() {
            loadRaceInfo(this.value);
        });
        
        // Événement de changement de langue supplémentaire
        document.getElementById('additional_language').addEventListener('change', function() {
            updateAdditionalLanguageDisplay();
        });
        
        // Événement de suppression de la langue supplémentaire
        document.getElementById('remove-additional-language').addEventListener('click', function() {
            const select = document.getElementById('additional_language');
            const removeButton = document.getElementById('remove-additional-language');
            
            // Réinitialiser la sélection
            select.value = '';
            
            // Supprimer la langue de choix de l'affichage
            const languagesList = document.getElementById('languages-list');
            const currentContent = languagesList.innerHTML;
            
            // Supprimer la section "Langue de choix" et le badge associé
            let updatedContent = currentContent.replace(/<div class="mt-2"><strong>Langue de choix :<\/strong><\/div>\s*<span[^>]*>.*?<\/span>/s, '');
            // Supprimer aussi le badge de langue de choix s'il existe
            updatedContent = updatedContent.replace(/<span[^>]*id="selected-additional-language"[^>]*>.*?<\/span>/g, '');
            
            languagesList.innerHTML = updatedContent;
            
            // Masquer le bouton de suppression
            removeButton.style.display = 'none';
        });
        
        // Fonction pour mettre à jour l'affichage de la langue supplémentaire
        function updateAdditionalLanguageDisplay() {
            const select = document.getElementById('additional_language');
            const languagesList = document.getElementById('languages-list');
            const removeButton = document.getElementById('remove-additional-language');
            
            if (select.value) {
                // Remplacer la langue de choix existante par la nouvelle
                const selectedLanguage = select.value;
                
                // Supprimer d'abord la section "Langue de choix" existante si elle existe
                let currentContent = languagesList.innerHTML;
                currentContent = currentContent.replace(/<div class="mt-2"><strong>Langue de choix :<\/strong><\/div>\s*<span[^>]*>.*?<\/span>/s, '');
                
                // Supprimer aussi l'ancien badge de langue de choix s'il existe
                currentContent = currentContent.replace(/<span[^>]*id="selected-additional-language"[^>]*>.*?<\/span>/g, '');
                
                // Ajouter la nouvelle langue directement dans la section "Langues raciales"
                // Trouver la section "Langues raciales" et ajouter la langue
                if (currentContent.includes('Langues raciales :')) {
                    // Ajouter la langue à la fin de la section existante
                    // Chercher la fin de la section des badges de langues raciales
                    const regex = /(<div class="mb-2"><strong>Langues raciales :<\/strong><\/div>)(.*?)(<div class="mt-2">|$)/s;
                    const match = currentContent.match(regex);
                    
                    if (match) {
                        const beforeSection = match[1];
                        const badgesSection = match[2];
                        const afterSection = match[3];
                        
                        // Ajouter la nouvelle langue dans la section des badges
                        const newBadgesSection = badgesSection + `<span class="badge bg-success me-1 mb-1" id="selected-additional-language">${selectedLanguage}</span>`;
                        currentContent = beforeSection + newBadgesSection + afterSection;
                    } else {
                        // Fallback: ajouter à la fin
                        currentContent += `<span class="badge bg-success me-1 mb-1" id="selected-additional-language">${selectedLanguage}</span>`;
                    }
                } else {
                    // Créer une nouvelle section "Langues raciales" avec la langue
                    currentContent += '<div class="mb-2"><strong>Langues raciales :</strong></div>';
                    currentContent += `<span class="badge bg-success me-1 mb-1" id="selected-additional-language">${selectedLanguage}</span>`;
                }
                
                languagesList.innerHTML = currentContent;
                removeButton.style.display = 'block';
            } else {
                // Supprimer la langue de choix de l'affichage
                const currentContent = languagesList.innerHTML;
                let updatedContent = currentContent.replace(/<div class="mt-2"><strong>Langue de choix :<\/strong><\/div>\s*<span[^>]*>.*?<\/span>/s, '');
                updatedContent = updatedContent.replace(/<span[^>]*id="selected-additional-language"[^>]*>.*?<\/span>/g, '');
                languagesList.innerHTML = updatedContent;
                removeButton.style.display = 'none';
            }
        }
        
        // Charger les informations de race au chargement de la page si une race est déjà sélectionnée
        document.addEventListener('DOMContentLoaded', function() {
            const selectedRace = document.getElementById('race_id').value;
            if (selectedRace) {
                loadRaceInfo(selectedRace);
            }
            
            // Ajouter des event listeners pour recalculer les totaux quand les caractéristiques changent
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            stats.forEach(stat => {
                const input = document.getElementById(stat);
                if (input) {
                    input.addEventListener('input', function() {
                        updatePointsRemaining();
                        calculateTotals();
                    });
                    input.addEventListener('change', function() {
                        updatePointsRemaining();
                        calculateTotals();
                    });
                }
            });
            
            // Ajouter des event listeners pour les boutons radio de génération
            document.getElementById('point_buy').addEventListener('change', switchGenerationMethod);
            document.getElementById('dice_roll').addEventListener('change', switchGenerationMethod);
            
            // Initialiser l'interface
            switchGenerationMethod();
            updatePointsRemaining();
            calculateTotals();
            
            // Gestion du calcul automatique du niveau basé sur l'expérience
            const experienceInput = document.getElementById('experience_points');
            const calculatedLevelDiv = document.getElementById('calculated_level');
            
            // Tableau des seuils d'expérience (niveau -> XP requis)
            const experienceThresholds = {
                1: 0, 2: 300, 3: 900, 4: 2700, 5: 6500, 6: 14000, 7: 23000, 8: 34000,
                9: 48000, 10: 64000, 11: 85000, 12: 100000, 13: 120000, 14: 140000,
                15: 165000, 16: 195000, 17: 225000, 18: 265000, 19: 305000, 20: 355000
            };
            
            function calculateLevelFromXP(xp) {
                let level = 1;
                for (let lvl = 20; lvl >= 1; lvl--) {
                    if (xp >= experienceThresholds[lvl]) {
                        level = lvl;
                        break;
                    }
                }
                return level;
            }
            
            function updateCalculatedLevel() {
                const xp = parseInt(experienceInput.value) || 0;
                const level = calculateLevelFromXP(xp);
                calculatedLevelDiv.textContent = `Niveau ${level}`;
            }
            
            experienceInput.addEventListener('input', updateCalculatedLevel);
            
            // Initialiser l'affichage
            updateCalculatedLevel();
            
            // Gestion des compétences automatiques (armure, armes, outils)
            const classSelect = document.getElementById('class_id');
            const armorCheckboxes = document.querySelectorAll('input[name="skills[]"][value*="Armure"], input[name="skills[]"][value*="Bouclier"]');
            const weaponCheckboxes = document.querySelectorAll('input[name="skills[]"][value*="Armes"]');
            const toolCheckboxes = document.querySelectorAll('input[name="skills[]"][value*="Outils"], input[name="skills[]"][value*="Instruments"], input[name="skills[]"][value*="Jeux"], input[name="skills[]"][value*="Véhicules"]');
            
            function updateClassProficiencies(classId) {
                if (!classId) {
                    // Aucune classe sélectionnée, décocher toutes les cases automatiques de classe
                    [...armorCheckboxes, ...weaponCheckboxes, ...toolCheckboxes].forEach(checkbox => {
                        // Ne pas toucher aux compétences d'historique
                        if (checkbox.dataset.backgroundSkill !== 'true') {
                            checkbox.checked = false;
                            checkbox.disabled = true;
                            const label = checkbox.nextElementSibling;
                            label.classList.add('text-muted');
                            label.classList.remove('text-success');
                            const autoText = label.querySelector('.text-success');
                            if (autoText) autoText.remove();
                        }
                    });
                    return;
                }
                
                // Récupérer les compétences de la classe via AJAX
                fetch(`get_class_armor_proficiencies.php?id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mettre à jour les cases d'armure
                            armorCheckboxes.forEach(checkbox => {
                                const skillName = checkbox.value;
                                const isProficient = data.armorProficiencies.includes(skillName);
                                // Ne pas écraser les compétences d'historique
                                if (checkbox.dataset.backgroundSkill !== 'true') {
                                    updateCheckbox(checkbox, isProficient);
                                }
                            });
                            
                            // Mettre à jour les cases d'armes
                            weaponCheckboxes.forEach(checkbox => {
                                const skillName = checkbox.value;
                                const isProficient = data.weaponProficiencies.includes(skillName);
                                // Ne pas écraser les compétences d'historique
                                if (checkbox.dataset.backgroundSkill !== 'true') {
                                    updateCheckbox(checkbox, isProficient);
                                }
                            });
                            
                            // Mettre à jour les cases d'outils
                            toolCheckboxes.forEach(checkbox => {
                                const skillName = checkbox.value;
                                const isProficient = data.toolProficiencies.includes(skillName);
                                // Ne pas écraser les compétences d'historique
                                if (checkbox.dataset.backgroundSkill !== 'true') {
                                    updateCheckbox(checkbox, isProficient);
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des compétences:', error);
                    });
            }
            
            function updateCheckbox(checkbox, isProficient) {
                checkbox.checked = isProficient;
                checkbox.disabled = true;
                
                const label = checkbox.nextElementSibling;
                if (isProficient) {
                    label.classList.remove('text-muted');
                    label.classList.add('text-success');
                    if (!label.querySelector('.text-success')) {
                        const autoText = document.createElement('small');
                        autoText.className = 'text-success';
                        autoText.textContent = ' (Automatique)';
                        label.appendChild(autoText);
                    }
                } else {
                    label.classList.add('text-muted');
                    label.classList.remove('text-success');
                    const autoText = label.querySelector('.text-success');
                    if (autoText) autoText.remove();
                }
            }
            
            // Mettre à jour les compétences quand la classe change
            classSelect.addEventListener('change', function() {
                updateClassProficiencies(this.value);
            });
            
            // Initialiser avec la classe sélectionnée
            updateClassProficiencies(classSelect.value);
            
            // Gestion des détails d'historique
            const backgroundSelect = document.getElementById('background_id');
            const backgroundDetailsSection = document.getElementById('background-details-section');
            const backgroundDetails = document.getElementById('background-details');
            
            function loadBackgroundDetails(backgroundId) {
                if (!backgroundId) {
                    backgroundDetailsSection.style.display = 'none';
                    // Décocher toutes les compétences d'historique
                    updateBackgroundSkills([]);
                    return;
                }
                
                fetch(`get_background_details.php?id=${backgroundId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const background = data.background;
                            
                            let html = `
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">${background.name}</h5>
                                        <p class="card-text">${background.description}</p>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-dice me-2"></i>Compétences maîtrisées</h6>
                                                <p>${background.skill_proficiencies ? JSON.parse(background.skill_proficiencies).join(', ') : 'Aucune'}</p>
                                                
                                                <h6><i class="fas fa-tools me-2"></i>Outils maîtrisés</h6>
                                                <p>${background.tool_proficiencies ? JSON.parse(background.tool_proficiencies).join(', ') : 'Aucun'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-language me-2"></i>Langues</h6>
                                                <p>${background.languages ? JSON.parse(background.languages).join(', ') : 'Aucune'}</p>
                                                
                                                <h6><i class="fas fa-gift me-2"></i>Capacité spéciale</h6>
                                                <p><strong>${background.feature}</strong></p>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <h6><i class="fas fa-backpack me-2"></i>Équipement de départ</h6>
                                            <p>${background.equipment}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            backgroundDetails.innerHTML = html;
                            backgroundDetailsSection.style.display = 'block';
                            
                            // Mettre à jour les compétences cochées
                            const backgroundSkills = background.skill_proficiencies ? JSON.parse(background.skill_proficiencies) : [];
                            const backgroundTools = background.tool_proficiencies ? JSON.parse(background.tool_proficiencies) : [];
                            updateBackgroundSkills([...backgroundSkills, ...backgroundTools]);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des détails de l\'historique:', error);
                    });
            }
            
            // Fonction pour mettre à jour les compétences d'historique
            function updateBackgroundSkills(skills) {
                // Décocher toutes les compétences d'historique précédentes
                document.querySelectorAll('input[name="skills[]"]').forEach(checkbox => {
                    if (checkbox.dataset.backgroundSkill === 'true') {
                        checkbox.checked = false;
                        checkbox.disabled = false;
                        checkbox.dataset.backgroundSkill = 'false';
                        const label = checkbox.closest('.form-check').querySelector('label');
                        label.classList.remove('text-success');
                        label.classList.add('text-muted');
                    }
                });
                
                // Cocher les nouvelles compétences d'historique
                skills.forEach(skill => {
                    const skillId = 'skill_' + skill.toLowerCase().replace(/[ ']/g, '_');
                    const checkbox = document.getElementById(skillId);
                    if (checkbox) {
                        checkbox.checked = true;
                        checkbox.disabled = true;
                        checkbox.dataset.backgroundSkill = 'true';
                        const label = checkbox.closest('.form-check').querySelector('label');
                        label.classList.remove('text-muted');
                        label.classList.add('text-success');
                    }
                });
            }
            
            // Mettre à jour les détails quand l'historique change
            backgroundSelect.addEventListener('change', function() {
                loadBackgroundDetails(this.value);
            });
            
            // Initialiser avec l'historique sélectionné
            loadBackgroundDetails(backgroundSelect.value);
        });
    </script>
</body>
</html>















