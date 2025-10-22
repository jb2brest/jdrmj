/**
 * JDR 4 MJ - JavaScript principal
 * Regroupe toutes les fonctionnalités JavaScript de l'application
 */

// ===== GESTION DE L'IDENTIFICATION DES PNJ =====

/**
 * Basculer l'identification d'un PNJ
 */
function toggleNpcIdentification(npcId, npcName, isIdentified) {
    const action = isIdentified ? 'Désidentifier' : 'Identifier';
    
    if (confirm(action + ' ' + npcName + ' pour les joueurs ?')) {
        // Afficher un indicateur de chargement
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Faire l'appel AJAX vers l'endpoint dédié
        fetch('api/toggle_npc_identification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                npc_id: npcId,
                place_id: window.location.search.match(/id=(\d+)/)[1]
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Recharger la page pour mettre à jour l'interface
                window.location.reload();
            } else {
                throw new Error(data.message || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'identification du PNJ:', error);
            alert('Erreur lors de l\'identification du PNJ: ' + error.message);
            
            // Restaurer le bouton
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}

// ===== GESTION DES TOKENS (DRAG & DROP) =====

// Variables globales pour le drag & drop
let draggedToken = null;
let isDragging = false;

/**
 * Initialisation du système de tokens (drag & drop)
 */
function initializeTokenSystem() {
    console.log('🎯 Initialisation du système de tokens');
    
    const tokens = document.querySelectorAll('.token');
    const mapImage = document.getElementById('mapImage');
    
    if (!mapImage || tokens.length === 0) {
        console.log('⚠️ Aucune carte ou token trouvé');
        return;
    }

    // Initialiser les positions des pions
    console.log('Initialisation du système de pions...');
    console.log('Nombre de pions trouvés:', tokens.length);
    
    tokens.forEach(token => {
        const isOnMap = token.dataset.isOnMap === 'true';
        const x = parseInt(token.dataset.positionX) || 0;
        const y = parseInt(token.dataset.positionY) || 0;
        
        console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId}: isOnMap=${isOnMap}, x=${x}, y=${y}`);
        
        // Positionner sur la carte si isOnMap=true OU si on a des positions valides
        if (isOnMap || (x > 0 && y > 0)) {
            console.log(`Initialisation pion: ${token.dataset.tokenType}_${token.dataset.entityId} à ${x}%, ${y}%`);
            positionTokenOnMap(token, x, y);
        } else {
            console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId} reste dans la sidebar`);
        }
    });
}

/**
 * Initialiser le drag & drop des tokens
 */
function initializeTokenDragDrop() {
    console.log('🎯 Initialisation du drag & drop des tokens');
    
    const tokens = document.querySelectorAll('.token');
    const mapContainer = document.getElementById('mapContainer');
    const mapImage = document.getElementById('mapImage');
    
    if (!mapContainer || !mapImage) {
        console.log('⚠️ Éléments de carte non trouvés');
        return;
    }
    
    tokens.forEach(token => {
        // Positionner le token selon sa position actuelle
        positionTokenOnMap(token);
        
        // Ajouter les événements de drag & drop
        token.draggable = true;
        
        token.addEventListener('dragstart', function(e) {
            draggedToken = this;
            isDragging = true;
            this.style.opacity = '0.5';
            console.log('🎯 Début du drag:', this.dataset.tokenType, this.dataset.entityId);
        });
        
        token.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
            isDragging = false;
            draggedToken = null;
        });
    });
    
    // Événements sur la carte
    mapImage.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    mapImage.addEventListener('drop', function(e) {
        e.preventDefault();
        
        if (draggedToken) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Convertir en pourcentages
            const xPercent = Math.round((x / rect.width) * 100);
            const yPercent = Math.round((y / rect.height) * 100);
            
            // Positionner le token sur la carte
            positionTokenOnMap(draggedToken, xPercent, yPercent);
            
            // Sauvegarder la position
            saveTokenPosition(draggedToken);
            
            console.log('🎯 Token déposé sur la carte:', draggedToken.dataset.tokenType, draggedToken.dataset.entityId, 'à', xPercent + '%', yPercent + '%');
        }
    });
    
    // Événements sur la sidebar
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (tokenSidebar) {
        tokenSidebar.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        tokenSidebar.addEventListener('drop', function(e) {
            e.preventDefault();
            
            if (draggedToken) {
                // Remettre le token dans la sidebar
                resetTokenToSidebar(draggedToken);
                
                // Sauvegarder la position
                saveTokenPosition(draggedToken);
                
                console.log('🎯 Token remis dans la sidebar:', draggedToken.dataset.tokenType, draggedToken.dataset.entityId);
            }
        });
    }
}

/**
 * Positionner un token sur la carte
 */
function positionTokenOnMap(token, x = null, y = null) {
    const mapContainer = document.getElementById('mapContainer');
    const mapImage = document.getElementById('mapImage');
    
    if (!mapContainer || !mapImage) {
        return;
    }
    
    // Retirer le token de la sidebar s'il y est
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (tokenSidebar && tokenSidebar.contains(token)) {
        tokenSidebar.removeChild(token);
    }
    
    // Ajouter le token au conteneur de la carte
    mapContainer.appendChild(token);
    
    // Si x et y sont fournis, les utiliser, sinon utiliser les données du token
    if (x !== null && y !== null) {
        token.style.position = 'absolute';
        token.style.left = x + '%';
        token.style.top = y + '%';
        token.style.transform = 'translate(-50%, -50%)';
        token.style.zIndex = '1000';
        token.style.margin = '0';
        token.style.pointerEvents = 'auto';
        token.dataset.isOnMap = 'true';
        token.dataset.positionX = x;
        token.dataset.positionY = y;
    } else {
        // Utiliser les positions stockées
        const isOnMap = token.dataset.isOnMap === 'true';
        const posX = parseInt(token.dataset.positionX) || 0;
        const posY = parseInt(token.dataset.positionY) || 0;
        
        if (isOnMap) {
            token.style.position = 'absolute';
            token.style.left = posX + '%';
            token.style.top = posY + '%';
            token.style.transform = 'translate(-50%, -50%)';
            token.style.zIndex = '1000';
            token.style.margin = '0';
            token.style.pointerEvents = 'auto';
        } else {
            // Remettre dans la sidebar
            resetTokenToSidebar(token);
        }
    }
}

/**
 * Remettre un token dans la sidebar
 */
function resetTokenToSidebar(token) {
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (tokenSidebar) {
        // Retirer le token de son conteneur actuel
        if (token.parentNode) {
            token.parentNode.removeChild(token);
        }
        
        // Ajouter le token à la sidebar
        tokenSidebar.appendChild(token);
        
        // Réinitialiser les styles
        token.style.position = 'static';
        token.style.left = 'auto';
        token.style.top = 'auto';
        token.style.transform = 'none';
        token.style.zIndex = 'auto';
        token.style.margin = '2px';
        token.dataset.isOnMap = 'false';
        token.dataset.positionX = '0';
        token.dataset.positionY = '0';
    }
}

/**
 * Sauvegarder la position d'un token
 */
function saveTokenPosition(token) {
    const tokenData = {
        token_type: token.dataset.tokenType,
        entity_id: token.dataset.entityId,
        position_x: parseInt(token.dataset.positionX) || 0,
        position_y: parseInt(token.dataset.positionY) || 0,
        is_on_map: token.dataset.isOnMap === 'true'
    };
    
    fetch('api/save_token_position.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            place_id: window.placeId,
            token_data: tokenData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Position du token sauvegardée:', tokenData);
        } else {
            console.error('❌ Erreur lors de la sauvegarde:', data.error);
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la sauvegarde:', error);
    });
}

/**
 * Initialiser le drag & drop des objets
 */
function initializeObjectDragDrop() {
    console.log('🎯 Initialisation du drag & drop des objets');
    
    const objectTokens = document.querySelectorAll('.token[data-token-type="object"]');
    console.log('🎯 Pions d\'objets trouvés:', objectTokens.length);
    
    objectTokens.forEach(token => {
        // Ajouter les événements de drag & drop
        token.draggable = true;
        
        token.addEventListener('dragstart', function(e) {
            draggedToken = this;
            isDragging = true;
            this.style.opacity = '0.5';
            console.log('🎯 Début du drag d\'un objet:', this.dataset.objectName);
        });
        
        token.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
            isDragging = false;
            draggedToken = null;
        });
    });
}

// ===== LOGIQUE DES DÉS =====

let selectedDiceSides = null;
let currentCampaignId = 0;

/**
 * Initialiser les variables globales
 */
function initializeGlobalVariables() {
    currentCampaignId = window.campaignId ? window.campaignId : 0;
    console.log('🎯 Variables globales initialisées:');
    console.log('  - currentCampaignId:', currentCampaignId);
    console.log('  - window.campaignId:', window.campaignId);
}

/**
 * Initialiser le système de dés
 */
function initializeDiceSystem() {
    console.log('🎲 Initialisation du système de dés');
    
    const diceButtons = document.querySelectorAll('.dice-btn');
    const rollButton = document.getElementById('roll-dice-btn');
    const resultsDiv = document.getElementById('dice-results');
    
    if (!diceButtons.length || !rollButton || !resultsDiv) {
        console.log('⚠️ Éléments de dés non trouvés');
        return;
    }
    
    // Charger l'historique des jets au chargement de la page
    loadDiceHistory();
    
    // Gestion de la sélection des dés
    diceButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Désélectionner tous les autres boutons
            diceButtons.forEach(btn => {
                btn.classList.remove('btn-primary', 'btn-success');
                btn.classList.add('btn-outline-primary', 'btn-outline-success');
            });
            
            // Sélectionner le dé actuel
            selectedDiceSides = parseInt(this.getAttribute('data-sides'));
            this.classList.remove('btn-outline-primary', 'btn-outline-success');
            
            if (selectedDiceSides === 100) {
                this.classList.add('btn-success');
            } else {
                this.classList.add('btn-primary');
            }
            
            // Activer le bouton de lancer
            rollButton.disabled = false;
            
            // Mettre à jour l'affichage
            updateDiceSelectionDisplay();
        });
    });
    
    // Gestion du lancer de dés
    rollButton.addEventListener('click', function() {
        if (selectedDiceSides) {
            rollDice();
        }
    });
    
    // Mettre à jour l'affichage quand la quantité change
    const quantityInput = document.getElementById('dice-quantity');
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            if (selectedDiceSides) {
                updateDiceSelectionDisplay();
            }
        });
    }
}

/**
 * Mettre à jour l'affichage de la sélection de dés
 */
function updateDiceSelectionDisplay() {
    const quantity = parseInt(document.getElementById('dice-quantity').value) || 1;
    const modifier = parseInt(document.getElementById('dice-modifier').value) || 0;
    
    console.log(`🎲 Sélection: ${quantity}d${selectedDiceSides}${modifier > 0 ? '+' + modifier : modifier < 0 ? modifier : ''}`);
}

/**
 * Obtenir l'icône du dé
 */
function getDiceIcon(sides) {
    const icons = {
        4: 'fas fa-dice-d4',
        6: 'fas fa-dice',
        8: 'fas fa-dice-d8',
        10: 'fas fa-dice-d10',
        12: 'fas fa-dice-d12',
        20: 'fas fa-dice-d20',
        100: 'fas fa-dice-d20'
    };
    return icons[sides] || 'fas fa-dice';
}

/**
 * Lancer les dés
 */
function rollDice() {
    if (!selectedDiceSides) {
        alert('Veuillez sélectionner un dé');
        return;
    }
    
    const quantity = parseInt(document.getElementById('dice-quantity').value) || 1;
    const modifier = parseInt(document.getElementById('dice-modifier').value) || 0;
    
    console.log(`🎲 Lancement: ${quantity}d${selectedDiceSides}${modifier > 0 ? '+' + modifier : modifier < 0 ? modifier : ''}`);
    
    // Générer les résultats
    const results = [];
    let total = 0;
    let maxResult = 0;
    let minResult = selectedDiceSides;
    
    for (let i = 0; i < quantity; i++) {
        const roll = Math.floor(Math.random() * selectedDiceSides) + 1;
        results.push(roll);
        total += roll;
        maxResult = Math.max(maxResult, roll);
        minResult = Math.min(minResult, roll);
    }
    
    total += modifier;
    
    // Afficher les résultats
    showFinalResults(results, total, maxResult, minResult, modifier);
    
    // Sauvegarder le jet
    saveDiceRoll(results, total, maxResult, minResult);
}

/**
 * Afficher les résultats finaux
 */
function showFinalResults(results, total, maxResult, minResult, modifier) {
    const resultsDiv = document.getElementById('dice-results');
    const diceIcon = getDiceIcon(selectedDiceSides);
    
    let html = `
        <div class="dice-result mb-2">
            <div class="d-flex align-items-center mb-2">
                <i class="${diceIcon} me-2"></i>
                <strong>Résultats: ${results.join(', ')}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Total: <strong>${total}</strong></span>
                <span>Max: ${maxResult} | Min: ${minResult}</span>
            </div>
        </div>
    `;
    
    resultsDiv.innerHTML = html;
}

/**
 * Sauvegarder le jet de dés
 */
function saveDiceRoll(results, total, maxResult, minResult) {
    if (!currentCampaignId || currentCampaignId === 0) {
        console.error('Impossible de sauvegarder le jet de dés : aucune campagne associée à ce lieu');
        alert('Impossible de sauvegarder le jet de dés : aucune campagne associée à ce lieu');
        return;
    }
    
    const diceType = `D${selectedDiceSides}`;
    const quantity = parseInt(document.getElementById('dice-quantity').value);
    const isHidden = document.getElementById('hide-dice-roll').checked;
    
    const rollData = {
        campaign_id: currentCampaignId,
        dice_type: diceType,
        dice_sides: selectedDiceSides,
        quantity: quantity,
        results: results,
        total: total,
        max_result: maxResult,
        min_result: minResult,
        is_hidden: isHidden
    };
    
    console.log('💾 Sauvegarde du jet:', rollData);
    
    fetch('api/save_dice_roll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(rollData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Jet sauvegardé avec succès');
            // Recharger l'historique
            loadDiceHistory();
        } else {
            console.error('❌ Erreur lors de la sauvegarde:', data.error);
            alert('Erreur lors de la sauvegarde: ' + data.error);
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la sauvegarde:', error);
        alert('Erreur lors de la sauvegarde: ' + error.message);
    });
}

/**
 * Charger l'historique des jets de dés
 */
function loadDiceHistory() {
    if (!currentCampaignId || currentCampaignId === 0) {
        console.log('⚠️ Pas de campagne associée, impossible de charger l\'historique');
        return;
    }
    
    const showHidden = window.isOwnerDM || false;
    const url = `api/get_dice_rolls_history.php?campaign_id=${currentCampaignId}&show_hidden=${showHidden}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDiceHistory(data.rolls);
            } else {
                console.error('❌ Erreur lors du chargement de l\'historique:', data.error);
                document.getElementById('dice-history').innerHTML = `
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                        <p class="mb-0 small">Erreur lors du chargement de l'historique</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement de l\'historique:', error);
            document.getElementById('dice-history').innerHTML = `
                <div class="text-muted text-center py-3">
                    <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                    <p class="mb-0 small">Erreur lors du chargement de l'historique</p>
                </div>
            `;
        });
}

/**
 * Afficher l'historique des jets de dés
 */
function displayDiceHistory(rolls) {
    const historyDiv = document.getElementById('dice-history');
    
    if (!rolls || rolls.length === 0) {
        historyDiv.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-history fa-lg mb-2"></i>
                <p class="mb-0 small">Aucun jet de dés enregistré</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    rolls.forEach(roll => {
        const diceIcon = getDiceIcon(roll.dice_sides);
        const isHidden = roll.is_hidden ? ' (masqué)' : '';
        
        html += `
            <div class="dice-history-item p-2 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="${diceIcon} me-2"></i>
                            <strong>${roll.dice_type}</strong>
                            <span class="badge bg-primary ms-2">${roll.total}</span>
                        </div>
                        <div class="small text-muted">
                            ${roll.username} • ${roll.rolled_at}${isHidden}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    historyDiv.innerHTML = html;
}

// ===== RECHERCHE DE MONSTRES =====

/**
 * Initialiser la recherche de monstres
 */
function initializeMonsterSearch() {
    console.log('🔍 Initialisation de la recherche de monstres');
    
    const searchInput = document.getElementById('monsterSearch');
    const resultsDiv = document.getElementById('monsterResults');
    
    if (!searchInput || !resultsDiv) {
        console.log('⚠️ Éléments de recherche de monstres non trouvés');
        return;
    }
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchMonsters(query);
        }, 300);
    });
}

/**
 * Rechercher des monstres
 */
function searchMonsters(query) {
    fetch(`api/search_monsters.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMonsterResults(data.monsters);
            } else {
                console.error('❌ Erreur lors de la recherche:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de la recherche:', error);
        });
}

/**
 * Afficher les résultats de recherche de monstres
 */
function displayMonsterResults(monsters) {
    const resultsDiv = document.getElementById('monsterResults');
    
    if (!monsters || monsters.length === 0) {
        resultsDiv.innerHTML = '<div class="text-muted p-2">Aucun monstre trouvé</div>';
        return;
    }
    
    let html = '';
    monsters.forEach(monster => {
        html += `
            <div class="list-group-item list-group-item-action cursor-pointer" onclick="selectMonster(${monster.id}, '${monster.name.replace(/'/g, "\\'")}')">
                <div class="fw-bold">${monster.name}</div>
                <div class="small text-muted">${monster.description || 'Aucune description'}</div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

/**
 * Sélectionner un monstre
 */
function selectMonster(monsterId, monsterName) {
    console.log('🎯 Sélection du monstre:', monsterId, monsterName);
    
    // Remplir le champ caché
    const selectedMonsterId = document.getElementById('selectedMonsterId');
    if (selectedMonsterId) {
        selectedMonsterId.value = monsterId;
    }
    
    // Mettre à jour l'affichage de la recherche
    const monsterSearch = document.getElementById('monsterSearch');
    if (monsterSearch) {
        monsterSearch.value = monsterName;
    }
    
    // Vider les résultats
    const resultsDiv = document.getElementById('monsterResults');
    if (resultsDiv) {
        resultsDiv.innerHTML = '';
    }
    
    console.log('✅ Monstre sélectionné:', monsterId);
}

// ===== RECHERCHE DE POISONS =====

/**
 * Initialiser la recherche de poisons
 */
function initializePoisonSearch() {
    console.log('☠️ Initialisation de la recherche de poisons');
    
    const searchInput = document.getElementById('poison-search');
    const resultsDiv = document.getElementById('poison-search-results');
    
    if (!searchInput || !resultsDiv) {
        console.log('⚠️ Éléments de recherche de poisons non trouvés');
        return;
    }
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchPoisons(query);
        }, 300);
    });
}

/**
 * Rechercher des poisons
 */
function searchPoisons(query) {
    fetch(`api/search_poisons.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPoisonResults(data.poisons);
            } else {
                console.error('❌ Erreur lors de la recherche:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de la recherche:', error);
        });
}

/**
 * Afficher les résultats de recherche de poisons
 */
function displayPoisonResults(poisons) {
    const resultsDiv = document.getElementById('poison-search-results');
    
    if (!poisons || poisons.length === 0) {
        resultsDiv.innerHTML = '<div class="text-muted p-2">Aucun poison trouvé</div>';
        return;
    }
    
    let html = '';
    poisons.forEach(poison => {
        html += `
            <div class="poison-result p-2 border-bottom cursor-pointer" onclick="addPoisonToPlace(${poison.id})">
                <div class="fw-bold">${poison.name}</div>
                <div class="small text-muted">${poison.description || 'Aucune description'}</div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

// ===== RECHERCHE D'OBJETS MAGIQUES =====

/**
 * Initialiser la recherche d'objets magiques
 */
function initializeMagicalItemSearch() {
    console.log('✨ Initialisation de la recherche d\'objets magiques');
    
    const searchInput = document.getElementById('magical-item-search');
    const resultsDiv = document.getElementById('magical-item-search-results');
    
    if (!searchInput || !resultsDiv) {
        console.log('⚠️ Éléments de recherche d\'objets magiques non trouvés');
        return;
    }
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchMagicalItems(query);
        }, 300);
    });
}

/**
 * Rechercher des objets magiques
 */
function searchMagicalItems(query) {
    fetch(`api/search_magical_items.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMagicalItemResults(data.items);
            } else {
                console.error('❌ Erreur lors de la recherche:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors de la recherche:', error);
        });
}

/**
 * Afficher les résultats de recherche d'objets magiques
 */
function displayMagicalItemResults(items) {
    const resultsDiv = document.getElementById('magical-item-search-results');
    
    if (!items || items.length === 0) {
        resultsDiv.innerHTML = '<div class="text-muted p-2">Aucun objet magique trouvé</div>';
        return;
    }
    
    let html = '';
    items.forEach(item => {
        html += `
            <div class="magical-item-result p-2 border-bottom cursor-pointer" onclick="addMagicalItemToPlace(${item.id})">
                <div class="fw-bold">${item.name}</div>
                <div class="small text-muted">${item.description || 'Aucune description'}</div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

// ===== GESTION DES JOUEURS =====

/**
 * Initialiser la gestion des joueurs
 */
function initializePlayerManagement() {
    console.log('👥 Initialisation de la gestion des joueurs');
    
    // TODO: Implémenter la gestion des joueurs si nécessaire
}

// ===== GESTION DES MODALES D'ACCÈS =====

/**
 * Initialiser les modales d'accès
 */
function initializeAccessModals() {
    console.log('🚪 Initialisation des modales d\'accès');
    
    // TODO: Implémenter les modales d'accès si nécessaire
}

// ===== FONCTIONS DE GESTION DES ENTITÉS =====

/**
 * Basculer la visibilité d'un PNJ
 */
function toggleNpcVisibility(npcId) {
    console.log('👁️ Basculement de la visibilité du PNJ:', npcId);
    
    // TODO: Implémenter l'appel API pour basculer la visibilité
    fetch('api/toggle_npc_visibility.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            npc_id: npcId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du basculement de la visibilité');
    });
}

/**
 * Supprimer un PNJ
 */
function removeNpc(npcId) {
    console.log('🗑️ Suppression du PNJ:', npcId);
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce PNJ ?')) {
        // TODO: Implémenter l'appel API pour supprimer le PNJ
        fetch('api/remove_npc.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                npc_id: npcId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Recharger la page pour voir les changements
                location.reload();
            } else {
                alert('Erreur: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du PNJ');
        });
    }
}

/**
 * Basculer la visibilité d'un monstre
 */
function toggleMonsterVisibility(monsterId) {
    console.log('👁️ Basculement de la visibilité du monstre:', monsterId);
    
    // TODO: Implémenter l'appel API pour basculer la visibilité
    fetch('api/toggle_monster_visibility.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            monster_id: monsterId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du basculement de la visibilité');
    });
}

/**
 * Basculer l'identification d'un monstre
 */
function toggleMonsterIdentification(monsterId) {
    console.log('🔍 Basculement de l\'identification du monstre:', monsterId);
    
    // TODO: Implémenter l'appel API pour basculer l'identification
    fetch('api/toggle_monster_identification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            monster_id: monsterId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du basculement de l\'identification');
    });
}

/**
 * Supprimer un monstre
 */
function removeMonster(monsterId) {
    console.log('🗑️ Suppression du monstre:', monsterId);
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce monstre ?')) {
        // TODO: Implémenter l'appel API pour supprimer le monstre
        fetch('api/remove_monster.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                monster_id: monsterId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Recharger la page pour voir les changements
                location.reload();
            } else {
                alert('Erreur: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du monstre');
        });
    }
}

/**
 * Basculer la visibilité d'un objet
 */
function toggleObjectVisibility(objectId) {
    console.log('👁️ Basculement de la visibilité de l\'objet:', objectId);
    
    // TODO: Implémenter l'appel API pour basculer la visibilité
    fetch('api/toggle_object_visibility.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            object_id: objectId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du basculement de la visibilité');
    });
}

/**
 * Basculer l'identification d'un objet
 */
function toggleObjectIdentification(objectId) {
    console.log('🔍 Basculement de l\'identification de l\'objet:', objectId);
    
    // TODO: Implémenter l'appel API pour basculer l'identification
    fetch('api/toggle_object_identification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            object_id: objectId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du basculement de l\'identification');
    });
}

/**
 * Supprimer un objet
 */
function removeObject(objectId) {
    console.log('🗑️ Suppression de l\'objet:', objectId);
    
    if (confirm('Êtes-vous sûr de vouloir supprimer cet objet ?')) {
        // TODO: Implémenter l'appel API pour supprimer l'objet
        fetch('api/remove_object.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                object_id: objectId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Recharger la page pour voir les changements
                location.reload();
            } else {
                alert('Erreur: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'objet');
        });
    }
}

/**
 * Supprimer un joueur
 */
function removePlayer(playerId) {
    console.log('🗑️ Suppression du joueur:', playerId);
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?')) {
        // TODO: Implémenter l'appel API pour supprimer le joueur
        fetch('api/remove_player.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                player_id: playerId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Recharger la page pour voir les changements
                location.reload();
            } else {
                alert('Erreur: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du joueur');
        });
    }
}

// ===== INITIALISATION PRINCIPALE =====

/**
 * Initialisation principale
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation de jdrmj.js');
    
    // Initialiser les variables globales
    initializeGlobalVariables();
    
    // Initialiser les différents systèmes
    initializeMonsterSearch();
    initializePoisonSearch();
    initializeMagicalItemSearch();
    initializePlayerManagement();
    initializeDiceSystem();
    initializeAccessModals();
    
    // Initialiser le système de tokens si la carte est présente
    if (document.getElementById('mapContainer')) {
        initializeTokenSystem();
        initializeTokenDragDrop();
        initializeObjectDragDrop();
    }
    
    console.log('✅ Initialisation terminée');
});
