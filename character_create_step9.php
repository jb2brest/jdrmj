<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/capabilities_functions.php';

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
if (!$sessionData || $sessionData['step'] < 8) {
    header('Location: characters.php');
    exit;
}

$data = $sessionData['data'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_equipment = $_POST['class_equipment'] ?? [];
    $class_weapon_choices = $_POST['class_weapon_choices'] ?? [];
    $background_equipment = $_POST['background_equipment'] ?? [];
    $background_weapon_choices = $_POST['background_weapon_choices'] ?? [];
    
    // Générer l'équipement final
    $finalEquipment = '';
    $totalGold = 0;
    
    // Équipement de classe
    if (!empty($class_equipment)) {
        $classEquipmentData = generateFinalEquipment($data['class_id'], $class_equipment, $data['background_id'], $class_weapon_choices);
        $finalEquipment .= $classEquipmentData['equipment'];
        $totalGold += $classEquipmentData['gold'];
    }
    
    // Équipement d'historique
    if (!empty($background_equipment)) {
        $backgroundEquipmentData = generateFinalEquipment($data['class_id'], $background_equipment, $data['background_id'], $background_weapon_choices);
        $finalEquipment .= "\n" . $backgroundEquipmentData['equipment'];
        $totalGold += $backgroundEquipmentData['gold'];
    }
    
    // Sauvegarder les données
    $stepData = [
        'starting_equipment' => $finalEquipment,
        'money_gold' => $totalGold, // Utiliser 'money_gold' pour correspondre à finalizeCharacterCreation
        'class_equipment_choices' => $class_equipment,
        'class_weapon_choices' => $class_weapon_choices,
        'background_equipment_choices' => $background_equipment,
        'background_weapon_choices' => $background_weapon_choices
    ];
    
    if (saveCharacterCreationStep($user_id, $session_id, 9, $stepData)) {
        // Finaliser la création du personnage
        $characterId = finalizeCharacterCreation($user_id, $session_id);
        
        if ($characterId) {
            // Ajouter l'équipement de départ au personnage
            if (!empty($finalEquipment)) {
                addStartingEquipmentToCharacter($characterId, ['equipment' => $finalEquipment]);
            }
            
            // Marquer le personnage comme équipé et verrouiller les modifications
            $stmt = $pdo->prepare("UPDATE characters SET is_equipped = 1, equipment_locked = 1, character_locked = 1 WHERE id = ?");
            $stmt->execute([$characterId]);
            
            // Rediriger vers la fiche du personnage avec un message de succès
            header("Location: view_character.php?id=$characterId&created=1");
            exit;
        } else {
            $error_message = "Erreur lors de la création du personnage. Vérifiez que toutes les données sont correctement sauvegardées.";
        }
    } else {
        $error_message = "Erreur lors de la sauvegarde des données.";
    }
}

// Récupérer les informations de la race, classe et historique
$selectedRaceId = $data['race_id'] ?? null;
$selectedClassId = $data['class_id'] ?? null;
$selectedBackgroundId = $data['background_id'] ?? null;

$raceInfo = null;
$classInfo = null;
$backgroundInfo = null;

if ($selectedRaceId) {
    $stmt = $pdo->prepare("SELECT name FROM races WHERE id = ?");
    $stmt->execute([$selectedRaceId]);
    $raceInfo = $stmt->fetch();
}

if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT name, starting_equipment FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $classInfo = $stmt->fetch();
}

if ($selectedBackgroundId) {
    $stmt = $pdo->prepare("SELECT name, equipment FROM backgrounds WHERE id = ?");
    $stmt->execute([$selectedBackgroundId]);
    $backgroundInfo = $stmt->fetch();
}

// Parser l'équipement de classe
$parsedClassEquipment = [];
if ($classInfo && $classInfo['starting_equipment']) {
    $parsedClassEquipment = parseStartingEquipment($classInfo['starting_equipment']);
}

// Parser l'équipement d'historique
$parsedBackgroundEquipment = [];
if ($backgroundInfo && $backgroundInfo['equipment']) {
    $parsedBackgroundEquipment = parseStartingEquipment($backgroundInfo['equipment']);
}

// Debug: Afficher les informations pour diagnostiquer
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Selected Class ID: " . ($selectedClassId ?? 'NULL') . "\n";
    echo "Selected Background ID: " . ($selectedBackgroundId ?? 'NULL') . "\n";
    echo "Class Info: " . print_r($classInfo, true) . "\n";
    echo "Background Info: " . print_r($backgroundInfo, true) . "\n";
    echo "Parsed Class Equipment: " . print_r($parsedClassEquipment, true) . "\n";
    echo "Parsed Background Equipment: " . print_r($parsedBackgroundEquipment, true) . "\n";
    echo "</pre>";
}

include 'includes/layout.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- En-tête de l'étape -->
            <div class="text-center mb-4">
                <h2><i class="fas fa-shopping-bag me-2"></i>Étape 9 : Équipement de Départ</h2>
                <p class="text-muted">Choisissez l'équipement de départ de votre personnage</p>
                
                <!-- Progression -->
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <small class="text-muted">Étape 9 sur 9 - Finalisation</small>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="step9Form">
                <div class="row">
                    <!-- Équipement de classe -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-shield-alt me-2"></i>Équipement de classe</h5>
                                <small class="text-muted"><?php echo htmlspecialchars($classInfo['name'] ?? 'Classe non sélectionnée'); ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($parsedClassEquipment)): ?>
                                    <?php foreach ($parsedClassEquipment as $index => $choice): ?>
                                        <div class="equipment-choice mb-3" data-choice="<?php echo $index; ?>">
                                            <?php if (isset($choice['fixed'])): ?>
                                                <!-- Équipement fixe -->
                                                <h6><i class="fas fa-check-circle text-success me-2"></i><?php echo htmlspecialchars($choice['fixed']); ?></h6>
                                            <?php else: ?>
                                                <!-- Choix d'équipement -->
                                                <h6>Choisissez une option :</h6>
                                                
                                                <?php if (is_array($choice) && !empty($choice)): ?>
                                                <div class="equipment-options">
                                                    <?php foreach ($choice as $choiceKey => $choiceValue): ?>
                                                        <div class="equipment-option" data-choice="<?php echo $index; ?>" data-option="<?php echo $choiceKey; ?>">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="class_equipment[<?php echo $index; ?>]" value="<?php echo $choiceKey; ?>" id="class_equipment_<?php echo $index; ?>_<?php echo $choiceKey; ?>">
                                                                <label class="form-check-label" for="class_equipment_<?php echo $index; ?>_<?php echo $choiceKey; ?>">
                                                                    <?php if (is_array($choiceValue) && isset($choiceValue['type'])): ?>
                                                                        <?php if ($choiceValue['type'] === 'weapon_choice'): ?>
                                                                            <strong><?php echo htmlspecialchars($choiceValue['description']); ?></strong>
                                                                            <div class="mt-2">
                                                                                <select class="form-select form-select-sm" name="class_weapon_choices[<?php echo $index; ?>][<?php echo $choiceKey; ?>]">
                                                                                    <option value="">Sélectionnez une arme</option>
                                                                                    <?php foreach ($choiceValue['options'] as $weapon): ?>
                                                                                        <option value="<?php echo htmlspecialchars($weapon['name']); ?>">
                                                                                            <?php echo htmlspecialchars($weapon['name']); ?> (<?php echo htmlspecialchars($weapon['type']); ?>)
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        <?php elseif ($choiceValue['type'] === 'pack'): ?>
                                                                            <strong><?php echo htmlspecialchars($choiceValue['description']); ?></strong>
                                                                            <div class="mt-2">
                                                                                <small class="text-muted">
                                                                                    <strong>Contenu :</strong><br>
                                                                                    <?php echo implode(', ', $choiceValue['contents']); ?>
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <?php echo htmlspecialchars($choiceValue); ?>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Aucun équipement de classe disponible.</strong>
                                        <?php if (!$classInfo): ?>
                                            <br>Classe non sélectionnée (ID: <?php echo $selectedClassId ?? 'NULL'; ?>)
                                        <?php elseif (!$classInfo['starting_equipment']): ?>
                                            <br>Cette classe n'a pas d'équipement de départ défini.
                                        <?php else: ?>
                                            <br>Erreur lors du parsing de l'équipement.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Équipement d'historique -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-backpack me-2"></i>Équipement d'historique</h5>
                                <small class="text-muted"><?php echo htmlspecialchars($backgroundInfo['name'] ?? 'Historique non sélectionné'); ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($parsedBackgroundEquipment)): ?>
                                    <?php foreach ($parsedBackgroundEquipment as $index => $choice): ?>
                                        <div class="equipment-choice mb-3" data-choice="<?php echo $index; ?>">
                                            <?php if (isset($choice['fixed'])): ?>
                                                <!-- Équipement fixe -->
                                                <h6><i class="fas fa-check-circle text-success me-2"></i><?php echo htmlspecialchars($choice['fixed']); ?></h6>
                                            <?php else: ?>
                                                <!-- Choix d'équipement -->
                                                <h6>Choisissez une option :</h6>
                                                
                                                <?php if (is_array($choice) && !empty($choice)): ?>
                                                <div class="equipment-options">
                                                    <?php foreach ($choice as $choiceKey => $choiceValue): ?>
                                                        <div class="equipment-option" data-choice="<?php echo $index; ?>" data-option="<?php echo $choiceKey; ?>">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="background_equipment[<?php echo $index; ?>]" value="<?php echo $choiceKey; ?>" id="background_equipment_<?php echo $index; ?>_<?php echo $choiceKey; ?>">
                                                                <label class="form-check-label" for="background_equipment_<?php echo $index; ?>_<?php echo $choiceKey; ?>">
                                                                    <?php if (is_array($choiceValue) && isset($choiceValue['type'])): ?>
                                                                        <?php if ($choiceValue['type'] === 'weapon_choice'): ?>
                                                                            <strong><?php echo htmlspecialchars($choiceValue['description']); ?></strong>
                                                                            <div class="mt-2">
                                                                                <select class="form-select form-select-sm" name="background_weapon_choices[<?php echo $index; ?>][<?php echo $choiceKey; ?>]">
                                                                                    <option value="">Sélectionnez une arme</option>
                                                                                    <?php foreach ($choiceValue['options'] as $weapon): ?>
                                                                                        <option value="<?php echo htmlspecialchars($weapon['name']); ?>">
                                                                                            <?php echo htmlspecialchars($weapon['name']); ?> (<?php echo htmlspecialchars($weapon['type']); ?>)
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        <?php elseif ($choiceValue['type'] === 'pack'): ?>
                                                                            <strong><?php echo htmlspecialchars($choiceValue['description']); ?></strong>
                                                                            <div class="mt-2">
                                                                                <small class="text-muted">
                                                                                    <strong>Contenu :</strong><br>
                                                                                    <?php echo implode(', ', $choiceValue['contents']); ?>
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <?php echo htmlspecialchars($choiceValue); ?>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Aucun équipement d'historique disponible.</strong>
                                        <?php if (!$backgroundInfo): ?>
                                            <br>Historique non sélectionné (ID: <?php echo $selectedBackgroundId ?? 'NULL'; ?>)
                                        <?php elseif (!$backgroundInfo['equipment']): ?>
                                            <br>Cet historique n'a pas d'équipement défini.
                                        <?php else: ?>
                                            <br>Erreur lors du parsing de l'équipement.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
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
                            <div class="col-md-4">
                                <h6>Informations de base</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nom :</strong> <?php echo htmlspecialchars($data['name'] ?? 'Non défini'); ?></li>
                                    <li><strong>Race :</strong> <?php echo htmlspecialchars($raceInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Classe :</strong> <?php echo htmlspecialchars($classInfo['name'] ?? 'Non sélectionnée'); ?></li>
                                    <li><strong>Historique :</strong> <?php echo htmlspecialchars($backgroundInfo['name'] ?? 'Non sélectionné'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <h6>Autres informations</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Alignement :</strong> <?php echo htmlspecialchars($data['alignment'] ?? 'Non sélectionné'); ?></li>
                                    <li><strong>Âge :</strong> <?php echo htmlspecialchars($data['age'] ?? 'Non défini'); ?></li>
                                    <li><strong>Taille :</strong> <?php echo htmlspecialchars($data['height'] ?? 'Non définie'); ?></li>
                                    <li><strong>Poids :</strong> <?php echo htmlspecialchars($data['weight'] ?? 'Non défini'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="d-flex justify-content-between">
                    <a href="character_create_step8.php?session_id=<?php echo htmlspecialchars($session_id); ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Étape précédente
                    </a>
                    
                    <button type="submit" class="btn btn-success btn-lg" id="createCharacterBtn">
                        <i class="fas fa-user-plus me-2"></i>Créer le personnage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des sélecteurs d'armes
    function toggleWeaponSelectors() {
        const weaponSelectors = document.querySelectorAll('select[name*="weapon_choices"]');
        weaponSelectors.forEach(selector => {
            const parentOption = selector.closest('.equipment-option');
            const radioInput = parentOption.querySelector('input[type="radio"]');
            
            if (radioInput && radioInput.checked) {
                selector.style.display = 'block';
                selector.required = true;
            } else {
                selector.style.display = 'none';
                selector.required = false;
                selector.value = '';
            }
        });
    }
    
    // Event listeners pour les radio buttons
    const radioInputs = document.querySelectorAll('input[type="radio"]');
    radioInputs.forEach(input => {
        input.addEventListener('change', toggleWeaponSelectors);
    });
    
    // Initialisation
    toggleWeaponSelectors();
    
    // Validation du formulaire
    const form = document.getElementById('step9Form');
    const createBtn = document.getElementById('createCharacterBtn');
    
    form.addEventListener('submit', function(e) {
        // Vérifier que tous les choix requis sont faits
        const requiredChoices = document.querySelectorAll('.equipment-choice');
        let allChoicesValid = true;
        
        requiredChoices.forEach(choice => {
            // Ignorer les équipements fixes (qui n'ont pas de radio buttons)
            const radioInputs = choice.querySelectorAll('input[type="radio"]');
            if (radioInputs.length === 0) {
                // C'est un équipement fixe, pas besoin de validation
                return;
            }
            
            const hasSelection = Array.from(radioInputs).some(input => input.checked);
            
            if (!hasSelection) {
                allChoicesValid = false;
                choice.style.border = '2px solid #dc3545';
            } else {
                choice.style.border = 'none';
            }
        });
        
        if (!allChoicesValid) {
            e.preventDefault();
            alert('Veuillez faire tous les choix d\'équipement requis.');
            return;
        }
        
        // Vérifier les sélecteurs d'armes
        const weaponSelectors = document.querySelectorAll('select[name*="weapon_choices"]');
        for (let selector of weaponSelectors) {
            if (selector.style.display !== 'none' && !selector.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une arme pour tous les choix d\'armes.');
                return;
            }
        }
        
        // Confirmation avant création
        if (!confirm('Êtes-vous sûr de vouloir créer ce personnage ? Cette action est définitive.')) {
            e.preventDefault();
            return;
        }
        
        // Désactiver le bouton pour éviter les double-clics
        createBtn.disabled = true;
        createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
    });
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

.equipment-choice {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: border-color 0.3s ease;
}

.equipment-choice:hover {
    border-color: #0d6efd;
}

.equipment-option {
    margin-bottom: 10px;
}

.form-check-label {
    cursor: pointer;
}

.form-select {
    margin-top: 8px;
}

.list-unstyled li {
    margin-bottom: 0.25rem;
}

.btn-success {
    background-color: #198754;
    border-color: #198754;
}

.btn-success:hover {
    background-color: #157347;
    border-color: #146c43;
}
</style>

