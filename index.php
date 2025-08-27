<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDR 4 MJ - Gestionnaire de Personnages D&D</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="characters.php">Mes Personnages</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_character.php">Créer un Personnage</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">
                <i class="fas fa-dice-d20 me-3"></i>
                Gestionnaire de Personnages D&D
            </h1>
            <p class="lead mb-4">Créez, gérez et développez vos personnages de Donjons & Dragons en toute simplicité</p>
            <?php if (!isLoggedIn()): ?>
                <div class="d-flex justify-content-center gap-3">
                    <a href="register.php" class="btn btn-dnd btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Commencer l'Aventure
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-center gap-3">
                    <a href="characters.php" class="btn btn-dnd btn-lg">
                        <i class="fas fa-users me-2"></i>Mes Personnages
                    </a>
                    <a href="create_character.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Créer un Personnage
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
