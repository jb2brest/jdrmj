<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est admin
User::requireAdmin();

// Fonction pour obtenir toutes les tables de la base de données sauf 'users'
function getAllTablesExceptUsers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrer pour exclure la table 'users'
        return array_filter($tables, function($table) {
            return $table !== 'users';
        });
    } catch (Exception $e) {
        return [];
    }
}

// Fonction pour exporter une table en SQL
function exportTableToSQL($tableName) {
    global $pdo;
    try {
        $sql = "-- Table: $tableName\n";
        $sql .= "-- Exporté le " . date('Y-m-d H:i:s') . "\n\n";
        
        // Obtenir la structure de la table
        $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $createTable['Create Table'] . ";\n\n";
        
        // Obtenir les données de la table
        $stmt = $pdo->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $sql .= "-- Données de la table $tableName\n";
            $sql .= "INSERT INTO `$tableName` VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $sql .= implode(",\n", $values) . ";\n\n";
        }
        
        return $sql;
    } catch (Exception $e) {
        return "-- Erreur lors de l'export de la table $tableName: " . $e->getMessage() . "\n\n";
    }
}

// Fonction pour obtenir tous les fichiers uploadés
function getAllUploadedFiles() {
    $uploadDirs = [
        'uploads/',
        'images/',
        'aidednddata/'
    ];
    
    $files = [];
    
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }
    }
    
    return $files;
}

// Créer le fichier ZIP
function createDataBackup() {
    $timestamp = date('Y-m-d_H-i-s');
    $zipFileName = "backup_data_{$timestamp}.zip";
    $zipPath = sys_get_temp_dir() . '/' . $zipFileName;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Impossible de créer le fichier ZIP");
    }
    
    // Exporter toutes les tables sauf 'users'
    $tables = getAllTablesExceptUsers();
    $sqlContent = "-- Sauvegarde de la base de données (sans table users)\n";
    $sqlContent .= "-- Généré le " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Par: " . $_SESSION['username'] . "\n\n";
    
    foreach ($tables as $table) {
        $sqlContent .= exportTableToSQL($table);
    }
    
    // Ajouter le fichier SQL au ZIP
    $zip->addFromString('database_backup.sql', $sqlContent);
    
    // Ajouter tous les fichiers uploadés en conservant la structure des répertoires
    $uploadedFiles = getAllUploadedFiles();
    foreach ($uploadedFiles as $file) {
        if (file_exists($file)) {
            // Conserver la structure des répertoires d'origine
            $zip->addFile($file, $file);
        }
    }
    
    // Ajouter un fichier d'informations
    $infoContent = "Sauvegarde des données générée le " . date('Y-m-d H:i:s') . "\n";
    $infoContent .= "Par: " . $_SESSION['username'] . "\n";
    $infoContent .= "Tables exportées: " . implode(', ', $tables) . "\n";
    $infoContent .= "Fichiers uploadés: " . count($uploadedFiles) . " fichiers\n";
    $infoContent .= "Version de l'application: " . (file_exists('VERSION') ? file_get_contents('VERSION') : 'Inconnue') . "\n";
    
    $zip->addFromString('backup_info.txt', $infoContent);
    
    $zip->close();
    
    return $zipPath;
}

// Traitement de la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_data') {
    try {
        $zipPath = createDataBackup();
        
        // Envoyer le fichier au navigateur
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        readfile($zipPath);
        
        // Supprimer le fichier temporaire
        unlink($zipPath);
        exit;
        
    } catch (Exception $e) {
        $error = "Erreur lors de la création de la sauvegarde: " . $e->getMessage();
    }
}

$page_title = "Sauvegarde des Données";
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
                    <i class="fas fa-download"></i> Sauvegarde des Données
                    <span class="badge bg-danger">Admin</span>
                </h1>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database"></i> Sauvegarde des Données
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Cette fonctionnalité permet de sauvegarder :</p>
                        <ul>
                            <li><strong>Base de données :</strong> Toutes les tables sauf la table <code>users</code></li>
                            <li><strong>Fichiers uploadés :</strong> Tous les fichiers dans les dossiers <code>uploads/</code>, <code>images/</code>, et <code>aidednddata/</code> en conservant leur structure de répertoires</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note :</strong> La table <code>users</code> n'est pas incluse dans cette sauvegarde pour des raisons de sécurité.
                        </div>
                        
                        <form method="POST" id="saveDataForm">
                            <input type="hidden" name="action" value="save_data">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-download"></i> Télécharger la Sauvegarde
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informations
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Tables à sauvegarder :</strong></p>
                        <?php 
                        $tables = getAllTablesExceptUsers();
                        if (!empty($tables)): 
                        ?>
                            <ul class="list-unstyled">
                                <?php foreach ($tables as $table): ?>
                                    <li><i class="fas fa-table text-muted"></i> <?php echo htmlspecialchars($table); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Aucune table trouvée</p>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <p><strong>Fichiers uploadés :</strong></p>
                        <?php 
                        $files = getAllUploadedFiles();
                        ?>
                        <p class="text-muted"><?php echo count($files); ?> fichiers trouvés</p>
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
        document.getElementById('saveDataForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création de la sauvegarde...';
            button.disabled = true;
            
            // Le formulaire sera soumis normalement pour télécharger le fichier
        });
    </script>
</body>
</html>
