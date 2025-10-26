/**
 * Gestion unifiée des points d'expérience
 * Supporte les PNJ, monstres et personnages joueurs
 */

/**
 * Fonction principale pour gérer les XP
 */
function manageXp(targetId, targetType, action, amount = 0, newXp = 0) {
    const data = {
        target_id: targetId,
        target_type: targetType,
        action: action,
        amount: amount,
        new_xp: newXp
    };

    fetch('api/manage_xp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showMessage(result.message, 'success');
            // Mettre à jour l'affichage des XP
            updateXpModalDisplay(result.current_xp);
        } else {
            showMessage(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la gestion des XP:', error);
        showMessage('Erreur lors de la gestion des points d\'expérience.', 'error');
    });
}

/**
 * Fonctions wrapper pour chaque type de cible
 */
function updateNpcXp(npcId, action, amount = 0, newXp = 0) {
    manageXp(npcId, 'PNJ', action, amount, newXp);
}

function updateMonsterXp(monsterId, action, amount = 0, newXp = 0) {
    manageXp(monsterId, 'monstre', action, amount, newXp);
}

function updateCharacterXp(characterId, action, amount = 0, newXp = 0) {
    manageXp(characterId, 'PJ', action, amount, newXp);
}

/**
 * Actions rapides pour ajouter des XP
 */
function quickAddXp(targetId, targetType, amount) {
    if (!amount || amount <= 0) {
        showMessage('Le montant doit être positif', 'error');
        return;
    }
    manageXp(targetId, targetType, 'add', amount);
}

/**
 * Actions rapides pour retirer des XP
 */
function quickRemoveXp(targetId, targetType, amount) {
    if (!amount || amount <= 0) {
        showMessage('Le montant doit être positif', 'error');
        return;
    }
    manageXp(targetId, targetType, 'remove', amount);
}

/**
 * Définir directement les XP
 */
function setXpDirect(targetId, targetType, newXp) {
    if (newXp < 0) {
        showMessage('Les points d\'expérience ne peuvent pas être négatifs', 'error');
        return;
    }
    manageXp(targetId, targetType, 'set', 0, newXp);
}

/**
 * Mettre à jour l'affichage des XP dans le modal
 */
function updateXpModalDisplay(currentXp) {
    // Mettre à jour l'affichage principal des XP
    const xpDisplayElement = document.getElementById('xp-display-text');
    const directInputElement = document.getElementById('direct_xp_input');
    
    if (xpDisplayElement) {
        xpDisplayElement.textContent = number_format(currentXp) + ' XP';
    }
    if (directInputElement) {
        directInputElement.value = currentXp;
    }
    
    // Mettre à jour l'affichage dans le profil si présent
    const profileXpDisplay = document.querySelector('.experience-display');
    if (profileXpDisplay) {
        profileXpDisplay.textContent = number_format(currentXp) + ' XP';
    }
}

/**
 * Fonction utilitaire pour formater les nombres (si elle n'existe pas déjà)
 */
if (typeof number_format === 'undefined') {
    function number_format(number) {
        return new Intl.NumberFormat('fr-FR').format(number);
    }
}
