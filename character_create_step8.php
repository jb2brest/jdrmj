<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? null;

// Vérifier que la session existe
if (!$session_id) {
    header('Location: characters.php');
    exit;
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 7) {
    header('Location: characters.php');
    exit;
}

$data = $sessionData['data'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $height = trim($_POST['height'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $eyes = trim($_POST['eyes'] ?? '');
    $skin = trim($_POST['skin'] ?? '');
    $hair = trim($_POST['hair'] ?? '');
    $backstory = trim($_POST['backstory'] ?? '');
    $personality_traits = trim($_POST['personality_traits'] ?? '');
    $ideals = trim($_POST['ideals'] ?? '');
    $bonds = trim($_POST['bonds'] ?? '');
    $flaws = trim($_POST['flaws'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Le nom du personnage est obligatoire.";
    }
    
    if (empty($backstory)) {
        $errors[] = "L'histoire du personnage est obligatoire.";
    }
    
    if (empty($errors)) {
        // Sauvegarder les données
        $stepData = [
            'name' => $name,
            'age' => $age,
            'height' => $height,
            'weight' => $weight,
            'eyes' => $eyes,
            'skin' => $skin,
            'hair' => $hair,
            'backstory' => $backstory,
            'personality_traits' => $personality_traits,
            'ideals' => $ideals,
            'bonds' => $bonds,
            'flaws' => $flaws
        ];
        
        if (saveCharacterCreationStep($user_id, $session_id, 8, $stepData)) {
            header("Location: character_create_step9.php?session_id=$session_id");
            exit;
        } else {
            $error_message = "Erreur lors de la sauvegarde des données.";
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Récupérer les informations de la race et classe pour les suggestions
$selectedRaceId = $data['race_id'] ?? null;
$selectedClassId = $data['class_id'] ?? null;
$selectedBackgroundId = $data['background_id'] ?? null;

$raceInfo = null;
$classInfo = null;
$backgroundInfo = null;

// Données par défaut pour les suggestions de race
$raceSuggestions = [
    'Humain' => [
        'age_range' => '18-80 ans',
        'height_range' => '1,50m - 1,90m',
        'weight_range' => '50kg - 100kg'
    ],
    'Elfe' => [
        'age_range' => '100-750 ans',
        'height_range' => '1,50m - 1,80m',
        'weight_range' => '40kg - 80kg'
    ],
    'Nain' => [
        'age_range' => '50-350 ans',
        'height_range' => '1,20m - 1,50m',
        'weight_range' => '60kg - 120kg'
    ],
    'Halfelin' => [
        'age_range' => '20-150 ans',
        'height_range' => '0,90m - 1,20m',
        'weight_range' => '20kg - 40kg'
    ],
    'Demi-elfe' => [
        'age_range' => '20-180 ans',
        'height_range' => '1,50m - 1,80m',
        'weight_range' => '45kg - 85kg'
    ],
    'Demi-orc' => [
        'age_range' => '14-75 ans',
        'height_range' => '1,70m - 2,00m',
        'weight_range' => '70kg - 120kg'
    ],
    'Gnome' => [
        'age_range' => '40-500 ans',
        'height_range' => '0,90m - 1,20m',
        'weight_range' => '20kg - 40kg'
    ],
    'Tieffelin' => [
        'age_range' => '18-100 ans',
        'height_range' => '1,50m - 1,80m',
        'weight_range' => '50kg - 90kg'
    ]
];

if ($selectedRaceId) {
    $stmt = $pdo->prepare("SELECT name FROM races WHERE id = ?");
    $stmt->execute([$selectedRaceId]);
    $raceInfo = $stmt->fetch();
    
    // Ajouter les suggestions si la race est connue
    if ($raceInfo && isset($raceSuggestions[$raceInfo['name']])) {
        $raceInfo = array_merge($raceInfo, $raceSuggestions[$raceInfo['name']]);
    }
}

if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $classInfo = $stmt->fetch();
}

if ($selectedBackgroundId) {
    $stmt = $pdo->prepare("SELECT name FROM backgrounds WHERE id = ?");
    $stmt->execute([$selectedBackgroundId]);
    $backgroundInfo = $stmt->fetch();
}

include 'includes/layout.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- En-tête de l'étape -->
            <div class="text-center mb-4">
                <h2><i class="fas fa-book me-2"></i>Étape 8 : Histoire du Personnage</h2>
                <p class="text-muted">Définissez l'apparence physique et l'histoire de votre personnage</p>
                
                <!-- Progression -->
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 88.9%"></div>
                </div>
                <small class="text-muted">Étape 8 sur 9</small>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="step8Form">
                <div class="row">
                    <!-- Informations de base -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-user me-2"></i>Informations de base</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du personnage <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="age" class="form-label">Âge</label>
                                    <input type="number" class="form-control" id="age" name="age" 
                                           value="<?php echo htmlspecialchars($data['age'] ?? ''); ?>" min="1" max="1000">
                                    <?php if ($raceInfo && $raceInfo['age_range']): ?>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Âge typique pour un <?php echo htmlspecialchars($raceInfo['name']); ?> : <?php echo htmlspecialchars($raceInfo['age_range']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="height" class="form-label">Taille</label>
                                    <input type="text" class="form-control" id="height" name="height" 
                                           value="<?php echo htmlspecialchars($data['height'] ?? ''); ?>" 
                                           placeholder="ex: 1m80, 5'10&quot;">
                                    <?php if ($raceInfo && $raceInfo['height_range']): ?>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Taille typique pour un <?php echo htmlspecialchars($raceInfo['name']); ?> : <?php echo htmlspecialchars($raceInfo['height_range']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Poids</label>
                                    <input type="text" class="form-control" id="weight" name="weight" 
                                           value="<?php echo htmlspecialchars($data['weight'] ?? ''); ?>" 
                                           placeholder="ex: 70kg, 150lbs">
                                    <?php if ($raceInfo && $raceInfo['weight_range']): ?>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Poids typique pour un <?php echo htmlspecialchars($raceInfo['name']); ?> : <?php echo htmlspecialchars($raceInfo['weight_range']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Apparence physique -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-eye me-2"></i>Apparence physique</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="eyes" class="form-label">Couleur des yeux</label>
                                    <input type="text" class="form-control" id="eyes" name="eyes" 
                                           value="<?php echo htmlspecialchars($data['eyes'] ?? ''); ?>" 
                                           placeholder="ex: Bleus, Verts, Marron">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="skin" class="form-label">Couleur de peau</label>
                                    <input type="text" class="form-control" id="skin" name="skin" 
                                           value="<?php echo htmlspecialchars($data['skin'] ?? ''); ?>" 
                                           placeholder="ex: Pâle, Bronzée, Sombre">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hair" class="form-label">Cheveux</label>
                                    <input type="text" class="form-control" id="hair" name="hair" 
                                           value="<?php echo htmlspecialchars($data['hair'] ?? ''); ?>" 
                                           placeholder="ex: Blonds courts, Bruns longs, Roux bouclés">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histoire du personnage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-scroll me-2"></i>Histoire du personnage</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Racontez l'histoire de votre personnage. D'où vient-il ? Qu'a-t-il fait avant de devenir aventurier ? 
                            Quels événements l'ont mené à cette vie ?
                        </p>
                        
                        <div class="mb-3">
                            <label for="backstory" class="form-label">Histoire <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="backstory" name="backstory" rows="8" 
                                      placeholder="Décrivez l'histoire de votre personnage..." required><?php echo htmlspecialchars($data['backstory'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong>Conseils :</strong> Parlez de son enfance, de sa famille, de ses motivations, de ses rêves, 
                                de ce qui l'a poussé à devenir aventurier.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Traits de personnalité -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-heart me-2"></i>Traits de personnalité</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Définissez les traits de personnalité de votre personnage. Ces éléments l'aideront à prendre des décisions 
                            et à interagir avec les autres.
                        </p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="personality_traits" class="form-label">Traits de personnalité</label>
                                    <textarea class="form-control" id="personality_traits" name="personality_traits" rows="4" 
                                              placeholder="Décrivez les traits de personnalité de votre personnage..."><?php echo htmlspecialchars($data['personality_traits'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Comment votre personnage se comporte-t-il ? Qu'est-ce qui le caractérise ?
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ideals" class="form-label">Idéaux</label>
                                    <textarea class="form-control" id="ideals" name="ideals" rows="4" 
                                              placeholder="Quels sont les idéaux qui guident votre personnage ?"><?php echo htmlspecialchars($data['ideals'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-star me-1"></i>
                                        Quelles valeurs sont importantes pour votre personnage ?
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bonds" class="form-label">Liens</label>
                                    <textarea class="form-control" id="bonds" name="bonds" rows="4" 
                                              placeholder="Quels sont les liens de votre personnage ?"><?php echo htmlspecialchars($data['bonds'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-link me-1"></i>
                                        À quoi ou à qui votre personnage est-il attaché ?
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="flaws" class="form-label">Défauts</label>
                                    <textarea class="form-control" id="flaws" name="flaws" rows="4" 
                                              placeholder="Quels sont les défauts de votre personnage ?"><?php echo htmlspecialchars($data['flaws'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Quelles sont les faiblesses ou les vices de votre personnage ?
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé du personnage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list me-2"></i>Résumé du personnage</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informations de base</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Race :</strong> <?php echo htmlspecialchars($raceInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Classe :</strong> <?php echo htmlspecialchars($classInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Historique :</strong> <?php echo htmlspecialchars($backgroundInfo['name'] ?? 'Non sélectionné'); ?></li>
                                    <li><strong>Alignement :</strong> <?php echo htmlspecialchars($data['alignment'] ?? 'Non sélectionné'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Caractéristiques</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Force :</strong> <?php echo ($data['strength'] ?? 10) + ($data['racial_strength_bonus'] ?? 0); ?></li>
                                    <li><strong>Dextérité :</strong> <?php echo ($data['dexterity'] ?? 10) + ($data['racial_dexterity_bonus'] ?? 0); ?></li>
                                    <li><strong>Constitution :</strong> <?php echo ($data['constitution'] ?? 10) + ($data['racial_constitution_bonus'] ?? 0); ?></li>
                                    <li><strong>Intelligence :</strong> <?php echo ($data['intelligence'] ?? 10) + ($data['racial_intelligence_bonus'] ?? 0); ?></li>
                                    <li><strong>Sagesse :</strong> <?php echo ($data['wisdom'] ?? 10) + ($data['racial_wisdom_bonus'] ?? 0); ?></li>
                                    <li><strong>Charisme :</strong> <?php echo ($data['charisma'] ?? 10) + ($data['racial_charisma_bonus'] ?? 0); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="d-flex justify-content-between">
                    <a href="character_create_step7.php?session_id=<?php echo htmlspecialchars($session_id); ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Étape précédente
                    </a>
                    
                    <button type="submit" class="btn btn-primary" id="continueBtn">
                        Continuer <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('step8Form');
    const nameInput = document.getElementById('name');
    const backstoryInput = document.getElementById('backstory');
    const continueBtn = document.getElementById('continueBtn');
    
    // Validation en temps réel
    function validateForm() {
        const nameValid = nameInput.value.trim().length > 0;
        const backstoryValid = backstoryInput.value.trim().length > 0;
        
        continueBtn.disabled = !(nameValid && backstoryValid);
        
        if (nameValid && backstoryValid) {
            continueBtn.classList.remove('btn-secondary');
            continueBtn.classList.add('btn-primary');
        } else {
            continueBtn.classList.remove('btn-primary');
            continueBtn.classList.add('btn-secondary');
        }
    }
    
    // Event listeners
    nameInput.addEventListener('input', validateForm);
    backstoryInput.addEventListener('input', validateForm);
    
    // Validation initiale
    validateForm();
    
    // Validation du formulaire
    form.addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        const backstory = backstoryInput.value.trim();
        
        if (!name || !backstory) {
            e.preventDefault();
            alert('Veuillez remplir le nom et l\'histoire du personnage.');
            return;
        }
        
        if (backstory.length < 50) {
            e.preventDefault();
            alert('L\'histoire du personnage doit contenir au moins 50 caractères.');
            return;
        }
    });
    
    // Compteur de caractères pour l'histoire
    const backstoryCounter = document.createElement('div');
    backstoryCounter.className = 'form-text text-end';
    backstoryCounter.innerHTML = '<span id="charCount">0</span> caractères';
    backstoryInput.parentNode.appendChild(backstoryCounter);
    
    const charCount = document.getElementById('charCount');
    
    backstoryInput.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count < 50) {
            charCount.style.color = '#dc3545';
        } else {
            charCount.style.color = '#198754';
        }
    });
    
    // Mise à jour initiale du compteur
    charCount.textContent = backstoryInput.value.length;
    if (backstoryInput.value.length < 50) {
        charCount.style.color = '#dc3545';
    } else {
        charCount.style.color = '#198754';
    }
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.6s ease;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-text {
    font-size: 0.875rem;
}

.list-unstyled li {
    margin-bottom: 0.25rem;
}

.btn:disabled {
    cursor: not-allowed;
}
</style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
