<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';

// Suppression d'un personnage
if (isset($_POST['delete_character'])) {
    $character_id = (int)$_POST['character_id'];
    $stmt = $pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
    $stmt->execute([$character_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        $message = displayMessage("Personnage supprimé avec succès.", "success");
    } else {
        $message = displayMessage("Erreur lors de la suppression du personnage.", "error");
    }
}

// Récupération des personnages de l'utilisateur
$stmt = $pdo->prepare("
    SELECT c.*, r.name as race_name, cl.name as class_name 
    FROM characters c 
    JOIN races r ON c.race_id = r.id 
    JOIN classes cl ON c.class_id = cl.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$characters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Personnages - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .character-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .character-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
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
        .stat-badge {
            font-size: 0.8rem;
            margin: 0.1rem;
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
                        <a class="nav-link active" href="characters.php">Mes Personnages</a>
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
            <h1><i class="fas fa-users me-2"></i>Mes Personnages</h1>
            <a href="create_character.php" class="btn btn-dnd">
                <i class="fas fa-plus me-2"></i>Créer un Personnage
            </a>
        </div>

        <?php echo $message; ?>

        <?php if (empty($characters)): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-plus fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">Aucun personnage créé</h3>
                <p class="text-muted">Commencez votre aventure en créant votre premier personnage !</p>
                <a href="create_character.php" class="btn btn-dnd btn-lg">
                    <i class="fas fa-plus me-2"></i>Créer mon Premier Personnage
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($characters as $character): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card character-card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($character['name']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Race</small><br>
                                        <strong><?php echo htmlspecialchars($character['race_name']); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Classe</small><br>
                                        <strong><?php echo htmlspecialchars($character['class_name']); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Niveau</small><br>
                                        <strong><?php echo $character['level']; ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Points de Vie</small><br>
                                        <strong><?php echo $character['hit_points_current']; ?>/<?php echo $character['hit_points_max']; ?></strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">Caractéristiques principales</small><br>
                                    <span class="badge bg-secondary stat-badge">FOR <?php echo $character['strength']; ?></span>
                                    <span class="badge bg-secondary stat-badge">DEX <?php echo $character['dexterity']; ?></span>
                                    <span class="badge bg-secondary stat-badge">CON <?php echo $character['constitution']; ?></span>
                                    <span class="badge bg-secondary stat-badge">INT <?php echo $character['intelligence']; ?></span>
                                    <span class="badge bg-secondary stat-badge">SAG <?php echo $character['wisdom']; ?></span>
                                    <span class="badge bg-secondary stat-badge">CHA <?php echo $character['charisma']; ?></span>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">Classe d'Armure</small><br>
                                    <strong><?php echo $character['armor_class']; ?></strong>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <a href="view_character.php?id=<?php echo $character['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Voir
                                    </a>
                                    <a href="edit_character.php?id=<?php echo $character['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce personnage ?');">
                                        <input type="hidden" name="character_id" value="<?php echo $character['id']; ?>">
                                        <button type="submit" name="delete_character" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash me-1"></i>Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
