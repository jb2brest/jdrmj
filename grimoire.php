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
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-book-open me-2"></i>Grimoire de <?php echo htmlspecialchars($character['name']); ?></h1>
                <div>
                    <a href="view_character.php?id=<?php echo $character_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour au personnage
                    </a>
                </div>
            </div>

            <!-- Informations sur les capacités de sorts -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-magic me-2"></i>Capacités de sorts</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Cantrips connus:</strong> <?php echo $spell_capabilities['cantrips_known']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Sorts connus:</strong> <?php echo $spell_capabilities['spells_known']; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Emplacements de sorts:</strong>
                            <?php
                            for ($i = 1; $i <= 9; $i++) {
                                $slots = $spell_capabilities["spell_slots_{$i}st"];
                                if ($slots > 0) {
                                    echo "Niveau $i: $slots ";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sorts connus -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-scroll me-2"></i>Sorts connus</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($character_spells)): ?>
                        <p class="text-muted">Aucun sort connu pour le moment.</p>
                    <?php else: ?>
                        <?php for ($level = 0; $level <= 9; $level++): ?>
                            <?php if (isset($spells_by_level[$level])): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary">
                                        <?php if ($level == 0): ?>
                                            Cantrips
                                        <?php else: ?>
                                            Sorts de niveau <?php echo $level; ?>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="row">
                                        <?php foreach ($spells_by_level[$level] as $spell): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <?php echo htmlspecialchars($spell['name']); ?>
                                                            <?php if ($spell['prepared']): ?>
                                                                <span class="badge bg-success ms-1">Préparé</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <p class="card-text">
                                                            <small class="text-muted">
                                                                <strong>École:</strong> <?php echo htmlspecialchars($spell['school']); ?><br>
                                                                <strong>Temps d'incantation:</strong> <?php echo htmlspecialchars($spell['casting_time']); ?><br>
                                                                <strong>Portée:</strong> <?php echo htmlspecialchars($spell['range_sp']); ?><br>
                                                                <strong>Durée:</strong> <?php echo htmlspecialchars($spell['duration']); ?>
                                                            </small>
                                                        </p>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="showSpellDetails(<?php echo $spell['id']; ?>)">
                                                                <i class="fas fa-eye me-1"></i>Détails
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-warning" 
                                                                    onclick="toggleSpellPrepared(<?php echo $character_id; ?>, <?php echo $spell['id']; ?>, <?php echo $spell['prepared'] ? 'false' : 'true'; ?>)">
                                                                <i class="fas fa-<?php echo $spell['prepared'] ? 'check' : 'times'; ?> me-1"></i>
                                                                <?php echo $spell['prepared'] ? 'Dépréparer' : 'Préparer'; ?>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="removeSpell(<?php echo $character_id; ?>, <?php echo $spell['id']; ?>, '<?php echo addslashes($spell['name']); ?>')">
                                                                <i class="fas fa-trash me-1"></i>Retirer
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sorts disponibles -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Sorts disponibles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($available_spells)): ?>
                        <p class="text-muted">Aucun sort disponible pour cette classe.</p>
                    <?php else: ?>
                        <?php for ($level = 0; $level <= 9; $level++): ?>
                            <?php if (isset($available_by_level[$level])): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary">
                                        <?php if ($level == 0): ?>
                                            Cantrips
                                        <?php else: ?>
                                            Sorts de niveau <?php echo $level; ?>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="row">
                                        <?php foreach ($available_by_level[$level] as $spell): ?>
                                            <?php
                                            $is_known = false;
                                            foreach ($character_spells as $known_spell) {
                                                if ($known_spell['id'] == $spell['id']) {
                                                    $is_known = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100 <?php echo $is_known ? 'border-success' : ''; ?>">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <?php echo htmlspecialchars($spell['name']); ?>
                                                            <?php if ($is_known): ?>
                                                                <span class="badge bg-success ms-1">Connu</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <p class="card-text">
                                                            <small class="text-muted">
                                                                <strong>École:</strong> <?php echo htmlspecialchars($spell['school']); ?><br>
                                                                <strong>Temps d'incantation:</strong> <?php echo htmlspecialchars($spell['casting_time']); ?><br>
                                                                <strong>Portée:</strong> <?php echo htmlspecialchars($spell['range_sp']); ?><br>
                                                                <strong>Durée:</strong> <?php echo htmlspecialchars($spell['duration']); ?>
                                                            </small>
                                                        </p>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="showSpellDetails(<?php echo $spell['id']; ?>)">
                                                                <i class="fas fa-eye me-1"></i>Détails
                                                            </button>
                                                            <?php if (!$is_known): ?>
                                                                <button class="btn btn-sm btn-outline-success" 
                                                                        onclick="addSpell(<?php echo $character_id; ?>, <?php echo $spell['id']; ?>, '<?php echo addslashes($spell['name']); ?>')">
                                                                    <i class="fas fa-plus me-1"></i>Ajouter
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails du sort -->
<div class="modal fade" id="spellModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="spellModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="spellModalBody">
                <!-- Le contenu sera chargé via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function showSpellDetails(spellId) {
    // Charger les détails du sort via AJAX
    fetch(`get_spell_details.php?id=${spellId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('spellModalTitle').textContent = data.spell.name;
                document.getElementById('spellModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Niveau:</strong> ${data.spell.level}</p>
                            <p><strong>École:</strong> ${data.spell.school}</p>
                            <p><strong>Temps d'incantation:</strong> ${data.spell.casting_time}</p>
                            <p><strong>Portée:</strong> ${data.spell.range_sp}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Composantes:</strong> ${data.spell.components}</p>
                            <p><strong>Durée:</strong> ${data.spell.duration}</p>
                            <p><strong>Classes:</strong> ${data.spell.classes}</p>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <strong>Description:</strong>
                        <p>${data.spell.description}</p>
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('spellModal')).show();
            } else {
                alert('Erreur lors du chargement des détails du sort');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails du sort');
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
            location.reload(); // Recharger la page pour voir les changements
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
            location.reload(); // Recharger la page pour voir les changements
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
            location.reload(); // Recharger la page pour voir les changements
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour du sort');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
