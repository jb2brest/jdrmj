<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'npc_functions.php';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_npc'])) {
    // Vérification que le formulaire est correctement soumis
    if (empty($_POST['world_id']) || $_POST['world_id'] === '') {
        $error_message = "Veuillez sélectionner un monde.";
    } else {
        $world_id = (int)($_POST['world_id'] ?? 0);
        $country_id = (int)($_POST['country_id'] ?? 0);
        $place_id = (int)($_POST['place_id'] ?? 0);
        $region_id = !empty($_POST['region_id']) ? (int)$_POST['region_id'] : null;
        $race_id = (int)($_POST['race'] ?? 0);
        $class_id = (int)($_POST['class'] ?? 0);
        $level = (int)($_POST['level'] ?? 1);
        $custom_name = $_POST['custom_name'] ?? '';
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $is_identified = isset($_POST['is_identified']) ? 1 : 0;
    
    // Vérification des données POST transmises
    
        // Vérification des données POST
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
        } elseif ($race_id <= 0) {
            $error_message = "Veuillez sélectionner une race.";
        } elseif ($level < 1 || $level > 20) {
            $error_message = "Le niveau doit être entre 1 et 20.";
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
                        // Créer le PNJ automatiquement
                        $npc = createAutomaticNPC($race_id, $class_id, $level, $user_id, $custom_name, $place_id, $is_visible, $is_identified, $world_id, $country_id);
                        
                        if ($npc) {
                            // Rediriger vers manage_npcs.php avec un message de succès
                            header('Location: manage_npcs.php?success=npc_created&name=' . urlencode($npc['name']));
                            exit();
                        } else {
                            $error_message = "Erreur lors de la création du PNJ.";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de la création du PNJ : " . $e->getMessage();
        }
        }
    }
}


// Récupérer les données pour le formulaire
$worlds = [];
$races = [];
$classes = [];

try {
    $pdo = getPdo();
    
    // Récupérer les mondes de l'utilisateur
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les races
    $stmt = $pdo->query("SELECT id, name FROM races ORDER BY name");
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les classes
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création automatique de PNJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-ninja me-2"></i>Création automatique de PNJ</h3>
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
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="world_id" class="form-label">Monde *</label>
                                        <select class="form-select" id="world_id" name="world_id" required>
                                            <option value="">Sélectionnez un monde</option>
                                            <?php foreach ($worlds as $world): ?>
                                                <option value="<?php echo $world['id']; ?>"><?php echo htmlspecialchars($world['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="country_id" class="form-label">Pays *</label>
                                        <select class="form-select" id="country_id" name="country_id" required>
                                            <option value="">Sélectionnez un pays</option>
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
                                            <option value="">Sélectionnez une pièce</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="race" class="form-label">Race *</label>
                                        <select class="form-select" id="race" name="race" required>
                                            <option value="">Sélectionnez une race</option>
                                            <?php foreach ($races as $race): ?>
                                                <option value="<?php echo $race['id']; ?>"><?php echo htmlspecialchars($race['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="class" class="form-label">Classe</label>
                                        <select class="form-select" id="class" name="class">
                                            <option value="">Sélectionnez une classe</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="level" class="form-label">Niveau *</label>
                                        <select class="form-select" id="level" name="level" required>
                                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="custom_name" class="form-label">Nom personnalisé</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="custom_name" name="custom_name" placeholder="Laissez vide pour un nom aléatoire">
                                            <button type="button" class="btn btn-outline-primary" id="generateNameBtn" title="Générer des suggestions de noms">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                        </div>
                                        <div id="nameSuggestions" class="mt-2" style="display: none;">
                                            <small class="text-muted d-block mb-1">Suggestions :</small>
                                            <div id="suggestionsList" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
                                        <label class="form-check-label" for="is_visible">
                                            Visible par les joueurs
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_identified" name="is_identified" checked>
                                        <label class="form-check-label" for="is_identified">
                                            Identifié par les joueurs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_npcs.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                                <button type="submit" name="create_npc" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Créer le PNJ
                                </button>
                            </div>
                        </form>
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
            
            // Gestion de la génération de noms
            const generateNameBtn = document.getElementById('generateNameBtn');
            const nameInput = document.getElementById('custom_name');
            const suggestionsDiv = document.getElementById('nameSuggestions');
            const suggestionsList = document.getElementById('suggestionsList');
            const raceSelect = document.getElementById('race');
            const classSelect = document.getElementById('class');
            
            if (generateNameBtn) {
                generateNameBtn.addEventListener('click', function() {
                    const raceId = raceSelect.value;
                    const classId = classSelect.value;
                    
                    if (!raceId || !classId) {
                        alert('Veuillez sélectionner une race et une classe');
                        return;
                    }
                    
                    // Afficher un indicateur de chargement
                    generateNameBtn.disabled = true;
                    generateNameBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    suggestionsList.innerHTML = '<small class="text-muted">Génération...</small>';
                    suggestionsDiv.style.display = 'block';
                    
                    // Appeler l'API
                    const formData = new FormData();
                    formData.append('race_id', raceId);
                    formData.append('class_id', classId);
                    formData.append('count', 5);
                    
                    fetch('api/generate_npc_name.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        generateNameBtn.disabled = false;
                        generateNameBtn.innerHTML = '<i class="fas fa-magic"></i>';
                        
                        if (data.success && data.suggestions && data.suggestions.length > 0) {
                            suggestionsList.innerHTML = '';
                            data.suggestions.forEach(function(suggestion) {
                                const badge = document.createElement('button');
                                badge.type = 'button';
                                badge.className = 'btn btn-sm btn-outline-primary';
                                badge.textContent = suggestion;
                                badge.style.marginBottom = '5px';
                                badge.addEventListener('click', function() {
                                    nameInput.value = suggestion;
                                });
                                suggestionsList.appendChild(badge);
                            });
                        } else {
                            suggestionsList.innerHTML = '<small class="text-danger">Aucune suggestion disponible</small>';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        generateNameBtn.disabled = false;
                        generateNameBtn.innerHTML = '<i class="fas fa-magic"></i>';
                        suggestionsList.innerHTML = '<small class="text-danger">Erreur lors de la génération</small>';
                    });
                });
            }
        });
    </script>
</body>
</html>
