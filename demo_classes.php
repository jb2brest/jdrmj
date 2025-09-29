<?php
/**
 * Démonstration du système de classes
 * 
 * Ce fichier montre comment utiliser le système de classes
 * pour gérer les mondes de campagne.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';
require_once 'includes/functions.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    die("Veuillez vous connecter pour accéder à cette démonstration.");
}

$user_id = $_SESSION['user_id'];
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_demo_world':
                $monde = new Monde(getPDO());
                $monde->setName("Monde de Démonstration")
                      ->setDescription("Ce monde a été créé pour démontrer l'utilisation de la classe Monde.")
                      ->setCreatedBy($user_id);
                
                $errors = $monde->validate();
                if (empty($errors)) {
                    $monde->save();
                    $message = "✅ Monde de démonstration créé avec succès !";
                } else {
                    $message = "❌ Erreurs: " . implode(', ', $errors);
                }
                break;
                
            case 'delete_demo_world':
                $world_id = (int)($_POST['world_id'] ?? 0);
                $monde = Monde::findById(getPDO(), $world_id);
                
                if ($monde && $monde->getCreatedBy() == $user_id && strpos($monde->getName(), 'Démonstration') !== false) {
                    $monde->delete();
                    $message = "✅ Monde de démonstration supprimé !";
                } else {
                    $message = "❌ Monde non trouvé ou non autorisé.";
                }
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Erreur: " . $e->getMessage();
    }
}

// Récupérer les mondes de l'utilisateur
try {
    $mondes = Monde::findByUser(getPDO(), $user_id);
} catch (Exception $e) {
    $mondes = [];
    $message = "❌ Erreur lors de la récupération des mondes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Démonstration - Système de Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .demo-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .code-block {
            background-color: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-code me-2"></i>Démonstration du Système de Classes</h1>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <!-- Section 1: Introduction -->
                <div class="demo-section">
                    <h2><i class="fas fa-info-circle me-2"></i>Introduction</h2>
                    <p>Ce système de classes permet de gérer les mondes de campagne de manière orientée objet. Voici les principales fonctionnalités :</p>
                    <ul>
                        <li><strong>Classe Monde</strong> : Encapsulation des données et méthodes des mondes</li>
                        <li><strong>Classe Database</strong> : Gestion centralisée des connexions à la base de données</li>
                        <li><strong>Autoloader</strong> : Chargement automatique des classes</li>
                        <li><strong>Validation</strong> : Validation automatique des données</li>
                    </ul>
                </div>

                <!-- Section 2: Exemple de code -->
                <div class="demo-section">
                    <h2><i class="fas fa-code me-2"></i>Exemple d'utilisation</h2>
                    <p>Voici comment utiliser la classe Monde :</p>
                    <div class="code-block">
// Créer un nouveau monde
$monde = new Monde(getPDO());
$monde->setName("Mon Monde")
      ->setDescription("Description de mon monde")
      ->setCreatedBy($user_id);

// Valider les données
$errors = $monde->validate();
if (empty($errors)) {
    $monde->save();
}

// Récupérer un monde existant
$monde = Monde::findById(getPDO(), $world_id);

// Récupérer tous les mondes d'un utilisateur
$mondes = Monde::findByUser(getPDO(), $user_id);
                    </div>
                </div>

                <!-- Section 3: Actions de démonstration -->
                <div class="demo-section">
                    <h2><i class="fas fa-play me-2"></i>Actions de démonstration</h2>
                    <p>Testez les fonctionnalités de la classe Monde :</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Créer un monde de test</h5>
                                    <p class="card-text">Créez un monde de démonstration pour tester les fonctionnalités.</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="create_demo_world">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Créer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Tester la récupération</h5>
                                    <p class="card-text">Les mondes sont automatiquement récupérés via la classe Monde.</p>
                                    <span class="badge bg-success"><?php echo count($mondes); ?> monde(s) trouvé(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Liste des mondes -->
                <div class="demo-section">
                    <h2><i class="fas fa-globe me-2"></i>Vos mondes (via la classe Monde)</h2>
                    
                    <?php if (empty($mondes)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun monde trouvé. Créez un monde de démonstration pour commencer.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($mondes as $monde): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($monde->getName()); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($monde->getDescription()); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-flag me-1"></i>
                                                    <?php echo $monde->getCountryCount(); ?> pays
                                                </small>
                                                <?php if (strpos($monde->getName(), 'Démonstration') !== false): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_demo_world">
                                                        <input type="hidden" name="world_id" value="<?php echo $monde->getId(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Supprimer ce monde de démonstration ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section 5: Avantages -->
                <div class="demo-section">
                    <h2><i class="fas fa-star me-2"></i>Avantages du système de classes</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fas fa-shield-alt text-success me-2"></i>Sécurité</h4>
                            <ul>
                                <li>Validation automatique des données</li>
                                <li>Protection contre les injections SQL</li>
                                <li>Gestion centralisée des erreurs</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="fas fa-cogs text-primary me-2"></i>Maintenabilité</h4>
                            <ul>
                                <li>Code organisé et réutilisable</li>
                                <li>Encapsulation des fonctionnalités</li>
                                <li>Facilité de débogage</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="fas fa-rocket text-warning me-2"></i>Performance</h4>
                            <ul>
                                <li>Connexions de base de données optimisées</li>
                                <li>Chargement automatique des classes</li>
                                <li>Pattern Singleton pour la DB</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="fas fa-expand-arrows-alt text-info me-2"></i>Évolutivité</h4>
                            <ul>
                                <li>Facilité d'ajout de nouvelles classes</li>
                                <li>Architecture modulaire</li>
                                <li>Prêt pour l'extension</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Navigation -->
                <div class="demo-section">
                    <h2><i class="fas fa-link me-2"></i>Liens utiles</h2>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="manage_worlds.php" class="btn btn-outline-primary">
                            <i class="fas fa-globe me-1"></i>Gestion des mondes (original)
                        </a>
                        <a href="manage_worlds_refactored.php" class="btn btn-outline-success">
                            <i class="fas fa-code me-1"></i>Gestion des mondes (refactorisé)
                        </a>
                        <a href="test_monde_class.php" class="btn btn-outline-info">
                            <i class="fas fa-vial me-1"></i>Tests de la classe
                        </a>
                        <a href="classes/README.md" class="btn btn-outline-secondary">
                            <i class="fas fa-book me-1"></i>Documentation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
