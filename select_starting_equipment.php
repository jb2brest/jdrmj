<?php
/**
 * Sélection de l'équipement de départ - Version adaptée à la nouvelle structure
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

/**
 * Fonction pour ajouter un item à l'équipement d'un personnage
 */
function addItemToCharacter($characterId, $itemType, $itemId, $quantity = 1) {
    $pdo = getPDO();
    
    try {
        $itemName = '';
        
        // Récupérer le nom de l'item selon son type
        switch ($itemType) {
            case 'weapon':
                $stmt = $pdo->prepare("SELECT name FROM weapons WHERE id = ?");
                $stmt->execute([$itemId]);
                $result = $stmt->fetch();
                if ($result) {
                    $itemName = $result['name'];
                }
                break;
                
            case 'armor':
            case 'bouclier':
                $stmt = $pdo->prepare("SELECT name FROM armor WHERE id = ?");
                $stmt->execute([$itemId]);
                $result = $stmt->fetch();
                if ($result) {
                    $itemName = $result['name'];
                }
                break;
                
            case 'instrument':
            case 'outils':
            case 'nourriture':
            case 'sac':
                $stmt = $pdo->prepare("SELECT nom FROM Object WHERE id = ?");
                $stmt->execute([$itemId]);
                $result = $stmt->fetch();
                if ($result) {
                    $itemName = $result['nom'];
                }
                break;
        }
        
        if ($itemName) {
            // Ajouter l'item à l'équipement du personnage
            $stmt = $pdo->prepare("
                INSERT INTO character_equipment 
                (character_id, item_name, item_type, quantity, equipped, obtained_from, obtained_at) 
                VALUES (?, ?, ?, ?, 0, 'Équipement de départ', NOW())
            ");
            $stmt->execute([$characterId, $itemName, $itemType, $quantity]);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout de l'item au personnage: " . $e->getMessage());
        return false;
    }
}

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$character_id = $_GET['character_id'] ?? null;
if (!$character_id) {
    header('Location: characters.php');
    exit;
}

// Récupérer les informations du personnage
$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT c.*, cl.name as class_name, b.name as background_name
    FROM characters c
    LEFT JOIN classes cl ON c.class_id = cl.id
    LEFT JOIN backgrounds b ON c.background_id = b.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$character_id, $_SESSION['user_id']]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit;
}

// Récupérer les choix d'équipement de classe
$classChoix = StartingEquipmentChoix::findBySource('class', $character['class_id']);

// Récupérer les choix d'équipement de background
$backgroundChoix = StartingEquipmentChoix::findBySource('background', $character['background_id']);

// Grouper les choix par no_choix
function groupChoixByNoChoix($choixArray) {
    $grouped = [];
    foreach ($choixArray as $choix) {
        $noChoix = $choix->getNoChoix();
        if (!isset($grouped[$noChoix])) {
            $grouped[$noChoix] = [];
        }
        $grouped[$noChoix][] = $choix;
    }
    
    // Trier chaque groupe par option_letter (ordre alphabétique)
    foreach ($grouped as $noChoix => $choixGroup) {
        usort($choixGroup, function($a, $b) {
            $letterA = $a->getOptionLetter() ?? '';
            $letterB = $b->getOptionLetter() ?? '';
            return strcmp($letterA, $letterB);
        });
        $grouped[$noChoix] = $choixGroup;
    }
    
    return $grouped;
}

$groupedClassChoix = groupChoixByNoChoix($classChoix);
$groupedBackgroundChoix = groupChoixByNoChoix($backgroundChoix);

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_equipment') {
    try {
        // Nettoyer l'ancien équipement de départ
        $stmt = $pdo->prepare("DELETE FROM character_equipment WHERE character_id = ? AND obtained_from = 'Équipement de départ'");
        $stmt->execute([$character_id]);
        
        $selectedClassEquipment = $_POST['class_equipment'] ?? [];
        $selectedBackgroundEquipment = $_POST['background_equipment'] ?? [];
        $selectedWeaponChoices = $_POST['weapon_choice'] ?? [];
        $selectedWeaponChoicesBackground = $_POST['weapon_choice_background'] ?? [];
        
        // Traiter les choix d'équipement de classe
        foreach ($selectedClassEquipment as $choiceIndex => $optionLetter) {
            // Récupérer le choix correspondant
            $choixGroup = null;
            $noChoix = null;
            $index = 0;
            foreach ($groupedClassChoix as $nc => $cg) {
                if ($nc == 0) continue; // Ignorer l'équipement par défaut
                if ($index == $choiceIndex) {
                    $choixGroup = $cg;
                    $noChoix = $nc;
                    break;
                }
                $index++;
            }
            
            if ($choixGroup) {
                // Trouver l'option sélectionnée
                foreach ($choixGroup as $choix) {
                    if ($choix->getOptionLetter() === $optionLetter) {
                        // Ajouter l'équipement au personnage
                        $options = StartingEquipmentOption::findByStartingEquipmentChoixId($choix->getId());
                        foreach ($options as $option) {
                            // Vérifier si c'est une arme avec choix spécifique
                            if ($option->needsWeaponDropdown()) {
                                if (isset($selectedWeaponChoices[$choiceIndex][$option->getId()])) {
                                    $weaponId = $selectedWeaponChoices[$choiceIndex][$option->getId()];
                                    // Ajouter l'arme spécifique
                                    addItemToCharacter($character_id, 'weapon', $weaponId, $option->getNb());
                                }
                            } else {
                                // Ajouter l'équipement normal
                                addItemToCharacter($character_id, $option->getType(), $option->getTypeId(), $option->getNb());
                            }
                        }
                        break;
                    }
                }
            }
        }
        
        // Traiter les choix d'équipement de background
        foreach ($selectedBackgroundEquipment as $choiceIndex => $optionLetter) {
            // Logique similaire pour le background
            $choixGroup = null;
            $noChoix = null;
            $index = 0;
            foreach ($groupedBackgroundChoix as $nc => $cg) {
                if ($nc == 0) continue; // Ignorer l'équipement par défaut
                if ($index == $choiceIndex) {
                    $choixGroup = $cg;
                    $noChoix = $nc;
                    break;
                }
                $index++;
            }
            
            if ($choixGroup) {
                foreach ($choixGroup as $choix) {
                    if ($choix->getOptionLetter() === $optionLetter) {
                        $options = StartingEquipmentOption::findByStartingEquipmentChoixId($choix->getId());
                        foreach ($options as $option) {
                            if ($option->needsWeaponDropdown()) {
                                if (isset($selectedWeaponChoicesBackground[$choiceIndex][$option->getId()])) {
                                    $weaponId = $selectedWeaponChoicesBackground[$choiceIndex][$option->getId()];
                                    addItemToCharacter($character_id, 'weapon', $weaponId, $option->getNb());
                                }
                            } else {
                                addItemToCharacter($character_id, $option->getType(), $option->getTypeId(), $option->getNb());
                            }
                        }
                        break;
                    }
                }
            }
        }
        
        // Ajouter l'équipement par défaut
        foreach ($groupedClassChoix as $noChoix => $choixGroup) {
            if ($noChoix == 0) {
                $defaultChoix = $choixGroup[0];
                $options = StartingEquipmentOption::findByStartingEquipmentChoixId($defaultChoix->getId());
                foreach ($options as $option) {
                    addItemToCharacter($character_id, $option->getType(), $option->getTypeId(), $option->getNb());
                }
            }
        }
        
        foreach ($groupedBackgroundChoix as $noChoix => $choixGroup) {
            if ($noChoix == 0) {
                $defaultChoix = $choixGroup[0];
                $options = StartingEquipmentOption::findByStartingEquipmentChoixId($defaultChoix->getId());
                foreach ($options as $option) {
                    addItemToCharacter($character_id, $option->getType(), $option->getTypeId(), $option->getNb());
                }
            }
        }
        
        // Rediriger vers la page du personnage
        header('Location: view_character.php?id=' . $character_id);
        exit;
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la sélection de l'équipement : " . $e->getMessage();
        error_log($error_message);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélection d'équipement de départ - <?php echo htmlspecialchars($character['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-sword me-2"></i>Sélection d'équipement de départ</h1>
                <p class="text-muted">Personnage : <strong><?php echo htmlspecialchars($character['name']); ?></strong></p>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" id="equipmentForm">
            <input type="hidden" name="action" value="select_equipment">
            <input type="hidden" name="character_id" value="<?php echo $character_id; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Équipement de classe -->
                    <h3><i class="fas fa-sword me-2"></i>Équipement de classe - <?php echo htmlspecialchars($character['class_name']); ?></h3>
                    
                    <?php if (!empty($groupedClassChoix)): ?>
                        <?php $choiceIndex = 0; ?>
                        <?php foreach ($groupedClassChoix as $noChoix => $choixGroup): ?>
                            <div class="equipment-choice mb-4 p-3 border rounded">
                                <?php if ($noChoix == 0): ?>
                                    <!-- Équipement par défaut -->
                                    <h5><i class="fas fa-check-circle text-success me-2"></i>Équipement par défaut</h5>
                                    <p class="text-muted">Cet équipement est automatiquement attribué.</p>
                                    <?php 
                                    // Prendre le premier choix du groupe (ils sont tous identiques)
                                    $defaultChoix = $choixGroup[0];
                                    if ($defaultChoix->hasOptions()): 
                                    ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>Équipement :</strong><br>
                                                <?php 
                                                $itemNames = [];
                                                    foreach ($defaultChoix->getOptions() as $option) {
                                                        $itemNames[] = $option->getNameWithQuantity();
                                                    }
                                                echo implode(', ', $itemNames);
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Choix d'équipement -->
                                    <h5>Choix <?php echo $noChoix; ?></h5>
                                    <p class="text-muted">Choisissez une option :</p>
                                    <?php foreach ($choixGroup as $choix): ?>
                                        <div class="equipment-option mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="class_equipment[<?php echo $choiceIndex; ?>]" value="<?php echo $choix->getOptionLetter(); ?>" id="class_<?php echo $choiceIndex; ?>_<?php echo $choix->getOptionLetter(); ?>" required>
                                                <label class="form-check-label" for="class_<?php echo $choiceIndex; ?>_<?php echo $choix->getOptionLetter(); ?>">
                                                    <strong>Option <?php echo $choix->getOptionLetter(); ?> :</strong>
                                                    <?php if ($choix->hasOptions()): ?>
                                                        <?php 
                                                        $itemNames = [];
                                                        $hasWeaponDropdown = false;
                                                        foreach ($choix->getOptions() as $option) {
                                                            if ($option->needsWeaponDropdown()) {
                                                                $hasWeaponDropdown = true;
                                                                $itemNames[] = $option->getTypeLabel() . ' (' . $option->getTypeFilter() . ')';
                                                            } else {
                                                                $itemNames[] = $option->getNameWithQuantity();
                                                            }
                                                        }
                                                        echo implode(', ', $itemNames);
                                                        ?>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            
                                            <?php if ($choix->hasOptions()): ?>
                                                <?php foreach ($choix->getOptions() as $option): ?>
                                                    <?php if ($option->needsWeaponDropdown()): ?>
                                                        <div class="weapon-dropdown mt-2 ms-4" style="display: none;">
                                                            <label class="form-label small">Choisir une arme :</label>
                                                            <select class="form-select form-select-sm" name="weapon_choice[<?php echo $choiceIndex; ?>][<?php echo $option->getId(); ?>]">
                                                                <option value="">-- Sélectionner une arme --</option>
                                                                <?php 
                                                                $weapons = $option->getFilteredWeapons();
                                                                foreach ($weapons as $weapon): 
                                                                ?>
                                                                    <option value="<?php echo $weapon['id']; ?>">
                                                                        <?php echo htmlspecialchars($weapon['name']); ?>
                                                                        <?php if ($weapon['damage']): ?>
                                                                            (<?php echo htmlspecialchars($weapon['damage']); ?>)
                                                                        <?php endif; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php $choiceIndex++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Équipement de background -->
                    <?php if (!empty($groupedBackgroundChoix)): ?>
                        <h3 class="mt-5"><i class="fas fa-user me-2"></i>Équipement de background - <?php echo htmlspecialchars($character['background_name']); ?></h3>
                        
                        <?php $choiceIndex = 0; ?>
                        <?php foreach ($groupedBackgroundChoix as $noChoix => $choixGroup): ?>
                            <div class="equipment-choice mb-4 p-3 border rounded">
                                <?php if ($noChoix == 0): ?>
                                    <!-- Équipement par défaut -->
                                    <h5><i class="fas fa-check-circle text-success me-2"></i>Équipement par défaut</h5>
                                    <p class="text-muted">Cet équipement est automatiquement attribué.</p>
                                    <?php 
                                    // Prendre le premier choix du groupe (ils sont tous identiques)
                                    $defaultChoix = $choixGroup[0];
                                    if ($defaultChoix->hasOptions()): 
                                    ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>Équipement :</strong><br>
                                                <?php 
                                                $itemNames = [];
                                                    foreach ($defaultChoix->getOptions() as $option) {
                                                        $itemNames[] = $option->getNameWithQuantity();
                                                    }
                                                echo implode(', ', $itemNames);
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Choix d'équipement -->
                                    <h5>Choix <?php echo $noChoix; ?></h5>
                                    <p class="text-muted">Choisissez une option :</p>
                                    <?php foreach ($choixGroup as $choix): ?>
                                        <div class="equipment-option mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="background_equipment[<?php echo $choiceIndex; ?>]" value="<?php echo $choix->getOptionLetter(); ?>" id="background_<?php echo $choiceIndex; ?>_<?php echo $choix->getOptionLetter(); ?>" required>
                                                <label class="form-check-label" for="background_<?php echo $choiceIndex; ?>_<?php echo $choix->getOptionLetter(); ?>">
                                                    <strong>Option <?php echo $choix->getOptionLetter(); ?> :</strong>
                                                    <?php if ($choix->hasOptions()): ?>
                                                        <?php 
                                                        $itemNames = [];
                                                        $hasWeaponDropdown = false;
                                                        foreach ($choix->getOptions() as $option) {
                                                            if ($option->needsWeaponDropdown()) {
                                                                $hasWeaponDropdown = true;
                                                                $itemNames[] = $option->getTypeLabel() . ' (' . $option->getTypeFilter() . ')';
                                                            } else {
                                                                $itemNames[] = $option->getNameWithQuantity();
                                                            }
                                                        }
                                                        echo implode(', ', $itemNames);
                                                        ?>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            
                                            <?php if ($choix->hasOptions()): ?>
                                                <?php foreach ($choix->getOptions() as $option): ?>
                                                    <?php if ($option->needsWeaponDropdown()): ?>
                                                        <div class="weapon-dropdown mt-2 ms-4" style="display: none;">
                                                            <label class="form-label small">Choisir une arme :</label>
                                                            <select class="form-select form-select-sm" name="weapon_choice_background[<?php echo $choiceIndex; ?>][<?php echo $option->getId(); ?>]">
                                                                <option value="">-- Sélectionner une arme --</option>
                                                                <?php 
                                                                $weapons = $option->getFilteredWeapons();
                                                                foreach ($weapons as $weapon): 
                                                                ?>
                                                                    <option value="<?php echo $weapon['id']; ?>">
                                                                        <?php echo htmlspecialchars($weapon['name']); ?>
                                                                        <?php if ($weapon['damage']): ?>
                                                                            (<?php echo htmlspecialchars($weapon['damage']); ?>)
                                                                        <?php endif; ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php $choiceIndex++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Sélectionnez l'équipement de départ pour votre personnage. 
                                Les équipements marqués comme "par défaut" seront automatiquement attribués.
                            </p>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Confirmer la sélection
                                </button>
                                <a href="view_character.php?id=<?php echo $character_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour au personnage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gérer l'affichage des menus déroulants d'armes
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour afficher/masquer les menus déroulants d'armes
            function toggleWeaponDropdowns() {
                // Masquer tous les menus déroulants d'armes
                document.querySelectorAll('.weapon-dropdown').forEach(function(dropdown) {
                    dropdown.style.display = 'none';
                });
                
                // Afficher les menus déroulants pour les options sélectionnées
                document.querySelectorAll('input[type="radio"]:checked').forEach(function(radio) {
                    const optionDiv = radio.closest('.equipment-option');
                    if (optionDiv) {
                        const weaponDropdowns = optionDiv.querySelectorAll('.weapon-dropdown');
                        weaponDropdowns.forEach(function(dropdown) {
                            dropdown.style.display = 'block';
                        });
                    }
                });
            }
            
            // Écouter les changements sur les boutons radio
            document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', toggleWeaponDropdowns);
            });
            
            // Initialiser l'affichage
            toggleWeaponDropdowns();
        });
    </script>
</body>
</html>