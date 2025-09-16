<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$character_id = (int)($_GET['id'] ?? 0);

if ($character_id === 0) {
    header('Location: characters.php');
    exit;
}

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $user_id]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit;
}

// Vérifier si la classe peut lancer des sorts
if (!canCastSpells($character['class_id'])) {
    header('Location: view_character.php?id=' . $character_id);
    exit;
}

// Récupérer les informations de la classe
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$character['class_id']]);
$class = $stmt->fetch();
$spell_capabilities = getClassSpellCapabilities($character['class_id'], $character['level']);

// Récupérer les sorts du personnage
$character_spells = getCharacterSpells($character_id);

// Récupérer les sorts disponibles pour la classe
$available_spells = getSpellsForClass($character['class_id']);

// Grouper les sorts par niveau
$spells_by_level = [];
foreach ($character_spells as $spell) {
    $spells_by_level[$spell['level']][] = $spell;
}

// Grouper les sorts disponibles par niveau
$available_by_level = [];
foreach ($available_spells as $spell) {
    $available_by_level[$spell['level']][] = $spell;
}

$page_title = "Grimoire - " . $character['name'];
include 'includes/header.php';

// Debug: Afficher des informations de débogage
if (isset($_GET['debug'])) {
    echo "<!-- DEBUG INFO -->\n";
    echo "<!-- Character ID: " . $character_id . " -->\n";
    echo "<!-- User ID: " . $user_id . " -->\n";
    echo "<!-- Character Name: " . $character['name'] . " -->\n";
    echo "<!-- Class ID: " . $character['class_id'] . " -->\n";
    echo "<!-- Level: " . $character['level'] . " -->\n";
    echo "<!-- Can Cast Spells: " . (canCastSpells($character['class_id']) ? 'YES' : 'NO') . " -->\n";
    echo "<!-- Available Spells Count: " . count($available_spells) . " -->\n";
    echo "<!-- Character Spells Count: " . count($character_spells) . " -->\n";
    echo "<!-- END DEBUG INFO -->\n";
}
?>

<style>
.grimoire-container {
    background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.grimoire-book {
    background: #F5F5DC;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    margin: 0 auto;
    width: 800px;
    min-height: 80vh;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.grimoire-book::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #8B4513, #A0522D, #8B4513);
    z-index: 10;
}

.grimoire-pages-container {
    display: flex;
    height: calc(100% - 200px);
    width: 100%;
}

.grimoire-page {
    padding: 15px;
    min-height: 80vh;
    position: relative;
    overflow-x: hidden;
    overflow-y: auto;
    width: 400px;
    box-sizing: border-box;
}

.grimoire-page-left {
    border-right: 1px solid #D2B48C;
    background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
}

.grimoire-page-right {
    background: linear-gradient(270deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
}

.spell-level-section {
    margin-bottom: 12px;
    padding: 6px;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    border: 1px solid rgba(139, 69, 19, 0.2);
}

.spell-level-title {
    color: #8B4513;
    font-weight: bold;
    font-size: 1em;
    margin-bottom: 10px;
    text-align: center;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
}

.spell-buttons-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
    width: 100%;
}

.spell-button {
    width: 350px;
    max-width: 350px;
    margin-bottom: 8px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #F5F5DC 0%, #E6E6FA 100%);
    border: 2px solid #D2B48C;
    border-radius: 6px;
    color: #8B4513;
    font-weight: 500;
    text-align: left;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    font-size: 0.85em;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.spell-button:hover {
    background: linear-gradient(135deg, #E6E6FA 0%, #DDA0DD 100%);
    border-color: #8B4513;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.spell-button.active {
    background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
    color: white;
    border-color: #654321;
}

.spell-button.known {
    border-left: 5px solid #28a745;
}

.spell-button.prepared {
    border-left: 5px solid #ffc107;
}

.spell-details {
    background: rgba(255,255,255,0.95);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    min-height: 400px;
    width: 100%;
    box-sizing: border-box;
}

.spell-details h3 {
    color: #8B4513;
    border-bottom: 2px solid #D2B48C;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.spell-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.spell-info-item {
    background: rgba(139, 69, 19, 0.05);
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #8B4513;
}

.spell-description {
    background: rgba(139, 69, 19, 0.05);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(139, 69, 19, 0.1);
    line-height: 1.6;
}

.grimoire-header {
    background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.capabilities-bar {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    text-align: center;
}

.capability-item {
    display: inline-block;
    margin: 0 15px;
    padding: 8px 15px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-weight: 500;
}

.no-spell-selected {
    text-align: center;
    color: #8B4513;
    font-style: italic;
    margin-top: 100px;
}

.no-spell-selected i {
    font-size: 3em;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Animations et effets supplémentaires */
.spell-button {
    position: relative;
    overflow: hidden;
}

.spell-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.spell-button:hover::before {
    left: 100%;
}

.grimoire-book {
    animation: bookOpen 0.8s ease-out;
}

@keyframes bookOpen {
    from {
        transform: rotateY(-90deg);
        opacity: 0;
    }
    to {
        transform: rotateY(0deg);
        opacity: 1;
    }
}

.spell-details {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Forcer l'affichage côte à côte sur les écrans larges */
.grimoire-page {
    height: 100%;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
}

.grimoire-page-left {
    height: 100%;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
}

.grimoire-page-right {
    height: 100%;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
}

/* S'assurer que le contenu des détails reste dans la page de droite */
#spell-details-container {
    position: relative;
    width: 85%;
    max-width: 85%;
    flex: 1;
    overflow-y: auto;
    min-height: 0;
    margin: 0 auto;
}

/* S'assurer que les colonnes restent côte à côte sur les écrans larges */
@media (min-width: 992px) {
    .col-lg-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .row.h-100 {
        height: calc(100vh - 200px) !important;
    }
}

/* Responsive design */
@media (max-width: 992px) {
    .grimoire-book::before {
        display: none;
    }
    
    .grimoire-page-left {
        border-right: none;
        border-bottom: 1px solid #D2B48C;
        height: auto;
        min-height: 400px;
    }
    
    .grimoire-page-right {
        height: auto;
        min-height: 400px;
    }
    
    .spell-info-grid {
        grid-template-columns: 1fr;
    }
    
    .capability-item {
        display: block;
        margin: 5px 0;
    }
}

@media (max-width: 768px) {
    .grimoire-container {
        padding: 10px 0;
    }
    
    .grimoire-page {
        padding: 15px;
        height: auto;
    }
    
    .spell-button {
        padding: 10px 12px;
        font-size: 0.9em;
    }
    
    .spell-details {
        padding: 15px;
        min-height: 300px;
    }
}
</style>

<div class="grimoire-container">
    <div class="container">
        <div class="grimoire-book">
            <!-- En-tête du grimoire -->
            <div class="grimoire-header">
                <h1><i class="fas fa-book-open me-2"></i>Grimoire de <?php echo htmlspecialchars($character['name']); ?></h1>
                <div class="mt-3">
                    <a href="view_character.php?id=<?php echo $character_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i>Retour au personnage
                    </a>
                </div>
                
                <!-- Barre des capacités -->
                <div class="capabilities-bar">
                    <div class="capability-item">
                        <i class="fas fa-magic me-1"></i>Sorts mineurs: <?php echo $spell_capabilities['cantrips_known']; ?>
                    </div>
                    <div class="capability-item">
                        <i class="fas fa-scroll me-1"></i>Sorts connus: <?php echo $spell_capabilities['spells_known']; ?>
                    </div>
                    <div class="capability-item">
                        <i class="fas fa-gem me-1"></i>Emplacements:
                        <?php
                        for ($i = 1; $i <= 9; $i++) {
                            $slots = $spell_capabilities["spell_slots_{$i}st"];
                            if ($slots > 0) {
                                echo "Niv.$i: $slots ";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="grimoire-pages-container">
                <!-- Page de gauche - Liste des sorts -->
                <div class="grimoire-page grimoire-page-left">
                    <h4 class="text-center mb-3" style="color: #8B4513; font-size: 1.1em;">
                        <i class="fas fa-list me-2"></i>Table des sorts
                    </h4>

                    <!-- Liste des sorts par niveau -->
                    <?php for ($level = 0; $level <= 9; $level++): ?>
                        <?php if (isset($available_by_level[$level])): ?>
                            <div class="spell-level-section">
                                <div class="spell-level-title">
                                    <?php if ($level == 0): ?>
                                        <i class="fas fa-sparkles me-2"></i>Sorts mineurs
                                    <?php else: ?>
                                        <i class="fas fa-gem me-2"></i>Sorts de niveau <?php echo $level; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="spell-buttons-container">
                                    <?php foreach ($available_by_level[$level] as $spell): ?>
                                        <?php
                                        $is_known = false;
                                        $is_prepared = false;
                                        foreach ($character_spells as $known_spell) {
                                            if ($known_spell['id'] == $spell['id']) {
                                                $is_known = true;
                                                $is_prepared = $known_spell['prepared'];
                                                break;
                                            }
                                        }
                                        
                                        $button_class = 'spell-button';
                                        if ($is_known) $button_class .= ' known';
                                        if ($is_prepared) $button_class .= ' prepared';
                                        ?>
                                        
                                        <button class="<?php echo $button_class; ?>" 
                                                onclick="selectSpell(<?php echo $spell['id']; ?>, this)"
                                                data-spell-id="<?php echo $spell['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php echo htmlspecialchars($spell['name']); ?>
                                                    <?php if ($is_known): ?>
                                                        <i class="fas fa-check-circle text-success ms-1"></i>
                                                    <?php endif; ?>
                                                    <?php if ($is_prepared): ?>
                                                        <i class="fas fa-star text-warning ms-1"></i>
                                                    <?php endif; ?>
                                                </span>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($spell['school']); ?>
                                                </small>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <!-- Page de droite - Détails du sort -->
                <div class="grimoire-page grimoire-page-right">
                    <h4 class="text-center mb-3" style="color: #8B4513; font-size: 1.1em;">
                        <i class="fas fa-scroll me-2"></i>Détails du sort
                    </h4>
                    
                    <div class="spell-details" id="spell-details-container">
                        <div class="no-spell-selected">
                            <i class="fas fa-hand-pointer"></i>
                            <h4>Sélectionnez un sort</h4>
                            <p>Cliquez sur un sort dans la liste de gauche pour voir ses détails ici.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('Script grimoire chargé');
let currentSpellId = null;
let characterSpells = <?php echo json_encode($character_spells); ?>;
console.log('Character spells chargés:', characterSpells);

function selectSpell(spellId, buttonElement) {
    console.log('selectSpell appelée avec spellId:', spellId, 'buttonElement:', buttonElement);
    
    // Retirer la classe active de tous les boutons
    document.querySelectorAll('.spell-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Ajouter la classe active au bouton cliqué
    buttonElement.classList.add('active');
    
    // Charger les détails du sort
    loadSpellDetails(spellId);
    currentSpellId = spellId;
}

function loadSpellDetails(spellId) {
    console.log('loadSpellDetails appelée avec spellId:', spellId);
    
    // Afficher un indicateur de chargement
    document.getElementById('spell-details-container').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="mt-2 text-muted">Chargement des détails...</p>
        </div>
    `;
    
    console.log('Appel API vers get_spell_details.php?id=' + spellId);
    
    // Charger les détails du sort via AJAX
    fetch(`get_spell_details.php?id=${spellId}`)
        .then(response => {
            console.log('Réponse reçue:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('Données reçues:', data);
            if (data.success) {
                const spell = data.spell;
                const isKnown = characterSpells.some(cs => cs.id == spellId);
                const characterSpell = characterSpells.find(cs => cs.id == spellId);
                const isPrepared = characterSpell ? characterSpell.prepared : false;
                
                document.getElementById('spell-details-container').innerHTML = `
                    <h3>${spell.name}</h3>
                    
                    <div class="spell-info-grid">
                        <div class="spell-info-item">
                            <strong>Niveau:</strong> ${spell.level == 0 ? 'Sort mineur' : 'Niveau ' + spell.level}
                        </div>
                        <div class="spell-info-item">
                            <strong>École:</strong> ${spell.school}
                        </div>
                        <div class="spell-info-item">
                            <strong>Temps d'incantation:</strong> ${spell.casting_time}
                        </div>
                        <div class="spell-info-item">
                            <strong>Portée:</strong> ${spell.range_sp}
                        </div>
                        <div class="spell-info-item">
                            <strong>Composantes:</strong> ${spell.components}
                        </div>
                        <div class="spell-info-item">
                            <strong>Durée:</strong> ${spell.duration}
                        </div>
                    </div>
                    
                    <div class="spell-description">
                        <strong>Description:</strong>
                        <p>${spell.description}</p>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-flex gap-2 flex-wrap">
                            ${!isKnown ? `
                                <button class="btn btn-success" onclick="addSpell(${<?php echo $character_id; ?>}, ${spellId}, '${spell.name}')">
                                    <i class="fas fa-plus me-1"></i>Ajouter au grimoire
                                </button>
                            ` : `
                                <button class="btn btn-${isPrepared ? 'warning' : 'info'}" 
                                        onclick="toggleSpellPrepared(${<?php echo $character_id; ?>}, ${spellId}, ${isPrepared ? 'false' : 'true'})">
                                    <i class="fas fa-${isPrepared ? 'star' : 'star-o'} me-1"></i>
                                    ${isPrepared ? 'Dépréparer' : 'Préparer'}
                                </button>
                                <button class="btn btn-danger" 
                                        onclick="removeSpell(${<?php echo $character_id; ?>}, ${spellId}, '${spell.name}')">
                                    <i class="fas fa-trash me-1"></i>Retirer du grimoire
                                </button>
                            `}
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('spell-details-container').innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h4>Erreur</h4>
                        <p>Impossible de charger les détails du sort.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails du sort:', error);
            document.getElementById('spell-details-container').innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h4>Erreur</h4>
                    <p>Erreur lors du chargement des détails du sort.</p>
                    <small>Vérifiez la console pour plus de détails.</small>
                </div>
            `;
        });
}

function addSpell(characterId, spellId, spellName) {
    if (!confirm(`Ajouter le sort "${spellName}" à votre grimoire ?`)) {
        return;
    }
    
    fetch('manage_character_spells.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            character_id: characterId,
            spell_id: spellId,
            prepared: false
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour la liste des sorts du personnage
            characterSpells.push({id: spellId, prepared: false});
            
            // Mettre à jour l'interface
            updateSpellButton(spellId, true, false);
            
            // Recharger les détails du sort
            if (currentSpellId == spellId) {
                loadSpellDetails(spellId);
            }
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'ajout du sort');
    });
}

function removeSpell(characterId, spellId, spellName) {
    if (!confirm(`Retirer le sort "${spellName}" de votre grimoire ?`)) {
        return;
    }
    
    fetch('manage_character_spells.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            character_id: characterId,
            spell_id: spellId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Retirer le sort de la liste des sorts du personnage
            characterSpells = characterSpells.filter(cs => cs.id != spellId);
            
            // Mettre à jour l'interface
            updateSpellButton(spellId, false, false);
            
            // Recharger les détails du sort
            if (currentSpellId == spellId) {
                loadSpellDetails(spellId);
            }
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression du sort');
    });
}

function toggleSpellPrepared(characterId, spellId, prepared) {
    fetch('manage_character_spells.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle_prepared',
            character_id: characterId,
            spell_id: spellId,
            prepared: prepared
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le statut de préparation dans la liste
            const characterSpell = characterSpells.find(cs => cs.id == spellId);
            if (characterSpell) {
                characterSpell.prepared = prepared;
            }
            
            // Mettre à jour l'interface
            const isKnown = characterSpells.some(cs => cs.id == spellId);
            updateSpellButton(spellId, isKnown, prepared);
            
            // Recharger les détails du sort
            if (currentSpellId == spellId) {
                loadSpellDetails(spellId);
            }
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour du sort');
    });
}

function updateSpellButton(spellId, isKnown, isPrepared) {
    const button = document.querySelector(`[data-spell-id="${spellId}"]`);
    if (button) {
        // Mettre à jour les classes CSS
        button.classList.remove('known', 'prepared');
        if (isKnown) button.classList.add('known');
        if (isPrepared) button.classList.add('prepared');
        
        // Mettre à jour les icônes
        const checkIcon = button.querySelector('.fa-check-circle');
        const starIcon = button.querySelector('.fa-star');
        
        if (isKnown && !checkIcon) {
            const span = button.querySelector('span');
            span.innerHTML += '<i class="fas fa-check-circle text-success ms-1"></i>';
        } else if (!isKnown && checkIcon) {
            checkIcon.remove();
        }
        
        if (isPrepared && !starIcon) {
            const span = button.querySelector('span');
            span.innerHTML += '<i class="fas fa-star text-warning ms-1"></i>';
        } else if (!isPrepared && starIcon) {
            starIcon.remove();
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
