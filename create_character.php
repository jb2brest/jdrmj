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
                
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="strength" class="form-label">Force</label>
                            <input type="number" class="form-control stat-input" id="strength" name="strength" 
                                   value="<?php echo isset($_POST['strength']) ? $_POST['strength'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="strength-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="dexterity" class="form-label">Dextérité</label>
                            <input type="number" class="form-control stat-input" id="dexterity" name="dexterity" 
                                   value="<?php echo isset($_POST['dexterity']) ? $_POST['dexterity'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="dexterity-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="constitution" class="form-label">Constitution</label>
                            <input type="number" class="form-control stat-input" id="constitution" name="constitution" 
                                   value="<?php echo isset($_POST['constitution']) ? $_POST['constitution'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="constitution-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="intelligence" class="form-label">Intelligence</label>
                            <input type="number" class="form-control stat-input" id="intelligence" name="intelligence" 
                                   value="<?php echo isset($_POST['intelligence']) ? $_POST['intelligence'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="intelligence-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="wisdom" class="form-label">Sagesse</label>
                            <input type="number" class="form-control stat-input" id="wisdom" name="wisdom" 
                                   value="<?php echo isset($_POST['wisdom']) ? $_POST['wisdom'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="wisdom-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="charisma" class="form-label">Charisme</label>
                            <input type="number" class="form-control stat-input" id="charisma" name="charisma" 
                                   value="<?php echo isset($_POST['charisma']) ? $_POST['charisma'] : '10'; ?>" 
                                   min="1" max="20" required>
                            <small class="form-text text-muted" id="charisma-bonus"></small>
                        </div>
                    </div>
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
        
        // Fonction pour afficher les bonus sous chaque caractéristique
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
                const bonusElement = document.getElementById(`${stat}-bonus`);
                if (bonuses[stat] > 0) {
                    bonusElement.textContent = `+${bonuses[stat]} racial`;
                    bonusElement.style.color = '#28a745';
                } else {
                    bonusElement.textContent = '';
                }
            });
        }
        
        // Fonction pour effacer les bonus
        function clearRaceBonuses() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            stats.forEach(stat => {
                const bonusElement = document.getElementById(`${stat}-bonus`);
                bonusElement.textContent = '';
            });
            
            // Effacer les champs de race
            document.getElementById('race_size').value = '';
            document.getElementById('race_languages').value = '';
            
            // Effacer la vitesse
            document.getElementById('speed').value = '';
            document.getElementById('speed-info').textContent = 'Vitesse de base';
            document.getElementById('speed-info').style.color = '#6c757d';
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
        });
    </script>
</body>
</html>















