<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est admin
User::requireAdmin();

// Fonction pour exporter la table users en SQL
function exportUsersTable() {
    global $pdo;
    try {
        $sql = "-- Table: users\n";
        $sql .= "-- Exporté le " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Par: " . $_SESSION['username'] . "\n\n";
        
        // Obtenir la structure de la table users
        $stmt = $pdo->query("SHOW CREATE TABLE `users`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $createTable['Create Table'] . ";\n\n";
        
        // Obtenir les données de la table users
        $stmt = $pdo->query("SELECT * FROM `users`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $sql .= "-- Données de la table users\n";
            $sql .= "INSERT INTO `users` VALUES\n";
            
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
        return "-- Erreur lors de l'export de la table users: " . $e->getMessage() . "\n\n";
    }
}

// Fonction pour obtenir les statistiques des utilisateurs
function getUserStatistics() {
    global $pdo;
    try {
        $stats = [];
        
        // Nombre total d'utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $stmt->fetch()['total'];
        
        // Nombre d'utilisateurs par rôle
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $stats['by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nombre d'utilisateurs DM
        $stmt = $pdo->query("SELECT COUNT(*) as dm_count FROM users WHERE is_dm = 1");
        $stats['dm_count'] = $stmt->fetch()['dm_count'];
        
        // Utilisateurs créés récemment (derniers 30 jours)
        $stmt = $pdo->query("SELECT COUNT(*) as recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent'] = $stmt->fetch()['recent'];
        
        return $stats;
    } catch (Exception $e) {
        return [
            'total' => 0,
            'by_role' => [],
            'dm_count' => 0,
            'recent' => 0
        ];
    }
}

// Créer le fichier ZIP pour les utilisateurs
function createUsersBackup() {
    $timestamp = date('Y-m-d_H-i-s');
    $zipFileName = "backup_users_{$timestamp}.zip";
    $zipPath = sys_get_temp_dir() . '/' . $zipFileName;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Impossible de créer le fichier ZIP");
    }
    
    // Exporter la table users
    $sqlContent = exportUsersTable();
    
    // Ajouter le fichier SQL au ZIP
    $zip->addFromString('users_backup.sql', $sqlContent);
    
    // Ajouter un fichier d'informations
    $stats = getUserStatistics();
    $infoContent = "Sauvegarde des utilisateurs générée le " . date('Y-m-d H:i:s') . "\n";
    $infoContent .= "Par: " . $_SESSION['username'] . "\n";
    $infoContent .= "Nombre total d'utilisateurs: " . $stats['total'] . "\n";
    $infoContent .= "Utilisateurs DM: " . $stats['dm_count'] . "\n";
    $infoContent .= "Utilisateurs créés récemment (30 jours): " . $stats['recent'] . "\n";
    $infoContent .= "Répartition par rôle:\n";
    
    foreach ($stats['by_role'] as $role) {
        $infoContent .= "  - " . $role['role'] . ": " . $role['count'] . " utilisateurs\n";
    }
    
    $infoContent .= "\nVersion de l'application: " . (file_exists('VERSION') ? file_get_contents('VERSION') : 'Inconnue') . "\n";
    
    $zip->addFromString('users_info.txt', $infoContent);
    
    $zip->close();
    
    return $zipPath;
}

// Traitement de la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_users') {
    try {
        $zipPath = createUsersBackup();
        
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
        $error = "Erreur lors de la création de la sauvegarde des utilisateurs: " . $e->getMessage();
    }
}

$page_title = "Sauvegarde des Utilisateurs";
$current_page = "admin";
$userStats = getUserStatistics();
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
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-users"></i> Sauvegarde des Utilisateurs
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
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i> Sauvegarde des Utilisateurs
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Cette fonctionnalité permet de sauvegarder :</p>
                        <ul>
                            <li><strong>Table users :</strong> Structure et données complètes de la table des utilisateurs</li>
                            <li><strong>Informations de sécurité :</strong> Mots de passe hashés et données sensibles</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention :</strong> Cette sauvegarde contient des données sensibles. Assurez-vous de la stocker en sécurité.
                        </div>
                        
                        <form method="POST" id="saveUsersForm">
                            <input type="hidden" name="action" value="save_users">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-download"></i> Télécharger la Sauvegarde des Utilisateurs
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar"></i> Statistiques des Utilisateurs
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border rounded p-2">
                                    <h4 class="text-primary mb-0"><?php echo $userStats['total']; ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="border rounded p-2">
                                    <h4 class="text-success mb-0"><?php echo $userStats['dm_count']; ?></h4>
                                    <small class="text-muted">DM</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <p><strong>Répartition par rôle :</strong></p>
                        <?php if (!empty($userStats['by_role'])): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($userStats['by_role'] as $role): ?>
                                    <li>
                                        <i class="fas fa-user text-muted"></i> 
                                        <?php echo ucfirst($role['role']); ?>: 
                                        <span class="badge bg-secondary"><?php echo $role['count']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Aucune donnée disponible</p>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <p><strong>Utilisateurs récents :</strong></p>
                        <p class="text-muted"><?php echo $userStats['recent']; ?> créés dans les 30 derniers jours</p>
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
        document.getElementById('saveUsersForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création de la sauvegarde...';
            button.disabled = true;
            
            // Le formulaire sera soumis normalement pour télécharger le fichier
        });
    </script>
</body>
</html>
