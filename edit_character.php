<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$character_id = (int)($_GET['id'] ?? 0);

if ($character_id === 0) {
    header('Location: characters.php');
    exit;
}

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $user_id]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit;
}

$error_message = '';
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $race_id = (int)($_POST['race_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $level = (int)($_POST['level'] ?? 1);
    $experience_points = (int)($_POST['experience_points'] ?? 0);
    
    // Statistiques
    $strength = (int)($_POST['strength'] ?? 10);
    $dexterity = (int)($_POST['dexterity'] ?? 10);
    $constitution = (int)($_POST['constitution'] ?? 10);
    $intelligence = (int)($_POST['intelligence'] ?? 10);
    $wisdom = (int)($_POST['wisdom'] ?? 10);
    $charisma = (int)($_POST['charisma'] ?? 10);
    
    // Combat
    $armor_class = (int)($_POST['armor_class'] ?? 10);
    $initiative = (int)($_POST['initiative'] ?? 0);
    $speed = (int)($_POST['speed'] ?? 30);
    $hit_points_max = (int)($_POST['hit_points_max'] ?? 0);
    $hit_points_current = (int)($_POST['hit_points_current'] ?? 0);
    
    // Autres
    $proficiency_bonus = (int)($_POST['proficiency_bonus'] ?? 2);
    $background = trim($_POST['background'] ?? '');
    $alignment = trim($_POST['alignment'] ?? '');
    $personality_traits = trim($_POST['personality_traits'] ?? '');
    $ideals = trim($_POST['ideals'] ?? '');
    $bonds = trim($_POST['bonds'] ?? '');
    $flaws = trim($_POST['flaws'] ?? '');
    
    if ($name === '') {
        $error_message = "Le nom du personnage est obligatoire.";
    } elseif ($race_id === 0 || $class_id === 0) {
        $error_message = "Veuillez sélectionner une race et une classe.";
        } else {
        // Upload de photo de profil si fournie
        $profile_photo = $character['profile_photo']; // Garder l'ancienne photo par défaut
        
        // Réinitialiser les messages d'erreur pour cette section
        $error_message = '';
        
        
        
        // Traitement de l'upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['profile_photo']['tmp_name'];
            $size = (int)$_FILES['profile_photo']['size'];
            $originalName = $_FILES['profile_photo']['name'];
            

            
            // Validation simple
            if ($size > 2 * 1024 * 1024) {
                $error_message = "Image trop volumineuse (max 2 Mo).";
            } else {
                // Créer le dossier d'upload
                $subdir = 'uploads/profiles/' . date('Y/m');
                $diskDir = __DIR__ . '/' . $subdir;
                
                if (!is_dir($diskDir)) {
                    mkdir($diskDir, 0755, true);
                }
                
                // Générer un nom de fichier unique
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                if (empty($ext)) $ext = 'jpg'; // Extension par défaut
                $basename = bin2hex(random_bytes(8)) . '.' . $ext;
                $diskPath = $diskDir . '/' . $basename;
                $webPath = $subdir . '/' . $basename;
                
                // Upload du fichier
                if (move_uploaded_file($tmp, $diskPath)) {
                    $profile_photo = $webPath;
                } else {
                    $error_message = "Échec de l'upload de la photo.";
                }
            }
        }
        
        if (empty($error_message)) {
            $stmt = $pdo->prepare("
                UPDATE characters SET 
                    name = ?, race_id = ?, class_id = ?, level = ?, experience_points = ?,
                    strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ?,
                    armor_class = ?, initiative = ?, speed = ?, hit_points_max = ?, hit_points_current = ?,
                    proficiency_bonus = ?, background = ?, alignment = ?, personality_traits = ?, ideals = ?, bonds = ?, flaws = ?, profile_photo = ?
                WHERE id = ? AND user_id = ?
            ");
            
            try {
                $result = $stmt->execute([
                    $name, $race_id, $class_id, $level, $experience_points,
                    $strength, $dexterity, $constitution, $intelligence, $wisdom, $charisma,
                    $armor_class, $initiative, $speed, $hit_points_max, $hit_points_current,
                    $proficiency_bonus, $background, $alignment, $personality_traits, $ideals, $bonds, $flaws, $profile_photo,
                    $character_id, $user_id
                ]);
                
                if ($result) {
                    $success_message = "Personnage mis à jour avec succès.";
                    if ($profile_photo && $profile_photo !== $character['profile_photo']) {
                        $success_message .= " Photo de profil mise à jour.";
                    }
                } else {
                    $error_message = "Échec de la mise à jour du personnage.";
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
            
            // Recharger les données du personnage
            $stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
            $stmt->execute([$character_id, $user_id]);
            $character = $stmt->fetch();
        }
    }
}

// Charger les races et classes
$races = $pdo->query("SELECT * FROM races ORDER BY name")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier <?php echo htmlspecialchars($character['name']); ?> - JDR MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">JDR MJ</a>
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
                        <a class="nav-link" href="campaigns.php">Mes Campagnes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public_campaigns.php">Campagnes Publiques</a>
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
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Modifier <?php echo htmlspecialchars($character['name']); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Informations de base</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom du personnage</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($character['name']); ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Niveau</label>
                                            <input type="number" class="form-control" name="level" value="<?php echo (int)$character['level']; ?>" min="1" max="20">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Points d'expérience</label>
                                            <input type="number" class="form-control" name="experience_points" value="<?php echo (int)$character['experience_points']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Race</label>
                                            <select class="form-select" name="race_id" required>
                                                <option value="">Choisir une race</option>
                                                <?php foreach ($races as $race): ?>
                                                    <option value="<?php echo (int)$race['id']; ?>" <?php echo $race['id'] == $character['race_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($race['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Classe</label>
                                            <select class="form-select" name="class_id" required>
                                                <option value="">Choisir une classe</option>
                                                <?php foreach ($classes as $class): ?>
                                                    <option value="<?php echo (int)$class['id']; ?>" <?php echo $class['id'] == $character['class_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($class['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h5>Photo de profil</h5>
                                    <div class="text-center mb-3">
                                        <?php if (!empty($character['profile_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" alt="Photo de profil" class="img-fluid rounded mb-2" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center mb-2" style="width: 150px; height: 150px; margin: 0 auto;">
                                                <i class="fas fa-user text-white" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="profile_photo" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 2 Mo)</div>
                                    </div>
                                </div>
                            </div>

                            <h5>Statistiques</h5>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Force</label>
                                    <input type="number" class="form-control" name="strength" value="<?php echo (int)$character['strength']; ?>" min="1" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Dextérité</label>
                                    <input type="number" class="form-control" name="dexterity" value="<?php echo (int)$character['dexterity']; ?>" min="1" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Constitution</label>
                                    <input type="number" class="form-control" name="constitution" value="<?php echo (int)$character['constitution']; ?>" min="1" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Intelligence</label>
                                    <input type="number" class="form-control" name="intelligence" value="<?php echo (int)$character['intelligence']; ?>" min="1" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sagesse</label>
                                    <input type="number" class="form-control" name="wisdom" value="<?php echo (int)$character['wisdom']; ?>" min="1" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Charisme</label>
                                    <input type="number" class="form-control" name="charisma" value="<?php echo (int)$character['charisma']; ?>" min="1" max="20">
                                </div>
                            </div>

                            <h5>Combat</h5>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Classe d'armure</label>
                                    <input type="number" class="form-control" name="armor_class" value="<?php echo (int)$character['armor_class']; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Initiative</label>
                                    <input type="number" class="form-control" name="initiative" value="<?php echo (int)$character['initiative']; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Vitesse</label>
                                    <input type="number" class="form-control" name="speed" value="<?php echo (int)$character['speed']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Points de vie max</label>
                                    <input type="number" class="form-control" name="hit_points_max" value="<?php echo (int)$character['hit_points_max']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Points de vie actuels</label>
                                    <input type="number" class="form-control" name="hit_points_current" value="<?php echo (int)$character['hit_points_current']; ?>">
                                </div>
                            </div>

                            <h5>Personnalité</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Historique</label>
                                    <input type="text" class="form-control" name="background" value="<?php echo htmlspecialchars($character['background'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Alignement</label>
                                    <input type="text" class="form-control" name="alignment" value="<?php echo htmlspecialchars($character['alignment'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Traits de personnalité</label>
                                    <textarea class="form-control" name="personality_traits" rows="3"><?php echo htmlspecialchars($character['personality_traits'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Idéaux</label>
                                    <textarea class="form-control" name="ideals" rows="3"><?php echo htmlspecialchars($character['ideals'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Liens</label>
                                    <textarea class="form-control" name="bonds" rows="3"><?php echo htmlspecialchars($character['bonds'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Défauts</label>
                                    <textarea class="form-control" name="flaws" rows="3"><?php echo htmlspecialchars($character['flaws'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="characters.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
