/**
 * Gestion des groupes pour les fiches de personnages
 */

// Initialisation des événements
function initializeGroupEvents(npcId, npcType) {
    // --- Logique pour l'onglet Groupes (Event Delegation) ---
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'group_id') {
            const groupId = e.target.value;
            const rankSelect = document.getElementById('hierarchy_level');

            if (!rankSelect) return;

            if (!groupId) {
                rankSelect.innerHTML = `
                    <option value="1">Dirigeant</option>
                    <option value="2" selected>Membre</option>
                    <option value="3">Recrue</option>
                    <option value="4">Associé</option>
                `;
                return;
            }

            rankSelect.disabled = true;
            rankSelect.innerHTML = '<option>Chargement...</option>';

            fetch(`api/get_group_hierarchy.php?group_id=${groupId}`)
                .then(response => response.json())
                .then(data => {
                    rankSelect.disabled = false;
                    rankSelect.innerHTML = '';

                    if (data.success) {
                        const maxLevels = data.max_levels || 5;
                        const customLevels = data.levels || {};

                        const defaultTitles = {
                            1: 'Dirigeant',
                            2: 'Membre',
                            3: 'Recrue',
                            4: 'Associé'
                        };

                        for (let i = 1; i <= maxLevels; i++) {
                            let title = defaultTitles[i] || `Niveau ${i}`;
                            if (customLevels[i] && customLevels[i].title) {
                                title = customLevels[i].title;
                            }

                            const option = document.createElement('option');
                            option.value = i;
                            option.textContent = title;
                            if (i === 2) option.selected = true;
                            rankSelect.appendChild(option);
                        }
                    } else {
                        rankSelect.innerHTML = `
                        <option value="1">Dirigeant</option>
                        <option value="2" selected>Membre</option>
                        <option value="3">Recrue</option>
                        <option value="4">Associé</option>
                    `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    rankSelect.disabled = false;
                    rankSelect.innerHTML = `
                    <option value="1">Dirigeant</option>
                    <option value="2" selected>Membre</option>
                    <option value="3">Recrue</option>
                    <option value="4">Associé</option>
                `;
                });
        }
    });

    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'addToGroupForm') {
            e.preventDefault();
            const formData = new FormData(e.target);
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('api/add_group_member.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof load_tab_data === 'function') {
                            load_tab_data('groups', npcId, npcType);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erreur réseau');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
    });

    // Gestion de la soumission du formulaire d'édition de rang
    const editRankForm = document.getElementById('editRankForm');
    if (editRankForm) {
        editRankForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('api/update_group_member_rank.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalEl = document.getElementById('editRankModal');
                        if (window.bootstrap) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        const targetId = document.getElementById('edit_rank_target_id').value;
                        const targetType = document.getElementById('edit_rank_target_type').value;
                        if (typeof load_tab_data === 'function') {
                            load_tab_data('groups', targetId, targetType);
                        }
                    } else {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erreur réseau');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }

    // Event delegation pour les boutons d'édition de rang
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-rank');
        if (editBtn) {
            console.log('Edit button clicked, all dataset:', editBtn.dataset);
            const groupId = editBtn.dataset.groupId;
            const targetId = editBtn.dataset.targetId;
            const targetType = editBtn.dataset.targetType;
            const currentRank = editBtn.dataset.currentRank;

            console.log('Extracted values:', { groupId, targetId, targetType, currentRank });

            if (window.openEditRankModal) {
                window.openEditRankModal(groupId, targetId, targetType, parseInt(currentRank));
            }
        }

        // Event delegation pour les boutons de suppression
        const removeBtn = e.target.closest('.btn-remove-group');
        if (removeBtn) {
            const groupId = removeBtn.dataset.groupId;
            const targetId = removeBtn.dataset.targetId;
            const targetType = removeBtn.dataset.targetType;

            if (window.removeFromGroup) {
                window.removeFromGroup(groupId, targetId, targetType);
            }
        }
    });
}

// Fonction globale pour le retrait d'un groupe
window.removeFromGroup = function (groupId, targetId, targetType) {
    if (!confirm('Retirer ce personnage du groupe ?')) return;

    const formData = new FormData();
    formData.append('group_id', groupId);
    formData.append('target_id', targetId);
    formData.append('target_type', targetType);

    fetch('api/remove_group_member.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof load_tab_data === 'function') {
                    load_tab_data('groups', targetId, targetType);
                }
            } else {
                alert('Erreur: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur réseau');
        });
};

// Fonction pour ouvrir le modal d'édition de rang
window.openEditRankModal = function (groupId, targetId, targetType, currentRank) {
    console.log('openEditRankModal called with:', { groupId, targetId, targetType, currentRank });

    const groupIdInput = document.getElementById('edit_rank_group_id');
    const targetIdInput = document.getElementById('edit_rank_target_id');
    const targetTypeInput = document.getElementById('edit_rank_target_type');
    const rankSelect = document.getElementById('edit_hierarchy_level');
    const modalEl = document.getElementById('editRankModal');

    // Vérifier que tous les éléments existent
    if (!groupIdInput || !targetIdInput || !targetTypeInput || !rankSelect || !modalEl) {
        console.error('Éléments du modal introuvables');
        return;
    }

    groupIdInput.value = groupId;
    targetIdInput.value = targetId;
    targetTypeInput.value = targetType;

    rankSelect.innerHTML = '<option>Chargement...</option>';
    rankSelect.disabled = true;

    if (window.bootstrap) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Chargement des rangs
    const apiUrl = `api/get_group_hierarchy.php?group_id=${groupId}`;
    console.log('Fetching hierarchy from:', apiUrl);

    fetch(apiUrl)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API response:', data);
            rankSelect.disabled = false;
            rankSelect.innerHTML = '';

            if (data.success) {
                const maxLevels = data.max_levels || 5;
                const customLevels = data.levels || {};

                const defaultTitles = {
                    1: 'Dirigeant',
                    2: 'Membre',
                    3: 'Recrue',
                    4: 'Associé'
                };

                for (let i = 1; i <= maxLevels; i++) {
                    let title = defaultTitles[i] || `Niveau ${i}`;
                    if (customLevels[i] && customLevels[i].title) {
                        title = customLevels[i].title;
                    }

                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = title;
                    if (i === currentRank) option.selected = true;
                    rankSelect.appendChild(option);
                }
            } else {
                console.error('API returned error:', data.message);
                rankSelect.innerHTML = '<option value="">Erreur chargement</option>';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            rankSelect.innerHTML = '<option value="">Erreur chargement</option>';
        });
};
