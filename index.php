<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<?php
$page_title = "Gestionnaire de Personnages D&D";
$current_page = "index";
$show_hero = true;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fonctionnalités</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-edit fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Création de Personnages</h5>
                            <p class="card-text">Créez facilement vos personnages avec toutes les races et classes D&D 5e, calcul automatique des statistiques.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-scroll fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Gestion des Sorts</h5>
                            <p class="card-text">Gérez les sorts de vos lanceurs de sorts, avec descriptions complètes et gestion des emplacements.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Combat & Équipement</h5>
                            <p class="card-text">Suivez les points de vie, classe d'armure, initiative et gérez l'équipement de vos personnages.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DM Features Section -->
    <?php if (User::isDMOrAdmin()): ?>
    <section class="bg-dark text-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fonctionnalités Maître de Donjon</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card bg-dark border-light h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Mes Sessions</h5>
                            <p class="card-text">Gérez vos sessions de jeu et vos campagnes.</p>
                            <a href="campaigns.php" class="btn btn-primary">Voir mes sessions</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-light h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-dragon fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Bestiaire D&D</h5>
                            <p class="card-text">Parcourez le bestiaire D&D complet.</p>
                            <a href="bestiary.php" class="btn btn-success">Parcourir le bestiaire</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-light h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Créer des MNJ</h5>
                            <p class="card-text">Créez des monstres et personnages non-joueurs.</p>
                            <a href="create_monster_npc.php" class="btn btn-warning">Créer un MNJ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stats Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="text-primary">
                        <i class="fas fa-users"></i>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                        $userCount = $stmt->fetch()['count'];
                        echo $userCount;
                        ?>
                    </h3>
                    <p>Joueurs Inscrits</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-success">
                        <i class="fas fa-user-friends"></i>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM characters");
                        $charCount = $stmt->fetch()['count'];
                        echo $charCount;
                        ?>
                    </h3>
                    <p>Personnages Créés</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-warning">
                        <i class="fas fa-dragon"></i>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM races");
                        $raceCount = $stmt->fetch()['count'];
                        echo $raceCount;
                        ?>
                    </h3>
                    <p>Races Disponibles</p>
                </div>
                <div class="col-md-3">
                    <h3 class="text-info">
                        <i class="fas fa-sword"></i>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
                        $classCount = $stmt->fetch()['count'];
                        echo $classCount;
                        ?>
                    </h3>
                    <p>Classes Disponibles</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p>&copy; 2024 JDR 4 MJ - Gestionnaire de Personnages D&D</p>
            <p class="small">Créé pour les Maîtres de Donjon et leurs joueurs</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

