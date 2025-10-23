<?php
/**
 * Modals pour la vue d'un lieu
 * Version refactorisée avec séparation HTML/PHP
 */
?>

<!-- Modal pour ajouter un monstre -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="addMonsterModal" tabindex="-1" aria-labelledby="addMonsterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMonsterModalLabel">Ajouter un monstre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="api/add_monster.php">
                <div class="modal-body">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    <div class="mb-3">
                        <label for="monsterSearch" class="form-label">Rechercher un monstre</label>
                        <input type="text" class="form-control" id="monsterSearch" placeholder="Tapez le nom du monstre...">
                        <input type="hidden" name="monster_id" id="selectedMonsterId">
                        <div id="monsterResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="monsterQuantity" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="monsterQuantity" name="quantity" value="1" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour ajouter un PNJ -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="addNpcModal" tabindex="-1" aria-labelledby="addNpcModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNpcModalLabel">Ajouter un PNJ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="api/add_npc.php">
                <div class="modal-body">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    <div class="mb-3">
                        <label for="npcName" class="form-label">Nom du PNJ</label>
                        <input type="text" class="form-control" id="npcName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="npcDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="npcDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="npcCharacter" class="form-label">Personnage du MJ (optionnel)</label>
                        <select class="form-control" id="npcCharacter" name="character_id">
                            <option value="">Aucun personnage</option>
                            <?php foreach ($dmCharacters as $character): ?>
                                <option value="<?php echo $character['id']; ?>"><?php echo htmlspecialchars($character['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour ajouter un joueur -->
<?php if ($canEdit && hasCampaignId($place)): ?>
<div class="modal fade" id="addPlayerModal" tabindex="-1" aria-labelledby="addPlayerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPlayerModalLabel">Ajouter un joueur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="api/add_player.php">
                <div class="modal-body">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    <input type="hidden" name="campaign_id" value="<?php echo $place['campaign_id']; ?>">
                    <div class="mb-3">
                        <label for="playerSelect" class="form-label">Joueur</label>
                        <select class="form-control" id="playerSelect" name="player_id" required>
                            <option value="">Sélectionner un joueur</option>
                            <?php foreach ($availablePlayers as $player): ?>
                                <option value="<?php echo isset($player['id']) ? $player['id'] : ''; ?>"><?php echo htmlspecialchars(isset($player['username']) ? $player['username'] : 'Inconnu'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="characterSelect" class="form-label">Personnage</label>
                        <select class="form-control" id="characterSelect" name="character_id" required>
                            <option value="">Sélectionner un personnage</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour ajouter un objet -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="addObjectModal" tabindex="-1" aria-labelledby="addObjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addObjectModalLabel">Ajouter un objet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addObjectForm">
                <div class="modal-body">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="objectType" class="form-label">Type d'objet</label>
                                <select class="form-control" id="objectType" name="object_type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="weapon">Arme</option>
                                    <option value="armor">Armure</option>
                                    <option value="magical_item">Objet magique</option>
                                    <option value="poison">Poison</option>
                                    <option value="bourse">Or</option>
                                    <option value="letter">Lettre</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="objectDisplayName" class="form-label">Nom d'affichage</label>
                                <input type="text" class="form-control" id="objectDisplayName" name="display_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sélection spécifique selon le type -->
                    <div id="specificSelection" style="display: none;">
                        <div class="mb-3">
                            <label for="specificItem" class="form-label" id="specificItemLabel">Sélectionner un élément</label>
                            <select class="form-control" id="specificItem" name="specific_item_id">
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Champs spécifiques pour les lettres -->
                    <div id="letterFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="letterContent" class="form-label">Contenu de la lettre</label>
                                    <textarea class="form-control" id="letterContent" name="letter_content" rows="4" placeholder="Saisir le contenu de la lettre..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="letterRecipient" class="form-label">Destinataire</label>
                                    <input type="text" class="form-control" id="letterRecipient" name="letter_recipient" placeholder="Nom du destinataire...">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="letterSealed" name="letter_sealed" value="1">
                                        <label class="form-check-label" for="letterSealed">
                                            <i class="fas fa-seal me-1"></i>Lettre scellée
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Champs spécifiques pour l'or -->
                    <div id="goldFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="goldCoins" class="form-label">
                                        <i class="fas fa-coins text-warning me-1"></i>Pièces d'or
                                    </label>
                                    <input type="number" class="form-control" id="goldCoins" name="gold_coins" min="0" value="0" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="silverCoins" class="form-label">
                                        <i class="fas fa-coins text-secondary me-1"></i>Pièces d'argent
                                    </label>
                                    <input type="number" class="form-control" id="silverCoins" name="silver_coins" min="0" value="0" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="copperCoins" class="form-label">
                                        <i class="fas fa-coins text-danger me-1"></i>Pièces de cuivre
                                    </label>
                                    <input type="number" class="form-control" id="copperCoins" name="copper_coins" min="0" value="0" placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Conversion :</strong> 1 pièce d'or = 10 pièces d'argent = 100 pièces de cuivre
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="objectDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="objectDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour éditer le lieu -->
<?php if ($canEdit): ?>
<div class="modal fade" id="editSceneModal" tabindex="-1" aria-labelledby="editSceneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSceneModalLabel">Modifier le lieu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="api/update_place.php">
                <div class="modal-body">
                    <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                    <div class="mb-3">
                        <label for="placeTitle" class="form-label">Nom du lieu</label>
                        <input type="text" class="form-control" id="placeTitle" name="title" value="<?php echo htmlspecialchars($place['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="placeMapUrl" class="form-label">URL de la carte</label>
                        <input type="url" class="form-control" id="placeMapUrl" name="map_url" value="<?php echo htmlspecialchars($place['map_url']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="placeNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="placeNotes" name="notes" rows="5"><?php echo htmlspecialchars($place['notes']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour créer un accès -->
<?php if ($canEdit): ?>
<div class="modal fade" id="createAccessModal" tabindex="-1" aria-labelledby="createAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAccessModalLabel">Créer un accès</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="api/create_access.php">
                <div class="modal-body">
                    <input type="hidden" name="from_place_id" value="<?php echo $place_id; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="accessName" class="form-label">Nom de l'accès</label>
                                <input type="text" class="form-control" id="accessName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="createAccessCountry" class="form-label">Pays</label>
                                <select class="form-control" id="createAccessCountry" name="country_id">
                                    <option value="">Sélectionner un pays</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?php echo $country['id']; ?>"><?php echo htmlspecialchars($country['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="createAccessRegion" class="form-label">Région</label>
                                <select class="form-control" id="createAccessRegion" name="region_id">
                                    <option value="">Sélectionner une région</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="createAccessToPlace" class="form-label">Lieu de destination</label>
                                <select class="form-control" id="createAccessToPlace" name="to_place_id" required>
                                    <option value="">Sélectionner un lieu</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="accessDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="accessDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="accessIsVisible" name="is_visible" checked>
                                <label class="form-check-label" for="accessIsVisible">Visible</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="accessIsOpen" name="is_open">
                                <label class="form-check-label" for="accessIsOpen">Ouvert</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="createAccessIsTrapped" name="is_trapped">
                                <label class="form-check-label" for="createAccessIsTrapped">Piégé</label>
                            </div>
                        </div>
                    </div>
                    <div id="trapDetails" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="trapDescription" class="form-label">Description du piège</label>
                                    <input type="text" class="form-control" id="trapDescription" name="trap_description">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="trapDifficulty" class="form-label">Difficulté</label>
                                    <input type="number" class="form-control" id="trapDifficulty" name="trap_difficulty" min="0" max="30">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="trapDamage" class="form-label">Dégâts</label>
                                    <input type="text" class="form-control" id="trapDamage" name="trap_damage">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour attribuer un objet -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="assignObjectModal" tabindex="-1" aria-labelledby="assignObjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignObjectModalLabel">Attribuer un objet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignObjectForm">
                <div class="modal-body">
                    <input type="hidden" id="assignObjectId" name="object_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Objet à attribuer</label>
                        <p class="form-control-plaintext" id="assignObjectName"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignTargetId" class="form-label">Attribuer à</label>
                        <select class="form-select" id="assignTargetId" name="target_id" required>
                            <option value="">Chargement des entités présentes...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignQuantity" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="assignQuantity" name="quantity" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus me-1"></i>Attribuer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
