<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role'] ?? 'player');
    
    // Validation
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    if (!in_array($role, ['player', 'dm'])) {
        $errors[] = "Rôle invalide.";
    }
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Un utilisateur avec ce nom ou cette adresse email existe déjà.";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $role]);
            
            $message = displayMessage("Inscription réussie ! Vous pouvez maintenant vous connecter.", "success");
        } catch (PDOException $e) {
            $message = displayMessage("Erreur lors de l'inscription : " . $e->getMessage(), "error");
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
    <title>Inscription - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="register-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-dice-d20 fa-3x text-primary mb-3"></i>
                        <h2>Inscription</h2>
                        <p class="text-muted">Rejoignez l'aventure D&D</p>
                    </div>
                    
                    <?php echo $message; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Nom d'utilisateur
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Adresse email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Au moins 6 caractères</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirmer le mot de passe
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-2"></i>Rôle
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="player" <?php echo (isset($_POST['role']) && $_POST['role'] === 'player') ? 'selected' : ''; ?>>
                                    <i class="fas fa-user me-2"></i>Joueur
                                </option>
                                <option value="dm" <?php echo (isset($_POST['role']) && $_POST['role'] === 'dm') ? 'selected' : ''; ?>>
                                    <i class="fas fa-crown me-2"></i>Maître du Jeu
                                </option>
                            </select>
                            <div class="form-text">
                                <strong>Joueur :</strong> Créez et gérez vos personnages<br>
                                <strong>Maître du Jeu :</strong> Créez des sessions et dirigez des parties
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-dnd w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>S'inscrire
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Déjà inscrit ? 
                                <a href="login.php" class="text-decoration-none">Se connecter</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

