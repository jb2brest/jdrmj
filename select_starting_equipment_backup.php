<?php
session_start();
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'includes/starting_equipment_functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les paramètres
$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : 0;

if (!$character_id) {
    header('Location: characters.php');
    exit();
}

// Récupérer le personnage avec la classe Character
$characterObject = Character::findById($character_id);

if (!$characterObject) {
    header('Location: characters.php');
    exit();
}

// Vérifier que le personnage appartient au joueur
if (!$characterObject->belongsToUser($user_id)) {
    header('Location: characters.php');
    exit();
}

// Convertir l'objet Character en tableau pour la compatibilité avec le code existant
$character = $characterObject->toArray();

// Récupérer l'objet Campaign si campaign_id est fourni
$campaign = null;
if ($campaign_id) {
    $campaign = Campaign::findById($campaign_id);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    // Vérifier que le personnage est dans la campagne
    if (!$campaign->isCharacterIn($characterObject)) {
        header('Location: campaigns.php');
        exit();
    }
}

// Vérifier si l'équipement de départ a déjà été choisi
$equipment_selected = $characterObject->hasStartingEquipment();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_equipment') {
    $starting_equipment = isset($_POST['starting_equipment']) ? $_POST['starting_equipment'] : [];
    $weapon_choices = isset($_POST['weapon_choice']) ? $_POST['weapon_choice'] : [];
    $background_equipment = isset($_POST['background_equipment']) ? $_POST['background_equipment'] : [];
    $background_weapon_choices = isset($_POST['background_weapon_choice']) ? $_POST['background_weapon_choice'] : [];
    
    // Debug: Afficher les données reçues
    error_log("DEBUG - starting_equipment: " . json_encode($starting_equipment));
    error_log("DEBUG - weapon_choices: " . json_encode($weapon_choices));
    error_log("DEBUG - background_equipment: " . json_encode($background_equipment));
    error_log("DEBUG - background_weapon_choices: " . json_encode($background_weapon_choices));
    
    // Générer l'équipement final avec les noms de classe et background
    $equipmentData = Character::generateFinalEquipment($character['class_id'], $starting_equipment, $character['background_id'], $weapon_choices);
    $finalEquipment = $equipmentData['equipment'];
    $backgroundGold = $equipmentData['gold'];
    
    // Ajouter l'équipement de l'historique
    if (!empty($background_equipment)) {
        $backgroundEquipmentData = Character::generateFinalEquipment($character['class_id'], $background_equipment, $character['background_id'], $background_weapon_choices);
        $finalEquipment .= "\n" . $backgroundEquipmentData['equipment'];
        $backgroundGold += $backgroundEquipmentData['gold'];
    }
    
    // Ajouter l'équipement de départ choisi par le joueur
    Character::addStartingEquipmentToCharacter($character_id, $equipmentData);
    
    // Synchroniser l'équipement de base avec la base de données
    Character::syncBaseEquipmentToCharacterEquipment($character_id);
    
    // Mettre à jour l'argent du personnage
    if ($backgroundGold > 0) {
        $characterObject->update(['gold' => $characterObject->gold + $backgroundGold]);
    }
    
    // Marquer le personnage comme équipé et verrouiller les modifications
    $characterObject->update([
        'is_equipped' => 1,
        'equipment_locked' => 1,
        'character_locked' => 1
    ]);
    
    // Rediriger vers la scène de jeu ou la fiche du personnage
    if ($campaign) {
        $campaignArray = $campaign->toArray();
        header("Location: view_scene_player.php?campaign_id=" . $campaignArray['id']);
    } else {
        header("Location: view_character.php?id=$character_id");
    }
    exit();
}

// Récupérer les choix d'équipement de départ avec le nouveau système
$classChoix = StartingEquipmentChoix::findByClassName($character['class_name']);
$backgroundChoix = [];
if ($character['background_name']) {
    $backgroundChoix = StartingEquipmentChoix::findByBackgroundName($character['background_name']);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélection d'équipement de départ - <?php echo htmlspecialchars($character['name']); ?><?php if ($campaign): ?><?php $campaignArray = $campaign->toArray(); ?> - <?php echo htmlspecialchars($campaignArray['title']); ?><?php endif; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .equipment-choice {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .equipment-choice.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .equipment-option {
            cursor: pointer;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: white;
            transition: all 0.2s;
        }
        .equipment-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .equipment-option.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .character-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="character-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-shield-alt me-3"></i>Sélection d'équipement de départ</h1>
                    <p class="mb-0">Choisissez l'équipement de départ pour votre personnage<?php if ($campaign): ?><?php $campaignArray = $campaign->toArray(); ?> dans la campagne "<?php echo htmlspecialchars($campaignArray['title']); ?>"<?php endif; ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <h3><?php echo htmlspecialchars($character['name']); ?></h3>
                    <p class="mb-0"><?php echo htmlspecialchars($character['race_name']); ?> <?php echo htmlspecialchars($character['class_name']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($equipment_selected): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                L'équipement de départ a déjà été choisi pour ce personnage.
                <?php if ($campaign): ?>
                    <?php $campaignArray = $campaign->toArray(); ?>
                    <a href="view_scene_player.php?campaign_id=<?php echo $campaignArray['id']; ?>" class="btn btn-primary ms-3">
                    <i class="fas fa-play me-2"></i>Rejoindre la partie
                </a>
                <?php else: ?>
                    <a href="view_character.php?id=<?php echo $character_id; ?>" class="btn btn-primary ms-3">
                        <i class="fas fa-user me-2"></i>Voir le personnage
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="POST" id="equipmentForm">
                <input type="hidden" name="action" value="select_equipment">
                
                <div class="row">
                    <div class="col-md-8">
                        <h3><i class="fas fa-sword me-2"></i>Équipement de classe - <?php echo htmlspecialchars($character['class_name']); ?></h3>
                        
                        <?php if (!empty($classChoix)): ?>
                            <?php foreach ($classChoix as $index => $choix): ?>
                                <div class="equipment-choice" data-index="<?php echo $index; ?>">
                                    <?php if ($choix->getNoChoix() == 0 && empty($choix->getOptionLetter())): ?>
                                        <!-- Équipement par défaut -->
                                        <h5><i class="fas fa-check-circle text-success me-2"></i><?php echo htmlspecialchars($choix->getFullDescription()); ?></h5>
                                        <p class="text-muted">Cet équipement est automatiquement attribué.</p>
                                        <?php if ($choix->hasOptions()): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Équipement :</strong><br>
                                                    <?php 
                                                    $itemNames = [];
                                                    foreach ($choix->getOptions() as $option) {
                                                        $quantity = $option->getNb();
                                                        $name = $option->getTypeLabel();
                                                        $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                    }
                                                    echo implode(', ', $itemNames);
                                                    ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Choix d'équipement -->
                                        <h5><?php echo htmlspecialchars($choix->getFullDescription()); ?></h5>
                                        <p class="text-muted">Choisissez une option :</p>
                                        <?php foreach ($choix->getOptions() as $option): ?>
                                            <div class="equipment-option" data-choice="<?php echo $option->getOptionLetter(); ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="starting_equipment[<?php echo $index; ?>]" value="<?php echo $option->getOptionLetter(); ?>" id="equipment_<?php echo $index; ?>_<?php echo $option->getOptionLetter(); ?>">
                                                    <label class="form-check-label" for="equipment_<?php echo $index; ?>_<?php echo $option->getOptionLetter(); ?>">
                                                        <strong>Option <?php echo $option->getOptionLetter(); ?> :</strong> <?php echo htmlspecialchars($option->getDescription()); ?>
                                                        <?php if (!empty($option->getItems())): ?>
                                                            <div class="mt-1">
                                                                    <small class="text-muted">
                                                                    <strong>Contenu :</strong>
                                                                    <?php 
                                                                    $itemNames = [];
                                                                    foreach ($option->getItems() as $item) {
                                                                        $quantity = $item['quantity'] ?? 1;
                                                                        $name = $item['name'] ?? $item['type'] ?? 'Item';
                                                                        $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                                    }
                                                                    echo implode(', ', $itemNames);
                                                                    ?>
                                                                    </small>
                                                                </div>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucun équipement de départ défini pour la classe <?php echo htmlspecialchars($character['class_name']); ?>.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($backgroundChoix)): ?>
                            <h3 class="mt-4"><i class="fas fa-backpack me-2"></i>Équipement d'historique - <?php echo htmlspecialchars($character['background_name']); ?></h3>
                            <?php foreach ($backgroundChoix as $index => $choix): ?>
                                <div class="equipment-choice" data-index="<?php echo $index; ?>">
                                    <?php if ($choix->getNoChoix() == 0 && empty($choix->getOptionLetter())): ?>
                                        <!-- Équipement par défaut -->
                                        <h5><i class="fas fa-check-circle text-success me-2"></i><?php echo htmlspecialchars($choix->getFullDescription()); ?></h5>
                                        <p class="text-muted">Cet équipement est automatiquement attribué.</p>
                                        <?php if ($choix->hasOptions()): ?>
                                                                    <div class="mt-2">
                                                                        <small class="text-muted">
                                                    <strong>Équipement :</strong><br>
                                                    <?php 
                                                    $itemNames = [];
                                                    foreach ($choix->getOptions() as $option) {
                                                        $quantity = $option->getNb();
                                                        $name = $option->getTypeLabel();
                                                        $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                    }
                                                    echo implode(', ', $itemNames);
                                                    ?>
                                                                        </small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                        <!-- Choix d'équipement -->
                                        <h5><?php echo htmlspecialchars($choix->getFullDescription()); ?></h5>
                                        <p class="text-muted">Choisissez une option :</p>
                                        <?php foreach ($choix->getOptions() as $option): ?>
                                            <div class="equipment-option" data-choice="<?php echo $option->getOptionLetter(); ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="background_equipment[<?php echo $index; ?>]" value="<?php echo $option->getOptionLetter(); ?>" id="background_<?php echo $index; ?>_<?php echo $option->getOptionLetter(); ?>">
                                                    <label class="form-check-label" for="background_<?php echo $index; ?>_<?php echo $option->getOptionLetter(); ?>">
                                                        <strong>Option <?php echo $option->getOptionLetter(); ?> :</strong> <?php echo htmlspecialchars($option->getDescription()); ?>
                                                        <?php if (!empty($option->getItems())): ?>
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    <strong>Contenu :</strong>
                                                                    <?php 
                                                                    $itemNames = [];
                                                                    foreach ($option->getItems() as $item) {
                                                                        $quantity = $item['quantity'] ?? 1;
                                                                        $name = $item['name'] ?? $item['type'] ?? 'Item';
                                                                        $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                                    }
                                                                    echo implode(', ', $itemNames);
                                                                    ?>
                                                                </small>
                                                            </div>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun équipement d'historique défini pour le background <?php echo htmlspecialchars($character['background_name']); ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user me-2"></i>Informations du personnage</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nom :</strong> <?php echo htmlspecialchars($character['name']); ?></p>
                                <p><strong>Race :</strong> <?php echo htmlspecialchars($character['race_name']); ?></p>
                                <p><strong>Classe :</strong> <?php echo htmlspecialchars($character['class_name']); ?></p>
                                <p><strong>Niveau :</strong> <?php echo $character['level']; ?></p>
                                <?php if ($character['background_name']): ?>
                                    <p><strong>Historique :</strong> <?php echo htmlspecialchars($character['background_name']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fas fa-coins me-2"></i>Argent de départ</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Argent actuel :</strong></p>
                                <ul class="list-unstyled">
                                    <li><?php echo $character['gold']; ?> PO (pièces d'or)</li>
                                    <li><?php echo $character['silver']; ?> PA (pièces d'argent)</li>
                                    <li><?php echo $character['copper']; ?> PC (pièces de cuivre)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check me-2"></i>Confirmer l'équipement et rejoindre la partie
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de la sélection d'équipement
        document.addEventListener('DOMContentLoaded', function() {
            const equipmentOptions = document.querySelectorAll('.equipment-option');
            
            equipmentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    const choiceContainer = this.closest('.equipment-choice');
                    
                    // Désélectionner toutes les options de ce choix
                    choiceContainer.querySelectorAll('.equipment-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Sélectionner l'option cliquée
                    this.classList.add('selected');
                    radio.checked = true;
                    
                    // Activer/désactiver les sélecteurs d'armes
                    toggleWeaponSelectors();
                });
            });
            
            // Gestion des sélecteurs d'armes
            function toggleWeaponSelectors() {
                const weaponSelects = document.querySelectorAll('select[name^="weapon_choice"], select[name^="background_weapon_choice"]');
                weaponSelects.forEach(select => {
                    const option = select.closest('.equipment-option');
                    const radio = option.querySelector('input[type="radio"]');
                    
                    if (radio && radio.checked) {
                        select.disabled = false;
                        select.required = true;
                        select.style.borderColor = '#dee2e6';
                    } else {
                        select.disabled = true;
                        select.required = false;
                        select.style.borderColor = '#dee2e6';
                        // Réinitialiser la valeur si l'option n'est pas sélectionnée
                        if (!radio.checked) {
                            select.value = '';
                        }
                    }
                });
            }
            
            // Initialiser l'état des sélecteurs
            toggleWeaponSelectors();
            
            // Validation du formulaire
            document.getElementById('equipmentForm').addEventListener('submit', function(e) {
                const requiredChoices = document.querySelectorAll('.equipment-choice:not([data-fixed])');
                let allSelected = true;
                let errorMessage = '';
                
                console.log('Validation - Nombre de choix requis:', requiredChoices.length);
                
                requiredChoices.forEach((choice, index) => {
                    const selected = choice.querySelector('input[type="radio"]:checked');
                    console.log(`Choix ${index}:`, selected ? selected.value : 'Aucun');
                    
                    if (!selected) {
                        allSelected = false;
                        choice.style.borderColor = '#dc3545';
                        errorMessage = 'Veuillez faire tous les choix d\'équipement requis.';
                    } else {
                        choice.style.borderColor = '#dee2e6';
                        
                        // Vérifier les sélecteurs d'armes seulement pour l'option sélectionnée
                        const selectedOption = choice.querySelector('.equipment-option.selected');
                        if (selectedOption) {
                            const weaponSelect = selectedOption.querySelector('select[name^="weapon_choice"]');
                            if (weaponSelect && !weaponSelect.disabled && !weaponSelect.value) {
                                allSelected = false;
                                weaponSelect.style.borderColor = '#dc3545';
                                errorMessage = 'Veuillez sélectionner une arme pour le choix d\'arme sélectionné.';
                                console.log('Erreur: Sélecteur d\'arme vide pour l\'option sélectionnée');
                            } else if (weaponSelect) {
                                weaponSelect.style.borderColor = '#dee2e6';
                                console.log('Sélecteur d\'arme OK:', weaponSelect.value);
                            }
                            // Si pas de sélecteur d'arme, c'est normal (ex: épée courte)
                            console.log('Option sélectionnée OK:', selectedOption.querySelector('input[type="radio"]').value);
                        }
                        
                        // Réinitialiser les sélecteurs non sélectionnés
                        choice.querySelectorAll('.equipment-option:not(.selected) select[name^="weapon_choice"]').forEach(select => {
                            select.style.borderColor = '#dee2e6';
                        });
                    }
                });
                
                console.log('Validation - Tous les choix sélectionnés:', allSelected);
                
                if (!allSelected) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
        });
    </script>
</body>
</html>
