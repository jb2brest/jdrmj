<?php
// Navbar commune pour toutes les pages
// Paramètres optionnels :
// $current_page - page actuelle pour marquer le lien comme actif
// $show_hero - afficher la section hero (défaut: false)
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="images/logo.png" alt="JDR 4 MJ" height="30" class="me-2">
            JDR 4 MJ
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Accueil -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($current_page) && $current_page === 'index') ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Accueil
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Personnages -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'characters') ? 'active' : ''; ?>" href="characters.php">
                            <i class="fas fa-users me-1"></i>Personnages
                        </a>
                    </li>
                    
                    <!-- Campagnes -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'campaigns') ? 'active' : ''; ?>" href="campaigns.php">
                            <i class="fas fa-book me-1"></i><?php echo User::isAdmin() ? 'Toutes les Campagnes' : 'Campagnes'; ?>
                        </a>
                    </li>
                    
                    <!-- Mondes (uniquement pour les MJ et Admin) -->
                    <?php if (User::isDMOrAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'manage_worlds') ? 'active' : ''; ?>" href="manage_worlds.php">
                            <i class="fas fa-globe-americas me-1"></i>Mondes
                        </a>
                    </li>
                    
                    <!-- Groupes (uniquement pour les MJ et Admin) -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'manage_groups') ? 'active' : ''; ?>" href="manage_groups.php">
                            <i class="fas fa-users me-1"></i>Groupes
                        </a>
                    </li>
                    
                    <!-- PNJ (uniquement pour les MJ et Admin) -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'manage_npcs') ? 'active' : ''; ?>" href="manage_npcs.php">
                            <i class="fas fa-user-tie me-1"></i>PNJ
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Admin (uniquement pour les administrateurs) -->
                    <?php if (User::isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($current_page) && $current_page === 'admin') ? 'active' : ''; ?>" href="admin_versions.php">
                            <i class="fas fa-shield-alt me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <!-- Menu utilisateur -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (User::isAdmin()): ?>
                                <span class="badge bg-danger ms-1">Admin</span>
                            <?php elseif (isDM()): ?>
                                <span class="badge bg-warning ms-1">MJ</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profil
                            </a></li>
                            <li><a class="dropdown-item" href="public_campaigns.php">
                                <i class="fas fa-book-open me-2"></i>Campagnes Publiques
                            </a></li>
                            <li><a class="dropdown-item" href="character_create_step1.php">
                                <i class="fas fa-plus me-2"></i>Créer un Personnage
                            </a></li>
                            <?php if (User::isDMOrAdmin()): ?>
                            <li><a class="dropdown-item" href="my_monsters.php">
                                <i class="fas fa-dragon me-2"></i>Mes Monstres
                            </a></li>
                            <li><a class="dropdown-item" href="bestiary.php">
                                <i class="fas fa-book me-2"></i>Bestiaire
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Menu non connecté -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($show_hero) && $show_hero): ?>
<!-- Hero Section (optionnelle) -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 mb-4">
            <img src="images/logo.png" alt="JDR 4 MJ" height="60" class="me-3">
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
                <a href="campaigns.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-book me-2"></i><?php echo User::isAdmin() ? 'Toutes les Campagnes' : 'Mes Campagnes'; ?>
                </a>
                <a href="character_create_step1.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-plus me-2"></i>Créer un Personnage
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
