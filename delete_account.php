<?php
/**
 * Page de suppression de compte utilisateur
 */

require_once 'includes/functions.php';
require_once 'classes/User.php';

// Vérifier que l'utilisateur est connecté
if (!User::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = User::getCurrentUser();
$message = '';
$error = '';

// Traitement du formulaire de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_delete = $_POST['confirm_delete'] ?? '';
    
    // Validation
    if (empty($password)) {
        $error = "Veuillez entrer votre mot de passe pour confirmer la suppression.";
    } elseif ($confirm_delete !== 'DELETE') {
        $error = "Veuillez taper 'DELETE' pour confirmer la suppression de votre compte.";
    } else {
        // Vérifier le mot de passe
        if (password_verify($password, $user->getPasswordHash())) {
            try {
                // Supprimer l'utilisateur
                if ($user->delete()) {
                    // Déconnecter l'utilisateur
                    session_destroy();
                    header('Location: index.php?deleted=1');
                    exit;
                } else {
                    $error = "Erreur lors de la suppression du compte. Veuillez réessayer.";
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la suppression du compte : " . $e->getMessage();
            }
        } else {
            $error = "Mot de passe incorrect.";
        }
    }
}

$page_title = "Supprimer mon compte";
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Supprimer mon compte
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-warning me-2"></i>Attention !</h5>
                        <p class="mb-0">
                            Cette action est <strong>irréversible</strong>. Toutes vos données seront 
                            définitivement supprimées :
                        </p>
                        <ul class="mt-2 mb-0">
                            <li>Votre profil utilisateur</li>
                            <li>Tous vos personnages</li>
                            <li>Toutes vos campagnes (si vous êtes MJ)</li>
                            <li>Tous vos monstres personnalisés</li>
                            <li>Toutes vos données associées</li>
                        </ul>
                    </div>
                    
                    <form method="POST" id="deleteAccountForm">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Mot de passe actuel
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required
                                   placeholder="Entrez votre mot de passe pour confirmer">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_delete" class="form-label">
                                <i class="fas fa-keyboard me-1"></i>
                                Confirmation
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="confirm_delete" 
                                   name="confirm_delete" 
                                   required
                                   placeholder="Tapez 'DELETE' pour confirmer">
                            <div class="form-text">
                                Tapez exactement <code>DELETE</code> pour confirmer la suppression
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" 
                                    class="btn btn-danger btn-lg" 
                                    id="deleteButton"
                                    disabled>
                                <i class="fas fa-trash-alt me-2"></i>
                                Supprimer définitivement mon compte
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Annuler et retourner au profil
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('confirm_delete');
    const deleteButton = document.getElementById('deleteButton');
    const form = document.getElementById('deleteAccountForm');
    
    // Activer le bouton seulement si 'DELETE' est tapé
    confirmInput.addEventListener('input', function() {
        if (this.value === 'DELETE') {
            deleteButton.disabled = false;
        } else {
            deleteButton.disabled = true;
        }
    });
    
    // Confirmation supplémentaire avant soumission
    form.addEventListener('submit', function(e) {
        if (!confirm('Êtes-vous ABSOLUMENT certain de vouloir supprimer votre compte ? Cette action est irréversible !')) {
            e.preventDefault();
        }
    });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
