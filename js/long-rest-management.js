/**
 * Gestion unifiée des longs repos
 * Supporte les PNJ et personnages joueurs
 * Restaure : emplacements de sorts, rages, points de vie, etc.
 */

/**
 * Fonction principale pour effectuer un long repos
 */
function performLongRest(targetId, targetType) {
    if (!confirm(`Effectuer un long repos pour ce ${targetType === 'PNJ' ? 'PNJ' : 'personnage'} ?\n\nCela restaurera :\n- Les points de vie au maximum\n- Les emplacements de sorts\n- Les rages\n- Autres capacités spéciales`)) {
        return;
    }

    const data = {
        target_id: targetId,
        target_type: targetType
    };

    fetch('api/manage_long_rest.php', {
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
            
            // Mettre à jour l'affichage selon le type de cible
            if (targetType === 'PNJ') {
                updateNpcDisplayAfterLongRest(result);
            } else if (targetType === 'PJ') {
                updateCharacterDisplayAfterLongRest(result);
            }
        } else {
            showMessage(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors du long repos:', error);
        showMessage('Erreur lors de l\'effectuation du long repos.', 'error');
    });
}

/**
 * Fonctions wrapper pour chaque type de cible
 */
function performNpcLongRest(npcId) {
    performLongRest(npcId, 'PNJ');
}

function performCharacterLongRest(characterId) {
    performLongRest(characterId, 'PJ');
}

/**
 * Mettre à jour l'affichage après un long repos pour un PNJ
 */
function updateNpcDisplayAfterLongRest(result) {
    // Mettre à jour les points de vie si le modal HP est ouvert
    const hpModal = document.getElementById('hpModal');
    if (hpModal && hpModal.classList.contains('show')) {
        // Recharger la page pour mettre à jour tous les affichages
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
    
    // Mettre à jour l'affichage des sorts si présent
    updateSpellSlotsDisplay();
    
    // Mettre à jour l'affichage des rages si présent
    updateRageDisplay();
}

/**
 * Mettre à jour l'affichage après un long repos pour un personnage
 */
function updateCharacterDisplayAfterLongRest(result) {
    // Mettre à jour les points de vie
    updateHpDisplay();
    
    // Mettre à jour l'affichage des sorts
    updateSpellSlotsDisplay();
    
    // Mettre à jour l'affichage des rages
    updateRageDisplay();
    
    // Mettre à jour d'autres capacités spéciales
    updateSpecialAbilitiesDisplay();
}

/**
 * Mettre à jour l'affichage des emplacements de sorts
 */
function updateSpellSlotsDisplay() {
    // Remettre tous les emplacements à "disponible"
    document.querySelectorAll('.spell-slot').forEach(slot => {
        slot.classList.remove('used');
        slot.classList.add('available');
    });
    
    // Mettre à jour tous les compteurs
    document.querySelectorAll('.spell-slots-grid').forEach(grid => {
        if (typeof updateSpellSlotCount === 'function') {
            updateSpellSlotCount(grid.dataset.level);
        }
    });
    
    // Mettre à jour la barre des capacités
    if (typeof updatePreparedSpellsCount === 'function') {
        updatePreparedSpellsCount();
    }
}

/**
 * Mettre à jour l'affichage des rages
 */
function updateRageDisplay() {
    // Remettre toutes les rages à "disponible"
    document.querySelectorAll('.rage-slot').forEach(slot => {
        slot.classList.remove('used');
        slot.classList.add('available');
    });
    
    // Mettre à jour les compteurs de rages
    document.querySelectorAll('.rage-counter').forEach(counter => {
        const maxRages = counter.dataset.max || 0;
        counter.textContent = maxRages;
    });
}

/**
 * Mettre à jour l'affichage des capacités spéciales
 */
function updateSpecialAbilitiesDisplay() {
    // Mettre à jour les capacités qui se restaurent avec un long repos
    document.querySelectorAll('.special-ability').forEach(ability => {
        if (ability.dataset.restoresOnLongRest === 'true') {
            ability.classList.remove('used');
            ability.classList.add('available');
        }
    });
}

/**
 * Fonction utilitaire pour vérifier si une capacité se restaure avec un long repos
 */
function isLongRestAbility(abilityElement) {
    return abilityElement.dataset.restoresOnLongRest === 'true';
}

/**
 * Fonction pour obtenir le texte de confirmation personnalisé
 */
function getLongRestConfirmationText(targetType) {
    const baseText = `Effectuer un long repos pour ce ${targetType === 'PNJ' ? 'PNJ' : 'personnage'} ?`;
    const actionsText = `\n\nCela restaurera :\n- Les points de vie au maximum\n- Les emplacements de sorts\n- Les rages\n- Autres capacités spéciales`;
    
    return baseText + actionsText;
}
