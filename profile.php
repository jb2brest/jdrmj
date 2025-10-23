<?php
require_once 'config/database.php';
require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = User::findById($user_id)->toArray();

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $experience_level = sanitizeInput($_POST['experience_level'] ?? 'debutant');
    $preferred_game_system = sanitizeInput($_POST['preferred_game_system'] ?? 'D&D 5e');
    $timezone = sanitizeInput($_POST['timezone'] ?? 'Europe/Paris');
    
    // Validation
    $errors = [];
    if (strlen($bio) > 1000) {
        $errors[] = "La bio ne peut pas dépasser 1000 caractères.";
    }
    
    if (!in_array($experience_level, ['debutant', 'intermediaire', 'expert'])) {
        $errors[] = "Niveau d'expérience invalide.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET bio = ?, experience_level = ?, preferred_game_system = ?, timezone = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$bio, $experience_level, $preferred_game_system, $timezone, $user_id]);
            
            $success_message = "Profil mis à jour avec succès !";
            $user = User::findById($user_id)->toArray(); // Recharger les données
        } catch (Exception $e) {
            $error_message = "Erreur lors de la mise à jour du profil.";
        }
    } else {
        $error_message = implode(" ", $errors);
    }
}

// Récupération des statistiques selon le rôle
if (User::isDMOrAdmin()) {
    // Statistiques pour les MJ
    $stmt = $pdo->prepare("SELECT COUNT(*) as campaign_count FROM campaigns WHERE dm_id = ?");
    $stmt->execute([$user_id]);
    $campaign_count = $stmt->fetch()['campaign_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as player_count FROM campaign_members cm JOIN campaigns c ON cm.campaign_id = c.id WHERE c.dm_id = ? AND cm.role = 'player'");
    $stmt->execute([$user_id]);
    $player_count = $stmt->fetch()['player_count'];
} else {
    // Statistiques pour les joueurs
    $stmt = $pdo->prepare("SELECT COUNT(*) as character_count FROM characters WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $character_count = $stmt->fetch()['character_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as campaign_count FROM campaign_members WHERE user_id = ? AND role = 'player'");
    $stmt->execute([$user_id]);
    $campaign_count = $stmt->fetch()['campaign_count'];
}

// Récupération des notifications non lues
$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$user_id]);
$unread_notifications = $stmt->fetch()['unread_count'];

$page_title = "Profil - " . htmlspecialchars($user['username']);
$current_page = "profile";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 0;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #7f8c8d;
            margin: 0 auto 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .role-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }
        .role-badge.player {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        .role-badge.dm {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        .role-badge.admin {
            background: linear-gradient(45deg, #8e44ad, #9b59b6);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- En-tête du profil -->
    <section class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="lead mb-2">
                        <span class="role-badge <?php echo $user['role']; ?>">
                            <i class="fas fa-<?php 
                                if ($user['role'] === 'admin') echo 'shield-alt';
                                elseif ($user['role'] === 'dm') echo 'crown';
                                else echo 'user';
                            ?> me-2"></i>
                            <?php 
                            $tempUser = new User(null, ['role' => $user['role']]);
                            echo $tempUser->getRoleLabel(); 
                            ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        <?php echo User::getExperienceLevelLabel($user['experience_level']); ?> en <?php echo htmlspecialchars($user['preferred_game_system']); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        Membre depuis <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-4">
        <!-- Messages d'alerte -->
        <?php if (isset($success_message)): ?>
            <?php echo displayMessage($success_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <?php echo displayMessage($error_message, 'error'); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Informations du profil -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Informations du profil
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Parlez-nous de vous, de votre expérience en JDR..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="form-text">Maximum 1000 caractères</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="experience_level" class="form-label">Niveau d'expérience</label>
                                        <select class="form-select" id="experience_level" name="experience_level">
                                            <option value="debutant" <?php echo ($user['experience_level'] ?? 'debutant') === 'debutant' ? 'selected' : ''; ?>>Débutant</option>
                                            <option value="intermediaire" <?php echo ($user['experience_level'] ?? 'debutant') === 'intermediaire' ? 'selected' : ''; ?>>Intermédiaire</option>
                                            <option value="expert" <?php echo ($user['experience_level'] ?? 'debutant') === 'expert' ? 'selected' : ''; ?>>Expert</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="preferred_game_system" class="form-label">Système de jeu préféré</label>
                                        <select class="form-select" id="preferred_game_system" name="preferred_game_system">
                                            <option value="D&D 5e" <?php echo ($user['preferred_game_system'] ?? 'D&D 5e') === 'D&D 5e' ? 'selected' : ''; ?>>D&D 5e</option>
                                            <option value="D&D 3.5" <?php echo ($user['preferred_game_system'] ?? 'D&D 5e') === 'D&D 3.5' ? 'selected' : ''; ?>>D&D 3.5</option>
                                            <option value="Pathfinder" <?php echo ($user['preferred_game_system'] ?? 'D&D 5e') === 'Pathfinder' ? 'selected' : ''; ?>>Pathfinder</option>
                                            <option value="Starfinder" <?php echo ($user['preferred_game_system'] ?? 'D&D 5e') === 'Starfinder' ? 'selected' : ''; ?>>Starfinder</option>
                                            <option value="Autre" <?php echo ($user['preferred_game_system'] ?? 'D&D 5e') === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Fuseau horaire</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Europe/Paris" <?php echo ($user['timezone'] ?? 'Europe/Paris') === 'Europe/Paris' ? 'selected' : ''; ?>>Europe/Paris (UTC+1/+2)</option>
                                    <option value="Europe/London" <?php echo ($user['timezone'] ?? 'Europe/Paris') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (UTC+0/+1)</option>
                                    <option value="America/New_York" <?php echo ($user['timezone'] ?? 'Europe/Paris') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (UTC-5/-4)</option>
                                    <option value="America/Los_Angeles" <?php echo ($user['timezone'] ?? 'Europe/Paris') === 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los_Angeles (UTC-8/-7)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-dnd">
                                <i class="fas fa-save me-2"></i>Mettre à jour le profil
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="col-md-4">
                <div class="stat-card">
                    <h3 class="text-primary">
                        <i class="fas fa-<?php echo User::isDMOrAdmin() ? 'crown' : 'users'; ?>"></i>
                        <?php echo User::isDMOrAdmin() ? $campaign_count : $character_count; ?>
                    </h3>
                    <p class="text-muted">
                        <?php echo User::isDMOrAdmin() ? 'Campagnes créées' : 'Personnages créés'; ?>
                    </p>
                </div>

                <?php if (User::isDMOrAdmin()): ?>
                    <div class="stat-card">
                        <h3 class="text-success">
                            <i class="fas fa-users"></i>
                            <?php echo $player_count; ?>
                        </h3>
                        <p class="text-muted">Joueurs dirigés</p>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <h3 class="text-warning">
                            <i class="fas fa-gamepad"></i>
                            <?php echo $campaign_count; ?>
                        </h3>
                        <p class="text-muted">Campagnes rejointes</p>
                    </div>
                <?php endif; ?>

                <div class="stat-card">
                    <h3 class="text-info">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </h3>
                    <p class="text-muted">Date d'inscription</p>
                </div>

                <!-- Actions rapides -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (User::isDMOrAdmin()): ?>
                                <a href="create_session.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Créer une session
                                </a>
                                <a href="sessions.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-list me-2"></i>Gérer mes sessions
                                </a>
                            <?php else: ?>
                                <a href="character_create_step1.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Créer un personnage
                                </a>
                                <a href="characters.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-list me-2"></i>Mes personnages
                                </a>
                            <?php endif; ?>
                            <a href="messages.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-envelope me-2"></i>Messages
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $unread_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <hr class="my-3">
                            <a href="delete_account.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash-alt me-2"></i>Supprimer mon compte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




















