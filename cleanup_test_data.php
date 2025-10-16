<?php
/**
 * Script de nettoyage manuel des utilisateurs de test
 * Usage: php cleanup_test_data.php [--dry-run] [--all] [--days=N]
 */

// Configuration de la base de données
require_once 'config/database.test.php';
$config = include 'config/database.test.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "🔗 Connexion à la base de données établie\n";
    
} catch (PDOException $e) {
    die("❌ Erreur de connexion à la base de données: " . $e->getMessage() . "\n");
}

// Analyser les arguments de ligne de commande
$options = getopt('', ['dry-run', 'all', 'days::']);
$dryRun = isset($options['dry-run']);
$cleanAll = isset($options['all']);
$daysOld = isset($options['days']) ? (int)$options['days'] : 1;

if ($dryRun) {
    echo "🔍 Mode dry-run activé - Aucune suppression ne sera effectuée\n";
}

echo "📋 Configuration:\n";
echo "  - Mode dry-run: " . ($dryRun ? 'OUI' : 'NON') . "\n";
echo "  - Nettoyer tout: " . ($cleanAll ? 'OUI' : 'NON') . "\n";
echo "  - Jours anciens: " . ($cleanAll ? 'TOUS' : $daysOld) . "\n\n";

// Identifier les utilisateurs de test
$testPatterns = [
    'test_%',
    'test_user_%',
    'test_dm_%',
    'test_player_%',
    'test_admin_%',
    'test_delete_%',
    '%@test.com',
    '%@example.com'
];

// Construire la requête
$whereConditions = [];
$params = [];

foreach ($testPatterns as $pattern) {
    $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = $pattern;
    $params[] = $pattern;
}

if (!$cleanAll) {
    $whereConditions[] = "created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $daysOld;
}

$query = "SELECT id, username, email, created_at, role, is_dm 
          FROM users 
          WHERE " . implode(' OR ', $whereConditions) . "
          ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($testUsers)) {
    echo "✅ Aucun utilisateur de test trouvé à supprimer\n";
    exit(0);
}

echo "📋 " . count($testUsers) . " utilisateur(s) de test trouvé(s):\n";
echo str_repeat('-', 80) . "\n";
foreach ($testUsers as $user) {
    printf("ID: %3d | Username: %-20s | Email: %-25s | Créé: %s | Rôle: %-7s | DM: %s\n",
        $user['id'],
        $user['username'],
        $user['email'],
        $user['created_at'],
        $user['role'] ?? 'N/A',
        $user['is_dm'] ? 'OUI' : 'NON'
    );
}

if ($dryRun) {
    echo "\n🔍 Mode dry-run: Aucune suppression effectuée\n";
    exit(0);
}

// Demander confirmation
echo "\n❓ Voulez-vous supprimer ces " . count($testUsers) . " utilisateur(s) de test? (oui/non): ";
$handle = fopen("php://stdin", "r");
$response = trim(fgets($handle));
fclose($handle);

if (!in_array(strtolower($response), ['oui', 'o', 'yes', 'y'])) {
    echo "❌ Suppression annulée\n";
    exit(0);
}

// Supprimer les utilisateurs de test
$deletedCount = 0;
$pdo->beginTransaction();

try {
    foreach ($testUsers as $user) {
        $userId = $user['id'];
        $username = $user['username'];
        
        echo "🗑️  Suppression de l'utilisateur: $username (ID: $userId)\n";
        
        // Supprimer les données liées
        $relatedTables = [
            'characters' => 'user_id',
            'campaigns' => 'dm_id',
            'campaign_sessions' => 'dm_id',
            'dice_rolls' => 'user_id',
            'scene_tokens' => 'user_id',
            'place_objects' => 'user_id',
            'monsters' => 'created_by',
            'magical_items' => 'created_by',
            'poisons' => 'created_by'
        ];
        
        foreach ($relatedTables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$userId]);
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                echo "  - $table: $affectedRows enregistrement(s) supprimé(s)\n";
            }
        }
        
        // Supprimer les données des personnages (cascade)
        $characterRelatedTables = [
            'character_spells' => 'character_id',
            'character_equipment' => 'character_id',
            'character_capabilities' => 'character_id',
            'character_languages' => 'character_id',
            'class_spells' => 'character_id',
            'spell_slots' => 'character_id'
        ];
        
        foreach ($characterRelatedTables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column IN (SELECT id FROM characters WHERE user_id = ?)");
            $stmt->execute([$userId]);
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                echo "  - $table: $affectedRows enregistrement(s) supprimé(s)\n";
            }
        }
        
        // Supprimer les personnages
        $stmt = $pdo->prepare("DELETE FROM characters WHERE user_id = ?");
        $stmt->execute([$userId]);
        $affectedRows = $stmt->rowCount();
        if ($affectedRows > 0) {
            echo "  - characters: $affectedRows personnage(s) supprimé(s)\n";
        }
        
        // Supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $deletedCount++;
        echo "  ✅ Utilisateur $username supprimé avec succès\n\n";
    }
    
    $pdo->commit();
    echo "🎉 Nettoyage terminé: $deletedCount/" . count($testUsers) . " utilisateur(s) supprimé(s)\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Erreur lors du nettoyage: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n📊 Statistiques finales:\n";
echo "  - Utilisateurs traités: " . count($testUsers) . "\n";
echo "  - Utilisateurs supprimés: $deletedCount\n";
echo "  - Erreurs: " . (count($testUsers) - $deletedCount) . "\n";
?>
