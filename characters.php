<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

// Traitement de la suppression d'un personnage
if (isset($_POST['delete_character']) && isset($_POST['character_id'])) {
    $character_id = (int)$_POST['character_id'];
    
    // Vérifier que le personnage appartient à l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT id FROM characters WHERE id = ? AND user_id = ?");
    $stmt->execute([$character_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        // Supprimer le personnage
        $stmt = $pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
        $stmt->execute([$character_id, $_SESSION['user_id']]);
        
        $success_message = "Personnage supprimé avec succès.";
    } else {
        $error_message = "Erreur: Personnage non trouvé ou vous n'avez pas les permissions.";
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
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            font-weight: bold;
        }
        .level-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            font-weight: bold;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
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
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-users me-2"></i>Mes Personnages
            </h1>
            <a href="create_character.php" class="btn btn-dnd">
                <i class="fas fa-plus me-2"></i>Créer un Personnage
            </a>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($success_message)): ?>
            <?php echo displayMessage($success_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <?php echo displayMessage($error_message, 'error'); ?>
        <?php endif; ?>

        <!-- Liste des personnages -->
        <?php if (empty($characters)): ?>
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <h3>Aucun personnage créé</h3>
                <p class="lead">Vous n'avez pas encore créé de personnage. Commencez votre aventure !</p>
                <a href="create_character.php" class="btn btn-dnd btn-lg">
                    <i class="fas fa-plus me-2"></i>Créer votre premier personnage
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($characters as $character): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card character-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($character['name']); ?>
                                    </h5>
                                    <span class="badge level-badge">Niv. <?php echo $character['level']; ?></span>
                                </div>
                                
                                <p class="card-text text-muted mb-3">
                                    <i class="fas fa-dragon me-1"></i><?php echo htmlspecialchars($character['race_name']); ?> 
                                    <i class="fas fa-shield-alt me-2 ms-2"></i><?php echo htmlspecialchars($character['class_name']); ?>
                                </p>
                                
                                <div class="row mb-3">
                                    <div class="col-4 text-center">
                                        <div class="stat-badge rounded p-2">
                                            <div class="small">PV</div>
                                            <strong><?php echo $character['hit_points_current']; ?>/<?php echo $character['hit_points_max']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="stat-badge rounded p-2">
                                            <div class="small">CA</div>
                                            <strong><?php echo $character['armor_class']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="stat-badge rounded p-2">
                                            <div class="small">XP</div>
                                            <strong><?php echo number_format($character['experience_points']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($character['background']): ?>
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-scroll me-1"></i><?php echo htmlspecialchars(substr($character['background'], 0, 100)) . (strlen($character['background']) > 100 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Créé le <?php echo date('d/m/Y', strtotime($character['created_at'])); ?>
                                    </small>
                                    <div class="btn-group" role="group">
                                        <a href="view_character.php?id=<?php echo $character['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_character.php?id=<?php echo $character['id']; ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" 
                                                onclick="confirmDelete(<?php echo $character['id']; ?>, '<?php echo htmlspecialchars($character['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Statistiques -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-chart-bar me-2"></i>Statistiques de vos personnages
                            </h5>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h4 class="text-primary"><?php echo count($characters); ?></h4>
                                    <p class="text-muted">Personnages créés</p>
                                </div>
                                <div class="col-md-3">
                                    <h4 class="text-success">
                                        <?php 
                                        $totalLevel = array_sum(array_column($characters, 'level'));
                                        echo $totalLevel;
                                        ?>
                                    </h4>
                                    <p class="text-muted">Niveaux totaux</p>
                                </div>
                                <div class="col-md-3">
                                    <h4 class="text-warning">
                                        <?php 
                                        $totalXP = array_sum(array_column($characters, 'experience_points'));
                                        echo number_format($totalXP);
                                        ?>
                                    </h4>
                                    <p class="text-muted">XP totale</p>
                                </div>
                                <div class="col-md-3">
                                    <h4 class="text-info">
                                        <?php 
                                        $avgLevel = count($characters) > 0 ? round($totalLevel / count($characters), 1) : 0;
                                        echo $avgLevel;
                                        ?>
                                    </h4>
                                    <p class="text-muted">Niveau moyen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulaire de suppression caché -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_character" value="1">
        <input type="hidden" name="character_id" id="deleteCharacterId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(characterId, characterName) {
            if (confirm('Êtes-vous sûr de vouloir supprimer le personnage "' + characterName + '" ? Cette action est irréversible.')) {
                document.getElementById('deleteCharacterId').value = characterId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>


