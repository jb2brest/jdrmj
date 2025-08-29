<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';

// Récupération des races et classes
$races = $pdo->query("SELECT * FROM races ORDER BY name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $race_id = (int)$_POST['race_id'];
    $class_id = (int)$_POST['class_id'];
    $level = (int)$_POST['level'];
    
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
    $background = sanitizeInput($_POST['background']);
    $alignment = sanitizeInput($_POST['alignment']);
    
    // Validation
    $errors = [];
    
    if (strlen($name) < 2) {
        $errors[] = "Le nom du personnage doit contenir au moins 2 caractères.";
    }
    
    if ($level < 1 || $level > 20) {
        $errors[] = "Le niveau doit être entre 1 et 20.";
    }
    
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
        $stmt = $pdo->prepare("SELECT hit_die FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $hitDie = $stmt->fetch()['hit_die'];
        
        $constitutionModifier = getAbilityModifier($constitution);
        $maxHP = calculateMaxHP($level, $hitDie, $constitutionModifier);
        
        // Calcul du bonus de maîtrise
        $proficiencyBonus = getProficiencyBonus($level);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, level, 
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, speed, hit_points_max, hit_points_current, proficiency_bonus,
                    background, alignment
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $name, $race_id, $class_id, $level,
                $strength, $dexterity, $constitution, $intelligence, $wisdom, $charisma,
                $armor_class, $speed, $maxHP, $maxHP, $proficiencyBonus,
                $background, $alignment
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du personnage</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                            <label for="level" class="form-label">Niveau</label>
                            <input type="number" class="form-control" id="level" name="level" 
                                   value="<?php echo isset($_POST['level']) ? $_POST['level'] : '1'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="background" class="form-label">Historique</label>
                            <input type="text" class="form-control" id="background" name="background" 
                                   value="<?php echo isset($_POST['background']) ? htmlspecialchars($_POST['background']) : ''; ?>">
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

            <!-- Caractéristiques -->
            <div class="form-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="strength" class="form-label">Force</label>
                            <input type="number" class="form-control stat-input" id="strength" name="strength" 
                                   value="<?php echo isset($_POST['strength']) ? $_POST['strength'] : '10'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="dexterity" class="form-label">Dextérité</label>
                            <input type="number" class="form-control stat-input" id="dexterity" name="dexterity" 
                                   value="<?php echo isset($_POST['dexterity']) ? $_POST['dexterity'] : '10'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="constitution" class="form-label">Constitution</label>
                            <input type="number" class="form-control stat-input" id="constitution" name="constitution" 
                                   value="<?php echo isset($_POST['constitution']) ? $_POST['constitution'] : '10'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="intelligence" class="form-label">Intelligence</label>
                            <input type="number" class="form-control stat-input" id="intelligence" name="intelligence" 
                                   value="<?php echo isset($_POST['intelligence']) ? $_POST['intelligence'] : '10'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="wisdom" class="form-label">Sagesse</label>
                            <input type="number" class="form-control stat-input" id="wisdom" name="wisdom" 
                                   value="<?php echo isset($_POST['wisdom']) ? $_POST['wisdom'] : '10'; ?>" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="charisma" class="form-label">Charisme</label>
                            <input type="number" class="form-control stat-input" id="charisma" name="charisma" 
                                   value="<?php echo isset($_POST['charisma']) ? $_POST['charisma'] : '10'; ?>" 
                                   min="1" max="20" required>
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
                            <label for="speed" class="form-label">Vitesse (pieds)</label>
                            <input type="number" class="form-control" id="speed" name="speed" 
                                   value="<?php echo isset($_POST['speed']) ? $_POST['speed'] : '30'; ?>" 
                                   min="5" max="120" required>
                        </div>
                    </div>
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
</body>
</html>




