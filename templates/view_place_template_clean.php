<?php
/**
 * Template pour la vue d'un lieu
 * Version refactorisée avec séparation HTML/PHP
 */

// Extraire les variables du template
extract($template_vars ?? []);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($place['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/view_place.css" rel="stylesheet">
    
    <style>
    .place-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
    }
    
    .place-title {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .place-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    .section-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 1rem 1.5rem;
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .section-body {
        padding: 1.5rem;
    }
    
    .object-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .object-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .object-name {
        font-weight: bold;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .object-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .object-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .token-container {
        position: relative;
        min-height: 200px;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        margin: 1rem 0;
    }
    
    .token {
        position: absolute;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
    }
    
    .token:hover {
        transform: scale(1.1);
        z-index: 20;
    }
    
    .token.player {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .token.npc {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .token.monster {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .token.object {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }
    
    .object-token .fa-box {
        color: #6c757d !important;
    }
    </style>
</head>
<body data-place-id="<?php echo $place['id']; ?>">

<?php if (!$isModal): ?>
    <?php include 'includes/navbar.php'; ?>
<?php endif; ?>

<div class="container-fluid">
    <!-- En-tête du lieu -->
    <div class="place-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="place-title"><?php echo htmlspecialchars($place['title']); ?></h1>
                    <?php if (!empty($place['description'])): ?>
                        <p class="place-subtitle"><?php echo htmlspecialchars($place['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($canEdit): ?>
                        <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#editSceneModal">
                            <i class="fas fa-edit me-2"></i>Modifier le lieu
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Description du lieu -->
                <?php if (!empty($place['description'])): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-scroll me-2"></i>Description
                    </div>
                    <div class="section-body">
                        <p><?php echo nl2br(htmlspecialchars($place['description'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Plan du lieu -->
                <?php if (!empty($place['map_url'])): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-map me-2"></i>Plan du lieu
                    </div>
                    <div class="section-body">
                        <img src="<?php echo htmlspecialchars($place['map_url']); ?>" 
                             alt="Plan du lieu" 
                             class="img-fluid rounded">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tokens et positions -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-users me-2"></i>Positions
                    </div>
                    <div class="section-body">
                        <div class="token-container" id="tokenContainer">
                            <!-- Les tokens seront ajoutés dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="col-lg-4">
                <!-- Joueurs présents -->
                <?php if (!empty($players)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-user-friends me-2"></i>Joueurs présents
                    </div>
                    <div class="section-body">
                        <?php foreach ($players as $player): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="token player me-2" style="position: static; width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($player['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($player['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($player['username']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PNJ présents -->
                <?php if (!empty($npcs)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-user-tie me-2"></i>PNJ présents
                    </div>
                    <div class="section-body">
                        <?php foreach ($npcs as $npc): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="token npc me-2" style="position: static; width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($npc['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($npc['name']); ?></strong>
                                    <?php if (!empty($npc['description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monstres présents -->
                <?php if (!empty($monsters)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-dragon me-2"></i>Monstres présents
                    </div>
                    <div class="section-body">
                        <?php foreach ($monsters as $monster): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="token monster me-2" style="position: static; width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($monster['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($monster['name']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Objets présents -->
                <?php if (!empty($objects)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-box me-2"></i>Objets présents
                    </div>
                    <div class="section-body">
                        <div id="objects-list">
                            <?php foreach ($objects as $object): ?>
                                <div class="object-item">
                                    <div class="object-name"><?php echo htmlspecialchars($object['display_name']); ?></div>
                                    <?php if (!empty($object['description'])): ?>
                                        <div class="object-description"><?php echo htmlspecialchars($object['description']); ?></div>
                                    <?php endif; ?>
                                    <div class="object-actions">
                                        <?php if ($isOwnerDM): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="assignObject(<?php echo $object['id']; ?>, '<?php echo htmlspecialchars($object['display_name']); ?>')" title="Attribuer cet objet">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/jdrmj.js"></script>

<?php if (!$isModal): ?>
    <?php include 'templates/view_place_modals.php'; ?>
    
    <script>
        // Variables JavaScript pour le template
        window.canEdit = <?php echo json_encode($js_vars['canEdit']); ?>;
        window.isOwnerDM = <?php echo json_encode($js_vars['isOwnerDM']); ?>;
        window.tokenPositions = <?php echo json_encode($js_vars['tokenPositions']); ?>;
        window.campaignId = <?php echo json_encode($js_vars['campaignId']); ?>;
        
        
        // Gestion des listes déroulantes dynamiques pour les objets
        document.addEventListener('DOMContentLoaded', function() {
            const objectTypeSelect = document.getElementById('objectType');
            const specificSelection = document.getElementById('specificSelection');
            const specificItemSelect = document.getElementById('specificItem');

            if (objectTypeSelect && specificSelection && specificItemSelect) {
                objectTypeSelect.addEventListener('change', function() {
                    const selectedType = this.value;
                    
                    if (selectedType === 'weapon' || selectedType === 'armor' || selectedType === 'magical_item' || selectedType === 'poison') {
                        specificSelection.style.display = 'block';
                        specificItemSelect.disabled = false;
                        
                        // Charger les options spécifiques
                        loadSpecificItems(selectedType);
                    } else {
                        specificSelection.style.display = 'none';
                        specificItemSelect.disabled = true;
                        specificItemSelect.innerHTML = '<option value="">Sélectionner...</option>';
                    }
                });
            }
            
            // Gestion des champs dynamiques pour les lettres
            const letterFields = document.getElementById('letterFields');
            const letterContent = document.getElementById('letterContent');
            const letterRecipient = document.getElementById('letterRecipient');
            const letterSealed = document.getElementById('letterSealed');
            const letterDisplayName = document.getElementById('letterDisplayName');
            
            if (letterFields && letterContent && letterRecipient && letterSealed && letterDisplayName) {
                function updateLetterDisplayName() {
                    const content = letterContent.value;
                    const recipient = letterRecipient.value;
                    const sealed = letterSealed.checked;
                    
                    let displayName = 'Lettre';
                    if (content) displayName += `: ${content.substring(0, 30)}${content.length > 30 ? '...' : ''}`;
                    if (recipient) displayName += ` (pour ${recipient})`;
                    if (sealed) displayName += ' [SCELLÉE]';
                    
                    letterDisplayName.value = displayName;
                }
                
                letterContent.addEventListener('input', updateLetterDisplayName);
                letterRecipient.addEventListener('input', updateLetterDisplayName);
                letterSealed.addEventListener('change', updateLetterDisplayName);
            }
            
            // Gestion des champs dynamiques pour l'or
            const goldFields = document.getElementById('goldFields');
            const goldCoins = document.getElementById('goldCoins');
            const silverCoins = document.getElementById('silverCoins');
            const copperCoins = document.getElementById('copperCoins');
            const goldDisplayName = document.getElementById('goldDisplayName');
            
            if (goldFields && goldCoins && silverCoins && copperCoins && goldDisplayName) {
                function updateGoldDisplayName() {
                    const gold = parseInt(goldCoins.value) || 0;
                    const silver = parseInt(silverCoins.value) || 0;
                    const copper = parseInt(copperCoins.value) || 0;
                    
                    let displayName = 'Bourse';
                    if (gold > 0 || silver > 0 || copper > 0) {
                        const totalGold = gold + (silver / 10) + (copper / 100);
                        displayName += ` (${totalGold.toFixed(2)} PO)`;
                    }
                    
                    goldDisplayName.value = displayName;
                }
                
                goldCoins.addEventListener('input', updateGoldDisplayName);
                silverCoins.addEventListener('input', updateGoldDisplayName);
                copperCoins.addEventListener('input', updateGoldDisplayName);
            }
        });
    </script>
<?php endif; ?>

</body>
</html>
