<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'monster_functions.php';

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_monsters'])) {
    // Vérification que le formulaire est correctement soumis
    if (empty($_POST['world_id']) || $_POST['world_id'] === '') {
        $error_message = "Veuillez sélectionner un monde.";
    } else {
        $world_id = (int)($_POST['world_id'] ?? 0);
        $country_id = (int)($_POST['country_id'] ?? 0);
        $place_id = (int)($_POST['place_id'] ?? 0);
        $region_id = !empty($_POST['region_id']) ? (int)$_POST['region_id'] : null;
        $count = (int)($_POST['count'] ?? 1);
        $monster_type = !empty($_POST['monster_type']) ? $_POST['monster_type'] : null;
        $challenge_rating = !empty($_POST['challenge_rating']) ? $_POST['challenge_rating'] : null;
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $is_identified = isset($_POST['is_identified']) ? 1 : 0;
    
        // Vérification des données POST transmises
        if (empty($_POST['country_id']) || empty($_POST['place_id'])) {
            $error_message = "Données du formulaire incomplètes. Veuillez remplir tous les champs requis.";
            error_log("Form data incomplete - world_id: " . ($_POST['world_id'] ?? 'NOT_SET') . ", country_id: " . ($_POST['country_id'] ?? 'NOT_SET') . ", place_id: " . ($_POST['place_id'] ?? 'NOT_SET'));
        }
        
        // Validation
        if ($world_id <= 0) {
            $error_message = "Veuillez sélectionner un monde.";
        } elseif ($country_id <= 0) {
            $error_message = "Veuillez sélectionner un pays.";
        } elseif ($place_id <= 0) {
            $error_message = "Veuillez sélectionner une pièce.";
        } elseif ($count <= 0 || $count > 10) {
            $error_message = "Le nombre de monstres doit être entre 1 et 10.";
        } else {
            try {
                $pdo = getPdo();
                
                // Vérifier que les données sont valides
                $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE id = ? AND created_by = ?");
                $stmt->execute([$world_id, $user_id]);
                $world = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$world) {
                    $error_message = "Monde invalide.";
                } else {
                    $stmt = $pdo->prepare("SELECT id, name FROM countries WHERE id = ? AND world_id = ?");
                    $stmt->execute([$country_id, $world_id]);
                    $country = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$country) {
                        $error_message = "Pays invalide.";
                    } else {
                        $stmt = $pdo->prepare("SELECT id, title FROM places WHERE id = ? AND country_id = ?");
                        $stmt->execute([$place_id, $country_id]);
                        $place = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$place) {
                            $error_message = "Pièce invalide.";
                        } else {
                            // Créer les monstres automatiquement
                            $result = createAutomaticMonsters($world_id, $country_id, $place_id, $count, $monster_type, $is_visible, $is_identified, $challenge_rating);
                            
                            if ($result && isset($result['count']) && $result['count'] > 0) {
                                // Rediriger vers manage_npcs.php avec un message de succès
                                header('Location: manage_npcs.php?success=monsters_created&count=' . $result['count']);
                                exit();
                            } else {
                                // Messages d'erreur plus spécifiques
                                if ($result === false) {
                                    $error_message = "Aucun monstre trouvé avec les critères sélectionnés (type: " . ($monster_type ?: 'tous') . ", FP: " . ($challenge_rating ?: 'tous') . "). Veuillez essayer avec d'autres critères.";
                                } else {
                                    $error_message = "Erreur lors de la création des monstres. Consultez les logs pour plus de détails.";
                                }
                                error_log("Échec création monstres - result: " . print_r($result, true));
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors de la création des monstres : " . $e->getMessage();
            }
        }
    }
}

// Récupérer les mondes de l'utilisateur
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les types de monstres disponibles
    $stmt = $pdo->prepare("SELECT DISTINCT type FROM dnd_monsters WHERE type IS NOT NULL ORDER BY type");
    $stmt->execute();
    $monster_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $worlds = [];
    $monster_types = [];
    $error_message = "Erreur lors de la récupération des données.";
}

$page_title = "Création Automatique de Monstres";
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-magic me-2"></i>Création Automatique de Monstres
                            </h4>
                            <a href="manage_npcs.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success_message): ?>
                            
                            <?php if (empty($worlds)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucun monde créé</h5>
                                    <p class="text-muted">Vous devez d'abord créer un monde avant de pouvoir créer des monstres.</p>
                                    <a href="manage_worlds.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Créer un Monde
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Création automatique :</strong> Cette fonctionnalité génère des monstres aléatoires. 
                                    Vous pourrez ensuite les personnaliser individuellement.
                                </div>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="world_id" class="form-label">Monde *</label>
                                                <select class="form-select" id="world_id" name="world_id" required>
                                                    <option value="">Sélectionnez un monde</option>
                                                    <?php foreach ($worlds as $world): ?>
                                                        <option value="<?php echo $world['id']; ?>">
                                                            <?php echo htmlspecialchars($world['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="country_id" class="form-label">Pays *</label>
                                                <select class="form-select" id="country_id" name="country_id" required>
                                                    <option value="">Sélectionnez d'abord un monde</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="region_id" class="form-label">Région</label>
                                                <select class="form-select" id="region_id" name="region_id">
                                                    <option value="">Aucune région</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="place_id" class="form-label">Pièce *</label>
                                                <select class="form-select" id="place_id" name="place_id" required>
                                                    <option value="">Sélectionnez d'abord un pays</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="count" class="form-label">Nombre de monstres à créer *</label>
                                                <select class="form-select" id="count" name="count" required>
                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <div class="form-text">Maximum 10 monstres à la fois.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="monster_type" class="form-label">Type de monstre (optionnel)</label>
                                                <select class="form-select" id="monster_type" name="monster_type">
                                                    <option value="">Tous les types</option>
                                                    <?php foreach ($monster_types as $type): ?>
                                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                                            <?php echo htmlspecialchars($type); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Filtrer par type de monstre.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="challenge_rating" class="form-label">Facteur de Puissance (FP)</label>
                                                <select class="form-select" id="challenge_rating" name="challenge_rating">
                                                    <option value="">Tous les FP</option>
                                                    <option value="0">0</option>
                                                    <option value="1/8">1/8</option>
                                                    <option value="1/4">1/4</option>
                                                    <option value="1/2">1/2</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="9">9</option>
                                                    <option value="10">10</option>
                                                    <option value="11">11</option>
                                                    <option value="12">12</option>
                                                    <option value="13">13</option>
                                                    <option value="14">14</option>
                                                    <option value="15">15</option>
                                                    <option value="16">16</option>
                                                    <option value="17">17</option>
                                                    <option value="18">18</option>
                                                    <option value="19">19</option>
                                                    <option value="20">20</option>
                                                    <option value="21">21</option>
                                                    <option value="22">22</option>
                                                    <option value="23">23</option>
                                                    <option value="24">24</option>
                                                    <option value="25">25</option>
                                                    <option value="26">26</option>
                                                    <option value="27">27</option>
                                                    <option value="28">28</option>
                                                    <option value="29">29</option>
                                                    <option value="30">30</option>
                                                </select>
                                                <div class="form-text">Filtrer par Facteur de Puissance spécifique.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
                                                    <label class="form-check-label" for="is_visible">
                                                        Visible par les joueurs
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_identified" name="is_identified" checked>
                                                    <label class="form-check-label" for="is_identified">
                                                        Identifié par les joueurs
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="manage_npcs.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-arrow-left me-2"></i>Retour
                                        </a>
                                        <button type="submit" name="create_monsters" class="btn btn-primary">
                                            <i class="fas fa-magic me-2"></i>Créer les Monstres
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const worldSelect = document.getElementById('world_id');
            const countrySelect = document.getElementById('country_id');
            const regionSelect = document.getElementById('region_id');
            const placeSelect = document.getElementById('place_id');
            
            let countriesData = [];
            let regionsData = [];
            let placesData = [];
            
            function updateCountries(worldId) {
                countrySelect.innerHTML = '<option value="">Sélectionnez un pays</option>';
                regionSelect.innerHTML = '<option value="">Aucune région</option>';
                placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                
                if (worldId) {
                    fetch(`get_countries_by_world.php?world_id=${worldId}`)
                        .then(response => response.json())
                        .then(data => {
                            countriesData = data;
                            data.forEach(country => {
                                const option = document.createElement('option');
                                option.value = country.id;
                                option.textContent = country.name;
                                countrySelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Erreur lors du chargement des pays:', error));
                }
            }
            
            function updateRegions(countryId) {
                regionSelect.innerHTML = '<option value="">Aucune région</option>';
                placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                
                if (countryId) {
                    fetch(`get_regions_by_country.php?country_id=${countryId}`)
                        .then(response => response.json())
                        .then(data => {
                            regionsData = data;
                            data.forEach(region => {
                                const option = document.createElement('option');
                                option.value = region.id;
                                option.textContent = region.name;
                                regionSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Erreur lors du chargement des régions:', error));
                }
            }
            
            function updatePlaces(countryId, regionId) {
                placeSelect.innerHTML = '<option value="">Sélectionnez une pièce</option>';
                
                if (countryId) {
                    fetch(`get_places_by_region.php?region_id=${regionId}&country_id=${countryId}`)
                        .then(response => response.json())
                        .then(data => {
                            placesData = data;
                            data.forEach(place => {
                                const option = document.createElement('option');
                                option.value = place.id;
                                option.textContent = place.title;
                                placeSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Erreur lors du chargement des pièces:', error));
                }
            }
            
            worldSelect.addEventListener('change', function() {
                updateCountries(this.value);
            });
            
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





