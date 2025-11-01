<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est admin
User::requireAdmin();

$error = '';
$success = '';

// Fonction pour valider le fichier ZIP
function validateZipFile($file) {
    $allowedTypes = ['application/zip', 'application/x-zip-compressed'];
    $maxSize = 50 * 1024 * 1024; // 50MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return "Type de fichier non autorisé. Seuls les fichiers ZIP sont acceptés.";
    }
    
    if ($file['size'] > $maxSize) {
        return "Fichier trop volumineux. Taille maximale: 50MB.";
    }
    
    return null;
}

// Fonction pour analyser le fichier ZIP des utilisateurs
function analyzeUsersZipFile($zipPath) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        throw new Exception("Impossible d'ouvrir le fichier ZIP");
    }
    
    $analysis = [
        'files' => [],
        'has_users_table' => false,
        'has_info' => false,
        'info_content' => '',
        'user_count' => 0
    ];
    
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $analysis['files'][] = $filename;
        
        if ($filename === 'users_backup.sql') {
            $analysis['has_users_table'] = true;
            
            // Compter le nombre d'utilisateurs dans le fichier SQL
            $sqlContent = $zip->getFromIndex($i);
            if ($sqlContent) {
                $analysis['user_count'] = substr_count($sqlContent, 'INSERT INTO `users` VALUES');
            }
        } elseif ($filename === 'users_info.txt') {
            $analysis['has_info'] = true;
            $analysis['info_content'] = $zip->getFromIndex($i);
        }
    }
    
    $zip->close();
    return $analysis;
}

// Fonction pour restaurer la table users
function restoreUsersTable($zipPath, $mode = 'replace') {
    global $pdo;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        throw new Exception("Impossible d'ouvrir le fichier ZIP");
    }
    
    $sqlContent = $zip->getFromName('users_backup.sql');
    if (!$sqlContent) {
        throw new Exception("Fichier users_backup.sql non trouvé dans l'archive");
    }
    
    $zip->close();
    
    try {
        // Désactiver les vérifications de clés étrangères temporairement
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        if ($mode === 'replace') {
            // Vider la table users existante
            $pdo->exec("TRUNCATE TABLE users");
        }
        
        // Diviser le contenu SQL en requêtes individuelles
        $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
        
        $restoredUsers = 0;
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^--/', $query)) {
                if (strpos($query, 'CREATE TABLE') !== false) {
                    // Ignorer la création de table si on est en mode merge
                    if ($mode === 'replace') {
                        $pdo->exec($query);
                    }
                } elseif (strpos($query, 'INSERT INTO `users`') !== false) {
                    $pdo->exec($query);
                    $restoredUsers++;
                }
            }
        }
        
        // Réactiver les vérifications de clés étrangères
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        return $restoredUsers;
        
    } catch (Exception $e) {
        // Réactiver les vérifications de clés étrangères en cas d'erreur
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        throw $e;
    }
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'load_users') {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Erreur lors de l'upload du fichier.";
    } else {
        $file = $_FILES['backup_file'];
        
        // Valider le fichier
        $validationError = validateZipFile($file);
        if ($validationError) {
            $error = $validationError;
        } else {
            try {
                // Déplacer le fichier vers un répertoire temporaire
                $tempPath = sys_get_temp_dir() . '/' . uniqid('users_backup_') . '.zip';
                if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                    throw new Exception("Impossible de déplacer le fichier uploadé");
                }
                
                // Analyser le fichier ZIP
                $analysis = analyzeUsersZipFile($tempPath);
                
                if (!$analysis['has_users_table']) {
                    throw new Exception("Fichier users_backup.sql non trouvé dans l'archive");
                }
                
                // Déterminer le mode de restauration
                $mode = isset($_POST['restore_mode']) ? $_POST['restore_mode'] : 'replace';
                
                // Restaurer la table users
                $restoredUsers = restoreUsersTable($tempPath, $mode);
                
                // Supprimer le fichier temporaire
                unlink($tempPath);
                
                $modeText = $mode === 'replace' ? 'remplacés' : 'ajoutés';
                $success = "Restauration réussie : $restoredUsers utilisateurs $modeText";
                
            } catch (Exception $e) {
                $error = "Erreur lors de la restauration des utilisateurs : " . $e->getMessage();
                
                // Supprimer le fichier temporaire en cas d'erreur
                if (isset($tempPath) && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }
    }
}

$page_title = "Chargement des Utilisateurs";
$current_page = "admin";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-users"></i> Chargement des Utilisateurs
                    <span class="badge bg-danger">Admin</span>
                </h1>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i> Chargement des Utilisateurs
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Cette fonctionnalité permet de restaurer :</p>
                        <ul>
                            <li><strong>Table users :</strong> Structure et données complètes de la table des utilisateurs</li>
                            <li><strong>Données de sécurité :</strong> Mots de passe hashés et informations de connexion</li>
                        </ul>
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention :</strong> Cette opération va affecter les utilisateurs existants. Assurez-vous d'avoir une sauvegarde récente avant de procéder.
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="loadUsersForm">
                            <input type="hidden" name="action" value="load_users">
                            
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">
                                    <i class="fas fa-file-archive"></i> Fichier de sauvegarde des utilisateurs (ZIP)
                                </label>
                                <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                       accept=".zip,application/zip" required>
                                <div class="form-text">
                                    Sélectionnez un fichier ZIP généré par la fonction "Save Users"
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="restore_mode" class="form-label">
                                    <i class="fas fa-cogs"></i> Mode de restauration
                                </label>
                                <select class="form-select" id="restore_mode" name="restore_mode" required>
                                    <option value="replace">Remplacer tous les utilisateurs existants</option>
                                    <option value="merge">Ajouter aux utilisateurs existants</option>
                                </select>
                                <div class="form-text">
                                    <strong>Remplacer :</strong> Vide la table et restaure tous les utilisateurs<br>
                                    <strong>Ajouter :</strong> Garde les utilisateurs existants et ajoute les nouveaux
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-upload"></i> Charger les Utilisateurs
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i> Instructions
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6>Étapes de restauration :</h6>
                        <ol class="small">
                            <li>Sélectionnez un fichier ZIP généré par "Save Users"</li>
                            <li>Choisissez le mode de restauration</li>
                            <li>Le système restaurera la table users</li>
                            <li>Les utilisateurs seront disponibles pour la connexion</li>
                        </ol>
                        
                        <hr>
                        
                        <h6>Contenu attendu :</h6>
                        <ul class="small">
                            <li><code>users_backup.sql</code> - Sauvegarde de la table users</li>
                            <li><code>users_info.txt</code> - Informations de sauvegarde</li>
                        </ul>
                        
                        <hr>
                        
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important :</strong> Cette opération affecte directement les comptes utilisateurs et leurs mots de passe.
                        </div>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Conseil :</strong> En mode "Ajouter", les utilisateurs avec des noms d'utilisateur identiques ne seront pas dupliqués.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="admin_versions.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour aux Versions
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loadUsersForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            const mode = document.getElementById('restore_mode').value;
            const modeText = mode === 'replace' ? 'remplacement' : 'ajout';
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement en cours...';
            button.disabled = true;
            
            // Le formulaire sera soumis normalement
        });
        
        // Validation du fichier côté client
        document.getElementById('backup_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.name.toLowerCase().endsWith('.zip')) {
                    alert('Veuillez sélectionner un fichier ZIP.');
                    e.target.value = '';
                } else if (file.size > 50 * 1024 * 1024) { // 50MB
                    alert('Le fichier est trop volumineux. Taille maximale: 50MB.');
                    e.target.value = '';
                }
            }
        });
        
        // Avertissement pour le mode remplacement
        document.getElementById('restore_mode').addEventListener('change', function(e) {
            if (e.target.value === 'replace') {
                if (!confirm('Êtes-vous sûr de vouloir remplacer tous les utilisateurs existants ? Cette action est irréversible.')) {
                    e.target.value = 'merge';
                }
            }
        });
    </script>
</body>
</html>
