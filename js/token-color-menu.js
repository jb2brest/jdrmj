// Gestion du menu contextuel pour changer la couleur des pions
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Token Color Menu] DOMContentLoaded - Script chargé');

    const colorMenu = document.getElementById('tokenColorMenu');
    if (!colorMenu) {
        console.error('[Token Color Menu] Element #tokenColorMenu non trouvé dans le DOM');
        console.log('[Token Color Menu] Vérification du HTML - recherche de commentaire debug...');

        // Chercher le commentaire de debug dans le body
        const bodyHTML = document.body.innerHTML;
        if (bodyHTML.includes('Menu contextuel non affiché')) {
            console.error('[Token Color Menu] Le menu est désactivé car canEdit = false');
        }
        return;
    }

    console.log('[Token Color Menu] Menu trouvé, initialisation...');

    let currentToken = null;

    // Gérer le clic droit sur les pions
    document.addEventListener('contextmenu', function (e) {
        const token = e.target.closest('.token');
        if (!token) {
            colorMenu.style.display = 'none';
            return;
        }

        console.log('[Token Color Menu] Clic droit sur pion détecté', {
            type: token.dataset.tokenType,
            id: token.dataset.entityId
        });

        e.preventDefault();
        currentToken = token;

        // Positionner le menu
        colorMenu.style.left = e.pageX + 'px';
        colorMenu.style.top = e.pageY + 'px';
        colorMenu.style.display = 'block';

        console.log('[Token Color Menu] Menu affiché à', e.pageX, e.pageY);
    });

    // Fermer le menu si on clique ailleurs
    document.addEventListener('click', function (e) {
        if (!colorMenu.contains(e.target) && !e.target.closest('.token')) {
            colorMenu.style.display = 'none';
        }
    });

    // Gérer la sélection d'une couleur prédéfinie
    const colorOptions = colorMenu.querySelectorAll('.color-option');
    console.log('[Token Color Menu] Nombre de couleurs prédéfinies:', colorOptions.length);

    colorOptions.forEach(option => {
        option.addEventListener('click', function (e) {
            e.stopPropagation();
            const color = this.dataset.color;
            console.log('[Token Color Menu] Couleur sélectionnée:', color);
            applyTokenColor(color);
        });
    });

    // Gérer la sélection d'une couleur personnalisée
    const customPicker = document.getElementById('customColorPicker');
    if (customPicker) {
        customPicker.addEventListener('change', function () {
            console.log('[Token Color Menu] Couleur personnalisée:', this.value);
            applyTokenColor(this.value);
        });
    }

    // Fonction pour mettre à jour le badge dans la liste
    function updateListBadge(tokenType, entityId, color) {
        // Construire le sélecteur pour trouver l'élément de liste correspondant
        let listItemSelector;

        switch (tokenType) {
            case 'player':
                listItemSelector = `.player-item`;
                break;
            case 'npc':
                listItemSelector = `.npc-item`;
                break;
            case 'monster':
                listItemSelector = `.monster-item`;
                break;
            default:
                console.warn('[Token Color Menu] Type de pion non supporté pour la mise à jour de liste:', tokenType);
                return;
        }

        // Trouver tous les éléments de ce type
        const listItems = document.querySelectorAll(listItemSelector);

        // Chercher celui qui correspond à l'entityId
        // Pour les joueurs, on cherche dans les data attributes ou dans les boutons/liens
        listItems.forEach(item => {
            let matches = false;

            if (tokenType === 'player') {
                // Pour les joueurs, chercher dans les boutons de suppression ou les liens
                const removeBtn = item.querySelector('button[onclick*="removePlayer"]');
                if (removeBtn && removeBtn.getAttribute('onclick').includes(`(${entityId})`)) {
                    matches = true;
                }
            } else if (tokenType === 'npc') {
                // Pour les NPCs, chercher dans les boutons
                const removeBtn = item.querySelector('button[onclick*="removeNpc"]');
                if (removeBtn && removeBtn.getAttribute('onclick').includes(`(${entityId})`)) {
                    matches = true;
                }
            } else if (tokenType === 'monster') {
                // Pour les monstres, chercher dans les boutons
                const visibilityBtn = item.querySelector('button[onclick*="toggleMonsterVisibility"]');
                if (visibilityBtn && visibilityBtn.getAttribute('onclick').includes(`(${entityId})`)) {
                    matches = true;
                }
            }

            if (matches) {
                // Trouver le badge de couleur dans cet élément
                const badge = item.querySelector('.position-absolute[style*="border-radius: 50%"]');
                if (badge) {
                    badge.style.background = color;
                    console.log('[Token Color Menu] Badge mis à jour dans la liste pour', tokenType, entityId);
                } else {
                    console.warn('[Token Color Menu] Badge non trouvé dans la liste pour', tokenType, entityId);
                }
            }
        });
    }

    // Fonction pour appliquer la couleur au pion
    function applyTokenColor(color) {
        if (!currentToken) {
            console.error('[Token Color Menu] Aucun pion sélectionné');
            return;
        }

        const tokenType = currentToken.dataset.tokenType;
        const entityId = currentToken.dataset.entityId;
        const placeId = window.placeId;

        console.log('[Token Color Menu] Application de la couleur', {
            color,
            tokenType,
            entityId,
            placeId
        });

        // Mettre à jour visuellement le pion immédiatement
        currentToken.style.borderColor = color;
        currentToken.dataset.borderColor = color;

        // Si le pion n'a pas d'image (background-color au lieu de background-image), mettre à jour le fond
        if (currentToken.style.backgroundColor) {
            currentToken.style.backgroundColor = color;
        }

        // Mettre à jour le badge dans la liste correspondante
        updateListBadge(tokenType, entityId, color);

        // Sauvegarder dans la base de données
        fetch('api/update_token_color.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                place_id: placeId,
                token_type: tokenType,
                entity_id: entityId,
                border_color: color
            })
        })
            .then(response => response.json())
            .then(data => {
                console.log('[Token Color Menu] Réponse API:', data);
                if (!data.success) {
                    console.error('[Token Color Menu] Erreur lors de la sauvegarde:', data.message);
                    alert('Erreur: ' + data.message);
                } else {
                    console.log('[Token Color Menu] Couleur sauvegardée avec succès');
                }
            })
            .catch(error => {
                console.error('[Token Color Menu] Erreur réseau:', error);
                alert('Erreur lors de la sauvegarde de la couleur');
            });

        // Fermer le menu
        colorMenu.style.display = 'none';
    }

    console.log('[Token Color Menu] Initialisation terminée - Prêt à recevoir les clics droits');
});
