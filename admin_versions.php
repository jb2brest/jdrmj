<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est admin
User::requireAdmin();

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

// Fonction pour charger les rapports de tests JSON
function getTestReports() {
    $reportsDir = __DIR__ . '/tests/reports';
    $individualDir = $reportsDir . '/individual';
    
    $testData = [
        'individual_reports' => [],
        'summary' => [
            'total_individual' => 0,
            'latest_individual' => null
        ]
    ];
    
    // Charger les rapports individuels
    if (is_dir($individualDir)) {
        $individualFiles = glob($individualDir . '/*.json');
        $testData['summary']['total_individual'] = count($individualFiles);
        
        foreach ($individualFiles as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                $testData['individual_reports'][] = [
                    'filename' => basename($file),
                    'data' => $data,
                    'modified' => filemtime($file)
                ];
            }
        }
        
        // Trier par date de modification (plus récent en premier)
        usort($testData['individual_reports'], function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        if (!empty($testData['individual_reports'])) {
            $testData['summary']['latest_individual'] = $testData['individual_reports'][0];
        }
    }
    
    // Les rapports de session ont été supprimés - seuls les rapports individuels sont conservés
    
    return $testData;
}

// Fonction pour calculer les statistiques des tests
function calculateTestStatistics($testData) {
    $stats = [
        'total_tests' => 0,
        'passed_tests' => 0,
        'failed_tests' => 0,
        'error_tests' => 0,
        'skipped_tests' => 0,
        'success_rate' => 0,
        'categories' => [],
        'tests_by_category' => []
    ];
    
    // Analyser les rapports individuels
    foreach ($testData['individual_reports'] as $report) {
        $data = $report['data'];
        $stats['total_tests']++;
        
        if ($data['result']['success']) {
            $stats['passed_tests']++;
        } else {
            if ($data['result']['status'] === 'FAILED') {
                $stats['failed_tests']++;
            } elseif ($data['result']['status'] === 'SKIPPED') {
                $stats['skipped_tests']++;
            } else {
                $stats['error_tests']++;
            }
        }
        
        // Organiser par catégorie
        $category = $data['test_info']['category'] ?? 'Autres';
        if (!isset($stats['categories'][$category])) {
            $stats['categories'][$category] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'error' => 0,
                'skipped' => 0
            ];
            $stats['tests_by_category'][$category] = [];
        }
        
        $stats['categories'][$category]['total']++;
        if ($data['result']['success']) {
            $stats['categories'][$category]['passed']++;
        } else {
            if ($data['result']['status'] === 'FAILED') {
                $stats['categories'][$category]['failed']++;
            } elseif ($data['result']['status'] === 'SKIPPED') {
                $stats['categories'][$category]['skipped']++;
            } else {
                $stats['categories'][$category]['error']++;
            }
        }
        
        // Ajouter le test à sa catégorie
        $stats['tests_by_category'][$category][] = [
            'name' => $data['test_info']['name'],
            'status' => $data['result']['status'],
            'success' => $data['result']['success'],
            'duration' => $data['test_info']['duration_seconds'],
            'date' => $data['test_info']['date'] ?? '',
            'time' => $data['test_info']['time'] ?? '',
            'timestamp' => $data['test_info']['timestamp'] ?? ''
        ];
    }
    
    // Trier les tests dans chaque catégorie par timestamp (plus récent en premier)
    foreach ($stats['tests_by_category'] as $category => $tests) {
        usort($stats['tests_by_category'][$category], function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
    }
    
    // Calculer le taux de réussite
    if ($stats['total_tests'] > 0) {
        $stats['success_rate'] = round(($stats['passed_tests'] / $stats['total_tests']) * 100, 2);
    }
    
    return $stats;
}

// Récupération des données
$appVersion = getApplicationVersion();
$dbVersions = getDatabaseVersions();
$migrations = getMigrationHistory();
$systemInfo = getSystemInfo();
$testData = getTestReports();
$testStats = calculateTestStatistics($testData);

$page_title = "Versions du Système";
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
        
        /* Styles pour les onglets */
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: #dee2e6;
            color: #495057;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
        
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            padding: 1.5rem;
            background-color: #fff;
        }
        
        .tab-pane {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-icon {
            margin-right: 0.5rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .stats-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Styles spécifiques pour l'onglet Tests */
        .test-status-passed {
            color: #28a745;
        }
        
        .test-status-failed {
            color: #dc3545;
        }
        
        .test-status-error {
            color: #ffc107;
        }
        
        .category-card {
            transition: transform 0.2s ease;
        }
        
        .category-card:hover {
            transform: translateY(-2px);
        }
        
        .test-badge {
            font-size: 0.8em;
        }
        
        /* Styles pour la timeline des étapes de tests */
        .timeline {
            position: relative;
        }
        
        .timeline-item {
            position: relative;
        }
        
        .timeline-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .timeline-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #dee2e6;
        }
        
        .timeline-item:not(:last-child) .timeline-content::after {
            content: '';
            position: absolute;
            left: 19px;
            top: 40px;
            width: 2px;
            height: calc(100% + 20px);
            background: #dee2e6;
            z-index: -1;
        }
        
        .test-name-link:hover {
            color: #0d6efd !important;
            text-decoration: underline !important;
        }
        
        /* Styles pour les marqueurs de timeline */
        .timeline-marker.bg-primary {
            background-color: #0d6efd !important;
        }
        
        .timeline-marker.bg-success {
            background-color: #198754 !important;
        }
        
        .timeline-marker.bg-info {
            background-color: #0dcaf0 !important;
        }
        
        .timeline-marker.bg-danger {
            background-color: #dc3545 !important;
        }
        
        .timeline-marker.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .timeline-marker.bg-secondary {
            background-color: #6c757d !important;
        }
        
        .timeline-marker.bg-muted {
            background-color: #6c757d !important;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-code-branch"></i> Versions du Système
                    <span class="badge bg-danger">Admin</span>
                </h1>
            </div>
        </div>

        <!-- Onglets de navigation -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="versionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="true">
                            <i class="fas fa-server tab-icon"></i>Système
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="application-tab" data-bs-toggle="tab" data-bs-target="#application" type="button" role="tab" aria-controls="application" aria-selected="false">
                            <i class="fas fa-code tab-icon"></i>Application
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab" aria-controls="database" aria-selected="false">
                            <i class="fas fa-database tab-icon"></i>Base de Données
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="migrations-tab" data-bs-toggle="tab" data-bs-target="#migrations" type="button" role="tab" aria-controls="migrations" aria-selected="false">
                            <i class="fas fa-history tab-icon"></i>Migrations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tests-tab" data-bs-toggle="tab" data-bs-target="#tests" type="button" role="tab" aria-controls="tests" aria-selected="false">
                            <i class="fas fa-vial tab-icon"></i>Tests
                            <?php if ($testStats['total_tests'] > 0): ?>
                                <span class="badge bg-<?= $testStats['success_rate'] >= 80 ? 'success' : ($testStats['success_rate'] >= 60 ? 'warning' : 'danger') ?> ms-1">
                                    <?= $testStats['success_rate'] ?>%
                                </span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab" aria-controls="actions" aria-selected="false">
                            <i class="fas fa-tools tab-icon"></i>Actions
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="versionTabsContent">
            <!-- Onglet Système -->
            <div class="tab-pane fade show active" id="system" role="tabpanel" aria-labelledby="system-tab">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card stats-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-server"></i> Informations Système
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-value"><?= $systemInfo['php_version'] ?></div>
                                        <div class="stat-label">PHP Version</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-value"><?= $systemInfo['mysql_version'] ?></div>
                                        <div class="stat-label">MySQL Version</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-value"><?= $systemInfo['server_time'] ?></div>
                                        <div class="stat-label">Heure Serveur</div>
                                    </div>
                                    <div class="col-md-3 stat-item">
                                        <div class="stat-value"><?= $systemInfo['timezone'] ?></div>
                                        <div class="stat-label">Timezone</div>
                                    </div>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.3);">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Limite mémoire:</strong> <?= $systemInfo['memory_limit'] ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Temps d'exécution max:</strong> <?= $systemInfo['max_execution_time'] ?>s
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Application -->
            <div class="tab-pane fade" id="application" role="tabpanel" aria-labelledby="application-tab">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card version-card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-code"></i> Version de l'Application
                                </h5>
                                <button class="btn btn-light btn-sm" onclick="copyVersionInfo()" title="Copier les informations de version">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
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
            </div>

            <!-- Onglet Base de Données -->
            <div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
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
            </div>

            <!-- Onglet Migrations -->
            <div class="tab-pane fade" id="migrations" role="tabpanel" aria-labelledby="migrations-tab">
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
            </div>

            <!-- Onglet Tests -->
            <div class="tab-pane fade" id="tests" role="tabpanel" aria-labelledby="tests-tab">
                <!-- Statistiques générales -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card stats-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar"></i> Statistiques des Tests
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value"><?= $testStats['total_tests'] ?></div>
                                        <div class="stat-label">Total Tests</div>
                                    </div>
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value text-success"><?= $testStats['passed_tests'] ?></div>
                                        <div class="stat-label">Réussis</div>
                                    </div>
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value text-danger"><?= $testStats['failed_tests'] ?></div>
                                        <div class="stat-label">Échoués</div>
                                    </div>
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value text-warning"><?= $testStats['skipped_tests'] ?></div>
                                        <div class="stat-label">Ignorés</div>
                                    </div>
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value text-info"><?= $testStats['error_tests'] ?></div>
                                        <div class="stat-label">Erreurs</div>
                                    </div>
                                    <div class="col-md-2 stat-item">
                                        <div class="stat-value text-<?= $testStats['success_rate'] >= 80 ? 'success' : ($testStats['success_rate'] >= 60 ? 'warning' : 'danger') ?>">
                                            <?= $testStats['success_rate'] ?>%
                                        </div>
                                        <div class="stat-label">Taux de Réussite</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tests par catégorie -->
                <div class="row">
                    <div class="col-12">
                        <div class="card version-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> Tests par Catégorie
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($testStats['tests_by_category'])): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        Aucun test trouvé.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($testStats['tests_by_category'] as $category => $tests): ?>
                                    <div class="mb-4">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-folder"></i> <?= htmlspecialchars($category) ?>
                                            <span class="badge bg-secondary ms-2"><?= count($tests) ?> tests</span>
                                            <span class="badge bg-<?= $testStats['categories'][$category]['passed'] == $testStats['categories'][$category]['total'] ? 'success' : ($testStats['categories'][$category]['passed'] > 0 ? 'warning' : 'danger') ?> ms-1">
                                                <?= $testStats['categories'][$category]['total'] > 0 ? round(($testStats['categories'][$category]['passed'] / $testStats['categories'][$category]['total']) * 100, 1) : 0 ?>% réussite
                                            </span>
                                        </h6>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 40%;">Nom du Test</th>
                                                        <th style="width: 15%;">Statut</th>
                                                        <th style="width: 10%;">Durée</th>
                                                        <th style="width: 15%;">Date</th>
                                                        <th style="width: 20%;">Heure</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tests as $test): ?>
                                                    <tr>
                                                        <td>
                                                            <code class="text-dark test-name-link" 
                                                                  style="cursor: pointer; text-decoration: underline;" 
                                                                  onclick="showTestDetails('<?= htmlspecialchars($test['name']) ?>', '<?= htmlspecialchars($test['timestamp']) ?>')"
                                                                  title="Cliquer pour voir les détails du test">
                                                                <?= htmlspecialchars($test['name']) ?>
                                                            </code>
                                                        </td>
                                                        <td>
                                                            <?php if ($test['status'] === 'PASSED'): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i> Réussi
                                                                </span>
                                                            <?php elseif ($test['status'] === 'FAILED'): ?>
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-times"></i> Échoué
                                                                </span>
                                                            <?php elseif ($test['status'] === 'SKIPPED'): ?>
                                                                <span class="badge bg-warning">
                                                                    <i class="fas fa-forward"></i> Ignoré
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info">
                                                                    <i class="fas fa-exclamation-triangle"></i> Erreur
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted"><?= number_format($test['duration'], 2) ?>s</span>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted"><?= $test['date'] ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="text-muted"><?= $test['time'] ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet Actions -->
            <div class="tab-pane fade" id="actions" role="tabpanel" aria-labelledby="actions-tab">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-tools"></i> Actions Administrateur
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Section Sauvegarde et Chargement -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-database"></i> Sauvegarde et Chargement des Données
                                        </h6>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin_save_data.php" class="btn btn-primary w-100">
                                            <i class="fas fa-download"></i> Save Data
                                        </a>
                                        <small class="text-muted d-block mt-1">Exporte la base de données (sans users) et les fichiers uploadés</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin_save_users.php" class="btn btn-success w-100">
                                            <i class="fas fa-users"></i> Save Users
                                        </a>
                                        <small class="text-muted d-block mt-1">Exporte uniquement la table des utilisateurs</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin_load_data.php" class="btn btn-warning w-100">
                                            <i class="fas fa-upload"></i> Load Data
                                        </a>
                                        <small class="text-muted d-block mt-1">Importe un fichier généré avec Save Data</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin_load_users.php" class="btn btn-info w-100">
                                            <i class="fas fa-user-plus"></i> Load Users
                                        </a>
                                        <small class="text-muted d-block mt-1">Importe un fichier généré avec Save Users</small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <!-- Section Navigation -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-secondary mb-3">
                                            <i class="fas fa-navigation"></i> Navigation
                                        </h6>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="index.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-home"></i> Retour à l'accueil
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button class="btn btn-outline-info w-100" onclick="location.reload()">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin_starting_equipment.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-shopping-bag"></i> Équipements de Départ
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="profile.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-user-cog"></i> Profil Admin
                                        </a>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <h6><i class="fas fa-info-circle"></i> Informations sur les onglets</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-server text-primary"></i> <strong>Système:</strong> Informations sur PHP, MySQL, serveur</li>
                                                    <li><i class="fas fa-code text-primary"></i> <strong>Application:</strong> Version actuelle et détails de déploiement</li>
                                                    <li><i class="fas fa-database text-success"></i> <strong>Base de Données:</strong> Historique des versions de la DB</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-history text-info"></i> <strong>Migrations:</strong> Historique des migrations exécutées</li>
                                                    <li><i class="fas fa-vial text-warning"></i> <strong>Tests:</strong> Résultats des tests JSON avec statistiques</li>
                                                    <li><i class="fas fa-tools text-secondary"></i> <strong>Actions:</strong> Actions administrateur et navigation</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher les détails des tests -->
    <div class="modal fade" id="testDetailsModal" tabindex="-1" aria-labelledby="testDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testDetailsModalLabel">
                        <i class="fas fa-vial"></i> Détails du Test
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="testDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2">Chargement des détails du test...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="exportTestDetails()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Amélioration de l'expérience utilisateur avec les onglets
        document.addEventListener('DOMContentLoaded', function() {
            // Sauvegarder l'onglet actif dans le localStorage
            const tabButtons = document.querySelectorAll('#versionTabs button[data-bs-toggle="tab"]');
            const tabContent = document.getElementById('versionTabsContent');
            
            // Restaurer l'onglet actif depuis le localStorage
            const activeTab = localStorage.getItem('activeVersionTab');
            if (activeTab) {
                const tabToActivate = document.querySelector(`#${activeTab}-tab`);
                if (tabToActivate) {
                    const tab = new bootstrap.Tab(tabToActivate);
                    tab.show();
                }
            }
            
            // Écouter les changements d'onglets
            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('data-bs-target').substring(1);
                    localStorage.setItem('activeVersionTab', targetId);
                    
                    // Animation d'entrée pour le contenu
                    const activePane = document.querySelector(`#${targetId}`);
                    if (activePane) {
                        activePane.style.opacity = '0';
                        activePane.style.transform = 'translateY(10px)';
                        
                        setTimeout(() => {
                            activePane.style.transition = 'all 0.3s ease';
                            activePane.style.opacity = '1';
                            activePane.style.transform = 'translateY(0)';
                        }, 50);
                    }
                });
            });
            
            // Auto-refresh des données système toutes les 30 secondes
            setInterval(function() {
                // Mettre à jour l'heure serveur dans l'onglet système
                const serverTimeElement = document.querySelector('.stat-value');
                if (serverTimeElement && serverTimeElement.textContent.includes(':')) {
                    const now = new Date();
                    const timeString = now.toLocaleString('fr-FR', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    serverTimeElement.textContent = timeString;
                }
            }, 1000);
            
            // Ajouter des tooltips aux badges
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Fonction pour afficher les détails d'un test
        function showTestDetails(testName, timestamp) {
            const modal = new bootstrap.Modal(document.getElementById('testDetailsModal'));
            const modalTitle = document.getElementById('testDetailsModalLabel');
            const modalContent = document.getElementById('testDetailsContent');
            
            // Mettre à jour le titre de la modal
            modalTitle.innerHTML = `<i class="fas fa-vial"></i> Détails du Test: ${testName}`;
            
            // Afficher la modal
            modal.show();
            
            // Charger les détails du test via AJAX
            loadTestDetails(testName, timestamp, modalContent);
        }
        
        // Fonction pour charger les détails d'un test
        function loadTestDetails(testName, timestamp, contentElement) {
            // Afficher le spinner de chargement
            contentElement.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails du test...</p>
                </div>
            `;
            
            // Simuler le chargement des données (en réalité, ce serait un appel AJAX)
            setTimeout(() => {
                // Pour l'instant, on va chercher les données dans les rapports JSON existants
                fetchTestReportData(testName, timestamp, contentElement);
            }, 500);
        }
        
        // Fonction pour récupérer les données du rapport de test
        function fetchTestReportData(testName, timestamp, contentElement) {
            // Construire le nom du fichier de rapport
            const safeTestName = testName.replace(/[<>:"/\\|?*]/g, '_');
            const reportUrl = `tests/reports/individual/${safeTestName}.json`;
            
            fetch(reportUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Rapport de test non trouvé');
                    }
                    return response.json();
                })
                .then(data => {
                    displayTestDetails(data, contentElement);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du rapport:', error);
                    contentElement.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Rapport non disponible</strong><br>
                            Le rapport détaillé pour ce test n'est pas encore disponible.
                            <br><br>
                            <small class="text-muted">Erreur: ${error.message}</small>
                        </div>
                    `;
                });
        }
        
        // Fonction pour afficher les détails du test
        function displayTestDetails(testData, contentElement) {
            const testInfo = testData.test_info || {};
            const result = testData.result || {};
            const execution = testData.execution || {};
            const testSteps = testData.test_steps || [];
            const versionInfo = testData.version_info || {};
            
            let html = `
                <div class="row">
                    <!-- Informations générales -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informations Générales</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Nom:</strong></td>
                                        <td><code>${testInfo.name || 'N/A'}</code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fichier:</strong></td>
                                        <td><small>${testInfo.file || 'N/A'}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Catégorie:</strong></td>
                                        <td><span class="badge bg-secondary">${testInfo.category || 'N/A'}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Priorité:</strong></td>
                                        <td><span class="badge bg-info">${testInfo.priority || 'N/A'}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statut:</strong></td>
                                        <td>
                                            ${result.status === 'PASSED' ? 
                                                '<span class="badge bg-success"><i class="fas fa-check"></i> Réussi</span>' :
                                                result.status === 'FAILED' ?
                                                '<span class="badge bg-danger"><i class="fas fa-times"></i> Échoué</span>' :
                                                '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Erreur</span>'
                                            }
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Durée:</strong></td>
                                        <td>${testInfo.duration_seconds ? testInfo.duration_seconds.toFixed(2) + 's' : 'N/A'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations d'exécution -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Exécution</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Début:</strong></td>
                                        <td><small>${execution.start_time_formatted || 'N/A'}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fin:</strong></td>
                                        <td><small>${execution.end_time_formatted || 'N/A'}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td><small>${testInfo.date || 'N/A'}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Timestamp:</strong></td>
                                        <td><small>${testInfo.timestamp || 'N/A'}</small></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter les erreurs si présentes
            if (result.error_message) {
                html += `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Erreur</h6>
                        <p class="mb-0">${result.error_message}</p>
                        ${result.stack_trace ? `<pre class="mt-2"><code>${result.stack_trace}</code></pre>` : ''}
                    </div>
                `;
            }
            
            // Ajouter les étapes du test si disponibles
            if (testSteps && testSteps.length > 0) {
                html += `
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-list-ol"></i> Étapes du Test (${testSteps.length})</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                `;
                
                testSteps.forEach((step, index) => {
                    const stepType = step.type || 'action';
                    const stepIcon = getStepIcon(stepType);
                    const stepColor = getStepColor(stepType);
                    
                    html += `
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="timeline-marker ${stepColor} me-3">
                                    <i class="${stepIcon}"></i>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1">${step.name || 'Étape ' + (index + 1)}</h6>
                                        <small class="text-muted">${step.duration_seconds ? step.duration_seconds.toFixed(2) + 's' : ''}</small>
                                    </div>
                                    <p class="mb-1">${step.description || ''}</p>
                                    <small class="text-muted">${step.datetime || ''}</small>
                                    ${step.details && Object.keys(step.details).length > 0 ? 
                                        `<div class="mt-2"><small><strong>Détails:</strong> ${JSON.stringify(step.details, null, 2)}</small></div>` : ''
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Aucune étape détaillée disponible pour ce test.
                    </div>
                `;
            }
            
            contentElement.innerHTML = html;
        }
        
        // Fonction pour obtenir l'icône d'une étape
        function getStepIcon(stepType) {
            const icons = {
                'action': 'fas fa-play',
                'assertion': 'fas fa-check',
                'info': 'fas fa-info-circle',
                'error': 'fas fa-times',
                'warning': 'fas fa-exclamation-triangle',
                'screenshot': 'fas fa-camera'
            };
            return icons[stepType] || 'fas fa-circle';
        }
        
        // Fonction pour obtenir la couleur d'une étape
        function getStepColor(stepType) {
            const colors = {
                'action': 'bg-primary',
                'assertion': 'bg-success',
                'info': 'bg-info',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'screenshot': 'bg-secondary'
            };
            return colors[stepType] || 'bg-muted';
        }
        
        // Fonction pour exporter les détails du test
        function exportTestDetails() {
            const testName = document.getElementById('testDetailsModalLabel').textContent.replace('Détails du Test: ', '');
            const testData = document.getElementById('testDetailsContent').innerHTML;
            
            // Créer un fichier de rapport HTML
            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Rapport de Test - ${testName}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                </head>
                <body>
                    <div class="container mt-4">
                        <h1><i class="fas fa-vial"></i> Rapport de Test: ${testName}</h1>
                        <p class="text-muted">Généré le ${new Date().toLocaleString('fr-FR')}</p>
                        <hr>
                        ${testData}
                    </div>
                </body>
                </html>
            `;
            
            // Télécharger le fichier
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `rapport_test_${testName.replace(/[^a-zA-Z0-9]/g, '_')}.html`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Fonction pour copier les informations de version
        function copyVersionInfo() {
            const versionInfo = {
                application: '<?= $appVersion['VERSION'] ?>',
                build: '<?= $appVersion['BUILD_ID'] ?>',
                environment: '<?= $appVersion['ENVIRONMENT'] ?>',
                deployDate: '<?= $appVersion['DEPLOY_DATE'] ?>',
                gitCommit: '<?= substr($appVersion['GIT_COMMIT'], 0, 8) ?>',
                php: '<?= $systemInfo['php_version'] ?>',
                mysql: '<?= $systemInfo['mysql_version'] ?>'
            };
            
            const text = `Version: ${versionInfo.application}\nBuild: ${versionInfo.build}\nEnvironment: ${versionInfo.environment}\nDeploy Date: ${versionInfo.deployDate}\nGit Commit: ${versionInfo.gitCommit}\nPHP: ${versionInfo.php}\nMySQL: ${versionInfo.mysql}`;
            
            navigator.clipboard.writeText(text).then(function() {
                // Afficher une notification de succès
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>Informations de version copiées !
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Supprimer l'élément après 3 secondes
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 3000);
            });
        }
    </script>
</body>
</html>
