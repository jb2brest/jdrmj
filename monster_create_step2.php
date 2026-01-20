<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';

// Vérifier que le monde_id est fourni
if (!isset($_POST['world_id']) || empty($_POST['world_id'])) {
    header('Location: monster_create_step1.php?error=no_world');
    exit();
}

$world_id = (int)$_POST['world_id'];

// Vérifier que le monde appartient à l'utilisateur
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE id = ? AND created_by = ?");
    $stmt->execute([$world_id, $user_id]);
    $world = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$world) {
        header('Location: monster_create_step1.php?error=invalid_world');
        exit();
    }
} catch (Exception $e) {
    header('Location: monster_create_step1.php?error=database_error');
    exit();
}

// Récupérer les pays du monde
try {
    $stmt = $pdo->prepare("SELECT id, name FROM countries WHERE world_id = ? ORDER BY name");
    $stmt->execute([$world_id]);
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($countries)) {
        $error_message = "Ce monde ne contient aucun pays. Vous devez d'abord créer des pays dans ce monde.";
    }
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des pays.";
    $countries = [];
}

$page_title = "Création de Monstre - Étape 2";
$current_page = "manage_npcs";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>Création de Monstre - Étape 2
                            </h4>
                            <a href="monster_create_step1.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Monde sélectionné :</strong> <?php echo htmlspecialchars($world['name']); ?>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <?php echo displayMessage($error_message, 'error'); ?>
                        <?php endif; ?>
                        
                        <?php if (empty($countries)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun pays créé</h5>
                                <p class="text-muted">Ce monde ne contient aucun pays. Vous devez d'abord créer des pays dans ce monde.</p>
                                <a href="manage_worlds.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Gérer les Mondes
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="monster_create_step3.php" id="locationForm">
                                <input type="hidden" name="world_id" value="<?php echo $world_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="country_id" class="form-label">Pays *</label>
                                            <select class="form-select" id="country_id" name="country_id" required>
                                                <option value="">Sélectionnez un pays</option>
                                                <?php foreach ($countries as $country): ?>
                                                    <option value="<?php echo $country['id']; ?>">
                                                        <?php echo htmlspecialchars($country['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="region_id" class="form-label">Région</label>
                                            <select class="form-select" id="region_id" name="region_id">
                                                <option value="">Aucune région</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="place_id" class="form-label">Pièce *</label>
                                            <select class="form-select" id="place_id" name="place_id" required>
                                                <option value="">Sélectionnez une pièce</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="monster_create_step1.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Étape précédente
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-1"></i>Étape suivante
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informations sur le processus -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Processus de création
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #28a745; color: white;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h6>Monde</h6>
                                <small class="text-muted">Sélection du monde</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #007bff; color: white;">
                                    <strong>2</strong>
                                </div>
                                <h6>Localisation</h6>
                                <small class="text-muted">Pays, région, pièce</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d;">
                                    <strong>3</strong>
                                </div>
                                <h6>Monstre</h6>
                                <small class="text-muted">Type, quantité, stats</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d;">
                                    <strong>4</strong>
                                </div>
                                <h6>Finalisation</h6>
                                <small class="text-muted">Confirmation</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countrySelect = document.getElementById('country_id');
            const regionSelect = document.getElementById('region_id');
            const placeSelect = document.getElementById('place_id');
            
            // Données des régions et pièces (récupérées via AJAX)
            let regionsData = [];
            let placesData = [];
            
            // Charger les régions et pièces pour tous les pays
            fetch('get_regions_by_country.php')
                .then(response => response.json())
                .then(data => {
                    regionsData = data;
                })
                .catch(error => console.error('Erreur lors du chargement des régions:', error));
            
            fetch('get_places_by_region.php')
                .then(response => response.json())
                .then(data => {
                    placesData = data;
                })
                .catch(error => console.error('Erreur lors du chargement des pièces:', error));
            
            function updateRegions(countryId) {
                regionSelect.innerHTML = '<option value="">Aucune région</option>';
                placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                
                if (countryId) {
                    const countryRegions = regionsData.filter(region => region.country_id == countryId);
                    countryRegions.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.id;
                        option.textContent = region.name;
                        regionSelect.appendChild(option);
                    });
                }
            }
            
            function updatePlaces(countryId, regionId) {
                placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                
                if (countryId) {
                    let filteredPlaces = placesData.filter(place => place.country_id == countryId);
                    
                    if (regionId) {
                        filteredPlaces = filteredPlaces.filter(place => place.region_id == regionId);
                    }
                    
                    filteredPlaces.forEach(place => {
                        const option = document.createElement('option');
                        option.value = place.id;
                        option.textContent = place.title;
                        placeSelect.appendChild(option);
                    });
                }
            }
            
            countrySelect.addEventListener('change', function() {
                updateRegions(this.value);
                updatePlaces(this.value, '');
            });
            
            regionSelect.addEventListener('change', function() {
                updatePlaces(countrySelect.value, this.value);
            });
        });
    </script>
</body>
</html>





