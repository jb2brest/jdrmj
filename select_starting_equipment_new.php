<?php
/**
 * Sélection de l'équipement de départ - Version adaptée à la nouvelle structure
 */

require_once 'classes/init.php';

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
    return $grouped;
}

$groupedClassChoix = groupChoixByNoChoix($classChoix);
$groupedBackgroundChoix = groupChoixByNoChoix($backgroundChoix);
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
                                                        foreach ($choix->getOptions() as $option) {
                                                            $quantity = $option->getNb();
                                                            $name = $option->getTypeLabel();
                                                            $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                        }
                                                        echo implode(', ', $itemNames);
                                                        ?>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
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
                                                        foreach ($choix->getOptions() as $option) {
                                                            $quantity = $option->getNb();
                                                            $name = $option->getTypeLabel();
                                                            $itemNames[] = ($quantity > 1 ? $quantity . 'x ' : '') . $name;
                                                        }
                                                        echo implode(', ', $itemNames);
                                                        ?>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
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
</body>
</html>