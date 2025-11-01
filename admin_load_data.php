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
    $maxSize = 100 * 1024 * 1024; // 100MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return "Type de fichier non autorisé. Seuls les fichiers ZIP sont acceptés.";
    }
    
    if ($file['size'] > $maxSize) {
        return "Fichier trop volumineux. Taille maximale: 100MB.";
    }
    
    return null;
}

// Fonction pour extraire et analyser le fichier ZIP
function analyzeZipFile($zipPath) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        throw new Exception("Impossible d'ouvrir le fichier ZIP");
    }
    
    $analysis = [
        'files' => [],
        'has_database' => false,
        'has_uploads' => false,
        'has_info' => false,
        'info_content' => ''
    ];
    
    $uploadDirs = ['uploads/', 'images/', 'aidednddata/'];
    
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $analysis['files'][] = $filename;
        
        if ($filename === 'database_backup.sql') {
            $analysis['has_database'] = true;
        } elseif ($filename === 'backup_info.txt') {
            $analysis['has_info'] = true;
            $analysis['info_content'] = $zip->getFromIndex($i);
        } else {
            // Vérifier si le fichier appartient à un des répertoires d'upload
            foreach ($uploadDirs as $dir) {
                if (strpos($filename, $dir) === 0) {
                    $analysis['has_uploads'] = true;
                    break;
                }
            }
        }
    }
    
    $zip->close();
    return $analysis;
}

// Fonction pour restaurer la base de données
function restoreDatabase($zipPath) {
    global $pdo;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        throw new Exception("Impossible d'ouvrir le fichier ZIP");
    }
    
    $sqlContent = $zip->getFromName('database_backup.sql');
    if (!$sqlContent) {
        throw new Exception("Fichier database_backup.sql non trouvé dans l'archive");
    }
    
    $zip->close();
    
    // Désactiver les vérifications de clés étrangères temporairement
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    try {
        // Diviser le contenu SQL en requêtes individuelles
        $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
        
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^--/', $query)) {
                $pdo->exec($query);
            }
        }
        
        // Réactiver les vérifications de clés étrangères
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        return true;
    } catch (Exception $e) {
        // Réactiver les vérifications de clés étrangères en cas d'erreur
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        throw $e;
    }
}

// Fonction pour restaurer les fichiers uploadés
function restoreUploadedFiles($zipPath) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        throw new Exception("Impossible d'ouvrir le fichier ZIP");
    }
    
    $restoredFiles = 0;
    $uploadDirs = ['uploads/', 'images/', 'aidednddata/'];
    
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        
        // Vérifier si le fichier appartient à un des répertoires d'upload
        $isUploadFile = false;
        foreach ($uploadDirs as $dir) {
            if (strpos($filename, $dir) === 0) {
                $isUploadFile = true;
                break;
            }
        }
        
        if ($isUploadFile) {
            // Créer le répertoire si nécessaire
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Extraire le fichier en conservant sa structure d'origine
            if ($zip->extractTo('.', $filename)) {
                $restoredFiles++;
            }
        }
    }
    
    $zip->close();
    return $restoredFiles;
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'load_data') {
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
                $tempPath = sys_get_temp_dir() . '/' . uniqid('backup_') . '.zip';
                if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                    throw new Exception("Impossible de déplacer le fichier uploadé");
                }
                
                // Analyser le fichier ZIP
                $analysis = analyzeZipFile($tempPath);
                
                $restoredItems = [];
                
                // Restaurer la base de données si présente
                if ($analysis['has_database']) {
                    restoreDatabase($tempPath);
                    $restoredItems[] = "Base de données restaurée";
                }
                
                // Restaurer les fichiers uploadés si présents
                if ($analysis['has_uploads']) {
                    $fileCount = restoreUploadedFiles($tempPath);
                    $restoredItems[] = "$fileCount fichiers uploadés restaurés";
                }
                
                // Supprimer le fichier temporaire
                unlink($tempPath);
                
                if (!empty($restoredItems)) {
                    $success = "Restauration réussie : " . implode(', ', $restoredItems);
                } else {
                    $error = "Aucune donnée valide trouvée dans l'archive.";
                }
                
            } catch (Exception $e) {
                $error = "Erreur lors de la restauration : " . $e->getMessage();
                
                // Supprimer le fichier temporaire en cas d'erreur
                if (isset($tempPath) && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }
    }
}

$page_title = "Chargement des Données";
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
                    <i class="fas fa-upload"></i> Chargement des Données
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
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-upload"></i> Chargement des Données
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Cette fonctionnalité permet de restaurer :</p>
                        <ul>
                            <li><strong>Base de données :</strong> Toutes les tables sauf la table <code>users</code></li>
                            <li><strong>Fichiers uploadés :</strong> Tous les fichiers dans les dossiers <code>uploads/</code>, <code>images/</code>, et <code>aidednddata/</code></li>
                        </ul>
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention :</strong> Cette opération va écraser les données existantes. Assurez-vous d'avoir une sauvegarde récente avant de procéder.
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="loadDataForm">
                            <input type="hidden" name="action" value="load_data">
                            
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">
                                    <i class="fas fa-file-archive"></i> Fichier de sauvegarde (ZIP)
                                </label>
                                <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                       accept=".zip,application/zip" required>
                                <div class="form-text">
                                    Sélectionnez un fichier ZIP généré par la fonction "Save Data"
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-upload"></i> Charger les Données
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
                            <li>Sélectionnez un fichier ZIP généré par "Save Data"</li>
                            <li>Le système analysera le contenu de l'archive</li>
                            <li>Les données de la base seront restaurées</li>
                            <li>Les fichiers uploadés seront extraits</li>
                        </ol>
                        
                        <hr>
                        
                        <h6>Contenu attendu :</h6>
                        <ul class="small">
                            <li><code>database_backup.sql</code> - Sauvegarde de la base</li>
                            <li><code>uploads/</code> - Fichiers uploadés</li>
                            <li><code>images/</code> - Images du système</li>
                            <li><code>aidednddata/</code> - Données D&D</li>
                            <li><code>backup_info.txt</code> - Informations de sauvegarde</li>
                        </ul>
                        
                        <hr>
                        
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important :</strong> La table <code>users</code> ne sera pas affectée par cette restauration.
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
        document.getElementById('loadDataForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
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
                } else if (file.size > 100 * 1024 * 1024) { // 100MB
                    alert('Le fichier est trop volumineux. Taille maximale: 100MB.');
                    e.target.value = '';
                }
            }
        });
    </script>
</body>
</html>
