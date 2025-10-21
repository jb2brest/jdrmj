<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
require_once 'includes/starting_equipment_functions.php';

$page_title = "Création de PNJ - Étape 11";
$current_page = "create_npc";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

if (!$session_id) {
    header('Location: npc_create_step1.php');
    exit();
}

// Récupérer les données de la session
$sessionData = getNPCCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 11) {
    header('Location: npc_create_step1.php');
    exit();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'finalize') {
        // Récupérer toutes les données de la session
        $data = $sessionData['data'];
        
        // Récupérer l'équipement de départ
        $equipmentData = generateFinalEquipmentNew(
            $data['class_id'] ?? 1, 
            $data['background_id'] ?? 1, 
            $data['race_id'] ?? 1, 
            $data['equipment_choices'] ?? []
        );
        
        // Créer le PNJ dans la base de données
        $npcData = [
            'name' => $data['name'] ?? 'PNJ',
            'class_id' => $data['class_id'] ?? 1,
            'race_id' => $data['race_id'] ?? 1,
            'background_id' => $data['background_id'] ?? 1,
            'archetype_id' => $data['archetype_id'] ?? null,
            'level' => 1,
            'experience' => 0,
            'strength' => $data['strength'] ?? 10,
            'dexterity' => $data['dexterity'] ?? 10,
            'constitution' => $data['constitution'] ?? 10,
            'intelligence' => $data['intelligence'] ?? 10,
            'wisdom' => $data['wisdom'] ?? 10,
            'charisma' => $data['charisma'] ?? 10,
            'hit_points' => $data['hit_points'] ?? 8,
            'armor_class' => $data['armor_class'] ?? 10,
            'speed' => $data['speed'] ?? 30,
            'alignment' => $data['alignment'] ?? 'Neutre Neutre',
            'age' => $data['age'] ?? '',
            'height' => $data['height'] ?? '',
            'weight' => $data['weight'] ?? '',
            'eyes' => $data['eyes'] ?? '',
            'skin' => $data['skin'] ?? '',
            'hair' => $data['hair'] ?? '',
            'backstory' => $data['backstory'] ?? '',
            'personality_traits' => $data['personality_traits'] ?? '',
            'ideals' => $data['ideals'] ?? '',
            'bonds' => $data['bonds'] ?? '',
            'flaws' => $data['flaws'] ?? '',
            'starting_equipment' => is_array($equipmentData) && !empty($equipmentData) ? json_encode($equipmentData) : (is_string($equipmentData) ? $equipmentData : ''),
            'gold' => 0,
            'spells' => $data['spells'] ?? '',
            'skills' => $data['skills'] ?? '',
            'languages' => $data['languages'] ?? '',
            'profile_photo' => $data['profile_photo'] ?? null,
            'created_by' => $user_id,
            'world_id' => $locationData['world_id'] ?? 307, // Utiliser l'ID du monde Aeridon par défaut
            'location_id' => $locationData['place_id'] ?? null,
            'is_active' => 1
        ];
        
        // Vérifier que le world_id existe
        $worldId = $npcData['world_id'];
        $stmt = $pdo->prepare("SELECT id FROM worlds WHERE id = ?");
        $stmt->execute([$worldId]);
        if (!$stmt->fetch()) {
            // Si le monde n'existe pas, utiliser le monde par défaut (Aeridon)
            $npcData['world_id'] = 307;
        }
        
        // Vérifier que le location_id existe (si fourni)
        if ($npcData['location_id']) {
            $stmt = $pdo->prepare("SELECT id FROM places WHERE id = ?");
            $stmt->execute([$npcData['location_id']]);
            if (!$stmt->fetch()) {
                // Si le lieu n'existe pas, ne pas spécifier de lieu
                $npcData['location_id'] = null;
            }
        }
        
        // Insérer le PNJ dans la base de données
        $npc = new NPC();
        $npcId = $npc->create($npcData);
        
        if ($npcId) {
            // Créer une entrée dans place_npcs pour la visibilité
            if ($npcData['location_id']) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO place_npcs (name, description, profile_photo, is_visible, is_identified, place_id, monster_id, npc_character_id) 
                        VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
                    ");
                    $description = "PNJ de niveau " . ($npcData['level'] ?? 1) . " - " . ($npcData['personality_traits'] ?? '');
                    $stmt->execute([
                        $npcData['name'],
                        $description,
                        $npcData['profile_photo'] ?? null,
                        1, // is_visible
                        1, // is_identified
                        $npcData['location_id'],
                        $npcId // npc_character_id (référence vers la table npcs)
                    ]);
                } catch (Exception $e) {
                    error_log("Erreur lors de la création dans place_npcs: " . $e->getMessage());
                }
            }
            
            // Nettoyer la session de création
            clearNPCCreationData($user_id, $session_id);
            
            // Rediriger vers la fiche du PNJ créé
            header("Location: view_npc.php?id=$npcId&created=1");
            exit();
        } else {
            $message = displayMessage("Erreur lors de la création du PNJ.", "error");
        }
    } elseif ($action === 'previous') {
        header("Location: npc_create_step11.php?session_id=$session_id");
        exit();
    }
}

// Récupérer les informations pour l'affichage
try {
    $stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->execute([$sessionData['data']['race_id'] ?? 1]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$sessionData['data']['class_id'] ?? 1]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
    $stmt->execute([$sessionData['data']['background_id'] ?? 1]);
    $background = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $archetype = null;
    if (!empty($sessionData['data']['archetype_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM class_archetypes WHERE id = ?");
        $stmt->execute([$sessionData['data']['archetype_id']]);
        $archetype = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $race = ['name' => 'Inconnue'];
    $class = ['name' => 'Inconnue'];
    $background = ['name' => 'Inconnu'];
    $archetype = null;
}

// Calculer les statistiques finales
$finalStats = [
    'strength' => $sessionData['data']['strength'] ?? 10,
    'dexterity' => $sessionData['data']['dexterity'] ?? 10,
    'constitution' => $sessionData['data']['constitution'] ?? 10,
    'intelligence' => $sessionData['data']['intelligence'] ?? 10,
    'wisdom' => $sessionData['data']['wisdom'] ?? 10,
    'charisma' => $sessionData['data']['charisma'] ?? 10,
    'hit_points' => $sessionData['data']['hit_points'] ?? 8,
    'armor_class' => $sessionData['data']['armor_class'] ?? 10,
    'speed' => $sessionData['data']['speed'] ?? 30
];

// Récupérer l'équipement de départ
$equipmentData = generateFinalEquipmentNew(
    $sessionData['data']['class_id'] ?? 1, 
    $sessionData['data']['background_id'] ?? 1, 
    $sessionData['data']['race_id'] ?? 1, 
    $sessionData['data']['equipment_choices'] ?? []
);

// Récupérer les informations de localisation
$locationData = $sessionData['data'] ?? [];
$worldName = '';
$placeName = '';

// Debug: Afficher les données de localisation
error_log("Debug locationData: " . print_r($locationData, true));

if (isset($locationData['world_id']) && $locationData['world_id']) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM worlds WHERE id = ?");
        $stmt->execute([$locationData['world_id']]);
        $world = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($world) $worldName = $world['name'];
    } catch (PDOException $e) {}
}

if (isset($locationData['place_id']) && $locationData['place_id']) {
    try {
        $stmt = $pdo->prepare("SELECT title FROM places WHERE id = ?");
        $stmt->execute([$locationData['place_id']]);
        $place = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($place) $placeName = $place['title'];
    } catch (PDOException $e) {}
}
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
    <style>
        .step-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .recap-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .equipment-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Indicateur d'étape -->
        <div class="step-indicator text-center">
            <h2><i class="fas fa-check-circle"></i> Finalisation de la Création</h2>
            <p class="mb-0">Étape 11 sur 11 - Vérifiez et finalisez votre PNJ</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Récapitulatif du PNJ -->
        <div class="recap-section">
            <h3 class="mb-4"><i class="fas fa-user"></i> Récapitulatif de votre PNJ</h3>
            
            <div class="row">
                <!-- Informations de base -->
                <div class="col-md-6">
                    <h5><i class="fas fa-info-circle"></i> Informations de Base</h5>
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($sessionData['data']['name'] ?? 'Non défini'); ?></p>
                    <p><strong>Race :</strong> <?php echo htmlspecialchars($race['name']); ?></p>
                    <p><strong>Classe :</strong> <?php echo htmlspecialchars($class['name']); ?></p>
                    <?php if ($archetype): ?>
                        <p><strong>Archétype :</strong> <?php echo htmlspecialchars($archetype['name']); ?></p>
                    <?php endif; ?>
                    <p><strong>Historique :</strong> <?php echo htmlspecialchars($background['name']); ?></p>
                    <p><strong>Alignement :</strong> <?php echo htmlspecialchars($sessionData['data']['alignment'] ?? 'Non défini'); ?></p>
                </div>
                
                <!-- Statistiques -->
                <div class="col-md-6">
                    <h5><i class="fas fa-dice"></i> Statistiques</h5>
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['strength']; ?></div>
                                <small>Force</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['dexterity']; ?></div>
                                <small>Dextérité</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['constitution']; ?></div>
                                <small>Constitution</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['intelligence']; ?></div>
                                <small>Intelligence</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['wisdom']; ?></div>
                                <small>Sagesse</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $finalStats['charisma']; ?></div>
                                <small>Charisme</small>
                            </div>
                        </div>
                    </div>
                    <p><strong>Points de vie :</strong> <?php echo $finalStats['hit_points']; ?></p>
                    <p><strong>Classe d'armure :</strong> <?php echo $finalStats['armor_class']; ?></p>
                    <p><strong>Vitesse :</strong> <?php echo $finalStats['speed']; ?> pieds</p>
                </div>
            </div>
        </div>

        <!-- Localisation -->
        <div class="recap-section">
            <h5><i class="fas fa-map-marker-alt"></i> Localisation</h5>
            <p><strong>Monde :</strong> <?php echo htmlspecialchars($worldName); ?></p>
            <p><strong>Lieu d'affectation :</strong> <?php echo htmlspecialchars($placeName); ?></p>
        </div>

        <!-- Équipement de départ -->
        <?php if (!empty($equipmentData['equipment'])): ?>
        <div class="recap-section">
            <h5><i class="fas fa-shield-alt"></i> Équipement de Départ</h5>
            <?php foreach ($equipmentData['equipment'] as $item): ?>
                <div class="equipment-item">
                    <i class="fas fa-sword"></i> <?php echo htmlspecialchars($item); ?>
                </div>
            <?php endforeach; ?>
            <?php if ($equipmentData['gold'] > 0): ?>
                <div class="equipment-item">
                    <i class="fas fa-coins"></i> <?php echo $equipmentData['gold']; ?> pièces d'or
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Sorts -->
        <?php if (!empty($sessionData['data']['spells'])): ?>
        <div class="recap-section">
            <h5><i class="fas fa-magic"></i> Sorts</h5>
            <p><?php echo htmlspecialchars($sessionData['data']['spells']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Compétences et Langues -->
        <?php if (!empty($sessionData['data']['skills']) || !empty($sessionData['data']['languages'])): ?>
        <div class="recap-section">
            <h5><i class="fas fa-graduation-cap"></i> Compétences et Langues</h5>
            <?php if (!empty($sessionData['data']['skills'])): ?>
                <p><strong>Compétences :</strong> <?php echo htmlspecialchars($sessionData['data']['skills']); ?></p>
            <?php endif; ?>
            <?php if (!empty($sessionData['data']['languages'])): ?>
                <p><strong>Langues :</strong> <?php echo htmlspecialchars($sessionData['data']['languages']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Histoire du personnage -->
        <?php if (!empty($sessionData['data']['backstory'])): ?>
        <div class="recap-section">
            <h5><i class="fas fa-book"></i> Histoire du Personnage</h5>
            <p><?php echo nl2br(htmlspecialchars($sessionData['data']['backstory'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Boutons de navigation -->
        <div class="d-flex justify-content-between mt-4">
            <button type="button" onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Étape Précédente
            </button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="finalize">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Créer le PNJ
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
