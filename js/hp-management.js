/**
 * Fonctions unifiées pour la gestion des points de vie
 * Supporte les PNJ, monstres et personnages joueurs
 */

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
            // Mettre à jour l'affichage des PV dans le modal
            updateHpModalDisplay(data.current_hp, data.max_hp);
            // Mettre à jour l'affichage général si la fonction existe
            if (typeof updateHpDisplay === 'function') {
                updateHpDisplay(data.current_hp, data.max_hp);
            }
            // Ne plus recharger la page automatiquement - mise à jour dynamique
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

/**
 * Fonction appelée par la modale HP
 */
function updateHP() {
    const newHp = document.getElementById('new_hp').value;
    const characterId = getCurrentCharacterId();
    
    if (newHp && characterId) {
        manageHp(characterId, 'PJ', 'update', 0, parseInt(newHp));
    }
}

/**
 * Fonctions pour les actions rapides dans les modals
 */
function quickDamage(targetId, targetType, amount) {
    manageHp(targetId, targetType, 'damage', amount);
}

function quickHeal(targetId, targetType, amount) {
    manageHp(targetId, targetType, 'heal', amount);
}

function resetHp(targetId, targetType) {
    manageHp(targetId, targetType, 'reset');
}

function updateHpDirect(targetId, targetType, newHp) {
    manageHp(targetId, targetType, 'update', 0, newHp);
}

/**
 * Mettre à jour l'affichage des HP dans le modal
 */
function updateHpModalDisplay(currentHp, maxHp) {
    // Mettre à jour le texte d'affichage des HP
    const hpDisplayElement = document.getElementById('hp-display-text');
    const percentageElement = document.getElementById('hp-percentage');
    const directInputElement = document.getElementById('direct_hp_input');
    
    if (hpDisplayElement) {
        hpDisplayElement.textContent = currentHp + '/' + maxHp;
    }
    if (percentageElement) {
        const percentage = maxHp > 0 ? (currentHp / maxHp) * 100 : 100;
        percentageElement.textContent = Math.round(percentage * 10) / 10;
    }
    if (directInputElement) {
        directInputElement.value = currentHp;
        directInputElement.max = maxHp;
    }
    
    // Mettre à jour la barre de progression
    const progressBar = document.getElementById('hp-progress-bar');
    if (progressBar) {
        const percentage = maxHp > 0 ? (currentHp / maxHp) * 100 : 100;
        progressBar.style.width = percentage + '%';
        
        // Mettre à jour la classe de couleur avec des classes personnalisées
        progressBar.classList.remove('hp-full', 'hp-high', 'hp-medium', 'hp-low', 'hp-critical', 'bg-success', 'bg-warning', 'bg-danger');
        if (percentage >= 90) {
            progressBar.classList.add('hp-full'); // Vert vif pour HP au maximum (90%+)
        } else if (percentage >= 75) {
            progressBar.classList.add('hp-high'); // Vert-bleu pour HP élevés (75-89%)
        } else if (percentage >= 50) {
            progressBar.classList.add('hp-medium'); // Jaune-orange pour HP moyens (50-74%)
        } else if (percentage >= 25) {
            progressBar.classList.add('hp-low'); // Orange pour HP faibles (25-49%)
        } else {
            progressBar.classList.add('hp-critical'); // Rouge pour HP critiques (<25%)
        }
    }
}
