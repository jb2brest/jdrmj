<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

// Fonction pour lire le fichier VERSION
function getApplicationVersion() {
    $versionFile = __DIR__ . '/VERSION';
    if (!file_exists($versionFile)) {
        return [
            'VERSION' => 'unknown',
            'DEPLOY_DATE' => 'unknown',
            'ENVIRONMENT' => 'unknown',
            'GIT_COMMIT' => 'unknown',
            'BUILD_ID' => 'unknown',
            'RELEASE_NOTES' => 'unknown'
        ];
    }
    
    $content = file_get_contents($versionFile);
    $lines = explode("\n", $content);
    $version = [];
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $version[trim($key)] = trim($value);
        }
    }
    
    return $version;
}

// Fonction pour obtenir les versions de la base de données
function getDatabaseVersions() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT 
                version_type,
                version_number,
                build_id,
                git_commit,
                deploy_date,
                deploy_user,
                environment,
                release_notes,
                is_current
            FROM system_versions 
            ORDER BY deploy_date DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Fonction pour obtenir l'historique des migrations
function getMigrationHistory() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT 
                migration_name,
                version_from,
                version_to,
                executed_at,
                executed_by,
                execution_time_ms,
                success,
                error_message
            FROM database_migrations 
            ORDER BY executed_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Fonction pour obtenir les informations système
function getSystemInfo() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT VERSION() as mysql_version");
        $mysql = $stmt->fetch();
        
        return [
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysql['mysql_version'] ?? 'unknown',
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    } catch (Exception $e) {
        return [
            'php_version' => PHP_VERSION,
            'mysql_version' => 'unknown',
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }
}

// Récupération des données
$appVersion = getApplicationVersion();
$dbVersions = getDatabaseVersions();
$migrations = getMigrationHistory();
$systemInfo = getSystemInfo();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Versions du Système - JDR MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .version-card {
            transition: transform 0.2s;
        }
        .version-card:hover {
            transform: translateY(-2px);
        }
        .version-badge {
            font-size: 0.9em;
        }
        .system-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .migration-success {
            color: #28a745;
        }
        .migration-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dice-d20"></i> JDR MJ
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i> Profil
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-code-branch"></i> Versions du Système
                    <span class="badge bg-danger">Admin</span>
                </h1>
            </div>
        </div>

        <!-- Informations Système -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card system-info">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-server"></i> Informations Système
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>PHP Version:</strong><br>
                                <span class="badge bg-light text-dark"><?= $systemInfo['php_version'] ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>MySQL Version:</strong><br>
                                <span class="badge bg-light text-dark"><?= $systemInfo['mysql_version'] ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Heure Serveur:</strong><br>
                                <span class="badge bg-light text-dark"><?= $systemInfo['server_time'] ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Timezone:</strong><br>
                                <span class="badge bg-light text-dark"><?= $systemInfo['timezone'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Version de l'Application -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card version-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-code"></i> Version de l'Application
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <strong>Version:</strong><br>
                                <span class="badge bg-primary version-badge"><?= $appVersion['VERSION'] ?></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Build ID:</strong><br>
                                <span class="badge bg-secondary version-badge"><?= $appVersion['BUILD_ID'] ?></span>
                            </div>
                            <div class="col-md-2">
                                <strong>Environnement:</strong><br>
                                <span class="badge bg-<?= $appVersion['ENVIRONMENT'] === 'production' ? 'success' : 'warning' ?> version-badge">
                                    <?= strtoupper($appVersion['ENVIRONMENT']) ?>
                                </span>
                            </div>
                            <div class="col-md-2">
                                <strong>Date de déploiement:</strong><br>
                                <small><?= $appVersion['DEPLOY_DATE'] ?></small>
                            </div>
                            <div class="col-md-2">
                                <strong>Commit Git:</strong><br>
                                <small><code><?= substr($appVersion['GIT_COMMIT'], 0, 8) ?></code></small>
                            </div>
                            <div class="col-md-2">
                                <strong>Statut:</strong><br>
                                <span class="badge bg-success">Déployé</span>
                            </div>
                        </div>
                        <?php if (!empty($appVersion['RELEASE_NOTES'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Notes de version:</strong><br>
                                <em><?= htmlspecialchars($appVersion['RELEASE_NOTES']) ?></em>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Versions de la Base de Données -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card version-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database"></i> Versions de la Base de Données
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dbVersions)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Aucune version de base de données trouvée. Le système de versioning n'est peut-être pas encore initialisé.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Version</th>
                                            <th>Build ID</th>
                                            <th>Environnement</th>
                                            <th>Déployé le</th>
                                            <th>Par</th>
                                            <th>Statut</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dbVersions as $version): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= $version['version_type'] === 'database' ? 'success' : 'primary' ?>">
                                                    <?= ucfirst($version['version_type']) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= $version['version_number'] ?></strong></td>
                                            <td><code><?= $version['build_id'] ?></code></td>
                                            <td>
                                                <span class="badge bg-<?= $version['environment'] === 'production' ? 'success' : 'warning' ?>">
                                                    <?= strtoupper($version['environment']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($version['deploy_date'])) ?></td>
                                            <td><?= $version['deploy_user'] ?></td>
                                            <td>
                                                <?php if ($version['is_current']): ?>
                                                    <span class="badge bg-success">Actuel</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Ancien</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($version['release_notes']) ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des Migrations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card version-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Historique des Migrations
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($migrations)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Aucune migration trouvée dans l'historique.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Migration</th>
                                            <th>De</th>
                                            <th>Vers</th>
                                            <th>Exécutée le</th>
                                            <th>Par</th>
                                            <th>Temps</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($migrations as $migration): ?>
                                        <tr>
                                            <td><code><?= $migration['migration_name'] ?></code></td>
                                            <td><?= $migration['version_from'] ?: 'N/A' ?></td>
                                            <td><strong><?= $migration['version_to'] ?></strong></td>
                                            <td><?= date('d/m/Y H:i:s', strtotime($migration['executed_at'])) ?></td>
                                            <td><?= $migration['executed_by'] ?></td>
                                            <td>
                                                <?php if ($migration['execution_time_ms']): ?>
                                                    <?= $migration['execution_time_ms'] ?>ms
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($migration['success']): ?>
                                                    <span class="badge bg-success migration-success">
                                                        <i class="fas fa-check"></i> Succès
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger migration-error">
                                                        <i class="fas fa-times"></i> Erreur
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (!$migration['success'] && !empty($migration['error_message'])): ?>
                                        <tr>
                                            <td colspan="7">
                                                <div class="alert alert-danger mb-0">
                                                    <strong>Erreur:</strong> <?= htmlspecialchars($migration['error_message']) ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Admin -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-tools"></i> Actions Administrateur
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Retour à l'accueil
                                </a>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-info" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i> Actualiser
                                </button>
                            </div>
                            <div class="col-md-4">
                                <a href="profile.php" class="btn btn-secondary">
                                    <i class="fas fa-user-cog"></i> Profil Admin
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
