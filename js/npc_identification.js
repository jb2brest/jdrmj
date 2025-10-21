/**
 * Gestion de l'identification des PNJ
 */

function toggleNpcIdentification(npcId, npcName, isIdentified) {
    const action = isIdentified ? 'Désidentifier' : 'Identifier';
    
    if (confirm(action + ' ' + npcName + ' pour les joueurs ?')) {
        // Afficher un indicateur de chargement
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Préparer les données
        const formData = new FormData();
        formData.append('npc_id', npcId);
        formData.append('place_id', window.location.search.match(/id=(\d+)/)[1]);
        
        // Faire l'appel AJAX vers l'endpoint dédié
        fetch('toggle_npc_identification.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Inclure les cookies de session
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
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

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script d\'identification des PNJ chargé');
});
