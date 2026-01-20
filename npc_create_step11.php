<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

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
if (!$sessionData || $sessionData['step'] < 9) {
    header('Location: npc_create_step1.php');
    exit();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'next') {
        $world_id = $_POST['world_id'] ?? '';
        $country_id = $_POST['country_id'] ?? '';
        $region_id = $_POST['region_id'] ?? '';
        $place_id = $_POST['place_id'] ?? '';
        
        if ($world_id && $place_id) {
            // Sauvegarder les données de positionnement
            $locationData = [
                'world_id' => $world_id,
                'country_id' => $country_id,
                'region_id' => $region_id,
                'place_id' => $place_id
            ];
            
            saveNPCCreationStep($user_id, $session_id, 11, $locationData);
            
            // Rediriger vers l'étape de finalisation
            header("Location: npc_create_step12.php?session_id=$session_id");
            exit();
        } else {
            $message = displayMessage("Veuillez sélectionner un monde et une pièce.", "error");
        }
    } elseif ($action === 'previous') {
        header("Location: npc_create_step9.php?session_id=$session_id");
        exit();
    }
}

// Récupérer les mondes de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $worlds = [];
}

// Récupérer les données de positionnement sauvegardées
$locationData = $sessionData['step_11'] ?? [];
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
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .location-selector {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: border-color 0.3s ease;
        }
        .location-selector:hover {
            border-color: #667eea;
        }
        .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: border-color 0.3s ease;
        }
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Indicateur d'étape -->
        <div class="step-indicator text-center">
            <h2><i class="fas fa-map-marker-alt"></i> Positionnement dans le Monde</h2>
            <p class="mb-0">Étape 10 sur 11 - Choisissez la pièce d'affectation initiale de votre PNJ</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de positionnement -->
        <div class="form-section">
            <h3 class="mb-4"><i class="fas fa-globe"></i> Sélection du Pièce d'Affectation</h3>
            
            <form method="POST" id="locationForm">
                <input type="hidden" name="action" value="next">
                
                <!-- Sélection du Monde -->
                <div class="location-selector">
                    <label for="world_id" class="form-label fw-bold">
                        <i class="fas fa-globe-americas"></i> Monde
                    </label>
                    <select class="form-select" id="world_id" name="world_id" required onchange="loadCountries()">
                        <option value="">Sélectionnez un monde</option>
                        <?php foreach ($worlds as $world): ?>
                            <option value="<?php echo $world['id']; ?>" 
                                    <?php echo (isset($locationData['world_id']) && $locationData['world_id'] == $world['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($world['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélection du Pays -->
                <div class="location-selector">
                    <label for="country_id" class="form-label fw-bold">
                        <i class="fas fa-flag"></i> Pays
                    </label>
                    <select class="form-select" id="country_id" name="country_id" onchange="loadRegions()">
                        <option value="">Sélectionnez d'abord un monde</option>
                    </select>
                </div>

                <!-- Sélection de la Région -->
                <div class="location-selector">
                    <label for="region_id" class="form-label fw-bold">
                        <i class="fas fa-mountain"></i> Région
                    </label>
                    <select class="form-select" id="region_id" name="region_id" onchange="loadPlaces()">
                        <option value="">Sélectionnez d'abord un pays</option>
                    </select>
                </div>

                <!-- Sélection du Pièce -->
                <div class="location-selector">
                    <label for="place_id" class="form-label fw-bold">
                        <i class="fas fa-map-marker-alt"></i> Pièce d'Affectation
                    </label>
                    <select class="form-select" id="place_id" name="place_id" required>
                        <option value="">Sélectionnez d'abord une région</option>
                    </select>
                    <div class="form-text">
                        <i class="fas fa-info-circle"></i> Cette pièce sera l'emplacement initial de votre PNJ dans le monde.
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" name="action" value="previous" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Étape Précédente
                    </button>
                    <button type="submit" name="action" value="next" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Finaliser la Création
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Charger les pays quand un monde est sélectionné
        function loadCountries() {
            const worldId = document.getElementById('world_id').value;
            const countrySelect = document.getElementById('country_id');
            const regionSelect = document.getElementById('region_id');
            const placeSelect = document.getElementById('place_id');
            
            // Réinitialiser les sélections dépendantes
            countrySelect.innerHTML = '<option value="">Sélectionnez d\'abord un monde</option>';
            regionSelect.innerHTML = '<option value="">Sélectionnez d\'abord un pays</option>';
            placeSelect.innerHTML = '<option value="">Sélectionnez d\'abord une région</option>';
            
            if (worldId) {
                fetch(`get_countries_by_world.php?world_id=${worldId}`)
                    .then(response => response.json())
                    .then(data => {
                        countrySelect.innerHTML = '<option value="">Sélectionnez un pays</option>';
                        data.forEach(country => {
                            const option = document.createElement('option');
                            option.value = country.id;
                            option.textContent = country.name;
                            countrySelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Charger les régions quand un pays est sélectionné
        function loadRegions() {
            const countryId = document.getElementById('country_id').value;
            const regionSelect = document.getElementById('region_id');
            const placeSelect = document.getElementById('place_id');
            
            // Réinitialiser les sélections dépendantes
            regionSelect.innerHTML = '<option value="">Sélectionnez d\'abord un pays</option>';
            placeSelect.innerHTML = '<option value="">Sélectionnez d\'abord une région</option>';
            
            if (countryId) {
                fetch(`get_regions_by_country.php?country_id=${countryId}`)
                    .then(response => response.json())
                    .then(data => {
                        regionSelect.innerHTML = '<option value="">Sélectionnez une région</option>';
                        data.forEach(region => {
                            const option = document.createElement('option');
                            option.value = region.id;
                            option.textContent = region.name;
                            regionSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Charger les pièces quand une région est sélectionnée
        function loadPlaces() {
            const regionId = document.getElementById('region_id').value;
            const placeSelect = document.getElementById('place_id');
            
            // Réinitialiser la sélection
            placeSelect.innerHTML = '<option value="">Sélectionnez d\'abord une région</option>';
            
            if (regionId) {
                fetch(`get_places_by_region.php?region_id=${regionId}`)
                    .then(response => response.json())
                    .then(data => {
                        placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                        data.forEach(place => {
                            const option = document.createElement('option');
                            option.value = place.id;
                            option.textContent = place.title;
                            placeSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Charger les données sauvegardées au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const locationData = <?php echo json_encode($locationData); ?>;
            
            if (locationData.world_id) {
                loadCountries();
                // Attendre que les pays soient chargés avant de sélectionner
                setTimeout(() => {
                    document.getElementById('country_id').value = locationData.country_id || '';
                    if (locationData.country_id) {
                        loadRegions();
                        setTimeout(() => {
                            document.getElementById('region_id').value = locationData.region_id || '';
                            if (locationData.region_id) {
                                loadPlaces();
                                setTimeout(() => {
                                    document.getElementById('place_id').value = locationData.place_id || '';
                                }, 500);
                            }
                        }, 500);
                    }
                }, 500);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>