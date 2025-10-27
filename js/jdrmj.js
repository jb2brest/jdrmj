/**
 * JDR 4 MJ - JavaScript principal
 * Regroupe toutes les fonctionnalités JavaScript de l'application
 */

// ===== GESTION DES PERSONNAGES =====

// ===== GESTION DES NPCs =====

/**
 * Fonctions HP supprimées - maintenant dans hp-management.js
 */

/**
 * Changement d'XP rapide sur un NPC
 */
function quickXpChange(amount, npcName) {
    const action = amount > 0 ? 'ajouter' : 'retirer';
    const absAmount = Math.abs(amount);
    if (confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${absAmount} points d'expérience à ${npcName} ?`)) {
        const npcId = getCurrentNpcId();
        updateNpcXp(npcId, amount > 0 ? 'add' : 'remove', absAmount);
    }
}

/**
 * API unifiée pour la gestion des points de vie
 * Supporte les PNJ, monstres et personnages joueurs
 */
function manageHp(targetId, targetType, action, amount = 0, newHp = 0) {
    const requestData = {
        target_id: targetId,
        target_type: targetType,
        action: action,
        amount: amount,
        new_hp: newHp
    };
    
    fetch('api/manage_hp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Mettre à jour l'affichage des PV si nécessaire
            if (typeof updateHpDisplay === 'function') {
                updateHpDisplay(data.current_hp, data.max_hp);
            }
            // Recharger la page pour les changements majeurs
            if (action === 'reset' || action === 'update') {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la gestion des PV:', error);
        showMessage('Erreur lors de la mise à jour des points de vie.', 'error');
    });
}

/**
 * Fonctions spécifiques pour chaque type de cible
 */
function updateNpcHp(npcId, action, amount) {
    manageHp(npcId, 'PNJ', action, amount);
}

function updateMonsterHp(monsterId, action, amount) {
    manageHp(monsterId, 'monstre', action, amount);
}

function updateCharacterHp(characterId, action, amount) {
    manageHp(characterId, 'PJ', action, amount);
}

/**
 * Met à jour l'XP d'un NPC via API
 */
function updateNpcXp(npcId, action, amount) {
    fetch('api/update_npc_xp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            npc_id: npcId,
            action: action,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de l\'XP');
    });
}

/**
 * Récupère l'ID du NPC actuel depuis l'URL
 */
function getCurrentNpcId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

/**
 * Équiper un objet (universel - personnages, PNJ, monstres)
 */
function equipItem(itemId) {
    fetch('api/equip_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showMessage(data.message || 'Erreur lors de l\'équipement', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showMessage('Erreur lors de l\'équipement', 'error');
    });
}

/**
 * Déséquiper un objet (universel - personnages, PNJ, monstres)
 */
function unequipItem(itemId) {
    fetch('api/unequip_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showMessage(data.message || 'Erreur lors du déséquipement', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showMessage('Erreur lors du déséquipement', 'error');
    });
}

/**
 * Supprimer un objet d'un personnage
 */
function dropItem(itemId, itemName) {
    if (confirm(`Supprimer ${itemName} de l'inventaire ?`)) {
        fetch('api/drop_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression');
        });
    }
}

/**
 * Fonction appelée par la modale HP
 */
function updateHP() {
    const newHp = document.getElementById('new_hp').value;
    const characterId = getCharacterIdFromUrl();
    
    if (newHp && characterId) {
        manageHp(characterId, 'PJ', 'update', 0, parseInt(newHp));
    }
}

/**
 * Fonction appelée par la modale XP
 */
function updateXP() {
    const newXp = document.getElementById('new_xp').value;
    const characterId = getCharacterIdFromUrl();
    
    if (newXp && characterId) {
        updateCharacterXp(characterId, parseInt(newXp));
    }
}

/**
 * Utiliser une rage (seulement pour les rages disponibles)
 */
function useRage(characterId, rageNumber) {
    if (confirm('Utiliser cette rage ?')) {
        fetch('api/manage_rage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                character_id: characterId,
                action: 'use'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage
                updateRageDisplay();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'utilisation de la rage');
        });
    }
}

/**
 * Mettre à jour l'affichage des rages
 */
function updateRageDisplay() {
    // Recharger la page pour mettre à jour l'affichage
    location.reload();
}

/**
 * Confirmer le transfert d'un objet
 */
function confirmTransfer() {
    const form = document.getElementById('transferForm');
    const formData = new FormData(form);
    
    fetch('api/transfer_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Objet transféré avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du transfert');
    });
}

/**
 * Déposer un objet dans le lieu actuel
 */
function dropItem(itemId, itemName) {
    if (confirm(`Déposer "${itemName}" dans le lieu actuel ?`)) {
        fetch('api/drop_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Objet déposé avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du dépôt');
        });
    }
}

/**
 * Initialiser le modal de transfert
 */
function initializeTransferModal() {
    const transferModal = document.getElementById('transferModal');
    if (transferModal) {
        transferModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            const itemType = button.getAttribute('data-item-type');
            const source = button.getAttribute('data-source');
            
            // Remplir les informations de base
            document.getElementById('transferItemName').textContent = itemName;
            document.getElementById('transferCurrentOwner').textContent = 'Personnage actuel';
            document.getElementById('transferItemId').value = itemId;
            document.getElementById('transferCurrentOwnerType').value = 'character';
            document.getElementById('transferSource').value = source;
            
            // Charger les cibles disponibles
            loadTransferTargets();
        });
    }
}

/**
 * Charger les cibles de transfert disponibles
 */
function loadTransferTargets() {
    const select = document.getElementById('transferTarget');
    select.innerHTML = '<option value="">Sélectionner une cible...</option>';
    
    // Pour l'instant, on ajoute des options basiques
    // TODO: Charger dynamiquement depuis l'API
    const options = [
        { value: 'campaign', text: 'Campagne' },
        { value: 'location', text: 'Lieu actuel' }
    ];
    
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        select.appendChild(optionElement);
    });
}

/**
 * Mettre à jour l'expérience d'un personnage via API
 */
function updateCharacterXp(characterId, newXp) {
    fetch('api/update_character_xp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            character_id: characterId,
            new_xp: newXp
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de l\'expérience');
    });
}

/**
 * Obtenir l'ID du personnage depuis l'URL
 */
function getCharacterIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return parseInt(urlParams.get('id'));
}

/**
 * Obtenir les points de vie actuels
 */
function getCurrentHp() {
    const hpElement = document.querySelector('.current-hp');
    return hpElement ? parseInt(hpElement.textContent) : 0;
}

/**
 * Obtenir les points de vie maximum
 */
function getMaxHp() {
    const maxHpElement = document.querySelector('.max-hp');
    return maxHpElement ? parseInt(maxHpElement.textContent) : 0;
}

/**
 * Obtenir l'expérience actuelle
 */
function getCurrentXp() {
    const xpElement = document.querySelector('.current-xp');
    return xpElement ? parseInt(xpElement.textContent) : 0;
}

/**
 * Gestion des filtres d'équipement
 */
function applyEquipmentFilters() {
    const weaponsChecked = document.getElementById('filter-weapons')?.checked ?? true;
    const armorChecked = document.getElementById('filter-armor')?.checked ?? true;
    const shieldChecked = document.getElementById('filter-shield')?.checked ?? true;
    const otherChecked = document.getElementById('filter-other')?.checked ?? true;
    
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const itemType = row.getAttribute('data-item-type');
        let shouldShow = false;
        
        if (itemType === 'weapon' && weaponsChecked) shouldShow = true;
        else if (itemType === 'armor' && armorChecked) shouldShow = true;
        else if (itemType === 'shield' && shieldChecked) shouldShow = true;
        else if (!['weapon', 'armor', 'shield'].includes(itemType) && otherChecked) shouldShow = true;
        
        row.style.display = shouldShow ? '' : 'none';
    });
}

function resetFilters() {
    // Réinitialiser tous les filtres d'équipement
    const filterIds = ['filter-weapons', 'filter-armor', 'filter-shield', 'filter-other'];
    filterIds.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    // Réinitialiser la recherche
    const searchInput = document.querySelector('input[type="search"]');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Réinitialiser le tri
    const table = document.querySelector('table');
    if (table) {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const tbody = table.querySelector('tbody');
        rows.sort((a, b) => {
            const nameA = a.cells[0].textContent.trim();
            const nameB = b.cells[0].textContent.trim();
            return nameA.localeCompare(nameB);
        });
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // Réafficher toutes les lignes
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
}

/**
 * Tri de tableau
 */
function sortTable(columnIndex) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = table.getAttribute('data-sort-direction') !== 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Essayer de convertir en nombre
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        } else {
            return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.setAttribute('data-sort-direction', isAscending ? 'asc' : 'desc');
}

/**
 * Gestion du modal de transfert d'objets
 */
function initTransferModal() {
    const transferModal = document.getElementById('transferModal');
    if (transferModal) {
        transferModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            const currentOwner = button.getAttribute('data-current-owner');
            const currentOwnerName = button.getAttribute('data-current-owner-name');
            const source = button.getAttribute('data-source');
            
            // Remplir les informations de base
            document.getElementById('transferItemName').textContent = itemName;
            document.getElementById('transferCurrentOwner').textContent = currentOwnerName;
            document.getElementById('transferItemId').value = itemId;
            document.getElementById('transferCurrentOwnerType').value = currentOwner;
            document.getElementById('transferSource').value = source;
            
            // Charger les cibles disponibles
            loadTransferTargets(currentOwner);
        });
    }
}

/**
 * Charger les cibles de transfert
 */
function loadTransferTargets(currentOwner) {
    const select = document.getElementById('transferTarget');
    if (!select) return;
    
    select.innerHTML = '<option value="">Chargement...</option>';
    
    // Simuler le chargement des cibles (à remplacer par un appel AJAX)
    setTimeout(() => {
        select.innerHTML = '<option value="">Sélectionner une cible...</option>';
        
        // Ajouter les personnages joueurs
        select.innerHTML += '<optgroup label="Personnages Joueurs">';
        select.innerHTML += '<option value="character_1">Hyphrédicte (Robin)</option>';
        select.innerHTML += '<option value="character_2">Lieutenant Cameron (MJ)</option>';
        select.innerHTML += '</optgroup>';
        
        // Ajouter les PNJ
        select.innerHTML += '<optgroup label="PNJ">';
        select.innerHTML += '<option value="npc_1">PNJ Test</option>';
        select.innerHTML += '</optgroup>';
        
        // Ajouter les monstres
        select.innerHTML += '<optgroup label="Monstres">';
        select.innerHTML += '<option value="monster_10">Aboleth #1</option>';
        select.innerHTML += '<option value="monster_11">Aboleth #2</option>';
        select.innerHTML += '</optgroup>';
    }, 500);
}

/**
 * Confirmer le transfert d'un objet
 */
function confirmTransfer() {
    const form = document.getElementById('transferForm');
    const target = document.getElementById('transferTarget').value;
    const itemName = document.getElementById('transferItemName').textContent;
    
    if (!target) {
        alert('Veuillez sélectionner une cible');
        return;
    }
    
    if (confirm(`Transférer ${itemName} vers la cible sélectionnée ?`)) {
        // Ici, on ferait l'appel AJAX pour transférer l'objet
        alert('Transfert effectué (fonctionnalité à implémenter)');
        const modal = bootstrap.Modal.getInstance(document.getElementById('transferModal'));
        modal.hide();
    }
}

/**
 * Upload de photo de profil
 */
function uploadPhoto() {
    const fileInput = document.getElementById('photoFile');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Veuillez sélectionner un fichier');
        return;
    }
    
    // Vérifier le type de fichier
    if (!file.type.startsWith('image/')) {
        alert('Veuillez sélectionner un fichier image');
        return;
    }
    
    // Vérifier la taille (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Le fichier est trop volumineux (max 5MB)');
        return;
    }
    
    const formData = new FormData();
    formData.append('photo', file);
    formData.append('character_id', getCharacterIdFromUrl());
    
    fetch('api/upload_character_photo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'upload');
    });
}

/**
 * Gestion des capacités
 */
function initCapabilities() {
    const capabilityItems = document.querySelectorAll('.capability-item');
    const capabilityDetail = document.getElementById('capability-detail');
    
    if (!capabilityDetail) return;
    
    capabilityItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer la classe active de tous les éléments
            capabilityItems.forEach(capability => capability.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
            
            // Récupérer les données de la capacité
            const capabilityData = JSON.parse(this.getAttribute('data-capability'));
            
            // Afficher les détails
            capabilityDetail.innerHTML = `
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="${capabilityData.icon} me-2"></i>${capabilityData.name}
                    </h6>
                    <p class="card-text">
                        <span class="badge bg-${capabilityData.color} me-2">${capabilityData.type}</span>
                    </p>
                    <p class="card-text">${capabilityData.description}</p>
                </div>
            `;
        });
    });
}

/**
 * Gestion des compétences
 */
function initSkills() {
    const skillItems = document.querySelectorAll('.skill-item');
    const skillDetail = document.getElementById('skill-detail');
    
    if (!skillDetail) return;
    
    // Données des compétences D&D 5e
    const skillsData = {
        'Athlétisme': {
            'caracteristic': 'Force',
            'description': 'Votre test d\'Athlétisme couvre les situations difficiles que vous rencontrez en escaladant, en sautant ou en nageant.',
            'examples': [
                'Escalader une falaise escarpée',
                'Sauter par-dessus un ravin',
                'Nager contre un courant fort',
                'Pousser une lourde pierre'
            ]
        },
        'Acrobaties': {
            'caracteristic': 'Dextérité',
            'description': 'Votre test d\'Acrobaties couvre votre tentative de rester debout dans une situation délicate.',
            'examples': [
                'Garder l\'équilibre sur une corde raide',
                'Atterrir sur vos pieds après une chute',
                'Effectuer des acrobaties',
                'Éviter une attaque en se baissant'
            ]
        },
        'Discrétion': {
            'caracteristic': 'Dextérité',
            'description': 'Votre test de Discrétion détermine si vous pouvez vous déplacer silencieusement et vous cacher.',
            'examples': [
                'Se cacher derrière un buisson',
                'Se faufiler derrière un ennemi',
                'Marcher silencieusement',
                'Se dissimuler dans l\'ombre'
            ]
        },
        'Escamotage': {
            'caracteristic': 'Dextérité',
            'description': 'Votre test d\'Escamotage détermine si vous pouvez subtiliser un objet ou effectuer des tours de passe-passe.',
            'examples': [
                'Voler une bourse',
                'Faire disparaître un objet',
                'Tricher aux cartes',
                'Cacher un objet sur soi'
            ]
        },
        'Histoire': {
            'caracteristic': 'Intelligence',
            'description': 'Votre test d\'Histoire mesure votre capacité à vous rappeler des légendes, des événements historiques, des dirigeants royaux, des guerres passées et des colonies récentes.',
            'examples': [
                'Se rappeler d\'un ancien roi',
                'Connaître l\'histoire d\'une guerre',
                'Identifier un artefact ancien',
                'Comprendre les traditions locales'
            ]
        },
        'Investigation': {
            'caracteristic': 'Intelligence',
            'description': 'Votre test d\'Investigation vous aide à déduire des choses basées sur des indices que vous trouvez.',
            'examples': [
                'Examiner une scène de crime',
                'Analyser un mécanisme complexe',
                'Déchiffrer un code',
                'Trouver des indices cachés'
            ]
        },
        'Nature': {
            'caracteristic': 'Intelligence',
            'description': 'Votre test de Nature mesure votre capacité à vous rappeler des informations sur le terrain, des plantes et des animaux, du temps et des cycles naturels.',
            'examples': [
                'Identifier une plante',
                'Prédire le temps',
                'Connaître les habitudes d\'un animal',
                'Naviguer dans la nature'
            ]
        },
        'Religion': {
            'caracteristic': 'Intelligence',
            'description': 'Votre test de Religion mesure votre capacité à vous rappeler des rituels, des prières, des déités et des pratiques religieuses.',
            'examples': [
                'Identifier un symbole religieux',
                'Connaître les rituels d\'une religion',
                'Reconnaître un clergé',
                'Comprendre les croyances locales'
            ]
        },
        'Dressage': {
            'caracteristic': 'Sagesse',
            'description': 'Votre test de Dressage détermine si vous pouvez calmer un animal domestique, garder une monture de guerre sous contrôle ou intuitivement deviner les intentions d\'un animal.',
            'examples': [
                'Calmer un cheval effrayé',
                'Entraîner un chien',
                'Comprendre le comportement d\'un animal',
                'Monter une créature sauvage'
            ]
        },
        'Médecine': {
            'caracteristic': 'Sagesse',
            'description': 'Votre test de Médecine vous permet de stabiliser un compagnon mourant ou de diagnostiquer une maladie.',
            'examples': [
                'Stabiliser un compagnon mourant',
                'Diagnostiquer une maladie',
                'Soigner une blessure',
                'Identifier un poison'
            ]
        },
        'Perception': {
            'caracteristic': 'Sagesse',
            'description': 'Votre test de Perception vous permet de repérer, entendre ou détecter autrement la présence de quelque chose.',
            'examples': [
                'Entendre des pas furtifs',
                'Repérer une embuscade',
                'Détecter un piège',
                'Observer des détails importants'
            ]
        },
        'Survie': {
            'caracteristic': 'Sagesse',
            'description': 'Votre test de Survie vous permet de suivre des pistes, de chasser, de guider votre groupe à travers des terres sauvages, d\'identifier des signes que des créatures ont passé par là.',
            'examples': [
                'Suivre une piste',
                'Trouver de la nourriture',
                'Se repérer dans la nature',
                'Prédire le temps'
            ]
        },
        'Intimidation': {
            'caracteristic': 'Charisme',
            'description': 'Votre test d\'Intimidation détermine si vous pouvez influencer les autres par des menaces, des actions hostiles et de la violence physique.',
            'examples': [
                'Menacer un bandit',
                'Faire parler un prisonnier',
                'Impressionner une foule',
                'Obtenir des informations par la peur'
            ]
        },
        'Persuasion': {
            'caracteristic': 'Charisme',
            'description': 'Votre test de Persuasion détermine si vous pouvez influencer les autres par la diplomatie, la négociation et la séduction.',
            'examples': [
                'Négocier un prix',
                'Convaincre un garde',
                'Séduire quelqu\'un',
                'Obtenir des informations amicalement'
            ]
        },
        'Représentation': {
            'caracteristic': 'Charisme',
            'description': 'Votre test de Représentation détermine si vous pouvez divertir une audience par la musique, la danse, l\'acting, la narration ou une autre forme de divertissement.',
            'examples': [
                'Jouer d\'un instrument',
                'Raconter une histoire',
                'Danser pour divertir',
                'Faire un spectacle'
            ]
        },
        'Supercherie': {
            'caracteristic': 'Charisme',
            'description': 'Votre test de Supercherie détermine si vous pouvez tromper les autres par la dissimulation, la tromperie ou la manipulation.',
            'examples': [
                'Mentir avec conviction',
                'Faire passer un faux document',
                'Se faire passer pour quelqu\'un d\'autre',
                'Tromper un garde'
            ]
        }
    };
    
    skillItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer la classe active de tous les éléments
            skillItems.forEach(skill => skill.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
            
            // Récupérer le nom de la compétence
            const skillName = this.getAttribute('data-skill');
            
            // Afficher les détails
            if (skillsData[skillName]) {
                const skill = skillsData[skillName];
                skillDetail.innerHTML = `
                    <div class="card-body">
                        <h6 class="card-title">${skillName}</h6>
                        <p class="card-text"><strong>Caractéristique :</strong> ${skill.caracteristic}</p>
                        <p class="card-text">${skill.description}</p>
                        <p class="card-text"><strong>Exemples :</strong></p>
                        <ul>
                            ${skill.examples.map(example => `<li>${example}</li>`).join('')}
                        </ul>
                    </div>
                `;
            } else {
                skillDetail.innerHTML = `
                    <div class="card-body">
                        <h6 class="card-title">${skillName}</h6>
                        <p class="text-muted">Aucune information détaillée disponible pour cette compétence.</p>
                    </div>
                `;
            }
        });
    });
}

/**
 * Réinitialiser les rages (long repos)
 */
function resetRages(characterId) {
    if (confirm('Effectuer un long repos pour récupérer toutes les rages ?')) {
        fetch('api/reset_rages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                character_id: characterId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la réinitialisation des rages');
        });
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initTransferModal();
    initCapabilities();
    initSkills();
});

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
    
    const mapImage = document.getElementById('mapImage');
    const tokenSidebar = document.getElementById('tokenSidebar');
    
    if (!mapImage || !tokenSidebar) {
        console.log('⚠️ Éléments de carte non trouvés pour les objets');
        return;
    }
    
    objectTokens.forEach(token => {
        // Positionner le token selon sa position actuelle
        positionTokenOnMap(token);
        
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
    
    // Événements sur la carte pour les objets
    mapImage.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    mapImage.addEventListener('drop', function(e) {
        e.preventDefault();
        
        if (draggedToken && draggedToken.dataset.tokenType === 'object') {
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
            
            console.log('🎯 Objet déposé sur la carte:', draggedToken.dataset.objectName, 'à', xPercent + '%', yPercent + '%');
        }
    });
    
    // Événements sur la sidebar pour les objets
    tokenSidebar.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    tokenSidebar.addEventListener('drop', function(e) {
        e.preventDefault();
        
        if (draggedToken && draggedToken.dataset.tokenType === 'object') {
            // Remettre le token dans la sidebar
            resetTokenToSidebar(draggedToken);
            
            // Sauvegarder la position
            saveTokenPosition(draggedToken);
            
            console.log('🎯 Objet remis dans la sidebar:', draggedToken.dataset.objectName);
        }
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
 * Initialiser les filtres d'équipement
 */
function initializeEquipmentFilters() {
    // Ajouter les événements aux filtres
    const filterIds = ['filter-weapons', 'filter-armor', 'filter-shield', 'filter-other'];
    filterIds.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            checkbox.addEventListener('change', applyEquipmentFilters);
        }
    });
    
    // Appliquer les filtres initiaux
    applyEquipmentFilters();
}

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
    initializeEquipmentFilters();
    initializeTransferModal();
    
    // Initialiser le système de tokens si la carte est présente
    if (document.getElementById('mapContainer')) {
        initializeTokenSystem();
        initializeTokenDragDrop();
        initializeObjectDragDrop();
    }
    
    console.log('✅ Initialisation terminée');
});

// =====================================================
// FONCTIONS POUR MANAGE_NPCS.PHP
// =====================================================

/**
 * Initialiser le système de gestion des entités
 */
function initializeEntityManagement() {
    console.log('🔧 Initialisation de la gestion des entités');
    
    // Initialiser les filtres dynamiques
    initializeFilterDependencies();
    
    // Initialiser la suppression d'entités
    initializeEntityDeletion();
    
    console.log('✅ Gestion des entités initialisée');
}

/**
 * Initialiser les dépendances entre filtres
 */
function initializeFilterDependencies() {
    const worldSelect = document.getElementById('world');
    const countrySelect = document.getElementById('country');
    const regionSelect = document.getElementById('region');
    const placeSelect = document.getElementById('place');
    
    if (!worldSelect || !countrySelect || !regionSelect || !placeSelect) {
        return;
    }
    
    // Données des options (récupérées depuis PHP)
    const countries = window.filterData?.countries || [];
    const regions = window.filterData?.regions || [];
    const places = window.filterData?.places || [];
    
    function filterOptions(select, data, filterField, filterValue) {
        const currentValue = select.value;
        select.innerHTML = '<option value="">' + (select.id === 'region' ? 'Toutes' : 'Tous') + '</option>';
        
        data.forEach(item => {
            if (!filterValue || item[filterField] == filterValue) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name || item.title;
                if (item.id == currentValue) option.selected = true;
                select.appendChild(option);
            }
        });
    }
    
    // Gestionnaire pour le monde
    worldSelect.addEventListener('change', function() {
        filterOptions(countrySelect, countries, 'world_id', this.value);
        filterOptions(regionSelect, regions, 'world_id', this.value);
        filterOptions(placeSelect, places, 'world_id', this.value);
    });
    
    // Gestionnaire pour le pays
    countrySelect.addEventListener('change', function() {
        filterOptions(regionSelect, regions, 'country_id', this.value);
        filterOptions(placeSelect, places, 'country_id', this.value);
    });
    
    // Gestionnaire pour la région
    regionSelect.addEventListener('change', function() {
        filterOptions(placeSelect, places, 'region_id', this.value);
    });
}

/**
 * Initialiser la suppression d'entités
 */
function initializeEntityDeletion() {
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const entityNameSpan = document.getElementById('entityName');
    
    if (!deleteModal || !confirmDeleteBtn || !entityNameSpan) {
        return;
    }
    
    let entityToDelete = null;
    
    // Gestionnaire pour le bouton de confirmation
    confirmDeleteBtn.addEventListener('click', function() {
        if (entityToDelete) {
            deleteEntityConfirmed(entityToDelete.id, entityToDelete.name, entityToDelete.type);
        }
    });
    
    // Fonction globale pour supprimer une entité
    window.deleteEntity = function(entityId, entityName, entityType) {
        entityToDelete = { id: entityId, name: entityName, type: entityType };
        entityNameSpan.textContent = entityName;
        
        const modal = new bootstrap.Modal(deleteModal);
        modal.show();
    };
}

/**
 * Confirmer la suppression d'une entité
 */
function deleteEntityConfirmed(entityId, entityName, entityType) {
    console.log(`🗑️ Suppression de l'entité ${entityId} (${entityName})`);
    
    // Afficher un indicateur de chargement
    const confirmBtn = document.getElementById('confirmDelete');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Suppression...';
    confirmBtn.disabled = true;
    
    // Appel AJAX pour supprimer l'entité
    fetch('api/delete_entity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entity_id=${entityId}&entity_type=${entityType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer l'élément de la page
            const entityElement = document.querySelector(`[data-entity-id="${entityId}"]`);
            if (entityElement) {
                entityElement.remove();
            }
            
            // Afficher un message de succès
            showNotification('Entité supprimée avec succès', 'success');
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) {
                modal.hide();
            }
            
            // Vérifier s'il reste des entités
            const entitiesList = document.getElementById('entitiesList');
            if (entitiesList && entitiesList.children.length === 0) {
                location.reload(); // Recharger pour afficher le message "Aucune entité trouvée"
            }
        } else {
            showNotification(data.error || 'Erreur lors de la suppression', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression:', error);
        showNotification('Erreur de connexion', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Ajouter à la page
    document.body.appendChild(notification);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialiser la gestion des entités si on est sur la page manage_npcs
if (document.getElementById('entitiesContainer')) {
    initializeEntityManagement();
}

// ========================================
// GESTION DE L'ATTRIBUTION D'OBJETS
// ========================================

/**
 * Fonction globale pour l'attribution d'objets
 * @param {number} objectId - ID de l'objet à attribuer
 * @param {string} objectName - Nom de l'objet à attribuer
 */
window.assignObject = function(objectId, objectName) {
    document.getElementById('assignObjectId').value = objectId;
    document.getElementById('assignObjectName').textContent = objectName;
    document.getElementById('assignTargetId').innerHTML = '<option value="">Chargement des entités présentes...</option>';
    document.getElementById('assignTargetId').disabled = true;
    document.getElementById('assignQuantity').value = 1;
    
    // Charger toutes les entités présentes dans le lieu
    loadAllAssignTargets();
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('assignObjectModal'));
    modal.show();
}

/**
 * Fonction globale pour charger toutes les entités présentes dans le lieu
 */
window.loadAllAssignTargets = function() {
    const select = document.getElementById('assignTargetId');
    
    // Récupérer l'ID du lieu depuis l'URL ou un élément de la page
    const urlParams = new URLSearchParams(window.location.search);
    const placeId = urlParams.get('id') || document.querySelector('[data-place-id]')?.dataset.placeId;
    
    if (!placeId) {
        console.error('ID du lieu non trouvé');
        select.innerHTML = '<option value="">Erreur: ID du lieu manquant</option>';
        select.disabled = false;
        return;
    }
    
    // Charger toutes les entités via AJAX
    fetch(`api/get_assign_targets.php?place_id=${placeId}`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Sélectionner une entité...</option>';
            
            if (data.success && data.targets && data.targets.length > 0) {
                data.targets.forEach(target => {
                    const option = document.createElement('option');
                    option.value = target.id;
                    option.textContent = target.name;
                    option.dataset.type = target.type; // Stocker le type pour l'API d'attribution
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Aucune entité présente dans ce lieu</option>';
            }
            
            select.disabled = false;
        })
        .catch(error => {
            console.error('Erreur lors du chargement:', error);
            select.innerHTML = '<option value="">Erreur de chargement</option>';
            select.disabled = false;
        });
}

/**
 * Initialiser la gestion de l'attribution d'objets
 */
function initializeObjectAssignment() {
    // Gestion de la soumission du formulaire d'attribution
    const assignObjectForm = document.getElementById('assignObjectForm');
    if (assignObjectForm) {
        assignObjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Ajouter le type de cible depuis l'option sélectionnée
            const targetSelect = document.getElementById('assignTargetId');
            const selectedOption = targetSelect.options[targetSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.type) {
                formData.append('target_type', selectedOption.dataset.type);
            }
            
            fetch('api/assign_object.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Objet attribué avec succès !');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'attribution');
            });
        });
    }
}

// Initialiser la gestion de l'attribution d'objets si le modal est présent
if (document.getElementById('assignObjectModal')) {
    initializeObjectAssignment();
}

// ========================================
// GESTION DE L'AJOUT D'OBJETS
// ========================================

/**
 * Initialiser la gestion de l'ajout d'objets
 */
function initializeObjectAddition() {
    const addObjectForm = document.getElementById('addObjectForm');
    if (addObjectForm) {
        addObjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add_object.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Objet ajouté avec succès !');
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addObjectModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Recharger la page pour afficher le nouvel objet
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout de l\'objet');
            });
        });
    }
}

// Initialiser la gestion de l'ajout d'objets si le modal est présent
if (document.getElementById('addObjectModal')) {
    initializeObjectAddition();
}

// ===== FONCTIONS SPÉCIFIQUES À VIEW_NPC.PHP =====

/**
 * Gestion du modal de transfert d'objets
 */
function initializeTransferModal() {
    const transferModal = document.getElementById('transferModal');
    if (transferModal) {
        transferModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            const currentOwner = button.getAttribute('data-current-owner');
            const currentOwnerName = button.getAttribute('data-current-owner-name');
            const source = button.getAttribute('data-source');
            
            // Remplir les informations de base
            document.getElementById('transferItemName').textContent = itemName;
            document.getElementById('transferCurrentOwner').textContent = currentOwnerName;
            document.getElementById('transferItemId').value = itemId;
            document.getElementById('transferCurrentOwnerType').value = currentOwner;
            document.getElementById('transferSource').value = source;
            
            // Charger les cibles disponibles
            loadTransferTargets(currentOwner);
        });
    }
}

/**
 * Charge les cibles de transfert disponibles
 */
function loadTransferTargets(currentOwner) {
    const select = document.getElementById('transferTarget');
    if (!select) return;
    
    select.innerHTML = '<option value="">Chargement...</option>';
    
    // Simuler le chargement des cibles (à remplacer par un appel AJAX)
    setTimeout(() => {
        select.innerHTML = '<option value="">Sélectionner une cible...</option>';
        
        // Ajouter les personnages joueurs
        select.innerHTML += '<optgroup label="Personnages Joueurs">';
        select.innerHTML += '<option value="character_1">Hyphrédicte (Robin)</option>';
        select.innerHTML += '<option value="character_2">Lieutenant Cameron (MJ)</option>';
        select.innerHTML += '</optgroup>';
        
        // Ajouter les PNJ
        select.innerHTML += '<optgroup label="PNJ">';
        select.innerHTML += '<option value="npc_1">PNJ Test</option>';
        select.innerHTML += '</optgroup>';
        
        // Ajouter les monstres
        select.innerHTML += '<optgroup label="Monstres">';
        select.innerHTML += '<option value="monster_10">Aboleth #1</option>';
        select.innerHTML += '<option value="monster_11">Aboleth #2</option>';
        select.innerHTML += '</optgroup>';
    }, 500);
}

/**
 * Confirme le transfert d'un objet
 */
function confirmTransfer() {
    const form = document.getElementById('transferForm');
    const target = document.getElementById('transferTarget').value;
    const itemName = document.getElementById('transferItemName').textContent;
    
    if (!target) {
        alert('Veuillez sélectionner une cible pour le transfert.');
        return;
    }
    
    const targetName = document.getElementById('transferTarget').selectedOptions[0].text;
    
    if (confirm(`Confirmer le transfert de "${itemName}" vers "${targetName}" ?`)) {
        form.submit();
    }
}

/**
 * Upload de photo de profil
 */
function uploadPhoto() {
    const form = document.getElementById('photoForm');
    const fileInput = document.getElementById('profile_photo');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Veuillez sélectionner un fichier image.');
        return;
    }
    
    const file = fileInput.files[0];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (file.size > maxSize) {
        alert('Le fichier est trop volumineux. Taille maximale : 10MB.');
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format de fichier non supporté. Utilisez JPG, PNG ou GIF.');
        return;
    }
    
    if (confirm('Confirmer l\'upload de cette photo de profil ?')) {
        form.submit();
    }
}



/**
 * Dépose un objet dans le lieu actuel
 */
function dropItem(itemId, itemName) {
    if (!confirm(`Êtes-vous sûr de vouloir déposer "${itemName}" dans le lieu actuel ?`)) {
        return;
    }

    fetch('drop_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            item_name: itemName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Objet déposé avec succès dans le lieu actuel !');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du dépôt de l\'objet');
    });
}

/**
 * Gère les rages d'un personnage
 */
function toggleRage(characterId, rageNumber) {
    const rageSymbol = document.querySelector(`[data-rage="${rageNumber}"]`);
    const isUsed = rageSymbol.classList.contains('used');
    
    const action = isUsed ? 'free' : 'use';
    
    fetch('manage_rage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            character_id: characterId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'affichage
            updateRageDisplay();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de la rage');
    });
}

/**
 * Réinitialise toutes les rages
 */
function resetRages(characterId) {
    if (confirm('Effectuer un long repos ? Cela récupérera toutes les rages.')) {
        fetch('manage_rage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                character_id: characterId,
                action: 'reset'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage
                updateRageDisplay();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la réinitialisation des rages');
        });
    }
}

/**
 * Met à jour l'affichage des rages
 */
function updateRageDisplay() {
    // Recharger la page pour mettre à jour l'affichage
    window.location.reload();
}

// Variables pour le tri
let currentSortColumn = -1;
let currentSortDirection = 'asc';

/**
 * Trie le tableau d'équipement
 */
function sortTable(columnIndex) {
    const table = document.getElementById('equipmentTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Déterminer la direction du tri
    if (currentSortColumn === columnIndex) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortDirection = 'asc';
        currentSortColumn = columnIndex;
    }
    
    // Trier les lignes
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        
        if (currentSortDirection === 'asc') {
            return aText.localeCompare(bText);
        } else {
            return bText.localeCompare(aText);
        }
    });
    
    // Réorganiser les lignes dans le DOM
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour les icônes de tri
    updateSortIcons(columnIndex);
}

/**
 * Met à jour les icônes de tri
 */
function updateSortIcons(activeColumn) {
    const headers = document.querySelectorAll('#equipmentTable th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('i');
        if (index === activeColumn) {
            icon.className = currentSortDirection === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
        } else {
            icon.className = 'fas fa-sort ms-1';
        }
    });
}

/**
 * Filtre le tableau d'équipement
 */
function filterTable() {
    const searchTerm = document.getElementById('equipmentSearch').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value;
    const equippedFilter = document.getElementById('equippedFilter').value;
    
    const table = document.getElementById('equipmentTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const type = row.dataset.type;
        const equipped = row.dataset.equipped;
        
        let showRow = true;
        
        // Filtre de recherche
        if (searchTerm && !name.includes(searchTerm)) {
            showRow = false;
        }
        
        // Filtre de type
        if (typeFilter && type !== typeFilter) {
            showRow = false;
        }
        
        // Filtre d'état d'équipement
        if (equippedFilter && equipped !== equippedFilter) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

/**
 * Réinitialise les filtres
 */
function resetFilters() {
    document.getElementById('equipmentSearch').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('equippedFilter').value = '';
    filterTable();
}

/**
 * Initialise les gestionnaires d'événements pour les filtres
 */
function initializeEquipmentFilters() {
    const searchInput = document.getElementById('equipmentSearch');
    const typeSelect = document.getElementById('typeFilter');
    const equippedSelect = document.getElementById('equippedFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', filterTable);
    }
    if (equippedSelect) {
        equippedSelect.addEventListener('change', filterTable);
    }
}

/**
 * Initialise la gestion des compétences
 */
function initializeSkillsManagement() {
    const skillItems = document.querySelectorAll('.skill-item');
    const skillDetail = document.getElementById('skill-detail');
    
    // Base de données des compétences
    const skillsData = {
        'Athlétisme': {
            'caracteristic': 'Force',
            'description': 'Votre test d\'Athlétisme couvre les situations difficiles que vous rencontrez en escaladant, en sautant ou en nageant.',
            'examples': [
                'Escalader une falaise escarpée',
                'Sauter par-dessus un ravin',
                'Nager contre un courant fort',
                'Pousser une lourde pierre'
            ]
        },
        'Intimidation': {
            'caracteristic': 'Charisme',
            'description': 'Quand vous tentez d\'influencer quelqu\'un par la menace, l\'hostilité ou la violence, le MJ peut vous demander de faire un test d\'Intimidation.',
            'examples': [
                'Menacer un garde pour qu\'il vous laisse passer',
                'Faire parler un prisonnier',
                'Impressionner des bandits',
                'Obtenir des informations par la peur'
            ]
        },
        'Nature': {
            'caracteristic': 'Intelligence',
            'description': 'Votre test d\'Intelligence (Nature) mesure votre capacité à vous rappeler des informations utiles sur le terrain, les plantes et les animaux, le temps et les cycles naturels.',
            'examples': [
                'Identifier une plante vénéneuse',
                'Prédire le temps qu\'il va faire',
                'Reconnaître les traces d\'un animal',
                'Trouver de l\'eau potable'
            ]
        }
    };
    
    skillItems.forEach(item => {
        item.addEventListener('click', function() {
            // Retirer la classe active de tous les éléments
            skillItems.forEach(skill => skill.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
            
            // Récupérer le nom de la compétence
            const skillName = this.querySelector('h6').textContent;
            
            // Afficher les détails
            if (skillDetail && skillsData[skillName]) {
                const skillData = skillsData[skillName];
                skillDetail.innerHTML = `
                    <div class="card-body">
                        <h6>${skillName}</h6>
                        <p><strong>Caractéristique :</strong> ${skillData.caracteristic}</p>
                        <p>${skillData.description}</p>
                        <h6>Exemples d'utilisation :</h6>
                        <ul>
                            ${skillData.examples.map(example => `<li>${example}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
        });
    });
}

/**
 * Initialise la gestion des capacités
 */
function initializeCapabilitiesManagement() {
    const capabilityItems = document.querySelectorAll('.capability-item');
    const capabilityDetail = document.getElementById('capability-detail');
    
    // Vérifier que les éléments existent
    if (capabilityItems.length === 0) {
        console.log('Aucun élément .capability-item trouvé');
        return;
    }
    
    // Base de données des capacités
    const capabilitiesData = {
        'Rage': {
            'description': 'En combat, vous pouvez entrer dans un état de rage déchaînée. Pendant votre rage, si vous n\'avez pas porté d\'armure lourde depuis le début de votre dernier tour, vous gagnez un bonus de +2 aux jets de dégâts des attaques de corps à corps avec des armes de Force.',
            'type': 'Classe',
            'level': '1'
        },
        'Défense sans armure': {
            'description': 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + modificateur de Dextérité + modificateur de Constitution.',
            'type': 'Classe',
            'level': '1'
        }
    };
    
    capabilityItems.forEach(item => {
        item.addEventListener('click', function() {
            // Retirer la classe active de tous les éléments
            capabilityItems.forEach(capability => capability.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
            
            // Récupérer le nom de la capacité
            const capabilityNameElement = this.querySelector('strong.text-primary');
            const capabilityName = capabilityNameElement ? capabilityNameElement.textContent : 'Capacité inconnue';
            
            // Afficher les détails
            if (capabilityDetail && capabilitiesData[capabilityName]) {
                const capabilityData = capabilitiesData[capabilityName];
                capabilityDetail.innerHTML = `
                    <div class="card-body">
                        <h6>${capabilityName}</h6>
                        <p><strong>Type :</strong> ${capabilityData.type}</p>
                        <p><strong>Niveau :</strong> ${capabilityData.level}</p>
                        <p class="card-text">${capabilityData.description}</p>
                    </div>
                `;
            } else if (capabilityDetail) {
                // Afficher un message si la capacité n'est pas dans la base de données
                capabilityDetail.innerHTML = `
                    <div class="card-body">
                        <h6>${capabilityName}</h6>
                        <p class="text-muted">Informations détaillées non disponibles pour cette capacité.</p>
                    </div>
                `;
            }
        });
    });
}

/**
 * Fonctions utilitaires pour la gestion des HP
 */
function getCurrentNpcId() {
    // Récupérer l'ID du NPC depuis l'URL ou un élément de la page
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || document.querySelector('[data-npc-id]')?.dataset.npcId;
}

function getCurrentCharacterId() {
    // Récupérer l'ID du personnage depuis l'URL ou un élément de la page
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || document.querySelector('[data-character-id]')?.dataset.characterId;
}

function getCurrentMonsterId() {
    // Récupérer l'ID du monstre depuis l'URL ou un élément de la page
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || document.querySelector('[data-monster-id]')?.dataset.monsterId;
}

// Fin du fichier - toutes les fonctions HP sont maintenant dans hp-management.js

/**
 * Mettre à jour l'affichage des points de vie
 */
function updateHpDisplay(currentHp, maxHp = null) {
    // Mettre à jour le champ de saisie des PV actuels
    const currentHpInput = document.querySelector('input[name="current_hp"]');
    if (currentHpInput) {
        currentHpInput.value = currentHp;
    }
    
    // Mettre à jour l'affichage des PV si maxHp est fourni
    if (maxHp !== null) {
        const maxHpInput = document.querySelector('input[name="max_hp"]');
        if (maxHpInput) {
            maxHpInput.value = maxHp;
        }
    }
    
    // Mettre à jour l'affichage textuel des PV
    const hpDisplay = document.querySelector('.hp-display');
    if (hpDisplay) {
        const maxHpValue = maxHp || document.querySelector('input[name="max_hp"]')?.value || currentHp;
        hpDisplay.textContent = `${currentHp}/${maxHpValue}`;
    }
}

/**
 * Mettre à jour les points d'expérience
 */
// Fin du fichier - toutes les fonctions XP sont maintenant dans xp-management.js

/**
 * Fonction utilitaire pour formater les nombres
 */
function number_format(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

/**
 * Uploader une photo de profil
 */
function uploadPhoto(npcId, typeCible = 'PNJ') {
    // Récupérer l'input de fichier
    const fileInput = document.querySelector('input[type="file"][name="profile_photo"]');
    if (!fileInput) {
        showMessage('Champ de fichier non trouvé.', 'error');
        return;
    }
    
    const file = fileInput.files[0];
    if (!file) {
        showMessage('Aucun fichier sélectionné.', 'error');
        return;
    }
    
    // Vérifier la taille du fichier (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showMessage('La photo est trop volumineuse (max 10MB).', 'error');
        return;
    }
    
    // Vérifier le type de fichier
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Format de fichier non supporté. Utilisez JPG, PNG ou GIF.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('npc_id', npcId);
    formData.append('profile_photo', file);
    formData.append('type_cible', typeCible);
    
    // Afficher un indicateur de chargement
    const submitButton = document.querySelector('button[type="submit"]');
    const originalText = submitButton ? submitButton.textContent : '';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Upload en cours...';
    }
    
    fetch('api/upload_photo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Mettre à jour l'affichage de la photo
            updatePhotoDisplay(data.photo_path);
            // Fermer la modale automatiquement
            const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'upload:', error);
        showMessage('Erreur lors de l\'upload de la photo.', 'error');
    })
    .finally(() => {
        // Restaurer le bouton
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
        // Vider le champ de fichier
        if (fileInput) {
            fileInput.value = '';
        }
    });
}

/**
 * Mettre à jour l'affichage de la photo de profil
 */
function updatePhotoDisplay(photoPath) {
    console.log('updatePhotoDisplay appelé avec:', photoPath);
    console.log('window.location.origin:', window.location.origin);
    console.log('window.location.pathname:', window.location.pathname);
    
    // Construire l'URL complète (photoPath est relatif au projet)
    const baseUrl = window.location.origin;
    const projectPath = window.location.pathname.split('/').slice(0, -1).join('/');
    const fullImageUrl = baseUrl + projectPath + '/' + photoPath;
    
    console.log('URL complète construite:', fullImageUrl);
    
    // Mettre à jour l'image de profil
    const profileImage = document.getElementById('npc-profile-photo');
    if (profileImage) {
        console.log('Image de profil trouvée, mise à jour...');
        profileImage.src = fullImageUrl + '?t=' + Date.now(); // Cache busting
        console.log('Nouvelle src:', profileImage.src);
    } else {
        console.log('Image de profil non trouvée');
    }
    
    // Mettre à jour l'image dans le modal ou autres endroits
    const modalImages = document.querySelectorAll('.modal .profile-image, .modal .character-image');
    console.log('Images dans modals trouvées:', modalImages.length);
    modalImages.forEach((img, index) => {
        img.src = fullImageUrl + '?t=' + Date.now();
        console.log(`Modal image ${index} mise à jour:`, img.src);
    });
}

/**
 * Transférer un objet via l'API
 */
function transferObject(itemId, target, notes, source, npcId) {
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('target', target);
    formData.append('notes', notes);
    formData.append('source', source);
    formData.append('npc_id', npcId);
    
    fetch('api/transferObject.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher le message de succès
            showMessage(data.message, 'success');
            // Recharger la page pour mettre à jour les données
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Afficher le message d'erreur
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors du transfert:', error);
        showMessage('Erreur lors du transfert de l\'objet.', 'error');
    });
}

/**
 * Afficher un message à l'utilisateur
 */
function showMessage(message, type) {
    // Créer un élément de message
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer le message en haut de la page
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(messageDiv, container.firstChild);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// ===== FONCTIONS POUR GESTION DES NPCs =====

/**
 * Basculer l'état de rage d'un NPC
 */
function toggleRage(targetId, targetType, rageIndex) {
    fetch('api/toggle_rage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            target_id: targetId,
            target_type: targetType,
            rage_index: parseInt(rageIndex)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'affichage de la rage
            updateRageDisplay(targetId, data.used_rages, data.total_rages);
        } else {
            showMessage(data.message || 'Erreur lors de la gestion de la rage', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showMessage('Erreur lors de la gestion de la rage', 'error');
    });
}

/**
 * Réinitialiser les rages d'un NPC
 */
function resetRages(npcId) {
    if (confirm('Réinitialiser toutes les rages de ce NPC ?')) {
        fetch('api/reset_rage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                npc_id: npcId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage de la rage
                updateRageDisplay(npcId, data.used_rages, data.total_rages);
                showMessage('Rages réinitialisées', 'success');
            } else {
                showMessage(data.message || 'Erreur lors de la réinitialisation', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Erreur lors de la réinitialisation', 'error');
        });
    }
}

/**
 * Mettre à jour l'affichage des rages
 */
function updateRageDisplay(npcId, usedRages, totalRages) {
    // Filtrer les rages pour ce NPC spécifique
    const rageSymbols = document.querySelectorAll(`[data-npc-id="${npcId}"][data-rage]`);
    rageSymbols.forEach((symbol, index) => {
        const rageIndex = parseInt(symbol.dataset.rage);
        if (rageIndex <= usedRages) {
            symbol.classList.remove('available');
            symbol.classList.add('used');
        } else {
            symbol.classList.remove('used');
            symbol.classList.add('available');
        }
    });
    
    // Mettre à jour le badge d'information
    const rageInfo = document.querySelector(`[data-npc-id="${npcId}"] .rage-info .badge`);
    if (rageInfo) {
        const available = totalRages - usedRages;
        rageInfo.textContent = `${available}/${totalRages} rages disponibles`;
    }
}



/**
 * Déposer un objet
 */
function dropItem(itemId, itemName) {
    if (confirm(`Déposer l'objet "${itemName}" dans le lieu actuel ?`)) {
        fetch('api/drop_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage(data.message || 'Erreur lors du dépôt', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Erreur lors du dépôt', 'error');
        });
    }
}

/**
 * Réinitialiser les filtres de la table
 */
function resetFilters() {
    // Réinitialiser les champs de recherche
    const searchInputs = document.querySelectorAll('input[type="search"]');
    searchInputs.forEach(input => {
        input.value = '';
    });
    
    // Réinitialiser les sélecteurs
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        select.selectedIndex = 0;
    });
    
    // Recharger la table
    filterTable();
}

/**
 * Trier une table
 */
function sortTable(columnIndex) {
    const table = document.querySelector('table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.dataset.sortColumn === columnIndex.toString() ? 
        table.dataset.sortDirection !== 'asc' : true;
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex]?.textContent.trim() || '';
        const bText = b.cells[columnIndex]?.textContent.trim() || '';
        
        const comparison = aText.localeCompare(bText, 'fr', { numeric: true });
        return isAscending ? comparison : -comparison;
    });
    
    // Mettre à jour les données de tri
    table.dataset.sortColumn = columnIndex.toString();
    table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
    
    // Réorganiser les lignes
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour les icônes de tri
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('i');
        if (icon) {
            icon.className = index === columnIndex ? 
                (isAscending ? 'fas fa-sort-up' : 'fas fa-sort-down') : 
                'fas fa-sort';
        }
    });
}

/**
 * Filtrer la table
 */
function filterTable() {
    const table = document.querySelector('table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    const searchInputs = document.querySelectorAll('input[type="search"]');
    const selects = document.querySelectorAll('select');
    
    rows.forEach(row => {
        let show = true;
        
        // Filtrer par champs de recherche
        searchInputs.forEach((input, index) => {
            const searchTerm = input.value.toLowerCase();
            const cellText = row.cells[index]?.textContent.toLowerCase() || '';
            if (searchTerm && !cellText.includes(searchTerm)) {
                show = false;
            }
        });
        
        // Filtrer par sélecteurs
        selects.forEach((select, index) => {
            const filterValue = select.value;
            if (filterValue && filterValue !== '') {
                const cellText = row.cells[index]?.textContent.trim() || '';
                if (cellText !== filterValue) {
                    show = false;
                }
            }
        });
        
        row.style.display = show ? '' : 'none';
    });
}

/**
 * Confirmer le transfert d'objet
 */
function confirmTransfer() {
    const itemId = document.getElementById('transferItemId').value;
    const target = document.getElementById('transferTarget').value;
    const notes = document.getElementById('transferNotes').value;
    const source = document.getElementById('transferSource').value;
    const npcId = document.getElementById('transferNpcId').value;
    
    if (!target) {
        showMessage('Veuillez sélectionner une cible', 'error');
        return;
    }
    
    transferObject(itemId, target, notes, source, npcId);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('transferModal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * Mettre à jour l'image de profil affichée
 */
function updateProfileImage(imageUrl) {
    const profileImage = document.getElementById('profile-photo');
    if (profileImage) {
        profileImage.src = imageUrl;
    }
}

/**
 * Uploader une photo de profil (PJ ou PNJ)
 */
function uploadPhoto(targetId, targetType) {
    const fileInput = document.getElementById('profile_photo');
    const file = fileInput.files[0];
    
    if (!file) {
        showMessage('Veuillez sélectionner un fichier', 'error');
        return;
    }
    
    const formData = new FormData();
    
    if (targetType === 'PJ') {
        formData.append('photo', file);
        formData.append('character_id', targetId);
    } else {
        formData.append('profile_photo', file);
        formData.append('npc_id', targetId);
    }
    
    // Utiliser l'API appropriée selon le type de cible
    const apiEndpoint = targetType === 'PJ' ? 'api/upload_character_photo.php' : 'api/update_npc_photo.php';
    
    fetch(apiEndpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            updateProfileImage(data.image_url);
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            showMessage(data.message || 'Erreur lors de l\'upload', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showMessage('Erreur lors de l\'upload', 'error');
    });
}

/**
 * Initialiser les gestionnaires d'événements pour les NPCs
 */
function initializeNpcEventHandlers() {
    // Gestionnaires pour les boutons de rage
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="toggle"]')) {
            const element = e.target.closest('[data-action="toggle"]');
            const targetId = element.dataset.targetId;
            const targetType = element.dataset.targetType;
            const rageIndex = element.dataset.rage;
            toggleRage(targetId, targetType, rageIndex);
        }
        
        if (e.target.closest('[data-action="reset"]')) {
            const element = e.target.closest('[data-action="reset"]');
            const npcId = element.dataset.npcId;
            resetRages(npcId);
        }
    });
    
    // Gestionnaires pour les boutons de dégâts/soins/XP
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="damage"]')) {
            const element = e.target.closest('[data-action="damage"]');
            const amount = parseInt(element.dataset.amount);
            const npcName = element.dataset.npcName;
            quickDamage(amount, npcName);
        }
        
        if (e.target.closest('[data-action="heal"]')) {
            const element = e.target.closest('[data-action="heal"]');
            const amount = parseInt(element.dataset.amount);
            const npcName = element.dataset.npcName;
            quickHeal(amount, npcName);
        }
        
        if (e.target.closest('[data-action="xp"]')) {
            const element = e.target.closest('[data-action="xp"]');
            const amount = parseInt(element.dataset.amount);
            const npcName = element.dataset.npcName;
            quickXpChange(amount, npcName);
        }
    });
    
    // Gestionnaires pour les boutons d'équipement
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="equip"]')) {
            const element = e.target.closest('[data-action="equip"]');
            const npcId = element.dataset.npcId;
            const itemName = element.dataset.itemName;
            const itemType = element.dataset.itemType;
            const slot = element.dataset.slot;
            equipItem(npcId, itemName, itemType, slot);
        }
        
        if (e.target.closest('[data-action="unequip"]')) {
            const element = e.target.closest('[data-action="unequip"]');
            const npcId = element.dataset.npcId;
            const itemName = element.dataset.itemName;
            unequipItem(npcId, itemName);
        }
        
        if (e.target.closest('[data-action="drop"]')) {
            const element = e.target.closest('[data-action="drop"]');
            const itemId = element.dataset.itemId;
            const itemName = element.dataset.itemName;
            dropItem(itemId, itemName);
        }
    });
    
    // Gestionnaires pour les boutons de tri et filtre
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-sort]')) {
            const element = e.target.closest('[data-sort]');
            const columnIndex = parseInt(element.dataset.sort);
            sortTable(columnIndex);
        }
        
        if (e.target.closest('[data-action="reset-filters"]')) {
            resetFilters();
        }
    });
    
    // Gestionnaires pour les modals
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="confirm-transfer"]')) {
            confirmTransfer();
        }
        
        if (e.target.closest('[data-action="upload-photo"]')) {
            const element = e.target.closest('[data-action="upload-photo"]');
            const targetId = element.dataset.targetId;
            const targetType = element.dataset.targetType;
            uploadPhoto(targetId, targetType);
        }
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeTransferModal();
    initializeEquipmentFilters();
    // initializeNpcEventHandlers(); // Supprimé car appelé dans view_npc.php
    initializeSkillsManagement();
    initializeCapabilitiesManagement();
});
